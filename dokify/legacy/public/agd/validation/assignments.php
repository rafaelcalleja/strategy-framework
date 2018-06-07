<?php
	require __DIR__ . '/../../api.php';

	if (isset($_REQUEST["agrupador"]) && is_numeric($_REQUEST["agrupador"]) ){
		$agrupador = new agrupador($_REQUEST["agrupador"]);
	} else {
		die("innacesible");
	}

	$m = obtener_modulo_seleccionado();
	$uid = obtener_uid_seleccionado();
	$filter = false;

	if ($m && is_numeric($uid)) {
		$filter = new $m($uid);
	}

	$modulo = (isset($_REQUEST["modulo"])) ? $_REQUEST["modulo"] : die("innacesible");

	$elementos = $agrupador->obtenerElementosAsignados($modulo, $filter);
	$template = Plantilla::singleton();
	$template->assign('elements', $elementos);
	$template->assign('uniqid', uniqid());
	$template->assign('module', $modulo);
	$template->display('elementosAsignados.tpl');