<?php
	include("../api.php");

	
	if( $usuario->isManager() ){

		$tpl = Plantilla::singleton();
		$tpl->assign("empleado", $usuario);

		if( $updated = $usuario->update( array("fecha_conformidad" => date("Y-m-d h:i:s") ), usuario::PUBLIFIELDS_MODE_CONFORMIDAD, $usuario ) ){
			$response = array( 
				"html" => array(
					"#conformidad" => $tpl->getHTML("empleado/confirmation-line.tpl")
				)
			);
		} else {
			$response = array( 
				"html" => array(
					"#conformidad" => "<div class='line-error'>Se ha producido un error. Intentalo mas tarde</div>"
				)
			);
		}

		die( json_encode($response) );
	}	
	
?>
