<?php
	include_once( "../../../api.php");

	$template = new Plantilla();
	$plantillaEmail = new plantillaemail( obtener_uid_seleccionado() );

	$html = $plantillaEmail->getFileContent($usuario->getCompany());

	$template->assign("html", $html);
	$template->assign("action", "configurar/plantillaemail/guardar.php" );
	$template->assign("goto", "configurar/plantillaemail.php" );


	$json = new jsonAGD();
	$json->establecerTipo("simple");
	$json->informacionNavegacion($template->getString("inicio"), 
			$template->getString("configurar"), 
			$template->getString("plantillas_email"), 
			$template->getString("modificar"),
			$plantillaEmail->getName().".html"
	);
	//$json->loadScript("http://js.nicedit.com/nicEdit-latest.js");
	$json->loadScript( RESOURCES_DOMAIN . "/js/tiny_mce/jquery.tinymce.js");
	$json->nuevoSelector("#main", $template->getHTML("plantillahtml.tpl") );
	$json->display();
?>
