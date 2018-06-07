<?php

include "../../config.php";
new customSession();
$template = Plantilla::singleton();
$log = new log();
$app = Dokify\Application::getInstance();

$context = $app['request_context'];

$context->setHost(get_cfg_var('dokify.domain'));
$context->setScheme(rtrim(get_cfg_var('dokify.protocol'), ':'));
$context->setBaseUrl('/app');

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $_SESSION['token'] = $token;
    unset($_SESSION['form']);
    header("Location: /agd/empresa/new.php");
} else if (isset($_SESSION['token'])) {
    $token = $_SESSION['token'];
} else {
    $template->assign("invalidToken", true);
    $template->display("newcompany/main.tpl");
    exit;
}

if (isset($_REQUEST["m"]) && ($m = trim($_REQUEST["m"])) == "municipio") {
    $municipios = $m::obtenerPorProvincia(obtener_uid_seleccionado());
    $campo = new FormField(array('tag' => 'select', 'data'=> $municipios, "class" => "chzn-select", "name" => "uid_municipio",  "id" => "uid_municipio"));
    $template->assign("campo", $campo);
    $template->display("form/form_parts.inc.tpl");
    exit;
}

if ($token) {
    $signIn = signinRequest::getFromToken($token);

    if ($signIn) {

        if (!isset($_SESSION['form']['nombre_empresa'])) {
            $_SESSION['form']['nombre_empresa'] = $signIn->getName();
        }

        if (isset($_SESSION['form']['uid_pais']) && $_SESSION['form']['uid_pais'] && is_numeric($_SESSION['form']['uid_pais'])) {
            $country = new pais($_SESSION['form']['uid_pais']);
            if ($country->exists()) {
                $lang = $country->getLanguage();
                $map = getLocaleMap();
                $langApp =  (isset($map["$lang"])) ? $map["$lang"]: getCurrentLanguage();

                setcookie("lang", $langApp, time()+60*60*24*30, '/');
                $_SESSION["lang"] = $langApp;
            }
        } else {
            $_SESSION['form']['uid_pais'] = $signIn->getCountry()->getUID();
        }

        if (!isset($_SESSION['form']['email'])) {
            $_SESSION['form']['email'] = $signIn->getInvitationEmail();
        }

        if (!isset($_SESSION['form']['cif'])) {
            $_SESSION['form']['cif'] = $signIn->getVat();
        }

        if (!isset($lang)) {
            $lang = $signIn->getCountry()->getLanguage();
            $map = getLocaleMap();
            $langApp =  (isset($map["$lang"])) ? $map["$lang"]: $langApp = getCurrentLanguage();

            setcookie("lang", $langApp, time()+60*60*24*30, '/');
            $_SESSION["lang"] = $langApp;
            $_SESSION["form"]["locale"] = $lang;

        }

        if (false === isset($_SESSION['form']['locale'])) {
            $_SESSION["form"]["locale"] = $lang;
        }

        $template->assign("lang", $lang);

    }

    if (!$signIn || $signIn->invitationExpired() || $signIn->getState() == signinRequest::STATE_DISCARD) {
        $template->assign("invalidToken", true);
        $template->display("newcompany/main.tpl");
        exit;
    }

    if ($signIn->getState() == signinRequest::STATE_ACCEPTED) {
        $template->assign("step", 5);
        $template->display("newcompany/main.tpl");
        exit;
    }
} else {
    $template->assign("invalidToken", true);
    $template->display("newcompany/main.tpl");
    exit;
}

$_SESSION['form'] = isset($_SESSION["form"]) ? array_merge($_SESSION['form'], $_POST) : $_SESSION['form'] = $_POST;
$step = (isset($_GET["step"])) ? $step = $_GET["step"]: $step = 1;
$data = $_SESSION['form'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = array();
    $validationStep = signinRequest::publicFields(elemento::PUBLIFIELDS_MODE_TAB, $signIn, null, $step-1);
    foreach ($data as $key => $value) {
        if (in_array($key, $validationStep->keys())) {
            $state = $signIn->validate($key, $value, $data);
            if (!$state) {
                 $error[$key]="true";
            }
        }
    }

    $_SESSION['error'] = $error;
}

if (isset($error) && !empty($error)) {
    $step = ($step>1) ? $step - 1 : $step;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($error)) {
        header("Location: /agd/empresa/new.php?step=$step&error=check");
        exit;
    } else {
        header("Location: /agd/empresa/new.php?step=$step");
        exit;
    }
}

if ($step == 5) {

    $log->info("signin_request", "Accept invitation ".$signIn->getInvitationEmail(), $signIn->getCompany()->getUserVisibleName());
    $formData = $_SESSION['form'];
    $signUpElements = $signIn->singUpElementCompanyAndUser($formData);

    if (is_bool($signUpElements) && $signUpElements) {
        $invitedCompany = $signIn->getCompanyInvited();
        $invitedCompanyUser = usuario::fromEmail($formData['email']);

        $companyStoreEvent = new \Dokify\Application\Event\Company\Store(
            $invitedCompany->asDomainEntity(),
            $invitedCompanyUser->asDomainEntity()
        );

        $invitationManageEvent = new \Dokify\Application\Event\Company\Invitation\Manage(
            $signIn->asDomainEntity(),
            $invitedCompanyUser->asDomainEntity()
        );

        $app->dispatch(\Dokify\Events\CompanyEvents::POST_COMPANY_STORE, $companyStoreEvent);
        $app->dispatch(\Dokify\Events\Company\InvitationEvents::POST_COMPANY_INVITATION_ACCEPT, $invitationManageEvent);

        $log->resultado("ok", true);
    } else {
        $template->assign("errorSignup", true);
        if ($signUpElements) {
            $template->assign("error", $signUpElements);
            $log->resultado("Error: ".$signUpElements, true);
        } else {
            $log->resultado("Unknown Error", true);
        }
        $template->display("newcompany/main.tpl");
        exit;
    }

}

if (!isset($_GET["error"]) && $_SERVER['REQUEST_METHOD'] != 'POST') {
    unset($_SESSION['error']);
} else {
    $error= @$_SESSION['error'];
}

$companySender = $signIn->getCompany();
if ($corp =  $companySender->perteneceCorporacion()) {
    $pagoObligatorio = $corp->pagoActivado();
} else {
    $pagoObligatorio = $companySender->pagoActivado();
}

$tiposEmpresa = $companySender->obtenerAgrupadoresVisibles(new categoria(categoria::TYPE_TIPOEMPRESA));
$template->assign("data", $data);

$preventionServiceRepo = $app['company_preventionservice.repository'];
$preventionServices = $preventionServiceRepo->all();
$preventionServices = $preventionServices->toArray();

$inviter = $signIn->getInviterUser();
$inviterEmail = $inviter->getEmail();

if (true === $inviter->esSATI()) {
    $inviterEmail = 'soporte@dokify.net';
}

$template->assign('inviterEmail', $inviterEmail);
$template->assign("inviter", $inviter);
$template->assign("tiposEmpresa", $tiposEmpresa);
$template->assign("kinds", empresa::getKindsSelect());
$template->assign("preventionServices", $preventionServices);
$template->assign("companySender", $companySender);
$template->assign("pagoObligatorio", $pagoObligatorio);
$template->assign("step", $step);
if (!empty($error)) {
    $template->assign("error", $error);
}

$template->display("newcompany/main.tpl");
