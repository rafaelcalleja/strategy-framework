<?php
	include( "../../../api.php");

	$solicitud = new solicitud( obtener_uid_seleccionado() );

	$solicitud->setState( solicitud::ESTADO_RECHAZADA );
?>
