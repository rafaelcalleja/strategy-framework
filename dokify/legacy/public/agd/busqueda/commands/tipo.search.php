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



	// Filtrar los documentos de un tipo determinado
	/*
	foreach( $modulosDocumentos as $modulo => $id ){
		if( strpos($value, $modulo) !== false && strpos($value, "anexo") !== false ){
			// Esta buscando este tipo
		} else {
			unset($buscarModulosDocumentos[$modulo]);
		}
	}
	*/

	if( !$value || (strtolower($objectType) !== strtolower($value)) ){
		if( strpos($value, "-") !== false ){
			$aux = explode("-",$value); 
			$tipo = reset($aux); 
			$tipodocs = end($aux);

			if( in_array($tipodocs, array_keys($modulosDocumentos)) ){
				$buscarModulosDocumentos = array($tipodocs => util::getModuleId($tipodocs) );
			}
		} else {
			$buscarModulosDocumentos = array();
		}
		return false;
	} else {
		$singleType	= $objectType;
	}
	if( $list ){
		$tableconcat = ( $objectType == "tipodocumento" ) ? "documento" : $objectType; //hack para documentos

		if( is_numeric($list) ){
			$where[] = " ( $tableconcat.$primaryKey = $list ) ";	
		} else {
			if( strpos($list, ",") !== false ){
				$ids = explode(",", $list);
				$ids = array_map("db::scape", $ids);
				$where[] = " ( $tableconcat.$primaryKey IN ( ". implode(",", $ids) ." ) ) ";	
			} else {
				return false;
			}
		}
	}

	return true;
?>
