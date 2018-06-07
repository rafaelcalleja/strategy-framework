<?php
include( "../../../api.php");
$solicitud = new solicitud( obtener_uid_seleccionado() );
// comprobamos que el usuario de la solicitud ha visto el resultado antes de marcarla como eliminada
if ($usuario->compareTo($solicitud->getUser())) {
	$solicitud->setState( solicitud::ESTADO_CANCELADA );
}