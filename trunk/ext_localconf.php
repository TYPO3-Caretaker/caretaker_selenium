<?php 

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// load Service Helper
include_once(t3lib_extMgm::extPath('caretaker').'classes/class.tx_caretaker_ServiceHelper.php');

	// register Tests
tx_caretaker_ServiceHelper::registerCaretakerService ($_EXTKEY , 'services' , 'tx_caretakerselenium'   ,'Selenium Test', 'Run A Selenium Test' );

?>