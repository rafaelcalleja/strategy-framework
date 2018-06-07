<?php
	/*MENSAJE DE INICIO DE AGD*/
	include( "../api.php");



	if( ($modulo = obtener_modulo_seleccionado()) && $uid = obtener_uid_seleccionado() ){
		$elemento = new $modulo($uid);
	} else {
		die("Inaccesible");
	}

	$template = new Plantilla();



	if( isset($_REQUEST["send"]) ){
		if( isset($_REQUEST["action"]) ){
			switch( $_REQUEST["action"] ){
				case "export":
					
					$company = $usuario->getCompany();
					if ($company->needsPay()) {
						die("<script>top.agd.func.open('payplugins.php?plugin=table2xls');</script>");
					}



					$list = false;
					if( $uids = obtener_uids_seleccionados() ){
						$list = $uids;
					}

					$parent = null;
					if( isset($_GET["oid"]) && is_numeric($_GET["oid"]) ){
						$parent = $_GET["oid"];
					} else if (!$parent && isset($_GET["poid"]) && is_numeric($_GET["poid"]) ) {
						$parent = new empresa($_GET['poid']);
					}

					$exportacion = new exportacion( $_REQUEST["name"], $modulo, $list);
					$exportacion->export("xls", $usuario, $parent);
					exit;
				break;
				case "dl":
					$informe = new informe( $_REQUEST["oid"], $elemento );
					$informe->descargar();
					exit;
				break;
				case "rm":
					$informe = new informe( $_REQUEST["oid"], $elemento );
					if( $informe->eliminar() ){
						$template->assign("succes", "exito_texto");
					} else {
						$template->assign("error", $estado);
					}
				break;
			}
		} else {
			$files = unserialize($_SESSION["FILES"]);
			$informe = informe::anexar( $files["archivo"], $elemento );
			if( $informe instanceof informe ){
				$template->assign("succes", "exito_texto");
			} else {
				$template->assign("error", $informe);
			}

			unset($_SESSION["FILES"]);
		}
		// dump($files);
	}

	$informes = $elemento->obtenerInformes();
	$exportaciones = $elemento->obtenerExportaciones("empresa", false);

	//$template->assign("exportaciones", $exportaciones);
	$template->assign("informes", $informes);
	$template->display("descargarinformes.tpl");

?>
