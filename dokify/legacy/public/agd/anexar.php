<?php

require_once('../api.php');
set_time_limit(0);
ini_set('memory_limit', '256M');

$log = log::singleton();
$template = Plantilla::singleton();

$company = $usuario->getCompany();
$startList = ($corp = $company->perteneceCorporacion()) ? new ArrayObjectList(array($company, $corp)) : $company->getStartList();


if (isset($_REQUEST["o"]) && is_numeric($_REQUEST["o"])) {
    $uid = $_REQUEST["o"];
} else {
    $place = isset($_SERVER['HTTP_X_LAST_PAGE']) ? $_SERVER['HTTP_X_LAST_PAGE'] : $_SERVER['REQUEST_URI'];

    $log->info('requestable', 'anexar', $place);
    $log->nivel(5);
    $log->resultado('malformed url', true);
    die("Inaccesible");
}

$modulo = obtener_modulo_seleccionado();
$elementoActual = new $modulo($uid);
$documento = new documento(obtener_uid_seleccionado(), $elementoActual);


if (!$elementoActual instanceof solicitable) {
    $log->info($modulo, 'anexar documento '.$documento->getUserVisibleName(), $elementoActual->getUserVisibleName());
    $log->nivel(5);
    $log->resultado('modulo no valido', true);
    die('Error: Modulo no especificado!');
}

$accionFirmar = @$usuario->getAvailableOptionsForModule($documento, "firmar")[0];
$reqType = documento_atributo::TYPE_FILE_UPLOAD;
if ($comefrom = obtener_comefrom_seleccionado()) {
    if ($comefrom === 'firmar' && $accionFirmar) {
        $reqType = null; // any doc
    }
}

$actionAnexar = @$usuario->getAvailableOptionsForModule($documento, "anexar")[0];
if ($reqType === documento_atributo::TYPE_FILE_UPLOAD && (!$usuario->accesoElemento($elementoActual) || !$actionAnexar)) {
    if ($usuario->esStaff()) {
        $template->display("sin_acceso_perfil.tpl");
    } else {
        die("Inaccesible");
    }
}


// --- read "req" param
$req = (isset($_REQUEST['req']) && is_numeric($_REQUEST['req'])) ? new solicituddocumento($_REQUEST['req']) : null;

if (isset($_REQUEST['action'])) {
    $log->info($modulo, 'anexar documento '.$documento->getUserVisibleName(), $elementoActual->getUserVisibleName());
    include('uploadactions.php');
    exit;
}

$uidUserCompany = $usuario->getCompany()->getUID();
$requestsFilters = array();
$requestsFilters['uid_empresa_referencia'] = $uidUserCompany;
$requestsFilters['uid_agrupador'] = 0;
$requestsFilters['!subcontratacion'] = true;
$requestsFilters['!replica'] = true;

$hayFichero = isset($_SESSION['FILES']) || isset($_FILES["archivo"]);

