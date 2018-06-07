<?php

	//BUSCAR EL MODULO SELECCIONADO
	function obtener_modulo_seleccionado(){
		if( isset($_REQUEST["m"]) && trim($_REQUEST["m"]) ){
			if( !in_array($_REQUEST["m"], util::getAllModules() ) ){
				return null;
			}
			return db::scape(trim($_REQUEST["m"]));
		} else {
			return null;
		}
	}

	function obtener_uids_seleccionados(){
		if( isset($_REQUEST["selected"]) && is_array($_REQUEST["selected"]) ){
			return new ArrayIntList($_REQUEST["selected"]);
		} else {
			return null;
		}
	}

	//BUSCAR EL ID SELECCIONADO
	function obtener_uid_seleccionado(){
		if( isset($_REQUEST["poid"]) && is_numeric($_REQUEST["poid"]) ){
			$idSeleccionado = $_REQUEST["poid"];
		} elseif( isset($_REQUEST["oid"]) && is_numeric($_REQUEST["oid"]) ){
			$idSeleccionado = $_REQUEST["oid"];
		}  else {
			return null;
		}
		return db::scape($idSeleccionado);
	}

	//BUSACR EL ID DEL GRUPO (AGRUPADOR) SELECCIONADO
	function obtener_grupo_seleccionado(){
		if( isset($_REQUEST["g"]) && (is_numeric($_REQUEST["g"]) || $_REQUEST["g"] == 0) ){
			$grupoSeleccionado = $_REQUEST["g"]; 
		}else{
			return null;
		}
		return db::scape($grupoSeleccionado);
	}


	function get_int_array_from_request($param){
		if (@isset($_REQUEST[$param])) {
			$array = $_REQUEST[$param];
		} else if (is_array($param)) {
			$array = $param;
		} else {
			return false;
		}
		
		$list = array();
		foreach ($array as $key => $val) {
			if( is_numeric($val) ){
				$list[$key] = $val;
			} elseif( is_array($val) && is_numeric(implode("",$val)) ){
				$list[$key] = $val;
			}
		}

		if( count($list) ) return $list;

	}


	function require_xhr(){
		if (!isset($_SERVER["HTTP_X_REQUESTED_WITH"]) || $_SERVER["HTTP_X_REQUESTED_WITH"] != "XMLHttpRequest") {
			$url = $_SERVER["REQUEST_URI"];
			$location = str_replace("/agd/", "/agd/#", $url);
			header("Location: $location");
			exit;
		}
	}

