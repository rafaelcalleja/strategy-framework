<?php
	include_once( dirname(__FILE__) . "/../../config.php");	

	if( isset($_SERVER["argv"]) && ($uid = $_SERVER["argv"][1]) && isset($_SERVER["argv"][2]) ){
		
		$attr = new documento_atributo($uid);
		$ontoOff = (bool)($_SERVER["argv"][2]==0);
		if( !$attr->exists() ){
			die("El uid no existe");
		}

		$debug = isset($_SERVER["argv"][3]) ? true : false;

		$attr->updateReferenciaEmpresaElemento($ontoOff, $debug);		
	
	}
?>
