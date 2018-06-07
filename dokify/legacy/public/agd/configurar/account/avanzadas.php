<?php
	require_once("../../../api.php");

	$template = new Plantilla();


	if( !$usuario->esStaff() ){ die("Inaccesible"); }

	$empresa = new empresa( obtener_uid_seleccionado() );

	//dump($_SERVER);	

	$opciones = array();
	
	$roles = rol::obtenerRolesGenericos();
	$opciones[0] = array("nombre" => $template->getString("Implantación"), "items" => array() );
		$opciones[0]["items"][] = array( 
			"href" => "configurar/account/alta/usuarios.php?poid=" . obtener_uid_seleccionado(), 
			"lang" => "Alta masiva de usuarios", 
			"class" => "form-to-box",
			"options" => array(
				"rol" => $roles
			)
		);


	foreach( $opciones as $i => $opcion ){
		if( !count($opcion["items"]) ){
			unset($opciones[$i]);
		}
	}

	$template->assign("secciones", $opciones);
	$template->assign("title", $template->getString("opt_herramientas_avanzadas") . " - " . $empresa->getUserVisibleName() );
	$optionHTML = $template->getHTML("opciones_simple.tpl");


	$json = new jsonAGD();
	$json->nombreTabla("cliente-avanzadas");
	$json->establecerTipo("options");
	$json->nuevoSelector(".option-list", $optionHTML);
	$json->addHelpers( $usuario );
	$json->display();
?>
