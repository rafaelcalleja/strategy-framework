<?php
	require_once("../../../api.php");

	//$template = new Plantilla();
	//----- INSTANCIAMOS EL OBJETO LOG
	$log = new log();

	//------ rescatamos datos
	$currentUIDUsuario = obtener_uid_seleccionado();


	//------ instanciamos al usuario seleccionado
	$usuarioSeleccionado = new usuario( $currentUIDUsuario );

	//----- AÑADIMOS INFORMACION MAS EXACTA AL LOG
	$log->info("usuario","listado perfiles",$usuario->getUsername());

	//--------- Comprobamos el acceso global al modulo
	$datosAccesoModulo = $usuario->accesoModulo("perfil", 1);
	if( !is_array($datosAccesoModulo) ){ $log->resultado("error acceso modulo", true); die("Inaccesible"); }

	//------ solicitamos los perfiles
	$coleccionPerfiles = $usuarioSeleccionado->obtenerPerfiles();

	//------ guardamos todos los datos y todos los perfiles
	$datosPerfiles = array();

	//------ para cada perfil, adaptamos la salida
	foreach($coleccionPerfiles as $perfil){

		//------ para cada perfil
		$datosPerfil = array();

		//------ solo tenemos este dato como propio, simulamos una salida de "->getInfo()"
		$datosPerfil["lineas"] = array(array( $perfil->getUserVisibleName() ));

		$opciones = $perfil->getAvailableOptions( $usuario, true, 1);/*MODO CONFIGURACION*/
		if( count($opciones) && ( $usuario->esAdministrador() || $usuario->esSATI() || $usuario->accesoElemento($perfil->getCompany()) ) ){
			$datosPerfil["options"] = $opciones;
		}



		$datosPerfil["inline"] = array(
			"empresa" => array( 
				array( "nombre" => $perfil->getCompany()->getUserVisibleName(), "href" => "configurar/usuario/activarperfil.php?poid={$perfil->getUID()}" )
			)
		);

		if ($rol = $perfil->getActiveRol()) {
			$datosPerfil["inline"]['rol'] = array(
				array('nombre' => $rol->getUserVisibleName())
			);
		}
		
		$datosPerfiles[] = $datosPerfil;
	}


	$json = new jsonAGD();
	$json->establecerTipo("data");

	$accionesRapidas = config::obtenerOpciones(null, "perfil", $usuario, false, 1, 3);
	foreach( $accionesRapidas as $accion ){
		$concat = ( strpos($accion["href"],"?") === false ) ? "?" : "&";
		$href = $accion["href"] . $concat . "poid=". $currentUIDUsuario;
		$json->acciones( $accion["alias"],	$accion["icono"],	$href, "box-it");
	}

	$json->nombreTabla("configurar-perfiles");
	$json->datos( $datosPerfiles );
	$log->resultado("ok", true);
	$json->display();
?>
