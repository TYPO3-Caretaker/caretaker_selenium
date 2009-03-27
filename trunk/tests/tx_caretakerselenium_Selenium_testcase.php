<?php 

require_once (t3lib_extMgm::extPath('caretaker').'classes/class.tx_caretaker_Test.php');
require_once (t3lib_extMgm::extPath('caretaker').'classes/class.tx_caretaker_Instance.php');
require_once (t3lib_extMgm::extPath('caretaker_selenium').'classes/class.tx_caretakerselenium_SeleniumTest.php');

class tx_caretakerselenium_Selenium_testcase extends tx_phpunit_testcase  {
	
	var $browser;
	var $selenium_host;
	var $test_host;
	var $test_query;
	
	function setUp(){
		$confArray = unserialize( $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['caretaker_selenium']);
		$this->browser         = $confArray['unittest.']['browser'];
		$this->selenium_host   = $confArray['unittest.']['host'];
		$this->baseURL         = $confArray['unittest.']['baseURL'];
		$this->testQuery       = $confArray['unittest.']['testQuery'];
	}
	
	function test_selenium_testrunner(){
		
		$baseUrl  = $this->baseURL;
		$commands = '
		open:
		-'.$test_query.'
		-300
		waitForPageToLoad:
		-20000
		'; 
		
		$browser  = $this->browser;
		$host     = $this->selenium_host;
		$test = new tx_caretakerselenium_SeleniumTest($commands,$browser,$baseUrl,$host);
		list($result, $msg) = $test->run();
		$this->assertEquals( true, $result);
		
	}
	
	function test_selenium_testservice(){

		$test = new tx_caretaker_Test(9990, 'tx_caretakerselenium', 
			array(
				'selenium_configuration' => 'open:
										-'.$test_query.'
										-300
										waitForPageToLoad:
										-20000	
										',
				'response_time_error' => 60,
				'response_time_warning' => 8,
				'selenium_server'=>array(
					'host'=>$this->selenium_host, 
					'browser'=>$this->browser 
				),
			)
		);
		$instance = new tx_caretaker_Instance(9990,  $this->baseURL, '');
		$result   = $test->runTest($instance);
		$this->assertEquals( $result->getState(), TX_CARETAKER_STATE_WARNING, 'State was not OK' );
	}
	
}

?>