<?php
/* Copyright (C) 2022 Alice Adminson <aadminson@example.com>
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
 * along with this program.  If not, see<https://www.gnu.org/licenses/>
.
 */

/**
 * \file    cfdixml/lib/cfdixml.lib.php
 * \ingroup cfdixml
 * \brief   Library files with common functions for Cfdixml
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function cfdixmlAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("cfdixml@cfdixml");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/cfdixml/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/cfdixml/admin/taxes.php", 1);
	$head[$h][1] = $langs->trans("Taxes");
	$head[$h][2] = 'taxes';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/cfdixml/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/cfdixml/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array( // 'entity:+tabname:Title:@cfdixml:/cfdixml/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array( // 'entity:-tabname:Title:@cfdixml:/cfdixml/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'cfdixml@cfdixml');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'cfdixml@cfdixml', 'remove');

	return $head;
}

function getRegimenFiscal(String $code = null)
{

	if (is_null($code)) setEventMessage('El receptor no tiene régimen fiscal asignado', 'errors');
	if (is_null($code)) return;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

	$regimen_emisor = getFormeJuridiqueLabel($code);
	$regimen_emisor = explode(' - ', $regimen_emisor);

	return $regimen_emisor[0];
}

function getDictionaryValues(String $table)
{

	global $db;

	$sql = "SELECT code, label FROM " . MAIN_DB_PREFIX . "c_cfdixml_" . $table;
	$sql .= " WHERE active = 1";

	$resql = $db->query($sql);
	if (!$resql) return -1; //Table error

	$num = $db->num_rows($resql);
	if ($num <= 0) return -2; // No records

	$data = [];
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		$data[$obj->code] = $obj->label;
		$i++;
	}

	return $data;
}

function getComprobanteAtributos(Facture $object, String $fecha_emision = null)
{

	global $db, $conf;


	// if (!$fecha_emision) {
	// 	$fecha_emision = date('Y-m-d H:i:s');
	// 	$fecha_emision = str_replace(" ", "T", $fecha_emision);
	// }

	$descuento = 0;
	$lines = count($object->lines);
	// echo $lines;exit;
	for ($i = 0; $i < $lines; $i++) {
		if (!empty($object->lines[$i]->remise_percent) || $object->lines[$i]->remise_percent > 0) {
			// Obtener el porcentaje de descuento
			$discount_percent = (float) $object->lines[$i]->remise_percent;

			// Calcular el descuento por artículo
			$original_price = (float) ($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice);
			$line_discount = ($original_price * $discount_percent / 100) * (float)$object->lines[$i]->qty;

			// Sumar el descuento al total de descuentos
			$descuento += abs($line_discount);;
		}
	}
	// echo $descuento;exit;

	if (strpos($object->ref, '-') !==	false) {

		$ref = explode("-", $object->ref);
	} else {
		$ref[0] = '';
		$ref[1] = $object->ref;
	}

	if ($object->type == Facture::TYPE_STANDARD) {
		$tipoComprobante = "I";
	}

	if ($object->type == Facture::TYPE_REPLACEMENT) {
		$tipoComprobante = "I";
	}

	if ($object->type == Facture::TYPE_DEPOSIT) {
		$tipoComprobante = "I";
	}

	if ($object->type == Facture::TYPE_CREDIT_NOTE) {
		$tipoComprobante = "E";
	}
	$auxsubtotal = abs(round($conf->multicurrency->enabled ? $object->multicurrency_total_ht : $object->total_ht, 2));
	$data = [
		'Serie' => $ref[0],
		'Folio' => $ref[1],
		'Fecha' => $fecha_emision,
		'SubTotal' => number_format($auxsubtotal, 2, '.', ''),
		'Total' => number_format(($conf->multicurrency->enabled ? $object->multicurrency_total_ttc : $object->total_ttc), 2, '.', ''),
		'TipoDeComprobante' => $tipoComprobante,
		'LugarExpedicion' => $conf->global->MAIN_INFO_SOCIETE_ZIP,
		'MetodoPago' => $object->array_options['options_cfdixml_metodopago'],
		'Exportacion' => $object->array_options['options_cfdixml_exportacion'],
		'Moneda' => $conf->multicurrency->enabled ? $object->multicurrency_code : $conf->currency,
	];

	if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) {
		$tx = 1 / (float)$object->multicurrency_tx;
		$data['TipoCambio'] = number_format($tx, 4,".","");
	}

	$data['FormaPago'] = $object->mode_reglement_code ? $object->mode_reglement_code : GETPOST('forma_pago');
	$data['CondicionesDePago'] = $object->cond_reglement_code ? $object->cond_reglement_code : GETPOST('metodo_pago');
	$descuento ? $data['Descuento'] = number_format($descuento, 2,".","") : null;

	if ($tipoComprobante == 'E') {

		$cfdirelacionado = new Facture($db);
		$cfdirelacionados = explode(',', $object->array_options['options_cfdixml_doctorel']);
		$i = 0;
		foreach ($cfdirelacionados as $idcfdi) {
			$cfdirelacionado->fetch($idcfdi);
			$data['CfdiRelacionados'] = [
				'TipoRelacion' => '01',
				'CfdiRelacionado' => [
					'UUID' => $cfdirelacionado->array_options['options_cfdixml_UUID']
				]
			];
			$i++;
		}
	}


	return $data;
}

function getConditionsPayments(array $array)
{

	$data = [];
	foreach ($array as $key => $value) {

		$data[$value['code']] = $value['label'];
	}
	return $data;
}

function getTypesPayments(array $array)
{

	$data = [];
	foreach ($array as $key => $value) {
		if ($value['active'] == '1') {
			$data[$value['code']] = $value['label'];
		}
	}
	return $data;
}

function getEmisor()
{

	global $conf;

	$data = [
		'Rfc' => $conf->global->MAIN_INFO_SIREN,
		'Nombre' => strtoupper($conf->global->MAIN_INFO_SOCIETE_NOM),
		'RegimenFiscal' => getRegimenFiscal($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE)
	];

	return $data;
}

function getReceptor(Facture $object, Societe $societe)
{

	global $db, $langs, $conf;
	$data = [];

	if ($societe->idprof1 == "XAXX010101000") {
		$data = [
			'Rfc' => "XAXX010101000",
			'Nombre' => strtoupper('PUBLICO EN GENERAL'),
			'DomicilioFiscalReceptor' => $conf->global->MAIN_INFO_SOCIETE_ZIP,
			'RegimenFiscalReceptor' => '616',
			'UsoCFDI' => $object->array_options['options_cfdixml_usocfdi']
		];

		return $data;
	}

	if ($societe->country_code != 'MX') {
		$sql = "SELECT code_iso FROM " . MAIN_DB_PREFIX . "c_country WHERE code = '" . $societe->country_code . "' LIMIT 1";
		$result = $db->query($sql);
		$obj = $db->fetch_object($result);
		$data = [
			'Rfc' => "XEXX010101000",
			'Nombre' => strtoupper($societe->name),
			'DomicilioFiscalReceptor' => $conf->global->MAIN_INFO_SOCIETE_ZIP,
			'RegimenFiscalReceptor' => '616',
			'UsoCFDI' => $object->array_options['options_cfdixml_usocfdi']
		];

		return $data;
	}

	$data = [

		'Rfc' => $societe->idprof1,
		'Nombre' => strtoupper($societe->name),
		'DomicilioFiscalReceptor' => $societe->zip,
		'RegimenFiscalReceptor' => getRegimenFiscal($societe->forme_juridique_code),
		'UsoCFDI' => $object->array_options['options_cfdixml_usocfdi']

	];

	return $data;
}


function getConceptos(Facture $object)
{

	global $conf, $db;

	$objimp = TRUE;
	$object->fetch_thirdparty();
	// echo '<pre>';print_r($object->thirdparty);exit;
	if (empty($object->thirdparty)) $objimp = FALSE;

	$data = [];
	for ($i = 0; $i < count($object->lines); $i++) {
		$descuento = 0;
		if (empty($object->lines[$i]->fk_product)) {

			if ($object->lines[$i]->remise_percent > 0) {
				$descuento = (100 - round($object->lines[$i]->remise_percent, 2)) / 100;
				if($conf->multicurrency->enabled){
						$descuento = (round($object->lines[$i]->multicurrency_subprice, 2) - (round($object->lines[$i]->multicurrency_subprice, 2) * $descuento));
				}else{
						$descuento = round($object->lines[$i]->subprice, 2)               - (round($object->lines[$i]->subprice, 2) * $descuento);
				}
				$descuento = $descuento * $object->lines[$i]->qty;
				$data[$i] = ['Descuento' => number_format($descuento, 2, '.', '')];		//JGG Solo se incluye si existe descuento
			}
			$data[$i] = [
				'Descripcion' => mb_convert_encoding(strip_tags($object->lines[$i]->desc), 'UTF-8'),
				'Cantidad' => $object->lines[$i]->qty,
				'ValorUnitario' => number_format(abs($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice), 2, '.', ''),
//1				'Importe' =>  	   number_format(abs(($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht) - $descuento), 2, ".", ""),
				'Importe' =>  	   number_format(abs(($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht) + $descuento), 2, ".", ""),
				'ClaveUnidad' => $object->lines[$i]->array_options['options_cfdixml_umed'],
				//'NoIdentificacion' => , // Serial Number
				'ClaveProdServ' => $object->lines[$i]->array_options['options_cfdixml_claveprodserv'],
				// 'CuentaPredial' => , //TODO
				'ObjetoImp' => $objimp ? $object->lines[$i]->array_options['options_cfdixml_objetoimp'] : '01',
			];
		} else {

			// include DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			$product =  new Product($db);
			$product->fetch($object->lines[$i]->fk_product);
			// echo $object->lines[$i]->product_label;

			if ($object->lines[$i]->remise_percent > 0) {
				$desctotal = (float)($object->lines[$i]->qty) * (float)($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice);
				$descuento = ($desctotal * (float)($object->lines[$i]->remise_percent)) / 100;
			}
			$data[$i] = [
				'Descripcion' => mb_convert_encoding(strip_tags($product->label), 'UTF8'),
				'Cantidad' => $object->lines[$i]->qty,
				'ValorUnitario' => number_format(abs($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice), 2, '.', ''),
//1				'Importe' =>  number_format(abs(($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht) - $descuento), 2,".",""),
				'Importe' =>  number_format(abs(($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht) + $descuento), 2,".",""),
				'ClaveUnidad' => $product->array_options['options_cfdixml_umed'],
				//'NoIdentificacion' => , // Serial Number
				'ClaveProdServ' => $product->array_options['options_cfdixml_claveprodserv'],
				// 'CuentaPredial' => , //TODO
				'ObjetoImp' => $objimp ? $product->array_options['options_cfdixml_objetoimp'] : '01',
			];
		}

		$resico = checkReceptor($object);
		if ($resico) {
			$var_codes = '001';
			$descuento ? $data[$i]['Descuento'] = number_format($descuento,2,".","") : null;
			$data[$i]['Impuestos']['Traslados'] = [
				'Base' => number_format(abs($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht), 2, '.', ''),
				'Impuesto' => $object->lines[$i]->vat_src_code,
				'TipoFactor' => 'Tasa',
				'TasaOCuota' => number_format($object->lines[$i]->tva_tx / 100, 6,".",""),
				'Importe' => number_format(abs(($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_total_tva : $object->lines[$i]->total_tva) ), 2,".",""),
			];

			//Calc revenuestamp by line;
			$sql = "SELECT f.revenuestamp FROM " . MAIN_DB_PREFIX . "facture f where rowid = " . $object->id;
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
			}
			$percent = (float)price2num(($conf->multicurrency->enabled) ? (abs($obj->revenuestamp) * 100) / $object->multicurrency_total_ht : (abs($obj->revenuestamp) * 100) / $object->total_ht,'MT');

			//$percent = (abs($obj->revenuestamp) * 100) / $conf->multicurrency->enabled ? $object->multicurrency_total_ht : $object->total_ht;
			$total_ht_line = ($conf->multicurrency->enabled ? ($object->lines[$i]->multicurrency_total_ht * $percent) : ($object->lines[$i]->total_ht * $percent)) / 100;

			$data[$i]['Impuestos']['Retencion'] = [
				'Base' => number_format(abs($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht), 2,".",""),
				'Impuesto' => $var_codes,
				'TipoFactor' => 'Tasa',
				'TasaOCuota' => number_format($percent / 100, 6,".",""),
				'Importe' => number_format(abs($total_ht_line), 2,".",""),
			];
			return $data;
		}
		// echo $descuento;exit;
		$descuento ? $data[$i]['Descuento'] = number_format($descuento, 2,".","") : null;
		$descuento ? $data[$i]['Importe'] = number_format($data[$i]['Importe'], 2,".","") : $data[$i]['Importe']; 	//JGG 

		if ($objimp) {
			// Calcular y formatear los valores antes de asignarlos al array
			$base = abs($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht);
			$importe = abs($conf->multicurrency->enabled ? $object->lines[$i]->multicurrency_total_tva: $object->lines[$i]->total_tva) ;
			$tasa = (float)$object->lines[$i]->tva_tx / 100;

			// Asignar los valores al array
			$data[$i]['Impuestos']['Traslados'] = [
				'Base' => number_format($base,2,".",""),
				'Impuesto' => $object->lines[$i]->vat_src_code,
				'TipoFactor' => 'Tasa',
				'TasaOCuota' => number_format($tasa,6,".",""),
				'Importe' => number_format($importe,2,".",""),
			];
		}
	}
	return $data;
}


/**
 * Optimización
 */
