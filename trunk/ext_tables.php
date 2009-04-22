<?php 

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_caretakerselenium_server'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:caretaker_selenium/locallang_db.xml:tx_caretakerselenium_server',
		'label'     => 'title',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete'    => 'deleted',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'res/icons/selenium_server.png',
		'enablecolumns' => array (        
			'disabled' => 'hidden',
		),
	),
	'feInterface' => array ('fe_admin_fieldList' => '' )
);

?>