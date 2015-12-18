<?php


class Db
{

	static function save($table, $fields, $data)
	{
		if(!isset($data["id"]))
		{
			error_log("DB::save(): FATAL ERROR! Missing \"id\" field.");
			die("Database error!");
		}

		$id = intval($data["id"]);

		$values = array();
		foreach($fields as $name => $type)
		{
			// no type provided
			if(is_numeric($name))
			{
				$name = $type;
				$type = "";
			}

			if("id" != $name)
			{
				if(isset($data[$name]))
				{
					switch($type)
					{
						case "integer":
							$values[$name] = intval($data[$name]);
							break;
						case "decimal":
							$values[$name] = sprintf("%0.2f", $data[$name]);
							break;
						default:
							$values[$name] = trim($data[$name]);
					}
				}
				else
				{
					error_log("DB::save(): WARNING! Missing \"$name\" field. Defaulting to NULL.");
					$values[$name] = null;
				}
			}
		}

		if($id) // UPDATE
		{
			$fields = array_keys($values);
			array_walk($fields, function(&$val) { $val = "`$val` = ?"; });
			$fields = implode(", ", $fields);

			$result = DB::query("UPDATE $table SET $fields WHERE id=$id", array_values($values));
		}
		else // INSERT
		{
			$fields = "`" . implode("`, `", array_keys($values)) . "`";
			$placeholders = rtrim(str_repeat("?,", count($values)), ",");

			$result = DB::query("INSERT INTO $table ($fields) VALUES ($placeholders)", array_values($values));
			if(false !== $result)
				$id = DB::lastInsertId();
		}

		return (false === $result ? 0 : $id);
	}


	static function query($sql)
	{
		self::$rowCount = 0;

		try
		{
			$args = self::funcArgsToArray(func_get_args());
			self::$sth = self::dbh()->prepare($sql);

			if(self::$sth->execute($args))
			{
				self::$rowCount = self::$sth->rowCount();

				if(self::$sth->columnCount())
					return self::$sth->fetchAll();
				else
					return true;
			}

			return false;
		}
		catch(PDOException $e)
		{
			error_log($sql);
			error_log($e->getMessage());
			die("Database error!");
        }
	}


	static function lookup($sql)
	{
		$args = self::funcArgsToArray(func_get_args());
		$result = self::query("$sql LIMIT 1", $args);
		if($result)
			return $result[0];
		return false;
	}


	static function lastInsertId()
	{
		return self::dbh()->lastInsertId();
	}


	static function affectedRows()
	{
		return self::$rowCount;
	}


	/** Get column meta for last query */
	static function meta($field_name)
	{
		static $meta = array();

		if(!count($meta))
		{
			$cols = self::$sth->columnCount();
			for($i = 0; $i < $cols; $i++)
			{
				$meta_col = self::$sth->getColumnMeta($i);
				$meta[$meta_col["name"]] = $meta_col;
			}
		}

		return $meta[$field_name];
	}


	/** Get column types for last query */
	static function colTypes()
	{
		$result = array();

		$cols = self::$sth->columnCount();
		for($i = 0; $i < $cols; $i++)
		{
			$meta_col = self::$sth->getColumnMeta($i);
			$result[$meta_col["name"]] = $meta_col["native_type"];
		}

		return $result;
	}


	/** Get description of table (field => type)*/
	static function desc($table)
	{
		$result = self::query("DESC `" . str_replace("`", "", $table) . "`");

		$fields = array();
		foreach($result as $row)
		{
			list($type) = explode("(", $row->Type);
			$fields[$row->Field] = $type;
		}

		return $fields;
	}


	/** Check if fields exists */
	static function fieldExists($table, $field)
	{
		self::query("SHOW COLUMNS FROM $table WHERE Field = ?", $field);
		return (0 != self::$rowCount);
	}


	private static function dbh()
	{
		static $dbh = null;

		if(null == $dbh) {
			require dirname(__FILE__) . "/../cfg/database.php";

			if(!isset($db_host))
				$db_host = "localhost";

			$dbh = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
			$dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			//$dbh->exec("SET NAMES utf8"); // for PHP < 5.3.6
		}

		return $dbh;
	}


	private static function funcArgsToArray($args)
	{
		$result = array();

		$first_arg = true;
		foreach($args as $arg)
		{
			if($first_arg)
				$first_arg = false;
			elseif(is_array($arg))
				$result = array_merge($result, $arg);
			else
				$result[] = $arg;
		}

		return $result;
	}


	private static $rowCount;
	private static $sth;
}
