<?php
	if( !isset($_REQUEST["poid"]) ){ exit(); }
	include_once( "../../api.php");

	$template = new Plantilla();

	$agrupador = new agrupador( obtener_uid_seleccionado() );

	if( isset($_REQUEST["send"]) ){
		$update = $agrupador->updateWithRequest(false, false, $usuario);
		switch( $update ){
			case null:
				$template->assign("error", "No se modifico nada");
			break;
			case false:
				$template->assign("error", "Error al intentar modificar");
			break;
			default:

				$template->assign("botones", array(
					array("innerHTML" => $template->getString("volver"), "href" => $_SERVER["PHP_SELF"] . "?poid=" . $agrupador->getUID() )
				));
				$template->display("succes_form.tpl");
				exit;
			break;
		}
	}	


	$template->assign("elemento", $agrupador);
	$template->display("form.tpl");


?>
