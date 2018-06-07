<?php

include_once (dirname(__FILE__) . '/config.php');
include_once (dirname(__FILE__) . '/auth.php');

register_shutdown_function("manejador_de_fin", $sessionType);

if (false === (isset($usuario) && $usuario instanceof $sessionType)) {
    // La variable $login se define en "auth.php"
    $usuario = $login;
}

$scriptNameExploded = explode("/", $_SERVER["SCRIPT_NAME"]);
if (isset($usuario) && $usuario instanceof usuario && end($scriptNameExploded) === "index.php") {
     // prevent remote user
    if (!isset($_SESSION[SESSION_USUARIO_SIMULADOR])) {
        $usuario->establecerEstadoConexion(1);
    }
}
