<?php
	/* DOCUMENTO DE RIESGOS */
	ob_start();
	include( "../api.php");

	if( !in_array($_REQUEST["m"], util::getAllModules("documentos=1") ) ){
		die("Error: Modulo no especificado!");
	}
	$modulo = db::scape($_REQUEST["m"]);

	//instanciamos nuestro elemento
	$elementoSeleccionado = new $modulo( obtener_uid_seleccionado(), false );

	//comprobacion de seguridad
	if( !$usuario->accesoElemento( $elementoSeleccionado ) ){ exit; }

	//instanciamos la plantillas
	$template = new Plantilla();

	//buscamos agrupamientos
	$agrupamientos = $elementoSeleccionado->obtenerAgrupamientos( $usuario );


	$html = "";
	//buscamos los agrupadores
	$agrupadores = $datos = array();
	foreach($agrupamientos as $i => $agrupamiento){
		$agrupadores = $agrupamiento->obtenerAgrupadoresAsignados($elementoSeleccionado);
		foreach( $agrupadores as $agrupador ){
			$filepath = DIR_RIESGOS . "empresa_". $usuario->getCompany()->getUID() . "/agrupador_". $agrupador->getUID();
			if (archivo::is_readable($filepath)) {
				if ($html = archivo::leer($filepath)) {
					$datos[] = $html;
				}
			}
		}
	}

	$html = implode("<br />",$datos);


	$sustituciones = array();

	$empresa = $elementoSeleccionado->obtenerEmpresaContexto($usuario);

	$empleadoDNI = $elementoSeleccionado->obtenerDato('dni');
	$sustituciones['{%empleado-nif%}'] = $empleadoDNI;
	$sustituciones['{empleado-nif}'] = $empleadoDNI;
	
	$epis = $elementoSeleccionado->obtenerEpis();
	if (count($epis)) {
		$HTMLEpis = '<ul>';
		foreach ($epis as $epi) {
			$HTMLEpis .= $epi->getHTMLName();
		}
		$HTMLEpis .= '</ul>';
	} else $HTMLEpis = $template->getString("sin_epis");
	$sustituciones['{%epis%}'] = $HTMLEpis;
	$sustituciones['{epis}'] = $HTMLEpis;
	
	$episSolicitadas = $elementoSeleccionado->obtenerTiposEpiSolicitados();
	$HTMLEpisSolicitadas = ( count($episSolicitadas) ) ? '<ul><li>'. utf8_decode(implode($episSolicitadas->getNames(), '</li><li>')).'</li></ul>' : $template->getString("sin_epis_solicitadas");
	$sustituciones['{%epis-solicitadas%}'] = $HTMLEpisSolicitadas;
	$sustituciones['{epis-solicitadas}'] = $HTMLEpisSolicitadas;

	$jobDescription = $elementoSeleccionado->obtenerDato('descripcion_puesto');
	$sustituciones['{%descripcion-puesto%}'] = $jobDescription;
	$sustituciones['{descripcion-puesto}'] = $jobDescription;


	/* Vamos a soportar las variables dinámicas con y sin % así que el código puede parecer algo redundante, pero va a ser solo temporal. */
	
	$sustituciones['{%representante-legal%}'] = $empresa->obtenerDato("representante_legal"); 

	$sustituciones['{%elemento-tipo%}'] = $elementoSeleccionado->getType(); 
	$sustituciones['{%empresa-nombre%}'] = $empresa->getUserVisibleName(); /*perfilActivo?*/
	$sustituciones['{%empresa-cif%}'] = $empresa->obtenerDato('cif');
	$empresasSuperiores = $empresa->obtenerEmpresaContexto()->obtenerEmpresasSuperiores();
	$empresaSuperior = reset($empresasSuperiores);
	$sustituciones['{%empresa-superior-cif%}'] = $empresaSuperior?$empresaSuperior->obtenerDato('cif'):'';
	$sustituciones['{%empresa-superior-nombre%}'] = $empresaSuperior?$empresaSuperior->getUserVisibleName():'';
	$sustituciones['{%elemento-nombre%}'] = $elementoSeleccionado->getUserVisibleName();


	$sustituciones['{representante-legal}'] = $empresa->obtenerDato("representante_legal"); 

	$sustituciones['{elemento-tipo}'] = $elementoSeleccionado->getType(); 
	$sustituciones['{empresa-nombre}'] = $empresa->getUserVisibleName(); /*perfilActivo?*/
	$sustituciones['{empresa-cif}'] = $empresa->obtenerDato('cif');
	$empresaSuperior = reset($empresasSuperiores);
	$sustituciones['{empresa-superior-cif}'] = $empresaSuperior?$empresaSuperior->obtenerDato('cif'):'';
	$sustituciones['{empresa-superior-nombre}'] = $empresaSuperior?$empresaSuperior->getUserVisibleName():'';
	$sustituciones['{elemento-nombre}'] = $elementoSeleccionado->getUserVisibleName();

	$downloadDateDocument =  util::getDateFormat(time());
	$sustituciones['{%fecha-descarga%}'] = $downloadDateDocument;
	$sustituciones['{fecha-descarga}'] = $downloadDateDocument;

	$matchesAsignados = plantillaemail::getMatches($html, 'agrupador');
	if ($matchesAsignados && count($matchesAsignados)) {
		foreach ($matchesAsignados as $matchAsignado) {
			$numeric = array_filter($matchAsignado['params'], 'is_numeric');

			// Paramétro de modo de "dibujar" la lista
			$string = reset(array_diff($matchAsignado['params'], $numeric));

			$uids = new ArrayIntList($numeric);
			$agrupadoresSeleccionados = $uids->toObjectList("agrupador");
			$agrupadoresAsignados = $elementoSeleccionado->obtenerAgrupadores();
			$agrupadores = $agrupadoresSeleccionados->match($agrupadoresAsignados);

			switch($string){
				default:
					$replace = $agrupadores->toUL(true);
				break;
				case 'comalist':
					$replace = $agrupadores->getUserVisibleName();
				break;
			}

			$sustituciones[$matchAsignado['var']] = $replace;
		}
	}
	
	$html = plantillaemail::reemplazar($html, $sustituciones);


	if( isset($_REQUEST["send"]) ){
  		$html2pdf = new HTML2PDF('P','A4','es', array(10, 10, 10, 10));
    	$html2pdf->WriteHTML($html);
		ob_end_clean();
    	$html2pdf->Output('evaluacionriesgos.pdf');
	} else {
		$template->display("evaluacionriesgos.tpl");
	}
