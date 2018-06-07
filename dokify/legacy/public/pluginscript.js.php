<?php
	include("api.php");
	//header ("Expires: " . gmdate ("D, d M Y H:i:s", time() + (60 * 60 * 30 * 3) ) . " GMT");
	header ("Content-type: text/javascript; charset: UTF-8");


	ob_start("ob_gzhandler");
	ob_start("compress");
	
	$tpl = Plantilla::singleton();
	
	$plugins = plugin::getAll();
	foreach( $plugins as $plugin){
		$archivos = $plugin->getFiles();
		if (isset($archivos["js"]) && is_traversable($archivos["js"]) && count($archivos["js"])) {
			foreach($archivos["js"] as $script){
				include_once( $plugin->getFolder() ."/" . $script );
			}
		}
	}


	ob_end_flush();
?>
