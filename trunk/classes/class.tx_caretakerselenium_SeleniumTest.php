<?php

require_once 'class.tx_caretakerselenium_Selenium.php';
require_once 'class.tx_caretakerselenium_SeleniumCommand.php';

/**
NaworkSeleniumTest is the test suite that extends the PHPUnit testcase
and uses the selenium tests.
*/

class tx_caretakerselenium_SeleniumTest {

	private $sel;
	private $commands = array();
	private $testSuccessful = true;
	private $commandsText = '';
	private $commandsLoaded = false;
	private $host;
	
	function __construct($commands,$browser,$baseUrl,$host) {
		$this->commandsText = $commands;
		$this->browser = $browser;
		$this->baseURL = $baseUrl;
		$this->host    = $host;
	}

	function run(){
		
		$this->setUp();
		$res = $this->testMyTestCase();
		$this->tearDown();
		return $res;
	}
	
	function setUp()  {
		
  		$this->sel = new tx_caretakerselenium_Selenium($this->browser,$this->baseURL, $this->host);
  		if($this->loadCommands()) {
  		
  			$this->commandsLoaded = true;  		
  			$this->sel->start();
  		}
  		
  	}
	
	function tearDown() {
	
		$this->sel->stop();
	
	}
	
	/*function startTests() {
		if($this->commandsLoaded) {
		
			$this->testMyTestCase();
		}
	}*/

	function testMyTestCase() {
		
		if($this->commandsLoaded) {
			foreach($this->commands as $command) {
				
				if($command->getCommand() == 'waitForLocation')
			
				$message = $this->sel->executeCommand($command);
				if($message != 'OK') {
			
					$this->sel->stop();
					$this->testSuccessful = false;
					return array(false,'An error occured: Command:'.$command->command.' at line '.$command->lineInFile.' in your commands file. Message: '.$message.' Comment: '.$command->comment."\n");
				}
			}
			if($this->testSuccessful) {
				return array(true, 'Test has passed successfully!'."\n");
			}
		}
		return array(false);
	}
  
  	function loadCommands() {
  	
  		$commandsLines = explode("\n",$this->commandsText);
  		if(empty($commandsLines)) {
  		
  			fwrite(STDERR,'An error occured: No commands were found!'."\n");
  			return false;
  		}
  		$lineNumber = 0;
  		
  		foreach($commandsLines as $command) {
  			$command = trim($command);
  			$firstChar = substr(trim($command),0,1);
  			if( !empty($command) && ($firstChar != '-' && $firstChar != '#' && $firstChar != ':' ) ) {
  			
  				if(!empty($com)) {
					$this->commands[] = $com;
				}
  				$commandText = substr($command,0,strpos($command,':'));
  				$commentText = substr($command,strpos($command,':') + 1);
  				$com = new tx_caretakerselenium_SeleniumCommand($commandText,$lineNumber + 1,$commentText);
  				$paramCount = 1;
  				
  			} elseif($firstChar == '-') {
  			
	  			if(!empty($paramCount) && $paramCount < 2) {
	  			
	  				if(!$com->addParam(trim(substr($command,1)))) {
	  				
	  					$paramCount++;
	  					fwrite(STDERR,'There are too many parameters for that command or wrong syntax at line'.$lineNumber.'.'."\n");
	  					return false;
	  				}
	  			}
	  		} elseif($firstChar == ':') {
	  			
	  			$com->addComment(trim(substr($command,1)));
	  		}
  			$lineNumber++;
  		}
  		return true;
  	}  
}

?>
