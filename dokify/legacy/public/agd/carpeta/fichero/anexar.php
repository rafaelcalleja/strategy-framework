<?php
	include("../../../api.php");


	$modulo = obtener_modulo_seleccionado();

	$modulo = ( $modulo ) ? $modulo : "fichero";

	// carpeta activa en la que vamos a crear el fichero...
	$objeto = new $modulo( obtener_uid_seleccionado() );

	// template
	$template = Plantilla::singleton();


	// Si se envia el archivo
	if( isset($_REQUEST["send"]) ){
		if( isset($_SESSION["FILES"]) ){

			if( $objeto instanceof carpeta ){
				$fichero = new fichero( $_REQUEST ,$usuario);
				if( !($fichero instanceof fichero && $fichero->getUID() && $fichero->guardarEn($objeto)) ){
					$template->assign("error", $estado );
				}
			} else {
				$fichero = $objeto;
			}

			if( !$template->get_template_vars("error") ){
				$files = unserialize($_SESSION["FILES"]);
				$estado = $fichero->anexar( $files["archivo"], true );
				if( $estado === true){

					if( isset($_REQUEST["alarma"]) && trim(@$_REQUEST["fecha_caducidad"]) ){
						$data = $_REQUEST;	

						$data["nombre"] = $template->getString("caducidad");
						$data["comentario"] = $template->getString("alarma_automatica_caducidad");
						$data["fecha_alarma"] = date("Y-m-d H:i:s", documento::parseDate($data["fecha_caducidad"]));
						$data["uid_usuario"] = $usuario->getUID();

						$alarma = new alarma($data, $usuario);
						if( $alarma->exists() ){
							$alarma->nuevoRelacionado( $fichero );
						} else {
							$template->assign("error", "error_crear_alarma" );
						}
					}

					//$log->resultado("ok", true);
					$template->display( "succes_form.tpl" );
					exit;
				} else {
					//$log->resultado("error $estado", true);
					$template->assign("error", $estado );
				}
			}
		} else {
			//$log->resultado("upload error", true);
			$template->assign("error", "error" );
		}
	}

	// Campos extra..
	$inputs = array();
	
	$inputs[] =  array("innerHTML" => "crear_alamara_en_caducidad", "type" => "checkbox", "name" => "alarma", "checked" => true );

	// Al anexar desde carpeta permitimos seleccionar el nombre del fichero directamente
	if( $objeto instanceof carpeta ){
		$inputs[] = array("innerHTML" => "nombre", "name" => "nombre", "blank" => false );
	}

	$template->assign( "fechas", array(
		"fecha_caducidad"
	));

	$template->assign( "inputs", $inputs);

	
	$template->display( "anexar_descargable.tpl" );
?>
