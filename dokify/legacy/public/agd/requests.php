<?php
	require_once __DIR__ . '/../api.php';


	if (isset($_REQUEST["type"]) && $_REQUEST["type"] == "modal") {
		die("<script>window.location.reload();</script>");
	}

	// --- document id
	if (!$docid = obtener_uid_seleccionado()) {
		die('Inaccesible');
	}

	// --- item object id
	if (!$uid = @$_REQUEST["o"]) {
		die('Inaccesible');
	}

	// --- item module
	if (!$m = obtener_modulo_seleccionado()) {
		die('Inaccesible');
	}


	$item = new $m($uid);
	$documento = new documento($docid);

	$extra = array();
	$extra[Ilistable::DATA_CONTEXT] = Ilistable::DATA_CONTEXT_TREE;

	$requests = $documento->obtenerSolicitudDocumentos($item, $usuario);
	$data = $requests->toArrayData($usuario, 0, $extra);


	header("Content-type: application/json");
	$json = new jsonAGD();
	$json->establecerTipo("data");
	$json->datos($data);
	$json->display();