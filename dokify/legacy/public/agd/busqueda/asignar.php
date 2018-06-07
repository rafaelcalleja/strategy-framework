<?php
	include( "../../api.php");

	$comefrom = obtener_comefrom_seleccionado();

	if( !$comefrom ){ die("Inaccesible"); }

	$template = Plantilla::singleton();
	$busqueda = new buscador( obtener_uid_seleccionado() );


	// Si se pulsa en guardar...
	if( isset($_REQUEST["send"]) ){
		switch($comefrom){
			case "atributo":
				$blacklist = ( isset($_REQUEST["elementos-disponibles"]) ) ? $_REQUEST["elementos-disponibles"] : array();
				if( $busqueda->getAvailableAttributes($usuario, $blacklist) ){
					$template->assign("succes", "exito_texto");
				}
			break;
		}
	}


	// Extrar datos para mostrar..
	switch($comefrom){
		case "atributo":
			$order = "uid_modulo_origen, uid_elemento_origen";
			$disponibles = $busqueda->getAvailableAttributes($usuario, $order);
			$asignados = $busqueda->getAssignedAttributes($usuario, $order);

			$template->assign( "groupby" , array("getElementName") );
		break;
	}



	// Display de la plantilla..
	$template->assign( "elemento" , $busqueda );
	$template->assign( "asignados" , $asignados );
	$template->assign( "disponibles" , $disponibles );
	$template->assign( "back" , "busqueda/exportar/documentos.php?poid=". $busqueda->getUID() );
	$template->display("configurar/asignarsimple.tpl");
?>
