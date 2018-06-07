<?php
	//----- CARGAMOS EL API
	require_once( "../../api.php");

	$log = new log();
	$template = Plantilla::singleton();
	
	// Buscamos la empresa actual
	if( $uid = obtener_uid_seleccionado() ){
		$empresa = new empresa($uid);
	} else {
		die("Inaccesible");
	}

	if( !($usuario->getAvailableOptionsForModule( util::getModuleId("empresa"), 67)) ){
		$log->nivel(6);
		$log->resultado("sin permiso", true);
		$template->display("erroracceso.tpl");
		exit;
	}

	try {
		$campos = signinRequest::publicFields(elemento::PUBLIFIELDS_MODE_IMPORT, NULL, $usuario);
		$campos = ( $campos instanceof ArrayObject ) ? $campos->getArrayCopy() : $campos;
		$campos = array_keys( $campos );
	} catch(Error $e){ 
		die("No se puede realizar la operacion"); 
	}

	$error = false;
	if( isset($_REQUEST["send"]) ){
		if( isset($_SESSION["FILES"]) ){
			$files = unserialize($_SESSION["FILES"]);
			$archivo = $files["archivo"];
			try {
				$timeDeadLine = (isset($_REQUEST["deadline_ok"])) ? DateTime::createFromFormat('d/m/Y', $_REQUEST["deadline_ok"]) : false;
				$now = new Datetime();

				if ($timeDeadLine && $now >= $timeDeadLine) {
					throw new Exception("error_falta_baja");
				} elseif (isset($_REQUEST["deadline_ok"]) && !$_REQUEST["deadline_ok"]) {
					$deadline = NULL;
				} elseif (isset($_REQUEST["deadline_ok"]) && $_REQUEST["deadline_ok"]) {
					$deadline = date("Y-m-d", strtotime(str_replace("/", "-", $_REQUEST["deadline_ok"])));
				}

				$info = signinRequest::importFromFile($archivo, $empresa, $usuario);
				if (isset($info['ok'])) {
					//Updaating deadline_ok
					if (isset($_REQUEST["deadline_ok"]) && count($info["uid_nuevos"])) {
						foreach ($info["uid_nuevos"] as $id) {
							$signIn = new signinRequest($id);
							$signIn->update(array("deadline_ok" => $deadline));
						}
					}

					$message = sprintf($template->getString("has_been_invited"), $info['insertados']);
					$htmlinfo = "<div style='text-align: center'> $message </div>";				
					$template->assign("succes", $htmlinfo );
			
				}else if (isset($info['email'])){
				
					$error = "{$template->getString("import_error_email_repeated")}";
					foreach ($info['email'] as $email) {
						$error .= " $email ";
					}
				
				}else if (isset($info['cif'])){
				
					$error = "{$template->getString("import_error_cif_repeated")}";
					foreach ($info['cif'] as $cif) {
						$error .= " $cif ";
					}
				
				}else{
					$error =  $template->getString("error_import");
				}
				
			} catch(Exception $e){
				$error = $template->getString($e->getMessage());
			}
		} else {
			$error = $template->getString("error_import");
		}
	}

	if ($error) {
		if (isset($files["archivo"])) {
			$template->assign('pass', true);
			$template->assign('file', $files['archivo']);
		}
		$template->assign("error", "Error: ".$error);
	}


	parse_str($_SERVER["QUERY_STRING"], $params);

	$template->assign( "botones", array(
		array("innerHTML" => $template->getString("volver"), "style" => "float:left", "href" => "/agd/empresa/invite.php" . "?" . http_build_query($params)),
		array("innerHTML" => $template->getString("button_send_invitations"), "type" => "submit", "img" => RESOURCES_DOMAIN ."/img/famfam/email_add.png", "className" => "send")
	));

	$template->assign("htmlafter",implode(", ", $campos));
	$template->assign('campos', signinRequest::publicFields(elemento::PUBLIFIELDS_MODE_MASSIVE, null, $usuario, false));
	$template->assign("ocultarComentario", true);
	$template->assign("boton", false);
	$template->assign("customTitle", "title_massive_invitations");
	$template->display("anexar_descargable.tpl");
?>
