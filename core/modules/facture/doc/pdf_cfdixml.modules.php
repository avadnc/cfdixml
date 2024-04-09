<?php
/* Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2005      Sylvain SCATTOLINI   <sylvain@s-infoservices.com>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008      Raphael Bertrand     <raphael.bertrand@resultic.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 * 		\file       htdocs/core/modules/facture/doc/pdf_oursin.modules.php
 * 		\ingroup    facture
 * 		\brief      Fichier de la classe permettant de generer les factures au modele oursin
 * 		\author	    Sylvain SCATTOLINI base sur un modele de Laurent Destailleur
 */



require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
include_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
dol_include_once('/cfdixml/lib/cfdixml.lib.php');
dol_include_once('/cfdixml/lib/phpqrcode/qrlib.php');




/**
 *  Classe permettant de generer les factures au modele oursin
 */
class pdf_cfdixml extends ModelePDFFactures
{
	var $marges = array("g" => 10, "h" => 5, "d" => 10, "b" => 15);

	var $phpmin = array(4, 3, 0); // Minimum version of PHP required by module
	var $version = 'dolibarr';

	var $emetteur;	// Objet societe qui emet
	var $checkpayment;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf, $langs, $mysoc;

		$langs->load("main");
		$langs->load("bills");
		$langs->load("products");
		$langs->load("cfdixml@cfdixml");
		$this->checkpayment = substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1);

		$this->db = $db;
		$this->name = "FacturaCfdixml";
		$this->description = "Representación impresa de un CFDI v. 4.0";

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = 10;
		$this->marge_droite = 10;
		$this->marge_haute = 10;
		$this->marge_basse = 10;

		$this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Gere choix mode reglement FACTURE_CHQ_NUMBER, FACTURE_RIB_NUMBER
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 0;                // Affiche si il y a eu escompte
		$this->option_credit_note = 1;             // Support credit note
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   // Support add of a watermark on drafts

		$this->franchise = !$mysoc->tva_assuj;

		// Recupere emmetteur
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2);    // Par defaut, si n'�tait pas d�fini

		// Defini position des colonnes
		$this->posxdesc = $this->marge_gauche + 1;
		$this->posxtva = 111;
		$this->posxup = 126;
		$this->posxqty = 145;
		$this->posxdiscount = 162;
		$this->postotalht = 174;

		$this->tva = array();
		$this->atleastoneratenotnull = 0;
		$this->atleastonediscount = 0;
	}


	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		int		$object				Id of object to generate
	 *  @param		object	$outputlangs		Lang output object
	 *  @param		string	$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int		$hidedetails		Do not show line details
	 *  @param		int		$hidedesc			Do not show desc
	 *  @param		int		$hideref			Do not show ref
	 *  @param		object	$hookmanager		Hookmanager object
	 *  @return     int             			1=OK, 0=KO
	 */
	function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $hookmanager = false)
	{
		global $user, $langs, $conf, $mysoc;

		if ($this->checkpayment !=  'payments.php') {

			if (!is_object($outputlangs)) $outputlangs = $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output = 'ISO-8859-1';

			$default_font_size = pdf_getPDFFontSize($outputlangs);
			$outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies"));
			// $outputlangs->load("main");
			// $outputlangs->load("dict");
			// $outputlangs->load("companies");
			// $outputlangs->load("bills");
			// $outputlangs->load("products");

			if ($conf->facture->dir_output) {
				$object->fetch_thirdparty();

				$deja_regle = $object->getSommePaiement();
				$amount_credit_notes_included = $object->getSumCreditNotesUsed();
				$amount_deposits_included = $object->getSumDepositsUsed();

				// Definition of $dir and $file
				if ($object->specimen) {
					$dir = $conf->facture->dir_output;
					$file = $dir . "/SPECIMEN.pdf";
				} else {
					$objectref = dol_sanitizeFileName($object->ref);
					$dir = $conf->facture->dir_output . "/" . $objectref;
					$file = $dir . "/" . $objectref . '_' . $object->array_options['options_cfdixml_UUID'] . ".pdf";
				}

				if (!file_exists($dir)) {
					if (dol_mkdir($dir) < 0) {
						$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
						return 0;
					}
				}

				if (file_exists($dir)) {
					$pdf = pdf_getInstance($this->format);
					$heightforinfotot = 50;	// Height reserved to output the info and total part
					$heightforfooter = 25;	// Height reserved to output the footer (value include bottom margin)
					$pdf->SetAutoPageBreak(1, 0);

					if (class_exists('TCPDF')) {
						$pdf->setPrintHeader(false);
						$pdf->setPrintFooter(false);
					}

					$pdf->SetFont(pdf_getPDFFont($outputlangs));
					// Set path to the background PDF File

					if (empty($conf->global->MAIN_DISABLE_FPDI) && !empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
						$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output . '/' . $conf->global->MAIN_ADD_PDF_BACKGROUND);
						$tplidx = $pdf->importPage(1);
					}

					$pdf->Open();
					$pagenb = 0;
					$pdf->SetDrawColor(128, 128, 128);

					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
					$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
					$pdf->SetCreator("Dolibarr " . DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("Invoice"));
					if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

					$nblignes = count($object->lines);	// evita PHP Warning: undefined varaible $nblignes
					
					// Positionne $this->atleastonediscount si on a au moins une remise
					for ($i = 0; $i < $nblignes; $i++) {
						if ($object->lines[$i]->remise_percent) {
							$this->atleastonediscount++;
						}
					}

					// New page
					$pdf->AddPage();
					if (!empty($tplidx)) $pdf->useTemplate($tplidx);
					$pagenb++;
					$this->_pagehead($pdf, $object, 1, $outputlangs, $hookmanager);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor(0, 0, 0);

					$tab_top = $this->marges['h'] + 90;
					$tab_top_newpage = $this->marges['h'];
					$tab_height = 110;
					$tab_height_newpage = 150;

					$pdf->SetFillColor(220, 220, 220);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetXY($this->marges['g'], $tab_top + $this->marges['g']);

					$iniY = $pdf->GetY();
					$curY = $pdf->GetY();
					$nexY = $pdf->GetY();
//					$nblignes = count($object->lines);

					// Loop on each lines
					for ($i = 0; $i < $nblignes; $i++) {
						$curY = $nexY;

						$pdf->setPageOrientation('', 1, $this->marge_basse + $heightforfooter + $heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
						$pageposbefore = $pdf->getPage();

						// Description of product line
						$hidedesc = $conf->global->CFDIXML_HIDEDESC;

						pdf_writelinedesc($pdf, $object, $i, $outputlangs, 65, 3, $this->posxdesc - 1, $curY + 1, $hideref, $hidedesc, 0, $hookmanager);

						$pageposafter = $pdf->getPage();
						$pdf->setPage($pageposbefore);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

						$nexY = $pdf->GetY();

						// UMED
						$pdf->SetXY($this->marges['g'] + 67, $curY);
						$product = new Product($this->db);
						// var_dump($object->lines[$i]->fk_product);exit;
						$product->fetch($object->lines[$i]->fk_product);

						if ($object->lines[$i]->array_options['options_cfdixml_umed']) {
							$pdf->MultiCell(12, 3, $object->lines[$i]->array_options['options_cfdixml_umed'], 0, 'R');
						} else {
							$pdf->MultiCell(12, 3, $product->array_options['options_cfdixml_umed'], 0, 'R');
						}
						// CLAVEPRODSERV

						$pdf->SetXY($this->marges['g'] + 87, $curY);
						if ($object->lines[$i]->array_options['options_cfdixml_claveprodserv']) {
							$pdf->MultiCell(18, 3, $object->lines[$i]->array_options['options_cfdixml_claveprodserv'], 0, 'R');
						} else {
							$pdf->MultiCell(18, 3, $product->array_options['options_cfdixml_claveprodserv'], 0, 'R');
						}
						if ($mysoc->useRevenueStamp()) {
							if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) {
								$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails, $hookmanager);
								$pdf->SetXY($this->marges['g'] + 106, $curY);
								$pdf->MultiCell(12, 3, $vat_rate, 0, 'R');
							}
							if ((float)$object->lines[$i]->ref_ext > 0) {
								$pdf->SetFont('', '', $default_font_size - 2);
								$pdf->SetXY($this->marges['g'] + 115, $curY);
								$pdf->MultiCell(20, 3, '001 - ' . number_format($object->lines[$i]->ref_ext, 2), 0, 'R');
								$pdf->SetFont('', '', $default_font_size - 1);
							}
						} else {
							// TVA
							if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) {
								$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails, $hookmanager);
								$pdf->SetXY($this->marges['g'] + 118, $curY);
								$pdf->MultiCell(12, 3, $vat_rate, 0, 'R');
							}
						}

						// Prix unitaire HT avant remise
						$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetXY($this->marges['g'] + 134, $curY);
						$pdf->MultiCell(16, 3, $up_excl_tax, 0, 'R', 0);

						// Quantity
						$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetXY($this->marges['g'] + 150, $curY);
						$pdf->MultiCell(10, 3, $qty, 0, 'R');

						// Remise sur ligne
						$pdf->SetXY($this->marges['g'] + 160, $curY);
						if ($object->lines[$i]->remise_percent) {
							$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails, $hookmanager);
							$pdf->MultiCell(14, 3, $remise_percent, 0, 'R');
						}

						// Total HT
						$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetXY($this->marges['g'] + 168, $curY);
						$pdf->MultiCell(21, 3, $total_excl_tax, 0, 'R', 0);

						// Detect if some page were added automatically and output _tableau for past pages
						// Detect if some page were added automatically and output _tableau for past pages
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							if ($pagenb == 1) {
								$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
							} else {
								$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
							}
							$this->_pagefoot($pdf, $object, $outputlangs, 1);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
							$conf->global->MAIN_PDF_DONOTREPEAT_HEAD = 1;
							if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
								$top_shift = $this->_pagehead($pdf, $object, 0, $outputlangs,null);
								$tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);
							}
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
						}
						if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
							if ($pagenb == 1) {
								$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
							} else {
								$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
							}
							$this->_pagefoot($pdf, $object, $outputlangs, 1);
							// New page
							$pdf->AddPage();
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							$pagenb++;
							
							if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
								$top_shift = $this->_pagehead($pdf, $object, 0, $outputlangs,null);
								$tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);
							}
						}
						/*if (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak){
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $tab_height + 40, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf,$object,$outputlangs);
						// New page
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;}*/
						// if (($nexY > 200 && $i < $nblignes - 1) || (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak)) {
						// 	$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs, 0, 1);
						// 	$nexY = $iniY;

						// 	// New page
						// 	$pdf->AddPage();
						// 	if (!empty($tplidx)) $pdf->useTemplate($tplidx);
						// 	$pagenb++;
						// 	$this->_pagehead($pdf, $object, 0, $outputlangs, $hookmanager);
						// 	$pdf->SetFont('', '', $default_font_size - 1);
						// 	$pdf->MultiCell(0, 3, '');		// Set interline to 3
						// 	$pdf->SetTextColor(0, 0, 0);
						// }
					}

					// Show square
					if ($pagenb == 1) {
						$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
						$bottomlasttab = $tab_top + $tab_height + 1;
					} else {
						//$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs, 1, 0);
						$bottomlasttab = $tab_top + $tab_height + 1;
					}

					// Affiche zone infos
