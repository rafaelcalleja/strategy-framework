<?php
	require_once("../api.php");
	$template = new Plantilla();

	$datosAccesoModulo = $usuario->accesoModulo("configurar");

	if( !is_array($datosAccesoModulo) ){ die("Inaccesible"); }

	$opciones = array();
	$empresa = $usuario->getCompany();
	
	
	$opciones[0]["items"][] = array( "href" => "empresa/modificar.php?poid={$empresa->getUID()}", "lang" => "desc_modificar_datos_cliente", "class" => "box-it" );
		
	if( $usuario->esSATI() || $usuario->esAdministrador() ){
		$opciones[0]["items"][] = array( "href" => "configurar/account/asignarcampos.php?poid={$empresa->getUID()}", "lang" => "desc_asignar_campos_cliente", "class" => "box-it" );
	}


	if( $usuario->accesoModulo("tipodocumento", 1) ){
		$opciones[0]["items"][] = array( "href" => "#configurar/tipodocumento.php", "lang" => "lista_tipos_documentos" );
	}
	
	if( $usuario->accesoModulo('exportacion_masiva',1) && ( $empresa->isEnterprise() || $usuario->esSATI() ) ){
			$opciones[0]["items"][] = array( "href" => "#configurar/listado.php?m=exportacion_masiva&config=1&comefrom=empresa", "lang" => "exportacion_masiva" );
	}

	if ( $empresa->isEnterprise() || $empresa->isPremium() || $usuario->esSATI() ) {
		$opciones[0]["items"][] = array( "href" => "#configurar/listado.php?m=etiqueta&comefrom=empresa&config=1", "lang" => "etiquetas" );
	}

	if ( $empresa->isEnterprise() || $usuario->esSATI() ) {
		$opciones[0]["items"][] = array( "href" => "#configurar/plantillaemail.php", "lang" => "plantillas_email");
	}
	

	if( $usuario->accesoModulo("noticia", 1) && ($empresa->isEnterprise() || $empresa->isPremium() || $usuario->esSATI()) ){
		$opciones[0]["items"][] = array( "href" => "#configurar/listado.php?m=noticia&comefrom=empresa&config=1", "lang" => "noticias" );
	}

	if( $usuario->accesoModulo("message", 1) && ($empresa->isEnterprise() || $empresa->isPremium() || $usuario->esSATI()) ){
		$opciones[0]["items"][] = array( "href" => "#configurar/listado.php?m=message&comefrom=empresa&config=1", "lang" => "messages" );
	}

	$opciones[0]["items"][] = array( "href" => "#list.php?m=invoice&action=Invoices&poid={$empresa->getUID()}&comefrom=empresa&data[parent]=$empresa", "lang" => "payment_summary");

	if (count($empresa->obtenerValidationPrices())) {
		$opciones[0]["items"][] = array( "href" => "#list.php?m=empresaPartner&action=ValidationPrices&poid={$empresa->getUID()}&comefrom=empresa&data[parent]={$empresa->getUID()}&data[context]=prices_validation&options=0&config=1", "lang" => "validation_prices");	
	}

	if( $usuario->esSATI() || $usuario->esAdministrador() ){
		$opciones[1] = array("nombre" => $template->getString("conf_sistema_staff"), "items" => array() );
		$opciones[1]["items"][] = array( "href" => "#configurar/llamada/listado.php", "lang" => "consulta_llamadas", "class" => "" );
		$opciones[1]["items"][] = array( "href" => "#configurar/account/reportes/?poid={$empresa->getUID()}", "lang" => "reportes");
		$opciones[1]["items"][] = array( "href" => "#configurar/comentarioanulacion.php", "lang" => "comentarios_anulacion");
		$opciones[1]["items"][] = array( "href" => "configurar/account/nuevaempresa.php", "lang" => "empresa", "class" => "box-it" );

		if( $usuario->accesoModulo("tipo_epi", 1) ){
			$opciones[1]["items"][] = array( "href" => "#configurar/listado.php?m=tipo_epi&config=1", "lang" => "tipos_de_epi" );
		}


	}

	if( $usuario->esAdministrador() ){
		$opciones[2] = array("nombre" => $template->getString("conf_sistema_admin"), "items" => array() );
		$opciones[2]["items"][] = array( "href" => "#configurar/roles.php", "lang" => "rol_generico" );
		$opciones[2]["items"][] = array( "href" => "configurar/modificar.php?m=system&poid=1", "lang" => "preferencias_globales", "class" => "box-it" );
		if ($empresa->isPartner()){
			$opciones[2]["items"][] = array( "href" => "configurar/account/editPartner.php?poid={$empresa->getUID()}", "lang" => "config_partner", "class" => "box-it" );
		}
		$opciones[2]["items"][] = array( "href" => "#configurar/account/listPartners.php", "lang" => "associate_partner");
	}


	foreach( $opciones as $i => $opcion ){
		if( !count($opcion["items"]) ){
			unset($opciones[$i]);
		}
	}

	if (!$usuario->isAgent()){
		$template->assign("secciones", $opciones);
	}
	
	$template->assign("title", $empresa->getUserVisibleName() );
	$optionHTML = $template->getHTML("opciones_simple.tpl");


	if( $usuario->esStaff() && !is_ie() ){
		// Información acerca del servidor
		$template = new Plantilla();
		//$template->assign("data", $_SERVER );
		$template->assign("data", system::getServerData() );
		$optionHTML = $template->getHTML("right_bar.tpl") . $optionHTML;
	}

	$json = new jsonAGD();
	$json->nombreTabla("cofigurar-sistema");
	$json->establecerTipo("options");
	$json->nuevoSelector(".option-list", $optionHTML);
	$json->addHelpers( $usuario );
	$json->display();
?>
