<?php

require __DIR__ . '/../api.php';

//------ Sacar el modulo seleccionado
if (!$modulo = obtener_modulo_seleccionado()) {
    die("Inaccesible");
}

// ----- Buscamos el uid seleccionado
$idElemento = obtener_uid_seleccionado();

if ($modulo == "usuario" && !is_numeric($idElemento)) {
    $selectedUser = usuario::instanceFromUsername($_REQUEST["poid"]);
    if ($selectedUser instanceof usuario) {
        $idElemento = $selectedUser->getUID();
    }
}

if (!is_numeric($idElemento)) {
    die("Inaccesible");
}

$fromQR = isset($_REQUEST["src"]) && $_REQUEST["src"] = "qr";
if (!$fromQR && is_mobile_device() && in_array($modulo, solicitable::getModules())) {
    print json_encode(array("action" => array("go" => "#documentos.php?m={$modulo}&poid={$idElemento}")));
    exit;
}

// ----- Definimos la plantilla del usuario
$template = Plantilla::singleton();


// Instanciar y asignar el item
$objeto = new $modulo($idElemento);
$template->assign("elemento", $objeto);

if ($address = trim($usuario->getAddress())) {
    $template->assign('userAddress', true);
}

$isMobile    = is_mobile_device();
$doQRStuff    = ($isMobile && $fromQR) || $address;

if ($fromQR && $objeto instanceof empleado && is_mobile_device()) {
    if (isset($_GET['profile']) && $p = $_GET['profile']) {
        $profile = new perfil($p);

        $usuario->cambiarPerfil($profile);
    } else {
        $userProfiles    = $usuario instanceof usuario ? $usuario->obtenerPerfiles() : array();
        $validProfiles    = [];

        foreach ($userProfiles as $profile) {
            if ($profile->accesoElemento($objeto)) {
                $validProfiles[] = $profile;
            }
        }

        // si no hay ningun perfil válido
        if (count($validProfiles) === 0) {
            $template->display("sin_acceso_qr.tpl");
            exit;
        }

        $geoUser    = $objeto->getLocationUser();
        $sameUser    = $usuario->compareTo($geoUser);
        $ask        = count($validProfiles) > 1 && $sameUser === false;

        // si tenemos mas de un posible perfil siempre y cuando no sea una salida registrada por el mismo usuario
        if ($ask) {
            $template->assign('profiles', $validProfiles);
            $template->display("touch/pickprofile.tpl");
            exit;
        } else {
            $profile = reset($validProfiles);
            $usuario->cambiarPerfil($profile);
        }

        // print "Tenemos ". count($validProfiles) . " perfiles válidos";exit;
    }
}


// Si no tenemos acceso ni fuera ni dentro de la papelera
$isAccesible = $usuario->accesoElemento($objeto, null, null);

if (!$isAccesible) {
    // Si venimos del QR
    if ($fromQR) {
        $template->display("sin_acceso_qr.tpl");
        exit;
    }

    $template->display("sin_acceso_perfil.tpl");
    if (!$usuario->esStaff()) {
        exit;
    }

} elseif (!$usuario->accesoElemento($objeto)) {
    $template->assign("objeto", $objeto);

    if ($objeto instanceof Iactivable && !$objeto instanceof usuario) {
        // Si venimos del QR
        if ($fromQR) {
            $template->display("sin_acceso_qr.tpl");
            exit;
        }

        $template->assign("papelera", true);
    }
}


if ($objeto instanceof solicitable) {
    $asig = $objeto->getInlineIcons($usuario);
    $template->assign('agrupadores', $asig);
}


if ($objeto instanceof solicitable) {
    //$informacionEstadoDocumentos = $objeto->obtenerEstadoDocumentos($usuario, 0, 1);

    //$isValid = count($informacionEstadoDocumentos) == 1 && isset($informacionEstadoDocumentos[2]);
    //$color = count($isValid) ? 'green' : 'red';

    $imgStatus = $objeto->getStatusImage($usuario);
    $template->assign('itemstatus', $imgStatus['color']);
    $template->assign('avisosestado', array(array(
        "string" => $imgStatus['title'],
        "class" => "elemento-status-bar elemento-status-{$imgStatus['color']}"
    )));
}

