<?php
	/*MENSAJE DE INICIO DE AGD*/
	include( "../../api.php");

	$template = Plantilla::singleton();

	$contactoSeleccionado = new contactoempresa( obtener_uid_seleccionado() );
	$empresaSeleccionada = $contactoSeleccionado->getCompany();

	if( is_numeric($contactoSeleccionado->getUID()) && isset($_REQUEST["send"]) ){
		$update = $contactoSeleccionado->updateWithRequest(false, false, $usuario);
		switch( $update ){
			case null:
				$template->assign("info", "No se modifico nada" );
			break;
			case false:
				$template->assign("info", "Error al intentar modificar" );
			break;
			default:
				if( isset($_REQUEST["return"]) ){
					header("Location: ". $_REQUEST["return"] ); exit;
				}
				$template->display("succes_form.tpl");
			break;
		}

	}
	
	if (!isset($_REQUEST["return"])) {
		$botonesExtra = array(
			array( "innerHTML" => "Volver a contactos", "className" => "box-it", "style" => "float:left", "href" => "empresa/contacto.php?poid=". $empresaSeleccionada->getUID(), "img" => RESOURCES_DOMAIN . '/img/famfam/arrow_left.png' )
		);
		$template->assign ("botones", $botonesExtra);
	}

	$template->assign ("titulo","titulo_modificar_contacto");
	//$template->assign ("boton","boton_modificar_contacto");
	$template->assign ("elemento",$contactoSeleccionado);
	$template->assign ("data", array( "contacto"=> @$_REQUEST["contacto"] ) );
	$template->display("form.tpl");
	
?>
