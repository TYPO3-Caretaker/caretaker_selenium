<?php

require_once 'class.tx_caretakerselenium_Selenium.php';
require_once 'class.tx_caretakerselenium_SeleniumCommand.php';

/**
tx_caretakerselenium_SeleniumTest manages the commands and executes them through a tx_caretakerseleneium_Selenium

Added IE Fix: In Internet Explorer waitForLocation includes waitForPageToLoad, so this must be caught to avoid a
timeout.

Added custom timer: You can start the test timer by adding '@startTimer:' into your selenese commands. Every call
resets the start time. You can stop the timer by adding '@stopTimer:' into your selenese commands. Only the first
call is interpreted. So take care you place your timer commands.
*/

class tx_caretakerselenium_SeleniumTest {

	private $sel;
	private $commands = array();
	private $testSuccessful = true;
	private $commandsText = '';
	private $commandsLoaded = false;
	private $host;
	
	function __construct($commands,$browser,$baseUrl,$host) {
		
		//echo $commands;
		
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

	
	/**
	 * added advanced timer functionality
	 * 
	 * '@startTimer' starts a timer
	 * '@stopTimer' stop the timer
	 * 
	 * you can call this several times and the time between '@startTimer' and '@stopTimer' is measured
	 * and added to the total time
	 * 
	 * there is now automatic timer anymore, must be configured in the commands
	 * if no timer commands are used the time is 0, should be everytime green
	 * 
	 */
	function testMyTestCase() {
		
		if($this->commandsLoaded) {
			
			// $avoidWaitForPageToLoad = false; // needed for ie fix, UPDATE: not needed at the moment
			$time = 0; // the measured time
			$timerRunning = false; // indicates if the timer is running
			
			// no automatic timer anymore
			
			// start the timer just before the first commands are send
			//$starttime = microtime(true);
			//$timerRunning = true;
			
			foreach($this->commands as $command) {
				
				// @ indicates a custom command
				if(substr($command->command, 0, 1) == '@') {
				
					// reset the start timer, because the time shoold start now
					// if called when the timer is not running starts to count the time
					if($command->command == '@startTimer') {
						
						if(!$timerRunning) {
							
							$starttime = microtime(true);
							$timerRunning = true;
						}
					}
					
					// set the end time, because the time important commands are executed
					// every time this is called it adds the time between now and $starttime to the measured $time
					if($command->command == '@stopTimer') {
						
						if($timerRunning) {
							
							$time += microtime(true) - $starttime;
							$timerRunning = false;
						}
					}
					
					// continue with the next command
					continue;
				}
				
				// ie waits for page to load if waitForLocation is called
				// so it must be excluded while walking through the commands
				
				// !!! UPDATE !!!
				// internet explorer seems to work exactly as firefox now, so it is commented out so it can
				// quickly be reactivated if the next official release of the selenium server behaves different again
				
				// if the browser is ie and waitForPageToLoad is called and the command before was waitForLocation so $avoidPageToLoad is true
				
				/*if($this->browser == '*iexplore' && $command->command == 'waitForPageToLoad' && $avoidWaitForPageToLoad) {
					
					// continue with the next command
					continue;
					
				}
				$avoidWaitForPageToLoad = false;
				
				// if browser is ie and command is waitForLocation set the avoid variable to true
				if($this->browser == '*iexplore' && $command->command == 'waitForLocation') {
					
					
					//$avoidWaitForPageToLoad = true;
				}
				*/
			
				$message = $this->sel->executeCommand($command);
				if($message != 'OK') {
			
					$this->sel->stop();
					$this->testSuccessful = false;
					$msg = 'An error occured: Command:'.$command->command.' at line '.$command->lineInFile.' in your commands. Message: '.$message;
					if(!empty($command->comment)) {
						
						$msg .= ' Comment: '.$command->comment."\n";
					}
					return array(false,$msg,0);
				}
			}
			
			// if timer is running, now stop it
			if($timerRunning) {
				
				$time += microtime(true) - $starttime;
			}
			
			if($this->testSuccessful) {
				return array(true, '', $time);
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
