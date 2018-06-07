<?php

	include_once( "../../api.php");

	// Comprobacion de seguridad
	if( !isset($_REQUEST["poid"]) || !$usuario->getAvailableOptionsForModule("agrupador", "manager")  ){ die("Inaccesible"); }


	$template = new Plantilla();

	$agrupador = new agrupador( obtener_uid_seleccionado() );

	if( isset($_REQUEST["send"]) ){

		$data = array();

		$lista = $_GET["list"];
		$selectedId = reset($lista);
		
		if( is_numeric($selectedId) ){
			$data["uid_usuario"] = $selectedId;
			$update = $agrupador->updateWithRequest($data, "manager", $usuario);

			switch( $update ){
				case null:
					$template->assign("error", "No se modifico nada");
				break;
				case false:
					$template->assign("error", "Error al intentar modificar");
				break;
				default:
					$template->assign("succes", "Guardado correctamente");
				break;
			}
		} else {
			$template->assign("error", "No se reconoce el usuario seleccionado");
		}
	}	
	
	$usuarios = $usuario->obtenerHermanos();
	$manager = $agrupador->obtenerManager();


	$template->assign("title", "seleccionar_manager" );
	$template->assign("seleccionados", array($manager) );
	$template->assign("elemento", $agrupador );
	$template->assign("elementos", $usuarios );
	$template->assign("inputtype", "radio");
	$template->display("listaseleccion.tpl");
?>
