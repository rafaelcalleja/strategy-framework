<?php
	
	
	function obtener_comefrom_seleccionado(){
		if( isset($_REQUEST["comefrom"]) && trim($_REQUEST["comefrom"]) ){
			return db::scape(trim($_REQUEST["comefrom"]));
		} else {
			return null;
		}
	}

	function obtener_referencia(){
		if( isset($_REQUEST["ref"]) && trim($_REQUEST["ref"]) ){
			return db::scape(trim($_REQUEST["ref"]));
		}
		return null;
	}


	function obtener_params($key='params'){
		if( !isset($_REQUEST[$key]) || !$_REQUEST[$key] ){
			return false;
		}

		$params = $_REQUEST[$key];
		if (!is_array($_REQUEST[$key])) {
			$params = array($_REQUEST[$key]);
		}

		$result = array();

		foreach ($params as $clave=>$value) {
			if ($elemento = elemento::factory($value)) {
				$result[$clave] = $elemento;
			} else {
				$result[$clave] = $value;
			}
		}

		return $result;
		
	}
