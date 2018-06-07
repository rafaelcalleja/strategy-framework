<?php
	include("../../../api.php");


	if( isset($_REQUEST["img"]) ){
		header("Content-type: image/png");
		$img = base64_decode( str_replace("data:image/png;base64,","",$_REQUEST["img"]) );


		$path = "/tmp/". uniqid() . ".png";
		if( archivo::escribir($path, $img) ){
			$email = new email($usuario->getEmail());
			$email->establecerAsunto("Captura de pantalla - ". $usuario->getEmail());
			$email->adjuntar($path, "captura.png");
			if( $email->enviar() ){
				die("1");
			}
		}
	}
	die("0");
?>