//JGG					$posy = $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

					// Affiche zone totaux
					$posy = $this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

					// Affiche zone versements
//					if ($deja_regle || $amount_credit_notes_included || $amount_deposits_included) {
//						$posy = $this->_tableau_versements($pdf, $object, $posy, $outputlangs);
//					}

					// Pied de page
					$this->_pagefoot($pdf, $object, $outputlangs);
					if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

					$pdf->Close();

					$pdf->Output($file, 'F');

					// Actions on extra fields (by external module or standard code)
					if (!is_object($hookmanager)) {
						include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
						$hookmanager = new HookManager($this->db);
					}
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					global $action;
					$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

					if (!empty($conf->global->MAIN_UMASK))
						@chmod($file, octdec($conf->global->MAIN_UMASK));

					return 1;   // Pas d'erreur
				} else {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			} else {
				$this->error = $langs->transnoentities("ErrorConstantNotDefined", "FAC_OUTPUTDIR");
				return 0;
			}
			$this->error = $langs->transnoentities("ErrorUnknown");
			return 0;   // Erreur par defaut
		} else {

			$payment = new Paiement($this->db);
			$payment->fetch(GETPOST('id', 'int'));
			$payment->fetch_optionals();


			//PDF PAGOS
			if (!is_object($outputlangs)) $outputlangs = $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output = 'ISO-8859-1';

			$default_font_size = pdf_getPDFFontSize($outputlangs) -1;

			$outputlangs->load("main");
			$outputlangs->load("dict");
			$outputlangs->load("companies");
			$outputlangs->load("bills");
			$outputlangs->load("products");

			if ($conf->facture->dir_output) {
				$object->fetch_thirdparty();

				$deja_regle = $object->getSommePaiement();
				$amount_credit_notes_included = $object->getSumCreditNotesUsed();
				$amount_deposits_included = $object->getSumDepositsUsed();

				// Definition of $dir and $file
				if ($object->specimen) {
					$dir = $conf->facture->dir_output;
					$file = $dir . "/SPECIMEN.pdf";
				} else {
					$objectref = dol_sanitizeFileName($payment->ref);
					$dir = $conf->cfdixml->dir_output . "/payment/" . $payment->ref;
					$file = $dir . "/" . $objectref . '_' . $payment->array_options['options_cfdixml_UUID'] . ".pdf";
				}

				if (!file_exists($dir)) {
					if (dol_mkdir($dir) < 0) {
						$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
						return 0;
					}
				}

				if (file_exists($dir)) {
					$pdf = pdf_getInstance($this->format);
					$heightforinfotot = 50;	// Height reserved to output the info and total part
					$heightforfooter = 25;	// Height reserved to output the footer (value include bottom margin)
					$pdf->SetAutoPageBreak(1, 0);

					if (class_exists('TCPDF')) {
						$pdf->setPrintHeader(false);
						$pdf->setPrintFooter(false);
					}

					$pdf->SetFont(pdf_getPDFFont($outputlangs));
					// Set path to the background PDF File

					if (empty($conf->global->MAIN_DISABLE_FPDI) && !empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
						$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output . '/' . $conf->global->MAIN_ADD_PDF_BACKGROUND);
						$tplidx = $pdf->importPage(1);
					}

					$pdf->Open();
					$pagenb = 0;
					$pdf->SetDrawColor(128, 128, 128);

					$pdf->SetTitle($outputlangs->convToOutputCharset($payment->ref));
					$pdf->SetSubject($outputlangs->transnoentities("Payment"));
					$pdf->SetCreator("Dolibarr " . DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($payment->ref) . " " . $outputlangs->transnoentities("Payment"));
					if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
					
					$nblignes = count($object->lines); 	// previene PHP Warning undefined variable $nblignes

					// Positionne $this->atleastonediscount si on a au moins une remise
					for ($i = 0; $i < $nblignes; $i++) {
						if ($object->lines[$i]->remise_percent) {
							$this->atleastonediscount++;
						}
					}

					// New page
					$pdf->AddPage();
					if (!empty($tplidx)) $pdf->useTemplate($tplidx);
					$pagenb++;
					$this->_pagehead($pdf, $object, 1, $outputlangs, $hookmanager);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor(0, 0, 0);

					$tab_top = $this->marges['h'] + 85;
					$tab_top_newpage = $this->marges['h'] +100;
					$tab_height = 110;
					$tab_height_newpage = 150;

					$pdf->SetFillColor(220, 220, 220);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$pdf->SetXY($this->marges['g'], $tab_top + $this->marges['g'] - 20);

					$iniY = $pdf->GetY();
					$curY = $pdf->GetY();



					// $table = ;
					// $pdf->MultiCell(190,5,"",1,'L');

					$posxcell = $pdf->GetX() + 6;
					$posycell = $pdf->GetY();

					//Header table
					$pdf->SetXY($posxcell, $posycell - 4.5);
					$pdf->MultiCell(30, 1, "Cantidad", 1, 'L');
					$pdf->SetXY($posxcell + 30, $posycell - 4.5);
					$pdf->MultiCell(30, 1, "UMED", 1, 'L');
					$pdf->SetXY($posxcell + 60, $posycell - 4.5);
					$pdf->MultiCell(90, 1, "Descripción", 1, 'L');
					$pdf->SetXY($posxcell + 120, $posycell - 4.5);
					$pdf->MultiCell(30, 1, "Precio Unitario", 1, 'L');
					$pdf->SetXY($posxcell + 150, $posycell - 4.5);
					$pdf->MultiCell(30, 1, "Importe", 1, 'L');

					$pdf->SetFont('', '', $default_font_size - 1);
					//Body table
					$pdf->SetXY($posxcell, $posycell);
					$pdf->MultiCell(30, 1, "  1  ", 1, 'L');
					$pdf->SetXY($posxcell + 30, $posycell);
					$pdf->MultiCell(30, 1, "  ACT  ", 1, 'L');
					$pdf->SetXY($posxcell + 60, $posycell);
					$pdf->MultiCell(90, 1, "  PAGO  ", 1, 'L');
					$pdf->SetXY($posxcell + 120, $posycell);
					$pdf->MultiCell(30, 1, "  $0.00  ", 1, 'L');
					$pdf->SetXY($posxcell + 150, $posycell);
					$pdf->MultiCell(30, 1, "  $0.00  ", 1, 'L');


				//PAYMENT TABLE
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$posy1 = $pdf->GetY();
					$posxcell = $pdf->GetX();
					$pdf->SetXY($posxcell + 6, $posy1 );
					$pdf->MultiCell(180, 1, "Pago", 0, 'C');
					
					$posycell = $pdf->GetY();
					
					$pdf->SetFont('', '', $default_font_size - 1);
					$posy1 = $pdf->GetY();
					$pdf->SetXY($posxcell + 6, $posy1);
//					$pdf->MultiCell(90, 0, "Fecha de pago : " . $payment->array_options['options_cfdixml_fechatimbrado'], 0, 'L');
					$pdf->MultiCell(90, 0, "Fecha de pago : " . date("Y-m-d H:i:s", $payment->datepaye), 0, 'L');	// Fecha de pago en lugar de fecha de timbrado

					$pdf->SetXY($posxcell + 60, $posy1);
					$pdf->MultiCell(90, 0, "Forma de pago : " . $payment->type_label, 0, 'L');

					$pdf->SetXY($posxcell + 140, $posy1);
					$pdf->MultiCell(90, 0, "Tipo de comprobante: P", 0, 'L');
					
					
					$posy1 = $pdf->GetY();
					$pdf->SetXY($posxcell + 6, $posy1);
					$pdf->MultiCell(90, 0, "Exportación: 01", 0, 'L');

					$pdf->SetXY($posxcell + 60, $posy1);
					if ($payment->multicurrency_amount != $payment->amount) {
						$moneda = $object->multicurrency_code;
						$monto = price2num($payment->multicurrency_amount);
					} else {
						$moneda = $conf->currency;
						$monto = price2num($payment->amount);
					}
					$pdf->MultiCell(90, 0, "Moneda de pago : " . $moneda, 0, 'L');
					
					$pdf->SetXY($posxcell + 140, $posy1);
					$pdf->MultiCell(90, 0, "Monto: " . number_format($monto, 2), 0, 'L');
			$altoPagos = 8;
					$pdf->SetXY($posxcell + 6, $posycell);
					$pdf->RoundedRectXY($posxcell + 6, $posycell, 180, $altoPagos, 2, 2, '0110', 'S', '', '255,255,255'); // JGG

// Documentos relacionados
					$posxcell = $pdf->GetX();
					$posycell = $pdf->GetY() + $altoPagos + 2;
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$pdf->SetXY($posxcell , $posycell );
					$pdf->MultiCell(180, 1, "Documentos Relacionados", 1, 'C');

					$pdf->SetFont('', '', $default_font_size - 1);
					$filename = dol_sanitizeFileName($payment->ref);
					$filedir = $conf->cfdixml->multidir_output[$conf->entity] . '/payment/' . dol_sanitizeFileName($payment->ref);

					if (file_exists($filedir . '/' . $filename . '_' . $payment->array_options['options_cfdixml_UUID'] . '.xml')) {
						$data = file_get_contents($filedir . '/' . $filename . '_' . $payment->array_options['options_cfdixml_UUID'] . '.xml');
						/* Mostrar pagos relacionados */
						$cfdi = \CfdiUtils\Cfdi::newFromString($data);
						$cfdi->getVersion(); // (string) 3.3
						$cfdi->getDocument(); // clon del objeto DOMDocument
						$cfdi->getSource(); // (string) <cfdi:Comprobante...
						$comprobante = $cfdi->getNode();
						$pagos = $comprobante->searchNodes('cfdi:Complemento', 'pago20:Pagos', 'pago20:Pago');
						$pagoCounter = 0;
						$pagoCount = $pagos->count();

//						$pdf->SetXY($posxcell , $posycell +20);
//						$pdf->MultiCell(180, 1, "Numero de Pagos: $pagoCount", 1, 'C');
						
						foreach ($pagos as $pago) {

							$pagoCounter = $pagoCounter + 1;
							$doctoRelacionados = $pago->searchNodes('pago20:DoctoRelacionado');

							$pagoCount = $doctoRelacionados->count();
//						$pdf->SetXY($posxcell , $posycell +20);
//						$pdf->MultiCell(180, 1, "Docs Relacionados: $pagoCount", 1, 'C');					

							foreach ($doctoRelacionados as $doctoRelacionado) {
//								if ($doctoRelacionado['IdDocumento'] == $object->array_options['options_cfdixml_UUID']) {
									// echo '<pre>';print_r($doctoRelacionado);exit;
									$posycell = $pdf->GetY();
									$posy1 = $posycell;
									$marcoini = $posycell; //inicio marco
									
									$pdf->SetXY($posxcell , $posycell);
									$pdf->MultiCell(180, 1, "IdDocumento: " . $doctoRelacionado['IdDocumento'], 0, 'L');

									$pdf->SetXY($posxcell + 90, $posycell);
									$pdf->MultiCell(180, 1, "Moneda: " . $doctoRelacionado['MonedaDR'], 0, 'L');

									$pdf->SetXY($posxcell + 130, $posycell);
									$pdf->MultiCell(180, 1, "Parcialidad: " . $doctoRelacionado['NumParcialidad'], 0, 'L');

									$posycell = $pdf->GetY();
									$pdf->SetXY($posxcell, $posycell);
									$pdf->MultiCell(180, 1, "Saldo anterior: " . $doctoRelacionado['ImpSaldoAnt'], 0, 'L');

									$pdf->SetXY($posxcell + 90, $posycell);
									$pdf->MultiCell(180, 1, "Imp. pagado: " . $doctoRelacionado['ImpPagado'], 0, 'L');

									$pdf->SetXY($posxcell + 130, $posycell);
									$pdf->MultiCell(180, 1, "Saldo insoluto: " . $doctoRelacionado['ImpSaldoInsoluto'], 0, 'L');

									$pdf->SetFont('', 'B', $default_font_size - 1);
									$posycell = $pdf->GetY() ;
//									$pdf->SetXY($posxcell, $posycell);
//									$pdf->MultiCell(180, 1, "Impuestos Relacionados", 0, 'C');
									$pdf->SetFont('', '', $default_font_size - 1);
//									$pdf->SetXY($posxcell, $posycell + $y + 16);

									$impuestos = $doctoRelacionado->searchNode('pago20:ImpuestosDR');
									if (null !== $impuestos) {
										$retenciones = $impuestos->searchNodes('pago20:RetencionesDR', 'pago20:RetencionDR');
										$traslados = $impuestos->searchNodes('pago20:TrasladosDR', 'pago20:TrasladoDR');
										foreach ($traslados as $impuesto) {
											$posycell = $pdf->GetY();
											$posy1 = $posycell;
											$pdf->SetXY($posxcell , $posy1);
											$pdf->MultiCell(180, 1, "Tipo de Impuesto: Traslado", 0, 'L');

											$pdf->SetXY($posxcell + 40, $posycell);
											$pdf->MultiCell(180, 1, "Impuesto: " . $impuesto['ImpuestoDR'], 0, 'L');

											$pdf->SetXY($posxcell + 80, $posycell);
											$pdf->MultiCell(180, 1, "Tipo factor: " . $impuesto['TipoFactorDR'], 0, 'L');

											$pdf->SetXY($posxcell + 120, $posycell);
											$pdf->MultiCell(180, 1, "Tasa o cuota: " . $impuesto['TasaOCuotaDR'], 0, 'L');

											$posycell = $pdf->GetY();
											$pdf->SetXY($posxcell +80, $posycell);
											$pdf->MultiCell(180, 1, "Base: " . number_format($impuesto['BaseDR'], 2), 0, 'L');

											$pdf->SetXY($posxcell + 120, $posycell);
											$pdf->MultiCell(180, 1, "Importe: " . number_format($impuesto['ImporteDR'], 2), 0, 'L');
										}
									}
									$marcofin = $pdf->GetY(); //inicio marco

									$pdf->SetXY($posxcell , $marcoini);
									$pdf->MultiCell(180, $marcofin - $marcoini, "", 1, 'C');
//								}
							}
						}
					}
					$nexY = $marcofin; //$pdf->GetY();

					// Show square
					if ($pagenb == 1) {
//						$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
//						$bottomlasttab = $tab_top + $tab_height + 1;
						$bottomlasttab = $this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
					} else {
						// $this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs, 1, 0);
						// $bottomlasttab = $tab_top + $tab_height + 1;
						$bottomlasttab = $this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs, 1, 0);
					}
					
//					$bottomlasttab = 190;

					// Affiche zone infos
//					$posy = $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

					// Affiche zone totaux
					$posy = $this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

					// Pied de page
//					$this->_pagefoot($pdf, $object, $outputlangs);
					if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

					$pdf->Close();

					$pdf->Output($file, 'F');

					// Actions on extra fields (by external module or standard code)
					if (!is_object($hookmanager)) {
						include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
						$hookmanager = new HookManager($this->db);
					}
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					global $action;
					$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

					if (!empty($conf->global->MAIN_UMASK))
						@chmod($file, octdec($conf->global->MAIN_UMASK));

					return 1;   // Pas d'erreur
				} else {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			} else {
				$this->error = $langs->transnoentities("ErrorConstantNotDefined", "FAC_OUTPUTDIR");
				return 0;
			}
			$this->error = $langs->transnoentities("ErrorUnknown");
			return 0;   // Erreur par defaut
		}
	}


	/**
	 *  Show payments table
	 *
	 *  @param	PDF			$pdf           Object PDF
	 *  @param  Object		$object         Object invoice
	 *  @param  int			$posy           Position y in PDF
	 *  @param  Translate	$outputlangs    Object langs for output
	 *  @return int             			<0 if KO, >0 if OK
	 */
