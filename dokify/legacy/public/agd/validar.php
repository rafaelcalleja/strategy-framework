<?php

require_once("../api.php");

db::singleton()->doctrineConnection()->connect('master');

//----- INSTANCIAMOS LA PLANTILLA
$template = new Plantilla();

$idSeleccionado = obtener_uid_seleccionado();
if (!is_numeric($idSeleccionado) || !isset($_REQUEST["o"])) {
    exit;
}

//----- INSTANCIAMOS EL OBJETO LOG
$log = log::singleton();
$action = isset($_REQUEST["validate"]) ? $_REQUEST["validate"] : "validar";
$force = isset($_REQUEST["fileId"]) ? true : false;

if ('audit_ok' === $action) {
    $validation = new validation($_REQUEST["validation"]);
    $validation->auditOk($usuario);

    header("Content-type: application/json");
    print json_encode(array("refresh" => 1, "iface" => "validation", "top" => true));
    exit;
} elseif ('audit_wrong' === $action) {
    $validation = new validation($_REQUEST["validation"]);
    $validation->auditWrong($usuario);

    header("Content-type: application/json");
    print json_encode(array("refresh" => 1, "iface" => "validation", "top" => true));
    exit;
}

//----- BUSCAMOS NUESTRO ELEMENTO ACTUAL
$modulo = obtener_modulo_seleccionado();
$elementoActual = new $modulo( $_REQUEST["o"] );
$documento = new documento($idSeleccionado, $elementoActual);

if (!$usuario->accesoElemento($elementoActual) && !$usuario->esValidador()) {
    $template->assign("objeto", $elementoActual);

    if (!$force) {
        $template->end($usuario); // acabará la ejecución del script o enviará mensaje de aviso
    }
}

//----- DEFINIMOS EL EVENTO PARA EL LOG
$log->info($modulo, "$action documento ".$documento->getUserVisibleName(), $elementoActual->getUserVisibleName());

//----- COMPROBAMOS QUE EL MODULO SEA VÁLIDO
if (!$elementoActual instanceof solicitable) {
    $log->nivel(5);
    $log->resultado("modulo no valido", true);
    die("Error: Modulo no especificado!");
}

// --- only one request
$req = (isset($_REQUEST['req']) && is_numeric($_REQUEST['req'])) ? new solicituddocumento($_REQUEST['req']) : null;