if (isset($_REQUEST["send"]) && $hayFichero) {
    $list = obtener_uids_seleccionados();

    if ($itemsAnexarMasivos = get_int_array_from_request("items")) {
        // Recibimos un conjunto de items para los que anexar el archivo
        $itemsAnexarMasivos = new ArrayIntList($itemsAnexarMasivos);
        $elementosAnexarMasivamente = $itemsAnexarMasivos->toObjectList($modulo);
    } else {
        //Solo anexamos para el elemento actual
        $elementosAnexarMasivamente = new ArrayObjectList(array($elementoActual));
    }

    if (isset($_FILES["archivo"])) {
        $files = $_FILES;
    } else {
        $files = unserialize($_SESSION['FILES']);
    }

    // --- unlock session
    session_write_close();

    if ($files["archivo"]["error"]) {
        // Si hay un error en el upload ...
        return "error_sin_archivo";
    } else {
        try {
            if (!isset($files['archivo']['tmp_name'])) {
                $elementoActual->logError("anexar.php", "Not found tmp_name");
                throw new Exception("detected_problems_upload_file");
            }

            if (!isset($files['archivo']['md5_file'])) {
                $elementoActual->logError("anexar.php", "Not found md5_file");
                throw new Exception("detected_problems_upload_file");
            }

            if (!isset($files['archivo']['name'])) {
                $elementoActual->logError("anexar.php", "Not found name");
                throw new Exception("detected_problems_upload_file");
            }

            $mime = null;
            if (isset($files['archivo']['type'])) {
                $mime = $files['archivo']['type'];
            }

            $file = $files['archivo']['tmp_name'];
            $md5 = $files['archivo']['md5_file'];
            $filename = utf8_decode($files['archivo']['name']);

            customSession::set('progress', null);


            // if we have a zip with one file, we use that file instead
            if (archivo::getExtension($file) == 'zip') {
                $zippedFiles = archivo::unzip($file);

                if (count($zippedFiles) === 1) {
                    $path = reset($zippedFiles);

                    if (!is_dir($path) && is_readable($path)) {
                        $file = str_replace('/tmp', '', $path);
                    }
                }
            }


            $isProcessable = $handler = $autoValidated = $saveWords = false;
            if (archivo::getExtension($file) === 'pdf') {
                $localfile = "/tmp/$file";

                // --- nos aseguramos de que tenemos el fichero en el disco
                if (archivo::is_readable($localfile) === false) {
                    if (archivo::escribir($localfile, archivo::tmp($file)) === false) {
                        throw new Exception("detected_problems_upload_file");
                    }
                }

                try {
                    $handler = new pdfHandler($localfile);


                    if ($handler->isReadable()) {
                        if ($documento->supportsChecks()) {
                            $isProcessable = $documento->isProcessable($handler) && count($employees = $handler->getEmployees($usuario)) && $employees->contains($elementoActual);

                        }
                    }
                } catch (Exception $e) {
                }

                // set flag to true
                $saveWords = true;
            }


            $agrupadoresSeleccionados = get_int_array_from_request("lista-agrupadores");
            $lengthElements = count($elementosAnexarMasivamente) - 1;


            if ($isProcessable) {
                $checksOk = $documento->passChecks($handler, $usuario, @$_REQUEST["fecha"]);
                $autoValidated = $checksOk && $documento->supportsAutovalidation();
            }


            $userAutoValidate = isset($_REQUEST['autovalidate']);

            foreach ($elementosAnexarMasivamente as $key => $elemento) {
                $log->info($modulo, 'anexar documento '.$documento->getUserVisibleName(), $elemento->getUserVisibleName());
                if (!$usuario->accesoElemento($elemento)) {
                    die("Inaccesible");
                }

                $documentoItem = new documento($documento->getUID(), $elemento);

                if (isset($_REQUEST["src"]) && $_REQUEST["src"] == "ajax") {
                    $solicitudes = $documentoItem->obtenerSolicitudDocumentos($elemento, $usuario, $requestsFilters);
                } else {
                    if ($documentoItem->applyForAllRequest($usuario)) {
                        $solicitudes = $documentoItem->obtenerSolicitudDocumentos($elemento, $usuario, $requestsFilters);
                    } else {
                        if (!$list instanceof ArrayObject) {
                            throw new Exception("error_seleccionar_destinatarios");
                        }
                        $solicitudes = $list->toObjectList("solicituddocumento");
                    }
                }

                if (!count($solicitudes)) {
                    continue;
                }

                $atributos = $solicitudes->foreachCall("obtenerDocumentoAtributo")->unique();

                foreach ($atributos as $atributo) {
                    $agrupamientoAuto = $atributo->getAgrupamientoAuto();
                    if ($agrupamientoAuto instanceof agrupamiento) {
                        if (isset($agrupadoresSeleccionados[$atributo->getUID()])) {
                            $array = $agrupadoresSeleccionados[$atributo->getUID()];
                            $arrayAgrupadores = $agrupamientoAuto->obtenerAgrupadores();

                            foreach ($array as $uid) {
                                $agrupador = new agrupador($uid);
                                if (in_array($agrupador, $arrayAgrupadores->getArrayCopy())) {
                                    if (!($elemento->asignarAgrupadores($agrupador))) {
                                        throw new Exception("error_agrupador_no_asignado");
                                    }
                                } else {
                                    throw new Exception("error_agrupador_no_asignable");
                                }
                            }

                            // Actualizar la solicitud de documentos para este item, por que puede haber nuevos despues de la asignacion
                            $elemento->actualizarSolicitudDocumentos();

                        } else {
                            // Si marcan que no esta en la lista...
                            if (isset($_REQUEST["not-in-list"][$atributo->getUID()])) {
                                // Debe haber comentario...
                                if (!isset($_REQUEST["comentario"]) || !trim($_REQUEST["comentario"])) {
                                    throw new Exception("comentario_obligatorio_respecto");
                                }
                            } else {
                                throw new Exception("error_agrupador_no_seleccionado");
                            }
                        }
                    }
                }


                if ($isProcessable) {
                    // --- Obtenemos el nuevo documento que contiene la linea del item
                    if (!$tmpFile = $documentoItem->getDocumentItemVersion($handler, $usuario)) {
                        // --- solo deberíamos estar aqui si la empresa es un autonomo, esta dado de alta como empleado de sí mismo y además él no sale en el ita como empleado
                        error_log("error creating document item version font {$handler->getFile()}");
                        continue;
                    }


                    $lastUpload = true;
                } else {
                    $handler = false;
                    $tmpFile = $file;
                    $lastUpload = ($lengthElements == $key) ? true : false;
                }



                if ($lengthElements) {
                    $progress = sprintf($template("anexando"), ($key+1), ($lengthElements+1));
                    customSession::set('progress', $progress);
                }

                $documentoItem->upload($tmpFile, $md5, $filename, @$_REQUEST["fecha"], $elemento, $solicitudes, $usuario, @$_REQUEST["comentario"], @$_REQUEST["caducidad"], $mime, $autoValidated, $lastUpload);
            }

            customSession::set('progress', "-1");

            // debería estar nada mas comprobar send?
            unset($_SESSION['FILES']);

        } catch (Exception $e) {
            customSession::set('progress', "-1");

            $error = $e->getMessage();
            $log->info($modulo, 'anexar documento '.$documento->getUserVisibleName(), $elementoActual->getUserVisibleName(), 'error '.$error, true);

            // En el caso de upload por ajax...
            if (isset($_REQUEST["src"]) && $_REQUEST["src"] == "ajax") {
                print json_encode(array("error" => $template->getString($error)));
                exit;
            } else {
                if (count($elementosAnexarMasivamente) > 1) {
                    $template->assign('items', $elementosAnexarMasivamente);
                    $template->assign('selectedItem', $elementoActual);
                    $template->assign('module', $elementoActual->getModuleName());
                    $template->assign('selected', true);
                    $template->assign('disable', true);
                }


                if ($elementoActual instanceof childItemEmpresa) {
                    $template->assign('uidUserCompany', $uidUserCompany);
                }

                if ($files['archivo']) {
                    $template->assign('pass', true);
                }
                $template->assign('file', $files['archivo']);
                $template->assign('error', $error);
                $template->assign('applyForAll', $documento->applyForAllRequest($usuario));
                $template->assign('reqType', $reqType);
                $template->assign('elemento', $elementoActual);
                $template->assign('documento', $documento);
                $template->assign('selectedRequest', $req);
                $template->display('anexar.tpl');
                exit;
            }
        }

        if (isset($_REQUEST["src"]) && $_REQUEST["src"] == "ajax") {
            print json_encode(array("upload" => true));
        } else {
            if (is_mobile_device()) {
                print json_encode(array("action" => array("go" => "#documentos.php?m={$modulo}&poid={$elemento->getUID()}")));
                exit;
            }

            // --- foreach request, get associated attachment
            $anexos = new ArrayAnexoList($solicitudes->foreachCall("getAnexo"));

            // --- contabilizamos las validaciones
            $validated            = 0;
            $validatedAttachments = new \Dokify\Domain\Attachment\Collection();
            $validatedRequests    = new ArrayObjectList();
            $partners             = new ArrayObjectList();

            foreach ($anexos as $anexo) {
                if ($anexo instanceof anexo) {
                    // --- tenemos que dejar los documentos validados?
                    if (!$autoValidated && $userAutoValidate) {
                        $atributo = $anexo->obtenerDocumentoAtributo();
                        $requestCompany = $atributo->getCompany();
                        // --- vemos si la empresa que solicita el documento está dentro de nuestro grupo
                        if ($startList->contains($requestCompany)) {
                            // --- validamos el documeno
                            if ($updated = $documento->updateStatus(array($anexo), documento::ESTADO_VALIDADO, $usuario)) {
                                $validated++;
                                $validatedAttachments->append($anexo->asDomainEntity());

                                if ($req = $anexo->getSolicitud()) {
                                    $validatedRequests[] = $req;
                                }
                            }
                        }

                    }
                } else {
                    error_log("Cant find attachments for one of this requests ({$solicitudes->toIntList()})");
                }
            }

            if ($validated && count($validatedRequests)) {
                // --- Guardar evento "validar" en comentarios
                $reqType = new requirementTypeRequest($validatedRequests, $elementoActual);
                $reqType->saveComment('', $usuario, documento::ESTADO_VALIDADO);

                $validationAction = \Dokify\Application\Event\Validation::ACTION_VALIDATE;

                $validationEvent = new \Dokify\Application\Event\Validation(
                    $validationAction,
                    $validatedAttachments,
                    $company->asDomainEntity(),
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
            }

            // --- use one attachment to get some file data
            $anexo = reset($anexos);


            // --- extraemos el fileID de un anexo cualquiera (son todos el mismo fileId)
            if (!$anexo instanceof anexo) {
                error_log("Can't find attachments in documento_elemento {$solicitudes->toComaList()}");
                header("HTTP/1.1 500 Internal Server Error");
                exit;
            }

            // --- get fileId instance
            $fileId = $anexo->getFileId();

            // --- save words if any
            if ($saveWords) {
                // we are not developing this features right now, so dont read any words from now on
                // $documento->saveWords([], $fileId, $localfile);
            }


            // --- caculate some validation stats
            if (!$userAutoValidate) {
                $partners = $anexos->getPartners();
            }

            $userTimeZone = $usuario->getTimeZone();
            if (count($partners) && is_traversable($partners) && !$autoValidated && $usuario instanceof usuario) {
                if (count($anexos)>1) {
                    $dates = $anexos->foreachCall("getExpirationTimestamp", array($userTimeZone))->getArrayCopy();
                    // --- quitamos ceros o false
                    $dates = array_filter($dates);
                    $expiredDate = reset($dates);
                } else {
                    $anexo = reset($anexos);
                    $expiredDate = $anexo->getExpirationTimestamp($userTimeZone);
                }

                if ($expiredDate!=0) {
                    $template->assign("expiredDate", $expiredDate);
                }

                if (count($partners) == 1) {
                    $anexo = reset($anexos);
                    $partner = reset($partners);
                    $atributo = $anexo->obtenerDocumentoAtributo();
                    $isCustom = ($atributo->getIsCutom()) ? documento_atributo::TEMPLATE_TYPE_CUSTOM : documento_atributo::TEMPLATE_TYPE_GENERAL;
                    $AVGValidation = $partner->getAVGTimeValidate();
                    $AVGValidation = ($AVGValidation == 0) ? false : $AVGValidation;
                    $template->assign("AVGValidation", $AVGValidation);
                }

                $template->assign("selected", $solicitudes);
                $template->assign("partners", $partners);
            } else {
                $template->assign("partners", false);
                $template->assign("expiredDate", $anexo->getExpirationTimestamp($userTimeZone));
            }

            // --- validation info to user
            if ($autoValidated) {
                $message = $template('auto_validated_correctly');
            } elseif ($userAutoValidate) {
                $message = sprintf($template('n_documents_validated'), $validated);
            } elseif ($anexo->isRenovation()) {
                $message = $template('waiting_renovation');
            } else {
                $message = $template('waiting_for_applicant_validation');
            }

            $title = $template->getString("success_upload_document");
            if ($lengthElements) {
                $title .= " " . strtolower($template('para')). " ". count($itemsAnexarMasivos) . " " . strtolower($template("{$modulo}_plural"));
            }

            // --- template variables

            $template->assign("elementId", $elementoActual->getUID());
            $template->assign("moduleName", $elementoActual->getModuleName());

            $template->assign("autoValidated", $autoValidated);
            $template->assign("successClass", "center");
            $template->assign("succes", $title);
            $template->assign("fileId", $anexo->getFileId()->getUID());
            $template->assign("waitingMessage", $message);
            $template->display('anexar_info.tpl');
            exit;
        }
    }
}

if (isset($_SERVER["HTTP_X_LAST_PAGE"]) && preg_match('/\#(.*)/', $_SERVER["HTTP_X_LAST_PAGE"], $matches) && $page = $matches[1]) {
    $page = parse_url($page);
    parse_str(@$page['query'], $query);
    $template->assign('comefrom', @$query['comefrom']);
}

$template->assign('applyForAll', $documento->applyForAllRequest($usuario));
$template->assign('reqType', $reqType);
$template->assign('elemento', $elementoActual);

$template->assign('selectedRequest', $req);
$template->assign('documento', $documento);
$template->assign('startList', $startList);



$anexosReattachable = $elementoActual->getReattachableDocuments($documento, $usuario);

$totalRequests = $documento->obtenerSolicitudDocumentos($elementoActual, $usuario, $requestsFilters, $reqType);

$reattachables = array();
foreach ($anexosReattachable as $key => $anexo) {
    $request = $anexo->getSolicitud();

    // If there are only one request we have to unset his attach (if exists)
    if (count($totalRequests) == 1 && $totalRequests->contains($request)) {
        continue;
    }

    // If we want to attach in a single filter request (replica, subcontratacion, relacion agrupadores)
    if ($req && !$totalRequests->contains($req)) {
        break;
    }

    if (!$request) {
        $reattachables[] = $anexo;
    } elseif (!$request->compareTo($req)) {
        $reattachables[] = $anexo;
    }
}


if (count($reattachables)) {
    $template->assign('anexosReattachable', $reattachables);
}

if ($elementoActual instanceof childItemEmpresa) {
    $template->assign('uidUserCompany', $uidUserCompany);
}

$template->assign('filtrosPartner', array('validation_payment_method' => "all"));

$template->display('anexar.tpl');
