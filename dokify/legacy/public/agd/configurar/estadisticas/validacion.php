<?php
	include( "../../../api.php");

	if( !$usuario->esStaff() ){ die("Inaccesible"); }
	$template = new Plantilla();

	$mes = date("m",time());
	if( $_GET["type"] == "modal" ){
		$start = date("Y-m", time()) ."-01";
		$end = date("Y-m", time()) ."-31 23:59:59";
		$data = validacion::getChartData($start, $end);
		
		header("Content-type: application/json");
		print json_encode($data);
		exit;
	}


	$HTML = "<div id='grafico' class='grafico' data='". $_SERVER["PHP_SELF"] ."?type=modal' style='width: 95%; height: 300px; margin: 0 auto; font-size: 13px;'></div>";


	$json = new jsonAGD();
	$json->nombreTabla("estadisticas-validacion");
	$json->establecerTipo("simple");
	$json->nuevoSelector("#main", $HTML);
	$json->addHelpers( $usuario );
	$json->loadStyle( RESOURCES_DOMAIN . '/css/jquery.plugins.css');
	$json->display();
?>
