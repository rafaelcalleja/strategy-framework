<?php
	function is_touch_device(){
		if( stripos($_SERVER['HTTP_USER_AGENT'], "Android") !== false || stripos($_SERVER['HTTP_USER_AGENT'], "iphone") !== false || stripos($_SERVER['HTTP_USER_AGENT'], "ipad") !== false ){
			return true;
		}
		return false;
	}
?>
