<?php 

require_once (t3lib_extMgm::extPath('caretaker').'classes/nodes/class.tx_caretaker_TestNode.php');
require_once (t3lib_extMgm::extPath('caretaker').'classes/nodes/class.tx_caretaker_InstanceNode.php');
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
		
		$instance = new tx_caretaker_InstanceNode(9990, 'title', false, $this->baseURL, '');
		$test = new tx_caretaker_TestNode(9990, 'title', $instance ,'tx_caretakerselenium', 
			array(
				'selenium_configuration' => 'open:
										-'.$test_query.'
										-300
										waitForPageToLoad:
										-20000	
										',
				'response_time_error' => 60,
				'response_time_warning' => 20,
				'selenium_server'=>array(
					'host'=>$this->selenium_host, 
					'browser'=>$this->browser 
				),
			)
		);
		
		$result   = $test->runTest($instance);
		$this->assertEquals( $result->getState(), TX_CARETAKER_STATE_OK, 'State was not OK' );
	}
	
}

?>