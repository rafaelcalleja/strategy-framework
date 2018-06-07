<?php
include( "../api.php");
$template = new Plantilla(); 
$modulo = obtener_modulo_seleccionado();

if ( !$modulo ) { die("Inaccesible"); } 

$log = new log();	
$log->info("cliente", "nuevo $modulo", $usuario->getCompany()->getUID() ); 
$config = ( isset($_GET["config"]) && $_GET["config"] == 1 ) ? 1 : 0;

$options = $usuario->getAvailableOptionsForModule(util::getModuleId($modulo), 22/*UID DE LA ACCION DE CREAR*/, $config);
if( !$op = reset($options) ){
	$log->nivel(6);
	$log->resultado("sin permiso", true);
	$template->display("erroracceso.tpl");
	exit;
}

if (isset($_REQUEST["send"])) {
	try {
		$nuevoelemento = new $modulo($_REQUEST, $usuario);
		if( $nuevoelemento->getUID() && !$nuevoelemento->error ){
			if( is_callable( array($modulo, "obtenerCallback" )) ){
				$callbackData = $nuevoelemento->obtenerCallback($usuario);
				$template->assign("acciones", $callbackData["acciones"]);
			}
			$log->resultado("ok", true);
			$template->display("succes_form.tpl");
			exit;
		} else {
			$log->resultado("error ".$nuevoelemento->error, true);
			$template->assign("error", $nuevoelemento->error);
		}	
	} catch(Exception $e) {
		$log->resultado("error ".$e->getMessage(), true);
		$template->assign("error", $e->getMessage());
	}
}


try {
	$item = ($uid = obtener_uid_seleccionado()) && ($comefrom = obtener_comefrom_seleccionado()) ? new $comefrom($uid) : null;
	$campos = $modulo::publicFields( elemento::PUBLIFIELDS_MODE_INIT, ($item instanceof empresa ? null : $item), $usuario);

	// --- si hay campos para completar
	if (count($campos)) {
		$template->assign("campos", $campos);
		$template->display("form.tpl");		

	// --- si solo hay que crear una nueva instancia...
	} else {
		$new = new $modulo($_REQUEST, $usuario);
		$log->resultado("ok", true);
		$template->display("succes_form.tpl");
	}

} catch(Exception $e){
	$template->assign("title", "error");
	$template->assign("html", $template->getString($e->getMessage()) );
	$template->display("simplebox.tpl");
}