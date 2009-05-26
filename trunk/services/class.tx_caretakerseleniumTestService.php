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

class tx_caretakerseleniumTestService extends tx_caretaker_TestServiceBase {
	
	protected $valueDescription = 'Seconds';
	
	public function getValueDescription(){
		
		return $this->valueDescription;
	}
	
	public function runTest(){
				
		$commands     = $this->getConfigValue('selenium_configuration');
		$error_time   = $this->getConfigValue('response_time_error');
		$warning_time = $this->getConfigValue('response_time_warning');

		$server       = $this->getConfigValue('selenium_server');
		
		$servers = array();
		
		if (is_array($server)){
			$servers[] = array(
				'host'    => $server['host'],
				'browser' => $server['browser']
			);
		} else {
			$server_ids = explode(',',$server);
			
			foreach($server_ids as $sid) {
				
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_caretakerselenium_server', 'deleted=0 AND hidden=0 AND uid='.$sid);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				if ($row){
					$servers[] = array(
						'host'    => $row['hostname'],
						'browser' => $row['browser']
					);
				}
			}			
		}

		if (count($servers) == 0 ) {
			return tx_caretaker_TestResult::create(TX_CARETAKER_STATE_ERROR, 0, 'Selenium server was not properly configured');
		}
		
		$baseURL = $this->instance->getUrl(); 
		
		$results  = array();
		foreach ($servers as $server){
			//$starttime = microtime(true);
			$test = new tx_caretakerselenium_SeleniumTest($commands,$server['browser'],$baseURL,$server['host']);
			list($success, $msg, $time) = $test->run();
			//$stoptime = microtime(true);
			//$time2 = $stoptime - $starttime;
			$results[]  = array(
				'success'	=> $success,
				'host'		=> $server['host'],
				'browser' 	=> $server['browser'],
				'message'  => $msg,
				'time'     => $time,
				'warning_time' => $warning_time,
				'error_time' => $error_time
			);
		}
		
		list($success, $time, $message) = $this->getAggregatedResults($results);
		
		if ($success){
			if ($time >= $error_time )  {
				return tx_caretaker_TestResult::create(TX_CARETAKER_STATE_ERROR, $time, $message);
			} else if ($time >= $warning_time) {
				return tx_caretaker_TestResult::create(TX_CARETAKER_STATE_WARNING, $time, $message);
			} else {
				return tx_caretaker_TestResult::create(TX_CARETAKER_STATE_OK, $time, $message);
			}
		}else{
			return tx_caretaker_TestResult::create(TX_CARETAKER_STATE_ERROR, 0, $message);
		}
		
		return $testResult;
	}
	
	function getAggregatedResults ($results){
		$sucess      = true;
		$message    = '';
		foreach ($results as $result){
			$time  = 0;
			if ($result['time']     > $time ) $time   = $result['time'];
			
			if ($result['success'] == false ) {
				
				$sucess = false;
				$message .= 'Test failed under '.$result['browser'].' with message: '.$result['message'].'!';
				
			} else {
				
				$message .= 'Test has passed successfully under '.$result['browser'].'!';
				$message .= ' The test took '.round($time,1).' seconds.';
			}
			
			//$message .= $result['message'].chr(10);
			//$message .= $result['browser'].':';
			//$message .= ' The test took '.round($time,1).' seconds.';
			
			if($result['time'] > $result['error_time']) {
				
				$message .= ' More than '.$result['error_time'].' seconds causes an error.';
				
			} elseif($result['time'] > $result['warning_time']) {
				
				$message .= ' More than '.$result['warning_time'].' seconds causes a warning.';
			}
			$message .= '';
		}
		return array($sucess,$time, $message );
				
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/caretaker/services/class.tx_caretaker_typo3_extensions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/caretaker/services/class.tx_caretaker_typo3_extensions.php']);
}
?>