<?php

include_once "../api.php";

use Dokify\Application\Event\Assignment\Store as AssignmentStoreEvent;

$app = \Dokify\Application::getInstance();

$modulo = obtener_modulo_seleccionado();
if (!in_array($modulo, agrupamiento::getModulesCategorizables())) {
    die("Error: Modulo no especificado!");
}

$log            = log::singleton();
$empresaUsuario = $usuario->getCompany();
$startList        = $empresaUsuario->getStartList();
//$esCorporacion = $clienteUsuario->esCorporacion();

$userCompanyEntity    = $empresaUsuario->asDomainEntity();
$userEntity        = $usuario->asDomainEntity();

//----- ACCIONES PARA LOS CHECKBOX DE GUARDAR
if (isset($_REQUEST["checked"]) && !obtener_uid_seleccionado()) {
    die( $usuario->configValue(array(  obtener_comefrom_seleccionado() => $_REQUEST["checked"] )) );
}

// instancia de la plantilla
$template = Plantilla::singleton();
$elementoSeleccionado = new $modulo( obtener_uid_seleccionado(), false );
if (!$usuario->accesoElemento($elementoSeleccionado) || !$usuario->accesoAccionConcreta($elementoSeleccionado, 20)) {
    die("Inaccesible");
}

// indica si se debe bloquear el formulario
$bloquear = $ocultar = false;

if ($usuario instanceof empleado && $elementoSeleccionado instanceof empleado && $usuario->compareTo($elementoSeleccionado)) {
    $bloquear = $ocultar = true;
}

$agrupamientosPropios = $empresaUsuario->obtenerAgrupamientosPropios($usuario);
$agrupamientosVisibles = $empresaUsuario->obtenerAgrupamientosVisibles($usuario);
$elementCompany = ($elementoSeleccionado instanceof empresa) ? $elementoSeleccionado :  $elementoSeleccionado->getCompany($usuario);

// Inmutable OR - the first condition must be always the first!
$isOwnOrCompany = $startList->contains($elementCompany) || $elementoSeleccionado instanceof empresa;

if ($usuario instanceof usuario) {
    // Tiene permiso de modificar las asignaciones de este tipo de elementos?
    if (!$usuario->accesoAccionConcreta($elementoSeleccionado, 153)) {
        $bloquear = true;
    } elseif ($isOwnOrCompany) {
        $bloquear = false;
    } else {
        $bloquear = true;
    }
}

$data = $elementoSeleccionado->getAssignData($usuario);

$asignacionMasiva = true;
if ($elementoSeleccionado instanceof empresa) {
    $asignacionMasiva = $usuario->getCompany()->getStartIntList()->contains($elementoSeleccionado->getUID());
}
$template->assign("asignacionmasiva", $asignacionMasiva);

/*************************************
/** EN ESTE PUNTO ES CONFIGURAR ASPECTOS DE LA RELACION
/*************************************/

