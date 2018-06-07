<?php
	/* -----------
		LISTADO DE USUARIOS
	----------- */
	include( "../../api.php");

	//----- INSTANCIAMOS EL OBJETO LOG
	$log = log::singleton();


	//--------- Creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	$template = Plantilla::singleton();
	//--------- Se guardaran los datos de todas las empresas
	$datosUsuarios = array();


	if( !$idEmpresaSeleccionada = obtener_uid_seleccionado() ){ //--------- Empresa que queremos ver
		$idEmpresaSeleccionada = $usuario->getCompany()->getUID();
	}



	//--------- Instanciamos nuestra empresa
	$empresaActual = new empresa( $idEmpresaSeleccionada );

	//----- AÑADIMOS INFORMACION MAS EXACTA AL LOG
	$log->info("empresa","listado usuarios",$empresaActual->getUserVisibleName());

	//--------- COMPROBAMOS ACCESO AL MODULO
	$datosAccesoModulo = $usuario->accesoModulo("usuario");
	if( !is_array($datosAccesoModulo) ){ $log->resultado("error acceso modulo", true); die("Inaccesible");}


	//--------- COMPROBAMOS QUE HAY PERMISO PARA VER LA EMPRESA SELECCIONADA
	//--------- QUEREMOS VER NUESTRA PROPIA EMPRESA O UNA SUBCONTRATA
	if( !$usuario->accesoElemento($empresaActual) && !$usuario->isViewFilterByGroups() ){$log->resultado("error acceso usuarios", true); die("Inaccesible"); }



	$numeroTotalUsuarios = $empresaActual->obtenerUsuarios(false, false, $usuario, true);

	//--------- Datos de la paginacion
	$datosPaginacion = preparePagination( 10, $numeroTotalUsuarios );


	//--------- Buscamos los usuarios
	$coleccionUsuarios = $empresaActual->obtenerUsuarios(  false, array($datosPaginacion["sql_limit_start"],$datosPaginacion["sql_limit_end"]), $usuario);

	
	//--------- Recorremos el listado
	foreach( $coleccionUsuarios as $usuarioEmpresa ){
		//--------- Si efectivamente es un objeto empleado
		if( $usuarioEmpresa instanceof usuario ){
			$uid = $usuarioEmpresa->getUID();
			//--------- array que almacenara los datos de el usuario actual
			$datosUsuario = array();

			//--------- concatenamos los valores en el array general | solicitamos la informacion en modo public "true"
			$informacionUsuario = $usuarioEmpresa->getTableInfo();
			//$informacionUsuario[$uid]["nombre"] = $informacionUsuario[$uid]["nombre"]." ".$informacionUsuario[$uid]["apellidos"];
			//unset($informacionUsuario[$uid]["apellidos"]);


			//--------- hay valores publicos que no se necesitan mostrar en la tabla
			$datosUsuario["lineas"] = $informacionUsuario;
			if( $usuario->esSati() ){
				$datosUsuario["lineas"][ $usuarioEmpresa->getUID() ]["uid"] = $usuarioEmpresa->getUID();
			}

			$datosUsuario["options"] = $usuarioEmpresa->getAvailableOptions( $usuario, true );			
			$datosUsuario["inline"] = $usuarioEmpresa->getInlineArray($usuario, false, array("comefrom" => "empresa") );


			//--------- Guardamos los datos de este usuario en el conjunto global
			$datosUsuarios[] = $datosUsuario;
		}
	}
	//exit;






	/* -------------------------------
	 *
	 * DESDE AQUI NO HAY MAS "NEGOCIO"
	 * DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR 
	 *
	 * -------------------------------
	 */


	$json = new jsonAGD();
	$json->addPagination( $datosPaginacion );
	$json->establecerTipo("data");
	$json->nombreTabla("usuario-". $empresaActual->getUID());


	$accionesRapidas = config::obtenerOpciones(null, "usuario", $usuario, false, 0, 3);
	foreach( $accionesRapidas as $accion ){
		$cncat = get_concat_char($accion["href"]);
		$json->acciones( $accion["alias"],	$accion["icono"], $accion["href"] . $cncat . "poid=". $empresaActual->getUID() , "box-it");
	}

	$accionesLinea = config::obtenerOpciones(null, "usuario", $usuario, false, 0, 2);
	foreach( $accionesLinea as $accion ){
		$cncat = get_concat_char($accion["href"]);
		$class = ( trim($accion["class"]) ) ? $accion["class"] : 'btn';
		$json->element("options", "button", array(
			'innerHTML' => $accion["alias"], 'class' => $class, 'href' => $accion["href"] . $cncat ."m=usuario", "img" => $accion["icono"]) 
		);	
	}


	/*
	$json->acciones( "Ver usuarios eliminados",			"papelera" ,	"papelera.php?m=usuario",		"box-it");
	$json->acciones( "Dar de alta un nuevo usuario",	"useradd" , 	"usuario/nuevo.php", 			"box-it");
	*/

	$json->informacionNavegacion(
		"inicio", 
		$template->getString("empresas"), 
		array( "innerHTML" => string_truncate($empresaActual->getUserVisibleName(),30), "href" => $empresaActual->obtenerUrlPreferida(), "title" => $empresaActual->getUserVisibleName(), "img" => $empresaActual->getStatusImage($usuario) ), 
		$template->getString("usuarios")
	);
	$json->menuSeleccionado( "usuario" );

	//--------- Agregar al objeto los datos y sacar por pantalla
	$json->datos( $datosUsuarios );
	$log->resultado("ok", true);
	$json->display();

?>