//	function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
//	{

//	}

	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		Object		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if ($this->checkpayment ==  'payments.php') {
			$payment = new Paiement($this->db);
			$payment->fetch(GETPOST('id', 'int'));
			$payment->fetch_optionals();
			$object = null;
			$object = new stdClass();
			$object = $payment;
		}

		//UUID
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->MultiCell(10, 4, 'UUID: ', 0, 'L');

		$pdf->SetFont('', '', $default_font_size - 2);
		$posx = $pdf->GetX() + 10;
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell(70, 4, $object->array_options['options_cfdixml_UUID'], 0, 'L');

		//Cert SAT
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$posx = $pdf->GetX() + 80;
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell(50, 4, 'Cert SAT: ', 0, 'L');

		$pdf->SetFont('', '', $default_font_size - 2);
		$posx = $pdf->GetX() + 95;
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell(80, 4, $object->array_options['options_cfdixml_certsat'], 0, 'L');


		//Cer Emisor
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($posx +40, $posy);
		$pdf->MultiCell(60, 4, 'Cert Emisor: ', 0, 'L');

		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($posx + 60, $posy);
		$pdf->MultiCell(40, 4, $object->array_options['options_cfdixml_certcfd'], 0, 'L');

		$posx = $pdf->GetX();
		$posy = $pdf->Gety();
		//Fecha Emision
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($posx, $posy );
		$pdf->MultiCell(20, 4, 'F. Emisión: ', 0, 'L');
		//Cambiar fecha emisión
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($posx + 18, $posy );
		$pdf->MultiCell(35, 4, $object->array_options['options_cfdixml_fechatimbrado'], 0, 'L');
		//Fecha Certificación
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($posx + 50, $posy);
		$pdf->MultiCell(25, 4, 'F. Certificación: ', 0, 'L');

		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($posx + 75, $posy);
		$pdf->MultiCell(35, 4, $object->array_options['options_cfdixml_fechatimbrado'], 0, 'L');


		//Sello Digital Emisor
		$posy = $pdf->Gety();
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche, $posy );
		$pdf->MultiCell(80, 4, 'Sello digital del emisor: ', 0, 'L');

		$posy = $pdf->Gety();
		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->SetXY(5, $posy );
		$pdf->MultiCell(157, 4, $object->array_options['options_cfdixml_sellocfd'], 0, 'L');

		//Sello Digital SAT
		$posy = $pdf->Gety();
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->MultiCell(80, 4, 'Sello digital del SAT: ', 0, 'L');

		$posy = $pdf->Gety();
		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->SetXY(5, $posy);
		$pdf->MultiCell(157, 4, $object->array_options['options_cfdixml_sellosat'], 0, 'L');

		//Sello Digital SAT
		$posy = $pdf->Gety();
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche, $posy );
		$pdf->MultiCell(80, 4, 'Cadena original: ', 0, 'L');

		$posy = $pdf->Gety();
		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->SetXY(5, $posy);
		$pdf->MultiCell(157, 4, $object->array_options['options_cfdixml_cadenaorig'], 0, 'L');
		$posy = $pdf->Gety();

		return $posy;
	}


	/**
	 *	Show total to pay
	 *
	 *	@param	PDF			$pdf           Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf, $langs, $mysoc;

		if ($this->checkpayment !=  'payments.php') {


			$sign = 1;
			if ($object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign = -1;

			$langs->load("main");
			$langs->load("bills");

			$default_font_size = pdf_getPDFFontSize($outputlangs);

			$tab2_top = $this->marges['h'] + 202;
			$tab2_hl = 4;
			$pdf->SetFont('', '', $default_font_size - 1);

			// Tableau total
			$col1x = $this->marges['g'] + 135;
			$col2x = $this->marges['g'] + 164;
			$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);

			$pdf->SetXY($this->marges['g'], $tab2_top + 0);

			$useborder = 0;
			$index = 0;

			// Total HT
			$pdf->SetXY($col1x, $tab2_top + 0);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 0);
			$pdf->SetXY($col2x, $tab2_top + 0);
			if ($object->multicurrency_code != "MXN") {
				$pdf->MultiCell($largcol2, $tab2_hl, price($sign * ($object->multicurrency_total_ht + $object->remise)), 0, 'R', 0);
			} else {
				$pdf->MultiCell($largcol2, $tab2_hl, price($sign * ($object->total_ht + $object->remise)), 0, 'R', 0);
			}

			// Show VAT by rates and total
			$pdf->SetFillColor(248, 248, 248);

			$this->atleastoneratenotnull = 0;
			if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) {
				foreach ($this->tva as $tvakey => $tvaval) {
					if ($tvakey > 0) {    // On affiche pas taux 0
						$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
						$tvacompl = '';
						if (preg_match('/\*/', $tvakey)) {
							$tvakey = str_replace('*', '', $tvakey);
							$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
						}
						$totalvat = $outputlangs->transnoentities("TotalVAT") . ' ';
						$totalvat .= vatrate($tvakey, 1) . $tvacompl;
						$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);
						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $tvaval), 0, 'R', 1);
					}
				}

				if (!$this->atleastoneratenotnull) {	// If no vat at all
					if ($mysoc->useRevenueStamp()) {

						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($col2x - $col1x, $tab2_hl, "Retencion ISR", 0, 'L', 1);
						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->revenuestamp), 0, 'R', 1);
					}
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalVAT"), 0, 'L', 1);
					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					if ($object->multicurrency_code != "MXN") {
						$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->multicurrency_total_tva), 0, 'R', 1);
					} else {
						$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->total_tva), 0, 'R', 1);
					}
				}
			}

			// Total TTC
			if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) {
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->SetTextColor(22, 137, 210);
				$pdf->SetFont('', 'B', $default_font_size + 1);
				$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), 0, 'L', 0);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				if ($object->multicurrency_code != "MXN") {
					$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->multicurrency_total_ttc), 0, 'R', 0);
				} else {
					$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->total_ttc), 0, 'R', 0);
				}
				$pdf->SetTextColor(0, 0, 0);
			}

			$comprobante = getComprobanteAtributos($object, $object->array_options['options_cfdixml_fechatibmrado']);
			$emisor = getEmisor();
			$receptor = new Societe($this->db);
			$receptor->fetch($object->socid);
			if ($comprobante['Moneda'] == 'MXN') $tipoDivisa = "PESOS";
			if ($comprobante['Moneda'] == 'USD') $tipoDivisa = "DÓLARES";
			if ($comprobante['Moneda'] == 'EUR') $tipoDivisa = "EUROS";
			if ($object->multicurrency_code != "MXN") {
				$letras = utf8_decode(num2letras($object->multicurrency_total_ttc, 0, 0) . ' ' . $tipoDivisa);
			} else {
				$letras = utf8_decode(num2letras($object->total_ttc, 0, 0) . ' ' . $tipoDivisa);
			}
			$letras_len = strlen($letras);
			$letras_substr = substr($letras, $letras_len - 2, $letras_len);
			if ($letras_substr == "SS") $letras = substr($letras, 0, $letras_len - 1);

			if ($object->multicurrency_code != "MXN") {
				$ultimo = substr(strrchr(number_format($object->multicurrency_total_ttc, 2), "."), 1); //recupero lo que este despues del decimal
			} else {
				$ultimo = substr(strrchr(number_format($object->total_ttc, 2), "."), 1); //recupero lo que este despues del decimal

			}
			$letras = strtoupper($letras) . " " . $ultimo . "/100 " . ($comprobante['Moneda'] == "MXN" ? "MN" : "ME");
			$index++;
			$pdf->SetFont('', '', $default_font_size);
			$pdf->SetXY($col2x - 80, $tab2_top + $tab2_hl * $index + 2);
			$pdf->MultiCell($largcol2 + 80, $tab2_hl, $letras, 0, 'R', 0);

			//QR CODE

			$data_cbb = 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=' . $object->array_options["options_cfdixml_UUID"] . '&re=' . $emisor["Rfc"] . '&rr=' . $receptor->idprof1 . '&tt=' . $object->total_ttc . '&fe=' . substr($object->array_options["options_cfdixml_sellocfd"], -8);
			QRcode::png($data_cbb, $conf->facture->dir_output . "/" . $object->ref . "/" . $object->ref . '_' . $object->array_options["options_cfdixml_UUID"] . ".png");
			$y = 150;
			$x = $pdf->getX() + 5;
			// $x = 15;

			$w = 30;
			$h = 30;
			$pdf->Image($conf->facture->dir_output . "/" . $object->ref . "/" . $object->ref . '_' . $object->array_options["options_cfdixml_UUID"] . ".png", $col2x - 20, $tab2_top + $tab2_hl * $index + 6, 50, 50, 'PNG', '');
		} else {
			$payment = new Paiement($this->db);
			$payment->fetch(GETPOST('id', 'int'));
			$payment->fetch_optionals();
			$object = null;
			$object = new stdClass();
			$object = $payment;


			$default_font_size = pdf_getPDFFontSize($outputlangs);

			$tab2_top = $this->marges['h'] + 202;
			$tab2_top = ($posy < 217) ? 217 : $posy;
			$tab2_hl = 4;
			$pdf->SetFont('', '', $default_font_size - 1);

			// Tableau total
			$col1x = $this->marges['g'] + 135;
			$col2x = $this->marges['g'] + 164;
			$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);

			$pdf->SetXY($this->marges['g'], $tab2_top + 0);

			$useborder = 0;
			$index = 0;

			//QR CODE
			$emisor = getEmisor();
			$receptor = new Societe($this->db);
			$receptor->fetch($object->socid);
			$data_cbb = 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=' . $object->array_options["options_cfdixml_UUID"] . '&re=' . $emisor["Rfc"] . '&rr=' . $receptor->idprof1 . '&tt=' . $object->total_ttc . '&fe=' . substr($object->array_options["options_cfdixml_sellocfd"], -8);
			$filename = dol_sanitizeFileName($payment->ref);
			$filedir = $conf->cfdixml->multidir_output[$conf->entity] . '/payment/' . dol_sanitizeFileName($payment->ref);
			QRcode::png($data_cbb,  $filedir . "/" . $object->ref . '_' . $object->array_options["options_cfdixml_UUID"] . ".png");
			$y = 150;
			$x = $pdf->getX() + 5;
			// $x = 15;

			$w = 50;
			$h = 50;
			$pdf->Image($filedir . "/" . $object->ref . '_' . $object->array_options["options_cfdixml_UUID"] . ".png", $col2x - 20, $tab2_top + $tab2_hl * $index + 6, $w, $h, 'PNG', '');
			$posy = $pdf->getY();
			$posy = $this->_tableau_info($pdf, $object, $posy, $outputlangs);