// OPCION CONFIGURAR ASPECTOS DE LA RELACION
if (isset($_REQUEST["oid"])) {
    // Acciones desplegables
    if (isset($_REQUEST["action"])) {
        $agrupamientoAccion = new agrupamiento($_REQUEST["oid"]);
        switch ($_REQUEST["action"]) {
            case "rebote":
                if ($elementoSeleccionado->configuracionAgrupamiento($agrupamientoAccion, "rebote", $_REQUEST["checked"])) {
                    die("1");
                } else {
                    die("0");
                }
                break;
            case "lock":
                $result = array("refresh" =>1 );
                if ($elementoSeleccionado->lockAll($agrupamientoAccion, $_REQUEST["checked"])) {
                    $result["jGrowl"] = $template->getString("exito_texto");
                } else {
                    $result["jGrowl"] = $template->getString("error_texto");
                }
                die( json_encode($result) );
                break;
        }
        exit;
    }

    if (!isset($_REQUEST["o"])) {
        die("Inaccesible");
    } // Hay algun error..

    $agrupador = new agrupador($_REQUEST["oid"]);
    $agrupamiento = new agrupamiento($_REQUEST["o"]);
    $agrupamientosAsignar = $agrupamiento->obtenerAgrupamientosAsignados();

    // de momento limitamos a un agrupador
    $agrupamientoAsignar = reset($agrupamientosAsignar);

    // Si se pulsa sobre guardar
    if (isset($_REQUEST["send"])) {
        // ------------- Debemos actualizar la duracion
        if (isset($_REQUEST["duracion"])) {
            $updated = $elementoSeleccionado->setDuracionValue($agrupador, $_REQUEST["duracion"], $_REQUEST["startdate"]);
            $duracion = $_REQUEST["duracion"];
            $startdate = $_REQUEST["startdate"];
            $template->assign("currenttab", "duracion");

            if ($updated == true) {
                $template->assign("succes", "exito_texto");

                if (isset($_REQUEST['return']) && $return = db::scape($_REQUEST['return'])) {
                    header("Location: {$return}");
                    exit;
                }
            }
        }

        // ------------- Modificar los bloqueos
        if (isset($_REQUEST["bloquear"]) && $_REQUEST["bloquear"]) {
            if (!$agrupador->bloquearRelacion($elementoSeleccionado)) {
                $template->assign("error", "error_texto");
            }
        } else {
            if (!$agrupador->desBloquearRelacion($elementoSeleccionado)) {
                $template->assign("error", "error_texto");
            }
        }

        // -------------  Debemos actualizar las relaciones
        $done = true;
        if (count($agrupamientosAsignar)) {
            foreach ($agrupamientosAsignar as $agrupamientoAsignar) {
                $name =  $agrupamientoAsignar->getUserVisibleName();
                $requestString = "{$agrupamientoAsignar}";
                //$name = str_replace(array(" ","(",")"),array("-","_","_"), strtolower($name)); // #EPICFAIL de Jose  !Se complementa con el mismo cod que hay en tabs.tpl

                $uidAsignados = (isset($_REQUEST["$requestString-elementos-asignados"])) ? $_REQUEST["$requestString-elementos-asignados"] : [];
                $uidDisponibles = (isset($_REQUEST["$requestString-elementos-disponibles"])) ? $_REQUEST["$requestString-elementos-disponibles"] : [];
                if (true === ($error = $agrupador->quitarRelacion($elementoSeleccionado, $uidDisponibles)) && true === ($error = $agrupador->asignarRelacion($elementoSeleccionado, $uidAsignados))) {
                    if ($elementoSeleccionado instanceof solicitable) {
                        $elementoSeleccionado->actualizarSolicitudDocumentos();
                    }
                    //$template->assign("succes","exito_texto" );
                } else {
                    $done = false;
                    //$template->assign("error", $error );
                }
            }
        } else {
            $done = false;
        }

        if ($done === true) {
            $template->assign("succes", "exito_texto");
        } elseif (isset($error)) {
            $template->assign("error", $error);
        }

    }

    $duracion = $elementoSeleccionado->getDuracionValue($agrupador);
    $startdate = ($date = $elementoSeleccionado->getStartDate($agrupador)) ? $date : null;
    $bloqueado = $agrupador->esBloqueado($elementoSeleccionado);

    // Definimos las pestañas
    $template->assign("titulo", $elementoSeleccionado->getUserVisibleName() . " - " . $agrupador->getUserVisibleName());
    $tabs = array();

    if (count($agrupamientosAsignar) && !isset($_REQUEST["tab"])) {
        $asignaciones = array();
        foreach ($agrupamientosAsignar as $agrupamientoAsignar) {
            $name =  $agrupamientoAsignar->getUserVisibleName();
            //$uid =  $agrupamientoAsignar->getUID();
            $tabs[$name] = "asignartab.tpl";
            $asignados = $agrupador->obtenerAgrupadoresRelacionados($elementoSeleccionado, $agrupamientoAsignar);
            $disponibles = $agrupamientoAsignar->obtenerAgrupadores();
            $disponibles = elemento::discriminarObjetos($disponibles, $asignados);

            $asignaciones[$name] = array("asignados" => $asignados, "disponibles" => $disponibles, "agrupamiento" => $agrupamientoAsignar, "al_vuelo" => $agrupamientoAsignar->configValue("al_vuelo") );
        }

        $template->assign("asignaciones", $asignaciones);
    }

    // Pestaña duracion
    $tabs["duracion"] = "duracion.tpl";

    $template->assign("duracioncount", "90");
    $template->assign("duracionvalue", $duracion);
    $template->assign("startdate", $startdate);

    // Pestaña bloquear
    if (isset($_REQUEST["tab"]) &&  $_REQUEST["tab"]=='duracion') {
        $template->assign("currenttab", md5("duracion"));
    } else {
        $availableOptions = $usuario->getAvailableOptionsForModule($modulo, "bloquear");
        if ($op = reset($availableOptions)) {
            $template->assign("bloqueado", $bloqueado);
            $tabs["bloquear"] = "bloquearasignacion.tpl";
        }
    }

    $button = array(
        "innerHTML" => $template("guardar"),
        "img"        => RESOURCES_DOMAIN . '/img/famfam/disk.png'
    );

    if (isset($_REQUEST["return"])) {
        $button["innerHTML"] = $template('continuar');
    }

    $template->assign("buttons", array($button));

    if (isset($_REQUEST["ctab"])) {
        $template->assign("currenttab", $_REQUEST["ctab"]);
    }

    $template->assign("agrupadorrelacion", $agrupador);
    if (isset($agrupamientoAccion)) {
        $template->assign("agrupamientorelacion", $agrupamientoAccion);
    }
    $template->assign("elemento", $elementoSeleccionado);
    $template->assign("tabs", $tabs);
    $template->display("tabs.tpl");
    exit;
}

