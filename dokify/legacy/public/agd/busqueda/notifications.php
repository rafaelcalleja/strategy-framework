<?php
	require __DIR__ . '/../../api.php';

	if (!$uid = obtener_uid_seleccionado()){
		header("HTTP/1.1 404"); exit;
	}

	$search = new buscador($uid);

	if (!$usuario->accesoElemento($search)) die("Inaccesible");


	$template = Plantilla::singleton();
	$json = new jsonAGD();	
	$tablename = 'searchnotification';


	if ($comefrom = obtener_comefrom_seleccionado()) {
		$searchNotification = new SearchNotification($comefrom);
		if (!$search->compareTo($searchNotification->getBusqueda())) die("Innacesible");

		if (isset($_GET['action']) && $action = $_GET['action']) {
			switch ($action) {
				case 'view':
					print $searchNotification->render();
					
					break;
			}

			exit;
		} else {
			$items = $searchNotification->getReceipts();
		}
	} else {
		$items = $search->getEmailNotifications();
	}

	$data = $items->toArrayData($usuario);

	
	$json->establecerTipo("data");
	$json->informacionNavegacion('Inicio', 'Búsquedas guardadas', $search->getUserVisibleName(), $template('aviso_email'));
	$json->menuSeleccionado('busquedas');
	$json->establecerTipo('data');
	$json->nombreTabla($tablename);
	$json->datos($data);
	$json->display();
