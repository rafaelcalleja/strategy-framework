<?php
	/* EDITAR UN USUARIO */
	include( "../../api.php");

	// actualizar datos del usuario actual en base a parametros
	if( isset($_REQUEST["flag"]) ){
		$data = array("flag_".$_REQUEST["flag"] => 0);
		if( $usuario->update( $data, elemento::PUBLIFIELDS_MODE_PREFS, $usuario ) ){
			$usuario->clearItemCache();
		}		 
	}	
?>
