<?php

	include( "../../../api.php");

	$template = new Plantilla();
	
	$filtro = $usuario->getCompany();
	if (isset($_REQUEST["all"]) && $usuario->esStaff()) {
		$filtro = null;
	}


	$partners = empresaPartner::getEmpresasPartners($filtro);
	$dataPartners = array();

	foreach ($partners as $partner) {
		$dataPartner = array();
		$dataPartner["lineas"] = $partner->getTableInfo();
		$dataPartner["options"] = $partner->getAvailableOptions($usuario, true);
		$dataPartners[] = $dataPartner;

	}
	
	$json = new jsonAGD();
	$json->establecerTipo("data");
	$json->nombreTabla("listar-partners");

	$accionesRapidas = $usuario->getOptionsFastFor("empresaPartner", 0, $usuario->getCompany());
	foreach ($accionesRapidas as $accion) {
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]);
		$options[$accion["uid_accion"]] = $accion;
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"], "box-it");
	}

	if ($usuario->esStaff()) {
		$json->element("options", "button", array('innerHTML' => $template->getString('ver_todos'), 'className' => 'btn searchtoggle pulsar', 'target' => 'all'));
	}

	$json->datos($dataPartners);
	$json->display();

	
?>
