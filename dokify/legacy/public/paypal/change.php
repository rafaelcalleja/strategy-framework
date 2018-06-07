<?php
	require_once("../api.php");

	$template = Plantilla::singleton();
	$log = new log();

	$modulo = $_REQUEST["m"];
	$elementoSustituto = new $modulo( $_REQUEST["oid"] );

	if( isset($_REQUEST["send"]) && isset($_REQUEST["elementos"]) ){
		$elementoSeleccionado = new $modulo($_REQUEST["elementos"][0]);
		$log->info($modulo,"Reemplazo elemento pagado por ".$elementoSustituto->getUserVisibleName(),$elementoSeleccionado->getUserVisibleName());

		if( count($_REQUEST["elementos"]) == 1 && $elementoSeleccionado instanceof pagable ){
			/*SI ESTA PAGADO Y ESTA EN LA PAPELERA*/
			if( $elementoSeleccionado->pagado($usuario, $papelera=1) ){
				try{
					if( $elementoSeleccionado->reemplazarElementoPagado($elementoSustituto, $usuario) ){
						$log->resultado("ok",true);
						$template->display("succes_form.tpl");exit;						
					}else{
						$log->resultado("error sql",true);
					}
				}catch(Exception $e){
					$template->assign("error",$e->getMessage());
				}
			} else{
				$log->resultado("error no pagado",true);
			}
		}else{
			$log->resultado("error seleccion",true);
			$template->assign("error","error_seleccionar_varios");
		}
	}

	$empresaActual = $usuario->getCompany();

	$elementosEliminados = $empresaActual->elementosPagados($usuario, "maquina", $papelera=1);

	$template->assign("elemento", $empresaActual);
	$template->assign("elementos", $elementosEliminados);
	$template->assign("title", "titulo_cambio_pago_elemento");
	//$template->assign("replace", array("tagName" => "input", "type" => "radio", "name" => "group"));

	$template->display("simplelist.tpl");

?>



