<?php

	include( "../api.php");

	$helper = new helper( obtener_comefrom_seleccionado() );
	if( $helper->getUID() ){
		$helper->setComplete($usuario);
	}
?>
