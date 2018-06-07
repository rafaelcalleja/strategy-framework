<?php
	require_once("../../../api.php");

	//---PLANTILLA
	$template = Plantilla::singleton();

	//----- BUSCAMOS EL ID SELECCIONADO
	$idSeleccionado = obtener_uid_seleccionado();
	if( !is_numeric($idSeleccionado) ){ exit; }

	//INSTANCIAMOS EL ELEMENTO SELECCIONADO, SIN FILTRO INDICANDO DESCARGA
	$documento = documento::instanceFromAtribute( $idSeleccionado, false );

	//----- INSTANCIAR EL LOG
	$log = log::singleton();


	//dump($documento);

	
	//----- BUSCAMOS NUESTRO ELEMENTO ACTUAL
	$elementoActual = $documento->elementoFiltro;
	$documentoAtributo = new documento_atributo( $idSeleccionado );
	//$documento = new documento( $idSeleccionado, $elementoActual);

	//----- DEFINIMOS EL EVENTO PARA EL LOG
	$log->info($elementoActual->getModuleName(),"anexar documento ".$documento->getUserVisibleName(), $elementoActual->getUserVisibleName() );


	if( isset($_REQUEST["send"]) ){
		try{
			if( isset($_SESSION["FILES"]) ){
				$files = unserialize($_SESSION["FILES"]);
				$estado = $documento->anexar( $files["archivo"], true, $documentoAtributo, $usuario );
				if( $estado === true){
					$log->resultado("ok", true);
					$template->display( "succes_form.tpl" );
					exit;
				} else {
					$log->resultado("error $estado", true);
					$template->assign("error", $estado );
				}
			} else {
				$log->resultado("upload error", true);
				$template->assign("error", "error" );
			}
		} catch(Exception $e) {
			$template->assign("error", $e->getMessage() );
		}
	}



	$template->display( "anexar_descargable.tpl" );