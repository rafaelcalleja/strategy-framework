<?php
	require_once("../api.php");

	$idSeleccionado = obtener_uid_seleccionado();
	if( !is_numeric($idSeleccionado) || !isset($_REQUEST["o"]) ){ exit; }
	
	$log = log::singleton(); //----- INSTANCIAMOS EL OBJETO LOG 
	$template = new Plantilla(); //----- INSTANCIAMOS LA PLANTILLA


	//----- BUSCAMOS NUESTRO ELEMENTO ACTUAL
	$modulo = obtener_modulo_seleccionado();
	$elementoActual = new $modulo( $_REQUEST["o"] );
	$documento = new documento( $idSeleccionado, $elementoActual);

	if( !$usuario->accesoElemento($elementoActual) ){
		if( $usuario->esStaff() ){ $template->display("sin_acceso_perfil.tpl"); } 
		else { die("Inaccesible"); }
	}

	//----- DEFINIMOS EL EVENTO PARA EL LOG
	$log->info($_REQUEST["m"], "enviar documento ".$documento->getUserVisibleName(), $elementoActual->getUserVisibleName() );

	//----- COMPROBAMOS QUE EL MODULO SEA VÁLIDO
	if( !in_array($_REQUEST["m"], util::getAllModules() ) ){
		$log->nivel(5); $log->resultado("modulo no valido", true);
		die("Error: Modulo no especificado!");
	}


	if( isset($_REQUEST["send"]) ){
	
		$anexos = obtener_uids_seleccionados();

		/*
		$solicitantesSeleccionados = $documento->verSolicitantesSeleccionados();	
		*/

		$emails = array();
		if( $destinatario = usuario::instanceFromUsername($_REQUEST["usuario"]) ){	
			$emails[] = $destinatario->obtenerDato("email");
		} elseif( preg_match("#".elemento::getEmailRegExp()."#", $_REQUEST["usuario"]) ){
			$emails[] = $_REQUEST["usuario"];
		}

		if( count($anexos) && count($emails) ){
			/*$plantillaemail = plantillaemail::instanciar("solicitudrevision");
			$plantillaemail->replaced["{%elemento-tipo%}"] = $modulo;
			$plantillaemail->replaced["{%comentario%}"] = isset($_REQUEST["comentario"]) ? db::scape($_REQUEST["comentario"]) : '&nbsp;';
			$plantillaemail->replaced["{%usuario%}"] = $usuario->getUserVisibleName();
			$plantillaemail->replaced["{%link%}"] = $documento->obtenerUrlPublica($usuario);
			$plantillaemail->replaced["{%email%}"] = $usuario->obtenerDato("email");*/

			$mailTemplate = new Plantilla();
			$mailTemplate->assign("link", $documento->obtenerUrlPublica($usuario));
			$mailTemplate->assign("usuario", $usuario);
			$mailTemplate->assign("documento", $documento);
			$mailTemplate->assign("comentario", isset($_REQUEST["comentario"]) ? db::scape($_REQUEST["comentario"]) : false);
			$mailTemplate->assign("adjunto", isset($_REQUEST["attach"]));
			


			if( isset($_REQUEST["replyto"]) ){
				$emails[] = $usuario->obtenerDato("email");
			}

			$email = new email($emails);
			$email->establecerAsunto("Envio de documento");
			

			$size = 0;
			$files = array();


			if( isset($_REQUEST["attach"]) ){
				if( count($anexos) > 1 ){
					$template->assign( "error", $template("adjuntar_solo_uno")  );
				} else {	
					$uid = reset($anexos);
					$anexo = new anexo($uid, $elementoActual);
					$attr = $anexo->obtenerDocumentoAtributo();
					$path = $anexo->getFullPath();
					
					$data = array( 
								"path" => $path,
								"downloadName" => $anexo->getDownloadName(),
								"timeUrl" => '7 days'
						);
					try{
						$publicFile = new publicfile($data, $usuario);
					} catch(Exception $e) {
						$template->assign("error", $e->getMessage() );
						$template->assign("elemento", $elementoActual);
						$template->assign("documento", $documento );
						$template->display( "enviardocumento.tpl" );
						exit;
					}
					$mailTemplate->assign("urlPublicFile", CURRENT_DOMAIN."/publicfile.php?token=".$publicFile->getToken());
					
					$size += archivo::filesize($path);
				}
			}

			$html = $mailTemplate->getHTML("email/enviodocumento.tpl");
			$email->establecerContenido($html);

			if( !$template->get_template_vars("error") ){
				set_time_limit(120);
				if( $email->enviar() ){
					$template->display( "succes_form.tpl" ); exit;
				}
			}
		} else {
			$template->assign( "error", "error_texto"  );
		}
	}

	$template->assign("elemento", $elementoActual);
	$template->assign("documento", $documento );
	$template->display( "enviardocumento.tpl" );
?>
