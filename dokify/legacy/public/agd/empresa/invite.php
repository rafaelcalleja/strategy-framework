<?php

include("../../api.php");

$template = new Plantilla();

$log = new log();

if (!($usuario->getAvailableOptionsForModule(util::getModuleId("empresa"), 22))) {
    $log->nivel(6);
    $log->resultado("sin permiso", true);
    $template->display("erroracceso.tpl");
    exit;
}

$empresaSeleccionadaId = obtener_uid_seleccionado();
if (is_numeric($empresaSeleccionadaId)) {
    $inviterCompany = new empresa($empresaSeleccionadaId);
} else {
    $inviterCompany = $usuario->getCompany();
}

if ($usuario->esStaff() === false) {
    $template->assign("error", "Funcionalidad únicamente disponible en <a href=\"/app/settings/appversion?version=2\">la nueva versión</a>");
    $template->display("form.tpl");
    exit;
}

$userCompany = $usuario->getCompany();

if (isset($_REQUEST["send"])) {
    try {
        if (isset($_REQUEST['oid']) && $uid = trim($_REQUEST["oid"])) {
            $invitedCompany = new empresa($uid);
            $message = isset($_REQUEST['response_message']) ? $_REQUEST['response_message'] : null;
            $signinRequest = $inviterCompany->createInvitation($invitedCompany, $usuario, $message);
            $numberClients = count($inviterCompany->obtenerEmpresasCliente());
            $belongCorp = $inviterCompany->perteneceCorporacion();
            $needClientsConf = ($belongCorp && $numberClients > 1) || (!$belongCorp && $numberClients > 0);

            if ($needClientsConf) {
                //Shows assign client modal
                $signinRequest->update(array("state" => signinRequest::STATE_CONFIGURATION));
                $params = [
                    "m" => $signinRequest->getModuleName(),
                    "poid" => $signinRequest->getUID(),
                    "comefrom" => "nuevo",
                ];

                $location = "/agd/asignarcliente.php?" . http_build_query($params);
                header("Location: $location");
                exit;
            }

            $visibleOrgs = $userCompany->obtenerAgrupamientosVisibles(array('modulo' => $signinRequest->getModuleName(), $usuario));
            if ($inviterCompany->perteneceCorporacion()) {
                $orgCorpAssigned    = $inviterCompany->obtenerAgrupamientosCorporacionAsignados($usuario);
                $visibleOrgs        = $visibleOrgs->match($orgCorpAssigned);
            }

            if (count($visibleOrgs)) {
                $signinRequest->update(array("state" => signinRequest::STATE_CONFIGURATION));
                $signinRequest->setClientesConfigured($usuario);
                //Redirect to the user to the assignement page
                $response = [
                    "closebox" => true,
                    "action" => [
                        "go" => "#asignacion.php?m={$signinRequest->getModuleName()}&poid={$signinRequest->getUID()}&comefrom=nuevo&return=0",
                    ],
                ];
                header("Content-type: application/json");
                print json_encode($response);
                exit;
            } else {
                $invitation = $signinRequest->createInvitationCompanyAlreadyExists();

                if ($invitation) {
                    $response = [
                        "closebox" => true,
                        "hightlight" => $signinRequest->getUID(),
                        "action" => [
                            "go" => "#empresa/listado.php?comefrom=invitacion&poid={$userCompany->getUID()}",
                            "force" => true,
                        ],
                    ];
                    header("Content-type: application/json");
                    print json_encode($response);
                    exit;
                } else {
                    $template->assign("error", "something_went_wrong");
                }
            }
        } else {
            $userCompany = $usuario->getCompany();

            $REQUEST = $_REQUEST;
            $isValidCif = true;
            $country = new pais($REQUEST ['uid_pais']);

            if (!$country->exists()) {
                error_log("country not exists!");
                exit;
            }

            $countries = pais::obtenerTodos();

            if ($countries->toIntList()->contains($country->getUID())) {
                $countryName = ($country->getUID() == pais::SPAIN_CODE) ? "Spain" : $country->getUserVisibleName();
                $funcValidVat = "vat::isValid" .$countryName. "VAT";
                if (is_callable($funcValidVat)) {
                    $isValidCif = call_user_func($funcValidVat, $REQUEST ['cif']);
                }
            }

            if ($isValidCif) {
                if ($userCompany->esCorporacion() && isset($inviterCompany) && $inviterCompany instanceof empresa && $inviterCompany->compareTo($userCompany)) {
                    $newCompany = new empresa($REQUEST, $usuario);

                    if ($newCompany->exists()) {
                        $newCompany->hacerInferiorDe($userCompany, $usuario, false, true);

                        if ($usuario->isViewFilterByGroups()) {
                            $agrupadores = $usuario->obtenerAgrupadores();
                            $newCompany->asignarAgrupadores($agrupadores->toIntList(), $usuario);
                        }


                        $newCompany->actualizarSolicitudDocumentos($usuario);
                        $uidPerfil = $usuario->crearPerfil($usuario, $newCompany, false);
                        $userPefil = new perfil($uidPerfil);
                        $rol = rol::obtenerRolesGenericos(rol::ROL_DEFAULT);
                        $perfilActualizado = $rol->actualizarPerfil($userPefil, true);
                        $contactoPrincipal = $userCompany->obtenerContactoPrincipal();

                        if ($contactoPrincipal instanceof contactoempresa) {
                            $dataContact = array("nombre" => $usuario->getName(),
                                    "apellidos" => $usuario->getSurname(),
                                    "email" => $usuario->getEmail(),
                                    "telefono" => $usuario->getPhone(),
                                    "idioma" => $usuario->getCompany()->getCountry()->getLanguage(),
                                    "principal" => 1,
                                    "uid_empresa" => $newCompany
                            );

                            $newContact = new contactoempresa($dataContact, $usuario);

                        }

                        $template->display("succes_form.tpl");
                        exit;
                    } else {
                        $template->assign("error", $newCompany->error);
                    }

                } else {
                    $REQUEST = $_REQUEST;
                    if (($userCompany->esCorporacion() && $userCompany->obtenerEmpresasInferiores()->contains($inviterCompany)) || $userCompany->compareTo($inviterCompany)) {
                        $REQUEST["uid_empresa"] = $inviterCompany->getUID();
                    } else {
                        $REQUEST["uid_empresa"] = $userCompany->getUID();
                    }

                    $isCifinUse = empresa::fromCif($_REQUEST["cif"]);
                    $conditionsInvitation = [
                        "email" => $REQUEST["email"],
                        "cif" => $REQUEST["cif"]
                    ];
                    $alreadyInvited = signinRequest::checkInvitationCompany($REQUEST["uid_empresa"], $conditionsInvitation);

                    if (!$alreadyInvited && !$isCifinUse) {
                        $REQUEST['app_version'] = 2;
                        $signinRequest = new signinRequest($REQUEST, $usuario);
                        if ($signinRequest) {
                            $numberClients = count($inviterCompany->obtenerEmpresasSuperioresSubcontratando(null, null, true, $usuario));

                            if ($numberClients > 0) {
                                //Shows assign client modal
                                $signinRequest->update(array("state" => signinRequest::STATE_CONFIGURATION));
                                $params = [
                                    "m"         => $signinRequest->getModuleName(),
                                    "poid"      => $signinRequest->getUID(),
                                    "comefrom"  => "nuevo"
                                ];

                                $location = "/agd/asignarcliente.php?" . http_build_query($params);
                                header("Location: $location");
                                exit;
                            } elseif (count($inviterCompany->obtenerAgrupamientosVisibles(array('modulo' => $signinRequest->getModuleName(), $usuario)))) {
                                $signinRequest->update(array("state" => signinRequest::STATE_CONFIGURATION));
                                $signinRequest->setClientesConfigured($usuario);
                                //Redirect to the user to the assignement page
                                $response = [
                                    "closebox"  => true,
                                    "action"    => [
                                        "go" => "#asignacion.php?m={$signinRequest->getModuleName()}&poid={$signinRequest->getUID()}&comefrom=nuevo&return=0"
                                    ]
                                ];
                                header("Content-type: application/json");
                                print json_encode($response);
                                exit;
                            } else {
                                $app = \Dokify\Application::getInstance();
                                $invitationEvent = new \Dokify\Application\Event\Company\Invitation\Store($signinRequest->asDomainEntity());
                                $app->dispatch(\Dokify\Events\Company\InvitationEvents::POST_COMPANY_INVITATION_UPDATE, $invitationEvent);
                                $log->info("signinRequest", " send invitation to ".$REQUEST['email'], $usuario->getCompany()->getUserVisibleName());
                                $log->resultado("ok", true);

                                $response = [
                                    "closebox"      => true,
                                    "hightlight"    => $signinRequest->getUID(),
                                    "action"        => [
                                        "go"    => "#empresa/listado.php?comefrom=invitacion&poid={$userCompany->getUID()}",
                                        "force" => true
                                    ]
                                ];
                                header("Content-type: application/json");
                                print json_encode($response);
                                exit;
                            }
                        } else {
                            $template->assign("error", $template->getString("invitation_error"));
                        }
                    } else {
                        if ($isCifinUse) {
                            $template->assign("error", $template->getString("invtation_cif_error"));
                        } else if ($alreadyInvited) {
                            $template->assign("error", $template->getString("already_invitation_error"));
                        }

                        $template->assign("request", $REQUEST);
                    }
                }
            } else {
                $template->assign("error", $template->getString("cif")." ".$template->getString("no_valido"));
            }
        }
    } catch (Exception $e) {
        $template->assign("error", $e->getMessage());
    }
}

