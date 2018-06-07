<?php

	require_once __DIR__ . '/../../api.php';

	if (!$uid = obtener_uid_seleccionado()) {
		header("HTTP/1.1 404"); exit;
	}

	$busqueda = new buscador($uid);



	if (!$usuario->accesoElemento($busqueda)) {
		die("Inaccesible");
	}

	$template = new Plantilla();



	// --- si se envía el formulario
	if (isset($_POST['send'])) {

		try {
			if(!$asunto = trim(@$_POST["asunto"])) {
				throw new Exception("sin_asunto");
			}

			if(!$comment = trim(@$_POST["comentario"])) {
				throw new Exception("sin_comentario");
			}
			
			if (!$notification = $busqueda->createNotification($usuario, $asunto, $comment, @$_POST["cc"])) {
				throw new Exception('error_texto');
			}

			if (!$notification->createQueue()) {
				throw new Exception('error_texto');
			}

			$response = array(
				"closebox" => true,
				"action" => array(
					"go" => "#busqueda/notifications.php?poid={$busqueda->getUID()}&comefrom={$notification->getUID()}"
				)
			);

			header("Content-type: application/json");
			print json_encode($response);
			exit;

		} catch (Exception $e) {
			$template->assign ("error", $e->getMessage());
		}
	}


	$template->assign ("inputs", array(
		array("name" => "asunto",	"innerHTML" => $template("asunto"), "value" => @$_REQUEST["asunto"]),
		array("name" => "cc", 		"innerHTML" => "CC", 				"value" => @$_REQUEST["cc"], 	"className" => "tag-list")
	));

	$template->assign ("attachments", true);
	$template->assign ("elemento", $busqueda);
	$template->display("emailmasivo.tpl");
