<?php
/* Copyright (C) 2022 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    cfdixml/class/cfdixml.class.php
 * \ingroup cfdixml
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */
dol_include_once('/cfdixml/class/facturalo.class.php');
dol_include_once('/cfdixml/lib/cfdixml.lib.php');
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
/**
 * Class Cfdixml
 */

class Cfdixml
{

    public $db;
    // const CANCEL_INVOICE = 'cancel_invoice';
    public $output;
    public $error;

    public function __construct($db)
    {

        $this->db = $db;
    }

    // Type
    // stamp_invoice
    // cancel_invoice //
    // cancel_invoice_r //reason
    // cancel_payment
    // stamp_payment

    public function insert_queue($id, $type, $reason = null)
    {

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cfdixml_queue (";
        $sql .= "fk_object,";
        $sql .= "type,";
        $sql .= "reason";
        $sql .= ") VALUES (";
        $sql .= $id . ",";
        $sql .= $reason ? "'" . $type . "'," : "'" . $type . "'";
        $sql .= $reason ? $reason : null;
        $sql .= ")";
        $resql = $this->db->query($sql);
    }

    public function queue()
    {
        global $user, $conf;

		$this->output = '';
		$this->error = '';
        
        $error = 0;

        $sql = " SELECT fk_object as id, type, reason  FROM " . MAIN_DB_PREFIX . "cfdixml_queue";
        $sql .= " WHERE active = 1";

        $resql = $this->db->query($sql);

        if ($resql) {
            $num = $this->db->num_rows($resql);
            if ($num > 0) {
                $i = 0;
                while ($i < $num) {
                    $obj =  $this->db->fetch_object($resql);

                    switch ($obj->type) {

                        case "cancel_invoice":
                            //Facturalo
                            $object = new Facture($this->db);
                            $object->fetch($obj->id);
                            if ($conf->global->CFDIXML_WS_MODE == 'TEST') $url = $conf->global->CFDIXML_WS_TEST;
                            if ($conf->global->CFDIXML_WS_MODE == 'PRODUCTION') $url = $conf->global->CFDIXML_WS_PRODUCTION;
                            $conexion = null;
                            $conexion = new Conexion($url);

                            $societe = new Societe($this->db);
                            $societe->fetch($object->socid);

                            $emisor =  getEmisor();
                            $receptor  = getReceptor($object, $societe);
                            $cerfile = file_get_contents($conf->global->CFDIXML_CER_FILE);
                            $keyfile = file_get_contents($conf->global->CFDIXML_KEY_FILE);

                            $xmlcanceled = $conexion->operacion_cancelar2(
                                $conf->global->CFDIXML_WS_TOKEN,
                                base64_encode($keyfile),
                                base64_encode($cerfile),
                                $conf->global->CFDIXML_CERKEY_PASS,
                                $object->array_options['options_cfdixml_UUID'],
                                $emisor['Rfc'],
                                $receptor['Rfc'],
                                $object->total_ttc,
                                '0' . $obj->reason //provisional
                            );

                            $xmlcanceled = json_decode($xmlcanceled);

                            if ($xmlcanceled->resultado == 'success') {
                                // echo '<pre>';print_r(json_decode($xmlcanceled));exit;
                                // exit;
                                $filedir = $conf->facture->multidir_output[$object->entity] . '/' . dol_sanitizeFileName($object->ref);

                                $file_xml = fopen($filedir . "/ACUSE_CANCELACION_" . $object->ref . '_' . $object->array_options['options_cfdixml_UUID'] . ".xml", "w");
                                fwrite($file_xml, utf8_encode($xmlcanceled->acuse));
                                fclose($file_xml);

                                //Provisional FIX
                                $fecha_emision = date('Y-m-d H:i:s');
                                $fecha_emision = str_replace(" ", "T", $fecha_emision);
                                $sql = "UPDATE " . MAIN_DB_PREFIX . "facture_extrafields ";
                                $sql .= " SET cfdixml_fechacancelacion = '" . $fecha_emision . "'";
                                $xmlcanceled->codigo ? $sql .= ", cfdixml_codigocancelacion = '" . $xmlcanceled->codigo . "'" : null;
                                $sql .= ", cfdixml_xml_cancel = \"" . base64_encode($xmlcanceled->acuse) . "\"";
                                $sql .= " WHERE fk_object = " . $object->id;



                                $invoice = new Facture($this->db);
                                $invoice->fetch($object->id);

                                $result = $invoice->setCanceled($user, 'cfdi_cancel', 'Factura cancelada ante el sat');
                                if ($result > 0) {
                                    dol_syslog("Factura " . $object->ref . " con UUID " . $object->array_options['options_cfdixml_UUID'] . ' cancelada con éxico');
                                    $update = "UPDATE " . MAIN_DB_PREFIX . "cfdixml_queue";
                                    $update .= " SET active = 0 WHERE fk_object = " . $obj->id;
                                    $this->db->query($update);
                                }
                            } else {
                                dol_syslog("Factura " . $object->ref . " con UUID " . $object->array_options['options_cfdixml_UUID'] . ' ERROR '.$xmlcanceled->codigo.': '.$xmlcanceled->mensaje );

                                $update = "UPDATE " . MAIN_DB_PREFIX . "cfdixml_queue";
                                $update .= " SET note = '".$xmlcanceled->codigo.": ".$xmlcanceled->mensaje."'";
                                $update .= " WHERE fk_object = " . $obj->id;
                                $this->db->query($update);
                                $error++;
                            }

                            break;
                    }
                    $i++;
                }
            }
        }

        if ($error > 0) {
            $this->output = "quedaron elementos por procesar";
            return 0;
        } else {
            $this->error = "todos los elementos procesados";
            return $error;
        }
    }
}
