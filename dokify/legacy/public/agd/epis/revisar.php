<?php
include( "../../api.php");
if( !$usuario->accesoModulo("epi") ){
	die("Inaccesible");
}

$template = new Plantilla();
$uidEpi = obtener_uid_seleccionado();
if ($uidEpi && is_numeric($uidEpi)) {
	$epi = new epi( obtener_uid_seleccionado() );
	if (isset($_REQUEST['send'])) {

		if($epi->revisar($usuario)) {
			$template->display( "succes_form.tpl" );
			exit;
		} else {
			$template->assign("error", "error_texto");
		}
	}
	$template->assign("html", "confirmar_accion");
	$template->display("confirmaraccion.tpl");
} else {
	die('Inaccesible');
}


