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


	if( isset($searchData) && isset($searchData["required"]) && $tipoBuscado = $searchData["required"]["tipo"] ){
		$moduloAnexo = db::scape(strtolower(str_replace("anexo-", "", $tipoBuscado)));

		if( is_numeric($inparam) ){
			$estadoSeleccionado = ( is_numeric($value) ) ? $value : documento::string2status($value);
			if( is_numeric($estadoSeleccionado) ){
				// PENDIENTES DE ANEXAR
				if( $estadoSeleccionado == documento::ESTADO_PENDIENTE ){
					$filter = "uid_documento_atributo NOT IN (
						SELECT uid_documento_atributo 
						FROM ". PREFIJO_ANEXOS ."{$moduloAnexo} anexo
						WHERE anexo.uid_{$moduloAnexo} = uid_elemento_destino 
						AND anexo.uid_documento_atributo = documento_elemento.uid_documento_atributo 
						AND anexo.uid_agrupador = documento_elemento.uid_agrupador
						AND anexo.uid_empresa_referencia = documento_elemento.uid_empresa_referencia
					)";
				} else {
					$filter = "uid_documento_atributo IN (
						SELECT uid_documento_atributo 
						FROM ". PREFIJO_ANEXOS ."{$moduloAnexo} anexo
						WHERE anexo.uid_{$moduloAnexo} = uid_elemento_destino 
						AND anexo.uid_agrupador = documento_elemento.uid_agrupador 
						AND anexo.uid_empresa_referencia = documento_elemento.uid_empresa_referencia
						AND estado = $estadoSeleccionado 
					)";
				}
			}

			$filter .= " AND ( uid_documento_atributo = $inparam )";
		} else {
			$filter = " ( uid_documento_atributo = $value ) ";
		}

		$where[] = $filter;
	} else {

		if( $objectType == "documento_atributo" && is_numeric($value) ){
			$filter = " ( uid_documento_atributo = $value ) ";
			$docsWhere[] = $where[] = $filter;
		} else {
			return false;
		}
	}


	return true;
?>
