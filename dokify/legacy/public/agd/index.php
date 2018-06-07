<?php
require __DIR__ . '/../api.php';

$mobile = is_mobile_device();
$system = new system(1);
$embedded = isset($_GET['embedded']);
if ($system->getSystemStatus() == 0) {
    include __DIR__ . '/../mantenimiento.html';
    exit;
}

$empresaUsuario = $usuario->getCompany();

if (false === $embedded && $usuario instanceof usuario) {
    $appVersion = $usuario->getAppVersion();
    if ($mobile === true) {
        $usuario->setAppVersion(2);
        $appVersion = 2;
    }

    $min = (int) $empresaUsuario->obtenerDato("min_app_version");
    if ($min > 1 && $usuario->esStaff() === false) {
        $usuario->setAppVersion(2);
        $appVersion = 2;
    }

    if ($appVersion === 2) {
        die('<script>location.href="/app/relocation?fragment="+ encodeURIComponent(location.hash.substring(1).replace(/\+/g, "%2B"));</script>');
        exit;
    }
}


// --- procesar los cambios en la bbdd si los hubiera
if (CURRENT_ENV === 'dev') {
    require_once dirname(__FILE__) . '/../../dev-tools/dbversion.php';
    try {
        if ($num = process_db_changes()) {
            print "Procesados {$num} fichero(s) SQL\n";
        }
    } catch (Exception $e) {
        print "Error al procesar las SQL. [{$e->getMessage()}]";
    }
}

if( isset($_GET["lang"]) && $lang = $_GET["lang"] ){
    setcookie("lang", $lang, time()+60*60*24*30 );
    $_SESSION["lang"] = Plantilla::getCurrentLocale();
}

if ($usuario instanceof empleado) {
    header("Location: ../empleado/");
    exit;
}

//----- creamos la instancia de la plantilla
$template = Plantilla::singleton();


//----------- objeto perfil del usuario actual
$perfilActivo   = $usuario->perfilActivo();
$userContinue   = isset($_SESSION['user_continue']);

if ($userContinue == false && $url = $usuario->needsRedirectToPayment()) {
    header("Location: $url");
    exit;
}

// never show pay page after access /agd/
$_SESSION['user_continue'] = true;

// Perfiles del usuario activo
$perfilesUsuarioActivo = $usuario instanceof usuario ? $usuario->obtenerPerfiles() : array();



$avisos = array();

//----------- comprobamos si el perfil esta activo
if (!$perfilActivo instanceof perfil || !$perfilActivo->isActive()) {

    if (!count($perfilesUsuarioActivo)) {
        header("Location: salir.php?loc=perfilinactivo&manual=1");
        exit;
    }

    $perfilesUsuarioActivo[0]->activate();
    $string = "Cambiando a perfil {$perfilesUsuarioActivo[0]->getUserVisibleName()} por tener el perfil {$perfilActivo->getUserVisibleName()} bloqueado";
    $perfilActivo = $usuario->perfilActivo();

    $avisos[] = array("titulo" => "Perfil cambiado", "texto" => $string );
}

$modulosDisponibles = $usuario->obtenerElementosMenu($mobile);


//----- opciones de menu disponibles para el usuario
if ($usuario->isViewFilterByGroups()) {
    $agrupamientos = $usuario->obtenerAgrupamientosAsignados(null, true);
    if (!count($agrupamientos)) {
        if (count($perfilesUsuarioActivo) === 1 && $perfilesUsuarioActivo[0]->getUID() === $perfilActivo->getUID()) {
            header("Location: salir.php?loc=sinagrupadores&manual=1");
            exit;
        }

        $exit = true;
        foreach ($perfilesUsuarioActivo as $otroPerfil) {
            if ($otroPerfil->compareTo($perfilActivo) === false) {
                $otroPerfil->activate();
                $usuario = $otroPerfil->getUser();
                $usuario->clearItemCache();
                if (false === $usuario->isViewFilterByGroups()) {
                    $exit = false;
                    break;
                }

                $agrupamientos  = $usuario->obtenerAgrupamientosAsignados(null, true);
                $companyUser    = $usuario->getCompany();
                $countEmployees = $companyUser->obtenerEmpleados(false, false, $usuario, true);
                $countMachines  = $companyUser->obtenerMaquinas(false, false, $usuario, true);
                $countCompanies = $companyUser->obtenerIdEmpresasInferiores(false, false, $usuario, 0, true);
                $totalCount     = $countCompanies + $countEmployees + $countMachines;
                if (count($agrupamientos) && $totalCount !== 0) {
                    $exit = false;
                }
            }
        }

        if ($exit) {
            header("Location: salir.php?loc=sinagrupadores&manual=1");
            exit;
        }

        $modulosDisponibles = $usuario->obtenerElementosMenu($mobile);
        $string = "Cambiando a perfil {$perfilesUsuarioActivo[0]->getUserVisibleName()} por no tener suficientes permisos en el perfil {$perfilActivo->getUserVisibleName()}";
        $avisos[] = array("titulo" => "Perfil cambiado", "texto" => $string);
    } else {
        $numeroTotalEmpleados = $empresaUsuario->obtenerEmpleados(false, false, $usuario, true);
        $numeroTotalMaquinas = $empresaUsuario->obtenerMaquinas(false, false, $usuario, true);
        $numeroTotalEmpresas = $empresaUsuario->obtenerIdEmpresasInferiores(false, false, $usuario, 0, true);
        if ($numeroTotalEmpresas == 0 && $numeroTotalEmpleados == 0 && $numeroTotalMaquinas == 0) {
            header("Location: salir.php?loc=sinagrupadores&manual=1");
            exit;
        }
    }
}



