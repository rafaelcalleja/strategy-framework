<?php
	/*MENSAJE DE INICIO DE AGD*/
	include( "../../api.php");

	$datosAccesoModulo = $usuario->accesoModulo("estructura");
	if( !is_array($datosAccesoModulo) ){ die("Inaccesible");}


	$modulo = obtener_modulo_seleccionado();

	$elemento = new $modulo( obtener_uid_seleccionado() );

	if( !$usuario->accesoElemento($elemento) ){ die("Inaccesible"); }

	$template = new Plantilla();

	if( is_numeric($elemento->getUID()) && isset($_REQUEST["send"]) ){

		if( isset($_REQUEST["oid"]) && is_numeric($_REQUEST["oid"]) && isset($_REQUEST["t"]) ){
			// Tipo de objeto seleccionado...
			if( $_REQUEST["t"] == "estructura" || $_REQUEST["t"] == "agrupador"){ $tipo = $_REQUEST["t"]; } else { die("Inaccesible"); }

			$contenedor = new $tipo($_REQUEST["oid"]);

			$update = $contenedor->aplicarEn($elemento, $usuario);

			switch( $update ){
				case null:
					$template->assign ("error", "No se modifico nada");
				break;
				case false:
					$template->assign ("error", "Error al intentar modificar");
				break;
				case true:
					$template->display("succes_form.tpl");exit;
				break;
			}
		} else {
			$template->assign ("error", "Selecciona un elemento");
		}
	}

	$elementos = array();

	//---- Buscamos las estructuras y si es el mismo tipo quitamos el elemento actual
	$agrupadores = $usuario->getCompany()->obtenerAgrupadoresPropios(array($usuario, "has=folder"));
	$agrupadores = elemento::discriminarObjetos($agrupadores, $elemento);
	if( count($agrupadores) ){
		$elementos["Elementos"] = $agrupadores;
	}

	$template->assign ("elementos",  $elementos);
	$template->display("seleccion.tpl");

?>