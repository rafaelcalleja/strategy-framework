<?php
function smarty_function_reset($params, &$smarty)
{
	$result = reset($params["array"]);
	$smarty->_tpl_vars[ $params["result"] ] = $result;

}
?>
