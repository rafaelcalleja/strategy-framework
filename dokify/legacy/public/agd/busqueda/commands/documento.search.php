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

	
			
	$table = constant("TABLE_". strtoupper($in));
	if( $objectType == "documento_atributo" ){
		if( is_numeric($value) ){
			$where[] = $docsWhere[] = " ( uid_documento = $value ) ";
		} else {
			$where[] = $docsWhere[] = " ( uid_documento IN (
				SELECT d.uid_documento FROM ". TABLE_DOCUMENTO ." d WHERE nombre LIKE '%$value%'
			) ) ";
		}
		//$buscarModulosDocumentos = array(); //cancelar query de documentos
	} elseif( $objectType == "tipodocumento" ){
		$where[] =" ( nombre LIKE '%$value%' ) ";
	} else {
		//return false;
	}

	return true;
?>
