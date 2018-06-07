<?php 

	if ($usuarioReal instanceof usuario) {
		$dataArray["remote"] = array(
			"user-id" => $usuarioReal->getUID(),
			"user-name" => $usuarioReal->getUserName(),
			//"hash" => $usuario->getLastPage(),
			"status" => $usuario->verEstadoConexion(),
			"remote-user" => $usuario->getUserName()
		);
	}