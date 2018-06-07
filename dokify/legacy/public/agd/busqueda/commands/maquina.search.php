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

	if( $objectType == "maquina" ){
		if( $singleType == "maquina" ){
			$buscarModulosDocumentos = array();
		} else {
			$buscarModulosDocumentos = array("maquina" => 14); //solo documentos de empleados
			if( is_numeric($value) ){
				$docsWhere[] = "( documento_elemento.uid_elemento_destino IN (
					SELECT uid_maquina FROM agd_data.maquina WHERE uid_maquina = $value
				) ) ";
			} else {
				$docsWhere[] = "( documento_elemento.uid_elemento_destino IN (
					SELECT uid_maquina FROM agd_data.maquina WHERE ( concat(serie,' ',marca_modelo) LIKE '%$value%' )
				)) ";
			}
		}
		return false;
	} else {
		if( $objectType == "empleado" ){
			if( is_numeric($value) ){
					$where[] = "( $objectType.$primaryKey IN (
					SELECT em.uid_empleado 
					FROM agd_data.empleado_maquina em
					WHERE em.uid_maquina = $value
				)) ";
			} else {
				$where[] = "( $objectType.$primaryKey IN (
					SELECT uid_empleado 
					FROM agd_data.empleado_maquina em
					WHERE em.uid_maquina IN (
						SELECT m.uid_maquina FROM agd_data.maquina m WHERE ( nombre LIKE '%$value%' OR serie LIKE '%$value%' )
					)
				)) ";
			}
		} else {
			return false;
		}
	}



	
	return true;
?>
