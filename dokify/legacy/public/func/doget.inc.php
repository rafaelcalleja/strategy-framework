<?php
	function doGET($url){
		exec("GET $url", $respuesta); 
		return @$respuesta[0];
	}
?>
