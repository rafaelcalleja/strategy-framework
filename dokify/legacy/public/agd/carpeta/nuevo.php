<?php
	include("../../api.php");
	
	// carpeta activa...
	$modulo = obtener_modulo_seleccionado();

	$elementoPadre = new $modulo( obtener_uid_seleccionado() );


	// template
	$template = new Plantilla();


	if( !is_object($elementoPadre) || !$elementoPadre->getUID() ){
		$template->display("error_string.tpl");
		exit;
	}

	// Si se envia el formulario...
	if( isset($_REQUEST["send"]) || $elementoPadre instanceof IfolderContainer ){
		// Si se envia el formulario de radio...
		if( isset($_REQUEST["radio"]) ){
			switch( $_REQUEST["radio"] ){
				case "folder":
					$template->assign ("campos", carpeta::publicFields("nuevo",$elementoPadre,null,false) );
					$template->display("form.tpl");
				break;
				case "file":
					header("Location: fichero/nuevo.php?poid=".$elementoPadre->getUID());
				break;
			}
			exit;
		} else {
			$carpeta = new carpeta( $_REQUEST, $usuario );
			if( $carpeta instanceof carpeta && $carpeta->getUID() && $carpeta->guardarEn( $elementoPadre, $usuario ) ){
				$href = $_SERVER["PHP_SELF"] . "?poid=". $elementoPadre->getUID()."&m=".$modulo."&radio=folder";
				$carpeta->updateWithRequest();
				$template->assign("acciones", array( array("href" => $href, "string" => "insertar_otro") ) );
				$template->display("succes_form.tpl");
				$carpeta->indexar();
				exit;
			} else {
				$template->assign("error", "error_texto");
			}
		}

	}

	$acciones = array();
		$acciones["file"] = array(
			"innerHTML" => "Nuevo archivo"
		);
		$acciones["folder"] = array(
			"innerHTML" => "Nuevo carpeta"
		);



	$template->assign("checked", "file");
	$template->assign("title", "Nuevo Elemento");
	$template->assign("array", $acciones);
	$template->display("functions/array2radio.tpl");
?>
