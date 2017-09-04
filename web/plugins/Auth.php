<?php
/**
 * The Machine
 *
 * PHP version 5
 *
 * @category  Plugin
 * @package   Machine
 * @author    Paolo Savoldi <paooolino@gmail.com>
 * @copyright 2017 Paolo Savoldi
 * @license   https://github.com/paooolino/Machine/blob/master/LICENSE 
 *            (Apache License 2.0)
 * @link      https://github.com/paooolino/Machine
 */
namespace Plugin;

use \Ramsey\Uuid\Uuid;

/**
 * Auth class
 *
 * Function related to user authentication
 *
 * @category Plugin
 * @package  Machine
 * @author   Paolo Savoldi <paooolino@gmail.com>
 * @license  https://github.com/paooolino/Machine/blob/master/LICENSE 
 *           (Apache License 2.0)
 * @link     https://github.com/paooolino/Machine
 */
class Auth
{
    
    private $_machine;
    private $_data_callback;
    
    public $logged_user_id;
    public $data;
    
    const AUTH_COOKIE_NAME = "KfjqrRAVhuJlzvX5ANWz";
    
    /**
     * Auth plugin constructor.
     *
     * The user should not use it directly, as this is called by the Machine.
     *
     * @param Machine $machine the Machine instance.
     */
    function __construct($machine) 
    {
        $this->_machine = $machine;
		
		$this->logged_user_id = 0;
		$this->data = [];
    }
    
	/**
	 * Set a function to be executed when a user authenticates.
	 *
	 * It is used mainly to retrieve any useful user data from database.
	 *
	 * @param function $func a callback function
	 *
	 * @return void
	 */
    public function setDataCallback($func) 
    {
        $this->_data_callback = $func; 
    }
    
	/**
	 * Generate auth cookies for the given user id.
	 *
	 * A loginsession table is used to save the current user id and the session 
	 * code. The same code is saved in the auth cookie.
	 *
	 * @param int $user_id the user id.
	 *
	 * @return void
	 */	
    public function generateAuthCookies($user_id) 
    {
        $req = $this->_machine->getRequest();
        
        // generate a unique session code.
        $sessioncode = md5($this->_uuid());
        
        // set the auth cookie with the session code.
        $this->_machine->setCookie(self::AUTH_COOKIE_NAME, $sessioncode, 0, "/");
        
        // save session in db.
        $this->_machine->plugin("Database")->addItem(
            "loginsession", [
            "user_id" => $user_id,
            "sessioncode" => $sessioncode,
            "ip" => $req["SERVER"]["REMOTE_ADDR"],
            "created" => date("Y-m-d H:i:s")
            ]
        );
    }
    
	/**
	 * Check the auth cookie if matches the session code saved in the db. 
	 *
	 * if true, the callback is executed.
	 *
	 * @return void
	 */	
    public function checkLogin() 
    {
        $this->logged_user_id = 0;
        $this->data = [];
        
        // retrieve cookie value
        $req = $this->_machine->getRequest();
        if (isset($req["COOKIE"][self::AUTH_COOKIE_NAME])) {
            $sessioncode = $req["COOKIE"][self::AUTH_COOKIE_NAME];
            // get the session in db
            $session = $this->_machine->plugin("Database")->findField("loginsession", "sessioncode", $sessioncode);
			if ($session) {
                // additional check based on ip
                if ($session->ip == $req["SERVER"]["REMOTE_ADDR"]) {
                    // return the user id
                    $this->logged_user_id = $session->user_id;
                    // execute data_callback
                    if ($this->_data_callback) {
                        $this->data = call_user_func_array($this->_data_callback, [$this->_machine, $this->logged_user_id]);
                    }
                }
            }
        }
    }
	
	private function _uuid() {
		return Uuid::uuid4();
	}
}
