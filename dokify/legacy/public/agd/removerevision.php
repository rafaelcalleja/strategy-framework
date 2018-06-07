<?php

	require_once __DIR__ . '/../api.php';

	$template 	= Plantilla::singleton();
	$uid 		= obtener_uid_seleccionado();
	$module 	= obtener_modulo_seleccionado();


	if (!$uid || !in_array($module, solicitable::getModules())) die("Innacesible");
	$revision 	= new revision($uid, $module);
	$revisor 	= $revision->getUser();

	// security check
	if (!$usuario->compareTo($revisor)) die("Innacesible");


	if (isset($_REQUEST['send'])) {
		if ($revision->eliminar()) {
			$template->display('succes_form.tpl');
		} else {
			$template->assign('message', 'error_texto');
			$template->display('error.tpl');
		}
	} else {
		$template->display('removerevision.tpl');
	}