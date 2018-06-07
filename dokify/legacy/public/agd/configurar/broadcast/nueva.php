<?php
	/* DAR DE ALTA UNA NUEVA NOTICIA */

	include( "../../../api.php");
	$template = new Plantilla();

	$usuarioActivo = new usuario( $_SESSION["usuario.uid_usuario"] );
	$clienteReferencia = $usuarioActivo->getCompany();


	if( isset($_REQUEST["send"]) ){
		$noticiaNueva = noticia::crearNueva( $clienteReferencia, $_REQUEST );

		$acciones = array( 
			array( 	"href" => "#configurar/noticia/modificar.php?poid=".$noticiaNueva->getUID(), 
						"string" => "editar_noticia", 
						"class" => "unbox-it" 
			)
		);
		$template->assign ("acciones", $acciones);
		$template->display("succes_form.tpl");

	} else {
		$template->assign ("titulo","titulo_nuevo_elemento");
		$template->assign ("boton","crear");
		//$template->assign ("elemento", $agrupamientoSeleccionado);
		$template->assign ("campos", noticia::publicFields("edit") );
		$template->display("form.tpl");
	}
?>
