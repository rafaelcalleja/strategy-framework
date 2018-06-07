<?php
	require_once("../../../../api.php");

	$empresa = new empresa( obtener_uid_seleccionado() );

	if( !$usuario->esStaff() || !$empresa->exists() ){ die("Inaccesible"); }
	$template = new Plantilla();


	// Cada paso que se tenga que dar
	if( isset($_REQUEST["step"]) && isset($_REQUEST["value"]) ){
		$reporte = new reporte( reset($_REQUEST["value"]) , $empresa);

		$template->assign("step", $_REQUEST["step"]);
		$template->assign("reporte", $reporte );
		$template->display("configurar/reportes.tpl");
		exit;
	}

	// Último paso: Generar xls
	if( isset($_REQUEST["send"]) ){
		set_time_limit(0);
		session_write_close();

		$reporte = new reporte( reset($_REQUEST["value"]) , $empresa);
		$reporte->generate( $_POST, $usuario );
		exit;
	}

	$template->assign("reportes", $usuario->getCompany()->getReportes());


	$json = new jsonAGD();
	$json->establecerTipo("simple");
	$json->nuevoSelector("#main", $template->getHTML("configurar/reportes.tpl"));
	$json->addHelpers( $usuario );
	$json->display();
?>
