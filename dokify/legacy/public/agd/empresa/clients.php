<?php
	
	require dirname(__FILE__) . '/../../api.php';


	// $log = log::singleton();
	$userCompany = $usuario->getCompany();
	$profile = $usuario->obtenerPerfil();
	$clients = $userCompany->obtenerEmpresasCliente();
	$selfDocs = (bool) $userCompany->countOwnDocuments();
	$clientsWithDocuments = $userCompany->obtenerEmpresasClienteConDocumentos();
	$template = Plantilla::singleton();


	if (isset($_REQUEST['action']) && $company = new empresa(obtener_uid_seleccionado())) {
		switch ($_REQUEST['action']) {
			case 'hide':
				if ($profile->setCompanyWithHiddenDocuments($company, true, $usuario)){
					$template->assign('succes', 'exito_texto');
				} else {
					$template->assign('error', 'error_texto');
				}
				break;

			case 'show':
				if ($profile->setCompanyWithHiddenDocuments($company, false)){
					$template->assign('succes', 'exito_texto');
				} else {
					$template->assign('error', 'error_texto');
				}
				break;
			
			default:
				# code...
				break;
		}
	}

	
	$hiddenClientes = $profile->getCompaniesWithHiddenDocuments();
	
	$clientCompanies = $userCompany->obtenerEmpresasCliente();
	$empresasSuperiores = $userCompany->obtenerEmpresasSuperiores(false, $usuario);

	$template->assign("currentCompanyHasDocuments", $selfDocs);
	$template->assign("user", $usuario);
	$template->assign("userCompany", $userCompany);
	$template->assign("companies", $clientCompanies);
	$template->assign("empresasSuperiores", $empresasSuperiores);
	$template->assign("hiddenCompanies", $hiddenClientes);
	$template->assign("globalview", $usuario->configValue('view'));

	$template->display('companyclients.tpl');