<?php
	function is_opera(){
		return ( strpos($_SERVER["HTTP_USER_AGENT"], "Opera") !== false ) ? true : false;
	}
