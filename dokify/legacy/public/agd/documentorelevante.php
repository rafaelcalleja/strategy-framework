<?php
	include_once dirname(__FILE__) . "/../api.php";

	$empresaUsuario = $usuario->getCompany();

	$empresasSolicitantesDocumentos = $empresaUsuario->obtenerEmpresasSolicitantes();
	$coleccionDocumentos = $empresasSolicitantesDocumentos->foreachCall("getAttributesDocuments", array(array($usuario,"relevante=1")) )->unique();
	
	$uid = obtener_uid_seleccionado();
	$attrs = array();
	foreach( $coleccionDocumentos as $i => $documentoAtributo ){	
		if( $documentoAtributo->isLoaded() ){
			$attrs[] = $documentoAtributo; 
			if( isset($_REQUEST["send"]) && $uid == $documentoAtributo->getUID() ){
				set_time_limit(0);
				$documentoAtributo->downloadFile();
				exit;
			}
		}
	}

	return $attrs;
	/*
	// Si no tiene documentos cancelamos...
	if( !count($attrs) ){ exit;}

	// Instanciar la plantilla
	$template = Plantilla::singleton();

	$template->assign("hiddebottom", true );
	$template->assign("icon", true );
	$template->assign("elementos", $attrs );

	$template->display("documentorelevante.tpl");
	*/