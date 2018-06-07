<?php
	/*MENSAJE DE INICIO DE AGD*/
	include( "../../api.php");


	if( $uid = obtener_uid_seleccionado() ){
		$empleado = new empleado($uid);
		if( $usuario->accesoElemento($empleado) ){
			
			$empresas = $empleado->getCompanies($usuario)->getArrayCopy();
			if( count($empresas) > 1 ){
				$empresa = $empleado->obtenerEmpresaContexto($usuario);
			} else {
				$empresa = reset($empresas);
			}

			session_write_close();
			citamedica::export($usuario, $empresa);
		}
	}

?>
