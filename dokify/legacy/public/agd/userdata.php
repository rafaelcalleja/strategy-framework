<?php
	
	require __DIR__ . '/api.php';

	if( isset($_REQUEST["message"]) && isset($_SERVER["HTTP_X_REQUESTED_WITH"]) ){
		$usuario->markAlertAsReaded($_REQUEST["message"]);
		exit;
	}

	if (isset($_REQUEST["option"])) {
		switch ($_REQUEST["option"]) {
			case 'view':
				$global = @$_REQUEST["checked"];

				if ($usuario->configValue(array('view' => $global))) {
					$response = array('refresh' => 1, 'result' => 1);
					print json_encode($response);
				}
			break;

			case 'viewall':

			break;

			case 'location':
				if (isset($_POST['location'])) {
					if (false === $usuario->setLatLng($_POST['location'])) {
						header($_SERVER['SERVER_PROTOCOL'] . ' 500');
					}
				}
			break;
		}

		exit;
	}


	// dismiss comment tour
	if (isset($_REQUEST['tourDismiss']) && isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
		$usuario->setTour($_REQUEST['tourDismiss']);
		exit;
	}



	if (isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
		// if( isset($_SESSION[SESSION_USUARIO_SIMULADOR]) && $uid = $_SESSION[SESSION_USUARIO_SIMULADOR] ){
		// 	$usuario = new usuario($uid);
		// }

		$simulating 	= isset($_SESSION[SESSION_USUARIO_SIMULADOR]);
		$locale 		= Plantilla::getCurrentLocale();
		$pass 			= isset($_SESSION["password"]) ? $_SESSION["password"] : null;
		session_write_close();

		// set user timezone if changed
		if (isset($_REQUEST['tz']) && is_numeric($_REQUEST['tz'])) {
			if ($usuario->getTimezoneOffset() !== $_REQUEST['tz']) {
				$usuario->setTimezoneOffset($_REQUEST['tz']);
			}
		}

		if (!$simulating) {
			$usuario->setUserAgent($_SERVER['HTTP_USER_AGENT'], Plantilla::getCurrentLocale());
		}

		$company = $usuario->getCompany();
		$output = array();
		$output["maxfile"] = $usuario->maxUploadSize();
		$output["sati"] = (int) $usuario->esStaff();
		$output["live"] = LIVE;
		$output["strings"] = Plantilla::getLanguage($locale);
		$output["p"] = base64_encode($pass);
		$output["locale"] = $locale;
		$output["un"] = uniqid();
		$output["empresa"] = $company->getUID();
		$output["agent"] = (int) $usuario->isAgent();
		$output["gkey"] = GOOGLE_API_KEY;

		$output["user"] = array(
			"name" => $usuario->getHumanName(),
			"username" => $usuario->getUserName()
		);


		if (!$company->needsPay()){
			$output["plugins"] = true;
		}

		ob_start("ob_gzhandler"); print json_encode($output); ob_end_flush();
	}