/*************************************
/** EN ESTE PUNTO ES ASIGNAR AGRUPADORES
/*************************************/

//----------------- EL USUARIO QUIERE GUARDAR LOS CAMBIOS ...SINO VIENE EL OID
if (isset($_REQUEST["send"])) {
    $arrayUIDagrupadoresAsignados = $arrayUIDagrupadoresDisponibles = array();
    foreach ($data->agrupamientos as $agrupamiento) {
        // Indice en el request que contendrá los agrupadores disponibles...
        $keyDisponibles = "e-d-".$agrupamiento->getUID();

        // Indice en el request que contendrá los agrupadores asignados...
        $keyAsignados = "e-a-".$agrupamiento->getUID();

        if (isset($_REQUEST[$keyAsignados]) && is_array($_REQUEST[$keyAsignados])) {
            $arrayUIDagrupadoresAsignados = array_merge_recursive($arrayUIDagrupadoresAsignados, $_REQUEST[$keyAsignados]);
        }
        if (isset($_REQUEST[$keyDisponibles]) && is_array($_REQUEST[$keyDisponibles])) {
            $arrayUIDagrupadoresDisponibles = array_merge_recursive($arrayUIDagrupadoresDisponibles, $_REQUEST[$keyDisponibles]);
        }
    }

    // si lo que queremos es sugerir en lugar de asignar
    if (isset($_REQUEST["suggest"]) && !isset($_REQUEST["save"])) {
        // lista de agrupadores a sugerir
        $asignados = $elementoSeleccionado->obtenerAgrupadoresDiff($arrayUIDagrupadoresAsignados);
        // empresas visibles para la empresa del usuario
        $uidsEmpresasUsuario = $empresaUsuario->getAllCompaniesIntList()->getArrayCopy();
        // empresas a las que pertenece el elemento
        $uidsEmpresasElemento = $elementoSeleccionado->obtenerEmpresas(false, $usuario)->toIntList()->getArrayCopy();
        // solo podemos enviar sugerencias a las empresas que pertenezcan a los dos grupos anteriores
        $empresasCandidatas = array_intersect($uidsEmpresasUsuario, $uidsEmpresasElemento);
        $empresasCandidatas = new ArrayIntList($empresasCandidatas);
        $empresasCandidatas = $empresasCandidatas->toObjectList('empresa');

        $empresasDescartadas = new ArrayObjectList();
        $arrayUidDescartar = array();
        foreach ($asignados as $asignado) {
            if ($asignado->esJerarquia()) {
                foreach ($empresasCandidatas as $key => $empresaCandidata) {
                    if (!$empresaCandidata->hasGroup($asignado)) {
                        $empresasDescartadas[] = $empresaCandidata;
                        $arrayUidDescartar[]=$key;
                    }
                }
            }
        }
        if (count($empresasDescartadas)) {
            foreach ($arrayUidDescartar as $key) {
                if (isset($empresasCandidatas[$key])) {
                    unset($empresasCandidatas[$key]);
                }
            }

            $template->assign("alert", "mensaje_empresas_sin_agrupador_jerarquia");
            $template->assign("empresasDescartadas", $empresasDescartadas->unique());
        }
        $template->assign("empresas", $empresasCandidatas);
        $template->assign("list", $asignados);
        $template->assign("elemento", $elementoSeleccionado);
        $template->assign("action", "sugeriragrupador.php");
        $template->display("sugeriragrupador.tpl");
        exit;
    }

    // Muy importante, si intentan guardar sin tener permiso
    if ($bloquear == true) {
        die("Inaccesible");
    }

    $actuales = $elementoSeleccionado->obtenerAgrupadores(null, $usuario)->toIntList()->getArrayCopy();
    $assignedRightNow = array_diff($arrayUIDagrupadoresAsignados, $actuales);

    //Asignacion masiva de empleados y maquinas del elemento empresa
    if (isset($_REQUEST["asignacion"]) && $modulo = $_REQUEST["asignacion"]) {
        ignore_user_abort(true);
        set_time_limit(6000);
        session_write_close();

        if ($elementoSeleccionado instanceof empresa) {
            $log->info($elementoSeleccionado->getModuleName(), 'asignacion masiva a '. $modulo, $elementoSeleccionado->getUserVisibleName());
            if ($usuario->accesoAccionConcreta($modulo, "asignaciones", 0, "empresa")) {
                $func = array($elementoSeleccionado, db::scape("obtener". ucfirst($modulo) ."s"));
                if (is_callable($func) && $datos = call_user_func($func)) {
                    // Creamos una copia de las asignaciones actuales
                    $elementoSeleccionado->createAssignCopy($modulo);

                    $actualizados = 0;
                    foreach ($datos as $ob) {
                        $ok = false;
                        if ($ob->quitarAgrupadores($arrayUIDagrupadoresDisponibles, $usuario) !== false) {
                            $ok = true;
                        }

                        if ($ob->asignarAgrupadores($arrayUIDagrupadoresAsignados, $usuario, 0, true) !== false) {
                            foreach ($assignedRightNow as $uid) {
                                $group = new agrupador($uid);

                                if (!$assignment = $ob->getAssignment($group)) {
                                    continue;
                                }

                                $entity = $assignment->asDomainEntity();
                                $event  = new AssignmentStoreEvent($entity, $userCompanyEntity, $userEntity);
                                $app->dispatch(\Dokify\Events::POST_ASSIGNMENT_STORE, $event);
                            }
                            $ok = true;
                        }

                        if ($ok) {
                            $ob->actualizarSolicitudDocumentos();
                            $actualizados++;
                        }
                    }
                    $resultado = ($actualizados != 0) ? "ok" : "error";
                    $log->resultado($resultado, true);
                    echo $template->getString("actualizacion_elementos_modulo")." $actualizados ". $template->getString($modulo."s") . "<br />";
                } else {
                    $log->resultado('error', true);
                    // die( "Error al buscar los elementos<br />" );
                }
            } else {
                $log->resultado('error permisos', true);
            }
        }
    } else {
        // Prepare log data
        $log->info($elementoSeleccionado->getModuleName(), 'asignacion elementos', $elementoSeleccionado->getUserVisibleName());

        // No remove nothing by default
        $agrupadoresRecienQuitados = array();

        // Quitar algun agrupador
        if (count($arrayUIDagrupadoresDisponibles)) {
            $removedAssignments = $elementoSeleccionado->quitarAgrupadores($arrayUIDagrupadoresDisponibles, $usuario, $arrayUIDagrupadoresAsignados);

            if ($removedAssignments === false) {
                $log->resultado('error', true);

                header("Content-type: text/plain");
                die( "Error al desasignar algun elemento<br />" );
            }

            $agrupadoresRecienQuitados = $removedAssignments->getGroups();
        }

        $arrayUIDagrupadoresAsignados = array_merge($arrayUIDagrupadoresAsignados, $actuales);

        // Asignar algun agrupador
        if (count($arrayUIDagrupadoresAsignados)) {
            $arrayUIDagrupadoresAsignados = array_diff($arrayUIDagrupadoresAsignados, elemento::getCollectionIds($agrupadoresRecienQuitados));

            if (count($arrayUIDagrupadoresAsignados)) {
                $agrupadoresRecienAsignados = $elementoSeleccionado->asignarAgrupadores($arrayUIDagrupadoresAsignados, $usuario);
                if ($agrupadoresRecienAsignados === false) {
                    $log->resultado('error', true);

                    header("Content-type: text/plain");
                    die( "Error al asignar algun elemento<br />" );
                }

                // add the bounces to assignedRightNow
                foreach ($agrupadoresRecienAsignados as $group) {
                    if (false === in_array($group->getUID(), $arrayUIDagrupadoresAsignados)) {
                        $assignedRightNow[] = $group->getUID();
                    }
                }

                foreach ($assignedRightNow as $uid) {
                    $group = new agrupador($uid);

                    if (!$assignment = $elementoSeleccionado->getAssignment($group)) {
                        continue;
                    }

                    $entity = $assignment->asDomainEntity();
                    $event  = new AssignmentStoreEvent($entity, $userCompanyEntity, $userEntity);
                    $app->dispatch(\Dokify\Events::POST_ASSIGNMENT_STORE, $event);
                }
            }
        }

        // Save log data
        $elementoSeleccionado->writeLogUI(logui::ACTION_ASSIGN_AGR, logui::STRING_ACTION_ADD ." = '".count($assignedRightNow)."',". logui::STRING_ACTION_REMOVE ." = '".count($agrupadoresRecienQuitados)."'", $usuario);
        $log->resultado('ok', true);

        // Check if we need to update requests
        if ($elementoSeleccionado instanceof solicitable) {
            $elementoSeleccionado->actualizarSolicitudDocumentos($usuario);

            // If we are updating a company, test if update of childs is needed
            if ($elementoSeleccionado instanceof empresa) {
                foreach ($assignedRightNow as $assigned) {
                    $assigned = new agrupador($assigned);

                    if ($assigned->hasReferencedAttrs()) {
                        // async update this compay childs
                        $elementoSeleccionado->asyncUpdateChildRequests($assigned);
                    }
                }
            }
        }

        $elementoSeleccionado->clearCache("obtenerAgrupadores-categorizable-". $elementoSeleccionado ."--");
        $groupsSet        = $elementoSeleccionado->obtenerAgrupadores();
        $orgsAssigned    = $groupsSet->toOrganizationList();
        $organizations    = new ArrayAgrupamientoList();
        $moduleName    = $elementoSeleccionado->getModuleName();

        // Retrieving all mandatory groups of clients of this element
        $modules        = agrupamiento::getModulesToApplyMandatory();
        if ($modules->contains(strtolower($moduleName))) {
            $organizations    = $elementoSeleccionado->getMandatoryOrganizations($usuario);
        }

        $needReviewOrganizations = count($organizations->diff($orgsAssigned));
        if ($elementoSeleccionado instanceof signinRequest) {
            $data        = false;
            if (count($arrayUIDagrupadoresAsignados)) {
                $clientesConfigured = !$elementoSeleccionado->needConfigureClients($usuario);

                if ($clientesConfigured) {
                    $alreadySent = $elementoSeleccionado->getState() == signinRequest::STATE_PENDING;
                    // If if has mandatory organizations to assign, we do not change the state of the invitation.
                    if ($alreadySent == false && $needReviewOrganizations == false) {
                        $elementoSeleccionado->changeStateInvitation(signinRequest::STATE_NOT_SENT);
                    }
                }

                if ($needReviewOrganizations == false) {
                    // check if the internal invitation is already set
                    $inviterCompany = $elementoSeleccionado->getCompany();
                    $invitedCompany = $elementoSeleccionado->getCompanyInvited();
                    $filter        =  array('estado' => empresasolicitud::ESTADO_CREADA);
                    if ($invitedCompany && !solicitud::getFromItem('empresa', $inviterCompany, $invitedCompany, $filter, true)) {
                        $invitation = $elementoSeleccionado->createInvitationCompanyAlreadyExists();
                        if (!$invitation) {
                            header("Content-type: text/plain");
                            die($template('something_went_wrong'));
                        }
                    }

                    $comefrom    = obtener_comefrom_seleccionado();
                    if ($comefrom == "nuevo" && !$needReviewOrganizations) {
                        $data = [
                            "jGrowl"        => $template("groups_assigned"),
                            "hightlight"    => $elementoSeleccionado->getUID(),
                            "action"        => [
                                "go" => "#empresa/listado.php?comefrom=invitacion&poid={$empresaUsuario->getUID()}"
                            ]
                        ];
                    }
                }

            } else {
                $data = [
                    "jGrowl"    => $template("need_at_least_one_group"),
                    "refresh"    =>1
                ];
            }
            if ($data) {
                //Redirect to the invitation list
                header("Content-type: application/json");
                print json_encode($data);
                exit;
            }
        }

        if ($needReviewOrganizations) {
            // The element has pending mandatory groups pending to be assigned.
            if ($elementoSeleccionado instanceof signinRequest && $elementoSeleccionado->getState() == signinRequest::STATE_CONFIGURATION) {
                //if the invitation is already sent, we do not show this message.
                $message = $template("alert_mandatory_groups_needed_invitation");
            } elseif (!$elementoSeleccionado instanceof signinRequest) {
                $message = $template("alert_mandatory_groups_needed_elements");
            }

        } else {
            //If the element has no mandatory groups pending to assign.
            if (isset($_REQUEST["return"])) {
                // Si después de asignar volvemos a una página anterior
                if (count($arrayUIDagrupadoresAsignados)) {
                    if (is_numeric($_REQUEST["return"])) {
                        // uid de una accion, utilizamos para conocer los
                        if ($action = $usuario->accesoAccionConcreta($elementoSeleccionado, $_REQUEST["return"])) {
                            $href = $action["href"] . '&poid=' . $elementoSeleccionado->getUID();
                            $data = array("action" => array("go" => $href));

                            header("Content-type: application/json");
                            die(json_encode($data));
                        }
                    }
                } else {
                    header("Content-type: text/plain");
                    die($template('no_asignado_nada'));
                }
            }
        }

        $comefrom = ($comefrom = obtener_comefrom_seleccionado()) ? "&comefrom=$comefrom" : "";
        $return = (isset($_REQUEST["return"])) ? "&return={$_REQUEST["return"]}" : "";
            $data = [
                "jGrowl"    => $template("exito_texto"),
                "action"    => [
                    "go"    => "#asignacion.php?m={$elementoSeleccionado->getModuleName()}&poid={$elementoSeleccionado->getUID()}$comefrom$return",
                    "force" => true
                ]
            ];

        if (isset($message)) {
            $data["alert"] = $message;
        }

        header("Content-type: application/json");
        print json_encode($data);
        exit;
    }

    exit;

}

