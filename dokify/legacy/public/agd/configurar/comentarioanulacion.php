<?php
	/* LISTADO DE ETIQUETAS*/

	include( "../../api.php");

	//creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	//$template = Plantilla::singleton();

	//---- Control de acceso
	$datosAccesoModulo = $usuario->accesoModulo("comentario_anulacion", 1);
	if( !is_array($datosAccesoModulo) ){ die("Inaccesible");}

	//elemento donde almacenaremos todos los documentos
	$datosElementos = array();

	//array con todas las etiquetas
	$comentarios = config::obtenerComentariosAnulacion();


	foreach( $comentarios as $comentario ){
		//objeto donde guardaremos los datos de este documento
		$datosElemento = array();

		//el nombre
		$nombre = "<span class='ucase'>". $comentario->getUserVisibleName() ."</span>";

		//asginamos los datos de la linea
		$datosElemento["lineas"] = array( $comentario->getUID() => array($nombre) );

		$datosElemento["options"] = config::obtenerOpciones( $comentario->getUID(), "comentario_anulacion" /* MODULO */, $usuario, true /* PUBLIC MODE */, 1 /*  MODO CONFIGURACION */ );

		//guardamos el objeto actual al global
		$datosEtiquetas[] = $datosElemento;
	}



	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();

	$accionesRapidas = config::obtenerOpciones(null, "comentario_anulacion"/*EL ID DEL MODULO TIPO_DOCUMENTO*/, $usuario, false, 1, 3);
	if(  is_array($accionesRapidas) && count($accionesRapidas) ){
		foreach( $accionesRapidas as $accion ){
			$json->acciones( $accion["alias"],	$accion["icono"],	$accion["href"], "box-it");
		}
	}

	$json->establecerTipo("data");
	$json->nombreTabla("comentario_anulacion");
	$json->datos( $datosEtiquetas );
	$json->display();

?>
