<?php
	require_once("../config.php");
	
	if (isset($_POST["custom"])) {
		$paypal = paypal::instanceFromCustom($_POST["custom"]);
		if (!$paypal) {
			header("Location: /app/payment/error?error=something_went_wrong"); exit;
		}
	} else {
		header("Location: /app/payment/error?error=something_went_wrong"); exit;
	}

	try {
		header("Location: /app/payment/". $paypal->getPathType() ."/done?warning=true");
	}catch(\Dokify\Exception\TransactionException $e) {
		error_log($e->getMessage());
		header("Location: /app/payment/". $paypal->getPathType() ."/done");

	}catch(Exception $e) {
		error_log($e->getMessage());
		header("Location: /app/payment/". $paypal->getPathType() ."/done?error=noaction");
	}

?>
