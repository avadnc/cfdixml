<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 Alice Adminson <aadminson@example.com>
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
 * \file    cfdixml/admin/setup.php
 * \ingroup cfdixml
 * \brief   Cfdixml setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/cfdixml.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "cfdixml@cfdixml"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('cfdixmlsetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';
$typeEndpoint = 0;

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	// For retrocompatibility Dolibarr < 16.0
	if (floatval(DOL_VERSION) < 16.0 && !class_exists('FormSetup')) {
		require_once __DIR__.'/../backport/v16/core/class/html.formsetup.class.php';
	} else {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
	}
}

$formSetup = new FormSetup($db);

$formcompany = new FormCompany($db);
// Hôte
$item = $formSetup->newItem('NO_PARAM_JUST_TEXT');
$item->fieldOverride = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];
$item->cssClass = 'minwidth500';


/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if ( versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if($action == 'save'){
	//Upload files
	if ($_FILES["csd"] || $_FILES["key"]) {

    	$upload_dir = $conf->cfdixml->dir_output . '/' . get_exdir(0, 0, 0, 1, $object, 'cfdimx');
    	$flag = 0;

    	$cer_file = $_FILES["csd"]["name"] ? $upload_dir . basename($_FILES["csd"]["name"]) : null;
    	$key_file = $_FILES["key"]["name"] ? $upload_dir . basename($_FILES["key"]["name"]) : null;

    	if($cer_file){

    	    if(move_uploaded_file($_FILES["csd"]["tmp_name"],$cer_file)) $flag++;
    	}

    	if($flag > 0) dolibarr_set_const($db, "CFDIXML_CER_FILE", $cer_file, 'chaine', 1, '', $conf->entity);

    	if($key_file){

    	    if(move_uploaded_file($_FILES["key"]["tmp_name"],$key_file)) $flag++;
    	}

    	if($flag > 0) dolibarr_set_const($db, "CFDIXML_KEY_FILE", $key_file, 'chaine', 1, '', $conf->entity);

	}
	if(GETPOST('passkey')) dolibarr_set_const($db, "CFDIXML_CERKEY_PASS", GETPOST('passkey','alpha'), 'chaine', 1, '', $conf->entity);
	if(GETPOST('setep')) dolibarr_set_const($db, "CFDIXML_WS_MODE", GETPOST('setep'), 'chaine', 1, '', $conf->entity);
	if(GETPOST('epprod')) dolibarr_set_const($db, "CFDIXML_WS_PRODUCTION", GETPOST('epprod'), 'chaine', 1, '', $conf->entity);
	if(GETPOST('eptest')) dolibarr_set_const($db, "CFDIXML_WS_TEST", GETPOST('eptest'), 'chaine', 1, '', $conf->entity);
	if(GETPOST('passtoken')) dolibarr_set_const($db, "CFDIXML_WS_TOKEN", GETPOST('passtoken'), 'chaine', 1, '', $conf->entity);
	if(GETPOST('typent_id')) dolibarr_set_const($db, "CFDIXML_RESICO", GETPOST('typent_id'), 'chaine', 1, '', $conf->entity);


}

if($conf->global->CFDIXML_WS_MODE == 'PRODUCTION') $epprod = 'checked';
if($conf->global->CFDIXML_WS_MODE == 'TEST') $eptest = 'checked';
if(!$conf->global->CFDIXML_WS_MODE) $eptest = 'checked';

if($conf->global->CFDIXML_WS_MODE){
	if($conf->global->CFDIXML_WS_MODE == 'PRODUCTION') $endpoint = $conf->global->CFDIXML_WS_PRODUCTION;
	if($conf->global->CFDIXML_WS_MODE == 'TEST') $endpoint = $conf->global->CFDIXML_WS_TEST;

	$check = curl_init($endpoint);

	if(!$check) setEventMessage('Error en el webservice seleccionado','warnings');

}

$emisor = getEmisor($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
$rfiscal = $emisor['RegimenFiscal'];

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "CfdixmlSetup";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = cfdixmlAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "cfdixml@cfdixml");
// Setup page goes here
echo '<span class="opacitymedium">' . $langs->trans("CfdiutilsSetupPage") . '</span><br><br>';

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="titlefield">' . $langs->trans("Parameter") . '</td><td>' . $langs->trans("Value") . '</td></tr>';
print '<tr><td>Endpoint Producción</td><td><input class="maxwidth500 widthcentpercentminusx" type="text" name="epprod" id="epprod" value="'.$conf->global->CFDIXML_WS_PRODUCTION.'"></td></tr>';
print '<tr><td>Endpoint Pruebas</td><td><input class="maxwidth500 widthcentpercentminusx" type="text" name="eptest" id="eptest" value="'.$conf->global->CFDIXML_WS_TEST.'"></td></tr>';
print '<tr><td colspan="2"><fieldset>';
print '<legend>Seleccione el endpoint:</legend>';
print '<div><input type="radio" id="selepprod" name="setep" value="PRODUCTION" '.$epprod.'> <label for="selepprod">Producción</label></div>';
print '<div><input type="radio" id="seleptest" name="setep" value="TEST" '.$eptest.'><label for="seleptest">Pruebas</label></div>';
print '</fieldset></td></tr>';

print '<tr><td>Archivo CSD CER</td><td><input type="file" name="csd" id="csd"></td></tr>';
if ($csd) {
	print '<tr><td>Ruta CSD CER</td><td>' . $csd . '</td></tr>';
}
print '<tr><td>Archivo CSD KEY</td><td><input type="file" name="key" id="key"></td></tr>';
if ($key) {
	print '<tr><td>Ruta CSD KEY</td><td>' . $key . '</td></tr>';
}
print '<tr><td>Contraseña de los certificados</td><td><input type="password" name="passkey" id="passkey" value="'.$conf->global->CFDIXML_CERKEY_PASS.'"></td></tr>';
print '<tr><td>Token</td><td><input type="text" name="passtoken" id="passtoken" value="'.$conf->global->CFDIXML_WS_TOKEN.'"></td></tr>';
// print '<pre>';print_r(getEmisor($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE));exit;
echo $rfiscal;
if($rfiscal == 626){
	print '<tr class="liste_titre"><td class="liste_titre">Seleccionar tipo de RESICO</td><td class="colspan2">'.$form->selectarray("typent_id", $formcompany->typent_array(0), $conf->global->CFDIXML_RESICO, 1, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT), '', 1).'</td></tr>';
}
print '</table>';

print '<br><div class="center">';
print '<input class="button button-save" type="submit" value="' . $langs->trans("Save") . '">';
print '</div>';
print '</form>';
print '<br>';
// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
//a4f2ebbb97f82871f509c579f8ea9f0cb2efab7f0126af4846e9edda4565
