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

	$buscarModulosDocumentos = array();
	$arrayModulosClase = array("agrupador");
	if( in_array($objectType, $arrayModulosClase) ){
		if( is_numeric($value) ){
			$where[] = "( $objectType.$primaryKey IN (
				SELECT uid_$objectType 
				FROM ". TABLE_AGRUPAMIENTO ."_agrupador
				WHERE uid_agrupamiento = $value
			)) ";
		} else {
			$where[] = "( $objectType.$primaryKey IN (
				SELECT uid_$objectType 
				FROM ". TABLE_AGRUPAMIENTO ."_agrupador
				WHERE uid_agrupamiento IN (
					SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ." WHERE nombre LIKE '%$value%'
				)
			)) ";
		}
	} else {
		return false;
	}

	return true;
?>
