<?php
	require_once("../api.php");

	$idSeleccionado = obtener_uid_seleccionado();

	if( !isset($_REQUEST["poid"]) ){ 
		if( isset($_REQUEST["uids"]) && is_array($_REQUEST["uids"]) ){
			$idSeleccionados = $_REQUEST["uids"];
		} else { return false; }
	} else {
		$idSeleccionados = array(0=>$idSeleccionado);
	}

	
	//----- TOMAMOS LA TABLA COMO REFERENCIA
	$modulo = db::scape( $_GET["m"] ); //obtener_modulo_seleccionado();


	if( strpos($modulo,"-") !== false ){
		$modulo = reset( new ArrayObject(explode("-",$modulo) ) );
	}


	foreach($idSeleccionados as $idSeleccionado){
		//----- INSTANCIAMOS EL OBJETO LOG
		$log = new log();

		//----- DEFINIMOS EL EVENTO PARA EL LOG
		$log->info($modulo,"eliminar elemento",$idSeleccionado);

		//----- INSTANCIAMOS LA PLANTILLA
		$template = Plantilla::singleton();

		//----- INSTANCIAMOS EL ELEMENTO	
		$elemento = new $modulo($idSeleccionado, false);

		//----- AÑADIMOS INFORMACION MAS EXACTA AL LOG
		$log->info($modulo,"eliminar elemento",$elemento->getUserVisibleName());

		$config = ( isset($_GET["config"]) && $_GET["config"] == 1 ) ? 1 : 0;
		//----- COMPROBAMOS EL ACCESO
		if( !$usuario->accesoEliminarElemento($elemento, $config) ){
			$log->nivel(6);
			$log->resultado("sin permiso", true);
			if( count($idSeleccionados) == 1 ){
				$template->display("erroracceso.tpl");
				exit;
			} else {
				continue;
			}
		}


		//----- SI SE ENVIA EL FORMULARIO
		if( isset($_REQUEST["send"] ) ){
			if( isset($_GET["confirm"]) ){
				$template->display("confirmacionborrar.tpl");
				exit;
			}

			//----- SI SE HA CONFIRMADO
			if( isset($_GET["confirmed"]) ){

				$estado = $elemento->eliminar($usuario); //$table se especifica en el archivo que incluye este, en cada elemento a eliminar

				if( $estado === true ){
					$elemento->writeLogUI(logui::ACTION_DESTROY, "", $usuario);
					$log->resultado("ok", true);
					if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && @$_REQUEST["type"] != "modal" ){
						$clear = array( 'current' => $idSeleccionados );
						$response = array('clear' => $clear );
						die( json_encode($response) );
					}

					if( count($idSeleccionados) == 1 ){
						if( isset($_GET["return"]) ) {
							$template->assign("acciones", array( array("href" => $_GET["return"], "string" => "volver") ) );								
						}
						$template->display("succes_form.tpl");
						exit;
					}
				} else {
					$log->resultado("error $estado", true);
					if( !$estado ) $estado = "error_desconocido";
					$template->assign("message", $estado );
					$template->display("error.tpl");
					exit;
				}
			}
		}
	}

	$template->assign ("titulo", "borrar_".$modulo);
	$template->assign ("boton", "eliminar" );
	$template->display("borrarelemento.tpl");
?>
