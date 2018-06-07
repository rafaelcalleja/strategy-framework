<?php
	include("api.php");
	header ("Content-type: text/css; charset: UTF-8");


	ob_start("ob_gzhandler");
	ob_start("compress");
	
		

	$plugins = $usuario->obtenerPlugins();
	if ($plugins && count($plugins)) {
		foreach( $plugins as $plugin){
			$archivos = $plugin->getFiles();
			if( isset($archivos["css"]) ){
				foreach($archivos["css"] as $style){

					include_once( $plugin->getFolder() ."/" . $style );
				}
			}
		}
	}
		


	ob_end_flush();
?>
