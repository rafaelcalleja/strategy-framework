<?php
	require_once __DIR__ ."/../../../api.php";

	$template 	= Plantilla::singleton();
	$json 		= new jsonAGD();
	$docat 		= new documento_atributo(obtener_uid_seleccionado());
	$allow 		= $usuario->accesoAccionConcreta('documento_atributo', 'criteria', true);


	if (!$allow || !$usuario->accesoElemento($docat)) die('Inaccesible');

	if (isset($_REQUEST['send'])) {

		$html = utf8_encode($_POST['contenido']);
		$data = ['criteria' => $html];

		$update = $docat->update($data, documento_atributo::PUBLIFIELDS_MODE_CRITERIA, $usuario);

		if ($update === true) {
			print $template('exito_texto');
		} elseif ($update === null) {
			print $template('sin_cambios');
		} else {
			print $template('error_texto');
		}

		exit;
	}

	$html = $docat->getCriteria();
	$template->assign("html", $html);
	$template->assign("action", "configurar/documento/criteria.php");
	$html = $template->getHTML("plantillahtml.tpl");


	$json->loadScript( RESOURCES_DOMAIN . "/js/tiny_mce/jquery.tinymce.js");
	$json->establecerTipo("simple");
	$json->informacionNavegacion($template->getString("inicio"), 
		$template->getString("configurar"),
		$template->getString("documentos"), 
		$template->getString("criterios"),
		$docat->getUserVisibleName());
	$json->nuevoSelector("#main", $html);
	$json->display();
