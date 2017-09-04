<?php
namespace Plugin;
class Email
{
    
    private $machine;
    
    // hooks
    private $after_mail_send = [];
    
    function __construct($machine) 
    {
        $this->machine = $machine;
    }
    
    public function addHook($hookname, $func) 
    {
        if (isset($this->{$hookname})) {
            $this->{$hookname}[] = $func; 
        }
    }
    
    public function send($opts) 
    {
        // get html
        $html = $this->machine->get_output_template($opts["template"], $opts["data"]);
        // send mail
        $result = mail($opts["to"], $opts["subject"], $html);
        
        // execute hooks
        $opts = [$this->machine, date("Y-m-d H:i:s"), $opts["to"], $opts["subject"], $html, $result];
        $this->machine->executeHook($this->after_mail_send, $opts);
    }
}
