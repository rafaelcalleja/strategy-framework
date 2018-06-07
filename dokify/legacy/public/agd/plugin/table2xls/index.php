<?php
	//----- CARGAMOS EL API
	require_once(dirname(__FILE__) . "/../../../api.php");

	//----- INSTANCIAMOS A NUESTRO USUARIO Y AL PLUGIN
	//$usuario = usuario::getCurrent();
	$plugin = new plugin("table2xls");

	$class = @$_REQUEST["table"];
	try {
		// Buscamos todos los agrupadores que podemos ver para hacer
		// un filtro rápido y eficaz
		//
		// necesitamos estas 2 variables para que funcione el include...
			$searchString 	= "tipo:$class";
			$searchExport 	= "uid";
			$uids 			= buscador::export($searchString, $usuario, $searchExport);
			
		//Buscamos un id padre
			$parent 	= (isset($_REQUEST["poid"]) && is_numeric($_REQUEST["poid"])) ? obtener_uid_seleccionado() : $usuario->getCompany()->getUID();
			$selected 	= (isset($_REQUEST["selected"]) && is_array($_REQUEST["selected"])) ? $_REQUEST["selected"] : array();

			if( ($comefrom = obtener_comefrom_seleccionado()) && !is_numeric($comefrom) && is_numeric($parent) ){
				$parent = new $comefrom($parent);
			}

		//Sacamos la url...
		$SQL = call_user_func( array($class, 'getExportSql'), $usuario, $selected, $uids, $parent );
		$headers = method_exists($class, 'addExportSqlHeaders') ? $class::addExportSqlHeaders() : false;
	} catch (Error $e){
		//dump($e);
		exit;
	}

	set_time_limit(0);
	$exportacion = new excel($SQL);
	if ($exportacion->getRows() > 0) {
		$exportacion->Generar("Exportacion.xls", $headers);
	} else {
		$template 	= Plantilla::singleton();
		$string 	= $template('no_resultados');
		die("<script>top.agd.func.open(alert('{$string}'));</script>"); 
	}
	