$groupsSet        = $elementoSeleccionado->obtenerAgrupadores();
$orgsAssigned    = $groupsSet->toOrganizationList();
$moduleName    = $elementoSeleccionado->getModuleName();
// Retrieving all mandatory groups of clients of this element
$modules        = agrupamiento::getModulesToApplyMandatory();
if ($modules->contains(strtolower($moduleName))) {
    $organizations                = $elementoSeleccionado->getMandatoryOrganizations($usuario);
    $needReviewOrganizations    = $organizations->diff($orgsAssigned);
    $template->assign("needReviewOrganizations", $needReviewOrganizations);
}

$template->assign("user", $usuario);
$template->assign("secciones", $data);

$template->assign("columnas", 1);
$template->assign("bloquear", $bloquear);
$template->assign("ocultar", $ocultar);
$template->assign("elemento", $elementoSeleccionado);
$template->assign("empresa", $empresaUsuario);
$template->assign("agrupamientosPropios", $agrupamientosPropios);
$template->assign("agrupamientosVisibles", $agrupamientosVisibles);

$template->assign("res", RESOURCES_DOMAIN); //No funciona el metodo por defecto, hay que ver por que
$optionHTML = $template->getHTML("asignacion.tpl");

$json = new jsonAGD();

// Viene de otra parte especifica de la aplicacion, solo lo mostramos si el elemento no tiene nada asignado.
if (isset($_REQUEST["return"]) && !count($elementoSeleccionado->obtenerAgrupadores())) {
    $json->addHelper(helper::getFirstAssignHelper());
}

