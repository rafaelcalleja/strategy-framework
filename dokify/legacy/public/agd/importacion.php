<?php
	//----- CARGAMOS EL API
	require_once( "../api.php");

	$template = Plantilla::singleton();
	
	// Buscamos la empresa actual
	if( $uid = obtener_uid_seleccionado() ){
		$empresa = new empresa($uid);
	} else {
		die("Inaccesible");
	}

	// Sobre que modulo trabajamos?
	$modulo = obtener_modulo_seleccionado();

	// Reemplazamos la empresa por el "padre" de un agrupador, su agrupamiento
	if( $modulo == "agrupador" ){
		$empresa = new agrupamiento($uid);
	}

	// Mostramos sobre quien se realizará la importacion
	$template->assign("titulo", $empresa->getUserVisibleName() . "<hr />");

	// Intentamos extraer los campos a modo de informacion
	try {
		$campos = call_user_func( array( $modulo ,"publicFields"), elemento::PUBLIFIELDS_MODE_IMPORT, NULL, $usuario );
		$campos = ( $campos instanceof ArrayObject ) ? $campos->getArrayCopy() : $campos;
		$campos = array_keys( $campos );
		//indicar por pantalla el modelo que se necesita
		//$template->assign("campos", $campos);
	} catch(Error $e){ 
		die("No se puede realizar la operacion"); 
	}


	if( isset($_REQUEST["send"]) ){
		if( isset($_SESSION["FILES"]) ){
			$files = unserialize($_SESSION["FILES"]);
			$archivo = $files["archivo"];

			try {
				$info = call_user_func( array($modulo, "importFromFile"), $archivo, $empresa, $usuario, $_POST);
				if ($info){
					$htmlinfo = "<div style='text-align: center'> | ";
					foreach( $info as $field => $value ){
						if( $field == "tmp_table" ){ continue; }
						if( !is_array($value) && trim($value) ){
							$htmlinfo .= "$field: $value | ";
						}
					}
					$htmlinfo .= "</div>";
					

					$template->assign("succes", $htmlinfo );
				}else{
					$template->assign("error", $template->getString("error_import") );
				}
				
			} catch(Exception $e){
				$template->assign("error", "Error: ". $e->getMessage() );
			}
		} else {
			$template->assign("error", $template->getString("error_import") );
		}
	}

	if ($modulo === "usuario" && $usuario->esStaff()) {
		$roles = rol::obtenerRolesGenericos();
		$options = [];

		foreach ($roles as $rol) {
			$options[] = [
				"innerHTML" => $rol->getUserVisibleName(),
				"value" => $rol->getUID()
			];
		}

		$inputs = [];
		$inputs[] = [
			'innerHTML' => 'opt_rol',
			'name' 		=> 'rol',
			'type'		=> 'select',
			'options'	=> $options
		];

		$template->assign("inputs", $inputs);
	}
		
	$template->assign("htmlafter", implode(", ", $campos));
	$template->assign("ocultarComentario", true);
	$template->display( "anexar_descargable.tpl" );
?>
