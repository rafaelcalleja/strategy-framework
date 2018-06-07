<?php

	include( "../../api.php");
	$template = new Plantilla();

	if (isset($_REQUEST["send"])) {
		$datosBusqueda = $_REQUEST;
		$datosBusqueda['uid_usuario'] = $usuario->getUID();
		$datosBusqueda['uid_empresa'] = $usuario->getCompany()->getUID();
		$datosBusqueda['pkey'] = buscador::getRandomKey();

		$busqueda = new buscador($datosBusqueda, $usuario);
		if ($busqueda instanceof buscador && $uid = $busqueda->getUID()) {
			$response = array(
				"closebox" => true,
				"hightlight" => $uid,
				"action" => array(
					"go" => "#busqueda/listado.php?created={$uid}"
				)
			);

			header("Content-type: application/json");
			print json_encode($response);

			exit;
		} else {
			$template->assign("error", "error_texto");
		}
	}

	$fields = buscador::publicFields('crear');
	if( isset($_REQUEST["selected"]) ){
		$filters = array();
		foreach( $_REQUEST["selected"] as $uid ){
			$filters[] = "asignado:$uid";
		}
		$fields['cadena']['value'] = implode("+", $filters);
	} elseif (isset($_REQUEST["q"]) ){
		$fields['cadena']['value'] = htmlspecialchars(base64_decode($_REQUEST['q']));
	}

	$template->assign ("titulo","titulo_nueva_busqueda");
	$template->assign ("boton","boton_nueva_busqueda");
	$template->assign ("campos", $fields );
	$template->assign ("className", "async-form");
	$template->display("form.tpl");
