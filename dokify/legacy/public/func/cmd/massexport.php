<?php

/*
 * parametros: uid_exportacion_masiva, dominio_para_correo (opcional)
 * uso: php massexport.php 5 http://dokify.local
 */
set_time_limit(0);
define('NO_CACHE_OBJECTS',1);
include_once( dirname(__FILE__) . "/../../config.php");
if( isset($_SERVER["argv"]) && isset($_SERVER["argv"]) && $uid = $_SERVER["argv"][1] ){
	$export = new exportacion_masiva($uid);
	if( !$export->exists() ){
		error_log("No hay ninguna exportacion masiva con el uid seleccionado {$uid}.");
	} else {
		if ($exito = $export->generarExportacion()) {
			error_log("Exportación {$export->getUserVisibleName()} generada con éxito.");
		} else {
			error_log("Ocurrió un error al generar la exportacion {$export->getUserVisibleName()}.");
		}
	}
} else {
	error_log('No se indicó uid_exportacion_masiva.');
}
