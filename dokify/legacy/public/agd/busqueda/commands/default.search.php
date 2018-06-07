<?php
	/*
		Plantilla de creacion de filtros de busqueda para AGD

			1. Incluimos la api - include_once("../../../api.php");
			2. Variables
				· $list = STRING - Conjunto o valor pasado por la busqueda
				· $value = STRING - Cadena de busqueda en si misma
				· $inparam = parametros de la busqueda
				-------------------
				· $objectType = STRING - Tipo de objeto sobre el que hacemos la busqueda
				· $primaryKey = STRING - Clave primaria del tipo de elemento sobre el que vamos a buscar
				· $inTrash = BOOL - Si buscamos papelera o no
				· $trashReplace = STRING - Comparar siempre con campo papelera
				· $tabla = STRING - Nombre de la tabla
				-------------------
				· $where = array() - Contiene todos los filtros en cada indice aplicable a elementos. [ STRING, STRING ]
				· $docsWhere = array() - Contiene todos los filtros en cada indice aplicable a anexo de documentos. [ STRING, STRING ]
			3. Retornar FALSE significa no extraer ningun resultado de este tipo de elemento
					EJ: Buscar pasaporte:XXXXXXX cancelará la busqueda
			* Para no buscar anexo-elemento debemos marcar ( $buscarModulosDocumentos = array() )
			* Para mostrar la ultima query: ( $showmelastquery = true )
	
	*/
	//include_once("../../../config.php"); // Incluimos la api.php


	// Si un filtro de busqueda es directamente un campo del propio elemento
	if( in_array($in, $info) ){
		$where[] = "( $in LIKE '%$value%') ";

		$docsWhere[] = "( $in LIKE '%$value%') ";
	} else {
		$docsWhere[] = $where[] = "( 0 ) ";
		// Default solo es una variable de control que unicamente se usa en este caso, por temas de los campos dinámicos
		if( $default ){ return false; }
	}


	return true;
?>
