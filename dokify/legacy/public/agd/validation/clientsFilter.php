<?php

require __DIR__ . '/../../api.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_REQUEST["clients"]) && $clients = $_REQUEST["clients"]) {
        $_SESSION["CLIENTS"] = implode(',', $clients);
    } else {
        unset($_SESSION["CLIENTS"]);
    }

    $usuario->clearValidationQueue();

    header("Location: /agd/#validation.php?tab=normal");
    exit;
}

$template = Plantilla::singleton();

$partner           = $usuario->getCompany();
$validationConfigs = $partner->getEmpresasPartnersAsPartner();
$allClients        = $validationConfigs->foreachCall("getCompany")->unique();

$selectedClients = [];
if (isset($_SESSION["CLIENTS"])) {
    $selectedClients = explode(',', $_SESSION["CLIENTS"]);
}

$allClients = $allClients->sort(function ($a, $b) use ($selectedClients) {
    // order by name and selected first
    if (
        true === in_array($a->getUID(), $selectedClients)
        || (
            ($a->getUserVisibleName() < $b->getUserVisibleName())
            && false === in_array($b->getUID(), $selectedClients)
        )
    ) {
        return -1;
    }
    return 1;
});

$clientsData = [];
foreach ($allClients as $client) {
    $clientsData[] = [
        'uid' => $client->getUID(),
        'name' => $client->getUserVisibleName(),
        'selected' => in_array($client->getUID(), $selectedClients)
    ];
}

$template->assign("clients", $clientsData);

$template->display('validation/clientsFilter.tpl');
