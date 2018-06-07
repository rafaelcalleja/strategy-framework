<?php
function smarty_function_new($params, &$smarty)
{
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

	$smarty->_tpl_vars[ $params["result"] ] = $object;

	//if( !is_numeric($uid) ){ die("Especifica el uid del objeto"); }



}
?>
