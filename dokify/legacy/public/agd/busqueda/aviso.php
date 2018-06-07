<?php

	include( "../../api.php");
	$template = new Plantilla();

	$uidSearch = obtener_uid_seleccionado();
	if (!$uidSearch) die("inaccesible");

	$busqueda = new buscador($uidSearch);

	if ( isset($_REQUEST["send"]) ) {
		$statusChecked = isset($_REQUEST["estado_0"]) || isset($_REQUEST["estado_1"]) || isset($_REQUEST["estado_2"]) || isset($_REQUEST["estado_3"]) || isset($_REQUEST["estado_4"]);

		if ($statusChecked) {
			$update = $busqueda->update(false, "aviso", $usuario);
			switch( $update ){
				case null:
					$template->assign("info", "No se modifico nada");
				break;
				case false:
					$template->assign("message", "No se modifico nada");
					$template->display("error.tpl");
					exit;
				break;
				default:
					$template->assign("succes", "exito_texto");
					//$href = $_SERVER["PHP_SELF"] . "?poid=".$busqueda->getUID();
					//$template->assign("acciones", array( array("href" => $href, "string" => "volver") ) );
					//$template->display("succes_form.tpl"); exit;
				break;
			}
		} else {
			$template->assign("error", "search_select_status");
		}	
		

	}



	//$template->assign ("note","Si no se selecciona ningun tipo de documento no llegará informe, se enviará el link de descarga.");

	$template->assign ("titulo","titulo_nueva_busqueda");
	//$template->assign ("boton","boton_nueva_busqueda");
	$template->assign ("elemento", $busqueda );
	$template->assign ("width", "600px" );
	$template->assign ("comefrom", "aviso" );
	$template->display("form.tpl");
