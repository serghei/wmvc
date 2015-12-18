<?php

class Model
{
	protected $table;
	protected $data = null;
	protected $data_new = null;

	// special fields
	protected $id = 0;
	protected $adate = false;
	protected $udate = false;


	function __construct($table, $id = 0)
	{
		$this->table = str_replace("`", "", $table);
		$this->id = (int)$id;
	}


	function __get($name)
	{
		if(!is_null($this->data_new) && property_exists($this->data_new, $name))
			return $this->data_new->$name;

		if($this->id)
		{
			if(is_null($this->data))
				$this->data = Db::lookup("SELECT * FROM `{$this->table}` WHERE id=?", $this->id);

			if($this->data && property_exists($this->data, $name))
				return $this->data->$name;
		}

		return "";
	}


	function __set($name, $value)
	{
		if(is_null($this->data_new))
			$this->data_new = new stdClass;

		$this->data_new->$name = $value;
	}


	function set($data)
	{
		if(is_array($data) || is_object($data))
		{
			foreach($data as $key => $val)
			{
				$this->$key = $val;
			}
		}
	}


	function save()
	{
		// update
		if($this->id)
		{
			if(is_null($this->data_new))
				return;

			$fields = "";
			$values = array();

			if($this->udate)
				$fields = "udate = NOW()";

			foreach($this->data_new as $key => $val)
			{
				if($fields) $fields .= ", ";
				$fields .= "`" . str_replace("`", "", $key) . "` = ?";
				$values[] = $val;
			}

			if(count($values))
			{
				$sql = "UPDATE `{$this->table}` SET $fields WHERE id = " . (int)$this->id;
				return Db::query($sql, $values);
			}

			return true;
		}

		// insert
		else
		{
			$fields = "";
			$values = array();
			$explicit_values = "";

			// adate
			if($this->adate)
			{
				if($fields) $fields .= ", ";
				$fields .= "adate";

				if($explicit_values) $explicit_values .= ", ";
				$explicit_values .= "NOW()";
			}

			// udate
			if($this->udate)
			{
				if($fields) $fields .= ", ";
				$fields .= "udate";

				if($explicit_values) $explicit_values .= ", ";
				$explicit_values .= "NOW()";
			}

			foreach($this->data_new as $key => $val)
			{
				if($fields) $fields .= ", ";
				$fields .= "`" . str_replace("`", "", $key) . "`";
				$values[] = $val;
			}

			$placeholders = rtrim(str_repeat("?, ", count($values)), ", ");

			if($explicit_values)
				$placeholders = ",{$placeholders}";

			$sql = "INSERT INTO `{$this->table}` ({$fields}) VALUES ({$explicit_values}{$placeholders})";

			if(Db::query($sql, $values))
			{
				$this->id = Db::lastInsertId();
				return true;
			}

			return false;
		}
	}


	function delete()
	{
		if($this->id)
			return Db::query("DELETE FROM `{$this->table}` WHERE id = ?", (int)$this->id);

		return true;
	}
}
