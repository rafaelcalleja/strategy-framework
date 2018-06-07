<?php
	/* DAR DE ALTA UNA NUEVA EMPRESA COMO SUBCONTRATA */

	include( "../../../api.php");

	$template = new Plantilla();
	//$usuarioActivo = new usuario( $_SESSION["usuario.uid_usuario"] );
	//$tipoDocumento = new tipodocumento( $_REQUEST["poid"] );

	if( isset($_REQUEST["send"]) ){
		$tipoDocumento = tipodocumento::crearNuevo( $_REQUEST, $usuario );
		if( $tipoDocumento instanceof tipodocumento ){
			$acciones = array( 
				array("href" => "#configurar/documento.php?poid=" . $tipoDocumento->getUID(), "string" => $template->getString('ir_a_atributos'), "class" => "unbox-it"),
				array("href" => $_SERVER["PHP_SELF"], "string" => "insertar_otro") 
			);
			$template->assign("acciones", $acciones );

			$template->display("succes_form.tpl"); exit;
		} elseif( is_array($tipoDocumento) ) {
			$list = implode(",",elemento::getCollectionIds($tipoDocumento));
			$acciones = array( 
				array("href" => "#buscar.php?p=0&q=tipo:tipodocumento#".$list, "string" => "Mostrar tipos recien insertados", "class" => "unbox-it")
			);
			$template->assign("acciones", $acciones );
			$template->display("succes_form.tpl"); exit;
		} else {
			$template->assign("error", $tipoDocumento);
		}
	}

	$modo = elemento::PUBLIFIELDS_MODE_NEW;
	if( isset($_GET["multiple"]) ){
		$modo = "new-multiple";
	} else {
		$template->assign("botones", array(
			array( 
				"href" => $_SERVER["PHP_SELF"] . "?multiple=true", 
				"innerHTML" => $template->getString('crear_masivamente'), 
				"img" => RESOURCES_DOMAIN . "/img/famfam/arrow_out.png", 
				"name" => "multiple" 
			)
		));
	}

	$template->assign ("titulo","titulo_nuevo_elemento");
	//$template->assign ("boton","crear");
	//$template->assign ("elemento", $agrupamientoSeleccionado);
	$template->assign ("campos", tipodocumento::publicFields($modo, NULL, $usuario));
	$template->display("form.tpl");
	
?>
