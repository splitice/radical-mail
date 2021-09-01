<?php
namespace Radical\Utility\Net\Mail;

class IMAP
{
    private $con;
    private $username;
    private $password;

    function __construct($hostname, $username, $password)
    {
        $this->open($hostname, $username, $password);
    }

    function open($hostname, $username, $password)
    {
        if(!$this->con || $this->username = $username || $this->password != $password){
            $result = $this->con = @imap_open($hostname, $username, $password);
            $this->username = $username;
            $this->password = $password;
        }else{
            $result = @imap_reopen($this->con, $hostname);
            if(!$result){
                @imap_close($this->con);
                $result = $this->con = @imap_open($hostname, $username, $password);
            }
        }
        if (!$result) {
            throw new \Exception("Unable to open connection to: " . $hostname . ' error: ' . imap_last_error());
        }
    }

    function ping(){
        if(!$this->con) return false;
        return @imap_ping($this->con);
    }

    function close()
    {
        @imap_close($this->con);
        $this->con = null;
    }

    function __destruct()
    {
        if ($this->con != null) {
            $this->close();
        }
    }

    function con()
    {
        return $this->con;
    }

    /**
     * @param $mailbox
     * @return IMAP\Mailbox
     */
    function get_mailbox($mailbox)
    {
        return new IMAP\Mailbox($this, $mailbox);
    }

    /**
     * @param $ref
     * @param string $pattern
     * @return IMAP\Mailbox[]
     */
    function get_mailboxes($ref, $pattern = '*')
    {
        $ret = array();
        $mailboxes = imap_list($this->con, $ref, $pattern);
        if($mailboxes===false){
            $this->close();
            throw new \Exception("Unable to open list mailboxes due to error: " . imap_last_error());
        }
        foreach ($mailboxes as $mb) {
            $ret[] = $this->get_mailbox($mb);
        }
        return $ret;
    }

    function get_msg($mid)
    {
        $ret = new \stdClass();
        $ret->htmlmsg = $ret->plainmsg = $ret->charset = '';
        $ret->attachments = array();

        // BODY
        $s = imap_fetchstructure($this->con, $mid);

        $ret->parameters = array();
        foreach ($s->parameters as $x)
            $ret->parameters[strtolower($x->attribute)] = $x->value;

        if (empty($s->parts))  // simple
            $this->get_part($ret, $mid, $s, 0);  // pass 0 as part-number
        else {  // multipart: cycle through each part
            foreach ($s->parts as $partno0 => $p)
                $this->get_part($ret, $mid, $p, $partno0 + 1);
        }

        return $ret;
    }

    function get_part($ret, $mid, $p, $partno)
    {
        // DECODE DATA
        $data = ($partno) ?
            imap_fetchbody($this->con, $mid, $partno) :  // multipart
            imap_body($this->con, $mid);  // simple

        // Any part may be encoded, even plain text messages, so check everything.
        if ($p->encoding == 4)
            $data = quoted_printable_decode($data);
        elseif ($p->encoding == 3)
            $data = base64_decode($data);

        // PARAMETERS
        // get all parameters, like charset, filenames of attachments, etc.
        $params = array();
        if (isset($p->parameters)) {
            foreach ($p->parameters as $x)
                $params[strtolower($x->attribute)] = $x->value;
        }
        if (isset($p->dparameters)) {
            foreach ($p->dparameters as $x)
                $params[strtolower($x->attribute)] = $x->value;
        }

        // ATTACHMENT
        // Any part with a filename is an attachment,
        // so an attached text file (type 0) is not mistaken as the message.
        if (!empty($params['filename']) || !empty($params['name'])) {
            // filename may be given as 'Filename' or 'Name' or both
            $filename = empty($params['filename']) ? $params['name'] : $params['filename'];
            // filename may be encoded, so see imap_mime_header_decode()
            $ret->attachments[$filename] = $data;  // this is a problem if two files have same name
        }

        // TEXT
        if ($p->type == 0 && $data) {
            // Messages may be split in different parts because of inline attachments,
            // so append parts together with blank row.
            if (strtolower($p->subtype) == 'plain')
                $ret->plainmsg .= trim($data) . "\n\n";
            else
                $ret->htmlmsg .= $data . "<br><br>";
            if(isset($params['charset'])) {
                $ret->charset = $params['charset'];  // assume all parts are same charset
            }
        }

        // EMBEDDED MESSAGE
        // Many bounce notifications embed the original message as type 2,
        // but AOL uses type 1 (multipart), which is not handled here.
        // There are no PHP functions to parse embedded messages,
        // so this just appends the raw source to the main message.
        elseif ($p->type == 2 && $data) {
            $ret->plainmsg .= $data . "\n\n";
        }

        // SUBPART RECURSION
        if (!empty($p->parts)) {
            foreach ($p->parts as $partno0 => $p2) {

                $subpart = new \stdClass();
                $subpart->htmlmsg = $subpart->plainmsg = $subpart->charset = '';
                $subpart->attachments = array();
                $ret->subpart = $subpart;

                $this->get_part($subpart, $mid, $p2, $partno . '.' . ($partno0 + 1));  // 1.2, 1.2.1, etc.
            }
        }
    }

    function fetch_body($msg_num)
    {
        //$bodyText = imap_fetchbody($connection,$emailnumber,1.2);
        //if(!strlen($bodyText)>0){
        $bodyText = imap_fetchbody($this->con, $msg_num, 1);
        //}
        return $bodyText;
    }

    function fetch_overview($msg_num)
    {
        $ret = imap_fetch_overview($this->con, $msg_num);
        if($ret){
            return $ret[0];
        }
    }

    function set_flag($msg, $flag)
    {
        return imap_setflag_full($this->con, $msg, $flag);
    }

    function set_read($msg)
    {
        return $this->set_flag($msg, '\Seen');
    }

    function search($for)
    {
        $result = imap_search($this->con, $for);
        return $result;
    }

    function search_unread()
    {
        return $this->search('UNSEEN');
    }

    function search_all()
    {
        return $this->search('ALL');
    }
}