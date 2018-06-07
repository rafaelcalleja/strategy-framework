<?php
	include( "../../../api.php");

	if( !isset($_REQUEST["t"]) ){ exit; }


	$tipo = $_REQUEST["t"];
	$value = ( isset($_REQUEST["v"]) ) ? $_REQUEST["v"] : "";

	$solicitud = solicitud::crearNueva( $tipo, $usuario, $value);

	if( $solicitud instanceof solicitud ){
		echo $solicitud->getUID();
	} else {
		echo 0;
	}

?>
