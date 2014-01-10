<?php 

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// Load Selenese parser
include(t3lib_extMgm::extPath('caretaker_selenium') . 'vendor/php-selenese/lib/Selenese/__init__.php');

// Load webdriver library
include(t3lib_extMgm::extPath('caretaker_selenium') . 'vendor/php-webdriver/lib/__init__.php');

	// load Service Helper
include_once(t3lib_extMgm::extPath('caretaker').'classes/helpers/class.tx_caretaker_ServiceHelper.php');
tx_caretaker_ServiceHelper::registerCaretakerService ($_EXTKEY , 'services' , 'tx_caretakerselenium'   ,'Selenium Test', 'Run A Selenium Test' );


?>