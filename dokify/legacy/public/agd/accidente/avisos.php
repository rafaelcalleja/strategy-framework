<?php
/* LISTADO DE AVISOS PARA accidentes */

include( "../../api.php");
$template = Plantilla::singleton();
$permiso = $usuario->accesoAccionConcreta("accidente","Avisos");
if (!$permiso) { die("Inaccesible"); }

if ($uid = obtener_uid_seleccionado()) {
	$accidente = new accidente($uid);
} else {
	die("Inaccesible");
}

if (isset($_REQUEST["aviso"])) {
	if (isset($_REQUEST['send']) && $_REQUEST['send'] == 1) {
		if (!empty($_REQUEST['otrosdestinatarios'])) {
			$masDestinatarios = explode(' ', $_REQUEST['otrosdestinatarios']); 
		}
		if ($data = $accidente->sendEmailInfo($usuario, false, $_REQUEST["aviso"], $masDestinatarios)) {
			if (!is_traversable($data) && !count($data)) {
				$data = "envio_email_error";
			}
		} else { 
			$data = "no_hay_emails";
		}
		$template->assign("results_prefix", "envio_email_correcto");
		$template->assign("data", $data);
		$template->display("htmlinside_iframe.tpl");
		exit;
	} else {
		$template->assign('send',1);
		$template->assign('oid',$uid);
		$template->assign('aviso',$_REQUEST['aviso']);
		$template->display("accidente/avisos.tpl");
		exit;
	}
}

$avisos = $accidente->obtenerAvisos($usuario);
$template->assign("title","desc_enviar_aviso_accidente");
$template->assign("elementos", $avisos);
$template->display("listaacciones.tpl");
