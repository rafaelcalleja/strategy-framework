<?php
	include( "../../api.php");


	$template = new Plantilla();
	$empresa = new empresa( obtener_uid_seleccionado() );


	if( isset($_REQUEST["send"]) ){
		if( isset($_REQUEST["elementos"]) && is_array($_REQUEST["elementos"]) ){
			$seleccionado = reset($_REQUEST["elementos"]);
			if( ($error=$empresa->applyAssignCopy($seleccionado)) === true ){
				$template->assign("succes", "exito_texto");
			} else {
				$template->assign("error", $error);
			}
		} else {
			$template->assign("info", "selecciona_un_elemento");
		}	
	}

	if( $usuario->accesoElemento($empresa) ){
		$copias = $empresa->getAssignCopies();

		$template->assign("elemento", $empresa );
		$template->assign("elementos", $copias );
		$template->assign("title", "restaurar");
		$template->assign("inputtype", "radio");

		$template->display("simplelist.tpl");
	}
?>
