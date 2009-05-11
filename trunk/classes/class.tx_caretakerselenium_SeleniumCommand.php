<?php

/**
NaworkSeleniumCommand represents a command executed by the selenium test suite

*/

class tx_caretakerselenium_SeleniumCommand {
	
	public $command = '';
	public $comment = '';
	public $params = array();
	public $lineInFile = 0;
	
	function __construct ($command,$lineInFile,$comment = '') {
	
		$this->command = $command;
		$this->comment = $comment;
		$this->lineInFile = $lineInFile;
		
	}
	
	function addParam($param) {
	
		if(count($this->params) < 2) {
		
			$this->params[] = $param;
			return true;
		}
		return false;
	}
	
	function addComment($comment) {
	
		$this->comment .= ' '.$comment;
	}
}

?>
