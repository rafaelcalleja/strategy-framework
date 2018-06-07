<?php
function smarty_function_arrayPush($params, &$smarty)
{

	if (isset($params["var"])) {
		$var = $params["var"];
	} else {
		$var = array();
	}

	$value = $params["value"];
	
	if( isset($params["key"]) ){
		$var[$params["key"]] = $value;
	} else {
		$var[] = $value;
	}

	$smarty->_tpl_vars[ $params["result"] ] = $var;


}
?>