function getPayments(Paiement $object)
{
	global $db, $conf;
	$data = [];
	$invoice = new Facture($db);
	$tipocambio = null;

	$fecha_pago = dol_print_date($object->date, '%Y-%m-%d %H:%M:%S');
	$fecha_pago = str_replace(" ", "T", $fecha_pago);
	$totalpago_ht = 0;
	$totalpago = 0;
	$totalpagoiva = 0;
	$moneda = '';

	if ($object->amount == $object->multicurrency_amount) {
		$moneda = $conf->currency;
	}

	$data[0] = [
		'pago' => [
			'FechaPago' => $fecha_pago,
			'FormaDePagoP' => $object->type_code,

		]
	];

	$data[0]['pago']['TipoCambioP'] = $tipocambio ?  $tipocambio : 1;

	$sql = 'SELECT f.rowid as facid';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'paiement_facture as pf,' . MAIN_DB_PREFIX . 'facture as f,' . MAIN_DB_PREFIX . 'societe as s';
	$sql .= ' WHERE pf.fk_facture = f.rowid';
	$sql .= ' AND f.fk_soc = s.rowid';
	$sql .= ' AND f.entity IN (' . getEntity('invoice') . ')';
	$sql .= ' AND pf.fk_paiement = ' . ((int) $object->id);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num > 0) {
			$i = 0;

			$traslado_totalpago_ht = 0;
			$traslado_totalpago  = 0;
			$traslado_totalpagoiva = 0;
			$traslado_c_tasaocuotaP = 0;

			while ($i < $num) {
				$numparcialidad = 0;
				$objetoimp = null;

				$obj = $db->fetch_object($resql);
				$invoice->fetch($obj->facid);
				if (empty($moneda)) {

					$data[0]['pago']['MonedaP'] = $invoice->multicurrency_code;
					$data[0]['pago']['Monto'] = round($object->multicurrency_amount, 2);
					$totalpago_ht = price2num($invoice->multicurrency_total_ht, 'MT');
					$totalpago = price2num($invoice->multicurrency_total_ttc, 'MT');
					$totalpagoiva = price2num($invoice->multicurrency_total_tva, 'MT');

				} else {

					$data[0]['pago']['Monto'] = round($object->amount, 2);
					$data[0]['pago']['MonedaP'] = $conf->currency;
					$totalpago_ht = price2num($invoice->total_ht, 'MT');
					$totalpago = price2num($invoice->total_ttc, 'MT');
					$totalpagoiva = price2num($invoice->total_tva, 'MT');

				}

				$objetoimp = '02';
				$impuesto = '002';

				$traslado_totalpago_ht 	= $traslado_totalpago_ht +  $totalpago_ht;
				$traslado_totalpago 	= $traslado_totalpago +  $totalpago;
				$traslado_totalpagoiva 	= $traslado_totalpagoiva +  $totalpagoiva;

				$sql = "SELECT count(*) as nb from " . MAIN_DB_PREFIX . "paiement_facture where fk_facture = " . $obj->facid;
				$sql .= " AND fk_paiement <> " . $object->id;
				$resqlpay = $db->query($sql);

				if ($resqlpay) {
					$objtotalpay = $db->fetch_object($resqlpay);

					if ($objtotalpay->nb > 0) {
						// TODO: verify if all payments before this have's UUID
					} else {
						$sql = "SELECT amount, multicurrency_amount from " . MAIN_DB_PREFIX . "paiement_facture where fk_facture = " . $obj->facid;
						$sql .= " AND fk_paiement = " . $object->id;
						// echo $sql;exit;
						$resqlp = $db->query($sql);
						if ($resqlp) {
							$objpay = $db->fetch_object($resqlp);
							if ($objpay) {
								$numparcialidad++;
								if ($moneda != $conf->currency) {
									$saldoanterior = round($invoice->multicurrency_total_ttc, 2);
									$totalpagado = $objpay->multicurrency_amount + ($objpay->multicurrency_amount - $invoice->multicurrency_total_ttc);
								} else {
									$saldoanterior = round($invoice->total_ttc, 2);
									$totalpagado = $objpay->amount + ($objpay->amount - $invoice->total_ttc);
								}
								// Calculamos el saldo insoluto
								$saldoinsoluto = max($saldoanterior - $totalpagado, 0); // Nos aseguramos de que sea al menos cero
							}
						}
					}

					$data[0]['pago']['DoctoRelacionado'][$i] = [
						'IdDocumento' => $invoice->array_options['options_cfdixml_UUID'],
						'MonedaDR' => $invoice->multicurrency_code,
						'NumParcialidad' => $numparcialidad,
						'ImpSaldoAnt' => $saldoanterior,
						'ImpPagado' => $totalpagado,
						'ImpSaldoInsoluto' => $saldoinsoluto,
						'MetodoDePagoDR' => $invoice->array_options['options_cfdixml_metodopago'],
						'ObjetoImpDR' => $objetoimp
					];

					if ($conf->global->CFDIXML_PAGO20_ACTIVE) {
						$data[0]['pago']['DoctoRelacionado'][$i]['EquivalenciaDR'] = $tipocambio ?  number_format($tipocambio, 10,".","") : number_format(1, 10,".","");
					} else {
						$data[0]['pago']['DoctoRelacionado'][$i]['EquivalenciaDR'] = $tipocambio ?  $tipocambio : 1;
					}


					if ($moneda != $conf->currency) {

						$c_tasaocuota = number_format($invoice->multicurrency_total_tva / $invoice->multicurrency_total_ht, 2, ".","");
						$c_tasaocuota = number_format($c_tasaocuota, 6,".","");
						$data[0]['pago']['DoctoRelacionado'][$i]['ImpuestosDR'][$i] = [
							'BaseDR' => number_format($invoice->multicurrency_total_ht, 2,".",""),
							'ImporteDR' => number_format($invoice->multicurrency_total_tva, 2,".",""),
							'ImpuestoDR' => $impuesto,
							'TasaOCuotaDR' => $c_tasaocuota,
							'TipoFactorDR' => 'Tasa',
						];
						$c_tasaocuotaP =  number_format($invoice->multicurrency_total_tva / $invoice->multicurrency_total_ht, 2,".","");
					} else {
						$c_tasaocuota = number_format($invoice->total_tva / $invoice->total_ht, 2,".","");
						$c_tasaocuota = number_format($c_tasaocuota, 6,".","");
						$data[0]['pago']['DoctoRelacionado'][$i]['ImpuestosDR'][$i] = [
							'BaseDR' => number_format($invoice->total_ht, 2,".",""),
							'ImporteDR' => number_format($invoice->total_tva, 2,".",""),
							'ImpuestoDR' => $impuesto,
							'TasaOCuotaDR' => $c_tasaocuota,
							'TipoFactorDR' => 'Tasa',
						];
						$c_tasaocuotaP =  number_format($invoice->total_tva / $invoice->total_ht, 2,".","");
					}


					$c_tasaocuotaP = number_format($c_tasaocuotaP, 6,".","");
					$traslado_c_tasaocuotaP = $traslado_c_tasaocuotaP +  $c_tasaocuotaP;
				}
				$i++;
			}
		}
	}

	$data[0]['pago']['Impuestosp']['Traslados'][0] = [
		'BaseP' => number_format($traslado_totalpago_ht,2,".",""),
		'ImporteP' => number_format($traslado_totalpagoiva,2,".",""),
		'ImpuestoP' => $impuesto,
		'TasaOCuotaP' => $c_tasaocuotaP,
		'TipoFactorP' => 'Tasa',
	];

	if (number_format($c_tasaocuota, 2) == 0.16) {
		$data[0]['totales'][0] = [
			'MontoTotalPagos' => round($traslado_totalpago, 2),
			'TotalTrasladosBaseIVA16' => round($traslado_totalpago_ht, 2),
			'TotalTrasladosImpuestoIVA16' => round($traslado_totalpagoiva, 2)
		];
	}

	if (number_format($c_tasaocuota, 2) == 0.08) {
		$data[0]['totales'][0] = [
			'MontoTotalPagos' => round($traslado_totalpago, 2),
			'TotalTrasladosBaseIVA8' => round($totalpago_ht, 2),
			'TotalTrasladosImpuestoIVA8' => round($totalpagoiva, 2)
		];
	}

	if (number_format($c_tasaocuota, 2) == 0.00) {
		$data[0]['totales'][0] = [
			'MontoTotalPagos' => round($traslado_totalpago, 2),
			'TotalTrasladosBaseIVA0' => round($totalpago_ht, 2),
			'TotalTrasladosImpuestoIVA0' => round($totalpagoiva, 2)
		];
	}

	return $data;
}




