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
 * \file    cfdixml/class/actions_cfdixml.class.php
 * \ingroup cfdixml
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsCfdixml
 */

dol_include_once('/cfdixml/class/cfdixml.class.php');
dol_include_once('/cfdixml/class/cfdiutils.class.php');
dol_include_once('/cfdixml/class/facturalo.class.php');

require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';


class ActionsCfdixml
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Create From
	 */
	public function createFrom($parameters, $object, $action)
	{


		if (in_array($parameters['currentcontext'], array('invoicecard'))) {

			// if (GETPOST('action') == 'confirm_clone' && GETPOST('confirm') == 'yes') {
			// 	unset($parameters['objFrom']->array_options);
			// 	// echo '<pre>';
			// 	// print_r($parameters['objFrom']);
			// 	// exit;
			// 	$this->results = array('objFrom' => $parameters['objFrom']);
			// 	return 1;
			// }
		}
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $conf, $user, $langs;

		$error = 0; // Error counter
		dol_include_once('/cfdixml/lib/cfdixml.lib.php');


		$cfdixml = new Cfdixml($this->db);

		// echo '<pre>';print_r($_POST);exit;
		/* print_r($parameters); print_r($object); echo "action: " . $action; */

		//REVISAR VALIDACION PPD y PUE
		if (in_array($parameters['currentcontext'], array('invoicecard'))) {


			$invoice = new Facture($this->db);
			$societe = new Societe($this->db);


			if ($action == 'delete' || $action == 'modif' ) {
				if (!empty($object->array_options['options_cfdixml_UUID']) || !empty($object->array_options['options_cfdixml_control'])) {

					switch ($action) {
						case 'delete':
							$text = 'eliminar una factura timbrada, solo cancelar';
							break;
						case 'modif':
							$text = 'modificar la factura, ya se mandó a timbrar';
							break;
						case 'reopen':
							$text = 'reabrir una factura timbrada, solo cancelar';
							break;
					}

					setEventMessage('No se puede ' . $text, 'errors');
					header('Location:' . DOL_MAIN_URL_ROOT . '/compta/facture/card.php?id=' . $object->id);
					exit;
				}
			}
			if ($action == 'add' && GETPOST('type') == 1) {


				$invoice->fetch(GETPOST('fac_replacement'));
				if ($invoice->array_options['options_cfdixml_UUID']) {
					$invoiceid = createFromCurrent($user, $invoice);
					header('Location:' . DOL_MAIN_URL_ROOT . '/compta/facture/card.php?id=' . $invoiceid);
					exit;
				}
			}

			if ($action == 'add' && GETPOST('type') == 2) {

				$_POST['options_cfdixml_doctorel'] = [GETPOST('fac_avoir')];
				$_POST['options_cfdixml_usocfdi'] = 'G02';
				$_POST['options_cfdixml_metodopago'] = 'PUE';


				// $invoice->setPaymentTerms($condicion_pago);
				// $invoice->setPaymentMethods($forma_pago);

			}

			if ($action == 'confirm_stamp' && GETPOST('confirm') == 'yes') {

				$id = GETPOST('id');

				//Check if all data
				if (GETPOST('uso_cfdi') 		< 0) $error++;
				if (GETPOST('condicion_pago') 	< 0) $error++;
				if (GETPOST('forma_pago') 		< 0) $error++;
				if (GETPOST('metodo_pago') 		< 0) $error++;
				if (GETPOST('exportacion') 		< 0) $error++;

				//Return if not ok
				if ($error > 0) setEventMessage('Faltan datos para timbrar la factura', 'errors');
				if ($error > 0) header('Location:' . $_SERVER['PHP_SELF'] . '/?id=' . $object->id);

				$usocfdi 		= 	GETPOST('uso_cfdi');
				$condicion_pago = 	GETPOST('condicion_pago');
				$forma_pago 	= 	GETPOST('forma_pago');
				$metodo_pago 	= 	GETPOST('metodo_pago');
				$exportacion 	= 	GETPOST('exportacion');

				if ($forma_pago == '99' && $metodo_pago != 'PPD') {
					setEventMessage('No se puede tener forma de pago 99 y método de pago PUE', 'errors');
					header('Location:' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
					exit;
				}

				if ($forma_pago != '99' && $metodo_pago == 'PPD') {
					setEventMessage('No se puede tener forma de pago 99 y método de pago PUE', 'errors');
					header('Location:' . $_SERVER['PHP_SELF'] . '/?id=' . $object->id);
					exit;
				}

				$condicion_pago = dol_getIdFromCode($this->db, $metodo_pago, 'c_payment_term');
				$forma_pago = dol_getIdFromCode($this->db, $forma_pago, 'c_paiement');

				if ( in_array($object->status,[1,4]) && empty($object->array_options["options_cfdixml_UUID"])) {

					//echo '<pre>';print(dol_print_date($object->date, '%Y-%m-%d 12:00:00'));exit;

					if (empty($invoice->array_options['options_cfdixml_control'])) {
						$fecha_emision = dol_print_date($object->date, '%Y-%m-%d %H:%M:%S');
						$fecha_emision = str_replace(" ", "T", $fecha_emision);
					} else {
						$fecha_emision = $invoice->array_options['options_cfdixml_control'];
					}


					$invoice->fetch($id);

					$this->db->begin();
					$invoice->array_options['options_cfdixml_usocfdi'] = $usocfdi;
					$invoice->array_options['options_cfdixml_metodopago'] = $metodo_pago;
					$invoice->array_options['options_cfdixml_exportacion'] = $exportacion;
					$invoice->array_options['options_cfdixml_control'] = $fecha_emision;
					$invoice->setPaymentTerms($condicion_pago);
					$invoice->setPaymentMethods($forma_pago);

					$result = $invoice->update($user, 1);

					if ($result < 0) setEventMessage('Error al guardar datos en la factura', 'errors');
					if ($result < 0)  header('Location:' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id);

					$this->db->commit();

					$societe->fetch($invoice->socid);
					$cfdiutils = new CfdiUtils();
					if($conf->global->CFDIXML_DEBUG_MODE){
					echo '<pre>';print_r(getComprobanteAtributos($invoice, $fecha_emision));echo '<pre>';
					echo '<pre>';print_r(getEmisor());echo '<pre>';
					echo '<pre>';print_r(getReceptor($invoice, $societe));echo '<pre>';
					echo '<pre>';print_r(getConceptos($invoice));echo '<pre>';

					exit;

				}
					try {
						$xml = $cfdiutils->preCfdi(
							getComprobanteAtributos($invoice, $fecha_emision),
							getEmisor(),
							getReceptor($invoice, $societe),
							getConceptos($invoice),
							null,
							$conf->global->CFDIXML_CER_FILE,
							$conf->global->CFDIXML_KEY_FILE,
							$conf->global->CFDIXML_CERKEY_PASS
						);
					} catch (Exception $e) {
						// echo '<pre>';print_r();exit;
						dol_syslog('Error al generar XML - '.$e->getMessage(), LOG_DEBUG);
						setEventMessage('Error al generar XML: '.$e->getMessage() , 'errors');
						header('Location:' . DOL_MAIN_URL_ROOT . '/compta/facture/card.php?facid=' . $object->id);
						exit;
					}

					$filedir = $conf->facture->multidir_output[$object->entity] . '/' . dol_sanitizeFileName($object->ref);

					$file_xml = fopen($filedir . "/" . $object->ref . ".xml", "w");
					fwrite($file_xml, mb_convert_encoding($xml, 'utf8'));
					fclose($file_xml);

					//FINKOK
					$cfdi = $cfdiutils->quickStamp($xml, $conf->global->CFDIXML_WS_TOKEN, $conf->global->CFDIXML_WS_MODE, $user);

					//FINKOK
					if ($cfdi['code'] == '400') setEventMessage($cfdi['data'], 'errors');
					if ($cfdi['code'] == '400') header('Location:' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id);
					if ($cfdi['code'] == '200' || $cfdi['code'] == '307') goto saveXML;
					if ($cfdi['code'] != '200') {

						setEventMessage($cfdi['code'] . ' - ' . $cfdi['message'], 'errors');
						header('Location:' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id);
						$invoice->array_options['options_cfdixml_control'] = '';
						$invoice->update($user, 1);
					}
					exit;

					saveXML:
					$data = $cfdiutils->getData($cfdi['data']);

					//FINKOK
					//This modify helps to change invoice in CFDIUTILS trigger, we need reload the invoice to load new status.
					$invoice = null;
					$invoice = new Facture($this->db);
					$invoice->fetch($object->id);

					$invoice->array_options['options_cfdixml_UUID'] = $data['UUID'];
					$invoice->array_options['options_cfdixml_fechatimbrado'] = $data['FechaTimbrado'];
					$invoice->array_options['options_cfdixml_sellosat'] = $data['SelloSAT'];
					$invoice->array_options['options_cfdixml_certsat'] = $data['CertSAT'];
					$invoice->array_options['options_cfdixml_sellocfd'] = $data['SelloCFD'];
					$invoice->array_options['options_cfdixml_certcfd'] = $data['CertCFD'];
					$invoice->array_options['options_cfdixml_cadenaorig'] = $data['CadenaOriginal'];
					$invoice->array_options['options_cfdixml_xml'] = base64_encode($cfdi['data']);
					$invoice->array_options['options_cfdixml_control'] = '';

					//FINKOK
					$file_xml = fopen($filedir . "/" . $object->ref . '_' . $data['UUID'] . ".xml", "w");
					fwrite($file_xml, mb_convert_encoding($cfdi['data'], 'utf8'));
					$invoice->update($user, 1);

					if ($object->type == Facture::TYPE_REPLACEMENT) {

						$invoicetocancel = new Facture($this->db);
						$invoicetocancel->fetch($object->fk_facture_source);

						//FINKOK
						$uuid = [
							'cancelacion' => $invoicetocancel->array_options['options_cfdixml_UUID'],
							'sustitucion' => $data['UUID']
						];

						$cfdiutils = null;
						$cfdiutils = new CfdiUtils();
						try {
							$result = $cfdiutils->CancelDocument(
								$uuid,
								'01',
								$conf->global->CFDIXML_WS_MODE,
								$conf->global->CFDIXML_CER_FILE,
								$conf->global->CFDIXML_KEY_FILE,
								$conf->global->CFDIXML_CERKEY_PASS,
								$conf->global->CFDIXML_WS_TOKEN
							);
							$xmlcanceled =  $result->voucher();
							$filedir = $conf->facture->multidir_output[$object->entity] . '/' . dol_sanitizeFileName($invoicetocancel->ref);

							$file_xml = fopen($filedir . "/ACUSE_CANCELACION_" . $invoicetocancel->ref . '_' . $data['UUID'] . ".xml", "w");
							fwrite($file_xml, utf8_encode($xmlcanceled));
							fclose($file_xml);

							//Provisional FIX
							$this->db->begin();
							$sql = "UPDATE " . MAIN_DB_PREFIX . "facture_extrafields ";
							$sql .= " SET cfdixml_fechacancelacion = '" . $result->date() . "',";
							$result->statusCode() ? $sql .= " cfdixml_codigocancelacion = '" . $result->statusCode() . "'," : null;
							$sql .= " cfdixml_xml_cancel = \"" . base64_encode($result->voucher()) . "\"";
							$sql .= " WHERE fk_object = " . $invoicetocancel->id;

							$result = $this->db->query($sql);
							if ($result > 0) {
								$this->db->commit();
							}

							// Not update correct invoice, update $object ¿why?
							// $invoicetocancel->array_options['options_cfdixml_fechacancelacion'] = $result->date();
							// $invoicetocancel->array_options['options_cfdixml_codigoncelacion'] = $result->statusCode();
							// $invoicetocancel->array_options['options_cfdixml_xml_cancel'] = $xmlcanceled;

							// $result = $invoicetocancel->update($user, 1);

						} catch (Exception $e) {

							dol_syslog("Exception Cancel Invoice: " . $e);
							/*echo '<pre>';
							print_r($e);
							exit;*/
						}
					}

					// FINKOK
					$invoice->generateDocument('cfdixml', $langs, false, false);
					setEventMessage('Factura timbrada con éxito UUID:' . $data['UUID'], 'mesgs');

					// header('Location:' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id);
					echo '<script>window.location.href="'. $_SERVER['PHP_SELF'] . '?facid=' . $object->id.'"</script>';
					exit;
				}
			}

			if ($action == 'confirm_cancel' && GETPOST('confirm') == 'yes') {
				//echo '<pre>';print_r($_GET);exit;

				//Finkok
				$cfdiutils = null;
				$cfdiutils = new CfdiUtils();
				try {
					$result = $cfdiutils->CancelDocument(
						$object->array_options['options_cfdixml_UUID'],
						GETPOST('motivo'),
						$conf->global->CFDIXML_WS_MODE,
						$conf->global->CFDIXML_CER_FILE,
						$conf->global->CFDIXML_KEY_FILE,
						$conf->global->CFDIXML_CERKEY_PASS,
						$conf->global->CFDIXML_WS_TOKEN
					);


					//Finkok
					$xmlcanceled =  $result->voucher();
					$filedir = $conf->facture->multidir_output[$object->entity] . '/' . dol_sanitizeFileName($object->ref);
					//Finkok
					$file_xml = fopen($filedir . "/ACUSE_CANCELACION_" . $object->ref . '_' . $object->array_options['options_cfdixml_UUID'] . ".xml", "w");
					fwrite($file_xml, utf8_encode($xmlcanceled));
					fclose($file_xml);

					// //Provisional FIX

					$sql = "UPDATE " . MAIN_DB_PREFIX . "facture_extrafields ";
					$sql .= " SET cfdixml_fechacancelacion = '" . $result->date() . "'";
					$result->statusCode() ? $sql .= ", cfdixml_codigocancelacion = '" . $result->statusCode() . "'" : null;
					$sql .= ", cfdixml_xml_cancel = \"" . base64_encode($result->voucher()) . "\"";
					$sql .= " WHERE fk_object = " . $object->id;


					$result = $db->query($sql);
					$db->commit();


					// Not update correct invoice, update $object ¿why?
					// $invoicetocancel->array_options['options_cfdixml_fechacancelacion'] = $result->date();
					// $invoicetocancel->array_options['options_cfdixml_codigoncelacion'] = $result->statusCode();
					// $invoicetocancel->array_options['options_cfdixml_xml_cancel'] = $xmlcanceled;

					// $result = $invoicetocancel->update($user, 1);

				} catch (Exception $e) {

					dol_syslog("Exception Cancel Invoice: " . $e);
				}
			}

			if ($action == 'rebuildpdf') {
				$invoice = new Facture($this->db);
				$invoice->fetch($object->id);
				$expInvoice = explode('cfdi',$object->model_pdf);
				if(count($expInvoice) > 1){
					if($expInvoice[1] == 'xml'){
						$invoice->generateDocument('cfdixml', $langs, false, false);
					}
				}
			}

			// Do what you want here...
			// You can for example call global vars like $fieldstosearchall to overwrite them, or update database depending on $action and $_POST values.
		}

		if (in_array($parameters['currentcontext'], array('paiementcard'))) {
			if ($action == 'confirm_paiement' && GETPOST('confirm') == 'yes') {
				$sql = "SELECT rowid from " . MAIN_DB_PREFIX . "facture where fk_statut = 1";
				$resql = $this->db->query($sql);
				if ($resql) {
					$i = 0;
					$ppd = 0;
					$pue = 0;
					$num = $this->db->num_rows($resql);
					if ($num > 0) {
						while ($i < $num) {
							$obj = $this->db->fetch_object($resql);

							foreach ($_POST as $key => $value) {
								if ($key == 'amount_' . $obj->rowid && $value > 0) {
									$invoice = new Facture($this->db);
									$invoice->fetch($obj->rowid);
									if ($invoice->array_options['options_cfdixml_metodopago'] == 'PPD') $ppd++;
									if ($invoice->array_options['options_cfdixml_metodopago'] == 'PUE') $pue++;
								}
							}

							$i++;
						}
					}
				}

				if ($ppd > 0 && $pue > 0) {
					setEventMessage('No se pueden grabar pagos PPD y PUE a la vez', 'errors');
					header('Location:' . $_SERVER['PHP_SELF'] . '?facid=' . $obj->rowid . '&action=create');
					exit;
				}
			}


			// $facid = explode('_',GETPOST())
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			foreach ($parameters['toselect'] as $objectid) {
				// Do action on each object id
			}
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$disabled = 1;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('invoicelist', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			$this->resprints = '<option value="0"' . ($disabled ? ' disabled="disabled"' : '') . '>' . $langs->trans("CfdixmlMassAction") . '</option>';
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}



	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$object		   	Object output on PDF
	 * @param   string	$action     	'add', 'update', 'view'
	 * @return  int 		        	<0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	public function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog(get_class($this) . '::executeHooks action=' . $action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}

	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog(get_class($this) . '::executeHooks action=' . $action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}



	/**
	 * Overloading the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$langs->load("cfdixml@cfdixml");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'cfdixml') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("Cfdixml");
			$this->results['picto'] = 'cfdixml@cfdixml';
		}

		$head[$h][0] = 'customreports.php?objecttype=' . $parameters['objecttype'] . (empty($parameters['tabfamily']) ? '' : '&tabfamily=' . $parameters['tabfamily']);
		$head[$h][1] = $langs->trans("CustomReports");
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		return 1;
	}



	/**
	 * Overloading the restrictedArea function : check permission on an object
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int 		      			  	<0 if KO,
	 *                          				=0 if OK but we want to process standard actions too,
	 *  	                            		>0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'myobject') {
			if ($user->rights->cfdixml->myobject->read) {
				$this->results['result'] = 1;
				return 1;
			} else {
				$this->results['result'] = 0;
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Execute action completeTabsHead
	 *
	 * @param   array           $parameters     Array of parameters
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         'add', 'update', 'view'
	 * @param   Hookmanager     $hookmanager    hookmanager
	 * @return  int                             <0 if KO,
	 *                                          =0 if OK but we want to process standard actions too,
	 *                                          >0 if OK and we want to replace standard actions.
	 */
	public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;

		if (!isset($parameters['object']->element)) {
			return 0;
		}
		if ($parameters['mode'] == 'remove') {
			// utilisé si on veut faire disparaitre des onglets.
			return 0;
		} elseif ($parameters['mode'] == 'add') {
			$langs->load('cfdixml@cfdixml');
			// utilisé si on veut ajouter des onglets.
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;
			// verifier le type d'onglet comme member_stats où ça ne doit pas apparaitre
			// if (in_array($element, ['societe', 'member', 'contrat', 'fichinter', 'project', 'propal', 'commande', 'facture', 'order_supplier', 'invoice_supplier'])) {
			if (in_array($element, ['context1', 'context2'])) {
				$datacount = 0;

				$parameters['head'][$counter][0] = dol_buildpath('/cfdixml/cfdixml_tab.php', 1) . '?id=' . $id . '&amp;module=' . $element;
				$parameters['head'][$counter][1] = $langs->trans('CfdixmlTab');
				if ($datacount > 0) {
					$parameters['head'][$counter][1] .= '<span class="badge marginleftonlyshort">' . $datacount . '</span>';
				}
				$parameters['head'][$counter][2] = 'cfdixmlemails';
				$counter++;
			}
			if ($counter > 0 && (int) DOL_VERSION < 14) {
				$this->results = $parameters['head'];
				// return 1 to replace standard code
				return 1;
			} else {
				// en V14 et + $parameters['head'] est modifiable par référence
				return 0;
			}
		}
	}

	public function formObjectOptions($parameters, $object, $action)
	{

		global $conf, $dolibarr_main_url_root;
		$string = '';
		if (in_array($parameters['currentcontext'], array('invoicecard'))) {

		if(!is_empty($object->status) && $object->statut != Facture::STATUS_DRAFT)	{
			if ($object->array_options['options_cfdixml_UUID']) {

				$receptor = new Societe($this->db);

				$receptor->fetch($object->socid);

				$expresion = 'id=' . $object->array_options["options_cfdixml_UUID"] . '&re=' . $conf->global->MAIN_INFO_SIREN . '&rr=' . $receptor->idprof1 . '&tt=' . $object->total_ttc . '&fe=' . substr($object->array_options["options_cfdixml_sellocfd"], -8);

				$image = file_get_contents($conf->facture->dir_output . "/" . $object->ref . "/" . $object->ref . '_' . $object->array_options["options_cfdixml_UUID"] . ".png");
				$image = base64_encode($image);


				// echo '<pre>';print_r($creditos);exit;

				$string = '<tr><td>UUID</td><td style="">' . $object->array_options['options_cfdixml_UUID'] . '</td></tr>';
				$string .= '<tr><td>Fecha de timbrado</td><td style="">' . $object->array_options['options_cfdixml_fechatimbrado'] . '</td></tr>';
				$string .= '<tr><td>Certificado SAT</td><td style="">' . $object->array_options['options_cfdixml_certsat'] . '</td></tr>';
				$string .= '<tr><td>Certificado CFD</td><td style="">' . $object->array_options['options_cfdixml_certcfd'] . '</td></tr>';
				// $string .= '<tr><td>QR</td><td style=""><img src="' . $dolibarr_main_url_root . '/document.php?modulepart=facture&attachment=0&file=' . $object->ref . '_' . $object->array_options['options_cfdixml_UUID'] . '.png&entity=' . $conf->entity . '"></td></tr>';
				$string .= '<tr><td>QR</td><td style=""><img src="data:image/png;base64,' . $image. '"></td></tr>';
				$string .= '<tr><td>Verificar CFDI</td><td style=""><a href="https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?' . $expresion . '" target="_blank">' . $object->array_options['options_cfdixml_UUID'] . '</a></td></tr>';
			}

			$this->resprints = $string;
		}
			// echo $string;
			return 0;
		}
	}

	public function addMoreActionsButtons($parameters, $object, $action)
	{

		if (in_array($parameters['currentcontext'], array('invoicecard'))) {

			$invoice = new Facture($this->db);

			if ($object->type == Facture::TYPE_REPLACEMENT) {

				$invoice->fetch($object->fk_facture_source);

				if (array_key_exists($object->status,[1,4]) &&  !$object->array_options['options_cfdixml_UUID']) {
					echo '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=prestamp" class="butAction">Timbrar CFDI</a>';
				}
				if (array_key_exists($object->status,[1,4]) &&  $object->array_options['options_cfdixml_UUID']) {
					echo '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=cancelxml" class="butActionDelete">Cancelar CFDI</a>';
					echo '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=rebuildpdf" class="butAction">Regenerar PDF</a>';
				}
			} else if (in_array($object->status,[1,4]) && empty($object->array_options['options_cfdixml_UUID']) && $object->type != Facture::TYPE_REPLACEMENT) {
				echo '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=prestamp" class="butAction">Timbrar CFDI</a>';
			} else if (in_array($object->status,[1,4]) && $object->array_options['options_cfdixml_UUID']) {
				echo '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=cancelxml" class="butActionDelete">Cancelar CFDI</a>';
				echo '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=rebuildpdf" class="butAction">Regenerar PDF</a>';
			}
		}
	}

	// public function printFieldListValue($parameters, $object, $action)
	// {
	// 	echo '<td><span class="badge badge-status1 badge-status">Require REP</span></td>';
	// 	if (in_array($parameters['currentcontext'], array('paiementcard'))) {
	// 		$invoice = new Facture($this->db);

	// 		$invoice->fetch($object->facid);

	// 		if ($invoice->array_options['options_cfdixml_metodopago'] == 'PPD') {
	// 			echo '<td><span class="badge badge-status1 badge-status">Require REP</span></td>';
	// 		}
	// 	}
	// }

	public function formConfirm($parameters, $object, $action)
	{
		global $langs;
		dol_include_once('/cfdixml/lib/cfdixml.lib.php');
		$form = new Form($this->db);


		if ($action == 'prestamp' && in_array($object->status,[1,4]) && empty($object->array_options["options_cfdixml_UUID"])) {

			$resico = checkReceptor($object);

			if ($resico) {
				$revapply = 0;
				foreach ($object->lines as $line) {
					if ($line->product_type == 1) $revapply++;
				}
				if ($revapply > 0) {
					$sql = "SELECT f.revenuestamp FROM " . MAIN_DB_PREFIX . "facture f where rowid = " . $object->id;
					$resql = $this->db->query($sql);

					if ($resql) {
						$obj = $this->db->fetch_object($resql);
					}
					if (abs($obj->revenuestamp) <= 0) {
						setEventMessage('RESICO: Se debe aplicar la retención del 1.25%', 'warnings');
						return;
					}
				}
			}

			$form->load_cache_conditions_paiements();
			$form->load_cache_types_paiements();

			$conditionsPayment = getConditionsPayments($form->cache_conditions_paiements);
			$typesPayment = getTypesPayments($form->cache_types_paiements);
			$usocfdi = getDictionaryValues('usocfdi');
			$metodoPago = getDictionaryValues('metodopago');
			$exportacion = getDictionaryValues('exportacion');
			$disabled = 0;
			if (!empty($object->array_options["options_cfdixml_control"])) {
				$disabled = 1;
			}

			$formquestion = [
				'text' => '<h2>Timbrar factura ' . $object->ref . '</h2>',
				['type' => 'select', 'name' => 'uso_cfdi', 'id' => 'uso_cfdi', 'label' => 'Uso del CFDI', 'values' => $usocfdi, 'default' => $object->array_options['options_cfdixml_usocfdi'], 'multiple', 'select_disabled' => $disabled],
				['type' => 'select', 'name' => 'condicion_pago', 'id' => 'condicion_pago', 'label' => 'Condiciones de pago', 'values' => $conditionsPayment, 'default' => $object->cond_reglement_code, 'select_disabled' => $disabled],
				['type' => 'select', 'name' => 'forma_pago', 'id' => 'forma_pago', 'label' => 'Forma de pago', 'values' => $typesPayment, 'default' =>  $object->mode_reglement_code, 'select_disabled' => $disabled],
				['type' => 'select', 'name' => 'metodo_pago', 'id' => 'metodo_pago', 'label' => 'Método de pago', 'values' => $metodoPago, 'default' => $object->array_options['options_cfdixml_metodopago'], 'select_disabled' => $disabled],
				['type' => 'select', 'name' => 'exportacion', 'id' => 'exportacion', 'label' => 'Exportación', 'values' => $exportacion, 'default' => $object->array_options['options_cfdixml_exportacion'] ? $object->array_options['options_cfdixml_exportacion'] : '01', 'select_disabled' => $disabled],

			];

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('stamp'), '', 'confirm_stamp', $formquestion, 0, 1, 420, 600);

			print $formconfirm;

			echo '<script>$(document).ready(function(){
				$(".select2-container").css("width","20rem");
				});</script>';
		}

		if ($action == 'cancelxml') {
			$cancelacion = getDictionaryValues('cancelacion');
			$formquestion = [
				'text' => '<h2>Cancelar fiscalmente factura ' . $object->ref . '</h2>',
				['type' => 'select', 'name' => 'motivo', 'id' => 'motivo', 'label' => 'Motivo', 'values' => $cancelacion]

			];

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('cancel'), '', 'confirm_cancel', $formquestion, 0, 1, 310, 500);

			print $formconfirm;

			echo '<script>$(document).ready(function(){
				$(".select2-container").css("width","20rem");
				});</script>';
		}
	}

	public function getFormMail($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;
		if (in_array($parameters['currentcontext'], array('invoicecard'))) {

			$invoice = new Facture($this->db);
			$invoice->fetch(GETPOST('facid'));

			$objectref = dol_sanitizeFileName($invoice->ref);
			$dir = $conf->facture->dir_output . "/" . $objectref;
			$file = $dir . "/" . $objectref . '_' . $invoice->array_options['options_cfdixml_UUID'] . ".xml";

			$object->add_attached_files($file);

			$file = $dir . "/" . $objectref . '_' . $invoice->array_options['options_cfdixml_UUID'] . ".pdf";

			$object->add_attached_files($file);

			return;
		}
	}

	// public function printFieldListSearchParam($parameters, $object, $action){
	// 	global $langs;
	// 	if(in_array($parameters['currentcontext'], array('invoicelist'))){

	// 		// echo "hola";exit;
	// 		// $arrayofmassactions = array(
	// 		// 	'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	// 		// 	'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	// 		// 	// 'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	// 		// 	'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	// 		// );
	// 		// $this->results = array('myreturn' => $myvalue);
	// 		$this->resprints = "&hola";
	// 		return 1; // or return 1 to replace standard code

	// 	}

	// }

	// public function addMoreMassActions($params) {

	// }
	/* Add here any other hooked methods... */
	function checkSecureAccess($parameters, $object){
		echo '<pre>';print_r($parameters);exit;
	}
}
