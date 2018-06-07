<?php
	/*
		Plantilla de creacion de filtros de busqueda para AGD

			1. Incluimos la api - include_once("../../../api.php");
			2. Variables
				· $value = STRING - Cadena de busqueda en si misma
				· $objectType = STRING - Tipo de objeto sobre el que hacemos la busqueda
				· $primaryKey = STRING - Clave primaria del tipo de elemento sobre el que vamos a buscar
				· $inparam = parametros de la busqueda
				· $where = array() - Contiene todos los filtros en cada indice aplicable a elementos. [ STRING, STRING ]
				· $docsWhere = array() - Contiene todos los filtros en cada indice aplicable a anexo de documentos. [ STRING, STRING ]
			3. Retornar FALSE significa no extraer ningun resultado de este tipo de elemento
					EJ: Buscar pasaporte:XXXXXXX cancelará la busqueda
	
	*/
	//include_once("../../../config.php"); // Incluimos la api.php
	/*
	$buscarModulosDocumentos = array(); //cancelar query
	if( $objectType == "agrupador" ){
		$buscarUsuarios = $arrayBusquedas[TABLE_USUARIO]; unset($buscarUsuarios["limite"]); unset($buscarUsuarios["tipo"]);
		$whereUsuarios = prepareLike( $buscarUsuarios, $value);

		$filter = "( uid_manager IN (
			SELECT uid_usuario FROM ". TABLE_USUARIO ." WHERE (". implode(" OR ", $whereUsuarios) .") 
		) ) ";

		$where[] = $filter;
	} elseif( $objectType == "empleado" ){
		$val = ( $value == 1 || $value == "si" )  ? 1 : 0;
		$filter = "( es_manager = $val ) ";

		$where[] = $filter;

	} else {
		return false;
	}


	return true;
	*/	
	return false;
?>
