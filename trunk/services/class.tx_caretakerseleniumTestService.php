<?php

/***************************************************************
* Copyright notice
*
* (c) 2009 by n@work GmbH and networkteam GmbH
*
* All rights reserved
*
* This script is part of the Caretaker project. The Caretaker project
* is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This is a file of the caretaker project.
 * http://forge.typo3.org/projects/show/extension-caretaker
 *
 * Project sponsored by:
 * n@work GmbH - http://www.work.de
 * networkteam GmbH - http://www.networkteam.com/
 *
 * $Id$
 */

require_once (t3lib_extMgm::extPath('caretaker_selenium').'classes/class.tx_caretakerselenium_SeleniumTest.php');

/**
 * Instancelist Test - Test that Monitoring Setups for the given List of Instance-Urls exist and are enabled
 *
 * @author Martin Ficzel <martin@work.de>
 * @author Thomas Hempel <thomas@work.de>
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @author Tobias Liebig <liebig@networkteam.com>
 * @author Thorben Kapp <kapp@work.de>
 *
 * @package TYPO3
 * @subpackage caretaker_selenium
 */
class tx_caretakerseleniumTestService extends tx_caretaker_TestServiceBase {
	
	protected $valueDescription = 'LLL:EXT:caretaker_selenium/locallang.xml:seconds';
	
	/**
	 * Service type description in human readble form.
	 * @var string
	 */
	protected $typeDescription = 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_service_description';

	/**
	 * Template to display the test Configuration in human readable form.
	 * @var string
	 */
	protected $configurationInfoTemplate = 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_service_configuration';
	
	
	/**
	 * Checks if all selenium servers that are needed for this test are free
	 * and returns the result. If only one server is busy the test must not be run
	 * to avoid parallel execution of seleniumtests on one machine.
	 * 
	 * @return boolean
	 */
	public function isExecutable() {
		
		$server = $this->getConfigValue('selenium_server');
		
		$servers = array();
		
		if (is_array($server)){
			$inUseSince = $server['inUseSince'];
			
			if($inUseSince + 3600 > time()) {
				
				return false; // server is busy and can NOT be used
			}
			
			return true; // server is free and can be used
			
		} else {
			
			$server_ids = explode(',',$server);
			$result = false;

			foreach($server_ids as $server_idsid) {
				if ( $this->getServerFree( $$server_id ) ){
					$result = true; // server is in use and can NOT be used
				}
			}
			// at least 1 server is free and can be used
			return $result; 
		}
	}
	
	public function runTest(){
		
		$commands     = $this->getConfigValue('selenium_configuration');
		$error_time   = $this->getConfigValue('response_time_error');
		$warning_time = $this->getConfigValue('response_time_warning');
		
		$server       = $this->getConfigValue('selenium_server');
		
		$activeServers   = array();
		$inactiveServers = array();
		
		if (is_array($server)){
			$activeServers[] = array(
				'uid' => $server['uid'],
				'title' => $server['title'],
				'host'    => $server['host'],
				'browser' => $server['browser']
			);
		} else {
			$server_ids = explode(',',$server);
			
			foreach($server_ids as $sid) {
				
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_caretakerselenium_server', 'deleted=0 AND hidden=0 AND uid='.$sid);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				if ($row){

						// check server if selftest is set
					$serverSelftestOk = FALSE;
					if ( ! $row['selftestInstanceUid'] ) {
						$serverSelftestOk = TRUE;
					} else {
						$nodeRepository = tx_caretaker_NodeRepository::getInstance();
						$instanceNode   = $nodeRepository->getInstanceByUid( $row['selftestInstanceUid'] );

							// allow selftest
						if ( !$instanceNode ) {
							$serverSelftestOk = TRUE;
						} else {
							if ( $instanceNode->getUid() == $this->instance->getUID()  ) {
								$serverSelftestOk = TRUE;
							}
								// check status of selftest instance nodes
							else {
								$instanceResult = $instanceNode->getTestResult();
								$serverSelftestOk = ( $instanceResult->getState() == tx_caretaker_Constants::state_ok ) ? TRUE : FALSE ;
							}
						} 
					}
					
						// add only tested servers
					if ($serverSelftestOk){
						$activeServers[] = array(
							'uid' => $sid,
							'title' => $row['title'],
							'host'    => $row['hostname'],
							'browser' => $row['browser']
						);
					} else {
						$inactiveServers[] = array(
							'uid' => $sid,
							'title' => $row['title'],
							'host'    => $row['hostname'],
							'browser' => $row['browser']
						);
					}
				}
			}			
		}

