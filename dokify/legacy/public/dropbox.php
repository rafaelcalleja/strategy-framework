<?php

	require 'api.php';


   	$redirectUri = "https://192.168.17.112/dropbox-done";
   	$csrfTokenStore = new Dropbox\ArrayEntryStore($_SESSION, 'dropbox_auth_csrf_token');
	$appInfo = Dropbox\AppInfo::loadFromJsonFile("/home/jose/dropbox-app.json");
	dump($appInfo); exit;
	$webAuth = new Dropbox\WebAuth($appInfo, "dokify", $redirectUri, $csrfTokenStore);

	$authorizeUrl = $webAuth->start();
	header("Location: $authorizeUrl");
	exit;
	// echo "1. Go to: " . $authorizeUrl . "\n";
	// echo "2. Click \"Allow\" (you might have to log in first).\n";
	// echo "3. Copy the authorization code.\n";
	// $authCode = \trim(\readline("Enter the authorization code here: "));

	list($accessToken, $dropboxUserId) = $webAuth->finish($authCode);
	print "Access Token: " . $accessToken . "\n";

	$dbxClient = new Dropbox\Client($accessToken, "PHP-Example/1.0");
	$accountInfo = $dbxClient->getAccountInfo();
	print_r($accountInfo);