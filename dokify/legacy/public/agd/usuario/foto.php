<?php
	ob_start();
	include("../../api.php");
	session_write_close();


	if( $uid = obtener_uid_seleccionado() ){
		$selected = new usuario( $uid );
	} else {
		exit;
	}

	if( $selected->exists() && $usuario->accesoElemento($selected) ){
		$rutaFoto = $selected->getImage();


		$etag = md5($rutaFoto);

		header("Content-type: image/png");
		header("ETag: $etag");
		header("Expires: " . gmdate ("D, d M Y H:i:s", time() + (60 * 60 * 24 * 15) ) . " GMT");
		header("Cache-control: public");

		$ifmatch = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : false;
		if( $ifmatch && $ifmatch === $etag ){
			header('HTTP/1.0 304 Not Modified'); exit;
		}

		if( url_exists($rutaFoto) ){
			ob_end_clean();
			print get($rutaFoto);		
		} else {
			die("Error al cargar la imagen");
		}
	}
?>
