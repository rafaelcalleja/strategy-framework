<?php 
	header('Content-type: multipart/x-mixed-replace;boundary="limite01234"');

	//include( "../auth.php");
	ob_start();
	include( "../api.php");
	ob_end_clean();
	$bucle = 0;


	echo "--limite01234\n"; 
	//session_start();
	while( true ){
		echo "Content-type: text/plain\n\n"; 

		$currentTable = ( isset($_SESSION["CURRENT_TABLE"]) ) ? $_SESSION["CURRENT_TABLE"] : false;


		if( $currentTable ){
			$usuario = new usuario( $_SESSION["usuario.uid_usuario"] );

			$db = db::singleton();
			$SQLgetLiveInfo = "SELECT UNIX_TIMESTAMP(`update`) FROM ". TABLE_LIVE ." WHERE `table` = '$currentTable'";
			$tableUpdateTime = $db->query( $SQLgetLiveInfo, 0, 0); 

			$liveData = array();
			$liveData["time"] = time();
			/*
				0 - Desconectado | 1 - Conectado | 2 - Inactivo | 3 - Bloqueado | 4 - 
			*/
			$liveData["userstatus"] = $usuario->verEstadoConexion(true);
			if( !$usuario->compararPassword( $_SESSION["usuario.login_pass"] ) ){
				$liveData["userstatus"] = 5;
			}
			$liveData["tabletime"] = $tableUpdateTime;

			$outputArray = $liveData;
		} else {
			$outputArray = array( time() );
		}

	

		
		echo json_encode( $outputArray );

		session_write_close();
		echo "--limite01234\n";

		//ob_flush();
		flush();
		$bucle++;
		sleep(1);
	} 
	echo "--limite01234--\n"; 
?> 
