<?php
	require_once("../api.php");



	if( isset($_REQUEST["current"]) && trim($_REQUEST["current"]) ){

		$tpl = Plantilla::singleton();
		$elemento = trim($_REQUEST["current"]);
		switch( $elemento ){

			case "empleado":
				$empleado = new empleado($_REQUEST["poid"]);
				$agrupador = new agrupador($_REQUEST["oid"]);
				$informacion = $empleado->obtenerEstadoEnAgrupador($usuario, $agrupador);
				//$estadoImg = agrupador::status2image($estado);

				switch( $informacion->estado ){
					case -1:
						print "<h1 style='font-size: 115%'>". $empleado->getUserVisibleName() ."</h1><br />" .$tpl->getString("info_agrupador_inline_asignado"). "<br /> <h1>". $agrupador->getTypeString() ." - ". $agrupador->getUserVisibleName().". ".$tpl->getString("info_agrupador_inline_sin_documentos"). "</h1>";
					break;
					case 0:
						print "<h1 style='font-size: 115%'>". $empleado->getUserVisibleName() ."</h1><br /> " .$tpl->getString("info_agrupador_inline_asignado"). "<br /> <h1>". $agrupador->getTypeString() ." - ". $agrupador->getUserVisibleName()."</h1>";
					break;
					case 2:
						print "<h1 style='font-size: 115%'>". $empleado->getUserVisibleName() ."</h1><br /> " .$tpl->getString("info_agrupador_inline_cumple_correctamente"). " <br /> <h1>". $agrupador->getTypeString() ." - ". $agrupador->getUserVisibleName()."</h1>";
					break;
					case 4:
						print "<h1 style='font-size: 115%'>". $empleado->getUserVisibleName() ."</h1><br /> " .$tpl->getString("info_agrupador_inline_no_cumple_correctamente"). "<br /> <h1>". $agrupador->getTypeString() ." - ". $agrupador->getUserVisibleName()."</h1>";
					break;
				}
			break;
			

			default:
				
				$elemento = new $elemento($_REQUEST["poid"]);
				$agrupador = new agrupador($_REQUEST["oid"]);
				$informacion = $elemento->obtenerEstadoEnAgrupador($usuario, $agrupador);

				$string = strtolower("definicion_".documento::status2string($informacion->estado));
				$string = str_replace(" ","_",$string);
				print $tpl->getString($string);

				break;

			break;
		}
	}

?>
