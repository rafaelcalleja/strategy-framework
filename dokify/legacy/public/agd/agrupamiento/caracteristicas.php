<?php
	if( !isset($_REQUEST["poid"]) ){ exit(); }
	include_once( "../../api.php");

	$template = new Plantilla();
	$empresa = $usuario->getCompany();

	$agrupamiento = new agrupamiento( obtener_uid_seleccionado() );

	if( isset($_REQUEST["action"]) ){

		if( isset($_REQUEST["mid"])  ){
			if( $_REQUEST["action"] == "asignar" ){
				if( ($estado = $agrupamiento->asignarModulo($_REQUEST["mid"])) === true ){
					$template->assign("succes", "exito_texto");
				} else {
					$template->assign("error", $estado);
				}
			} else {
				if( ($estado = $agrupamiento->desasignarModulo($_REQUEST["mid"])) === true ){
					$template->assign("succes", "exito_texto");
				} else {
					$template->assign("error", $estado);
				}
			}
		} else {
			$template->assign("currenttab", strtolower($_REQUEST["action"]) );
			switch( $_REQUEST["action"] ){
				case "replicar":
					$m = obtener_modulo_seleccionado();
					$result = $agrupamiento->establecerReplica( array("config_replica_".$m => $_GET["input"]) );
					if( $result ){
						$template->assign("succes", "exito_texto");
					} else {
						$template->assign("error", "error_texto");
					}
				break;
				case "organizador":
					if( isset($_GET["input"]) && $_GET["input"] == 1  ){
						$result = $empresa->establecerOrganizador( $agrupamiento );
					} else {
						$result = $empresa->quitarOrganizador( $agrupamiento );
					}
					if( $result ){
						$template->assign("succes", "exito_texto");
					} else {
						$template->assign("error", "error_texto");
					}
				break;
				case "modulo":
					$template->assign("currenttab", $template->getString(strtolower("Agrupamientos")) );
					$agrupamientoAsignar = new agrupamiento($_GET["oid"]);
					if( $_GET["input"] ){
						$estado = $agrupamiento->asignarAgrupamiento($agrupamientoAsignar);
					} else {
						$estado = $agrupamiento->quitarAgrupamiento($agrupamientoAsignar);
					}

					if( $estado  ){
						$template->assign("succes", "exito_texto");
					} else {
						$template->assign("error", $estado);
					}
				break;
				default:
					$tab = $_REQUEST["action"];
					$template->assign("currenttab", $tab );
					if( $tab == "documentos" ){
						$template->assign("currenttab", "Proyecto" );
					}

					try {
						if( $agrupamiento->updateWithRequest( array("config_$tab" => $_GET["input"]), "attr", $usuario ) ){
							$template->assign("succes", "exito_texto");
						} else {
							$template->assign("error", "error_texto");
						}
					} catch(Exception $e){
						$template->assign("error", $e->getMessage());
					}
				break;
			}

		}
	}




	// ------- DESDE AQUI, DEFINIR LA GUI

	$tabs = array();
		$tabs[$template->getString(strtolower("Modulos"))] 		= "ficha_agrupamiento.tpl";

		$agrupamientos = $empresa->obtenerAgrupamientosVisibles();
		$agrupamientos = elemento::discriminarObjetos($agrupamientos, $agrupamiento);
		if( count($agrupamientos) ){
			$template->assign("agrupamientos", $agrupamientos);
			$tabs[ucfirst($template->getString(strtolower("Agrupamientos")))]	= "lista_agrupamientos.tpl";
		}

		$organizables = array(8 => "empleado");
		$template->assign("organizables", $organizables);
		$tabs[$template->getString(strtolower("Organizador"))] 	= "lista_organizador.tpl";
		$tabs[$template->getString(strtolower("Anclaje"))] 		= "ficha_anclaje.tpl";
		$tabs[$template->getString(strtolower("Al_vuelo"))] 		= "ficha_alvuelo.tpl";
		$tabs[$template->getString(strtolower("Jerarquia"))] 		= "ficha_jerarquia.tpl";
		$tabs[$template->getString(strtolower("Replicar"))] 		= "lista_replica.tpl";


	$template->assign("anclaje", $agrupamiento->configValue("anclaje") );
	$template->assign("filter", $agrupamiento->configValue("filter") );
	$template->assign("documentos", $agrupamiento->configValue("documentos") );
	$template->assign("al_vuelo", $agrupamiento->configValue("al_vuelo") );
	$template->assign("jerarquia", $agrupamiento->configValue("jerarquia") );
	$template->assign("replicables", agrupamiento::getModulesReplicables() );
	//$template->assign("organizador", $agrupamiento->configValue("organizador") );


	$template->assign("titulo", "caracteristicas");
	$template->assign("empresa", $empresa);
	$template->assign("tabs", $tabs );
	$template->assign("elemento", $agrupamiento);
	$template->display("tabs.tpl");

?>
