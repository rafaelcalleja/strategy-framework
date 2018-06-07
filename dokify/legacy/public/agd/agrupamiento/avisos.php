<?php
	/* EDITAR UN EMPLEADO */
	include( "../../api.php");

	$agrupador = new agrupador( obtener_uid_seleccionado() );
	if( !$usuario->accesoElemento($agrupador) ){ die("Inaccesible"); }

	$namePlantilla = "avisoagrupadores";
	$modulosDisponibles = array("empresa", "empleado");
	$plantillaemail = plantillaemail::instanciar($namePlantilla);
	$contentDefined = $plantillaemail->getFileContent($usuario->getCompany());
	$template = new Plantilla();

	if( isset($_GET["send"]) ){
		$plantillaemail->replaced["{%elemento-tipo%}"] = $agrupador->getTypeString();

		$plantillaemail->replaced["{elemento-tipo}"] = $agrupador->getTypeString();


		$asunto = utf8_decode("Información trabajos en ") .  $agrupador->getUserVisibleName();
		if( isset($_GET["asunto"]) && $asunto=trim($_GET["asunto"]) ){
			$asunto = utf8_decode($_GET["asunto"]);
		}

		$comentario = @$_REQUEST["comentario"];
		$modulo = @$_REQUEST["modulo"];

		if (!in_array($modulo, $modulosDisponibles)) die("Error!");

		$elementos = $agrupador->obtenerElementosAsignados($modulo);
		set_time_limit(0);
		session_write_close(); // por si tarda demasiado no bloquear...
		$result = true;

		foreach($elementos as $i => $elemento){
			if ($elemento instanceof empresa) {
				$emails = $elemento->obtenerEmailContactos($plantillaemail);
			} else {
				$emails = array($elemento->getEmail());
			}

			if( count($emails) ){
				$plantillaemail->replaced["{%elemento-nombre%}"] =  $agrupador->getUserVisibleName();
				$plantillaemail->replaced["{%empresa-nombre%}"] = $elemento->getUserVisibleName();
				$plantillaemail->replaced["{%comentario%}"] = $comentario;

				$plantillaemail->replaced["{elemento-nombre}"] =  $agrupador->getUserVisibleName();
				$plantillaemail->replaced["{empresa-nombre}"] = $elemento->getUserVisibleName();
				$plantillaemail->replaced["{comentario}"] = $comentario;


				$email = new email( $emails );

				if (!$contentDefined) {
					$HTML = "";
					$HTML .= $comentario;
					$HTML .= '<br />' . $template->getHTML('email/pie.tpl');
					$email->establecerContenido($HTML);
				} else {
					$email->enviardesdePlantilla($plantillaemail, $usuario->getCompany() );
				}
				
				$email->establecerAsunto( $asunto );

				if( ($estado = $email->enviar()) !== true ){
					$template->assign ("error", $estado);
					$result = false;
				}
			}
		}
		// si todo ha ido bien
		if( $result === true ){
			$template->display("succes_form.tpl");
			exit;
		}
	}


	$template->assign ("inputs", array(
		array("name" => "asunto", "innerHTML" => $template("asunto"), "value" => "Información relativa a ". $agrupador->getUserVisibleName() ),
		array("tagName" => "select", "name" => "modulo", "innerHTML" => $template("enviar_email"), "options" => array(
			"empresa" => $template("empresa"),
			"empleado" => $template("empleado")
		))
	));

	$template->assign ("boton", "enviar");
	$template->assign ("plantilla", $namePlantilla);
	$template->assign ("elemento", $agrupador);
	$template->assign ("contentDefined", $contentDefined);
	$template->display("emailmasivo.tpl");

?>
