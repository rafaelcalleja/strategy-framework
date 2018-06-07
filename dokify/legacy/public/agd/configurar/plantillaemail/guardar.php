<?php
	include_once( "../../../api.php");
	$template = new Plantilla();

	if( !isset($_REQUEST["contenido"]) ){ die("Error, no hay nada que guardar"); }

	$empresaReferencia = $usuario->getCompany();


	$plantillaEmail = new plantillaemail( obtener_uid_seleccionado() );

	if (version_compare(PHP_VERSION, '5.4', '>=')) {
 		$contenido = utf8_decode(html_entity_decode(stripcslashes($_POST["contenido"])));
  	} else {
  		$contenido = html_entity_decode(stripcslashes($_POST["contenido"])); 
 	}

	$templateFILE = DIR_EMAILTEMPLATES . "empresa_". $empresaReferencia->getUID() ."/". $plantillaEmail->getName() .".html";

	if( archivo::escribir($templateFILE, $contenido, true) ){
		die( "Guardado correctamente" );
	} else {
		die( "Error al guardar" );
	}
?>