		if (count($activeServers) == 0 ) {
			if (count($inactiveServers) > 0){
				return tx_caretaker_TestResult::create(tx_caretaker_Constants::state_undefined, 0, 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_serverfailure');
			} else {
				return tx_caretaker_TestResult::create(tx_caretaker_Constants::state_error, 0, 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_configuration_error');
			}
		}
		
		
		
		$baseURL = $this->instance->getUrl(); 
		
		$results  = array();
		
		$num_servers = 0;
		$num_ok      = 0;
		$num_warning = 0;
		$num_error   = 0;
		$max_time    = 0;

		$details = array();
		
		foreach ($activeServers as $server){

			if ( $this->getServerFree($server['uid']) ){
				$num_servers ++;

					// set the servers busy
				$this->setServerBusy($server['uid']);

				$test = new tx_caretakerselenium_SeleniumTest($commands,$server['browser'],$baseURL,$server['host']);
				list($success, $msg, $time) = $test->run();

					// set the servers free
				$this->setServerFree($server['uid']);

				$values  = array(
					'success'	=> $success,
					'host'		=> $server['host'],
					'title'     => $server['title'],
					'browser' 	=> $server['browser'],
					'message'   => $msg,
					'time'      => round($time, 2)
				);

				if ( $time > $max_time ) $max_time = $time;

				if (!$success){
					$message = 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_detail_error';
					$num_error ++;
				} else {
					if ($time > $error_time ){
						$message = 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_detail_timeout_error';
						$num_error ++;
					} else if  ($time > $warning_time){
						$message = 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_detail_timeout_warning';
						$num_warning ++;
					} else {
						$message = 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_detail_ok';
						$num_ok ++;
					}
				}

				$details[] = array( 'message'=>$message , 'values'=>$values );
			} else {
				$details[] = array( 'message'=>'Server ' . $server['uid'] . ':' . $server['title'] .  ' was busy and could not be used.' , 'values'=>array() );
			}
		}
		
		
			
		$values = array( 'num_servers'=>$num_servers, 'num_ok' => $num_ok, 'num_warning'=>$num_warning, 'num_error' =>$num_error, 'time'=>$whole_time );

			// submessages for subresults
		$submessages = array();
		foreach ($details as $detail){
			$submessages[] =  new tx_caretaker_ResultMessage( $detail['message'], $detail['values'] );
		}

			// submessages for serverfailures
		foreach ($inactiveServers as $inactiveServer){
			$submessages[] =  new tx_caretaker_ResultMessage( 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_info_serverfailure', $inactiveServer );
		}

			// create results
		if ( $num_error > 0 )  {
			$value   = round($max_time, 2);
			$message = new tx_caretaker_ResultMessage( 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_info_problems', $values );
			return tx_caretaker_TestResult::create(tx_caretaker_Constants::state_error, $value , $message , $submessages );
		}

		if ( $num_warning > 0 ) {
			$value   = round($max_time, 2);
			$message = new tx_caretaker_ResultMessage( 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_info_problems', $values );
			return tx_caretaker_TestResult::create(tx_caretaker_Constants::state_warning, $value , $message , $submessages );
		}

		$value   = round($max_time, 2);
		$message = new tx_caretaker_ResultMessage( 'LLL:EXT:caretaker_selenium/locallang.xml:selenium_info_ok', $values );
		return tx_caretaker_TestResult::create( tx_caretaker_Constants::state_ok , $value , $message , $submessages );
		
	}

	/**
	 * Set the server busy state by entering the current time in the inUseSince field
	 * @param array $server SeleniumServer DB-Row
	 */
	protected function setServerBusy($serverId) {
		// echo ("setServerBusy ".$server['title'].':'.$server['uid'] );
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_caretakerselenium_server', 'uid='.(int)$serverId, array('inUseSince' => time() ) );
	}

	/**
	 * Set the serverfree state by entering 0 into the inUseSince field
	 * @param array $server SeleniumServer DB-Row
	 */
	protected function setServerFree($serverId) {
		// echo ("setServerFree ".$server['title'].':'.$server['uid'] );
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_caretakerselenium_server', 'uid='.(int)$serverId, array('inUseSince' => 0) );
	}

	/**
	 * Check weather the given selenium server is available
	 * @param array $server SeleniumServer DB-Row $server
	 */
	protected function getServerFree($serverId){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_caretakerselenium_server', 'deleted=0 AND hidden=0 AND uid='.(int)$serverId);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		if ( $row['inUseSince'] + 3600 < time()){
			return true; 
		} else {
			return false;
		}
	}

	private function setServersBusy($servers, $state = true) {
		
		$serverIds = array();
		
		foreach($servers as $server) {
			
			$serverIds[] = $server['uid'];
		}
		
		foreach($serverIds as $sid) {
			
			if($state) {
				
				// set the selenium servers needed for that test to busy state
				// for that set the inUseSince timestamp to the current time
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_caretakerselenium_server', 'uid='.$sid, array('inUseSince' => time() ) );
				
			} else {
				
				// set the selenium servers needed for that test to free state
				// for that set the inUseSince timestamp to the current time minus one hour and one second
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_caretakerselenium_server', 'uid='.$sid, array('inUseSince' => 0) );
			}
			
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/caretaker/services/class.tx_caretaker_typo3_extensions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/caretaker/services/class.tx_caretaker_typo3_extensions.php']);
}
?>