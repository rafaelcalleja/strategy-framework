<?php

    require __DIR__ . '/../config.php';

    use \Dokify\Imap\GmailClient;

    if (!isset($_POST['reply_plain']) || !isset($_POST["headers"])) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        exit;
    }


    $text = ($reply = trim($_POST['reply_plain'])) ? $reply : trim($_POST['plain']);
    $from = $_POST["headers"]["From"];
    $recipient = $_POST["headers"]["To"];

    // -- error code to cloudmailin
    $errorStop = $_SERVER['SERVER_PROTOCOL'] . ' 403 - Forbidden';
    $errorBounce = $_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error';
    $errorCode = strpos($from, "@dokify.net") ? $errorStop : $errorBounce;


    // --- limpiamos lo mejor que podemos el texto del email
    $text = GmailClient::cleanPlainText($text);

    if (!trim($text)) {
        $text = GmailClient::cleanPlainText(trim($_POST['plain']));
    }

    try {
        list ($commentHash, $usuario, $env, $target) = commentId::unmountEmailAddress($recipient);
    } catch (Exception $e) {
        error_log($e->getMessage());
        header($errorStop);
        exit;
    }
    
    $log = log::singleton();
    $log->info("comment", "Comment ID {$commentHash}, user: {$usuario->getUserName()}, ENV:{$env}, target: {$target}, from: {$recipient}", "{$recipient}");

    // -- reenviar la peticion al target adecuado
    if ($env == 'dev' && isset($target)  && $target != CURRENT_DOMAIN) {
        if (!$response = util::doPost($target . '/agd/comment.php', $_POST)) {
            header($errorCode);
            print "Error posting to ".$target;
        }

        exit;
    }


    if (!$usuario->exists()) {
        error_log("User {$usuario->getUID()} [". get_class($usuario) ."] not exits");
        $log->resultado("User {$usuario->getUID()} [". get_class($usuario) ."] not exits", true);
        header($errorCode);
        print "User {$usuario->getUID()} [". get_class($usuario) ."] not exits";
        exit;
    }


    if ($text && $commentHash) {
        try {
            $commentId = new commentId($commentHash);
            $newCommentId = $commentId->reply($text, $usuario, comment::ACTION_EMAIL);
            $log->resultado("Ok", true);
            if ($newCommentId) {
                $app = \Dokify\Application::getInstance();
                $event = new Dokify\Application\Event\CommentId\Store($newCommentId);
                $app->dispatch(Dokify\Events\CommentIdEvents::POST_COMMENTID_STORE, $event);

            } else {
                error_log("Cant post reply");
                $log->resultado("Cant post reply", true);
                header($errorStop);
                print "Cant post reply";
            }
        } catch (Exception $e) {
            $log->resultado($e->getMessage(), true);
            error_log($e->getMessage());
            header($errorCode);
            print "Unexpected error ocurred: ".$e->getMessage();
        }
   } else {
         $receivedText = ($reply = trim($_POST['reply_plain'])) ? $reply : trim($_POST['plain']);
        if (!$receivedText) {
            $errorMessage   = "No text from account {$recipient}";
            $log->resultado($errorMessage, true);
            print $errorMessage;
            error_log($errorMessage);
            header($errorStop);
        } else {
            print "No commentHash\n"; 
            print "Text:\n$receivedText";
            $log->resultado("No commentHash from account {$recipient}", true);
            header($errorCode);
        }
    }
        