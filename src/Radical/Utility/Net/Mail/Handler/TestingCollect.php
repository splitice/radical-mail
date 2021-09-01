<?php
namespace Radical\Utility\Net\Mail\Handler;
use Radical\Utility\Net\Mail\Message;

class TestingCollect implements IMailHandler {
	private $sent = array();
	function getSent(){
		return $this->sent;
	}
	function clearSent(){
		$sent = $this->sent;
		$this->sent = array();
		return $sent;
	}
	function send(Message $message){
        $this->sent[] = $message;
        return true;
	}
}