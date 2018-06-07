<?php
	include_once( "../../../api.php");
	$template = new Plantilla();

	//--------- Buscamos que exista algo que modificar
	if (!$modulo = obtener_modulo_seleccionado()) die("Inaccesible");
	if( !isset($_REQUEST["contenido"]) ){ die("Error, no hay nada que guardar"); }

	//--------- Instanciamos el broadcast(mesaje/noticia) que vamos a modificar
	$broadcast = new $modulo(  obtener_uid_seleccionado() );

	//--------- Se parsea correctamente el contenido
	$contenido = utf8_decode(html_entity_decode(stripcslashes($_REQUEST["contenido"])));


	//--------- Se trata de actualizar los datos
	if( !$broadcast->actualizarTexto($contenido) ){
		die("Error #1");
	}


	//--------- Se envia la confirmacion
	echo "Guardado correctamente!!";
?>
