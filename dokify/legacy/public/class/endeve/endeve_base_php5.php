<?php

class EndeveBase {
	static private function getInstance() {
		static $singleton = null;
		if(!isset($singleton)) {
			$singleton = new EndeveBaseFunctions;
		}
		return $singleton;
	}
	
	static function getUrl() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), 'getUrl'), $args);
	}
	
	static function init() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), 'init'), $args);
	}

	static function ping() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), 'ping'), $args);
	}

	static function exec() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), 'exec'), $args);
	}

	static function read() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), 'read'), $args);
	}

	static function find() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), 'find'), $args);
	}
	
	static public function create($model, &$xml) {
		$args = func_get_args();
		$args[1] = &$xml;
		return call_user_func_array(array(self::getInstance(), 'create'), $args);
	}

	static function update($model, $id, &$xml) {
		$args = func_get_args();
		$args[2] = &$xml;
		return call_user_func_array(array(self::getInstance(), 'update'), $args);
	}
	
	static function delete() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), 'delete'), $args);
	}

	static function deliver() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), 'deliver'), $args);
	}
	
	static function getLastResponse() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), 'getLastResponse'), $args);
	}
	
	static function validResponse() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), 'validResponse'), $args);
	}

	static function __exec() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), '__exec'), $args);
	}
	
	static function __static() {
		$args = func_get_args();
		return call_user_func_array(array(self::getInstance(), '__static'), $args);
	}
	
}
