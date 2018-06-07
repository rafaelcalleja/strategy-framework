<?php
	require_once("../../../api.php");

	$template = new Plantilla();

	$rol = new rol( obtener_uid_seleccionado(), false );

	if( !$usuario->accesoElemento($rol) ) { die("Inaccesible"); }
	
	if (isset($_REQUEST["send"])) {

		$arrayDatos = isset($_REQUEST["opciones"]) ? $_REQUEST["opciones"] : array();

		if( $usuario->comprobarAccesoOpcion($arrayDatos) ){
			header("Content-type: text/plain");

			$estadoActualizacion = $rol->actualizarOpciones($arrayDatos);
			if( $estadoActualizacion === true ){
				if( !$rol->actualizarOpcionesExtra() ){ echo "Error al actualizar las opciones extra <br />"; }


				$num = $rol->actualizarPerfilesVinculados(function ($index, $total) {
					$progress = round(($index * 100) / $total, 2);
					customSession::set('progress', $progress);
				});

				if (!is_numeric($num)) {
					echo $template->getString("error_actualizar_perfiles_vinculados") . "<br />";
				}

				customSession::set('progress', "-1");

				die( $template->getString("exito_texto") . "<br /> {$num} pefiles afectados");
			} else {
				die( $template->getString("error_guardar") );
			}		
		}
		
	}	
	
	$template->assign("usuario", $usuario );
	$template->assign("perfil", $rol );

	$optionHTML = $template->getHTML( "editarperfil.tpl" );


	$json = new jsonAGD();
	$json->establecerTipo("options");
	$json->nuevoSelector(".option-title", $template->getString("informacion_perfiles") );
	$json->nuevoSelector(".option-list", $optionHTML);
	$json->display();
?>