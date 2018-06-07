<?php
	function playthesound(){
		// Este método es asincrono!! No pasa nada por utilizarlo
		exec("GET http://playthesound.dokify.net?".time()." >/dev/null 2>&1 &");
	}
?>
