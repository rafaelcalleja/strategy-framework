<?php

require __DIR__ . '/../../api.php';


if (!$uid = obtener_uid_seleccionado()) {
    header("HTTP/1.1 404");
    exit;
}

$empleado = new empleado($uid);

if (!$usuario->accesoElemento($empleado)) {
    header("HTTP/1.1 404");
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'checkin';

$userCompany = $usuario->getCompany();
$app = \Dokify\Application::getInstance();
$entity = $empleado->asDomainEntity();

$profile = $usuario->perfilActivo();

if ($role = $profile->getActiveRol()) {
    $role = $role->asDomainEntity();
} else {
    $role = null;
}

$userEntity = $usuario->asDomainEntity();
$profile = $profile->asDomainEntity();
$company = $usuario->getCompany()->asDomainEntity();

$login = new \Dokify\Domain\Login(
    $app['profile.access'],
    $userEntity,
    $profile,
    $company,
    $role,
    null
);

switch ($action) {
    case 'checkin':
        if ($empleado->writeLogUI(logui::ACTION_PLACE_ACCESS, $userCompany->getUID(), $usuario)) {
            $companies = $empleado->getCompanies(false, $usuario);
            $validCompanies = [];
            foreach ($companies as $company) {
                if ($empleado->getStatusInCompany($usuario, $company)) {
                    $validCompanies[] = $company;
                }
            }

            $checkinCompany = null;
            if (count($validCompanies) === 1) {
                $checkinCompany = $validCompanies[0]->asDomainEntity();
            }

            $event = new \Dokify\Application\Event\Employee\Checkin($entity, $login, $checkinCompany);
            $app->dispatch(\Dokify\Events::EMPLOYEE_CHECKIN, $event);

            $tpl = new Plantilla();
            $tpl->display("empleado/place_access.tpl");
        }
        break;

    case 'checkout':
        if ($empleado->writeLogUI(logui::ACTION_PLACE_LEAVE, $userCompany->getUID(), $usuario)) {
            $event = new \Dokify\Application\Event\Employee\Checkout($entity, $profile);
            $app->dispatch(\Dokify\Events::EMPLOYEE_CHECKOUT, $event);

            $tpl = new Plantilla();
            $tpl->display("empleado/place_leave.tpl");
        }
        break;
}