//JGG			
		}

		// $creditnoteamount=$object->getSumCreditNotesUsed();
		// $depositsamount=$object->getSumDepositsUsed();
		// $resteapayer = price2num($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		// if ($object->paye) $resteapayer=0;

		// if ($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0) {
		// 	$pdf->SetFont('', '', $default_font_size);

		// 	// Already paid + Deposits
		// 	$index++;
		// 	$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		// 	$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPaid"), 0, 'L', 0);
		// 	$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		// 	$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle + $depositsamount), 0, 'R', 0);

		// 	// Credit note
		// 	if ($creditnoteamount) {
		// 		$index++;
		// 		$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		// 		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("CreditNotes"), 0, 'L', 0);
		// 		$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		// 		$pdf->MultiCell($largcol2, $tab2_hl, price($creditnoteamount), 0, 'R', 0);
		// 	}

		// 	// Escompte
		// 	if ($object->close_code == 'discount_vat') {
		// 		$index++;
		// 		$pdf->SetFillColor(255, 255, 255);

		// 		$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		// 		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOffered"), $useborder, 'L', 1);
		// 		$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		// 		$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount), $useborder, 'R', 1);

		// 		$resteapayer=0;
		// 	}

		// 	$index++;
		// 	$pdf->SetTextColor(0, 0, 60);
		// 	$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		// 	$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), 0, 'L', 0);
		// 	$pdf->SetFillColor(224, 224, 224);
		// 	$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		// 	$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer), 0, 'R', 0);

		// 	// Fin
		// 	$pdf->SetFont('', 'B', $default_font_size + 1);
		// 	$pdf->SetTextColor(0, 0, 0);
		// }

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
		if ($this->checkpayment !=  'payments.php') {

			global $conf, $mysoc;

			$default_font_size = pdf_getPDFFontSize($outputlangs);


			$pdf->line($this->marges['g'], $tab_top + 8, 210 - $this->marges['d'], $tab_top + 8);
			$pdf->line($this->marges['g'], $tab_top + $tab_height, 210 - $this->marges['d'], $tab_top + $tab_height);

			$pdf->SetFont('', '', $default_font_size - 1);

			$pdf->SetXY($this->marges['g'], $tab_top + 1);
			$pdf->MultiCell(0, 4, $outputlangs->transnoentities("Designation"), 0, 'L');

			//UMED
			$pdf->SetXY($this->marges['g'] + 70, $tab_top + 1);
			$pdf->MultiCell(0, 4, 'UMED', 0, 'L');

			//CLAVEPRODSERV
			$pdf->SetXY($this->marges['g'] + 90, $tab_top + 1);
			$pdf->MultiCell(0, 4, 'Clave SAT', 0, 'L');

			if ($mysoc->useRevenueStamp()) {
				if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) {
					$pdf->SetXY($this->marges['g'] + 110, $tab_top + 1);
					$pdf->MultiCell(0, 4, $outputlangs->transnoentities("VAT"), 0, 'L');
				}
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marges['g'] + 118, $tab_top + 1);
				$pdf->MultiCell(0, 4, "TasaOCuota", 0, 'L');
			} else {
				if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) {
					$pdf->SetXY($this->marges['g'] + 120, $tab_top + 1);
					$pdf->MultiCell(0, 4, $outputlangs->transnoentities("VAT"), 0, 'L');
				}
			}
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($this->marges['g'] + 140, $tab_top + 1);
			$pdf->MultiCell(0, 4, $outputlangs->transnoentities("PriceUHT"), 0, 'L');
			$pdf->SetXY($this->marges['g'] + 153, $tab_top + 1);
			$pdf->MultiCell(0, 4, $outputlangs->transnoentities("Qty"), 0, 'L');

			if ($this->atleastonediscount) {
				$pdf->SetXY($this->marges['g'] + 165, $tab_top + 1);
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities("%"), 0, 'L');
			}
			$pdf->SetXY($this->marges['g'] + 170, $tab_top + 1);
			$pdf->MultiCell(20, 4, $outputlangs->transnoentities("TotalHTShort"), 0, 'R');
		}
		return $pdf->GetY();
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	object		$hookmanager	Hookmanager object
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $hookmanager)
	{
		global $langs, $conf;
		$langs->load("main");
		$langs->load("bills");
		$langs->load("propal");
		$langs->load("companies");

		$payment = new Paiement($this->db);
		$payment->fetch(GETPOST('id', 'int'));
		$payment->fetch_optionals();

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if ($object->statut == 0 && (!empty($conf->global->FACTURE_DRAFT_WATERMARK))) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->FACTURE_DRAFT_WATERMARK);
		}

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$pdf->SetXY($this->marges['g'], 6);

		// Logo
		$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
		if ($this->emetteur->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $this->marges['g'], $this->marges['h'], 0, $height);	// width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(80, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell(80, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur);

			// Show sender
			$posy = 35;
			$posx = $this->marge_gauche;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->page_largeur - $this->marge_droite - 80;
			$hautcadre = 40;

			// Show sender frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy - 5);
			$pdf->MultiCell(66, 5, "Emisor :", 0, 'L');
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(255, 255, 255);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0, 0, 60);

			// Show sender name
			$pdf->SetXY($posx + 2, $posy );
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');

			//Datos fiscales
			$emisor = getEmisor();

