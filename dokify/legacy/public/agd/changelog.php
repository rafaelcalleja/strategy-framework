<?php
	header('Content-Type: text/plain; charset=iso-8859-1');
	$fileContent = archivo::leer("change.log");
	print utf8_decode($fileContent);
?>
