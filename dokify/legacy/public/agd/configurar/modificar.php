<?php

	include_once( "../../api.php");

	$template = Plantilla::singleton();
	$log = new log();

	if( !($modulo = @$_REQUEST["m"]) || !($uid = obtener_uid_seleccionado()) ){	die("Inaccesible");	}

	$elemento = new $modulo($uid, false);

	if( is_callable(array($elemento, "getUserVisibleName")) ){
		$log->info($modulo, "modificar elemento", $elemento->getUserVisibleName());
	}

	$config = ( isset($_GET["config"]) && $_GET["config"] == 1 ) ? 1 : 0;
	if( !$usuario->accesoModificarElemento($elemento, $config) ){
		$log->nivel(6);
		$log->resultado("sin permiso", true);
		$template->display("erroracceso.tpl"); 
		exit;
	}
	
	$comefrom = (isset($_REQUEST["comefrom"])) ? $_REQUEST["comefrom"] : false;

	if( isset($_REQUEST["send"] ) ){
		try{
			$update = $elemento->update(false, $comefrom, $usuario);

			if ($update === null) {
				$log->resultado("null", true);
				if( isset($_REQUEST["inline"]) ){ die("null"); }
				$template->assign("info", "No se modifico nada");
			} elseif ($update === false) {
				$mysqlErrorString = (($elemento->error)?$elemento->error:'error');
				$log->resultado($mysqlErrorString, true);
				if( isset($_REQUEST["inline"]) ){ die($mysqlErrorString); }
				$template->assign("error", $mysqlErrorString);
			} else {
				if( is_callable( array($elemento, "obtenerCallback" )) ){
					$callbackData = $elemento->obtenerCallback($usuario);
					$template->assign("acciones", $callbackData["acciones"]);
				}
				$log->resultado("ok", true);
				if( isset($_REQUEST["inline"]) ){ die("ok"); }
				$template->display("succes_form.tpl");
				exit;
			}

		} catch(Exception $e){	
			$log->resultado("error ".$e->getMessage(), true);		
			if( isset($_REQUEST["inline"]) ){ die($e->getMessage()); }
			
			$template->assign("error", $e->getMessage());		
		}		
	}


	// Simplificar la interfaz
	if (isset($_GET['edit']) && $edit = $_GET['edit']) {
		$campos = $elemento->getPublicFields(true, empresa::PUBLIFIELDS_MODE_EDIT, $usuario);

		if (isset($campos[$edit])) {
			$reduced = new FieldList;
			$reduced[$edit] = $campos[$edit];

			$template->assign("tip", array(
				"innerHTML" => "completa_campo_{$edit}_continuar"
			));

			$template->assign("campos", $reduced);	
		}
		
	}
	
	$template->assign ("titulo","titulo_modificar");
	$template->assign ("boton","boton_modificar");
	$template->assign ("elemento", $elemento);
	$template->assign ("width", "650px");

	if ($comefrom) $template->assign ("comefrom", $comefrom);

	$template->assign ("data", array("m" => $_REQUEST["m"]));

	$templatefile = DIR_TEMPLATES . "ficha/{$elemento->getType()}.tpl";
	if (is_readable($templatefile)) {
		$template->display("ficha/{$elemento->getType()}.tpl");
	} else {
		$template->display("form.tpl");
	}

?>