function createFromCurrent(User $user, Facture $object)
{
	global $db, $conf;

	// Source invoice load
	$facture = new Facture($db);

	// Retrieve all extrafield
	// fetch optionals attributes and labels
	$object->fetch_optionals();

	if (!empty($object->array_options)) {
		$extrafields['extrafields'] = $object->array_options;
		foreach ($extrafields as $extrafield) {

			if ($extrafield['options_cfdixml_usocfdi']) {
				$facture->array_options['options_cfdixml_usocfdi'] = $extrafield['options_cfdixml_usocfdi'];
			}
			if ($extrafield['options_cfdixml_metodopago']) {
				$facture->array_options['options_cfdixml_metodopago'] = $extrafield['options_cfdixml_metodopago'];
			}
			if ($extrafield['options_cfdixml_exportacion']) {
				$facture->array_options['options_cfdixml_exportacion'] = $extrafield['options_cfdixml_exportacion'];
			}
			if ($extrafield['options_cfdixml_doctorel']) {
				$facture->array_options['options_cfdixml_doctorel'] = $object->id;
			}
			// $facture->array_options
		}
		//Borrar datos relativos al timbrado
		// $facture->array_options = $object->array_options;
	}

	foreach ($object->lines as &$line) {
		$line->fetch_optionals(); //fetch extrafields
	}

	$facture->fk_facture_source = $object->id;
	$facture->type 			    = 1;
	$facture->socid 		    = $object->socid;
	$facture->date              = $object->date;
	$facture->date_pointoftax   = $object->date_pointoftax;
	$facture->note_public       = $object->note_public;
	$facture->note_private      = $object->note_private;
	$facture->ref_client        = $object->ref_client;
	$facture->modelpdf          = $object->model_pdf; // deprecated
	$facture->model_pdf         = $object->model_pdf;
	$facture->fk_project        = $object->fk_project;
	$facture->cond_reglement_id = $object->cond_reglement_id;
	$facture->mode_reglement_id = $object->mode_reglement_id;
	$facture->remise_absolue    = $object->remise_absolue;
	$facture->remise_percent    = $object->remise_percent;

	$facture->origin            = $object->origin;
	$facture->origin_id         = $object->origin_id;

	$facture->lines = $object->lines; // Array of lines of invoice
	$facture->situation_counter = $object->situation_counter;
	$facture->situation_cycle_ref = $object->situation_cycle_ref;
	$facture->situation_final = $object->situation_final;

	$facture->retained_warranty = $object->retained_warranty;
	$facture->retained_warranty_fk_cond_reglement = $object->retained_warranty_fk_cond_reglement;
	$facture->retained_warranty_date_limit = $object->retained_warranty_date_limit;

	$facture->fk_user_author = $user->id;


	// Loop on each line of new invoice
	// foreach ($facture->lines as $i => $tmpline) {
	// 	$facture->lines[$i]->fk_prev_id = $this->lines[$i]->rowid;
	// 	if ($invertdetail) {
	// 		$facture->lines[$i]->subprice  = -$facture->lines[$i]->subprice;
	// 		$facture->lines[$i]->total_ht  = -$facture->lines[$i]->total_ht;
	// 		$facture->lines[$i]->total_tva = -$facture->lines[$i]->total_tva;
	// 		$facture->lines[$i]->total_localtax1 = -$facture->lines[$i]->total_localtax1;
	// 		$facture->lines[$i]->total_localtax2 = -$facture->lines[$i]->total_localtax2;
	// 		$facture->lines[$i]->total_ttc = -$facture->lines[$i]->total_ttc;
	// 		$facture->lines[$i]->ref_ext = '';
	// 	}
	// }

	// dol_syslog(get_class($this)."::createFromCurrent invertdetail=".$invertdetail." socid=".$this->socid." nboflines=".count($facture->lines));

	$facid = $facture->create($user);
	if ($facid <= 0) {
		$object->error = $facture->error;
		$object->errors = $facture->errors;
	} elseif ($object->type == Facture::TYPE_SITUATION && !empty($conf->global->INVOICE_USE_SITUATION)) {
		$object->fetchObjectLinked('', '', $object->id, 'facture');

		foreach ($object->linkedObjectsIds as $typeObject => $Tfk_object) {
			foreach ($Tfk_object as $fk_object) {
				$facture->add_object_linked($typeObject, $fk_object);
			}
		}

		$facture->add_object_linked('facture', $object->fk_facture_source);
	}

	return $facid;
}

