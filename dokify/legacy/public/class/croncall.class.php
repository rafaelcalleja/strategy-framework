<?php
class croncall {
	public static function lastcall($classname) {
		if (!trim($classname)) { return false; }
		$db  = db::singleton();
		$sql = "SELECT unix_timestamp(max(`last_call`)) FROM ".TABLE_CRONCALL . " WHERE classname='{$classname}'";
		return $db->query($sql,0,0);
	}
	
	public static function update($classname,$time) {
		$db  = db::singleton();
		$sql = "INSERT INTO ".TABLE_CRONCALL." (`classname`, `last_call`) 
		VALUES ( '{$classname}', from_unixtime({$time}) )
		ON DUPLICATE KEY UPDATE `last_call` = from_unixtime({$time});";
		$db->query($sql);
	}
	
	public static function period($classname) {
		// podríamos convertir esto en una constante de clase?
		return $classname::cronperiod();
	}
}

