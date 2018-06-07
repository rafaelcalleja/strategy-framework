<?php
	require("../api.php");
	if( !isset($_GET["t"]) ){ exit; }

	$plantillaEmail = plantillaemail::instanciar( $_GET["t"] );

	if( $plantillaEmail instanceof plantillaemail ){
		$template = new Plantilla();
		$fileContent = $plantillaEmail->getFileContent($usuario->getCompany());
		if (mb_detect_encoding($fileContent, "UTF-8", true) != "UTF-8") {
			$fileContent = utf8_encode($fileContent);
		}

		$template->assign("html",  $fileContent);
		$template->display("simplehtml.tpl");
	} else {
		$template = new Plantilla();
		$template->assign("message","No se puede leer una plantilla en $templateDIR");
		$template->display("error.tpl");
	}
?>
