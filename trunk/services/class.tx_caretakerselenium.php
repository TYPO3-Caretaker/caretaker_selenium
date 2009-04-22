<?php
/**
 * This is a file of the caretaker project.
 * Copyright 2008 by n@work Internet Informationssystem GmbH (www.work.de)
 * 
 * @Author	Thomas Hempel 		<thomas@work.de>
 * @Author	Martin Ficzel		<martin@work.de>
 * @Author	Patrick Kollodzik	<patrick@work.de>
 * 
 * $$Id: class.tx_caretaker_typo3_extensions.php 33 2008-06-13 14:00:38Z thomas $$
 */

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Patrick Kollodzik <patrick@work.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once (t3lib_extMgm::extPath('caretaker').'/services/class.tx_caretaker_TestServiceBase.php');
require_once (t3lib_extMgm::extPath('caretaker_selenium').'classes/class.tx_caretakerselenium_SeleniumTest.php');

class tx_caretakerselenium extends tx_caretaker_TestServiceBase {
	
	function __construct(){
		$this->valueDescription = "Seconds";
	}
	
	/*
	 * @TODO: add handling of multiple selenium Servers
	 */
	
	public function runTest(){
				
		$commands     = $this->getConfigValue('selenium_configuration');
		$error_time   = $this->getConfigValue('response_time_error');
		$warning_time = $this->getConfigValue('response_time_warning');

		$server       = $this->getConfigValue('selenium_server');
		
		$host    = false;
		$browser = false;
		
		if (is_array($server)){
			$host    = $server['host'];
			$browser = $server['browser'];
		} else {
			$server_ids = explode(',',$server);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_caretakerselenium_server', 'deleted=0 AND hidden=0 AND uid='.$server_ids[0]);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if ($row){
				$host    = $row['hostname'];
				$browser = $row['browser'];
			}
		}

		if (!$host || !$browser) {
			return tx_caretaker_TestResult::create(TX_CARETAKER_STATE_ERROR, 0, 'Selenium server was not properly configured');
		}
		
		$baseURL = $this->instance->getUrl(); 
		
		$starttime = microtime(true);
		
		$test = new tx_caretakerselenium_SeleniumTest($commands,$browser,$baseURL,$host);
		list($result, $msg) = $test->run();
		
		$stoptime = microtime(true);
		
		if ($result){
			$time = $stoptime - $starttime;
			
			if ($time > $error_time )  {
				return tx_caretaker_TestResult::create(TX_CARETAKER_STATE_ERROR, $time, 'Selenium took '.$time.' Seconds');
			} else if ($time > $warning_time) {
				return tx_caretaker_TestResult::create(TX_CARETAKER_STATE_WARNING, $time, 'Selenium took '.$time.' Seconds');
			} else {
				return tx_caretaker_TestResult::create(TX_CARETAKER_STATE_OK, $time, 'OK');
			}
		}else{
			return tx_caretaker_TestResult::create(TX_CARETAKER_STATE_ERROR, 0, 'Selenium Test failed: '.$msg);
		}
		
		return $testResult;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/caretaker/services/class.tx_caretaker_typo3_extensions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/caretaker/services/class.tx_caretaker_typo3_extensions.php']);
}
?>