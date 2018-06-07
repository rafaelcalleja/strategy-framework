<?php
	function is_webkit(){
		return ( strpos($_SERVER["HTTP_USER_AGENT"], "WebKit") !== false ) ? true : false;
	}
?>
