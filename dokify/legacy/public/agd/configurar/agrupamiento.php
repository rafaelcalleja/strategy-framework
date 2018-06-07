<?php
	/* LISTADO DE USUARIOS*/

	include( "../../api.php");

	if( !$usuario->esStaff() ){ die("Inaccesible"); }
	//creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	//$template = Plantilla::singleton();



	//elemento donde almacenaremos todos los documentos
	$datosAgrupamientos = array();

	$datosPaginacion = preparePagination( 15, config::getCountOf( TABLE_AGRUPAMIENTO ) );


	$agrupamientos = config::obtenerAgrupamientos(false, false, $datosPaginacion["sql_limit_start"], $datosPaginacion["sql_limit_end"]);


	
	foreach( $agrupamientos as $agrupamiento ){
		//objeto donde guardaremos los datos de este agrupamiento
		$datosAgrupamiento = array();

		$uid = $agrupamiento->getUID();



		//buscamos opciones para este elemento
		$opciones = config::obtenerOpciones( $uid, "Agrupamiento" , $usuario, true /* PUBLIC MODE */, 1 /*  MODO CONFIGURACION */ );
		if( count($opciones) ){
			$datosAgrupamiento["options"] = $opciones;
		}		






		if( $agrupamiento instanceof agrupamiento ){
			$empresas = $agrupamiento->getEmpresasClientes();
			
			$datosAgrupamiento["inline"]["clientes"] = array();
			if( is_array($empresas) && count($empresas) ){
				foreach($empresas as $empresa){
					$datosAgrupamiento["inline"]["clientes"][] = array( "nombre" => $empresa->getUserVisibleName() );
				};
			}


			//asginamos los datos de la linea
			$datosAgrupamiento["lineas"] = $agrupamiento->getInfo(true);

			//guardamos el objeto actual al global
			$datosAgrupamientos[] = $datosAgrupamiento;
		}
	}


	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();
	$json->addPagination( $datosPaginacion );
	$json->establecerTipo("data");
	$json->nombreTabla("agrupamiento-config");
	//$jsonObject->acciones( "Crear nuevo usuario",	"useradd",	"configurar/usuario/nuevo.php",	"box-it");
	$json->datos( $datosAgrupamientos );
	$json->display();

?>
