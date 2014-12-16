<?php
namespace Radical\Utility\Net\Mail;

use Radical\Core\IRenderToString;
use Radical\Utility\Net\Mail\Handler;

class Message {
	/**
	 * @var Handler\IMailHandler
	 */
	private $handler;
	
	private $to;
	private $from;
	private $subject;
	private $reply_to;
	private $html = false;
	private $headers;
    private $body;

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body)
    {
        $this->body = self::body($body);
    }
	
	/**
	 * @return array $headers
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * @param array $headers
	 */
	public function setHeaders($headers) {
		$this->headers = $headers;
	}

	function __construct(Handler\IMailHandler $handler = null){
		if($handler === null){
			$handler = HandlerRouter::get_handler();
		}
		$this->handler = $handler;
	}
	
	/**
	 * @return string $reply_to
	 */
	public function getReplyTo() {
		return $this->reply_to;
	}

	/**
	 * @param string $reply_to
	 */
	public function setReplyTo($reply_to) {
		$this->reply_to = $reply_to;
	}

	/**
	 * @return string $to
	 */
	public function getTo() {
		return $this->to;
	}

	/**
	 * @return string $from
	 */
	public function getFrom() {
		return $this->from;
	}

	/**
	 * @return string $subject
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @param string $to
	 */
	public function setTo($to) {
		$this->to = $to;
	}

	/**
	 * @param string $from
	 */
	public function setFrom($from) {
		$this->from = $from;
	}

	/**
	 * @param boolean $html
	 */
	public function setHtml($html) {
		$this->html = (bool)$html;
	}

	/**
	 * @return string $html
	 */
	public function getHtml() {
		return $this->html;
	}

	/**
	 * @param string $subject
	 */
	public function setSubject($subject) {
		$this->subject = $subject;
	}
	
	static function body($body){
		if($body instanceof IRenderToString){
			return $body->renderString();
		}else{
			return $body;
		}
	}

    /**
     * @return bool success status
     */
    function send(){
		//$this->handler->setHeaders($this->headers);
		return $this->handler->Send($this);
	}
}