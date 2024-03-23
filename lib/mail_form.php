<?php
/* Copyright (C)    2017-2018 Laurent Destailleur <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/*
 * Code to ouput content when action is presend
 *
 * $trackid must be defined
 * $modelmail
 * $defaulttopic
 * $diroutput
 * $arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}


if ($action == 'presend')
{
	$langs->load("mails");

	$titreform = 'SendMail';

	$payment->fetch_projet();

	if (!in_array($payment->element, array('societe', 'user', 'member')))
	{
		// TODO get also the main_lastdoc field of $payment. If not found, try to guess with following code

		$ref = dol_sanitizeFileName($payment->ref);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		// Special case
		if ($payment->element == 'invoice_supplier')
		{
			$fileparams = dol_most_recent_file($diroutput.'/'.get_exdir($payment->id, 2, 0, 0, $payment, $payment->element).$ref, preg_quote($ref, '/').'([^\-])+');
		}
		else
		{
			$fileparams = dol_most_recent_file($diroutput.'/'.$ref, preg_quote($ref, '/').'[^\-]+');
		}

		$file = $fileparams['fullname'];
	}

	// Define output language
	$outputlangs = $langs;
	$newlang = '';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && !empty($_REQUEST['lang_id']))
	{
		$newlang = $_REQUEST['lang_id'];
	}
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
	{
		$newlang = $payment->thirdparty->default_lang;
	}

	if (!empty($newlang))
	{
		$outputlangs = new Translate('', $conf);
		$outputlangs->setDefaultLang($newlang);
		// Load traductions files required by page
		$outputlangs->loadLangs(array('commercial', 'bills', 'orders', 'contracts', 'members', 'propal', 'products', 'supplier_proposal', 'interventions'));
	}

	$topicmail = '';
	if (empty($payment->ref_client)) {
		$topicmail = $outputlangs->trans($defaulttopic, '__REF__');
	} elseif (!empty($payment->ref_client)) {
		$topicmail = $outputlangs->trans($defaulttopic, '__REF__ (__REFCLIENT__)');
	}

	// get builded document every time
	
	/*$fileparams = dol_most_recent_file($diroutput.'/'.$ref, preg_quote($ref, '/').'[^\-]+');
	$file = $fileparams['fullname'];*/

	
	print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
	print '<div class="clearboth"></div>';
	print '<br>';
	print load_fiche_titre($langs->trans($titreform));

	dol_fiche_head('');

	// Create form for email
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);

	$formmail->param['langsmodels'] = (empty($newlang) ? $langs->defaultlang : $newlang);
	$formmail->fromtype = (GETPOST('fromtype') ?GETPOST('fromtype') : (!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE) ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user'));

	if ($formmail->fromtype === 'user')
	{
		$formmail->fromid = $user->id;
	}

	if ($payment->element === 'facture' && !empty($conf->global->INVOICE_EMAIL_SENDER)) {
		$formmail->frommail = $conf->global->INVOICE_EMAIL_SENDER;
		$formmail->fromname = '';
		$formmail->fromtype = 'special';
	}
	if ($payment->element === 'shipping' && !empty($conf->global->SHIPPING_EMAIL_SENDER)) {
		$formmail->frommail = $conf->global->SHIPPING_EMAIL_SENDER;
		$formmail->fromname = '';
		$formmail->fromtype = 'special';
	}
	if ($payment->element === 'commande' && !empty($conf->global->COMMANDE_EMAIL_SENDER)) {
		$formmail->frommail = $conf->global->COMMANDE_EMAIL_SENDER;
		$formmail->fromname = '';
		$formmail->fromtype = 'special';
	}

	$formmail->trackid=$trackid;
	if (!empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
	{
		include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$formmail->frommail = dolAddEmailTrackId($formmail->frommail, $trackid);
	}
	$formmail->withfrom = 1;

	// Fill list of recipient with email inside <>.
	$liste = array();
	if ($payment->element == 'expensereport')
	{
		$fuser = new User($db);
		$fuser->fetch($payment->fk_user_author);
		$liste['thirdparty'] = $fuser->getFullName($outputlangs)." <".$fuser->email.">";
	}
	elseif ($payment->element == 'societe')
	{
		foreach ($payment->thirdparty_and_contact_email_array(1) as $key => $value) {
			$liste[$key] = $value;
		}
	}
	elseif ($payment->element == 'contact')
	{
		$liste['contact'] = $payment->getFullName($outputlangs)." <".$payment->email.">";
	}
	elseif ($payment->element == 'user' || $payment->element == 'member')
	{
		$liste['thirdparty'] = $payment->getFullName($outputlangs)." <".$payment->email.">";
	}
	else
	{
		if (!empty($payment->socid) && empty($payment->thirdparty) && method_exists($payment, 'fetch_thirdparty')) {
			$payment->fetch_thirdparty();
		}
		if (is_object($payment->thirdparty))
		{
			foreach ($payment->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
				$liste[$key] = $value;
			}
		}
	}
	if (!empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
		$listeuser = array();
		$fuserdest = new User($db);

		$result = $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, array('customsql'=>'t.statut=1 AND t.employee=1 AND t.email IS NOT NULL AND t.email<>\'\''), 'AND', true);
		if ($result > 0 && is_array($fuserdest->users) && count($fuserdest->users) > 0) {
			foreach ($fuserdest->users as $uuserdest) {
				$listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id, 'email');
			}
		} elseif ($result < 0) {
			setEventMessages(null, $fuserdest->errors, 'errors');
		}
		if (count($listeuser) > 0) {
			$formmail->withtouser = $listeuser;
			$formmail->withtoccuser = $listeuser;
		}
	}

	$formmail->withto = $liste;
	//$formmail->withtofree = (GETPOSTISSET('sendto') ? (GETPOST('sendto', 'alpha') ? GETPOST('sendto', 'alpha') : '1') : '1');
	$formmail->withtofree = $rma_email_to;
	$formmail->withtocc = $liste;
	$formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
	$formmail->withtopic = $topicmail;
	$formmail->withfile = 2;
	$formmail->withbody = 1;
	$formmail->withdeliveryreceipt = 1;
	$formmail->withcancel = 1;

	//$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
	if (!isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude = null;

	// Make substitution in email content
	$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $payment);
	$substitutionarray['__CHECK_READ__'] = (is_object($payment) && is_object($payment->thirdparty)) ? '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$payment->thirdparty->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';
	$substitutionarray['__PERSONALIZED__'] = ''; // deprecated
	$substitutionarray['__CONTACTCIVNAME__'] = '';
	$parameters = array(
		'mode' => 'formemail'
	);
	complete_substitutions_array($substitutionarray, $outputlangs, $payment, $parameters);

	// Find the good contact address
	/*$tmpobject = $payment;
	if (($payment->element == 'shipping' || $payment->element == 'reception')) {
		$origin = $payment->origin;
		$origin_id = $payment->origin_id;

		if (!empty($origin) && !empty($origin_id)) {
			$element = $subelement = $origin;
			$regs = array();
			if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
				$element = $regs[1];
				$subelement = $regs[2];
			}
			// For compatibility
			if ($element == 'order') {
				$element = $subelement = 'commande';
			}
			if ($element == 'propal') {
				$element = 'comm/propal';
				$subelement = 'propal';
			}
			if ($element == 'contract') {
				$element = $subelement = 'contrat';
			}
			if ($element == 'inter') {
				$element = $subelement = 'ficheinter';
			}
			if ($element == 'shipping') {
				$element = $subelement = 'expedition';
			}
			if ($element == 'order_supplier') {
				$element = 'fourn';
				$subelement = 'fournisseur.commande';
			}
			if ($element == 'project') {
				$element = 'projet';
			}

			dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');
			$classname = ucfirst($origin);
			$paymentsrc = new $classname($db);
			$paymentsrc->fetch($origin_id);

			$tmpobject = $paymentsrc;
		}
	}*/

	/*$contactarr = array();
	$contactarr = $tmpobject->liste_contact(-1, 'external');

	if (is_array($contactarr) && count($contactarr) > 0) {
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		$contactstatic = new Contact($db);

		foreach ($contactarr as $contact) {
			$contactstatic->fetch($contact['id']);
			$substitutionarray['__CONTACT_NAME_'.$contact['code'].'__'] = $contactstatic->getFullName($outputlangs, 1);
		}
	}*/

	// Tableau des substitutions
	$formmail->substit = $substitutionarray;

	// Tableau des parametres complementaires
	$formmail->param['action'] = 'send';
	$formmail->param['models'] = $modelmail;
	$formmail->param['models_id'] = GETPOST('modelmailselected', 'int');
	$formmail->param['id'] = $payment->id;
	$formmail->param['returnurl'] = $_SERVER["PHP_SELF"].'?id='.$payment->id;
	$formmail->param['fileinit'] = array($file);

	// Show form
	print $formmail->get_form();

	dol_fiche_end();
}
