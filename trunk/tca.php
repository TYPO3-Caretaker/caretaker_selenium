<?php
/**
 * This is a file of the caretaker project.
 * Copyright 2008 by n@work Internet Informationssystem GmbH (www.work.de)
 * 
 * @Author	Thomas Hempel 		<thomas@work.de>
 * @Author	Martin Ficzel		<martin@work.de>
 * @Author	Tobias Liebig		<mail_typo3.org@etobi.de>
 * @Author	Christopher Hlubek 	<hlubek@networkteam.com>
 * @Author	Patrick Kollodzik	<patrick@work.de>  
 * 
 * $$Id: tca.php 46 2008-06-19 16:09:17Z martin $$
 */

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_caretakerselenium_server'] = array (
	'ctrl' => $TCA['tx_caretakerselenium_server']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,exec_interval,testservice,testconf,name,last_exec'
	),
	'feInterface' => $TCA['tx_caretakerselenium_server']['feInterface'],
	'columns' => Array (
		'hidden' => Array (        
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config'  => Array (
				'type'    => 'check',
				'default' => '0'
			),
		),
		'starttime' => Array (        
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
            'config' => Array (
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
                'checkbox' => '0'
            )
        ),
        'endtime' => Array (        
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
            'config' => Array (
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'checkbox' => '0',
                'default' => '0',
                'range' => Array (
                    'upper' => mktime(0,0,0,12,31,2020),
                    'lower' => mktime(0,0,0,date('m')-1,date('d'),date('Y'))
                )
            )
        ),
        'fe_group' => Array (        
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
            'config' => Array (
                'type' => 'select',
                'items' => Array (
                    Array('', 0),
                    Array('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
                    Array('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
                    Array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--')
                ),
                'foreign_table' => 'fe_groups'
            )
        ),
        'title' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:caretaker_selenium/locallang_db.xml:tx_caretakerselenium_server.title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'hostname' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:caretaker_selenium/locallang_db.xml:tx_caretakerselenium_server.hostname',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'browser' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:caretaker_selenium/locallang_db.xml:tx_caretakerselenium_server.browser',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
			
	
	),
	'types' => array (
		'0' => array('showitem' => 'title;;1;;1-1-1, hostname, browser')
	),
	'palettes' => array (
		'1' => array('showitem' => 'hidden, starttime,endtime,fe_group'),
	)
);