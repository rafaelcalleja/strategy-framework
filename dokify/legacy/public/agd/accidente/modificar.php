<?php
/* EDITAR UN EMPLEADO */
include( "../../api.php");
$user = usuario::getCurrent();

if( $uid = obtener_uid_seleccionado() ){
	$accidente = new accidente($uid);
	// foreach (accidente::fieldTabs() as $tab) {
	// 
	// 	$modulo = 'accidente';
	// 	foreach ($accidente->getPublicFields(true, "edit", $user, $tab) as $campos) {
	// 	} 
	// }

	if( $usuario->accesoElemento($accidente) ){
		$template = new Plantilla();
		$template->assign("elemento", $accidente);
		$template->display("ficha/accidente.tpl");
	}
}