<?php

	require '../../config.php';


	if ($args = $_SERVER['argv']) {
		$number = @$args[1];
		$text = @$args[2];

		if (!is_numeric($number)) die("Especifica el numero de telefono como primer parametro\n");
		if (!strlen($text)) die("Especifica el texto como segundo parametro\n");
		//$number = '675991565';
		//$text = "Hola, le llamamos de Dokifai por que se ha solicitado un cambio de empresa.";
		//$confirm = "Pulsa 1 para confirmar el cambio o cuelga para cancelar";

		if ($confirm = @$args[3]) {
			$cid = c2c::confirm($number, $text, $confirm);
		} else {
			$cid = c2c::call($number, $text);
		}

		if ($cid) {
			print $cid;
			exit(0);
		} else {
			exit(1);
		}
	}