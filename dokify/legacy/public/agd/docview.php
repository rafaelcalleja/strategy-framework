<?php
	require_once("../api.php");

	// make sure 50MB docs can be downloaded
    ini_set('memory_limit', '256M');

	if (($m = obtener_modulo_seleccionado()) && $uid = obtener_uid_seleccionado()) {
		session_write_close();

		$anexo = new $m($uid, $m);
		$item = $anexo->getElement();
		
		if ($item instanceof Ielemento && $usuario->accesoElemento($item) || $usuario->esValidador() || $usuario->esStaff()) {

			if ($data = $anexo->getAsImage()) {
				header("Content-Type: image/png");
				print $data;
			} elseif ($data = $anexo->getAsPDF()) {
				header("Content-Type: application/pdf");
				print $data;
			} else {
				header("HTTP/1.1 500 Internal Server Error");
				// print "We can't convert the document!";
			}

		} else {
			die("inaccesible");
		}
	}