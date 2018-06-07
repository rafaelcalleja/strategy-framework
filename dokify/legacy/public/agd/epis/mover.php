<?php
	require("../../api.php");

	if( !$usuario->accesoModulo("epi") ){
		die("Inaccesible");
	}


	if( $uid = obtener_uid_seleccionado() ){
		$template = new Plantilla();
		$epi = new epi($uid);
		$estados = $epi->obtenerEstado(true);
		if( in_array( epi::ESTADO_ALMACEN, $estados) ){
			if( isset($_REQUEST["send"]) && isset($_REQUEST["elementos"]) && is_array($_REQUEST["elementos"]) ){
				$uid = reset($_REQUEST["elementos"]);
				$empleado = new empleado($uid);
				if( $empleado->asignarEpi($epi) ){
					if( isset($_REQUEST["drop"]) ){ 
						$data = array(
							"refresh"=>1, 
							"jGrowl"=> "Se asignó correctamente",
							"open" => "configurar/modificar.php?m=epi&poid={$epi->getUID()}&edit=fecha_entrega"
						);
						die( json_encode($data) );
					}
					$template->display( "succes_form.tpl" );
				} else {
					if( isset($_REQUEST["drop"]) ){ 
						$data = array("jGrowl"=> "error");
						die( json_encode($data) );
					}
					$template->assign("error", "error_texto");
				}
			} else {
				// Este if se validará cuando no se seleccione nada...
				if( isset($_REQUEST["send"]) ){
					$template->assign("error", "selecciona_un_elemento");
				}
				$empleados = $epi->getCompany()->obtenerEmpleados();
				$template->assign( "noSelectAll", 'true' );
				$template->assign("inputtype", "radio");
				$template->assign("title", "asignar");
				$template->assign("elementos", $empleados);
				$template->display("simplelist.tpl"); exit;
			}
		} else {
			if( isset($_REQUEST["send"]) ){
				if( $epi->moverAlmacen() ){
					if( isset($_REQUEST["drop"]) ){ 
						$data = array("refresh"=>1, "jGrowl"=>"Se movió al almacén correctamente");
						die( json_encode($data) );
					}
					$template->display( "succes_form.tpl" );
				} else {
					if( isset($_REQUEST["drop"]) ){ 
						$data = array("jGrowl"=> "error");
						die( json_encode($data) );
					}
					//$template->assign("error", "desc_error_papelera");
					$template->display( "error_string.tpl" );
				}
			} else {
				$template->assign("html", "confirmar_mover_epi");
				$template->display("confirmaraccion.tpl");
				//dump("El epi esta almacen, queremos moverlo a empresa"); exit;
			}
		}



	}

?>
