<?php
	require("../api.php");

	if( $usuario instanceof usuario ){ header("Location: /agd/"); exit; }

	$companies = $usuario->getCompanies();
	$access = false;

	foreach ($companies as $company) {
		if (!$company->needsRedirectToPayment()) {
			$access = true;
			break;
		}
	}

	if (!$access) {
		header("Location: salir.php?loc=sinlicencia&manual=1"); 
		exit;
	}	

	$template = Plantilla::singleton();

	$empresa = $usuario->getCompany();
	if( !$empresa instanceof empresa || !$empresa->exists() ){
		header("Location: salir.php?loc=sinempresa&manual=1");
		exit;
	}

	$modulosDisponibles = $usuario->obtenerElementosMenu();

	$template->assign("modules", $modulosDisponibles );
	// Deberiamos eliminar una de las 2... pero hay que cambiar todas las TPL
	$template->assign("usuario", $usuario);
	$template->assign("user", $usuario);
	$template->assign("version", "0.1-alpha");
	$template->assign("visibleonstart", true);
	$template->assign("route", "empleado/main.tpl");
	$template->assign("currentAPP", "portal_empleado");
	$template->display("index.tpl");
?>
