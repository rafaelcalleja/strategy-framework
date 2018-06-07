<?php

// included from anexar.php and firmar.php

switch(@$_REQUEST['action']){
    // Queremos reanexar de un elemento - documento atributo otro(s) documentos atributos
    case "re":
        if( isset($_REQUEST['oid']) ){
            $anexo = new anexo($_REQUEST['oid'], $elementoActual);

            $fileinfo   = $anexo->getInfo();
            $fullValid  = $anexo->isFullValid();

            $timestamp = $anexo->getRealTimestamp($usuario->getTimezoneOffset());
            $fecha = array('dd'=> date('d', $timestamp), 'mm' => date('m', $timestamp), 'yyyy' => date('Y', $timestamp));

            $template->assign('fecha_reanexion', $fecha);
            $template->assign('elemento', $elementoActual );
            $template->assign('documento', $documento );
            $template->assign('anexosrc', $anexo );
            $template->assign('fileinfo', $fileinfo);

            if (isset($_REQUEST['send'])) {
                if ($uids = obtener_uids_seleccionados()) {
                    $solicitudes = $uids->toObjectList("solicituddocumento");

                    $file = DIR_FILES . $fileinfo["archivo"];
                    $hash = $fileinfo["hash"];

                    try {
                        // A documento "UPLOAD" le tenemos que pasar un string que represente nuestro archivo temporal, no se me ocurre form mejor que esta ahora mismo
                        $filename = archivo::getRandomName($fileinfo["nombre_original"]);
                        if( !archivo::tmp($filename, archivo::leer($file)) ) throw new Exception("error_leer_archivo", 1);

                        $fechaExpiracion = isset($_REQUEST["caducidad"]) ? $_REQUEST["caducidad"] : $fileinfo["fecha_expiracion"];
                        // RE-Anexamos el fichero temporal
                        $estado = $documento->upload($filename, $hash, $fileinfo["nombre_original"], @$_REQUEST["fecha"], $elementoActual, $solicitudes, $usuario, NULL, $fechaExpiracion, null, $anexo);
                        $anexos = new ArrayAnexoList ($solicitudes->foreachCall("getAnexo"));
                        $partners = $anexos->getPartners();
                        $anexo = reset($anexos);
                        $log->resultado('ok',true);

                        if ($fullValid) {

                            $message = $template('auto_validated_correctly');
                            $template->assign("waitingMessage", $message);
                            $template->assign("partners", false);

                        } elseif (count($partners) == 1) {
                            $template->assign("partners", $partners);
                            $partner = reset($partners);
                            $AVGValidation = $partner->getAVGTimeValidate();
                            $AVGValidation = ($AVGValidation == 0) ? false : $AVGValidation;
                            $template->assign("AVGValidation", $AVGValidation);
                        } else {
                            if ($anexo->isRenovation()) {
                                $message = $template('waiting_renovation');
                            } else {
                                $message = $template('waiting_for_applicant_validation');
                            }
                            $template->assign("waitingMessage", $message);
                            $template->assign("partners", false);
                        }

                        $title = $template->getString("success_upload_document");
                        $userTimeZone = $usuario->getTimeZone();
                        $expiredDate = $anexo->getExpirationTimestamp($userTimeZone);

                        // --- template variables
                        $template->assign("successClass", "center");
                        $template->assign("succes", $title);
                        $template->assign("fileId", $anexo->getFileId()->getUID());
                        $template->assign("expiredDate", $expiredDate);
                        $template->display('anexar_info.tpl');
                        exit;

                    } catch(Exception $e) {
                        $estado = $e->getMessage();
                        $log->resultado('error '.$estado, true);
                        //$template->assign('file', $files['archivo']);
                        $template->assign('error', $estado);
                    }

                } else {
                    $template->assign('error', "sin_seleccionar");
                }
            }

            $template->assign('selectedRequest', $req);
            $template->display('anexar.tpl');
        }
    break;
    case "date":
        if (isset($_REQUEST['oid'])) {
            $anexo = new anexo($_REQUEST['oid'], $elementoActual);
            $atributo = $anexo->obtenerDocumentoAtributo();
            $solicitante = $atributo->getElement();
            $info = $anexo->getInfo();
            $timezone = $usuario->getTimezoneOffset();
            $userTimeZone = $usuario->getTimeZone();
            $fechaEmision = $anexo->getRealTimestamp($timezone);
            $fechaExpiracion = $anexo->getExpirationTimestamp($userTimeZone);
            $partner = $anexo->getPartner();

            $arrayCampos = new FieldList();
            $arrayCampos['nueva_fecha'] = new FormField(array( "tag" => "input", "type" => "text", "value" => date("d/m/Y", $fechaEmision), "className" => "datepicker", "size" => 8 ));


            if( $atributo->caducidadManual() ){
                $val = ( $info["fecha_expiracion"] ) ? date("d/m/Y", $fechaExpiracion) : "";
                $arrayCampos['nueva_fecha_caducidad'] = new FormField(array( "tag" => "input", "type" => "text", "value" => $val, "className" => "datepicker", "size" => 8, "id" => "caducidad-manual" ));
                $arrayCampos['marcar_si_no_caduca'] = new FormField(array(  "tag" => "input", "type" => "checkbox", "className" => "alternative", "attr" => array( "data-src" => "#caducidad-manual","data-src-value" => "no caduca" )));
            }

            if( isset($_REQUEST['send']) ){

                $delayedStatus = NULL;
                $validationArgument = $anexo->getValidationArgument();
                $validationWrongDate = $validationArgument && ($validationArgument->getUID() == ValidationArgument::WRONG_DATE);
                $delayedRejectRenovation = ($anexo->getReverseStatus() == documento::ESTADO_ANULADO && $anexo->getStatus() == documento::ESTADO_VALIDADO);
                if ($validationWrongDate || $delayedRejectRenovation) {
                    $reverseStatus = documento::ESTADO_ANEXADO;
                    $delayedStatus = new DelayedStatus($reverseStatus, DelayedStatus::defaultChangeDays());
                }

                $fileId = $anexo->getFileId();

                if ($fileId) {
                    $estado = $fileId->updateDate(@$_REQUEST["nueva_fecha"], @$_REQUEST["nueva_fecha_caducidad"], $usuario, $delayedStatus);
                } else {
                    $estado = $anexo->updateDate(@$_REQUEST["nueva_fecha"], @$_REQUEST["nueva_fecha_caducidad"], $usuario, $delayedStatus);
                }


                if ( $estado === true ) {
                    //Introduce the action as date changed
                    $requirement = $anexo->getSolicitud();

                    $setRequirements = new ArrayObjectList(array($requirement));
                    $reqType = new requirementTypeRequest($setRequirements, $elementoActual);
                    $commentId = $reqType->saveComment(false, $usuario, comment::ACTION_CHANGE_DATE, watchComment::AUTOMATICALLY_CHANGE_DATE);
                    $app = \Dokify\Application::getInstance();
                    $event = new Dokify\Application\Event\CommentId\Store($commentId);
                    $app->dispatch(Dokify\Events\CommentIdEvents::POST_COMMENTID_STORE, $event);

                    if ($partner instanceof empresa) {
                        $partners = new ArrayObjectList();
                        $partners[] = $partner;
                        $template->assign("partners", $partners);
                        $AVGValidation = $partner->getAVGTimeValidate();
                        $AVGValidation = ($AVGValidation == 0) ? false : $AVGValidation;
                        $template->assign("AVGValidation", $AVGValidation);
                    } else {
                        if ($anexo->isRenovation()) {
                            $message = $template('waiting_renovation');
                        } else {
                            $message = $template('waiting_for_applicant_validation');
                        }
                        $template->assign("waitingMessage", $message);
                        $template->assign("partners", false);
                    }

                    $title = $template->getString("fecha_actualizada");

                    // --- template variables
                    $template->assign("successClass", "center");
                    $template->assign("succes", $title);
                    if ($anexo->isUrgent()) {
                        $template->assign("isUrgent", true);
                    } else {
                        $template->assign("fileId", $anexo->getFileId()->getUID());
                    }
                    $template->assign("expiredDate", $anexo->getExpirationTimestamp($userTimeZone));
                    $template->display('anexar_info.tpl');
                    exit;
                } else {
                    $template->assign('error', $estado);
                }
            }

            $template->assign( "titulo", "actualizar_fecha");
            $template->assign( "title", $documento->getUserVisibleName() ." - ". $solicitante->getUserVisibleName() );
            $template->assign( "campos", $arrayCampos);

            parse_str( $_SERVER["QUERY_STRING"], $params  );
                unset($params["action"]);


            $backButton = array(
                "innerHTML" => $template("volver"),
                "href" => $_SERVER["PHP_SELF"] . "?" . http_build_query($params),
                "img" => RESOURCES_DOMAIN . '/img/famfam/arrow_left.png',
                "style" => "float:left"
            );

            $template->assign("botones", array(
                $backButton
            ));

            $template->assign('className', 'form-to-box');
            $template->assign('selectedRequest', $req);
            $template->assign( "data", array("action" => "date") );
            $template->display('form.tpl');

        }
    break;
}