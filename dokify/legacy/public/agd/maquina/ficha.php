<?php

	include_once( "../../api.php");

	$idElemento = $_REQUEST["oid"];
	if( !is_numeric($idElemento) ){ exit; }

	$maquina = new maquina( $idElemento, false);



	$tpl = Plantilla::singleton();
	$tpl->assign("elemento", $maquina);
	$tpl->assign("usuario", $usuario);
	$tpl->display("ficha_elemento.tpl");
?>
