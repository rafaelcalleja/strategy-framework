<?php

	require 'api.php';

   	$redirectUri = "https://192.168.17.112/dropbox-done";
   	$csrfTokenStore = new Dokify\Dropbox\SessionStore('dropbox_auth_csrf_token');
	$appInfo = Dropbox\AppInfo::loadFromJsonFile("/home/jose/dropbox-app.json");
	$webAuth = new Dropbox\WebAuth($appInfo, "dokify", $redirectUri, $csrfTokenStore);

	try {
	   list($accessToken, $userId, $urlState) = $webAuth->finish($_GET);
	   assert($urlState === null);  // Since we didn't pass anything in start()
	}
	catch (Dropbox\WebAuthException_BadRequest $ex) {
	   error_log("/dropbox-auth-finish: bad request: " . $ex->getMessage());
	   // Respond with an HTTP 400 and display error page...
	}
	catch (Dropbox\WebAuthException_BadState $ex) {
	   // Auth session expired.  Restart the auth process.
	   error_log("Auth session expired: " . $ex->getMessage());
	   exit;
	   //header('Location: /dropbox');
	}
	catch (Dropbox\WebAuthException_Csrf $ex) {
	   error_log("/dropbox-auth-finish: CSRF mismatch: " . $ex->getMessage());
	   // Respond with HTTP 403 and display error page...
	}
	catch (Dropbox\WebAuthException_NotApproved $ex) {
	   error_log("/dropbox-auth-finish: not approved: " . $ex->getMessage());
	}
	catch (Dropbox\WebAuthException_Provider $ex) {
	   error_log("/dropbox-auth-finish: error redirect from Dropbox: " . $ex->getMessage());
	}
	catch (Dropbox\Exception $ex) {
	   error_log("/dropbox-auth-finish: error communicating with Dropbox API: " . $ex->getMessage());
	}

	// We can now use $accessToken to make API requests.
	$client = new Dropbox\Client($accessToken, "dokify");
	$accountInfo = $client->getAccountInfo();

	dump($accountInfo);