<?php

	define('WP_USE_THEMES', false);

	require '../api.php';
	require dirname(__FILE__) . '/../blog/wp-load.php';

	$template = new Plantilla();
	$company = $usuario->getCompany();

	$datosAccesoModulo = $usuario->accesoModulo("home");
	if (is_array($datosAccesoModulo)) {
		$breves = null;

		$latest = (bool) !isset($_GET['old']) && $usuario instanceof usuario;
		$news = $company->getNews($usuario, $latest);


		$template->assign("noticias",  $news);
	}

	$template->assign("isUsuario", $usuario instanceof usuario );
	$template->assign("busquedas", $usuario->obtenerBusquedas("show_on_home=1") );
	$template->assign("inlineParams", array(Ilistable::DATA_COMEFROM => 'home'));
	$template->assign("atributosRelevantes", include('documentorelevante.php') );

	$json = new jsonAGD();

	if( isset($_GET["req"]) && is_numeric($_GET["req"]) ){
		$solicitud = new empresasolicitud($_GET["req"]);
		if ($solicitud->exists() && $solicitud->getState() === solicitud::ESTADO_CREADA) {
			$json->addData("open", $solicitud->getURL());
		}
	}

	$viewName = is_mobile_device() ? 'touch/home.tpl' : 'home.tpl';

	$json->informacionNavegacion( $template->getString("inicio") );
	$json->establecerTipo("simple");
	$json->nuevoSelector("#main", $template->getHTML($viewName));
	$json->menuSeleccionado( "home" );
	$json->display();
