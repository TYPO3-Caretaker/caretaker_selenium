<?php

/**
 tx_caretakerselenium_SeleniumTest manages the commands and executes them through a tx_caretakerseleneium_Selenium

 Added custom timer: You can start the test timer by adding '@startTimer:' into your selenese commands.
 If you want to reset the time just add '@resetTimer:' to your commmands.
 You can stop the timer by adding '@stopTimer:' into your selenese commands. Only the first
 call is interpreted. So take care you place your timer commands.
 */

class tx_caretakerselenium_SeleniumTest {
	protected $sel;
	protected $host;
	protected $connectTimeout;

	protected $browser;
	protected $browserWidth;
	protected $browserHeight;

	protected $baseURL;

	protected $commands = array();
	protected $commandsText = '';
	protected $commandsLoaded = false;

	protected $testSuccessful = true;

	public function __construct($commands, $browser, $baseUrl, $host, $connectTimeout = 5000, $browserWidth = 1280, $browserHeight = 960) {
		// Selenium server information
		$this->host = $host;
		$this->connectTimeout = $connectTimeout;
		$this->browser = $browser;
		$this->browserWidth = $browserWidth;
		$this->browserHeight = $browserHeight;

		// Selenium commands
		$this->commandsText = $commands;

		// Ensure that the base url has a trailling slash
		$this->baseURL = rtrim($baseUrl, '/') . '/';
	}

	public function run() {
		$this->setUp();
		$res = $this->testMyTestCase();
		$this->tearDown();

		return $res;
	}

	protected function setUp()  {
		// Initialize web driver
		$capabilities = array(\WebDriverCapabilityType::BROWSER_NAME => $this->browser);
		$this->sel = \RemoteWebDriver::create($this->host, $capabilities, $this->connectTimeout);

		// Set browser window dimensions
		$this->sel->manage()->window()->setSize(
			new \WebDriverDimension($this->browserWidth, $this->browserHeight)
		);

		// Parse commands
		if ($this->loadCommands()) {
			$this->commandsLoaded = true;
		}
	}

	protected function tearDown() {
		// Delete all cookies
		$this->sel->manage()->deleteAllCookies();

		// Close connection to selenium session
		$this->sel->quit();
	}

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
	protected function testMyTestCase() {
		if (!$this->commandsLoaded) {
			// Commands are not ready yet
			return array(false);
		}

		// $avoidWaitForPageToLoad = false; // needed for ie fix, UPDATE: not needed at the moment
		$time = 0; // the measured time
		$starttime = microtime(true); // time is started automatically
		$lastRound = $starttime;
		$timerRunning = true; // indicates if the timer is running
		$timeLogArray = array();

		foreach ($this->commands as $command) {
			// @ indicates a custom command
			if (isset($command->command) && substr($command->command, 0, 1) === '@') {

				// reset the start timer, because the time should start now
				// if called when the timer is not running starts to count the time
				switch ($command->command) {
					case '@resetTimer':
						$starttime = microtime(true);
						$lastRound = $starttime;
						$timerRunning = true;
						if (count($timeLogArray)) {
							$timeLogArray[] = ':resetTimeLog:';
						}
						break;

					case '@startTimer':
						if (!$timerRunning) {
							$starttime = microtime(true);
							$lastRound = $starttime;
							$timerRunning = true;
						}
						break;

					case '@stopTimer':
						if ($timerRunning) {
							$timeLogArray[] = round(microtime(true) - $lastRound, 2) . ' ' . $command->comment;
							$lastRound = microtime(true);
							$time += microtime(true) - $starttime;
							$timerRunning = false;
						}
						break;
						
					case '@stopTimer':
						if ($timerRunning) {
							$timeLogArray[] = round(microtime(true) - $lastRound, 2) . ' ' . $command->comment;
							$lastRound = microtime(true);
							$time += microtime(true) - $starttime;
							$timerRunning = false;
						}
						break;
						
					case '@pause':
						$duration = ceil($command->arg1 / 1000);
						if ($duration > 0) {
							sleep($duration);
						} else {
							sleep(1);
						}
						break;
				}
					
				// continue with the next command
				continue;
			}
			
			// Execute web driver command
			$result = $command->runWebDriver($this->sel);

			// Not successful?
			if (!$result->success) { // $result->continue
				// Abort test
				$this->testSuccessful = false;
				break;
			}
		}

		// if timer is running, now stop it
		if ($timerRunning) {
			$time += microtime(true) - $starttime;
		}

		// Generate time log output
		$msg = implode(':', $timeLogArray);

		if ($this->testSuccessful) {
			return array(
				true,
				$msg,
				$time
			);
		} else {
			if (isset($result) && isset($command)) {
				// Generate custom error message
				$msg = 'An error occured: Command:' . $command->command . ' at line ' . $command->lineInFile . ' in your commands. Message: ' . $result->message;
				if (!empty($command->comment)) {
					$msg .= ' Comment: '.$command->comment."\n";
				}
			}

			return array(
				false,
				$msg,
				$time
			);
		}
	}

	protected function loadCommands() {
		$commandsLines = explode("\n", trim($this->commandsText));

		// No commands available
		if (empty($commandsLines)) {
			fwrite(STDERR, "An error occured: No commands were found!\n");
			return false;
		}

		$lineNumber = 1;
		$lastCommand = $commandText = $paramCount = null;
		foreach ($commandsLines as $command) {
			$command = trim($command);
			$firstChar = substr($command, 0, 1);

			switch ($firstChar) {
				// Add parameters to last command object
				case '-':
					if (isset($lastCommand) && $paramCount <= 2) {
						$varName = "arg$paramCount";
						$lastCommand->$varName = trim(substr($command, 1));

						switch ($commandText) {
							// Add full URL to open command
							case 'open':
								if ($paramCount === 1) {
									$lastCommand->$varName = $this->baseURL . ltrim($lastCommand->$varName, '/');
								}
								break;
						}

						$paramCount++;
					}
					break;

				// Add comments to last command object
				case ':':
					if (isset($lastCommand)) {
						$lastCommand->comment .= ' ' . trim(substr($command, 1));
					}
					break;

				// Ignore disabled lines
				case '#':
					break;

				// Add new command
				default:
					// Strip comments from command name
					$commandParts = explode(':', $command, 2);
					$commandText = $commandParts[0];

					// Check if command is already implemented
					$commandClass = '\\Selenese\\Command\\' . preg_replace('#[^a-zA-Z0-9_]#', '', $commandText);
					if ($firstChar !== '@' && class_exists($commandClass)) {
						$lastCommand = new $commandClass();
					} else {
						// Not implemented, so use stub class
						$lastCommand = new \Selenese\Command\Stub();
						$lastCommand->command = $commandText;
					}

					// Add debugging information to command object
					$lastCommand->comment = (isset($commandParts[1])) ? $commandParts[1] : '';
					$lastCommand->lineInFile = $lineNumber;

					$this->commands[] = $lastCommand;
					$paramCount = 1;
					break;
			}

			$lineNumber++;
		}

		return true;
	}
}