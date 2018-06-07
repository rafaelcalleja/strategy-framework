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
	$ahora = time();
	$factores = array(
		'Y' => 365*60*60*24,
		'M' => 30*60*60*24,
		'w' => 7*60*60*24,
		'd' => 60*60*24,
		'h' => 60*60,
		'm' => 60,
		's' => 1);
	$regex = array(
		'Y' => '/([0-9]+)Y/',
		'M' => '/([0-9]+)M/',
		'w' => '/([0-9]+)w/',
		'd' => '/([0-9]+)d/',
		'h' => '/([0-9]+)h/',
		'm' => '/([0-9]+)m/',
		's' => '/([0-9]+)s/');
	$match = array();
	$total = 0;
	foreach ($regex as $k=>$v) {
		if (preg_match($v,$value,$match[$k])) {
			$total += $factores[$k]*$match[$k][1];
		} 
	} 
	
	$corte = $ahora - $total;
	
	$buscarModulosDocumentos = array();
	if ( in_array($objectType, array('empleado','maquina','empresa'))){
		$filter = " ( CREATED >= {$corte} ) ";
		$where[] = $filter;
	} else {
		return false;
	}
	return true;
?>