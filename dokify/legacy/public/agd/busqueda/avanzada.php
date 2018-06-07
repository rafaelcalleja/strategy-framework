<?php
	require_once("../../api.php");


	$template = Plantilla::singleton();

	$empresa = $usuario->getCompany();
	$clientCompanies = $empresa->obtenerEmpresasCliente();


	$agrupamientos = array();

	// --- los agrupamientos de esta empresa
	$agr = $empresa->obtenerAgrupamientosVisibles();
	if ($agr && count($agr)) {
		$agrupamientos[$empresa->getUserVisibleName()] = $agr;
	}


	// --- los agrupamientos de los clientes
	foreach ($clientCompanies as $client) {
		$agr = $client->obtenerAgrupamientosVisibles(array('asignado' => $empresa));

		if ($agr && count($agr)) {
			$agrupamientos[$client->getUserVisibleName()] = $agr;
		}
	}


	$template->assign( "agrupamientos", $agrupamientos );
	$template->display( "busquedaavanzada.tpl" );