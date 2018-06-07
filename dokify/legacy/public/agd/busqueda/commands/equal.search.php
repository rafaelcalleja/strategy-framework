<?php
	/*
		Plantilla de creacion de filtros de busqueda para AGD

			1. Incluimos la api - include_once("../../../api.php");
			2. Variables
				· $value = STRING - Cadena de busqueda en si misma
				· $inparam = parametros de la busqueda
				· $where = array() - Contiene todos los filtros en cada indice aplicable a elementos. [ STRING, STRING ]
				· $docsWhere = array() - Contiene todos los filtros en cada indice aplicable a anexo de documentos. [ STRING, STRING ]
	
	*/
	//include_once("../../../config.php"); // Incluimos la api.php


	// Aplicamos todos los strings normales en modo estricto con el tercer parámetro
	$where[] = "( ". implode(" OR ", prepareLike( $info, $value, false )) .") ";

	// Limitar resultados de documentos [ IN ARRAY POR QUE RECORREMOS CADA TIPO DE OBJETO, PERO ES MAS COMO PONERLO AQUI PARA NO REPETIR EL SWITCH ]
	$docsWhere[] = " ( alias like '$value' ) ";

	return true;
?>
