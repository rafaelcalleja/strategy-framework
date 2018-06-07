<?php
	include( "../../../api.php");

	if( !$usuario->esStaff() ){ die("Inaccesible"); }
	$template = new Plantilla();

	$mes = date("m",time());
	if( $_GET["type"] == "modal" ){
		$start = date("Y-m", time()) ."-01";
		$end = date("Y-m", time()) ."-31 23:59:59";

		$data = llamada::getChartData($start, $end, @$_GET["m"] );
		
		header("Content-type: application/json");
		print json_encode($data);
		exit;
	}


	// URL de los datos
	$src = $_SERVER["PHP_SELF"];
	if( isset($_GET["m"]) ){
		$src .= "?m=". $_GET["m"];
	}

	$HTML = "<div id='grafico' class='grafico' data='". $src . get_concat_char($src) ."type=modal' style='width: 95%; height: 300px; margin: 0 auto; font-size: 13px;'></div>";
	//$HTML .= "<div id='grafico-3' char-type='BarRenderer' class='grafico' data='". $src . get_concat_char($src) ."type=modal' style='width: 95%; height: 300px; margin: 0 auto; font-size: 13px;'></div>";
	//$HTML .= "<div id='grafico-2' char-type='BarRenderer' class='grafico' data='". $_SERVER["PHP_SELF"] . get_concat_char($_SERVER["PHP_SELF"]) ."&m=ambito-count&type=modal' style='width: 95%; height: 300px; margin: 0 auto; font-size: 13px;'></div>";


	$json = new jsonAGD();
	$json->nombreTabla("estadisticas-llamadas");
	$json->establecerTipo("simple");
	$json->nuevoSelector("#main", $HTML);
	$json->addHelpers( $usuario );
	//$json->loadStyle( RESOURCES_DOMAIN . '/css/jquery/jquery.jqplot.min.css');
	$json->display();
?>
