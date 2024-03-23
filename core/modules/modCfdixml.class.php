<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022 Alice Adminson <aadminson@example.com>
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
 */

/**
 * 	\defgroup   cfdixml     Module Cfdixml
 *  \brief      Cfdixml module descriptor.
 *
 *  \file       htdocs/cfdixml/core/modules/modCfdixml.class.php
 *  \ingroup    cfdixml
 *  \brief      Description and activation file for module Cfdixml
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Cfdixml
 */
class modCfdixml extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 900999; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'cfdixml';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "financial";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '100';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleCfdixmlName' not found (Cfdixml is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleCfdixmlDesc' not found (Cfdixml is name of module).
		$this->description = "CfdixmlDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "CfdixmlDescription";

		// Author
		$this->editor_name = 'Alex Vives Alcazar';
		$this->editor_url = 'https://www.vivescloud.com';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where CFDIXML is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'generic';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 1,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 1,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				//    '/cfdixml/css/cfdixml.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/cfdixml/js/cfdixml.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				'globalcard',
				'invoicecard',
				'invoicelist',
				'paiementcard',
				'paymentcard',
				'paymentsupplierlist', //Temporal
				'formmail',
				'createFrom',
				'cfdixmlpaymentcard'
				//   'data' => array(
				//       'hookcontext1',
				//       'hookcontext2',
				//   ),
				//   'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/cfdixml/temp","/cfdixml/subdir");
		$this->dirs = array("/cfdixml/temp");

		// Config pages. Put here list of php page, stored into cfdixml/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@cfdixml");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array('always1'=>'modNumberWords');
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("cfdixml@cfdixml");

		// Prerequisites
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'CfdixmlWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('CFDIXML_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('CFDIXML_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->cfdixml) || !isset($conf->cfdixml->enabled)) {
			$conf->cfdixml = new stdClass();
			$conf->cfdixml->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'invoice:+tabname1:Title1:mylangfile@cfdixml:TRUE:/cfdixml/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		$this->tabs[] = array('data'=>'payment:+pagocfdi:PagoCFDI:mylangfile@cfdixml:TRUE:/custom/cfdixml/payments.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'invoice:+tabname2:SUBSTITUTION_Title2:mylangfile@cfdixml:$user->rights->othermodule->read:/cfdixml/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'invoice:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view

		// Dictionaries
		$this->dictionaries = [
			'langs' => 'cfdiutils@cfdiutils',
			'tabname' => [
				MAIN_DB_PREFIX . 'c_cfdixml_umed',				//CFDI - Unidad de medida
				MAIN_DB_PREFIX . 'c_cfdixml_claveprodserv',		//CFDI - Claves de producto y/o servicio
				MAIN_DB_PREFIX . 'c_cfdixml_usocfdi',				//CFDI - Uso del CFDI
				MAIN_DB_PREFIX . 'c_cfdixml_metodopago',			//CFDI - Método de pago
				MAIN_DB_PREFIX . 'c_cfdixml_tiporelacion',		//CFDI - Tipo de relación
				MAIN_DB_PREFIX . 'c_cfdixml_objetoimp',			//CFDI - Objeto de Impuesto
				MAIN_DB_PREFIX . 'c_cfdixml_exportacion',			//CFDI - Exportación
				MAIN_DB_PREFIX . 'c_cfdixml_cancelacion',			//CFDI - Cancelación
			],
			'tablib' => [
				'CFDI 4.0 - Unidad de medida',					//CFDI - Unidad de medida
				'CFDI - Claves de producto y/o servicio',	//CFDI - Claves de producto y/o servicio
				'CFDI - Uso del CFDI',						//CFDI - Uso del CFDI
				'CFDI - Método de Pago',					//CFDI - Método de pago
				'CFDI - Tipo de relación',					//CFDI - Tipo de relación
				'CFDI - Objeto de Impuesto',				//CFDI - Objeto de Impuesto
				'CFDI - Exportación',						//CFDI - Exportación
				'CFDI - Cancelación',						//CFDI - Cancelación
			],
			'tabsql' => [
				'SELECT f.rowid as rowid, f.code, f.label, f.active FROM ' . MAIN_DB_PREFIX . 'c_cfdixml_umed as f', 				//CFDI - Unidad de medida
				'SELECT f.rowid as rowid, f.code, f.label, f.active FROM ' . MAIN_DB_PREFIX . 'c_cfdixml_claveprodserv as f', 	//CFDI - Claves de producto y/o servicio
				'SELECT f.rowid as rowid, f.code, f.label, f.active FROM ' . MAIN_DB_PREFIX . 'c_cfdixml_usocfdi as f', 			//CFDI - Uso del CFDI
				'SELECT f.rowid as rowid, f.code, f.label, f.active FROM ' . MAIN_DB_PREFIX . 'c_cfdixml_metodopago as f', 		//CFDI - Método de pago
				'SELECT f.rowid as rowid, f.code, f.label, f.active FROM ' . MAIN_DB_PREFIX . 'c_cfdixml_tiporelacion as f',		//CFDI - Tipo de relación
				'SELECT f.rowid as rowid, f.code, f.label, f.active FROM ' . MAIN_DB_PREFIX . 'c_cfdixml_objetoimp as f',			//CFDI - Objeto de Impuesto
				'SELECT f.rowid as rowid, f.code, f.label, f.active FROM ' . MAIN_DB_PREFIX . 'c_cfdixml_exportacion as f',		//CFDI - Exportacion
				'SELECT f.rowid as rowid, f.code, f.label, f.active FROM ' . MAIN_DB_PREFIX . 'c_cfdixml_cancelacion as f',		//CFDI - Cancelación
			],
			'tabsqlsort' => [
				"label ASC", 	//CFDI - Unidad de medida
				"label ASC", 	//CFDI - Claves de producto y/o servicio
				"label ASC",	//CFDI - Uso del CFDI
				"label ASC",	//CFDI - Método de pago
				"label ASC",	//CFDI - Tipo de relación
				"label ASC", 	//CFDI - Objeto de Impuesto
				"label ASC", 	//CFDI - Exportacion
				"label ASC", 	//CFDI - Cancelación
			],
			'tabfield' => [
				"code,label",	//CFDI - Unidad de medida
				"code,label",	//CFDI - Claves de producto y/o servicio
				"code,label",	//CFDI - Uso del CFDI
				"code,label",	//CFDI - Método de pago
				"code,label",	//CFDI - Tipo de relación
				"code,label", 	//CFDI - Objeto de Impuesto
				"code,label", 	//CFDI - Exportacion
				"code,label", 	//CFDI - Cancelación
			],
			'tabfieldvalue' => [
				"code,label", //CFDI - Unidad de medida
				"code,label", //CFDI - Claves de producto y/o servicio
				"code,label", //CFDI - Uso del CFDI
				"code,label", //CFDI - Método de pago
				"code,label", //CFDI - Tipo de relación
				"code,label", 	//CFDI - Objeto de Impuesto
				"code,label", //CFDI - Exportacion
				"code,label", //CFDI - Cancelación
			],
			'tabfieldinsert' => [
				"code,label",	//CFDI - Unidad de medida
				"code,label",	//CFDI - Claves de producto y/o servicio
				"code,label",	//CFDI - Uso del CFDI
				"code,label",	//CFDI - Método de pago
				"code,label",	//CFDI - Tipo de relación
				"code,label", 	//CFDI - Objeto de Impuesto
				"code,label", 	 //CFDI - Exportacion
				"code,label", 	 //CFDI - Cancelación
			],
			'tabrowid' => [
				"rowid", //CFDI - Unidad de medida
				"rowid", //CFDI - Claves de producto y/o servicio
				"rowid", //CFDI - Uso del CFDI
				"rowid", //CFDI - Método de pago
				"rowid", //CFDI - Tipo de relación
				"rowid", 	//CFDI - Objeto de Impuesto
				"rowid", 	//CFDI - Exportacion
				"rowid", 	//CFDI - Cancelación
			],
			'tabcond' => [
				$conf->cfdixml->enabled, //CFDI - Unidad de medida
				$conf->cfdixml->enabled, //CFDI - Claves de producto y/o servicio
				$conf->cfdixml->enabled, //CFDI - Uso del CFDI
				$conf->cfdixml->enabled, //CFDI - Método de pago
				$conf->cfdixml->enabled, //CFDI - Tipo de relación
				$conf->cfdixml->enabled, //CFDI - Objeto de Impuesto
				$conf->cfdixml->enabled, //CFDI - Exportacion
				$conf->cfdixml->enabled, //CFDI - Cancelación
			]
		];
		/* Example:
		$this->dictionaries=array(
			'langs'=>'cfdixml@cfdixml',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array("table1", "table2", "table3"),
			// Label of tables
			'tablib'=>array("Table1", "Table2", "Table3"),
			// Request to select fields
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
			// Sort order
			'tabsqlsort'=>array("label ASC", "label ASC", "label ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("code,label", "code,label", "code,label"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("code,label", "code,label", "code,label"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("code,label", "code,label", "code,label"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array("rowid", "rowid", "rowid"),
			// Condition to show each dictionary
			'tabcond'=>array($conf->cfdixml->enabled, $conf->cfdixml->enabled, $conf->cfdixml->enabled),
			// Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
			'tabhelp'=>array(array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), ...),
		);
		*/

		// Boxes/Widgets
		// Add here list of php file(s) stored in cfdixml/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'cfdixmlwidget1.php@cfdixml',
			//      'note' => 'Widget provided by Cfdixml',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/cfdixml/class/test.class.php',
			//      'objectname' => 'Test',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => '$conf->cfdixml->enabled',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->cfdixml->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->cfdixml->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read objects of Cfdixml'; // Permission label
		$this->rights[$r][4] = 'cfdixml';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->cfdixml->test->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update objects of Cfdixml'; // Permission label
		$this->rights[$r][4] = 'cfdixml';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->cfdixml->test->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete objects of Cfdixml'; // Permission label
		$this->rights[$r][4] = 'cfdixml';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->cfdixml->test->delete)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read Payments of Cfdixml'; // Permission label
		$this->rights[$r][4] = 'payment';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->cfdixml->test->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update Payments of Cfdixml'; // Permission label
		$this->rights[$r][4] = 'payment';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->cfdixml->test->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete Payments of Cfdixml'; // Permission label
		$this->rights[$r][4] = 'payment';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->cfdixml->test->delete)
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++] = array(
			'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'top', // This is a Top menu entry
			'titre'=>'ModuleCfdixmlName',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'cfdixml',
			'leftmenu'=>'',
			'url'=>'/cfdixml/cfdixmlindex.php',
			'langs'=>'cfdixml@cfdixml', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->cfdixml->enabled', // Define condition to show or hide menu entry. Use '$conf->cfdixml->enabled' if entry must be visible if module is enabled.
			'perms'=>'1', // Use 'perms'=>'$user->rights->cfdixml->test->read' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU TEST
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=cfdixml',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Test',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'cfdixml',
			'leftmenu'=>'test',
			'url'=>'/cfdixml/cfdixmlindex.php',
			'langs'=>'cfdixml@cfdixml',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->cfdixml->enabled',  // Define condition to show or hide menu entry. Use '$conf->cfdixml->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->cfdixml->test->read',			                // Use 'perms'=>'$user->rights->cfdixml->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=cfdixml,fk_leftmenu=test',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'List_Test',
			'mainmenu'=>'cfdixml',
			'leftmenu'=>'cfdixml_test_list',
			'url'=>'/cfdixml/test_list.php',
			'langs'=>'cfdixml@cfdixml',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->cfdixml->enabled',  // Define condition to show or hide menu entry. Use '$conf->cfdixml->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->cfdixml->test->read',			                // Use 'perms'=>'$user->rights->cfdixml->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=cfdixml,fk_leftmenu=test',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'New_Test',
			'mainmenu'=>'cfdixml',
			'leftmenu'=>'cfdixml_test_new',
			'url'=>'/cfdixml/test_card.php?action=create',
			'langs'=>'cfdixml@cfdixml',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->cfdixml->enabled',  // Define condition to show or hide menu entry. Use '$conf->cfdixml->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->cfdixml->test->write',			                // Use 'perms'=>'$user->rights->cfdixml->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		*/

        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=cfdixml',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'Factura Global ',
            'mainmenu'=>'cfdixml',
            'leftmenu'=>'cfdixml_',
            'url'=>'/cfdixml/fglobal_list.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'cfdixml@cfdixml',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->cfdixml->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->cfdixml->enabled',
            // Use 'perms'=>'$user->rights->cfdixml->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );
        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=cfdixml,fk_leftmenu=cfdixml_',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'Nueva Factura Global',
            'mainmenu'=>'cfdixml',
            'leftmenu'=>'cfdixml_',
            'url'=>'/cfdixml/fglobal_card.php?action=create',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'cfdixml@cfdixml',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->cfdixml->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->cfdixml->enabled',
            // Use 'perms'=>'$user->rights->cfdixml->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2
        );

		/* */

        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=cfdixml',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'List ',
            'mainmenu'=>'cfdixml',
            'leftmenu'=>'cfdixml_',
            'url'=>'/cfdixml/fglobal_list.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'cfdixml@cfdixml',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->cfdixml->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->cfdixml->enabled',
            // Use 'perms'=>'$user->rights->cfdixml->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );
        // $this->menu[$r++]=array(
        //     // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
        //     'fk_menu'=>'fk_mainmenu=cfdixml,fk_leftmenu=cfdixml_',
        //     // This is a Left menu entry
        //     'type'=>'left',
        //     'titre'=>'New ',
        //     'mainmenu'=>'cfdixml',
        //     'leftmenu'=>'cfdixml_',
        //     'url'=>'/cfdixml/_card.php?action=create',
        //     // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
        //     'langs'=>'cfdixml@cfdixml',
        //     'position'=>1100+$r,
        //     // Define condition to show or hide menu entry. Use '$conf->cfdixml->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
        //     'enabled'=>'$conf->cfdixml->enabled',
        //     // Use 'perms'=>'$user->rights->cfdixml->level1->level2' if you want your menu with a permission rules
        //     'perms'=>'1',
        //     'target'=>'',
        //     // 0=Menu for internal users, 1=external users, 2=both
        //     'user'=>2
        // );

		/* */

        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=cfdixml',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'List Test',
            'mainmenu'=>'cfdixml',
            'leftmenu'=>'cfdixml_test',
            'url'=>'/cfdixml/test_list.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'cfdixml@cfdixml',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->cfdixml->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->cfdixml->enabled',
            // Use 'perms'=>'$user->rights->cfdixml->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );
        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=cfdixml,fk_leftmenu=cfdixml_test',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'New Test',
            'mainmenu'=>'cfdixml',
            'leftmenu'=>'cfdixml_test',
            'url'=>'/cfdixml/test_card.php?action=create',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'cfdixml@cfdixml',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->cfdixml->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->cfdixml->enabled',
            // Use 'perms'=>'$user->rights->cfdixml->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2
        );

		/* END MODULEBUILDER LEFTMENU TEST */
		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT TEST */
		/*
		$langs->load("cfdixml@cfdixml");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='TestLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='test@cfdixml';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'Test'; $keyforclassfile='/cfdixml/class/test.class.php'; $keyforelement='test@cfdixml';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'TestLine'; $keyforclassfile='/cfdixml/class/test.class.php'; $keyforelement='testline@cfdixml'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='test'; $keyforaliasextra='extra'; $keyforelement='test@cfdixml';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='testline'; $keyforaliasextra='extraline'; $keyforelement='testline@cfdixml';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('testline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'test as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'test_line as tl ON tl.fk_test = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('test').')';
		$r++; */
		/* END MODULEBUILDER EXPORT TEST */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT TEST */
		/*
		$langs->load("cfdixml@cfdixml");
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='TestLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r]='test@cfdixml';
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'cfdixml_test', 'extra' => MAIN_DB_PREFIX.'cfdixml_test_extrafields');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_author'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'Test'; $keyforclassfile='/cfdixml/class/test.class.php'; $keyforelement='test@cfdixml';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='test'; $keyforaliasextra='extra'; $keyforelement='test@cfdixml';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'cfdixml_test');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			't.ref' => array(
				'rule'=>'getrefifauto',
				'class'=>(empty($conf->global->CFDIXML_TEST_ADDON) ? 'mod_test_standard' : $conf->global->CFDIXML_TEST_ADDON),
				'path'=>"/core/modules/commande/".(empty($conf->global->CFDIXML_TEST_ADDON) ? 'mod_test_standard' : $conf->global->CFDIXML_TEST_ADDON).'.php'
				'classobject'=>'Test',
				'pathobject'=>'/cfdixml/class/test.class.php',
			),
			't.fk_soc' => array('rule' => 'fetchidfromref', 'file' => '/societe/class/societe.class.php', 'class' => 'Societe', 'method' => 'fetch', 'element' => 'ThirdParty'),
			't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
			't.fk_mode_reglement' => array('rule' => 'fetchidfromcodeorlabel', 'file' => '/compta/paiement/class/cpaiement.class.php', 'class' => 'Cpaiement', 'method' => 'fetch', 'element' => 'cpayment'),
		);
		$r++; */
		/* END MODULEBUILDER IMPORT TEST */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		//$result = $this->_load_tables('/install/mysql/', 'cfdixml');
		$result = $this->_load_tables('/cfdixml/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);

		// Productos

		//Umed
		$result1=$extrafields->addExtraField('cfdixml_umed', "Unidad de Medida", 'sellist', 1,  '', 'product',   0, 1, '', 'a:1:{s:7:"options";a:1:{s:35:"c_cfdixml_umed:label:code::active=1";N;}}', 1, '', 1, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled');		$result1=$extrafields->addExtraField('cfdixml_umed', "Unidad de Medida", 'sellist', 1,  '', 'facturedet',   0, 1, '', 'a:1:{s:7:"options";a:1:{s:35:"c_cfdixml_umed:label:code::active=1";N;}}', 1, '', 1, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled');

		//Claveprodserv
		$result1=$extrafields->addExtraField('cfdixml_claveprodserv', "Clave de producto servicio", 'sellist', 1,  '', 'product',   0, 1, '', 'a:1:{s:7:"options";a:1:{s:44:"c_cfdixml_claveprodserv:label:code::active=1";N;}}', 1, '', 1, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled');
		$result1=$extrafields->addExtraField('cfdixml_claveprodserv', "Clave de producto servicio", 'sellist', 1,  '', 'facturedet',   0, 1, '', 'a:1:{s:7:"options";a:1:{s:44:"c_cfdixml_claveprodserv:label:code::active=1";N;}}', 1, '', 1, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled');

		//ObjetoImpuesto
		$result1=$extrafields->addExtraField('cfdixml_objetoimp', "Objeto de Impuesto", 'sellist', 1,  '', 'product',   0, 1, '', 'a:1:{s:7:"options";a:1:{s:40:"c_cfdixml_objetoimp:label:code::active=1";N;}}', 1, '', 1, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled');
		$result1=$extrafields->addExtraField('cfdixml_objetoimp', "Objeto de Impuesto", 'sellist', 1,  '', 'facturedet',   0, 1, '', 'a:1:{s:7:"options";a:1:{s:40:"c_cfdixml_objetoimp:label:code::active=1";N;}}', 1, '', 1, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled');


		//Facturas
		//control
		$result1=$extrafields->addExtraField('cfdixml_control', "control timbrado", 'varchar', 1,  32, 'facture',   0, 0, '', '', 0, '', 0, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Uso CFDI
		$result1=$extrafields->addExtraField('cfdixml_usocfdi', "Uso del CFDI", 'sellist', 1,  '', 'facture',   0, 0, '', 'a:1:{s:7:"options";a:1:{s:38:"c_cfdixml_usocfdi:label:code::active=1";N;}}', 1, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);

		//Método de pago
		$result1=$extrafields->addExtraField('cfdixml_metodopago', "Metodo de pago", 'sellist', 1,  '', 'facture',   0, 0, '', 'a:1:{s:7:"options";a:1:{s:41:"c_cfdixml_metodopago:label:code::active=1";N;}}', 1, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);

		//Documento Relacionado
		$result1=$extrafields->addExtraField('cfdixml_doctorel', "Documento Relacionado", 'chkbxlst', 108,  '', 'facture',   0, 0, '', 'a:1:{s:7:"options";a:1:{s:33:"facture:ref:rowid::fk_statut != 0";N;}}', 1, '', -1, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		$result1=$extrafields->addExtraField('cfdixml_tiporelacion', "Tipo de relación", 'sellist', 1,  '', 'facture',   0, 0, '', 'a:1:{s:7:"options";a:1:{s:43:"c_cfdixml_tiporelacion:label:code::active=1";N;}}', 1, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);

		//Exportación
		$result1=$extrafields->addExtraField('cfdixml_exportacion', "Exportación", 'sellist', 1,  '', 'facture',   0, 0, '', 'a:1:{s:7:"options";a:1:{s:42:"c_cfdixml_exportacion:label:code::active=1";N;}}', 1, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);

		//UUID
		$result1=$extrafields->addExtraField('cfdixml_UUID', "UUID", 'varchar', 1,  50, 'facture',   1, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Fecha de Timbrado
		$result1=$extrafields->addExtraField('cfdixml_fechatimbrado', "Fecha de Timbrado", 'varchar', 1,  32, 'facture',   1, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Certificado SAT
		$result1=$extrafields->addExtraField('cfdixml_certsat', "Certificado Sat", 'varchar', 1,  50, 'facture', 0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Sello SAT
		$result1=$extrafields->addExtraField('cfdixml_sellosat', "Sello Sat", 'text', 1,  '', 'facture',   1, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Certificado CFD
		$result1=$extrafields->addExtraField('cfdixml_certcfd', "Certificado CFD", 'varchar', 1,  50, 'facture', 0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Sello CFD
		$result1=$extrafields->addExtraField('cfdixml_sellocfd', "Sello CFD", 'text', 1,  '', 'facture',   1, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Cadena Original
		$result1=$extrafields->addExtraField('cfdixml_cadenaorig', "Cadena Original", 'text', 1,  '', 'facture',   1, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//XML??
		$result1=$extrafields->addExtraField('cfdixml_xml', "XML", 'text', 1,  '', 'facture',   1, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Fecha de Timbrado
		$result1=$extrafields->addExtraField('cfdixml_fechacancelacion', "Fecha de Cancelacion", 'varchar', 0,  32, 'facture',   0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
				//Codigo Cancelacion
		$result1=$extrafields->addExtraField('cfdixml_codigocancelacion', "Codigo de Cancelacion", 'varchar', 0,  32, 'facture',   0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//XML CANCELACION
		$result1=$extrafields->addExtraField('cfdixml_xml_cancel', "Comprobante Cancelacion", 'text', 1,  '', 'facture',   1, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);


		//Pagos
		//control
		$result1=$extrafields->addExtraField('cfdixml_control', "control timbrado", 'varchar', 1,  32, 'paiement',   0, 0, '', '', 0, '', -1, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);

		//Uso CFDI
		$result1=$extrafields->addExtraField('cfdixml_usocfdi', "Uso del CFDI", 'sellist', 1,  '', 'paiement',   0, 0, '', 'a:1:{s:7:"options";a:1:{s:38:"c_cfdixml_usocfdi:label:code::active=1";N;}}', 1, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);

		//UUID
		$result1=$extrafields->addExtraField('cfdixml_UUID', "UUID", 'varchar', 1,  50, 'paiement',   0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Fecha de Timbrado
		$result1=$extrafields->addExtraField('cfdixml_fechatimbrado', "Fecha de Timbrado", 'varchar', 0,  32, 'paiement',   0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Certificado SAT
		$result1=$extrafields->addExtraField('cfdixml_certsat', "Certificado Sat", 'varchar', 1,  50, 'paiement', 0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Sello SAT
		$result1=$extrafields->addExtraField('cfdixml_sellosat', "Sello Sat", 'text', 1,  '', 'paiement',   0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Certificado CFD
		$result1=$extrafields->addExtraField('cfdixml_certcfd', "Certificado CFD", 'varchar', 1,  50, 'paiement', 0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Sello CFD
		$result1=$extrafields->addExtraField('cfdixml_sellocfd', "Sello CFD", 'text', 1,  '', 'paiement',   0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Cadena Original
		$result1=$extrafields->addExtraField('cfdixml_cadenaorig', "Cadena Original", 'text', 1,  '', 'paiement',   0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//XML??
		$result1=$extrafields->addExtraField('cfdixml_xml', "XML", 'text', 1,  '', 'paiement',   0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);

		//Fecha de Timbrado
		$result1=$extrafields->addExtraField('cfdixml_fechacancelacion', "Fecha de Cancelacion", 'varchar', 0,  32, 'paiement',   0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//Codigo Cancelacion
		$result1=$extrafields->addExtraField('cfdixml_codigocancelacion', "Codigo de Cancelacion", 'varchar', 0,  32, 'paiement',   0, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);
		//XML CANCELACION
		$result1=$extrafields->addExtraField('cfdixml_xml_cancelacion', "Comprobante Cancelacion", 'text', 1,  '', 'paiement',   1, 0, '', '', 0, '', 2, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled',0,1);

		//$result2=$extrafields->addExtraField('cfdixml_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled');
		//$result3=$extrafields->addExtraField('cfdixml_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled');
		//$result4=$extrafields->addExtraField('cfdixml_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled');
		//$result5=$extrafields->addExtraField('cfdixml_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'cfdixml@cfdixml', '$conf->cfdixml->enabled');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('cfdixml');
		$myTmpObjects = array();
		$myTmpObjects['Test'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'Test') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_tests.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_tests.odt';

				if (file_exists($src) && !file_exists($dest)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					dol_mkdir($dirodt);
					$result = dol_copy($src, $dest, 0, 0);
					if ($result < 0) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
						return 0;
					}
				}

				$sql = array_merge($sql, array(
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")",
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")"
				));
			}
		}

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
