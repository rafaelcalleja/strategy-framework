<?php
	include("../../api.php");
	$template = new Plantilla();
	
	
	$cuestionario= new cuestionario(13);
	
	$preguntas= $cuestionario->getPreguntas();
	foreach($preguntas as $pregunta){
		$txt[]=$pregunta->obtenerDato('pregunta');
	}
	
	$template->assign ("preguntas",$txt);
	$template->display("cumplimentacion.tpl");

?>
