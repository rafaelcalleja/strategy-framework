<?php
	/* EDITAR UN USUARIO */
	include( "../../api.php");

	// actualizar datos del usuario actual en base a parametros
	$viewall = isset($_REQUEST["viewall"]) ? $_REQUEST["viewall"] : 0;

	if (isset($_REQUEST['checked'])) {
		$viewall = $_REQUEST["checked"];
	}

	$data = array("config_viewall" => $viewall);
	
	if ($usuario->esStaff()) {
		if ($usuario->update($data, elemento::PUBLIFIELDS_MODE_PREFS, $usuario)) {
			$usuario->clearItemCache();

			$cache = cache::singleton();
			$cache->deleteData("configvalue-{$usuario}-viewall");

			print json_encode(array("refresh" => 1, 'result' => 1));
		} else {
			print json_encode(array("jGrowl" => "Error!"));
		}
	}
?>
