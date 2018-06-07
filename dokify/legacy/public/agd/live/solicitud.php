<?php 

	if ($solicitudes = $usuario->getEmpresaSolicitudPendientes()) {
		foreach ($solicitudes as $solicitud) {
			$dataArray["solicitud"][] = $solicitud->getJsonData();
		}
	}