if (($usuario->getAvailableOptionsForModule(util::getModuleId("empresa"), 67)) && !$usuario->getCompany()->esCorporacion()) {
    parse_str($_SERVER["QUERY_STRING"], $params);
    $template->assign("boton", false);

    $stringButton = "invite";
    $ownInvitationGroups = count($userCompany->obtenerAgrupamientosVisibles(array('modulo' => "signinRequest", $usuario)));

    $numberClients      = count($inviterCompany->obtenerEmpresasCliente());
    $belongCorp         = $inviterCompany->perteneceCorporacion();
    $needClientsConf    = ($belongCorp && $numberClients > 1) || (!$belongCorp && $numberClients > 0);

    if ($needClientsConf || $ownInvitationGroups) {
        $stringButton = "continuar";
    }

    $template->assign("botones", [
        ["innerHTML" => $template->getString($stringButton), "type" => "submit", "style" => "float:right", "img" => RESOURCES_DOMAIN ."/img/famfam/add.png"],
    ]);
}

if (isset($inviterCompany) && $inviterCompany instanceof empresa && $inviterCompany->compareTo($userCompany) && $usuario->getCompany()->esCorporacion()) {
    $mode = elemento::PUBLIFIELDS_MODE_INIT;
    $template->assign("campos", empresa::publicFields($mode, null, $usuario));
    $template->assign("titulo", "titulo_nuevo_cliente");
} else {
    $campos = signinRequest::publicFields(elemento::PUBLIFIELDS_MODE_INIT, null, $usuario);
    $template->assign("titulo", "title_invite_new_company");
    $template->assign("campos", $campos);
    $template->assign("className", "async-form");
    $template->assign("tip", array(
        "innerHTML" => "video_explicacion_alta_empresas",
        "href" => system::VIDEO_ALTA_EMPRESA
    ));
}

$template->display("form.tpl");
