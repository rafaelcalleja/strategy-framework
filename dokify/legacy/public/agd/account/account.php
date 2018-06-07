<?php
	include_once( "../../api.php");

	// ----- Buscamos el uid seleccionado
	$idElemento = obtener_uid_seleccionado();

	$empresa = new empresa($idElemento);

	$opciones = array();
		$opciones[0]["items"][] = array( "href" => "#configurar/cliente/asignarcampos.php?poid=$idElemento", "lang" => "desc_asignar_campos_cliente" );
		
		$opciones[0]["items"][] = array( "href" => "#configurar/cliente/avanzadas.php?poid=$idElemento", "lang" => "opt_herramientas_avanzadas" );

		$opciones[0]["items"][] = array( "href" => "#configurar/cliente/avisos.php?poid=$idElemento", "lang" => "desc_enviar_avisos_cliente" );

		$opciones[0]["items"][] = array( "href" => "#configurar/cliente/modificar.php?poid=$idElemento", "lang" => "desc_modificar_datos_cliente" );

		$opciones[0]["items"][] = array( "href" => "#configurar/cliente/asignarplugins.php?poid=$idElemento", "lang" => "desc_asignar_plugins_empleados" );


		$template = Plantilla::singleton();
		$template->assign("secciones", $opciones);
		$template->assign("title", $empresa->getUserVisibleName() );
		$optionHTML = $template->getHTML("opciones_simple.tpl");


		$json = new jsonAGD();
		$json->nombreTabla("cofigurar-sistema");
		$json->establecerTipo("options");
		$json->nuevoSelector(".option-list", $optionHTML);
		$json->addHelpers( $usuario );
		$json->display();
?>
