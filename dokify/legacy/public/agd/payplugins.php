<?php
	include_once "../api.php";

	$template = new Plantilla();

	if (isset($_REQUEST['plugin'])) {
		$template->assign('infostring', $template("pago_plugin_". $_REQUEST['plugin']));
		$template->display("paypal/payplugins.tpl");
	}