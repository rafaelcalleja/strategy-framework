<?php
	include("../../api.php");

	
	if( ($uid = obtener_uid_seleccionado()) && is_numeric($uid) && $usuario->esStaff() ){
		$empleado = new empleado($uid);

		if( $empleado->exists() ){
			$_SESSION[SESSION_USUARIO_SIMULADOR] = $usuario->getUID();
			$_SESSION[SESSION_USUARIO] = $empleado->getUID();
			$_SESSION[SESSION_TYPE] = 'empleado';

			session_write_close();
			header("Location: /empleado");
		} else {
			die("Error");
		}
	}
?>
