<?php
	include_once( "../../../api.php");

	$template = new Plantilla();
	if (!$modulo = obtener_modulo_seleccionado()) die("Inaccesible");

	$broadcast = new $modulo(obtener_uid_seleccionado());


	$template->assign("html", $broadcast->getHTML() );
	$template->assign("action", "configurar/broadcast/guardar.php?m=$modulo" );
	$template->assign("goto", "configurar/$modulo.php" );


	$json = new jsonAGD();
	$json->informacionNavegacion( $template->getString("inicio"),  $template->getString("configurar"),  $template->getString($modulo."s"), $broadcast->getUserVisibleName());
	//$json->loadScript("http://js.nicedit.com/nicEdit-latest.js");
	$json->loadScript( RESOURCES_DOMAIN . "/js/tiny_mce/jquery.tinymce.js");
	$json->nombreTabla("modificar-$broadcast-".$broadcast->getUID());
	$json->establecerTipo("simple");
	$json->nuevoSelector("#main", $template->getHTML("plantillahtml.tpl") );
	$json->display();
?>
