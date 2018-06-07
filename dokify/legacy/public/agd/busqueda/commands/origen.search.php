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

	if( is_array($value) ) $value = reset($value);
	
	if( $objectType == "documento_atributo" ){
		if( $inparam == "empresa" || $inparam == "agrupador" ){
			$table = constant("TABLE_". strtoupper($inparam));
			$moduloorigen = util::getModuleId($inparam);

			if( is_numeric($value) ){
				$filter = " ( uid_elemento_origen = $value AND uid_modulo_origen = $moduloorigen ) ";
			} else {
				$filter = " ( uid_modulo_origen = $moduloorigen AND uid_elemento_origen IN (
					SELECT $inparam.uid_"."$inparam FROM $table WHERE nombre LIKE '%$value%'
				) ) ";
			}


			$where[] = $docsWhere[] = $filter;
		} else {
			if( is_numeric($value) ){
				$filter = " ( uid_modulo_origen = $value ) ";
			} else {
				$uidmodulo = util::getModuleId($value);
				if( is_numeric($uidmodulo) ){
					$filter = " ( uid_modulo_origen = ". util::getModuleId($value) ." ) ";
				} else {
					$filter = "( 0 )";
				}
			}

			$docsWhere[] = $where[] = $filter;
		}
	} else {
		return false;
	}


	return true;
?>
