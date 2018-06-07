<?php
	include("../api.php");
	
	$tpl = new Plantilla();

	$tpl->assign("paypal", new paypalLicense);
	$tpl->display("asistente/inicio.tpl");
?>
