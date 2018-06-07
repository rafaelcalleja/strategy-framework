<?php
	function is_ie(){
		return ( strpos($_SERVER["HTTP_USER_AGENT"], "MSIE") !== false ) ? true : false;
	}

	function is_ie7(){
		return ( strpos($_SERVER["HTTP_USER_AGENT"], "MSIE 7") !== false ) ? true : false;
	}

	function is_ie8(){
		return ( strpos($_SERVER["HTTP_USER_AGENT"], "MSIE 8") !== false ) ? true : false;
	}

	function is_ie6(){
		return ( strpos($_SERVER["HTTP_USER_AGENT"], "MSIE 6") !== false ) ? true : false;
	}
?>
