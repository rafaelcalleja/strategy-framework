<?php
	include_once( "../../../api.php");
	$template = new Plantilla();

	$template->assign("data", plantillaemail::obtenerStringsPredefinidos() );

	$template->display("configurar/stringspredefinidos.tpl");

?>
