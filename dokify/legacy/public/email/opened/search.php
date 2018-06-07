<?php

	require __DIR__ . '/../../../src/config.php';

	// error_log('open!');


	if (!$email = @$_GET['email']) {
		header("HTTP/1.1 404"); exit;
	}

	if (!$token = @$_GET['token']) {
		header("HTTP/1.1 404"); exit;
	}
	
	parse_str(base64_decode($token), $params);
	list($tokenEmail, $uid, $timestamp) = array_values($params);
	

	// --- same as encoded
	if ($email != $tokenEmail) {
		header("HTTP/1.0 500 Internal Server Error");
		exit;
	} 

	$notificationStatus = new SearchNotificationStatus($uid);
	$receipt = $notificationStatus->getReceipt();

	// --- same as saved
	if ($email != $receipt) {
		header("HTTP/1.0 500 Internal Server Error");
		exit;
	}


	$notificationStatus->setStatus(SearchNotificationStatus::STATUS_OPENED);