<?php
function smarty_function_push($params, &$smarty)
{

	$var = $params["var"];
	$value = $params["value"];
	
	
	if( isset($params["key"]) ){
		if( is_numeric($value) ){
			if( !isset($var[ $params["key"] ]) ){
				$var[ $params["key"] ] = 0;
			} else {
				$var[ $params["key"] ] += $value;
			}
		} else {
			if( !isset($var[ $params["key"] ]) ){
				$var[ $params["key"] ] = array();
			} else {
				$var[ $params["key"] ][] = $value;
			}
		}
	} else {
		$var[] = $value;
	}

	/*
	$type = $params["type"];
	$uid = ( isset($params["uid"]) ) ? $params["uid"] : null;
	$param = false;
	if( isset($params["param"]) ){
		$param = $params["param"];
		$object = new $type($uid, $param );
	} else {
		if( $uid ){
			$object = new $type($uid);
		} else {
			$object = new $type();
		}
	}
	*/

	$smarty->_tpl_vars[ $params["result"] ] = $var;

	//if( !is_numeric($uid) ){ die("Especifica el uid del objeto"); }



}
?>