//			$pdf->SetXY($posx + 2, $posy + 8);
//			$pdf->SetFont('', 'B', $default_font_size);
//			$pdf->MultiCell(80, 4, "RFC: " . $emisor['Rfc'], 0, 'L');
			// var_dump($emisor);
			//Regimen Fiscal
			$posy = $pdf->GetY();
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', 'B', $default_font_size);
//			$pdf->MultiCell(80, 4, "R. Fiscal: " . $emisor['RegimenFiscal'], 0, 'L');
			$RegimenFiscal = getValueCfdixml($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE, 'c_forme_juridique'); //getRegimenFiscal($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
			$pdf->MultiCell(80, 4, "R. Fiscal: " . $RegimenFiscal, 0, 'L');


			// Show sender information
			$posy = $pdf->GetY();
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');

			// If BILLING contact defined on invoice, we use it
			$usecontact = false;
			$arrayidcontact = $object->getIdContact('external', 'BILLING');
			if (count($arrayidcontact) > 0) {
				$usecontact = true;
				$result = $object->fetch_contact($arrayidcontact[0]);
			}

			// Recipient name
			if ($usecontact && ($object->contact->socid != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}



			$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);
			$mode =  'target';
			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object);

			if (strpos($carac_client, "R.F.C.")) {
				$validaterfc = true;
			}
			// $carac_client=pdf_build_address($outputlangs, $this->emetteur, $object->client, $object->contact, $usecontact, 'target', $object);

			// Show recipient
			$posy = 25;
			$posx = $this->page_largeur - $this->marge_droite - 100;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx + 2, $posy - 5);
			$pdf->MultiCell(80, 5, "Receptor :", 0, 'L');
			
			$marcoini = $pdf->GetY();
			// Show recipient name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(96, 4, $carac_client_name, 0, 'L');

			// Show recipient information
			$posy = $pdf->GetY();
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell(86, 4, $carac_client, 0, 'L');

			//CFDIXML
			$posy = $pdf->GetY();
			$societe = new Societe($this->db);
			$societe->fetch($object->socid);
			// echo '<pre>'; print_r($societe);exit;
			if (!$validaterfc) {

				//RFC
				$pdf->SetXY($posx + 2, $posy);
				$pdf->SetFont('', '', $default_font_size);
				$pdf->MultiCell(96, 4, 'RFC: ' . $societe->idprof1, 0, 'L');
			}

			//REGIMEN FISCAL
			$posy = $pdf->GetY();
			$pdf->SetXY($posx + 2, $posy );
			$pdf->SetFont('', '', $default_font_size);
			$pdf->MultiCell(96, 4, 'R. Fiscal: ' . $societe->forme_juridique, 0, 'L');
		}


		if ($this->checkpayment !=  'payments.php') {

			if ($showaddress) {
				//USO CFDI FISCAL
				$posy = $pdf->GetY();
				$pdf->SetXY($posx + 2, $posy);
				$pdf->SetFont('', '', $default_font_size);
				$pdf->MultiCell(96, 4, 'Uso CFDI: ' . $object->array_options['options_cfdixml_usocfdi'], 0, 'L');
			}

			/*
		 	* ref facture
		 	*/
			$posy = 78;
			$posy = $pdf->GetY();
			$pdf->SetFont('', 'B', $default_font_size + 2);
			$pdf->SetXY($this->marges['g'], $posy - 5);
			$pdf->SetTextColor(0, 0, 0);
			$title = $outputlangs->transnoentities("Invoice");
			if ($object->type == 1) $title = $outputlangs->transnoentities("InvoiceReplacement");
			if ($object->type == 2) $title = $outputlangs->transnoentities("InvoiceAvoir");
			if ($object->type == 3) $title = $outputlangs->transnoentities("InvoiceDeposit");
			if ($object->type == 4) $title = $outputlangs->transnoentities("InvoiceProForma");


			$pdf->MultiCell(100, 10, $title . ' ' . $outputlangs->transnoentities("Of") . ' ' . dol_print_date($object->date, "day", false, $outputlangs, true), '', 'L');
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->SetXY($this->marges['g'], $posy);
			$pdf->SetTextColor(22, 137, 210);
			$pdf->MultiCell(100, 10, $outputlangs->transnoentities("RefBill") . " : " . $outputlangs->transnoentities($object->ref), '', 'L');
			$pdf->SetTextColor(0, 0, 0);

			$objectidnext = $object->getIdReplacingInvoice('validated');

			if ($object->type == 0 && $objectidnext) {
				$objectreplacing = new Facture($this->db);
				$objectreplacing->fetch($objectidnext);

				$posy += 4;
				$pdf->SetXY($this->marges['g'], $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ReplacementByInvoice") . ' : ' . $outputlangs->convToOutputCharset($objectreplacing->ref), '', 'L');
			}
			if ($object->type == 1) {
				$objectreplaced = new Facture($this->db);
				$objectreplaced->fetch($object->fk_facture_source);

				$posy += 4;
				$pdf->SetXY($this->marges['g'], $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ReplacementInvoice") . ' : ' . $outputlangs->convToOutputCharset($objectreplaced->ref), '', 'L');
			}
			if ($object->type == 2) {
				$objectreplaced = new Facture($this->db);
				$objectreplaced->fetch($object->fk_facture_source);

				$posy += 4;
				$pdf->SetXY($this->marges['g'], $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CorrectionInvoice") . ' : ' . $outputlangs->convToOutputCharset($objectreplaced->ref), '', 'L');
			}

			$posy += 1;

			if ($object->type != 2) {
				$posy += 3;
				$pdf->SetXY($this->marges['g'], $posy);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("DateDue") . " : " . dol_print_date($object->date_lim_reglement, "day", false, $outputlangs, true), '', 'L');
			}

			if ($societe->code_client) {
				$posy += 3;
				$pdf->SetXY($this->marges['g'], $posy);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode") . " : " . $outputlangs->transnoentities($societe->code_client), '', 'L');
			}

			$posy += 1;

			//Datos del comprobante
			$pdf->SetXY($posx - 30, $posy - 13);
			$pdf->SetFont('', 'B', $default_font_size + 1);
			$pdf->MultiCell(96, 4, 'Datos del comprobante: ', 0, 'L');


			$comprobante = getComprobanteAtributos($object, $object->array_options['options_cfdixml_fechatimbrado']);

			if ($comprobante['TipoDeComprobante'] == "I") {
				$tipoComprobante = "Ingreso";
			}

			if ($comprobante['TipoDeComprobante'] == "E") {
				$tipoComprobante = "Egreso";
			}
			$pdf->SetXY($posx - 30, $posy - 8);
			$pdf->SetFont('', '', $default_font_size);
			$pdf->MultiCell(96, 4, 'Tipo de comprobante:' . $tipoComprobante, 0, 'L');

			//Forma de pago
			$pdf->SetXY($posx - 30, $posy - 4);
			$pdf->SetFont('', '', $default_font_size);
			$pdf->MultiCell(96, 4, 'Forma de pago:' . $comprobante['FormaPago'], 0, 'L');

			//Condiciones de pago
			$pdf->SetXY($posx - 30, $posy);
			$pdf->SetFont('', '', $default_font_size);
			$pdf->MultiCell(96, 4, 'Condiciones de pago:' . $comprobante['CondicionesDePago'], 0, 'L');

			//Métdodo de pago
			$pdf->SetXY($posx + 30, $posy - 8);
			$pdf->SetFont('', '', $default_font_size);
			$pdf->MultiCell(96, 4, 'Métdodo de pago:' . $comprobante['MetodoPago'], 0, 'L');

			//Versión CFDI
			$pdf->SetXY($posx + 30, $posy - 4);
			$pdf->SetFont('', '', $default_font_size);
			$pdf->MultiCell(96, 4, 'Versión CFDI: 4.0', 0, 'L');

			//Versión CFDI
			$pdf->SetXY($posx + 30, $posy);
			$pdf->SetFont('', '', $default_font_size);
			$pdf->MultiCell(96, 4, 'Moneda: ' . $comprobante['Moneda'], 0, 'L');



			// Show list of linked objects
			$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'L', $default_font_size, $hookmanager);

			// Amount in (at tab_top - 1)
			// $pdf->SetTextColor(0, 0, 0);
			// $pdf->SetFont('', '', $default_font_size-1);
			// $titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$conf->currency));
			// $pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), 90);
			// $pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

		} else {

			//USO CFDI FISCAL
			$posy = $pdf->GetY();
			$pdf->SetXY($posx + 2, $posy );
			$pdf->SetFont('', '', $default_font_size -1);
			$pdf->MultiCell(96, 4, 'Uso CFDI: CP01', 0, 'L');
		}
		$marcofin = $pdf->GetY();
		$pdf->Rect($posx, $marcoini, 100, $marcofin - $marcoini);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf, $object, $outputlangs)
	{
		global $conf;
		$conf->FACTURE_FREE_TEXT = "Esta es una repesentación impresa de un CFDI v4.0";
		return pdf_pagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object);
	}
}
