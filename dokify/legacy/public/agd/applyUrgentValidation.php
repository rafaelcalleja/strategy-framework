<?php
	require_once('../api.php');

	if (!isset($_REQUEST["fileId"])) die("Inaccesible");

	$log = log::singleton();
	$template = Plantilla::singleton();

	$fileId = new fileId($_REQUEST["fileId"], fileId::getModuleOfFileId($_REQUEST["fileId"]));
	if (!$fileId instanceof fileId) die("Inaccesible");
	$anexos = $fileId->getAttachments();
	$partners = new ArrayObjectList();

	foreach ($anexos as $anexo) {
		$partner = $anexo->getPartner();
		if ($partner instanceof empresa) $partners[] = $partner;								
	}


	$partners = $partners->unique();

	if (!count($partners)) {
		$template->assign("message", $template->getString("validator_company_error"));
		$template->display('error.tpl');
		exit;
	}

	if (isset($_REQUEST["send"])) {
		$params = array('fileId' => $fileId->getUID());
		$plantillaemail = plantillaemail::instanciar("validacionurgente");
		try { 
			foreach ($partners as $partner) {
				$partner->sendEmailWithParams('validacion_urgente', 'validacionurgente', $params, array(), $plantillaemail);
			}
		} catch(Exception $e) {
			error_log("Error sending email urgent validation fileId: ".$fileId->getUID());
		}


		$anexos->foreachCall("makeUrgent");
		$anexos->writeLogUI(logui::ACTION_URGENT, "", $usuario);
		$template->display("succes_form.tpl");
		exit;
	}

	if (count($partners)>1) {
		$urgentPartners = array_map(function($partner){
			return $partner->getValidationPrice(true);
			}, $partners->getArrayCopy());
			$template->assign("urgentPrice", array_sum($urgentPartners));
	} else {
		$anexo = reset($anexos);
		$partner = reset($partners);
		$atributo = $anexo->obtenerDocumentoAtributo();
		$language = $anexo->obtenerLanguage();
		$isCustom = ($atributo->getIsCutom()) ? documento_atributo::TEMPLATE_TYPE_CUSTOM : documento_atributo::TEMPLATE_TYPE_GENERAL;
		$AVGValidation = $partner->getAVGTimeValidate(true);
		$AVGValidation = ($AVGValidation == 0) ? false : $AVGValidation;
		$template->assign("AVGValidation", $AVGValidation);
		$template->assign("urgentPrice", $partner->getValidationPrice(true));

		if (($poid = obtener_uid_seleccionado()) && ($module = obtener_modulo_seleccionado())) {
			$element = new $module($poid);
			$documentId = $atributo->getDocumentsId();
			if ($element && $documentId) {
				$template->assign("elementName", $element->getUserVisibleName());
				$documento = new documento($documentId);
				$template->assign("documentName", $documento->getUserVisibleName());
				$template->assign("canSelectItems", $documento->canSelectItems($usuario, $module));
			}
		}
	}
	

	$template->assign("fileId", $fileId->getUID());
	$template->display('applyUrgentValidation.tpl');


?>