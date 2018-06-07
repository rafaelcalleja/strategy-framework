<?php

require __DIR__ . '/../api.php';

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

if (!$comefrom = obtener_comefrom_seleccionado()) {
    die("No source defined");
}

// --- instance objects
$element = new $module($itemId);
$document = new documento($uid, $element);
$template = Plantilla::singleton();


$arrayCampos = new FieldList();
$arrayCampos['nueva_fecha'] = new FormField(array( "tag" => "input", "type" => "text", "value" => "", "className" => "datepicker", "size" => 8));


$fix = elemento::factory($comefrom);
$download = "descargar.php?action=dl&m={$module}&o={$element->getUID()}";

if ($fix instanceof anexo) {
    $attachment = $fix;
    $attachments = new ArrayAnexoList(array($attachment));
} elseif ($fix instanceof commentId) {
    $attachments = $fix->getAttachments($usuario);

    if (count($attachments)) {
        // --- we only use one attachments in this context
        $attachment = $attachments[0];
    } else {
        $template->assign('message', 'error_ver_archivo');
        $template->display('error.tpl');
        exit;
    }
}

$rejected = $attachment->getStatus() == documento::ESTADO_ANULADO;
$validated = $attachment->getStatus() == documento::ESTADO_VALIDADO;

// --- form submit
if (isset($_REQUEST['send'])) {
    if ($date = $_REQUEST['date']) {
        foreach ($attachments as $attachment) {

            $delayedStatus = NULL;

            if ($validated) {
                $reverseStatus = documento::ESTADO_ANEXADO;
                $delayedStatus = new DelayedStatus($reverseStatus, DelayedStatus::defaultChangeDays());
            }

            $fileId = $attachment->getFileId();
            // --- actualizamos la fecha teniendo en cuenta el posible estado de renovacion
            $estado = $fileId->updateDate($date, NULL, $usuario, $delayedStatus);

            if ($estado === true) {

                $requirements = new ArrayObjectList(array($attachment->getSolicitud()));
                $reqType = new requirementTypeRequest($requirements, $element);
                $commentId = $reqType->saveComment(false, $usuario, comment::ACTION_CHANGE_DATE, watchComment::AUTOMATICALLY_CHANGE_DATE);
                $app = \Dokify\Application::getInstance();
                $event = new Dokify\Application\Event\CommentId\Store($commentId);
                $app->dispatch(Dokify\Events\CommentIdEvents::POST_COMMENTID_STORE, $event);

            } else {
                $template->assign('error', $estado);
            }
        }

        if (!$template->get_template_vars("error")) {

            $partners = $attachments->getPartners();
            $attachment = reset($attachments);

            if (count($partners) == 1) {
                $template->assign("partners", $partners);
                $partner = reset($partners);
                $AVGValidation = $partner->getAVGTimeValidate();
                $AVGValidation = ($AVGValidation == 0) ? false : $AVGValidation;
                $template->assign("AVGValidation", $AVGValidation);
            } else {
                if ($attachment->isRenovation()) {
                    $message = $template('waiting_renovation');
                } else {
                    $message = $template('waiting_for_applicant_validation');
                }
                $template->assign("waitingMessage", $message);
                $template->assign("partners", false);
            }

            $title = $template->getString("success_upload_document");
            $userTimeZone = $legacyUser->getTimeZone();
            $expiredDate = $attachment->getExpirationTimestamp($userTimeZone);

            // --- template variables
            $template->assign("successClass", "center");
            $template->assign("succes", $title);
            if ($attachment->isUrgent()) {
                $template->assign("isUrgent", true);
            } else {
                $template->assign("fileId", $attachment->getFileId()->getUID());
            }
            $template->assign("expiredDate", $expiredDate);
            $template->display('anexar_info.tpl');
            exit;
        }

    } else {
        $template->assign('error', 'seleccionar_fecha_emision');
    }
}

$tip = array(
    'innerHTML' => 'informacion_fecha_documento',
    'href' => ''
);

if ($rejected) {
    $template->assign('info', $template->getString('rejected_update_date'));
}

$template->assign('title', 'actualizar_fecha');
$template->assign('tip', $tip);
$template->assign('download', "descargar.php?action=dl&m={$module}&o={$element->getUID()}&oid={$attachment->getUID()}");
$template->assign('dateUpdated', $attachment->dateUpdated());
$template->display('updatedate.tpl');