function num2letras($num, $fem = true, $dec = true)
{
	$matuni[2]  = "dos";
	$matuni[3]  = "tres";
	$matuni[4]  = "cuatro";
	$matuni[5]  = "cinco";
	$matuni[6]  = "seis";
	$matuni[7]  = "siete";
	$matuni[8]  = "ocho";
	$matuni[9]  = "nueve";
	$matuni[10] = "diez";
	$matuni[11] = "once";
	$matuni[12] = "doce";
	$matuni[13] = "trece";
	$matuni[14] = "catorce";
	$matuni[15] = "quince";
	$matuni[16] = "dieciseis";
	$matuni[17] = "diecisiete";
	$matuni[18] = "dieciocho";
	$matuni[19] = "diecinueve";
	$matuni[20] = "veinte";
	$matunisub[2] = "dos";
	$matunisub[3] = "tres";
	$matunisub[4] = "cuatro";
	$matunisub[5] = "quin";
	$matunisub[6] = "seis";
	$matunisub[7] = "sete";
	$matunisub[8] = "ocho";
	$matunisub[9] = "nove";

	$matdec[2] = "veint";
	$matdec[3] = "treinta";
	$matdec[4] = "cuarenta";
	$matdec[5] = "cincuenta";
	$matdec[6] = "sesenta";
	$matdec[7] = "setenta";
	$matdec[8] = "ochenta";
	$matdec[9] = "noventa";
	$matsub[3]  = 'mill';
	$matsub[5]  = 'bill';
	$matsub[7]  = 'mill';
	$matsub[9]  = 'trill';
	$matsub[11] = 'mill';
	$matsub[13] = 'bill';
	$matsub[15] = 'mill';
	$matmil[4]  = 'millones';
	$matmil[6]  = 'billones';
	$matmil[7]  = 'de billones';
	$matmil[8]  = 'millones de billones';
	$matmil[10] = 'trillones';
	$matmil[11] = 'de trillones';
	$matmil[12] = 'millones de trillones';
	$matmil[13] = 'de trillones';
	$matmil[14] = 'billones de trillones';
	$matmil[15] = 'de billones de trillones';
	$matmil[16] = 'millones de billones de trillones';

	$num = trim((string)@$num);
	if ($num[0] == '-') {
		$neg = 'menos ';
		$num = substr($num, 1);
	} else
		$neg = '';
	while ($num[0] == '0') $num = substr($num, 1);
	if ($num[0] < '1' or $num[0] > 9) $num = '0' . $num;
	$zeros = true;
	$punt = false;
	$ent = '';
	$fra = '';
	for ($c = 0; $c < strlen($num); $c++) {
		$n = $num[$c];
		if (!(strpos(".,'''", $n) === false)) {
			if ($punt) break;
			else {
				$punt = true;
				continue;
			}
		} elseif (!(strpos('0123456789', $n) === false)) {
			if ($punt) {
				if ($n != '0') $zeros = false;
				$fra .= $n;
			} else

				$ent .= $n;
		} else

			break;
	}
	$ent = '     ' . $ent;
	if ($dec and $fra and !$zeros) {
		$fin = ' punto';
		for ($n = 0; $n < strlen($fra); $n++) {
			if (($s = $fra[$n]) == '0')
				$fin .= ' cero';
			elseif ($s == '1')
				$fin .= $fem ? ' una' : ' un';
			else
				$fin .= ' ' . $matuni[$s];
		}
	} else
		$fin = '';
	if ((int)$ent === 0) return 'Cero ' . $fin;
	$tex = '';
	$sub = 0;
	$mils = 0;
	$neutro = false;
	while (($num = substr($ent, -3)) != '   ') {
		$ent = substr($ent, 0, -3);
		if (++$sub < 3 and $fem) {
			$matuni[1] = 'una';
			$subcent = 'as';
		} else {
			$matuni[1] = $neutro ? 'un' : 'uno';
			$subcent = 'os';
		}
		$t = '';
		$n2 = substr($num, 1);
		if ($n2 == '00') {
		} elseif ($n2 < 21)
			$t = ' ' . $matuni[(int)$n2];
		elseif ($n2 < 30) {
			$n3 = $num[2];
			if ($n3 != 0) $t = 'i' . $matuni[$n3];
			$n2 = $num[1];
			$t = ' ' . $matdec[$n2] . $t;
		} else {
			$n3 = $num[2];
			if ($n3 != 0) $t = ' y ' . $matuni[$n3];
			$n2 = $num[1];
			$t = ' ' . $matdec[$n2] . $t;
		}
		$n = $num[0];
		if ($n == 1) {
			$t = ' ciento' . $t;
		} elseif ($n == 5) {
			$t = ' ' . $matunisub[$n] . 'ient' . $subcent . $t;
		} elseif ($n != 0) {
			$t = ' ' . $matunisub[$n] . 'cient' . $subcent . $t;
		}
		if ($sub == 1) {
		} elseif (!isset($matsub[$sub])) {

			if ($num == 1) {
				$t = ' mil';
			} elseif ($num > 1) {
				$t .= ' mil';
			}
		} elseif ($num == 1) {
			$t .= ' ' . $matsub[$sub] . '�n';
		} elseif ($num > 1) {
			$t .= ' ' . $matsub[$sub] . 'ones';
		}
		if ($num == '000') $mils++;
		elseif ($mils != 0) {
			if (isset($matmil[$sub])) $t .= ' ' . $matmil[$sub];
			$mils = 0;
		}
		$neutro = true;
		$tex = $t . $tex;
	}
	$tex = $neg . substr($tex, 1) . $fin;
	return ucfirst($tex);
}

