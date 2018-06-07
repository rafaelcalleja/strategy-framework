<?php

	include dirname(__FILE__) . '/../agd/salir.php';

	/*
	include("../config.php");

	// Inicializar la session
	require_once DIR_CLASS . '/customSession.class.php';
	$session = new CustomSession();


	if( isset($_REQUEST["mode"]) ){
		if( $_REQUEST["mode"] == "ajax" ){
			echo time();
		}
	} else {
		unset($_SESSION[SESSION_USUARIO]);
		unset($_SESSION[SESSION_TYPE]);
		$location = "login.php?".$_SERVER["QUERY_STRING"];

		session_destroy();
		setcookie("usuario", 0, time()-3600, '/');
		setcookie("password", 0, time()-3600, '/');
		//print_r( $_SESSION );
		//session_write_close();

		header("Location: $location");
	}
?>
*/