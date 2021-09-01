<?php
namespace Radical\Utility\Net\Mail\Handler;
use Radical\Utility\Net\Mail\Message;

interface IMailHandler {
    /**
     * @param Message $message
     * @param $body
     * @return bool success status
     */
    function send(Message $message);
}