// Viene para aceptar/rechazar una asignacion
if ($uid = @$_REQUEST["request"]) {
    $solicitud = new empresasolicitud($uid);

    $empresaSolicitud = $solicitud->getCompany();

    if (!$empresaUsuario->getStartList()->contains($empresaSolicitud)) {
        $template->assign("error", "desc_error_aviso_no_propiedad");
        $template->display("error_string.tpl"); //MENSAJE CONCRETO DE QUE NO TE PERTENCE LA SOLICITUD
        exit;
    }

    if ($solicitud->getTypeOf() === solicitud::TYPE_ASIGNAR) {
        $item = $solicitud->getItem();
        // Si la solicitud aun no ha sido aceptada/rechazada
        $status = $solicitud->getState();
        if ($status === solicitud::ESTADO_CREADA) {
            // Y la solicitud pertenece a este elemento
            if ($item->compareTo($elementoSeleccionado)) {
                $url = 'sugeriragrupador.php?request='. $uid .'&m=' . $item->getType() . '&poid=' . $item->getUID() . '&st=' . $status;
                $json->addData('open', $url);
            }
        }
    }
}

$json->nombreTabla("asignacion-".$elementoSeleccionado->getType());
$json->establecerTipo("simple");
$json->nuevoSelector("#main", $optionHTML);
$json->menuSeleccionado($modulo);
//$json->addData("cache", 0);

$img = ( is_callable(array($elementoSeleccionado,"getStatusImage")) && !$elementoSeleccionado instanceof agrupador ) ? $elementoSeleccionado->getStatusImage($usuario) : null;

$urlElement = array("innerHTML" => $elementoSeleccionado->getUserVisibleName(), "href" => $elementoSeleccionado->obtenerUrlFicha(), "title" => $elementoSeleccionado->getUserVisibleName(), "img" => $img);

if (!$elementoSeleccionado instanceof signinRequest) {
    $urlElement["className"] = "box-it";
}

//--------- Para mostrar claramente al usuario donde se encuentra
$json->informacionNavegacion("inicio", $template->getString("asignar"), $template->getString($elementoSeleccionado->getType()), $urlElement);

$json->display();
