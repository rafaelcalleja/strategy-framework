<?php
	include( "../../../api.php");

	$template = Plantilla::singleton();
	
	if( isset($_REQUEST["send"]) ){
		$data = $_REQUEST;

		$tipoEPI = new tipo_epi( $data, $usuario );
		if( $tipoEPI instanceof tipo_epi && $tipoEPI->getUID() ){
			$template->display("succes_form.tpl");
			exit;
		} else {
			$template->assign("error", $estado );
		}
	}

	$template->assign ("campos", tipo_epi::publicFields(elemento::PUBLIFIELDS_MODE_INIT, null, $usuario) );
	$template->display( "form.tpl");