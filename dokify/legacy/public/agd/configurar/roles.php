<?php
	/* LISTADO DE CLIENTES*/

	include( "../../api.php");

	$template = Plantilla::singleton();

	if (!$usuario->esAdministrador()) {
		exit; //si accede a roles genericos y no tiene permisos
	}

	$datosRoles = array();
	$tiposRol = rol::obtenerTipos();
	foreach( $tiposRol as $uidtipo => $name ){

		$roles = rol::obtenerRolesGenericos(false,$uidtipo, null);

		if( $uidtipo && $roles && count($roles) ){
			$datosRoles[] = array( 
				"group" => $template->getString("rol_tipo_$uidtipo")
			);
		}

		foreach( $roles as $rol ){
		
			$datosRol = array();
			//asginamos los datos de la linea
			$datosRol["lineas"] = $rol->getInfo(true);
			$datosRol["inline"] =  $rol->getInlineArray($usuario, true);
			$datosRol["options"]  = $rol->getAvailableOptions( $usuario, true ,1);

			//guardamos el objeto actual al global
			$datosRoles[] = $datosRol;
		}
	}

	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();

	$accionesRapidas = config::obtenerOpciones(null, "rol", $usuario, false, 1, 3);
	if( is_array($accionesRapidas) && count($accionesRapidas) ){
		foreach( $accionesRapidas as $accion ){
			$accion["href"] = $accion["href"] . get_concat_char($accion["href"]);

			$json->acciones( $accion["alias"],	$accion["icono"],$accion["href"], "box-it");
		}
	}

	$json->establecerTipo("data");
	$json->nombreTabla("rol-");//concatenar con el ui cliente
	$json->datos( $datosRoles );
	$json->display();

?>
