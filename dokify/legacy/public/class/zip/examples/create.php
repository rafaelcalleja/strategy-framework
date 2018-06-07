<?php
error_reporting(E_ALL);
if (!extension_loaded('zip')) {
    dl('zip.so');
}
$thisdir = dirname(__FILE__);
$filename = $thisdir. "/prueba112.zip";
if( file_exists($filename) ){
	unlink($filename);
}
$zip = new ZipArchive();

if (!$zip->open($filename, ZIPARCHIVE::CREATE)) {
	exit("cannot open <$filename>\n");
} else {
	//echo "file <$filename> OK\n";
}

$zip->addFromString("testfilephp.txt" . time(), "#1 This is a test string added as testfilephp.txt.\n");
$zip->addFromString("testfilephp2.txt" . time(), "#2 This is a test string added as testfilephp2.txt.\n");
$zip->addFile($thisdir . "/too.php","/testfromfile.php");
//echo "numfiles: " . $zip->numFiles . "\n";
//echo "status:" . $zip->status . "\n";
$zip->close();

unset($zip);
			$len = filesize($filename);
			//Begin writing headers
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: public");
			header("Content-Description: File Transfer");

			//Use the switch-generated Content-Type
			header("Content-Type: application/zip");

			//Force the download
			$header="Content-Disposition: attachment; filename=\"".basename($filename)."\";";

			header( $header );
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".$len);
			@readfile($filename);


