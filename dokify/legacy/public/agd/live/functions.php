<?php 
	$dataArray["access"] = $system->getSystemStatus();

	if ($usuarioReal === null) {
		$usuario->touch();
	}