<?php
	include( "../../api.php");
	
	$template = Plantilla::singleton();

	$json = new jsonAGD();


	$json->iface("analytics");
	//$json->establecerTipo("data");
	//$json->nombreTabla($m);
	$json->loadStyle( RESOURCES_DOMAIN . "/css/analytics/style.css");
	$json->nuevoSelector("#main", $template->getHTML("analytics/iface.tpl"));
	
	if( isset($_REQUEST["return"]) ){
		$json->addData("action", array(
			"go" => $_REQUEST["return"]
		));
	}

	$json->display();
?>
