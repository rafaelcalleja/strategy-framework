<?php

	/** NOS INDICA SI SE PUEDE RECORRER UN OBJETO O NO **/
	function is_traversable($var){
		if( is_array($var) || $var instanceof Traversable ){
			return true;
		}
		return false;
	}

	function in_arrayi_multiple($arr1,$arr2){
		foreach($arr1 as $key => $value){
			if( in_arrayi($value,$arr2) ){
				return true;
			}
		}
		return false;
	}

	function in_arrayi($val,$array){
		return in_array(strtoupper($val),array_map("strtoupper",$array));
	}


	function array_limite_first($array){
		return current($array);
	}

 	function array_multiple_unique($array){
		$new = $new1 = array();
		foreach ($array as $k=>$na)
			$new[$k] = serialize($na);
		$uniq = array_unique($new);
		foreach($uniq as $k => $ser)
			$new1[$k] = unserialize($ser);
		return ($new1);
    }

 	function utf8_multiple_encode($array){
 		if (!$array) return $array;
		foreach($array as $key => $value) {
			if (is_array($array[$key])) {
				$array[$key] = utf8_multiple_encode($array[$key]);
			} else {
				$array[$key] = utf8_encode($array[$key]);
			}
		}
		return $array;
	}

 	function utf8_multiple_decode($array){
		$newArray = array();
		foreach( $array as $key => $value ){
			if( is_array($array[$key]) ){
				$array[$key] = utf8_multiple_decode($array[$key]);
			} else {
				$array[$key] = utf8_decode($array[$key]);
			}
		}
		return $array;
	}



	function merge_values_and_fields($fields, $values) {
		if (!$fields || !$values) return $fields;
		foreach ($fields as $fieldsKey => $fieldsValue) {
			foreach ($values as $valueskey => $valuesValue) {
				if ($fieldsKey == $valueskey) {
					$fields[$fieldsKey]['value'] = $_REQUEST[$valueskey];
				}
			}
		}

		return $fields;
	}