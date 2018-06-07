<?php
	/*ASIGNAR EMPRESAS*/

	include( "../../api.php");
	$template = new Plantilla();

	if( !isset($_REQUEST["poid"]) || !$uid = obtener_uid_seleccionado() ) {
		die("Inaccesible");
	}

	if( !isset($_REQUEST["oid"]) || !is_numeric($_REQUEST["oid"]) ){ exit; }
	$idEmpresa = $_REQUEST["oid"];


	$empresaActual = new empresa($uid);

	if( $idEmpresa === $empresaActual->getUID() ){
		$template->assign("message",'mensaje_asignarte_como_contrata');
		$template->display("error.tpl");
		exit;
	}


	$empresaInferior = new empresa($idEmpresa);
	if ($empresaInferior->inTrash($empresaActual)) {
		$empresaInferior->restaurarPapelera($empresaActual,$usuario);
		$template->assign('textoextra','mensaje_empresa_papelera');
		$template->display("succes_form.tpl");
		exit;
	}

	if ($empresaActual->hasPendingInvitation($empresaInferior)) {
		$template->assign("message",'has_pending_invitation');
		$template->display("error.tpl");
		exit;
	}

	if ( in_array($idEmpresa, $empresaActual->obtenerIdEmpresasInferiores()->getArrayCopy()) ){
		$template->display("empresa_preasignada.tpl");
		exit;
	}
	
	$template->display("empresa_existente.tpl");
	
?>
