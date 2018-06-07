<?php
	
	require_once __DIR__ . '/../../api.php';

	set_time_limit(0);
	ini_set('memory_limit', '512M');

	$lang 	= Plantilla::singleton();
	$query 	= isset($_GET['q']) ? $_GET['q'] : NULL;

	if ($xls = buscador::getRequestSummaryXLS($usuario, $query)) {
		// --- send to browser
		if ($xls->send("resumen")) {
			exit;
		}
	}

	die('<script>alert("'. $lang('no_resultados') .'");</script>');