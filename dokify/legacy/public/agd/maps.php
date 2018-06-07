<?php
	require __DIR__ . '/../api.php';

	$template = new Plantilla();

	$company = $usuario->getCompany();


	$uid 		= obtener_uid_seleccionado();
	$modulo 	= obtener_modulo_seleccionado();
	$item		= ($uid && $modulo) ? new $modulo($uid) : null;

	if ($item && !$usuario->accesoElemento($item)) die('Inaccesible');



	if (isset($_REQUEST['action']) && $action = $_REQUEST['action']) {
		switch ($action) {
			case 'data':
				header("Content-type: application/json");

				if ($company->isFree()) {
					$out = array(
						'markers' 	=> array(),
						'hash'		=> 'free'
					);

				} else {
					$geoData = $company->getGeoData($usuario, array('item' => $item));
					$out = array(
						'markers' 	=> $geoData->markers,
						'hash' 		=> md5(json_encode($geoData->markers)),
						'activity'	=> sprintf($template('mostrando_n_empleados_mapa'), count($geoData->points))
					);

					if ($item instanceof empleado) $out['activity'] = '';
				}

				print json_encode($out);
				break;
		}

		exit;
	}


	$srcdata = 'maps.php?action=data';
	if ($item) $srcdata .= '&m='. $modulo .'&poid=' . $uid;

	$template->assign('item', $item);
	$template->assign('mapsrc', $srcdata);
	$dataHTML = $template->getHTML('maps.tpl');

	$json = new jsonAGD();

	$json->nombreTabla("maps");
	$json->establecerTipo("simple");
	$json->menuSeleccionado("maps");
	$json->nuevoSelector("#main", $dataHTML);
	$json->informacionNavegacion("inicio", "maps");
	$json->display();
