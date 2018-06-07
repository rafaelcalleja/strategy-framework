<?php

	require_once __DIR__ . '/class/customSession.class.php';
	$session = new CustomSession();


	if (isset($_SESSION[SESSION_TYPE]) && $sessionType = trim($_SESSION[SESSION_TYPE])) {
		if (isset($_SESSION[SESSION_USUARIO]) && $uid = $_SESSION[SESSION_USUARIO]) {
			require_once __DIR__ . '/class/'. $sessionType .'.class.php';
			$login = new $sessionType($uid);
		}


	// --- if we loose the session, try the cookies
	} elseif ( (isset($_COOKIE["token"]) && isset($_COOKIE["username"])) || (isset($_GET["token"]) && isset($_GET["username"])) ) {
		$username = isset($_REQUEST["username"]) ? $_REQUEST["username"] : $_COOKIE["username"];
		$token = isset($_REQUEST["token"]) ? $_REQUEST["token"] : $_COOKIE["token"];

		// --- try to login
		$login = usuario::instanceFromCookieToken($username , $token);


		// --- if we have a user, create the session
		if ($login instanceof usuario) {
			$sessionType 	= 'usuario';
			$_SESSION[SESSION_TYPE] = get_class($login);
			$_SESSION[SESSION_USUARIO] = $login->getUID();
		}
	}


	// Si no esta establecida, fuera
	if (!isset($sessionType) || !isset($login) || !$login->exists()) {

		if (isset($_REQUEST["type"]) && $_REQUEST["type"] == "ajax") {
			die('{"action":"restore"}');
		}

		if (isset($_REQUEST["type"]) && $_REQUEST["type"] == "modal") {
			die('<script>agd.actionCallback({"action":"restore"});</script>');
		}

		$dir = (!isset($sessionType) || $sessionType == "usuario") ? "agd" : $sessionType;
		$URI = str_replace("//", "/", $_SERVER["REQUEST_URI"]); // prevenir errores de IE

 		// --- redirect to page after login
		$params = "goto=" . urlencode($URI);

		// --- custom params
		if (isset($loginParams)) $params .= get_concat_char($params) . http_build_query($loginParams);


		//if( isset($_REQUEST["type"]) && $_REQUEST["type"] == "modal" ){
		//(function(g){var a=location.href.split("#!")[1];if(a){window.location.hash = "";g.location.pathname = g.HBR = a;}})(window);
		die("<script>l='$params';if(location.hash){l+=encodeURIComponent(location.hash);};location.href='/$dir/salir.php?'+l;</script>");
		//}

		//header("Location: $url");
		exit;
	}

?>
