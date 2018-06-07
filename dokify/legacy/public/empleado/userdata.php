<?php
	include("../api.php");



	if( isset($_SERVER["HTTP_X_REQUESTED_WITH"]) ){
		/*if( isset($_SESSION[SESSION_USUARIO_SIMULADOR]) && is_numeric($_SESSION[SESSION_USUARIO_SIMULADOR]) && $uid = $_SESSION[SESSION_USUARIO_SIMULADOR] ){
			$usuario = new usuario($uid);
		}*/

		if (isset($_REQUEST['tz']) && is_numeric($_REQUEST['tz'])) {
			if ($usuario->getTimezoneOffset() !== $_REQUEST['tz']) {
				$usuario->setTimezoneOffset($_REQUEST['tz']);
			}
		}

		$locale = Plantilla::getCurrentLocale();
		session_write_close();

		$output = array();
		$output["maxfile"] = $usuario->maxUploadSize();
		$output["sati"] = 0;
		$output["live"] = LIVE;
		$output["strings"] = Plantilla::getLanguage($locale);
		//$output["p"] = base64_encode($_SESSION["password"]);
		$output["locale"] = $locale;
		$output["un"] = uniqid();

		$output["routes"] = array(
			"documentos.php" => "../agd/documentos.php",
			"asignacion.php" => "../agd/asignacion.php",
			"agrupamiento/listado.php" => "../agd/agrupamiento/listado.php",
			"carpeta/listado.php" => "../agd/carpeta/listado.php",
			"home.php" => "../agd/home.php",
			"agrupamiento/documento.php" => "../agd/agrupamiento/documento.php",
			"documentocomentario.php" => "../agd/documentocomentario.php",
			"profile.php" => "../agd/profile.php"
		);

		$output["type"] = "employee";
	
		ob_start("ob_gzhandler"); print json_encode($output); ob_end_flush();
	}


	


?>


