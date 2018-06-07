<?php
function exception_handler($exception) {
	$error = "Uncaught exception ". get_class($exception) .": {$exception->getMessage()} ";
	$error .= "on file {$exception->getFile()}:{$exception->getLine()}";
	error_log($error);
}

function error_handler($errno, $errstr, $errfile, $errline){
	if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }

	switch ($errno) {
	case E_USER_ERROR:
		$error = " [user] Fatal error in file $errfile:$errline [$errno] $errstr";
		return;
		break;

	case E_USER_WARNING:
		$error = " [warning] Error in file $errfile:$errline [$errno] $errstr";
		break;

	case E_USER_NOTICE:
		$error = "[notice] Error in file $errfile:$errline [$errno] $errstr";
		break;

	default:
		$error = "[unknown] Error in file $errfile:$errline [$errno] $errstr";
		break;
	}

	error_log($error);

	if (isset($_SERVER["PWD"])) {
		return true;
	}

	if( strpos($errstr,"mysql_result") == 0 ){
		return true;
	}

	$message = 'Tipo de error: [<strong>'.$errno.'</strong>] <strong>'.$errstr.'</strong><br />' .
	'En la fila <strong>'.$errline.'</strong> dentro del archivo <strong>'.$errfile.'</strong>';

	$template = Plantilla::singleton();
	$template->assign("message", $message);

	//mostramos la plantilla
	$template->display( "error.tpl");

	/* Don't execute PHP internal error handler */
	return true;
}

//GUARDAR LOGS DE ERROR
function log_error($message, $lenguaje = "php", $user = false, $script=false ){
	error_log($message);
	/*
	$trace = array();
	$traceparts = debug_backtrace();
	if( count($traceparts) ){
		foreach($traceparts as $part){
			$trace[] = str_replace(DIR_ROOT,"",@$part["file"]).":".@$part["line"];
		} 
	}*/
}


set_exception_handler('exception_handler');
set_error_handler("error_handler");
