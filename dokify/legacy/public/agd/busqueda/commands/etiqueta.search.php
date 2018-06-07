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

	//value = nombre etiqueta
	//objectType = empresa
	//primaryKey = uid_empresa
	//$tabla=agd_data.empresa

	
	$filterDocs = "uid_documento_atributo IN (
		SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta INNER JOIN ". TABLE_ETIQUETA ." USING(uid_etiqueta)
		WHERE nombre like '%$value%'
	)";
		
	if( !in_array($filterDocs, $docsWhere) ){
		//dump($filterDocs);exit;
		$docsWhere[] = $filterDocs;
	}


	/*cuando sea usuario, documento_atributo -> where*/
	if($objectType == "usuario" || $objectType == "documento_atributo"){
		if($objectType == "usuario"){
			$where[] = " 
					$tabla.$primaryKey IN ( 
						SELECT $primaryKey FROM agd_data.perfil WHERE uid_perfil IN (
							SELECT uid_perfil FROM agd_data.perfil_etiqueta WHERE uid_etiqueta = ( 
								SELECT uid_etiqueta FROM agd_data.etiqueta WHERE nombre like '%$value%'
							)
						) 
					) ";
		} else {
			$where[] = "
						$tabla.$primaryKey IN (
							SELECT uid_documento_atributo FROM agd_docs.documento_atributo WHERE uid_documento_atributo IN (
								SELECT uid_documento_atributo FROM agd_docs.documento_atributo_etiqueta WHERE uid_etiqueta = (
									SELECT uid_etiqueta FROM agd_data.etiqueta WHERE nombre like '%$value%'
								)
							)
						) ";
		}

		//in = etiqueta
		//value = critica
	} else {
		return false;
	}


	/*en este caso no afecta a documentos*/


	return true;
?>
