<?php

	/* LISTADO DE CAMPOS DINAMICOS*/

	include( "../../api.php");

	//----- INSTANCIAMOS EL OBJETO LOG
	$log = log::singleton();

	//creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	//$template = Plantilla::singleton();
	if (!$usuario->esAdministrador() ){ exit;}
	//----- AÑADIMOS INFORMACION MAS EXACTA AL LOG
	$log->info("configuracion","listado campos", $usuario->getUserVisibleName());

	//--------- COMPROBAMOS ACCESO AL MODULO
	$datosAccesoModulo = $usuario->accesoModulo("campo", 1);
	if( !is_array($datosAccesoModulo) ){ $log->resultado("error acceso modulo", true); exit;}

	//elemento donde almacenaremos todos datos de los campos que se mostraran por pantalla
	$datosCampos = array();

	//array con todos los campos en funcion del tipo de usuario (todos/asignados)
	if( $usuario->esAdministrador() || 1 ){
		$campos = config::obtenerCamposDinamicos();
	} else {
		$campos = $usuario->getCompany()->obtenerCamposDinamicos();
	}

	if( count($campos) ){
		foreach( $campos as $campo ){
			//objeto donde guardaremos los datos de este campo
			$datosCampo = $lineasCampo = array();

			//el nombre
			$nombreCampo = "<span class='ucase'>". $campo->getUserVisibleName() ."</span>";

			$lineasCampo[] = $nombreCampo;
			$lineasCampo[] = elemento::obtenerNombreModulo( $campo->obtenerModuloDestino() );

			//asginamos los datos de la linea
			$datosCampo["lineas"] = array( $lineasCampo );

			$datosCampo["options"] = config::obtenerOpciones( $campo->getUID(), "Campo" /* MODULO */, $usuario, true /* PUBLIC MODE */, 1 /*  MODO CONFIGURACION */ );

			//guardamos el objeto actual al global
			$datosCampos[] = $datosCampo;
		}
	}





	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();

	/*
	$accionesRapidas = config::obtenerOpciones(null, "campo", $usuario, false, 1, 3);
	if( is_array($accionesRapidas) && count($accionesRapidas) ){
		foreach( $accionesRapidas as $accion ){
			$json->acciones( $accion["alias"],	$accion["icono"],	$accion["href"], "box-it");
		}
	}
	*/

	$json->establecerTipo("data");
	$json->nombreTabla("campo");
	$json->datos( $datosCampos );
	$json->display();

?>
