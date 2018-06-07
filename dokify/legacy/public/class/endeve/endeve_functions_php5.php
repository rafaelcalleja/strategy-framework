<?php

class EndeveFunctions {
	static function shutdownErrorReporting() {
		error_reporting(EndeveFunctions::restoreErrorReporting(true) & ~E_STRICT);
	}

	static function restoreErrorReporting($preload=false) {
		static $last_config = null;
		if($preload || !isset($last_config)) {
			return $last_config = error_reporting();
		}else{
			return error_reporting($last_config);
		}
	}
	
	static function array_intersect_key($array1, $array2) {
		if(function_exists('array_intersect_key')) {
			return array_intersect_key($array1, $array2);
		}else{
			$res = array();
			foreach(array_keys($array1) as $key) {
				if(isset($array2[$key])) {
					$res[$key] = $array1[$key];
				}
			}
			return $res;
		}
	}
	
	static function array_combine($keys, $values) {
		return array_combine($keys, $values);
	}
	
	static function is_a($object, $class_name) {
		return $object instanceof $class_name;
	}
	
	static function curl_setopt_array($ch, $options) {
		if(function_exists('curl_setopt_array')) {
			return curl_setopt_array($ch, $options);
		}else{
			foreach($options as $option => $value) {
				if(!curl_setopt($ch, $option, $value)) {
					return false;
				} 
			}
			return true;
		}
	}
}