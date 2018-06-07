<?php
	/* LISTADO DE USUARIOS*/
	include( "../../api.php");

	//creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	//$template = Plantilla::singleton();


	//comprobamos el acceso

	//--------- COMPROBAMOS ACCESO AL MODULO
	$datosAccesoModulo = $usuario->accesoModulo("usuario", 1);
	if( !is_array($datosAccesoModulo) ){ die("Inaccesible");}

	//elemento donde almacenaremos todos los documentos
	$datosUsuarios = array();

	$datosPaginacion = preparePagination( 20, config::getCountOf( TABLE_USUARIO ) );


	$usuarios = config::obtenerArrayUsuarios($datosPaginacion["sql_limit_start"], $datosPaginacion["sql_limit_end"]);


	foreach( $usuarios as $infousuario ){
		//objeto donde guardaremos los datos de este documento
		$datosUsuario = array();

		//añadimos la clase al nombre
		$infousuario["usuario"] = "<span class='ucase'>".$infousuario["usuario"]."</span>";

		//id
		$uid = $infousuario["uid_usuario"];

		//quitamos el uid
		unset($infousuario["uid_usuario"]);

		//buscamos opciones para este elemento
		$opciones = config::obtenerOpciones( $uid, "2" /* MODULO USUARIOS */, $usuario, true /* PUBLIC MODE */, 1 /*  MODO CONFIGURACION */ );
		if( count($opciones) ){
			$datosUsuario["options"] = $opciones;
		}		

		// INFORMACION EN LA MISMA LINEA
		$currentUser = new usuario( $uid );




		if( $currentUser instanceof usuario ){
			//BUSCAMOS LAS ETIQUETAS PARA AÑADIR LOS NOMBRE A LAS SALIDA POR PANTALLA
			$etiquetas = $currentUser->obtenerEtiquetas();

			$datosUsuario["inline"]["perfil"] = array( 
				0 => array( "nombre" => $currentUser->nombrePerfilActivo() )
			);

			if( is_array($etiquetas) && count($etiquetas) ){
				$datosUsuario["inline"]["etiquetas"] = array();
				foreach($etiquetas as $etiqueta){
					$datosUsuario["inline"]["etiquetas"][] = array("nombre" => $etiqueta->getUserVisibleName());
				};
			}

			//asginamos los datos de la linea
			$datosUsuario["lineas"] = array( $infousuario );

			//guardamos el objeto actual al global
			$datosUsuarios[] = $datosUsuario;
		}
	}


	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();
	$json->addPagination( $datosPaginacion );
	$json->establecerTipo("data");
	$json->nombreTabla("usuario-config");
	//$jsonObject->acciones( "Crear nuevo usuario",	"useradd",	"configurar/usuario/nuevo.php",	"box-it");
	$json->datos( $datosUsuarios );
	$json->display();

?>
