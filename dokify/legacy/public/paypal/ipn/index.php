<?php
require_once("../../config.php");

$app = Dokify\Application::getInstance();
$messageLog = "paypal request ";

if (isset($_POST["custom"])) {
    $messageLog .= "({$_POST["custom"]}) ";
    $paypal = paypal::instanceFromCustom($_POST["custom"]);

    if (!$paypal) {
        $app['log']->addInfo("{$messageLog}- no invoice", $_REQUEST);

        // this could happens if a Canceled_Reversal is done before a Reversed txn
        error_log("Custom {$_POST["custom"]} not found");
        header("HTTP/1.0 500 Internal Server Error");
        exit;
    }
} else {
    $app['log']->addInfo("{$messageLog}- no custom", $_REQUEST);
    header("HTTP/1.0 500 Internal Server Error");
    exit;
}

if (isset($_REQUEST["payment_status"])) {
    $messageLogExtra = "- no payment status";

    switch ($_REQUEST["payment_status"]) {
        case 'Refunded': //we refund the payment
            $app['log']->addInfo("{$messageLog}- refunded payment", $_REQUEST);
            exit;

        case 'Reversed': //We do not have the funds anymore, we need to cancel the payment.
            if (isset($_REQUEST["parent_txn_id"]) && ($txnParent = $_REQUEST["parent_txn_id"])) {
                $app['log']->addInfo("{$messageLog}- reversed payment", $_REQUEST);

                $log = log::singleton();
                $log->info("paypal", "Recibido cancelacion pago $txnParent", "custom: " . $_REQUEST["custom"], "Ok", true);

                $paypalConcept = paypal::deleteTransactions($txnParent);
            } else {
                $app['log']->addInfo("{$messageLog}- reversed payment (no parent_txn_id)", $_REQUEST);
            }
            exit;

        case 'Completed':
        case 'Canceled_Reversal': //Paypal cancel the reversal, we have the funds again in our account.
            try {
                if (($result = paypal::isIPNVerified()) === true) {
                    $app['log']->addInfo("{$messageLog}- completed/canceled payment (to save)", $_REQUEST);
                    $paypal->saveTransaction("ipn", $_POST);
                    exit;
                } else {
                    $messageLogExtra = "- completed/canceled payment (IPN not verified)";
                    error_log("IPN no valido " . @$_POST["txn_id"] . " - $result");
                }
            } catch (Exception $e) {
                $messageLogExtra = "- completed/canceled payment (EXCEPTION)";
                error_log($e->getMessage());
            }
            break;

        default:
            $app['log']->addInfo("{$messageLog}- unknown payment status", $_REQUEST);
            exit;
    }

}

$app['log']->addInfo("{$messageLog}{$messageLogExtra}", $_REQUEST);
header("HTTP/1.0 500 Internal Server Error");
