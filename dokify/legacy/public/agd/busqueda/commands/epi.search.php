<?php
	/*
		Plantilla de creacion de filtros de busqueda para AGD

			
			1. Variables
				· $value = STRING - Cadena de busqueda en si misma
				· $objectType = STRING - Tipo de objeto sobre el que hacemos la busqueda
				· $primaryKey = STRING - Clave primaria del tipo de elemento sobre el que vamos a buscar
				· $inparam = parametros de la busqueda
				· $where = array() - Contiene todos los filtros en cada indice aplicable a elementos. [ STRING, STRING ]
				· $docsWhere = array() - Contiene todos los filtros en cada indice aplicable a anexo de documentos. [ STRING, STRING ]
			2. Retornar FALSE significa no extraer ningun resultado de este tipo de elemento
					EJ: Buscar pasaporte:XXXXXXX cancelará la busqueda
	
	*/


	if( $objectType == "epi" ){
		$filter = " ( uid_tipo_epi IN ( SELECT uid_tipo_epi FROM ". TABLE_TIPO_EPI ." WHERE descripcion LIKE '%". db::scape($value) ."%' ) )";
		$where[] = $filter;
		$docsWhere[] = "(0)";
	} else {
		return false;
	}


	return true;
?>
