<?php
	/* -----------
		LISTADO DE ELEMENTOS QUE AGRUPAN (PUESTOS, PROYECTOS)
	----------- */
	include( "../../api.php");
	
	//--------- Creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	$template = Plantilla::singleton();

	//--------- Se guardaran los datos de todas las empresas
	$datosListado = array();

	//--------- COMPROBAMOS ACCESO AL MODULO
	$datosAccesoModulo = $usuario->accesoModulo("agrupador");
	if( !is_array($datosAccesoModulo) ){ die("Inaccesible");}

	//--------- POR SI EL USUARIO NO PUEDE VER TODOS LOS DATOS
 	if( $usuario->isViewFilterByGroups() ){
		$agrupadoresUsuarioActivo = $usuario->obtenerAgrupadores();
		$arrayIdAgrupadoresUsuario = $agrupadoresUsuarioActivo->toIntList();
	}

	/********************
		MODO LISTA DE AGRUPADORES ASIGNADOS A OTROS AGRUPADORES
	**/
	if( isset($_GET["mode"]) && $mode = trim($_GET["mode"]) ){
		switch($mode){
			case "assign":
				$referencia = new agrupamiento( obtener_referencia() );
				$agrupador = new agrupador( obtener_uid_seleccionado() );


				$agrupamientos = $agrupador->obtenerAgrupamientos( $usuario );
				foreach( $agrupamientos as $i => $agrupamiento ){
					$numero = $agrupamiento->obtenerAgrupadoresAsignados($agrupador, $usuario, true);
					if( !$numero ){
						unset($agrupamientos[$i]); // si no tiene al menos uno asignado, no nos interesa
					}
				}

				$uidAgrupamientoSeleccionado = ( isset($_GET["oid"]) ) ? $_GET["oid"] : null;
				if( count($agrupamientos) === 1 ){
					$uidAgrupamientoSeleccionado = reset($agrupamientos)->getUID();
				}

				// Se ha seleccionado un agrupamiento
				if( $uidAgrupamientoSeleccionado ){
					$agrupamiento = new agrupamiento( $uidAgrupamientoSeleccionado );
					$agrupadores = $agrupamiento->obtenerAgrupadoresAsignados($agrupador);

					foreach( $agrupadores as $subagrupador ){
						
						$datosElemento = array();

						$datosElemento["lineas"] = $subagrupador->getInfo(true);
						

						$datosElemento["type"] =  $subagrupador->getModuleName();

						$opciones = $subagrupador->getAvailableOptions($usuario, true);
						if( count($opciones) ){ $datosElemento["options"] = $opciones; }	

						$datosListado[] = $datosElemento;
					}
				} else {
					// Se ha seleccionado un agrupador y tenemos que ver que tipos tiene asignados...
					foreach( $agrupamientos as $agrupamiento ){
						if( $agrupamiento->getUID() == $referencia->getUID() ){ exit; }
						$datosElemento = array();
						$datosElemento["lineas"] = $agrupamiento->getInfo(true);

						$datosElemento["type"] =  $agrupamiento->getModuleName();

						$datosListado[] = $datosElemento;
					}
				}

				$json = new jsonAGD();
				$json->datos( $datosListado );
				$json->display();
			break;
		}
		exit;
	}



	/********************
		MODO NORMAL DE LISTA
	**/
	$agrupamientoActual = new agrupamiento( obtener_uid_seleccionado() );


	/** CONTROL DE ACCESO */
	if( !$usuario->accesoElemento($agrupamientoActual) ){ die("Inaccesible"); }	
	/*

	*/

	$numero = $agrupamientoActual->obtenerNumeroAgrupadores($usuario);
	$paginacion = preparePagination( 18, $numero, 0 );


	$coleccionAgrupadores = $agrupamientoActual->obtenerAgrupadores($usuario, false, array(), false, array($paginacion["sql_limit_start"],$paginacion["sql_limit_end"]) );


	foreach( $coleccionAgrupadores as $agrupador ){
		$datosElemento = array();
		$categoria = $agrupador->obtenerAgrupamientoPrimario()->obtenerCategoria();
		if ($usuario instanceof usuario || ($usuario instanceof empleado && (int) $categoria->getUID() !== categoria::TYPE_INTRANET)){
			$opciones = $agrupador->getAvailableOptions($usuario, true);
		}
		
		if( isset($opciones) && count($opciones) ){ $datosElemento["options"] = $opciones; }

		$datosElemento["lineas"] = $agrupador->getTableInfo($usuario, $agrupamientoActual);
		
		if( $usuario->esSATI() ){
			$datosElemento["lineas"][ $agrupador->getUID() ]["uid"] = $agrupador->getUID();
		}

		$inline = $agrupador->getInlineArray($usuario);
		if( count($inline) ){
			$datosElemento["inline"] = $inline;
		}

		if( $class = $agrupador->getLineClass($agrupamientoActual, $usuario) ){
			$datosElemento["lineas"]["className"] = $class;
		}

		$datosElemento["href"] = "#profile.php?m=agrupador&poid={$agrupador->getUID()}";
		if ($categoria) {

			switch($categoria->getUID()){
				case categoria::TYPE_INTRANET:
					if ($usuario instanceof empleado) {
						$datosElemento["href"] = "#carpeta/listado.php?m=agrupador&poid={$agrupador->getUID()}";
					}
				break;
				default:break;
			}
		}
		

		$datosListado[] = $datosElemento;
	}


	/* -------------------------------
	 *
	 * DESDE AQUI NO HAY MAS "NEGOCIO"
	 * DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR 
	 *
	 * -------------------------------
	 */


	$json = new jsonAGD();
	$json->establecerTipo("data");
	$json->addPagination( $paginacion );
	$json->addHelpers( $usuario );
	$json->nombreTabla("agrupador-".$agrupamientoActual->getUID());

 	if( $usuario->isViewFilterByGroups() ){
		//--------- Para mostrar claramente al usuario donde se encuentra
		$json->informacionNavegacion($agrupamientoActual->getUserVisibleName() );
	} else {
		//--------- Para mostrar claramente al usuario donde se encuentra
		$json->informacionNavegacion($template->getString("inicio"), $template->getString("agrupamientos"), $agrupamientoActual->getUserVisibleName() );
	}

	//--------- Acciones
	$accionesRapidas = $usuario->getOptionsFastFor("agrupador", 0, $agrupamientoActual);
	foreach( $accionesRapidas as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) . "poid=".$agrupamientoActual->getUID();
		$class = ( $accion["href"][0] == "#" ) ? "" : "box-it";
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"], $class);
	}


	$accionesLinea = $usuario->getOptionsMultipleFor("agrupador", 0, $agrupamientoActual);

	if ($accionesLinea) {
		foreach( $accionesLinea as $accion ){
			$cncat = get_concat_char($accion["href"]);
			$class = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
			$json->element("options", "button", array(
				'innerHTML' => $accion["innerHTML"], 'class' => $class, 'href' => $accion["href"] . $cncat ."m=agrupador&poid=". $agrupamientoActual->getUID(), "img" => $accion["img"]) 
			);	
		}
	}
	

	$agrupamientosMenu = $usuario->getCompany()->obtenerAgrupamientosVisibles(array("menu"));

	if( $usuario instanceof empleado || (isset($agrupadoresUsuarioActivo) && is_traversable($agrupadoresUsuarioActivo)) || in_array($agrupamientoActual->getUID(), elemento::getCollectionIds($agrupamientosMenu)) ){
		$json->menuSeleccionado( $agrupamientoActual->getUserVisibleName() );
	} else {
		$json->menuSeleccionado( "agrupamiento" );
	}

	//--------- Agregar al objeto los datos y sacar por pantalla

	$json->datos( $datosListado );
	$json->display();

?>
