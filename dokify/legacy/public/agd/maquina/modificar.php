<?php
	/* EDITAR UNA MAQUINA */
	include( "../../api.php");

	$maquinaSeleccionada = new maquina( obtener_uid_seleccionado() );

	if( !($maquinaSeleccionada instanceof maquina) ){ exit; }

	$template = new Plantilla();
	if( isset($_REQUEST["send"]) ){

		$update = $maquinaSeleccionada->updateWithRequest(false, false, $usuario);
		switch( $update ){
			case null:
				$template->assign("error", "No se modifico nada");
				$template->display("error.tpl");
			break;
			case false:
				$template->assign("error", "No se modifico nada");
				$template->display("error.tpl");
			break;
			default:
				$template->display("succes_form.tpl"); exit;
			break;
		}

	}

	$template->assign ("titulo","titulo_modificar_maquina");
	$template->assign ("boton","boton_modificar_maquina");
	$template->assign ("elemento",$maquinaSeleccionada);
	$template->display("form.tpl");	
?>
