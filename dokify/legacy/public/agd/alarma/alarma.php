<?php

	if( !isset($_REQUEST["poid"]) && isset($_REQUEST["poid"]) ){ exit(); }
	include( "../../api.php");

	$tpl = Plantilla::singleton();

	$modulo = $_REQUEST["m"];
	$objActual = new $modulo( $_REQUEST["poid"], false );

	$config = ( isset($_GET["config"]) && $_GET["config"] == 1 ) ? 1 : 0;

	$tpl->assign("usuario", $usuario);
	$tpl->assign("config", $config);
	$tpl->assign("objActual", $objActual);
	$tpl->display("alarma.tpl");
?>
