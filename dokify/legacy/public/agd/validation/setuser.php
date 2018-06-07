<?php
	require_once("../../api.php");

	if (!$uid = @$_REQUEST['poid']) die('Inaccesible');
	if (!$m = @$_REQUEST['m']) die('Inaccesible');
	if (!$tab = @$_REQUEST['tab']) die('Inaccesible');
	if (!$user = @$_REQUEST['validator']) die('Inaccesible');


	if ($uid && $m){
		$company = $usuario->getCompany();
		$fileId = new fileId($uid, $m); 
		$validator = new usuario($user);


		if ($company->obtenerValidadores()->contains($validator)) {

			$anexos = $fileId->getAttachments($company, $tab==validation::TYPE_VALIDATION_OTHERS);

			if (isset($anexos) && $anexos) {
				unset($_SESSION["CURRENT_FILEID"]);
				$timeToValiate = time() + 1800; // time + (60 segundos * 30 minutos)
				$anexos->foreachCall("update", array(array("screen_uid_usuario" => $validator->getUID(), "screen_time_seen" => date("Y-m-d H:i:s", $timeToValiate))));

				header("Content-type: application/json");
				print json_encode(array("refresh" => 1, "iface" => "validation", "top" => true));
			}

		}
	} else {
		header('HTTP/1.1 404 Not Found');
	}