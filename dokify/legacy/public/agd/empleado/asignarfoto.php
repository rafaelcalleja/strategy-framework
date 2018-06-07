<?php
	include( "../../api.php");
	
	$template = new Plantilla();
	
	// ----- Buscamos el uid seleccionado
	$idElemento = obtener_uid_seleccionado();
	if (!is_numeric($idElemento)){ dump("uid"); exit; }

	//------ Sacar el modulo seleccionado
	$modulo = obtener_modulo_seleccionado();
	if (!$modulo) { dump("modulo"); exit; }

	$objeto = new $modulo($idElemento, false);
	
	if (isset($_REQUEST["send"])) {
		
		try {
			if (isset($_SESSION["FILES"])) {
				$files = unserialize($_SESSION["FILES"]);
				$fileName = $files['archivo']['tmp_name'];

				if ($files["archivo"]["error"]) {
					throw new Exception("error_sin_archivo");
				}

				$estado = $objeto->putPhoto($fileName, @$_REQUEST["size"]);
			}
				
			if ($estado){
			 	$template->assign("elemento", $objeto);
				$template->assign("usuario", $usuario);
				$template->display( "ficha_elemento.tpl");
				exit;
			} else {
				throw new Exception($estado);
			}
			
		} catch(Exception $e){
			$template->assign("error", $e->getMessage());
		}
		
	}
	$template->assign("elemento", $objeto);
	$template->display("asignar_foto.tpl");
?>
