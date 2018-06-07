--TEST--
zip::close() function
--SKIPIF--
<?php
/* $Id: oo_close.phpt 260064 2008-05-20 21:06:03Z pajoye $ */
if(!extension_loaded('zip')) die('skip');
?>
--FILE--
<?php

$dirname = dirname(__FILE__) . '/';
$zip = new ZipArchive;
if (!$zip->open($dirname . 'test.zip')) {
	exit('failed');
}

if ($zip->status == ZIPARCHIVE::ER_OK) {
	$zip->close();
	echo "ok\n";
} else {
	echo "failed\n";
}
?>
--EXPECTF--
ok
