--TEST--
zip_open() function
--SKIPIF--
<?php
/* $Id: zip_open.phpt 260064 2008-05-20 21:06:03Z pajoye $ */
if(!extension_loaded('zip')) die('skip');
?>
--FILE--
<?php
$zip = zip_open(dirname(__FILE__)."/test_procedural.zip");

echo is_resource($zip) ? "OK" : "Failure";

?>
--EXPECT--
OK
