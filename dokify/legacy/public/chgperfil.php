<?php
	include("api.php");

	//el usuario que cambia de perfil
	$uidPerfilSeleccionado = db::scape($_REQUEST["pid"]);


	if( is_numeric($uidPerfilSeleccionado) ){
		$perfil = new perfil( $uidPerfilSeleccionado );
		if( ($perfil instanceof perfil) && $perfil->getUser()->getUID() === $usuario->getUID() ){
			$_SESSION["ULTIMO_PERFIL"] = $usuario->idPerfilActivo();
			$usuario->cambiarPerfil( $perfil->getUID() );
			$back = ( isset($_REQUEST["backto"]) ) ? $_REQUEST["backto"] : "";
			$url = "agd/";

			header("Location: $url");
		}
	}


?>
