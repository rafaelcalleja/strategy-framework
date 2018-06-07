<?php
require __DIR__ . '/../../api.php';

if (!($modulo = obtener_modulo_seleccionado()) || !($uid = obtener_uid_seleccionado())) {
    die("Inaccesible");
}

$element = new $modulo($uid);
$assignments = $element->getAssignments($usuario);

$clients = [];
$organizations = [];
$groups = [];

foreach ($assignments as $assignment) {
    $organization = $assignment->getOrganization();
    $client = $organization->getCompany();
    $group = $assignment->getGroup();

    $clients[$client->getUID()] = $client->getUserVisibleName();

    if (isset($organizations[$client->getUID()]) === false) {
        $organizations[$client->getUID()] = [];
    }
    $organizations[$client->getUID()][$organization->getUID()] = $organization->getUserVisibleName();

    if (isset($groups[$organization->getUID()]) === false) {
        $groups[$organization->getUID()] = [];
    }
    $groups[$organization->getUID()][$group->getUID()] = [
        'name' => $group->getUserVisibleName(),
        'bounce' => null
    ];

    if ($bounce = $assignment->getBounce()) {
        $groups[$organization->getUID()][$group->getUID()]['bounce'] = $bounce->getUserVisibleName();
    }
}

$template = Plantilla::singleton();
$template->assign('element', $element);
$template->assign('clients', $clients);
$template->assign('organizations', $organizations);
$template->assign('groups', $groups);
$template->display('validation/elementAssignments.tpl');
