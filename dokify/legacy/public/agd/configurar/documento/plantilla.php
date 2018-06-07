<?php
	include_once( "../../../api.php");

	$template = Plantilla::singleton();
	$json = new jsonAGD();
	$docat = new documento_atributo(obtener_uid_seleccionado());
	$info = $docat->downloadFile(true);

	if ( $info["path"] ) {
		if ($docat->isTemplate()) {
			$html = utf8_encode((archivo::leer(DIR_FILES.$info["path"])));

			//$html = ($info['ext']=='txt') ? nl2br(archivo::leer(DIR_FILES.$info["path"])) : ;
			// si el documento actual es html o txt lo cargamos en el editor
			$template->assign("html", $html);
		} else {
			// si es otro tipo, informamos de que sustituira el anexado por la plantilla
			$template->assign("html", "");
			$json->addData('open','/agd/configurar/documento/aviso.php');
		}
	} else {
		$template->assign("html", "");
	}

	$template->assign("action", "configurar/documento/guardar.php" );
	//$json->loadScript("http://js.nicedit.com/nicEdit-latest.js");
	$json->loadScript( RESOURCES_DOMAIN . "/js/tiny_mce/jquery.tinymce.js");
	$json->establecerTipo("simple");
	$json->informacionNavegacion($template->getString("inicio"), 
		$template->getString("configurar"),
		$template->getString("documentos"), 
		$template->getString("modificar"),
		$docat->getUserVisibleName());
	$json->nuevoSelector("#main", $template->getHTML("plantillahtml.tpl") );
	$json->display();
