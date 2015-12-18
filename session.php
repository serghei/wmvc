<?php

// CREATE TABLE sessions (id INT AUTO_INCREMENT PRIMARY KEY, time DATETIME, `key` VARCHAR(32), data TEXT);


class Session
{
	protected $__sid = 0;


	function __construct()
	{
		// get cookie name (if it's configured)
		if(!($cookie_name = $this->__getCookieName()))
			return;

		// get cookie TTL
		$cookie_ttl = Config::instance()->get("session[ttl]", 30);

		// get session cookie; if is not defined yet, create it
		$session_key = Cookie::get($cookie_name, Tools::randomString(32));
		Cookie::set($cookie_name, $session_key, $cookie_ttl);

		// get session data from database
		$this->__loadDataFromDatabase($session_key);
	}


	function __destruct()
	{
		// get cookie name (if it's configured)
		if(!($cookie_name = $this->__getCookieName()))
			return;

		// get session key
		$session_key = Cookie::get($cookie_name);

		// save data to database
		$this->__saveDataToDatabase($session_key);
	}


	static function instance()
	{
		static $instance;

		if(!$instance)
			$instance = new Session;

		return $instance;
	}


	function get($name, $default = null)
	{
		if(property_exists($this, $name))
			return $this->$name;

		return $default;
	}


	function set($name, &$value)
	{
		if(isset($value))
			$this->$name = $value;
		else
			$this->remove($name);
	}


	function remove($name)
	{
		if(property_exists($this, $name))
			unset($this->$name);
	}


	protected function __getCookieName()
	{
		$config = Config::instance("app-global");

		// check if we should start a session
		if(!isset($config->session))
			return false;

		// the name of session cookie is not configured
		if(!isset($config->session["name"]))
		{
			error_log("The session support is disabled (the name of session was not configured).");
			return false;
		}

		return $config->session["name"];
	}


	protected function __loadDataFromDatabase($session_key)
	{
		$result = Db::lookup("SELECT id, data FROM sessions WHERE `key`=?", $session_key);

		if($result)
		{
			$this->__sid = $result->id;
			$values = json_decode($result->data);

			if(JSON_ERROR_NONE == json_last_error())
			{
				foreach($values as $key => $val)
				{
					$this->$key = $val;
				}
			}
		}
	}


	protected function __saveDataToDatabase($session_key)
	{
		$data = array();

		foreach(get_object_vars($this) as $key => $val)
		{
			// skip __sid variable
			if("__sid" == $key)
				continue;

			$data[$key] = $val;
		}

		$data = array(
			"id" => $this->__sid,
			"time" => date("Y-m-d H:i:s"),
			"key" => $session_key,
			"data" => json_encode($data)
		);

		Db::save("sessions", array("time", "key", "data"), $data);
	}
}
