<?php
	require_once __DIR__ . '/../../config.php';

	if (!$uid = @$_SERVER["argv"][1]) {
		die("We need a document uid");
	}

	if (!$file = @$_SERVER["argv"][2]) {
		die("We need a file");
	}

	if (!is_readable($file)) {
		die("We need a readable file");
	}

	// fileId is optional
	if ($fileId = @$_SERVER["argv"][3]) {
		$fileId = new fileId($fileId);
	}



	// Always try to extract words, ocr only on small files
	$size = filesize($file);
	$ocr = $size < (3*1024*1024);

	
	$words = pdfHandler::getPlainWords($file, NULL, $ocr);
	$doc = new documento($uid);

	$doc->saveWords($words, $fileId);

	print count($words) . " words saved!\n";