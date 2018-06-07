<?php
	require_once("../api.php");


	$template = new Plantilla();


	//Elemento y documento actual
	if( $uid = obtener_uid_seleccionado() ){
		$empresa = new empresa($uid);
		if( !$usuario->accesoElemento($empresa) ){
			if( $usuario->esStaff() ){
				if( !isset($_REQUEST["inline"]) ) $template->display("sin_acceso_perfil.tpl");
			} else { 
				$template->assign("objeto", $empresa);
				$template->display("sin_acceso_perfil.tpl"); 
				exit;
			}
		}

		$inTrash = $usuario->esStaff() ? null : false;
		$empresasSuperiores = $empresa->obtenerEmpresasSuperiores($inTrash, $usuario);

		$info = array();
		foreach($empresasSuperiores as $superior){
			if( $usuario->accesoElemento($superior) ){
				$info[] = array("" => $superior->obtenerUrlFicha($superior->getUserVisibleName()) );
			} else {
				$info[] = array("" => "<span class='light'>{$superior->getUserVisibleName()}</span>" );
			}
		}
		$template->assign("array", $info );

		echo "<div class='extended-cell-info' style='background-color:#fff;'><div>". $template->getHTML("functions/array2table.tpl") ."</div></div>";
	}

?>
