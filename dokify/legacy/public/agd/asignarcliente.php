<?php
require_once("../api.php");


$template = Plantilla::singleton();


$modulo = obtener_modulo_seleccionado();

if( $uid = obtener_uid_seleccionado() ){
    $elemento = new $modulo($uid);
    $empresaUsuario = $usuario->getCompany();


    $template->assign("elemento", $elemento);

    // Si es un cliente corporación habrá que preguntar por que empresa se usará como referencia
    if( isset($empresaUsuario)){
        if( $empresaUsuario->esCorporacion() ){
            if( $comefrom = obtener_referencia() ){
                $empresaUsuario = new empresa($comefrom);
            } else {
                $empresasCorporacion = $empresaUsuario->obtenerEmpresasInferiores();

                // Intentamos seleccionar la empresa de la lista de forma automática
                $empresas = $elemento->getCompanies();
                $matches = $empresasCorporacion->match($empresas);

                                    if (false !== $matches && count($matches) === 1) {
                                        $empresaUsuario = $matches[0];
                                    } elseif (count($matches) > 1) {
                                        $empresasCorporacion = $matches;
                                        $template->assign("button", "continuar");
                                        $template->assign("varname", "ref");
                                        $template->assign("unsetsend", true);
                                        $template->assign("title", $template("seleccionar_empresa_referencia"));
                                        $template->assign("elementos", $empresasCorporacion );
                                        $template->display("listaseleccion.tpl");
                                        exit;
                                    }
            }
        }
    }


    // Asignamos la empresa del usuario, ya que puede ser seleccionada manualmente
    $template->assign("empresaUsuario", $empresaUsuario);
    $companiesSet = new ArrayObjectList();
    if( isset($_REQUEST["send"]) ){
        try {
            $assign = true;
            switch($modulo){
                case 'empresa':case 'signinRequest':
                    // OJO, que aqui solo borramos la relación entre las 2 empresas, y no todas las contratas inferiores!
                    // Por eso después de reasignar las nuevas cadenas de contratación debemos eliminar los posibles 'residuos'
                    $EmpresasClienteAnteriores = $elemento->obtenerEmpresasCliente();
                    $elemento->eliminarCadenasContratacion($empresaUsuario);

                    if (isset($_POST["list"])) {
                        $companiesRequestingDocuments = new ArrayObjectList();
                        foreach($_POST["list"] as $i => $val){
                            // Si es una subcontrata de segundo nivel segundo nivel
                            if( is_numeric($i) ){
                                if( !in_array($i, $_POST["list"]["_"]) ) continue; // no aplicamos superiores si la inf no está

                                $empresaIntermediaria = new empresa($i);
                                foreach($val as $uid){
                                    $empresaCliente = new empresa($uid);
                                    $assign = $elemento->crearCadenaContratacion($empresaUsuario, $empresaIntermediaria, $empresaCliente);
                                    if ($assign) {
                                        $companiesSet[] = $empresaCliente;
                                        if ($empresaCliente->countCorpDocuments()) $companiesRequestingDocuments[]      = $empresaCliente;
                                        if ($empresaIntermediaria->countCorpDocuments()) $companiesRequestingDocuments[] = $empresaIntermediaria;
                                    } else {
                                        $template->assign("error", "Error asignando para la empresa ".$empresaCliente->getUserVisibleName());
                                    }



                                }
                            } else {
                            // Si solo es una subcontrata de primer nivel
                                foreach($val as $uid){
                                    $empresaCliente = new empresa($uid);
                                    $assign = $elemento->crearCadenaContratacion($empresaUsuario, $empresaCliente);
                                    if ($assign) {
                                        $companiesSet[] = $empresaCliente;
                                        if ($empresaCliente->countCorpDocuments()) $companiesRequestingDocuments[] = $empresaCliente;
                                    } else {
                                        $template->assign("error", "Error asignando para la empresa ".$empresaCliente->getUserVisibleName());
                                    }
                                }
                            }
                        }
                    }
                    if ($elemento instanceof empresa) {
                        $elemento->eliminarCadenasContratacionResiduales($empresaUsuario);
                        $elemento->clearCache("obtenerEmpresasCliente-{$elemento->getUID()}-{$elemento->getModuleName()}--");
                    }
                break;
                case 'usuario': 
                    $elementCompany                 = $elemento->getCompany(); //elemento is an user, just one company
                    $companiesWithDocuments         = $elementCompany->obtenerEmpresasClienteConDocumentos();
                    if ($companiesWithDocuments) {
                        if ((bool) $empresaUsuario->countOwnDocuments()) {
                            //own company also has documents
                            $companiesWithDocuments = $companiesWithDocuments->merge($empresaUsuario);
                        }
                        if (isset($_POST["list"])) {
                            $intListCompanies   = ArrayIntList::factory(implode(",",$_POST["list"]));
                            $visiblesCompanies  = $intListCompanies->toObjectList('empresa');
                        } else {
                            //It means the user unchecked all the comapnies, so we remove do not allow to see the documents of all the companies
                            $visiblesCompanies   = new ArrayObjectList;
                        }

                        $setCompaniesTohide     = $companiesWithDocuments->diff($visiblesCompanies);

                        if (!$elemento->setVisibilityForAllCompanies()) break;
                        foreach ($setCompaniesTohide as $company) {
                            $elemento->setCompanyWithHiddenDocuments($company, true, $usuario);
                        }
                    }
                    break;
                default: //maquina y empleado
                    $elemento->eliminarVisibilidad($empresaUsuario);
                    if (isset($_POST["list"])) {
                        foreach($_POST["list"] as $uid){
                            $empresa = new empresa($uid);
                            $companiesSet[] = $empresa;
                            $elemento->hacerVisiblePara($empresa, $empresaUsuario);
                        }
                    }
                break;
            }

            // Es posible que los documentos solicitados cambien
            if ($elemento instanceof solicitable) $elemento->actualizarSolicitudDocumentos();
            $companiesSet = $companiesSet->unique();
            $list = "";
            foreach ($companiesSet as $company) {
                $list .= "uid_empresa = '{$company->getUID()}',";
            }
            $elemento->writeLogUI(logui::ACTION_SET_VISIBILITY,  $list, $usuario);

            if ($assign && !$elemento instanceof signinRequest) $template->assign("succes", "exito_texto");
        } catch(Exception $e) {
            $template->assign("error", $e->getMessage());
        }

    }

    if (($comefrom = obtener_comefrom_seleccionado()) && $comefrom == "nuevo") {
        if (!$elemento instanceof signinRequest)  $template->assign("succes","exito_texto");
        if ($empresaUsuario->justOutRange()) {
            $infoRange = sprintf($template->getString("new_range_license"), CURRENT_DOMAIN.'//licencias-plataforma-CAE-coordinacion-actividades-empresariales.php#/toggle/premium');
            $template->assign("info", $infoRange);
        }
    }

    $template->assign("elemento", $elemento);
    switch($modulo){
        case 'empresa':
        $companies      = $empresaUsuario->obtenerEmpresasSuperioresSubcontratando(NULL, NULL, true, $usuario);
        $setCompanies   = new ArrayObjectList([$elemento]);
        $companies      = $companies->discriminar($setCompanies);
        $template->assign("empresas", $companies);
        $template->display("contratacion.tpl");
        break;
        case 'usuario':
            $hiddenClientes             = $elemento->getCompaniesWithHiddenDocuments();
            $companiesWithDocuments     = $empresaUsuario->obtenerEmpresasClienteConDocumentos();
            $selfDocs                   = (bool) $empresaUsuario->countOwnDocuments();
            if ($selfDocs) {
                $companiesWithDocuments = $companiesWithDocuments->merge($empresaUsuario);
            }
            $template->assign("elemento", $elemento);
            $template->assign("hiddenCompanies", $hiddenClientes);
            $template->assign("empresas", $companiesWithDocuments);
            $template->assign("title", "Visibilidad");
            $template->display("visibilidad.tpl");
        break;
        case 'signinRequest':
            if (isset($_REQUEST["send"])) {
                $forceContinue          =  isset($_REQUEST["continue"]) && $_REQUEST["continue"];
                $ownCompanyDocuments    = $empresaUsuario->countCorpDocuments();
                $erroCompanyNoDocuments = !$forceContinue && (!$ownCompanyDocuments && (!isset($companiesRequestingDocuments) || (isset($companiesRequestingDocuments) && !count($companiesRequestingDocuments))));
                if ($erroCompanyNoDocuments) {
                    $template->assign("error", "No has seleccionado ningún cliente que solicite documentación");
                    $template->assign("continue", true);
                } else {
                    //Clients already configured
                    $elemento->setClientesConfigured($usuario);
                    if (($comefrom = obtener_comefrom_seleccionado()) && $comefrom == "nuevo") {
                        //it is a new invitation
                        if (count($elemento->getAssignData($usuario)) && $elemento->needConfigureAssignment($usuario)) {
                            //if the invitation has groups to assign redirect to asignacion.php and it has nothing assigned
                            $response = [
                                "closebox" => true,
                                "action" => [
                                    "go" => "#asignacion.php?m={$modulo}&poid={$elemento->getUID()}&comefrom=nuevo&return=0"
                                ]
                            ];
                        } else {
                            //if the invitation does not have groups to assign, then show the list of invitations.
                            $response = [
                                "closebox"      => true,
                                "jGrowl"        => $template("invitation_configured"),
                                "hightlight"    => $elemento->getUID(),
                                "action"        => [
                                    "go"    => "#empresa/listado.php?comefrom=invitacion&poid={$empresaUsuario->getUID()}",
                                    "force" => true
                                ]
                            ];
                            //Change the state from need_configuration to not_setn, so the cron task will send it.
                            $elemento->changeStateInvitation(signinRequest::STATE_NOT_SENT);
                            if ($elemento->getCompanyInvited()) {
                                $elemento->createInvitationCompanyAlreadyExists();
                            }
                        }

                        header("Content-type: application/json");
                        print json_encode($response);
                        exit;

                    } else if ($elemento->getState() == signinRequest::STATE_CONFIGURATION && (!$elemento->needConfigureAssignment($usuario) || !count($elemento->getAssignData($usuario)))) {
                        //Case invitation no need configure or it is already configured
                        $elemento->changeStateInvitation(signinRequest::STATE_NOT_SENT);
                        $elemento->createInvitationCompanyAlreadyExists();
                    }

                    //Success text if assign
                    if (isset($assign) && $assign) $template->assign("succes", "exito_texto");
                }
            }


            if (count($elemento->getAssignData($usuario)) && $comefrom == "nuevo") {
                //It has groups to assign and it is a new element
                $template->assign("reload", true);
                $buttonText = $template("continuar");
            } elseif ($comefrom == "nuevo") {
                $template->assign("reload", true);
                //It is a new element and it has not groups to assign
                $buttonText = $template("Invitar");
            } else {
                //The invitation is already created
                $buttonText = $template("Guardar");
            }

            $companies = $empresaUsuario->obtenerEmpresasSuperioresSubcontratando(NULL, NULL, true, $usuario);
            $invitedCompany = $elemento->getCompanyInvited();
            if ($invitedCompany) {
                $setCompanies   = new ArrayObjectList([$invitedCompany]);
                $companies      = $companies->discriminar($setCompanies);
            }
            $template->assign("buttonText", $buttonText);
            $template->assign("empresas", $companies);
            $template->display("contratacion.tpl");
            break;
        default: //maquina y empleado
            $companiesElement   = $elemento->obtenerElementosActivables($usuario);
            $empresaItem        = reset($companiesElement);
            if (!$empresaItem) {
                die("Inaccesible");
            }

            $companies          = $empresaItem->obtenerEmpresasCliente(NULL, $usuario);


            // Verificar si tenemos solicitudes
            $hasRequest = false;
            foreach ($companies as $empresa) {

                // Solicita algún documento?
                if ($num = $empresa->countCorpDocuments()) {

                    // Está asignado el item?
                    if ($elemento->esVisiblePara($empresa, $empresaUsuario)) {
                        $hasRequest = true;
                        break;
                    }
                }
            }
            if (($comefrom = obtener_comefrom_seleccionado()) && $comefrom == elemento::PUBLIFIELDS_MODE_NEW) {
                if (isset($_REQUEST["send"])) {
                    $ownGroupsByModule = $empresaUsuario->obtenerAgrupamientosVisibles(array('modulo' => $elemento->getModuleName(), $usuario));
                    if ($hasRequest === true || count($ownGroupsByModule)) {
                        $response = array(
                            "closebox" => true,
                            "action" => array(
                                "go" => "#asignacion.php?m={$modulo}&poid={$elemento->getUID()}&comefrom=nuevo&return=3"
                            )
                        );

                        header("Content-type: application/json");
                        print json_encode($response);
                        exit;
                    }
                    $template->assign ("className", "async-form");
                } else {
                    $template->assign("closeConfirm",'close-confirm');
                    $template->assign ("className", "async-form");
                }
            }
            if ($hasRequest === false) {
                $noRequestError = sprintf($template("should_select_company_docs"), RESOURCES_DOMAIN . '/img/famfam/folder_page.png');
                $template->assign("error", $noRequestError);
            }

            $template->assign("empresaItem", $empresaItem);
            $template->assign("empresas", $companies);
            $template->display("visibilidad.tpl");
        break;
    }

} elseif( $uids = obtener_uids_seleccionados() ){
    die("Deprecated");
}
?>
