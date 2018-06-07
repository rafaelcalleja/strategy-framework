<?php

	include( "../../../api.php");

	$template = new Plantilla();
	if (isset($_REQUEST["send"])) {
		$REQUEST = $_REQUEST;
		
		$company = $usuario->getCompany();
		$partner = new empresa($REQUEST["uid_partner"]);
		$languages = $partner->getLanguages();

		$filters = array('language' => $REQUEST["language"]);
		$alreadyPartner = empresaPartner::getEmpresasPartners($company, null, $filters);
		if (count($alreadyPartner) && is_traversable($alreadyPartner)) {
			$template->assign ("error", "La empresa ya tiene un partner para ese idiomas");
		} else {
			if (in_array($REQUEST["language"], $languages)) {
				$newCompanyPartner = new empresaPartner($REQUEST, $usuario);
				if ($newCompanyPartner->exists()) {
					$template->display("succes_form.tpl");
					exit;
				} else {
					$template->assign ("error", $newCompanyPartner->error);
				}
			} else {
				$template->assign ("error", "El partner que has elegido no valida en ese idioma");
			}
		}
	}

	$template->assign("campos", empresaPartner::publicFields(empresa::PUBLIFIELDS_MODE_ASSIGN_PARTNER, null, $usuario));
	$template->assign("titulo","titulo_nuevo_partner");
	$template->display("form.tpl");
	
?>