function getPaymentNum(Facture $object, $xmlContents = null)
{

	global $db;

	if (empty($xml)) {

		$sql = "SELECT count(*) as nb FROM " . MAIN_DB_PREFIX . "paiement_facture";
		$sql .= " WHERE fk_facture = " . $object->id;

		$resql = $db->query($sql);
		if ($resql) {

			$obj = $db->fetch_object($resql);

			return $obj->nb;
		}
	}

	$cfdi = \CfdiUtils\Cfdi::newFromString($xmlContents);
	$cfdi->getVersion(); // (string) 3.3
	$cfdi->getDocument(); // clon del objeto DOMDocument
	$cfdi->getSource(); // (string) <cfdi:Comprobante...
	$comprobante = $cfdi->getNode();
	$pagos = $comprobante->searchNodes('cfdi:Complemento', 'pago20:Pagos', 'pago20:Pago');
	$pagoCounter = 0;
	$pagoCount = $pagos->count();
	foreach ($pagos as $pago) {

		$pagoCounter = $pagoCounter + 1;
		$doctoRelacionados = $pago->searchNodes('pago20:DoctoRelacionado');
		foreach ($doctoRelacionados as $doctoRelacionado) {
			if ($doctoRelacionado['IdDocumento'] == $object->array_options['options_cfdixml_UUID']) {
				return $doctoRelacionado['NumParcialidad'];
			}
		}
	}



	// echo '<pre>';print_r($doctoRelacionado->DoctoRelacionado);
}

