<?php

	require __DIR__ . '/api.php';
	

	// --- Con esto podemos registrar errores que ocurren del lado del cliente
	$errorLine = date("Y-m-d H:i:s") . " js/error host:{$_SERVER["HTTP_HOST"]} hash:". @$_REQUEST["hash"]. " file:". @$_REQUEST["script"]. " error:". @$_REQUEST["error"] ." \n";

	// file_put_contents(ERROR_LOG_FILE, $errorLine, FILE_APPEND);