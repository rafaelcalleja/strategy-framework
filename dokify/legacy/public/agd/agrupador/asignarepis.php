<?php
require_once("../../api.php");
$template = Plantilla::singleton();
$agrupador = new agrupador( obtener_uid_seleccionado() );

if( isset($_REQUEST["send"]) ){
	$estado = $agrupador->actualizarTiposEpi();
	if( $estado === true ){
		$template->assign("succes", "exito_titulo");
	} else {
		$template->assign( "error" , $estado  );
	}
}

$tiposAsignados = $agrupador->obtenerTiposEpi();
$tiposEpi = tipo_epi::getAll();
$tiposDisponibles = elemento::discriminarObjetos($tiposEpi, $tiposAsignados);
	
	
$template->assign( "asignados" , $tiposAsignados  );
$template->assign( "disponibles" , $tiposDisponibles  );
$template->assign( "acciones" , array(
	array("string" => "ver_tipos_asignados", "href" => "#buscar.php?p=0&q=tipo:tipo_epi%agrupador:" . $agrupador->getUID(), "class" => "unbox-it" )	
));
$template->display( "configurar/asignarsimple.tpl" );