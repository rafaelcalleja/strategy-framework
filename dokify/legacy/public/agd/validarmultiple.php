<?php
	require_once("../../auth.php");
	require_once("../../config.php");
	include( DIR_FUNC . "common.php");

	$lang = Plantilla::singleton();
	$elementoActual = unserialize( $_SESSION["OBJETO_".strtoupper($modulo)] );


	if( isset($_REQUEST["selected"]) ){
		$estados = array();

		foreach( $_REQUEST["selected"] as $idDocumentoSeleccionado ){
			$idDocumentoSeleccionado = db::scape( $idDocumentoSeleccionado );
			$documento = new documento( $idDocumentoSeleccionado, $elementoActual);
			
			$solicitantes = $documento->obtenerSolicitantes();
			foreach( $solicitantes as $solicitante ){
				$estado = $documento->validar($solicitantes);
				if( $estado === 0 ){
					if( !isset($estados["ningun_cambio"]) ){ $estados["ningun_cambio"] = 0;}
					$estados["ningun_cambio"]++;
					
				} else {
					if( is_numeric( $estado ) ){
						if( !isset($estados["exito_texto"]) ){ $estados["exito_texto"] = 0;}
						$estados["exito_texto"]++;

					} else {
						if( !isset($estados["error_texto"]) ){ $estados["error_texto"] = 0;}
						$estados["error_texto"]++;
						
					}
				}
			}
		}
		
		if( isset($estados["exito_texto"]) ){
			echo $lang->getString("exito_texto")."<br /><br />";
		} 
		if( isset($estados["error_texto"]) ){
			echo $lang->getString("error_texto")."<br /><br />";
		}
		if( isset($estados["ningun_cambio"]) ){
			echo $lang->getString("alguno_ningun_cambio")."<br /><br />";
		}
		
	}
	


?>
