<?php 

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// load Service Helper
include_once(t3lib_extMgm::extPath('caretaker').'classes/helpers/class.tx_caretaker_ServiceHelper.php');
tx_caretaker_ServiceHelper::registerCaretakerService ($_EXTKEY , 'services' , 'tx_caretakerselenium'   ,'Selenium Test', 'Run A Selenium Test' );


?>