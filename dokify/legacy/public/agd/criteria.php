<?php
	require_once __DIR__ . "/../api.php";

	$uid 		= obtener_uid_seleccionado();
	$company 	= obtener_comefrom_seleccionado();

	if (!$uid || !$company) exit;


	$template 	= Plantilla::singleton();
	$doc 		= new documento($uid);
	$company 	= new empresa($company);
	$criteria 	= $doc->getCriteria($company);


	$template->assign('html', $criteria);
	$template->display('criteria.tpl');