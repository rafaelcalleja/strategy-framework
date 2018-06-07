<?php

class Deploy extends elemento implements Ielemento 
{

	const TABLE = 'agd_core.deploy';

	public function __construct($param, $extra = false)
	{
		$this->tabla = self::TABLE;
		$this->nombre_tabla = "deploy";
		parent::instance($param, $extra);
	}


	public function getUserVisibleName () 
	{
		return 'v' . $this->getUID();
	}


	public function getVkey () 
	{
		$info = $this->getInfo();

		if ($vkey = $info['vkey']) {
			return $vkey;
		}
		return false;
	}


	public function getMessage () 
	{
		$info 		= $this->getInfo();
		$message 	= $info['message'];
		$numpull	= $this->getPullNumber();
		$message 	= str_ireplace("Merge pull request ".$numpull, "", $message);
		return $message;
	}

	public function getTimestamp () 
	{
		$info 		= $this->getInfo();
		$date 		= strtotime($info['date']);
		return $date;
	}

	public function getUrl () 
	{
		$info 		= $this->getInfo();
		$url 		= ($info['url']);
		return $url;
	}

	public function isDone () 
	{
		$info 		= $this->getInfo();
		$done 		= ($info['puppet'] === "0" && $info['sql_after'] === "0" && $info['sql_before'] === "0");
		return $done;
	}


	public function isInProgress () 
	{
		return $this->isDone() === false;
	}


	public function getUsername () 
	{
		$info 		= $this->getInfo();
		return $info['user'];
	}

	public function getLog () 
	{
		$info = $this->getInfo();

		if ($log = $info['puppet_out']) {
			return $log;
		}
		return false;
	}

	public function getPullNumber ()
	{
		$info 		= $this->getInfo();
		$message 	= $info['message'];

		if (preg_match('/#(\d+)/', $message, $matches)) {
			return $matches[1];
		}

		return false;
	}

	public function getSQLStatus ()
	{
		$info 			= $this->getInfo();
		$sql_before_txt = $info['sql_before_txt'];
		$sql_after_txt	= $info['sql_after_txt'];
		if (($sql_before_txt != '0 SQL files processed') || ($sql_after_txt != '0 SQL files processed')) {
			return false;
		}
		return true;
	}

	public static function getAll ($SQLOptions = []) 
	{
		$key 	= 'uid_deploy';
		$count 	= isset($SQLOptions['count']) ? $SQLOptions['count'] : false;
		$limit 	= isset($SQLOptions['limit']) ? $SQLOptions['limit'] : false;
		$order 	= isset($SQLOptions['order']) ? $SQLOptions['order'] : $key . ' DESC';

		
		$table 	= self::TABLE;
		$field 	= $count ? "count({$key})" : $key;
		$SQL 	= "SELECT {$field} FROM {$table}";

		if ($count) {
			return db::get($SQL, 0, 0);
		}


		if ($order) {
			$SQL .= " ORDER BY {$order}";
		}

		if ($limit) {
			$SQL .= " LIMIT {$limit[0]}, {$limit[1]}";
		}


		if ($list = db::get($SQL, '*', 0, 'deploy')) {
			return new ArrayObjectList($list);
		}

		return new ArrayObjectList;
	}

	public static function getLast ()
	{
		$table 	= self::TABLE;
		$SQL = "SELECT MAX(uid_deploy) FROM {$table}";

		if ($uid = db::get($SQL, 0, 0)) {
			return new Deploy($uid);
		}
	}

	public static function isRunning () 
	{
		$last = self::getLast();
		return $last->isInProgress();
	}

	public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
	{
		$fieldList = new FieldList;

		return $fieldList;
	}

	public function getTableFields(){
			return array (
				array ("Field" => "uid_deploy",		"Type" => "int(20)", 		"Null" => "NO",		"Key" => "PRI",		"Default" => "",					"Extra" => "auto_increment"),
				array ("Field" => "branch",			"Type" => "text",			"Null" => "NO",		"Key" => "",		"Default" => "",					"Extra" => ""),
				array ("Field" => "date",			"Type" => "timestamp",		"Null" => "NO",		"Key" => "",		"Default" => "CURRENT_TIMESTAMP",	"Extra" => ""),
				array ("Field" => "user",			"Type" => "varchar(20)",	"Null" => "NO",		"Key" => "",		"Default" => "",					"Extra" => ""),
				array ("Field" => "url",			"Type" => "text",			"Null" => "NO",		"Key" => "",		"Default" => "",					"Extra" => ""),
				array ("Field" => "message",		"Type" => "text",			"Null" => "NO",		"Key" => "",		"Default" => "",					"Extra" => ""),
				array ("Field" => "vkey",			"Type" => "bigint(20)",		"Null" => "YES",	"Key" => "",		"Default" => "",					"Extra" => ""),
				array ("Field" => "sql_after",		"Type" => "int(11)",		"Null" => "YES",	"Key" => "",		"Default" => "",					"Extra" => ""),
				array ("Field" => "sql_before",		"Type" => "int(11)",		"Null" => "YES",	"Key" => "",		"Default" => "",					"Extra" => ""),
				array ("Field" => "sql_after_txt",	"Type" => "text",			"Null" => "NO",		"Key" => "",		"Default" => "",					"Extra" => ""),
				array ("Field" => "sql_before_txt",	"Type" => "text",			"Null" => "NO",		"Key" => "",		"Default" => "",					"Extra" => ""),
				array ("Field" => "puppet",			"Type" => "int(11)",		"Null" => "YES",	"Key" => "",		"Default" => "",					"Extra" => ""),
				array ("Field" => "puppet_out",		"Type" => "longtext",		"Null" => "YES",	"Key" => "",		"Default" => "",					"Extra" => "")
			);
		}
}
