<?php
	require_once("../../api.php");

	$template = new Plantilla();


	if($uid = obtener_uid_seleccionado()) { //--------- Empresa que queremos ver
		$empresa = new empresa($uid);
	} else {
		$empresa = $usuario->getCompany();
	}


	//------- comprobar el acceso a esta empresa
	if (!$usuario->accesoElemento($empresa)) die('Inaccesible');

	// --- formato de filtro
	$filter = array(
		'kind' => array(),
		'src' => array()
	);

	if (($comefrom = obtener_comefrom_seleccionado()) && ($comefrom = elemento::factory($comefrom))) {

		if ($comefrom instanceof agrupador) $filter['assign'] = $comefrom;
	}

	//------- en este caso se piden los eventos de un mes determinado
	if (isset($_GET["eventos"])) {

		
		if (isset($_GET['attached'])) $filter['kind'][] = 'attached';
		if (isset($_GET['expired'])) $filter['kind'][] = 'expired';
		if (isset($_GET['validated'])) $filter['kind'][] = 'validated';
		if (isset($_GET['rejected'])) $filter['kind'][] = 'rejected';

		if (isset($_GET['manual'])) $filter['src'][] = 'manual';
		if (isset($_GET['empresa'])) $filter['src'][] = 'empresa';
		if (isset($_GET['empleado'])) $filter['src'][] = 'empleado';
		if (isset($_GET['maquina'])) $filter['src'][] = 'maquina';

		$user = $usuario->asDomainEntity();
		$timezone = $user->timezone();
		$eventos = $empresa->obtenerEventos($usuario, @$_GET["start"], @$_GET["end"], $filter, $timezone);

		foreach ($eventos as &$event) {
			if (($num = count($event['items'])) > 1) {
				$event['title'] .= " ({$num})";
			}
		}
		
		header("Content-type: application/json");
		die (json_encode($eventos));
		exit;
	}

	// --- calendar feed
	$query = $_GET;
	$query['eventos'] = '1';
	$query['poid'] = $empresa->getUID();

	//$srcAnexados = "empresa/calendario.php?eventos=1&type=fecha_anexion&poid=". $empresaActual->getUID();
	//$srcExpirados = "empresa/calendario.php?eventos=1&type=fecha_expiracion&poid=". $empresaActual->getUID();
	$srcAll = "empresa/calendario.php?". http_build_query($query);

	//-------- se muestra la pagina con el calendario
	// $dataHTML = "";

	$kinds = $empresa->obtenerAgrupamientosAsignados($usuario, false, [categoria::TYPE_TIPOMAQUINARIA, categoria::TYPE_INTRANET, categoria::TYPE_TIPOEMPRESA], true);
	$groups = $empresa->obtenerAgrupadores(NULL, $usuario, $kinds);

	$template->assign('groups', $groups);
	$template->assign('company', $empresa);
	$template->assign('src', $srcAll);
	$template->assign('comefrom', $comefrom);
	$dataHTML = $template->getHTML('calendar.tpl');

	$json = new jsonAGD();
	$json->nombreTabla("calendario-emrpesa");
	$json->establecerTipo("simple");
	$json->menuSeleccionado( "calendar" );
	$json->nuevoSelector("#main", $dataHTML);
	$json->informacionNavegacion("inicio", $template->getString("opt_eventos"), array( 
		"innerHTML" => $empresa->getUserVisibleName(), "href" => $empresa->obtenerUrlPreferida() 
	) );
	$json->display();
?>
