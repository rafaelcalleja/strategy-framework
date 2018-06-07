<?php
	require_once("../api.php");

	//----- INSTANCIAMOS LA PLANTILLA
	$template = new Plantilla();

	
	$url = urldecode($_REQUEST["url"]);
	$return = urldecode($_REQUEST["return"]);
	if( !$modulo = obtener_modulo_seleccionado() ) die("Inaccesible");
	$anexo = new anexo($_REQUEST["oid"], $modulo);
	$elemento = $anexo->getElement();


	if( $usuario->accesoElemento($elemento) ){
		$template->assign("documento", $anexo->obtenerDocumento() );
		$template->assign("elemento", $elemento);
		$template->assign("url", $url);
		$template->assign("return", $return);

		$template->display( "filepreview.tpl" );
	}


/*
	if( $usuario->accesoElemento($elemento) ){

		if( isset($_REQUEST["o"]) && isset($_REQUEST["ref"]) ){
			$modulo = $_REQUEST["ref"];
			$referencia = new $modulo($_REQUEST["o"]);
			$template->assign("referencia", $referencia);	
		}


		// JOSE - De momento hago un hack para esto. No sabemos si merece la pena reutilizar y hacer un metodo, en cualquier caso no perdemos nada.. 
		if( $elemento instanceof documento_atributo ){
			$params = array( "m" => $referencia->getType(), "o" => $referencia->getUID(), "poid" => $elemento->getDocumentsId() );
			$botones = array();


			if( $op = reset($usuario->getAvailableOptionsForModule($referencia->getType()."_documento", "anular")) ){
				$href = $op["href"] . get_concat_char($op["href"]) . http_build_query($params);
				$botones[] = array("innerHTML" => $template->getString("ir_a") . " " . $template->getString($op["alias"]), "className" => "box-it", "href" => $href);
			}

			$botones[] = array("innerHTML" => "validar" );

			$template->assign("botones", $botones);
			
		}

		$template->assign("elemento", $elemento);	
		$template->assign("url", $url);
		$template->assign("return",$return);

		$template->display( "filepreview.tpl" );
	}
*/
?>
