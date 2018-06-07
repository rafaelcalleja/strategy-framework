<?php
	include_once "../api.php";

	if( ($uid = obtener_uid_seleccionado()) && ($m=obtener_modulo_seleccionado()) ){
		$item = new $m($uid);
		
		if( !$item || !$usuario->accesoElemento($item) ) die("Inaccesible");
		$template = Plantilla::singleton();
		$template->assign("elemento", $item);
		//$template->display("item_profile.tpl");


		$json = new jsonAGD();
		$json->informacionNavegacion( $template("inicio"), $template($m), $item->getUserVisibleName() );
		$json->establecerTipo("simple");
		$json->addHelpers( $usuario );
		$json->nuevoSelector("#main", $template->getHTML("item_profile.tpl") );
		//$json->menuSeleccionado();
		$json->display();
	}

?>
