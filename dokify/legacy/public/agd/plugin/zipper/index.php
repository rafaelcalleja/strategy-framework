<?php
	ob_start();
	require_once __DIR__ . "/../../../api.php";



	$plugin = new plugin("zipper");

	//----- COMPROBAMOS QUE EL USUARIO TIENE ACCESO A ESTOS DATOS
	if( !$usuario->accesoPlugin($plugin) ){ die("<script>top.agd.func.open('payplugins.php?plugin=zipper');</script>"); }

	/**	Buscamos el elemento actual
	  * 	al cual pertencen los documentos
	  */
	$modulo = obtener_modulo_seleccionado();
	if( ( $uid = obtener_uid_seleccionado() ) === null ){
		die("<script>alert('Error de acceso');</script>");
	}
	$elemento = new $modulo($uid);


	/**	Recogemos los uid de documento seleccionados 
	  */
	if( !isset($_REQUEST["selected"]) || !is_array($_REQUEST["selected"]) ){ exit; }
	$uidElementosSeleccionados = $_REQUEST["selected"];



	/**	Si no esta cargada la extension de archivos
	  *		zip, la cargamos y instanciamos nuestro objeto
	  */
	if (!extension_loaded('zip')){	dl('zip.so'); }
	$zip = new ZipArchive();




	/**	Asignamos un nombre temporal y 
	  * 	creamos el fichero zip vacio de momento
	  */
	$tempName = "/tmp/".time().".zip";
	if( !$zip->open( $tempName, ZIPARCHIVE::CREATE) ) {
		return false;
	}


	$files = array();

	/**	Recorremos el conjunto de id seleccionados
	  *		para instanciar el documento y extraer el archivo
	  *
	foreach( $uidElementosSeleccionados as $uidDocumento ){
		$documento = new documento( $uidDocumento, $elemento);
		
		/** Recuperamos todos los objeto archivo 
		  * asociados a este documento, referenciando al usuario
		  * para que filtre lo necesario
		  *
		$ficheros = $documento->getAllFiles($usuario);

		if( count($ficheros) ){
			foreach( $ficheros as $fichero ){
				$filePath = $fichero->getPath();
				if( is_readable($filePath) && !is_dir($filePath) ){
					$files[] = $filePath;
					$zip->addFile( $filePath,  archivo::cleanFilenameString($fichero->getRealfilename())  );
				}
			}
		}
	}/**/
	foreach( $uidElementosSeleccionados as $uidDocumento ){
		$documento = new documento($uidDocumento);
		$solicitudes = $documento->obtenerSolicitudDocumentos($elemento, $usuario);

		foreach( $solicitudes as $solicitud ){
			if( $anexo = $solicitud->getAnexo() ){
				$path = $anexo->getFullPath();

				$fileData = is_readable($path) && !is_dir($path) ? file_get_contents($path) : archivo::leer($path);
				if( $fileData ){
					$files[] = $path;

					$name = $anexo->getDownloadName();
					if( $agrupador = $anexo->obtenerAgrupadorReferencia() ){
						$name .= "_" . $agrupador->getUserVisibleName();
					}

					if( $empresa = $anexo->obtenerEmpresaReferencia() ){
						if( $empresa instanceof empresa ){
							$name .= "_" . $empresa->getUserVisibleName();
						} elseif ( $empresa instanceof ArrayObject ){
							$name .= "_" . implode("_", $empresa->getNames());
						}
					}

					$zip->addFromString(archivo::cleanFilenameString($name) ."." . $anexo->getExtension(), $fileData);
				}
			}
		}

		
	}


	if( !count($files) ){
		die("<script>alert('No se encuentran ficheros para añadir al archivo zip')</script>");
	}

	/** No lo necesitamos mas **/
	unset($zip);

	ob_end_clean();
	$elemento->writeLogUI(logui::ACTION_ZIP_DOCS, "", $usuario);

	if( !archivo::descargar($tempName, "documentos.zip", true) ){
		die("<script>alert('Error al generar el archivo zip');</script>");
	}
	
?>
