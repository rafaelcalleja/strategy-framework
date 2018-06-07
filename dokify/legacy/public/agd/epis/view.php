<?php
	require("../../api.php");

	if( !$usuario->accesoModulo("epi") ){
		die("Inaccesible");
	}

	$json = new jsonAGD();
	$m = obtener_modulo_seleccionado();
	$modulo = ( $m ) ? $m : "empresa";
	$busqueda = null;

	if( $uid = obtener_uid_seleccionado() ){
		$data = new extendedArray();
		$item = new $modulo($uid);
		$total = 0;
		
		$json->informacionNavegacion("inicio");

		$datosBusqueda = new extendedArray();

		if (isset($_REQUEST["q"])) {

			$busqueda = utf8_decode(urldecode( $_REQUEST["q"] ));

			$datosBusqueda = array("query" => $busqueda);
			
		}

		if( $modulo == "empleado" ){
			$uids = array();

			$empresas = $item->obtenerElementosActivables($usuario);
			foreach($empresas as $i => $empresa){
				$uids[] = $empresa->getUID();
				if( $usuario->accesoElemento($empresa) ){
					$filters = isset($busqueda) ? array('alias' => $busqueda) : null;
					$limit = false;
					$total = $empresa->obtenerNumeroEpis(false, false, epi::ESTADO_ALMACEN, false, false, $filters);
					$paginacion = preparePagination(10, $total, 0);
					$epis = $empresa->obtenerEpis(false, array($paginacion["sql_limit_start"], $paginacion["sql_limit_end"]), epi::ESTADO_ALMACEN)->toArrayData($usuario);

					$data[] = array( 
						"id" => "epi-almacen", 
						"group" => "EPIs en el almacen de ". $empresa->getUserVisibleName(), 
						"droppable" => "epis/mover.php?drop=true&confirm=1&send=1", 
						"css" => array("height" => "110px", "overflow" => "auto") ,
						"moveable" => ($i)?true:false,
						"route" => "epis/view.php?m=empresa&poid={$empresa->getUID()}&async=true&estado=". epi::ESTADO_ALMACEN
					);
					$epis = $empresa->obtenerEpis(false, array($paginacion["sql_limit_start"], $paginacion["sql_limit_end"]), epi::ESTADO_ALMACEN)->toArrayData($usuario);
					$data = $data->merge( $epis );
					$json->addPagination($paginacion);
				}
			}

			$uid = reset($uids);
			$filters = isset($busqueda) ? array('alias' => $busqueda) : null;
			$solicitudEpis = $item->obtenerSolicitudEpis($filters);
			$epis = $item->obtenerEpis(null, $usuario, false, false, false, $filters);

			$data[] = array( "group" => "EPIs solicitadas/asignadas a ". $item->getUserVisibleName(),
			 	"id" => "epi-empleado",
				"droppable" => "epis/mover.php?drop=true&confirm=1&send=1&elementos[]={$item->getUID()}", 
				"moveable" => true, 
				"css" => array("height" => "250px")
			);

			if ($solicitudEpis) {
				$data = $data->merge($solicitudEpis->toArrayData($usuario));
			}

			if($epis){
				$data = $data->merge($epis->toArrayData($usuario));
			}

			$total = $total + count($epis) + count($solicitudEpis);
			$paginacion = $total ? preparePagination( $total, $total, 0, true ) : null;	
			$json->addPagination($paginacion);			

		} else {
			$data[] = array( "group" => "EPIs en el almacen de {$item->getUserVisibleName()}" );
			$estado = false;
			if( $usuario->accesoElemento($item) ){
				if( isset($_REQUEST["estado"]) ){
					$estado = $_REQUEST["estado"];
				}
				$limit = false;
				$filters = null;
				if (isset($busqueda)) {
					$filters = array('alias' => $busqueda);
					$total = $item->obtenerNumeroEpis(false, false, $estado, false, false, $filters);				
					$paginacion = $total ? preparePagination( $total, $total, 0, true ) : null;	
				} else {
					$total = $item->obtenerNumeroEpis(false, false, $estado, false, false, $filters);
					$paginacion = preparePagination( 10, $total, 0 );	
					$limit = array($paginacion["sql_limit_start"], $paginacion["sql_limit_end"]);
				}
				$epis = $item->obtenerEpis(false, $limit, $estado, false, false, $filters);
				$data = $data->merge( $epis->toArrayData($usuario) );
				if($item instanceof empresa ){
					$json->addPagination($paginacion);
				}
			}

		}

		// Información de la navegación
		$json->informacionNavegacion(array(
			"innerHTML" => $item->getUserVisibleName(), "href" => $item->obtenerUrlFicha(), "title" => $item->getUserVisibleName(), "img" => $item->getStatusImage($usuario), "className" => "box-it" 
		), "epis");


		

		//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
		

		$accionesRapidas = $usuario->getOptionsFastFor("epi", 0);
		foreach( $accionesRapidas as $accion ){
			$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) . "ref=empresa&poid={$uid}";
			$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"], "box-it");
		}

		$accionesLinea = $usuario->getOptionsMultipleFor("epi", 0);
		foreach( $accionesLinea as $accion ){
			$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m={$modulo}";
			$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
			$json->element("options", "button", $accion);
		}

		$json->busqueda($datosBusqueda);
		$json->menuSeleccionado($modulo);
		$json->establecerTipo("data");
		$json->nombreTabla("epi-$modulo");
		$json->datos( $data );
		$json->display();
	}
?>
