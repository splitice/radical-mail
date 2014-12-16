<?php
namespace Radical\Utility\Net\Mail;

class IMAP {
	private $con;
	
	function __construct($hostname, $username, $password){
		$this->open($hostname, $username, $password);
	}

    function open($hostname, $username, $password){
        $this->con = imap_open($hostname, $username, $password);
        if(!$this->con){
            throw new \Exception("Unable to open connection to: ".$hostname.' error: '.imap_last_error());
        }
    }

    function close(){
        imap_close($this->con);
        $this->con = null;
    }

	function __destruct(){
        if($this->con!=null){
            $this->close();
        }
	}
	
	function con(){
		return $this->con;
	}

    /**
     * @param $mailbox
     * @return IMAP\Mailbox
     */
    function get_mailbox($mailbox){
		return new IMAP\Mailbox($this, $mailbox);
	}

    /**
     * @param $ref
     * @param string $pattern
     * @return IMAP\Mailbox[]
     */
    function get_mailboxes($ref, $pattern = '*'){
		$ret = array();
		foreach(imap_list($this->con, $ref, $pattern) as $mb){
			$ret[] = $this->get_mailbox($mb);
		}
		return $ret;
	}
	
	function fetch_body($msg_num){
		//$bodyText = imap_fetchbody($connection,$emailnumber,1.2);
		//if(!strlen($bodyText)>0){
		$bodyText = imap_fetchbody($this->con,$msg_num,1);
		//}
		return $bodyText;
	}
	
	function fetch_overview($msg_num){
		return array_pop(imap_fetch_overview ($this->con, $msg_num));
	}
	
	function set_flag($msg, $flag){
		return imap_setflag_full($this->con, $msg, $flag);
	}
	function set_read($msg){
		return $this->set_flag($msg, '\Seen');
	}
	function search($for){
		$result = imap_search($this->con, $for);
		return $result;
	}
	function search_unread(){
		return $this->search('UNSEEN');
	}
	
	function search_all(){
		return $this->search('ALL');
	}
}