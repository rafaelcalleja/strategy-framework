<?php
	/* DAR DE ALTA UNA NUEVA NOTICIA */

	include( "../../../api.php");
	$template = new Plantilla();

	if( isset($_REQUEST["send"]) ){
		$m = obtener_modulo_seleccionado();
		try{
			$rolNuevo = rol::crearNuevo($_REQUEST );
			$template->display("succes_form.tpl");
		} catch(Exception $e){
			$template->assign("message", $template->getString($e->getMessage()));
			$template->display("error.tpl");
		}
	}

	$template->assign ("titulo","titulo_nuevo_elemento");
	$template->assign ("boton","crear");
	$template->assign ("campos", rol::publicFields("edit") );
	$template->display("form.tpl");

?>
