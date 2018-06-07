<?php
	include_once( "../../api.php");

	if( !isset($_GET["folder"]) ){ die("Inaccesible"); }
	$dir = DIR_IMG . db::scape($_GET["folder"]) ."/";
	if( !is_dir($dir) ){ die("Inaccesible"); }


	$template = new Plantilla();
	$template->assign("folder", basename($dir) );

	$iconos = array();
	foreach( glob($dir . "*.png") as $file){
		$iconos[] = basename($file);
	}

	$template->assign("iconos", $iconos);
	$template->display("iconos.tpl");
?>
