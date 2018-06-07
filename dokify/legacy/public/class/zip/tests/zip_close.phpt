--TEST--
zip_close() function
--SKIPIF--
<?php
/* $Id: zip_close.phpt 260064 2008-05-20 21:06:03Z pajoye $ */
if(!extension_loaded('zip')) die('skip');
?>
--FILE--
<?php
$zip = zip_open(dirname(__FILE__)."/test_procedural.zip");
if (!is_resource($zip)) die("Failure");
zip_close($zip);
echo "OK";

?>
--EXPECT--
OK
