<?php
include_once( "../../../api.php");
$template = Plantilla::singleton();
// $documentoAtributo = new documento_atributo(obtener_uid_seleccionado());
// $documento = documento::instanceFromAtribute(obtener_uid_seleccionadoseleccionado());

// if ( !isset($_REQUEST["contenido"]) || !trim($_REQUEST['contenido']) ) {
// 	die("Error, no hay nada que guardar"); 
// }

if (!@$_REQUEST['send']) {
	// si es otro tipo, preguntamos si quiere sustituir
	$template->assign("html", "Ya existe un archivo anexado para este documento. Si guarda una plantilla se sustituirá por el actual.");
	$template->display("confirmaraccion.tpl");
	exit;
} else {
	// cerramos el colorbox
	$template->assign('html','<script type="text/javascript" charset="utf-8">$.colorbox.close();</script>');
	$template->display("confirmaraccion.tpl");
}