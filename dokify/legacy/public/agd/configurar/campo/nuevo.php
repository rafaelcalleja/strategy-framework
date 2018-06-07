<?php
	include( "../../../api.php");

	$template = Plantilla::singleton();
	
	if( isset($_REQUEST["send"] ) ){
		$campo = new campo( $_REQUEST );
		if( $campo instanceof campo ){
			$template->display("succes_form.tpl");
			exit;
		} else {
			
			$template->assign("error", "error_insertar_elemento" );
		}
	}

	$template->assign ("campos", campo::publicFields("nuevo") );
	$template->display( "form.tpl");
?>