function typent_array($mode = 0, $filter = '')
{
	// phpcs:enable
	global $db, $langs, $mysoc;

	$effs = array();

	$sql = "SELECT id, code, libelle";
	$sql .= " FROM " . $db->prefix() . "c_typent";
	$sql .= " WHERE active = 1 AND (fk_country IS NULL OR fk_country = " . (empty($mysoc->country_id) ? '0' : $mysoc->country_id) . ")";
	if ($filter) {
		$sql .= " " . $filter;
	}
	$sql .= " ORDER by position, id";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num) {
			$objp = $db->fetch_object($resql);
			if (!$mode) {
				$key = $objp->id;
			} else {
				$key = $objp->code;
			}
			if ($langs->trans($objp->code) != $objp->code) {
				$effs[$key] = $langs->trans($objp->code);
			} else {
				$effs[$key] = $objp->libelle;
			}
			if ($effs[$key] == '-') {
				$effs[$key] = '';
			}
			$i++;
		}
		$db->free($resql);
	}

	return $effs;
}

function checkReceptor(Facture $object)
{

	global $db, $conf;

	$emisor = getEmisor();
	$rfiscal = $emisor['RegimenFiscal'];

	if ($rfiscal != 626) {
		return false;
	}
	$sql = "SELECT c.code FROM " . MAIN_DB_PREFIX . "c_typent c ";
	$sql .= "WHERE c.id = " . $conf->global->CFDIXML_RESICO;
	$resql = $db->query($sql);

	if (!$resql) {
		return false;
	}

	$typentobj = $db->fetch_object($resql);

	if ($typentobj->code == 'P_FISICA') {

		$sql = "SELECT c.code FROM " . MAIN_DB_PREFIX . "c_typent c ";
		$sql .= "LEFT JOIN " . MAIN_DB_PREFIX . "societe s on s.fk_typent = c.id ";
		$sql .= "WHERE s.rowid = " . $object->socid;

		$resql2 = $db->query($sql);
		if ($resql2) {
			$soctypent = $db->fetch_object($resql2);
			if ($soctypent->code != 'P_MORAL') {
				return false;
			}
		}
	}

	return true;
}
