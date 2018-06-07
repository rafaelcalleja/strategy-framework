<?php

	include "api.php";

	if( $modulo = obtener_modulo_seleccionado() ){
		if( $uid = obtener_uid_seleccionado() ){
			$item = new $modulo($uid);

			$data = array("refresh" => 1);
			$update = $item->actualizarSolicitudDocumentos($usuario);
			if( $update === true ){
				$data["jGrowl"] = "Actualizado correctamente";
			} elseif( $updte === NULL ){
				$data["jGrowl"] = "No hay documentos que solicitar";
			} else {
				unset($data["refresh"]);
				$data["jGrowl"] = "Error al actualizar";
			}

			die(json_encode($data));
		}
	}