if (isset($_REQUEST["send"])) {
    $companies = array();

    if ($anexos = obtener_uids_seleccionados()) {
        array_walk($anexos, function(&$uid, $i, $elementoActual) {
            $uid = new anexo($uid, $elementoActual);
        }, $elementoActual);

        $anexos = new ArrayAnexoList($anexos);

    } elseif (isset($_REQUEST["fileId"])) {
        if (isset($_REQUEST["companies"]) && is_array($_REQUEST["companies"]) && isset($_REQUEST["fileId"]) && isset($_REQUEST["module"])) {
            $companies = new ArrayIntList($_REQUEST["companies"]);
        } else {
            $companies = new ArrayIntList();
        }

        $fileId = new fileId($_REQUEST["fileId"], $_REQUEST["module"]);
        $anexos = $fileId->getAttachments($usuario->getCompany(), false, $companies, false, $force);
        $others = $fileId->getAttachments($usuario->getCompany(), true, $companies, false, $force);
        if ($anexos && $others) {
            $anexos = $anexos->merge($others)->unique();
        } elseif (!$anexos && $others) {
            $anexos = $others;
        }
    }

    if (count($anexos) && is_traversable($anexos)) {
        try {
            $empresasCliente = array();
            $companyUser = $usuario->getCompany();

            if (isset($_REQUEST['argument']) && is_numeric($_REQUEST['argument']) && $validationArgument = new ValidationArgument($_REQUEST['argument'])) {
                $status = $validationArgument->getDocumentStatus();
            } else {
                $validationArgument = null;
                $status = ($action=='validar') ? documento::ESTADO_VALIDADO : documento::ESTADO_ANULADO;
            }

            if (isset($fileId) && $fileId instanceof fileId) {
                $empresaSolicitante = $fileId->getCompanyApplicant();
            }

            $statusAnexo = $anexos->foreachCall('getStatus');
            $renovationAnexo = $anexos->foreachCall('isRenovation');

            $errors = 0;

            if (!$statusAnexo->contains(documento::ESTADO_ANEXADO) && !$renovationAnexo->contains(true)) {
                foreach ($anexos as $anexo) {
                    $oldStatus = $anexo->getStatus();
                    $errorValidation = $oldStatus == documento::ESTADO_VALIDADO && $status == documento::ESTADO_ANULADO;
                    $errorRejection = $oldStatus == documento::ESTADO_ANULADO && $status == documento::ESTADO_VALIDADO;
                    if ($errorValidation || $errorRejection) {
                        $errors = $anexo->getValidationErrors() + 1;
                        $anexo->update(array("validation_errors" => $errors));
                    }
                }
            }

            $owners = $anexos->foreachCall('obtenerDocumentoAtributo')->foreachCall('getCompany');
            $companiesToValidate = $companyUser->getValidationCompanies();

            $allow = $usuario->esStaff() && $force;
            if (!$usuario->accesoElemento($elementoActual) && !$owners->match($companiesToValidate) && !$allow) {
                die("Inaccesible");
            }

            $updateTimeAnexos = array();
            foreach ($anexos as $anexo) {
                $updateTimeAnexos[$anexo->getUID()] = $anexo->getUpdateDate();
            }

            $relativeComment =false;
            $updateDate = false;
            $date = $_REQUEST['date'] ?? '';
            $duration = $_REQUEST['duration'] ?? null;
            if (true === isset($_REQUEST['no-expiring'])) {
                $expirationDate = 'no_caduca';
            } else {
                $expirationDate = $_REQUEST['expiration_date'] ?? '';
            }

            if ('' !== $date || null !== $duration || '' !== $expirationDate) {
                if (documento::ESTADO_VALIDADO !== $status) {
                    header("Content-type: application/json");
                    print json_encode([
                        "refresh" => true,
                        "alert" => $template("No está permitido actualizar la fecha si el documento va a ser anulado."),
                    ]);
                    exit;
                }

                $attachment = $anexos->getFirst();
                $requirementRequest = $attachment->getSolicitud();
                $requirement = $requirementRequest->obtenerDocumentoAtributo();
                $manualExpiring = (bool) $requirement->obtenerDato('caducidad_manual');
                $uploadUser = $attachment->getUploaderUser();
                $uploaderUserTimeZone = null;

                if (false !== $uploadUser) {
                    $uploaderUserTimeZone = $uploadUser->getTimeZone();
                }

                if ('' === $expirationDate && true === $manualExpiring) {
                    $expireDate = $attachment->getExpirationTimestamp($uploaderUserTimeZone);
                    if (0 == $expireDate) {
                        $expirationDate = 'no_caduca';
                    } else {
                        $expirationDate = date('d/m/Y', $expireDate);
                    }
                }

                $updateDate = $fileId->updateDate($date, $expirationDate, $usuario, null, $duration, $anexos);

                if (true === $updateDate) {
                    $validationArgument = new ValidationArgument(ValidationArgument::FIXED_DATE);
                } else {
                    header("Content-type: application/json");
                    print json_encode([
                        "refresh" => true,
                        "alert" => $template($updateDate),
                    ]);
                    exit;
                }
            }

            if ($updated = $documento->updateStatus($anexos, $status, $usuario, $validationArgument)) {
                $delayedStatus = null;
                $comment = (isset($_REQUEST["comentario"])) ? $_REQUEST["comentario"]: false;
                $solicitudes = $anexos->foreachCall('getSolicitud');
                $delayedStatusAnexos = array_filter($anexos->foreachCall('getDelayedStatus')->getArrayCopy());
                if (count($delayedStatusAnexos)) {
                    $delayedStatus = reset($delayedStatusAnexos);
                }
                //Inroducimos action de validar
                $reqType = new requirementTypeRequest($solicitudes, $elementoActual);
                $reply = $_REQUEST["attach_comment_id"] ?? false;
                $commentId = $reqType->saveComment($comment, $usuario, $status, watchComment::AUTOMATICALLY_VALIDATION, $reply, $validationArgument, $delayedStatus);

                $app = \Dokify\Application::getInstance();
                $event = new Dokify\Application\Event\CommentId\Store($commentId);
                $app->dispatch(Dokify\Events\CommentIdEvents::POST_COMMENTID_STORE, $event);

                if ($force && $errors > 0) {
                    //Means it was an error, we notify the change to the validator
                    $data['html'] = array('#validar-documento' => $template->getHTML("validation/update.tpl"));
                    $data["iface"] = "validation";
                    header("Content-type: application/json");
                    print json_encode($data);
                    exit;
                }

                $anexo = reset($anexos);
                if (!isset($fileId)) {
                    $fileId = $anexo->getFileId();
                }

                foreach ($anexos as $anexo) {
                    $docAtributo = $anexo->obtenerDocumentoAtributo();
                    $empresaSolicitante = $docAtributo->getCompany();
                    $typeDocument = ($docAtributo->getIsCutom()) ? documento_atributo::TEMPLATE_TYPE_CUSTOM : documento_atributo::TEMPLATE_TYPE_GENERAL;
                    $language = $anexo->obtenerLanguage();
                    $partner = $empresaSolicitante->getPartner($language, $typeDocument);
                    if (isset($updateTimeAnexos[$anexo->getUID()])) {
                        $updateDate = $updateTimeAnexos[$anexo->getUID()];
                    }
                    $isUrgent = $anexo->isUrgent();
                    $diffTime = abs(time() - $updateDate);
                    $anexo->update(array("time_to_validate" => $diffTime, "screen_uid_usuario" => "NULL", "screen_time_seen" => 0));

                }

                // ------- no se registra la "validation" si el documento no estaba previamente anexado (si por ejemplo anulamos despues de validar)
                $statusAnexo = $anexos->foreachCall('getStatus');

                // set as full valid attachment if the document is standard and is valid
                if ($documento->isStandard() && $status === documento::ESTADO_VALIDADO) {
                    // if no delayedStatus or if delayedStatus is rejected
                    if ($delayedStatus === null || $delayedStatus->getReverseStatus() != documento::ESTADO_ANULADO) {
                        $update = ["full_valid" => "1"];
                        $anexos->foreachCall("update", array($update));
                    }
                }

                if ($status === documento::ESTADO_VALIDADO) {
                    $validationAction = \Dokify\Application\Event\Validation::ACTION_VALIDATE;
                } elseif ($status === documento::ESTADO_ANULADO) {
                    $validationAction = \Dokify\Application\Event\Validation::ACTION_REJECT;
                }

                $validationEvent = new \Dokify\Application\Event\Validation(
                    $validationAction,
                    $anexos->asDomainEntity(),
                    $companyUser->asDomainEntity(),
                    $usuario->asDomainEntity()
                );

                $app = \Dokify\Application::getInstance();

                switch ($elementoActual->getRouteName()) {
                    case 'company':
                        $app->dispatch(\Dokify\Events::POST_COMPANY_ATTACHMENT_VALIDATION, $validationEvent);
                        break;
                    case 'employee':
                        $app->dispatch(\Dokify\Events::POST_EMPLOYEE_ATTACHMENT_VALIDATION, $validationEvent);
                        break;
                    case 'machine':
                        $app->dispatch(\Dokify\Events::POST_MACHINE_ATTACHMENT_VALIDATION, $validationEvent);
                        break;
                }

                // check if any of the attrs is ceritification
                foreach ($anexos as $anexo) {
                    $attr = $anexo->obtenerDocumentoAtributo();

                    if ($attr->isCertification()) {
                        $elementoActual->actualizarSolicitudDocumentos();
                        break;
                    }
                }


                $log->resultado("ok", true);
                if (isset($_REQUEST["type"]) && $_REQUEST["type"] == "async-form") {
                    header("Content-type: application/json");
                    print json_encode(array("refresh" => 1, "iface" => "validation", "top" => true));
                } elseif (isset($_REQUEST["type"]) && $_REQUEST["type"] == "modal") {
                    $template->display("succes_form.tpl");
                }
                exit;
            } else {
                $template->assign("error", "ningun_cambio");
            }
        } catch (Exception $e) {
            $template->assign("error", $e->getMessage());
        }
    } else {
        if (isset($_REQUEST["type"]) && $_REQUEST["type"] == "modal") {
            $template->assign("error", "sin_seleccionar");
        }
    }
}

$template->assign("elemento", $elementoActual);
$template->assign("usuario", $usuario);
$template->assign("documento", $documento);
$template->assign("selectedRequest", $req);
$status = ($action=='validar') ? $template->display("validar.tpl") : $template->display("anular.tpl");
