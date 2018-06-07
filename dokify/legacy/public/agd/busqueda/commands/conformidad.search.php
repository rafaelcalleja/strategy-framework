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

	$buscarModulosDocumentos = array(); //cancelar query
	if( $objectType == "empleado" ){
		$restantes = "DATEDIFF(NOW(), fecha_conformidad)";
		if( $value == 1 || $value == "si" ){
			$filter = "( fecha_conformidad AND $restantes >= 0 ) ";
		} else {
			$filter = "( !fecha_conformidad OR $restantes < 0  ) ";
		}

		$filter .= " AND ( es_manager = 1 )";

		$where[] = $filter;
	} else {
		return false;
	}


	return true;
?>
