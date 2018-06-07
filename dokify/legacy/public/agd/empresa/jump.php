<?php
	include '../../api.php';

	if (!$usuario->esStaff()) exit;

	if (!$uid=obtener_uid_seleccionado()) exit;

	$empresa = new empresa($uid);
	$usuario->jumpTo($empresa);

	unset($_SESSION['user_continue']); // permite a SATI desbloquear empresas

	header("Location: ../");