<?php

class Cookie
{

	static public function set($name, $value, $ttl)
	{
		$config = Config::instance();

		$_COOKIE[$name] = $value;
		setcookie($name, $value, ($ttl ? time() + $ttl * 86400 : 0), "/", $config->domain);
	}


	static public function del($name)
	{
		$config = Config::instance();

		unset($_COOKIE[$name]);
		setcookie($name, "", time() - 3600, "/", $config->domain);
	}


	static public function get($name, $default = false)
	{
		if(isset($_COOKIE[$name]))
			return $_COOKIE[$name];
		$_COOKIE[$name] = $default;

		return $default;
	}

}
