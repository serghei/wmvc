<?php


// CREATE TABLE config (id INT AUTO_INCREMENT PRIMARY KEY, scope VARCHAR(50), name VARCHAR(50), value TEXT);


class Config
{
	protected $__const_vars = array();
	protected $__db_vars = array();
	protected $__scope;


	protected function __construct($scope)
	{
		$this->__scope = $scope;

		// load global config
		if("app-global" == $scope)
		{
			define("CFG_FILE", dirname(__FILE__) . "/../cfg/config.php");

			// include config file, if exists
			if(file_exists(CFG_FILE))
			{
				include CFG_FILE;
				foreach(get_defined_vars() as $key => $val)
				{
					$this->__const_vars[] = $key;
					$this->$key = $val;
				}
			}
		}

		$this->__loadDataFromDatabase();
	}


	function __destruct()
	{
		$this->__saveDataToDatabase();
	}


	static function instance($scope = "app-global")
	{
		static $instance = array();

		if(!isset($instance[$scope]))
			$instance[$scope] = new Config($scope);

		return $instance[$scope];
	}


	function get($name, $default = null)
	{
		if(property_exists($this, $name))
			return $this->$name;

		return $default;
	}

	function remove($name)
	{
		if(property_exists($this, $name))
		{
			unset($this->$name);
			Db::query("DELETE FROM config WHERE scope=? AND name=?", $this->__scope, $name);
		}
	}


	protected function __loadDataFromDatabase()
	{
		$class_vars = get_class_vars(__CLASS__);

		$configs = Db::query("SELECT id, scope, name, value FROM config WHERE scope=?", $this->__scope);
		foreach($configs as $config)
		{
			// do not override constant configs
			if(in_array($config->name, $this->__const_vars))
				continue;

			// do not override class' vars
			if(in_array($config->name, $class_vars))
				continue;

			$value = json_decode($config->value);

			if(JSON_ERROR_NONE == json_last_error())
			{
				$this->{$config->name} = $value;
				$this->__db_vars[$config->name] = array("id" => $config->id, "hash" => md5($config->value));
			}
		}
	}


	protected function __saveDataToDatabase()
	{
		$class_vars = array_keys(get_class_vars(__CLASS__));

		foreach(get_object_vars($this) as $key => $val)
		{
			// do not save constant configs
			if(in_array($key, $this->__const_vars))
				continue;

			// do not save class' vars
			if(in_array($key, $class_vars))
				continue;

			$config = array(
				"id" => (isset($this->__db_vars[$key]) ? (int)$this->__db_vars[$key]["id"] : 0),
				"scope" => $this->__scope,
				"name" => $key,
				"value" => json_encode($val)
			);

			// the config was not changed, skip it
			if($config["id"] && md5($config["value"]) == $this->__db_vars[$key]["hash"])
				continue;

			Db::save("config", array("scope", "name", "value"), $config);
		}
	}

}
