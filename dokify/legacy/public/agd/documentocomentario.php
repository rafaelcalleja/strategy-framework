<?php

require __DIR__ . '/../api.php';

$template = new Plantilla();
if (isset($_REQUEST["edit"])) {
    $idCommentEdit = $_REQUEST["edit"];
    if( !strlen($_REQUEST["comentario"]) || !isset($_REQUEST["comentario"])) {
        header("Content-type: application/json");
        print json_encode(array("alert" => $template("comment_cannot_be_empty"), "refresh" => 1));
    } else {
        $commentId = new commentId($idCommentEdit, $_REQUEST["m"]);
        $commenter = $commentId->getCommenter();
        if ($commenter && $usuario->compareTo($commenter) && $commentId->editComment($_REQUEST["comentario"])) {
            echo 1;
        } else {
            echo 0;
        }
    }
    exit;
}

if (isset($_REQUEST["delete"])) {
    $idCommentDelete = $_REQUEST["delete"];
    $commentId = new commentId($idCommentDelete, $_REQUEST["m"]);
    $commenter = $commentId->getCommenter();
    if ($commenter && $usuario->compareTo($commenter) && $commentId->deleteComment()) {
        header("Content-type: application/json");
        print json_encode(array("refresh" => 1, "nofollow" => 1));
    } else {
        header("Content-type: application/json");
        print json_encode(array("alert" => $template("cannot_delete_comment"), "refresh" => 1));
    }
    exit;
}


if (!$module = obtener_modulo_seleccionado()) {
    die("Error: Module is undefined!");
}

if (!in_array($module, solicitable::getModules())) {
    die("Error: Invalid module!");
}

if (!isset($_REQUEST["o"]) || !$itemId = $_REQUEST["o"]) {
    die("Object id is undefined");
}

if (!$uid = obtener_uid_seleccionado()) {
    die("Document id is undefined");
}

// --- instance objects
$element = new $module($itemId);
$document = new documento($uid, $element);
$req = (isset($_REQUEST['req']) && is_numeric($_REQUEST['req'])) ? new solicituddocumento($_REQUEST['req']) : NULL;

// --- security access
$hasAccess = $usuario->accesoElemento($element) || ($usuario->esValidador() && isset($_REQUEST["offset"]));
if (!$hasAccess) {
    if ($usuario->esStaff()){
        if (!isset($_REQUEST["inline"]) && !isset($_REQUEST["edit"])) {
            $template->display("sin_acceso_perfil.tpl");
        }
    } else { die("Inaccesible"); }
}

$json = new jsonAGD();

// --- Comentarios del documento...
if ($req) {
    $solicitudes = new ArrayObjectList(array($req));
} else{
    $solicitudes = $document->obtenerSolicitudDocumentos($element, $usuario);
}

if (!count($solicitudes)) {
    $json->addData("alert", $template->getString("no_requirement_for_document"));

    $href = "#documentos.php?m=$module&poid=$itemId";
    $json->addData("action", array("go" => $href));
    $json->display();
    exit;
}

$reqType = new requirementTypeRequest($solicitudes, $element);

// --- fix a wrong date
if (isset($_REQUEST['fix']) && $fix = $_REQUEST['fix']) {
    $href = "updatedate.php?m={$element->getModuleName()}&o={$element->getUID()}&poid={$document->getUID()}&comefrom={$fix}";
    $json->addData("open", $href);

    $href = "#documentocomentario.php?m={$element->getModuleName()}&o={$element->getUID()}&poid={$document->getUID()}";
    $json->addData("action", array("go" => $href));
    $json->display();
    exit;
}


if (isset($_REQUEST["event"]) && $event = $_REQUEST["event"]) {
    switch ($event) {
        case 'watch':
            $usuario->wacthThread($element, $solicitudes);
            print json_encode(array("refresh" => true, "nofollow" => 1));
            break;
        case 'unwatch':
            $usuario->unWatchThread($element, $solicitudes);
            print json_encode(array("refresh" => true, "nofollow" => 1));
            break;
    }

    exit;
}

if (isset($_REQUEST["offset"])) {
    $comments = $reqType->getComments($usuario, false, false, $_REQUEST["offset"]);
    if (!count($comments)) exit;

    $template->assign("element", $element);
    $template->assign("document", $document);
    $template->assign("moduleName", $module);
    $template->assign("reqs", $solicitudes);

    $html = "<div class=\"closed-banner\"></div>";
    foreach ($comments as $comment) {
        $template->assign("comment", $comment);
        $html .= $template->getHTML('comment/comment.tpl');
    }

    $newOffset = $_REQUEST["offset"] + 1;
    $commentsRemaining = $reqType->getComments($usuario, false, false, $newOffset);

    if (count($commentsRemaining)) {
        $template->assign("offset", $newOffset);
        $html .= $template->getHTML('loadmore.tpl');
    }
    print $html;
    exit;
}

$comments = $reqType->getComments($usuario, false, false);
// Parametros actuales para usar en redirecciones
// $currentParams = "?m={$module}&p=0&poid={$uid}&o={$itemId}";


// Envio de nuevo comentario
if (isset($_REQUEST["send"])) {
    if (!strlen($_REQUEST["comentario"]) || !isset($_REQUEST["comentario"])) {
        $template->assign("error", "error_completar_todos_campos" );
    } else {
        $comentario = @$_REQUEST["comentario"];
        $solicitudes = obtener_uids_seleccionados();
        $solicitudes = $solicitudes->toObjectList("solicituddocumento");

        $reqType = new requirementTypeRequest($solicitudes, $element);
        if ($comentario && $reqType) {
            $replyId = (isset($_REQUEST["replyId"]) && $_REQUEST["replyId"]) ? $_REQUEST["replyId"] : NULL;
            $commentId = $reqType->saveComment($comentario, $usuario, comment::NO_ACTION, watchComment::AUTOMATICALLY_COMMENT, $replyId);

            $app = \Dokify\Application::getInstance();
            $event = new Dokify\Application\Event\CommentId\Store($commentId);
            $app->dispatch(Dokify\Events\CommentIdEvents::POST_COMMENTID_STORE, $event);

        }

        header("Content-type: application/json");
        print json_encode(array("refresh" => 1, "nofollow" => 1));
        exit;
    }
}


$watchingComment = $usuario->watchingThread($element, $solicitudes);
$commentsRemaining = $reqType->getComments($usuario, false, false, 1);


$template->assign("watchingComment", $watchingComment);
$template->assign("element", $element);
$template->assign("commentsRemaining", $commentsRemaining);
$template->assign("comments", $comments);
$template->assign("document", $document);
$template->assign("moduleName", $module);
$template->assign("offset", 1);
$template->assign("req", $req);
$template->assign("reqs", $solicitudes);
$HTML = $template->getHTML("comment/iface.tpl");



$titleString = $element->getUserVisibleName();
$itemTitle = array(
    "innerHTML" => string_truncate($titleString, 30),
    "href" => $element->obtenerUrlFicha(),
    "img" => $element->getStatusImage($usuario),
    "className" => "box-it"
);
if (strlen($titleString) > 30) $itemTitle['title'] = $titleString;

$json->informacionNavegacion(
    $template("inicio"),
    $itemTitle,
    array("innerHTML" => $template('documentos'), "href" => "#documentos.php?m={$module}&poid={$element->getUID()}"),
    $template->getString("comentarios")
);

$json->establecerTipo("simple");
$json->nuevoSelector("#main", $HTML);
$json->menuSeleccionado($module);

$json->display();