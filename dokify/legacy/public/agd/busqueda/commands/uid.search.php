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


	$modulo = ( $objectType == "tipodocumento" ) ? "documento" : $objectType;

	if( is_array($value) ){
		if( is_numeric(implode('',$value)) ){
			$where[] = "( $modulo.uid_$modulo IN (". implode(',',$value) .") ) ";
		}
	} elseif( is_numeric($value) ){
		$where[] = "( $modulo.uid_$modulo = $value ) ";
	}

	$buscarModulosDocumentos = array(); //cancelar query de documentos

	return true;
?>
