<?php
	/**
		ES SCRIPT TRABAJA A DIFERENCIA DEL RESTO DE LA APLICACION
		EN MODO PROCEDIMIENTO POR LO DELICADO DE LA OPERACION Y LO UNUTIL DE ESTAS FUNCIONES 
		EN OTROS AMBITOS
	**/
	
	if( !isset($m) || !isset($list) ){
		require_once('../api.php');
		$m = obtener_modulo_seleccionado(); // el parametro m que indica el modulo
		$list = $_REQUEST["selected"];
	}


	if( ( reset($usuario->getAvailableOptionsForModule($m, "reanexar")) || realpath(__FILE__) != realpath($_SERVER['SCRIPT_FILENAME']) ) && is_array($list) ){
		$reattachs = new ArrayObjectList();
		foreach($list as $uid){
			$object = new $m($uid);

			$reattachs = $reattachs->merge($object->reattachAll(NULL, $usuario));
		}
		$numReattachs = count($reattachs);

		$text = "Se han reanexado $numReattachs documentos";

		$data = array("jGrowl" => $text , "refresh" => 1 );
		if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])){
			header("Content-type: application/json");
			print json_encode($data);
		} else {
			return $data;
		}
	}
?>
