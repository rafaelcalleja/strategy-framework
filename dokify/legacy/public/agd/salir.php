<?php
	require_once __DIR__ . '/../config.php';
	require_once DIR_CLASS . '/customSession.class.php';

	// session start
	new CustomSession();


	if (isset($_REQUEST["mode"])){
		if ($_REQUEST["mode"] == "ajax") print time();
		exit;
	}

	unset($_SESSION[SESSION_USUARIO]);
	$location = "/login.php";

	$query = $_GET;
	if (isset($query['manual'])) unset($query["manual"]);

	if (count($query)) {
		$location .=  "?" . http_build_query($query);
	}

	session_destroy();

	if (isset($_REQUEST['manual'])) {
		setcookie("username", 0, time()-3600, '/');
		setcookie("token", 0, time()-3600, '/');
		setcookie("lang", 0, time()-3600, '/');
	}


	header("Location: $location");