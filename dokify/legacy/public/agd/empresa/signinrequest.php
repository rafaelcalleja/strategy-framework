<?php

include("../../api.php");

$template = Plantilla::singleton();

if ($uid = obtener_uid_seleccionado()) {
    $signInRequest = new signinRequest($uid);
} else {
    die("Inaccesible");
}

if (isset($_REQUEST["send"])) {

    try {
        if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "enviar") {
            $alreadyInvited = false;
            if ($signInRequest->getInvitationEmail() != $_REQUEST["newemail"]) {
                $conditions =  array("email" => $_REQUEST["newemail"]);
                $alreadyInvited = signinRequest::checkInvitationCompany($signInRequest->getCompany()->getUID(), $conditions);
            }

            if (!$alreadyInvited) {
                if ($newemail = trim($_REQUEST["newemail"])) {
                    $statusUpdate = $signInRequest->changeEmailInvitation($newemail);
                    if (is_bool($statusUpdate) && !$statusUpdate) {
                        $template->assign('signInRequest', $signInRequest);
                        $template->assign("error", $template->getString("error_texto"));
                        $template->display("empresa/reenviarInvitacion.tpl");
                        exit;
                    }
                }
                $signInRequest->update(array("deadline_ok" => "NULL"));
                $app = \Dokify\Application::getInstance();
                $invitationEvent = new \Dokify\Application\Event\Company\Invitation\Store($signInRequest->asDomainEntity());
                $app->dispatch(\Dokify\Events\Company\InvitationEvents::POST_COMPANY_INVITATION_UPDATE, $invitationEvent);
                $signInRequest->update(array("state" => signinRequest::STATE_PENDING, "date" => time()));
                $template->display("succes_form.tpl");
                exit;
            } else {
                $template->assign('alreadyInvited', true);
            }
        }
    } catch (Exception $e) {

        if ($signInRequest->getState() != signinRequest::STATE_ACCEPTED) {
            $template->assign("activeInvitation", true);
        }
        $template->assign("error", $e->getMessage());
        $template->assign('inviterCompany', $signInRequest->getCompany());
        $template->assign('signInRequest', $signInRequest);
        $template->display("empresa/reenviarInvitacion.tpl");
        exit;
    }
}

if (isset($_REQUEST["action"]) && (trim($_REQUEST["action"]) == "descartar" || trim($_REQUEST["action"]) == "enviar")) {
    $action = $_REQUEST["action"];
}

if (!$usuario->accesoAccionConcreta("signinRequest", $action)) {
    die("Inaccesible");
}

switch ($action) {
    case 'descartar':
        if (isset($_GET["confirmed"])) {
            $statusChanged = $signInRequest->changeStateInvitation(signinRequest::STATE_DISCARD);
            if ($statusChanged) {
                $template->display("succes_form.tpl");
            } else {
                $template->assign("error", $template->getString("error_texto"));
            }
        } else {
            $template->assign("action", $action);
            $template->display("borrarelemento.tpl");
            exit;
        }
        break;
    case 'enviar':
        if ($signInRequest->getState() != signinRequest::STATE_ACCEPTED) {
            $template->assign("activeInvitation", true);
        }


        $template->assign('inviterCompany', $signInRequest->getCompany());
        $template->assign('signInRequest', $signInRequest);
        $template->display("empresa/reenviarInvitacion.tpl");
        break;
}