// Comprobamos de donde viene para si queremos mostrar accesos directos
if (obtener_comefrom_seleccionado() == elemento::PUBLIFIELDS_MODE_NEW) {
    if (is_callable(array($objeto, "afterInsertOptions"))) {
        $template->assign("botones", $objeto->afterInsertOptions($usuario));
    } else {
        $template->assign("acciones", array(
            array("href" => "$modulo/nuevo.php", "string" => "insertar_otro_$modulo", "class" => "box-it reloader")
        ));
    }
    if ($usuario->getCompany()->justOutRange()) {
        $infoRange = sprintf(
            $template->getString("new_range_license"),
            CURRENT_DOMAIN.'/licencias-plataforma-CAE-coordinacion-actividades-empresariales.php#/toggle/premium'
        );
        $template->assign("info", $infoRange);
    }
}

if (is_subclass_of($objeto, 'childItemEmpresa')) {
    $empresas = $objeto->getCompanies(false, $usuario);

    $valids = 0;
    foreach ($empresas as $empresa) {
        if ($objeto->getStatusInCompany($usuario, $empresa)) {
            $valids++;
        }
    }

    $template->assign('validCompanies', $valids);
    $template->assign('empresas', $empresas);
}


if ($objeto instanceof empleado) {
    if ($doQRStuff) {
        $showButtons    = true;
        $retryAccess    = false;
        $leaving        = false;
        $exitButton        = false;

        if ($fromQR) {
            $objeto->writeLogUI(logui::ACTION_FICHA, "", $usuario);
        }

        if ($geoUser = $objeto->getLocationUser()) {
            $showButtons = false;
            $template->assign('inside', true);

            $sameUser    = $usuario->compareTo($geoUser);
            $time        = $objeto->getLocationTimestamp();
            $diff        = time() - $time;

            if ($isMobile) {
                if ($diff > empleado::MINIMUM_PLACE_STAY || $sameUser == false) {
                    // if the user is staff, must be the same. If not, unset anyway
                    $unsetLocation = ($usuario->esStaff() && $sameUser) || !$usuario->esStaff();

                    // for dev env we dont need to prevent support scans
                    if (CURRENT_ENV == 'dev') {
                        $unsetLocation = true;
                    }

                    // Si el empleado viene de otro centro de trabajo
                    if ($sameUser == false) {
                        $showButtons = true;
                        $template->assign('moving', true);
                    }


                    if ($unsetLocation) {
                        $app        = \Dokify\Application::getInstance();
                        $profile    = $usuario->perfilActivo()->asDomainEntity();
                        $entity     = $objeto->asDomainEntity();
                        $event      = new \Dokify\Application\Event\Employee\Checkout($entity, $profile);
                        $app->dispatch(\Dokify\Events::EMPLOYEE_CHECKOUT, $event);

                        // only set leaving to true if we dont show the buttons, which means other user is scanning
                        $leaving = ($showButtons === false);


                        $actionUser = $usuario->compareTo($geoUser) ? $usuario : null;

                        $objeto->writeLogUI(logui::ACTION_PLACE_LEAVE, "", $actionUser);
                    }
                } else {
                    $retryAccess = true;
                }
            } else {
                $exitButton = true;
            }
        }

        $template->assign('showExitButton', $exitButton);
        $template->assign('leaving', $leaving);
        $template->assign("showQRButtons", $showButtons);
        $template->assign("retryQR", $retryAccess);

        // --- TELL TEMPLATE THIS IS A QR REQUEST
        $template->assign("qr", true);
    }



    if (count($maquinas = $objeto->obtenerMaquinas())) {
        $more = array(
            "string" => "ver_maquinas_asignadas",
            "href"   => "#buscar.php?p=0&q=tipo:maquina%20empleado:{$objeto->getUID()}",
            "class"  => "unbox-it"
        );

        $template->assign('maquinas', $maquinas);
        $template->assign("masMaquinas", $more);
    }
}


if (is_mobile_device()) {
    $templatefile = DIR_TEMPLATES . "touch/ficha/{$objeto->getType()}.tpl";
    if (is_readable($templatefile)) {
        $template->display("touch/ficha/{$objeto->getType()}.tpl");
        exit;
    }
}


$template->display("ficha_elemento.tpl");
