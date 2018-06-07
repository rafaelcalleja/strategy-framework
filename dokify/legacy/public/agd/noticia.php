<?php
	require_once("../api.php");

	$template = new Plantilla();
	
	$datosAccesoModulo = $usuario->accesoModulo("noticia");

	$json = new jsonAGD();


	if( !is_array($datosAccesoModulo) ){
		$optionHTML = "<br /><h1>NO PUEDES VER NOTICIAS</h1>";
	} else {
		$noticias = $usuario->getCompany()->obtenerNoticias();
		/*
		if( count($noticias) ){
			$template->assign("title",	$template->getString("noticias") );
		}
		*/
		$template->assign("noticias", $noticias);
		$optionHTML = $template->getHTML("noticias.tpl");

		if( !$usuario->isViewFilterByGroups() ){
			//$template->assign("title",	$template->getString("sumario") );
			$template->assign("info", obtener_informacion_sumario($usuario) );
			$optionHTML = $template->getHTML("sumario.tpl") . $optionHTML;
		}



		$optionHTML = $template->getHTML("buscador.tpl") . $optionHTML;

		$json->informacionNavegacion($template->getString("inicio"), $template->getString("noticias"));
		//dump($template->getString("noticias"));
	}


	$json->establecerTipo("options");
	$json->nombreTabla("noticia");
	$json->nuevoSelector(".option-list", $optionHTML);

	$json->menuSeleccionado( "noticia" );
	$json->display();
?>
