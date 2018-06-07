<?php

	include( "../../../api.php");

	$template = new Plantilla();

	if( isset($_REQUEST["send"]) ){
		$REQUEST = $_REQUEST;

		if( !isset($REQUEST["nombre"]) ){ 
			$REQUEST["nombre"] = "DEFINIR NOMBRE";
		}
		
		$nuevaEmpresa = new empresa( $REQUEST, $usuario );
	
		if( $nuevaEmpresa->exists() ){

			$template->assign("acciones", array( 
				array("href" => CURRENT_DOMAIN."/agd/empresa/jump.php?poid=" . $nuevaEmpresa->getUID() , "string" => "cambiar_empresa","class" => "unbox-it")
			));	
			$template->display("succes_form.tpl");
			exit;
		} else {
			$template->assign ("error", $nuevaEmpresa->error);
		}

	}

	$mode = elemento::PUBLIFIELDS_MODE_NEW;
	if (isset($_REQUEST["partner"])){
		$mode = empresa::PUBLIFIELDS_MODE_PARTNER;
	}
	
	$template->assign ("campos", empresa::publicFields($mode,null,$usuario));
	$template->assign ("titulo","titulo_nuevo_cliente");
	$template->display("form.tpl");
	
?>