//----- si no hay modulos, expulsamos al usuario y le explicamos por que con loc=sinpermisos
if (!count($modulosDisponibles)) {
    if( !count($perfilesUsuarioActivo) || ( count($perfilesUsuarioActivo) == 1 && $perfilesUsuarioActivo[0]->getUID() == $perfilActivo->getUID() ) ){
        header("Location: salir.php?loc=sinpermisos&manual=1"); exit;
    }

    $perfilesUsuarioActivo[0]->activate();
    $modulosDisponibles = $usuario->obtenerElementosMenu($mobile);
    $string = "Cambiando a perfil {$perfilesUsuarioActivo[0]->getUserVisibleName()} por no tener suficientes permisos en el perfil {$perfilActivo->getUserVisibleName()}";
    $avisos[] = array("titulo" => "Perfil cambiado", "texto" => $string );

}


//------ comprobamos el perfil activo
if( !$perfilActivo->getCompany()->exists() ){
    if( !count($perfilesUsuarioActivo) || ( count($perfilesUsuarioActivo) == 1 && $perfilesUsuarioActivo[0]->getUID() == $perfilActivo->getUID() ) ){
        header("Location: salir.php?loc=sinempresa&manual=1"); exit;
    }

    $perfilesUsuarioActivo[0]->activate();
    $modulosDisponibles = $usuario->obtenerElementosMenu($mobile);

    $avisos[] = array(
        "titulo" => "Empresa desactivada",
        "texto" => "La empresa asociada al perfil <strong>{$perfilActivo->getUserVisibleName()}</strong> no se encuentra disponible"
    );
}


if (!$mobile) {
    try {
        // Solicitudes genericas para cualquier empresa, todas deberían estar aqui dentro (incluso las de arriba)
        if( $solicitudes = $usuario->getEmpresaSolicitudPendientes() ){
            foreach($solicitudes as $solicitud) $avisos[] = $solicitud->getInPageAlert();
        }
    } catch (Exception $e) {
        if (CURRENT_ENV == 'dev') {
            error_log('Aqui pasa algo raro.');
        }
    }

    //------ leemos para mostrar la version
    $changeLog = archivo::leer("change.log");
    $changeLogExploded = explode("\n", $changeLog);
    $changeLogExplodedArray = new ArrayObject($changeLogExploded);
    $appVersion = reset($changeLogExplodedArray);

    $svnfile = dirname(dirname(__FILE__)) . "/version";
    if( is_readable($svnfile) && $versionSVN = archivo::leer($svnfile) ){
        $appVersion .= " r$versionSVN";
    }

    $template->assign("version", $appVersion);
}

//------ definimos varibales de la plantilla
if(!$mobile && $usuario->accesoAccionConcreta(29,10,1) && count($avisos) ){ $template->assign("avisos", $avisos ); }

$template->assign("usuario", isset($_SESSION[SESSION_USUARIO_SIMULADOR]) ? new usuario($_SESSION[SESSION_USUARIO_SIMULADOR]) : $usuario );
$template->assign("modules", $modulosDisponibles );
$template->assign("logo", $empresaUsuario->obtenerLogo() );
$template->assign("empresaUsuario", $empresaUsuario );
$template->assign("perfilActivo", $perfilActivo);
$template->assign( "readonly", db::isReadOnly());
$template->assign( "pluginfiles", true);
$template->assign( "currentAPP", "Dokify");
$template->assign( "route", "main.tpl");

$nextQR = isset($_GET['origin']) && $_GET['origin'] == 'dokireader';
$template->assign("nextQR", $nextQR);
$template->assign("embedded", $embedded);

//------ mostramos la plantilla
if ($mobile) {
    $template->display("touch/index.tpl");
} else {
    $template->display("index.tpl");
}
