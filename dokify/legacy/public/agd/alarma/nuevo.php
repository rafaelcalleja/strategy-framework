<?php
	include( "../../api.php");

	$template = Plantilla::singleton();
	$modulo = obtener_modulo_seleccionado();
	$objActual = new $modulo( obtener_uid_seleccionado() );

	//dump("--->".obtener_modulo_seleccionado()."<--");

	if( isset($_REQUEST["send"]) ){ 
		$data = $_REQUEST;
		
		$data["fecha_alarma"] = date("Y-m-d H:i:s", documento::parseDate($data["fecha_alarma"]));
		$data["uid_usuario"] = $usuario->getUID();
		$alarma = new alarma($data, $usuario);

		if( $alarma->exists() ){
			$alarma->nuevoRelacionado($objActual);
			$template->assign("acciones", array( array("href" => "alarma/alarma.php?poid=".$objActual->getUID()."&m=".$modulo, "string" => "volver") ) );	
			$template->display("succes_form.tpl");
			exit;
		}else{
			$template->assign("error","No se ha podido completar la operación");
		}
	}

	$template->assign ("titulo","titulo_nueva_alarma");
	//$template->assign ("boton", "boton_nueva_alarma");
	$template->assign ("campos", alarma::publicFields(elemento::PUBLIFIELDS_MODE_INIT));
	$template->display("form.tpl");
?>
