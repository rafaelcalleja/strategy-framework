<?php

require __DIR__ . '/../../api.php';

if (!($uid = obtener_uid_seleccionado())) {
    die("Inaccesible");
}

$uploaderCompany = new empresa($uid);
$requests = $uploaderCompany->obtenerSolicitudDocumentos($usuario, ["papelera" => 0]);

$clients = [];
$documents = [];

foreach ($requests as $request) {
    $client = $request->getClientCompany();

    $clients[$client->getUID()] = $client->getUserVisibleName();
    if (isset($documents[$client->getUID()]) === false) {
        $documents[$client->getUID()] = [];
    }

    $statusData = $request->getStatusData();

    if (in_array($statusData['status'], [documento::ESTADO_ANEXADO, documento::ESTADO_VALIDADO])) {
        $attachment = $request->getAnexo();

        $fileId = null;
        if ($attachment) {
            $fileId = $attachment->getFileId();
        }

        $documents[$client->getUID()][$request->getUID()] =[
            'name' =>  $request->getUserVisibleName(),
            'statusData' => $statusData,
            'fileId' => $fileId,
            'uploadDate' => $attachment->getTimestamp(),
        ];
    }

}

$template = Plantilla::singleton();

$tab = isset($_REQUEST["tab"]) ? $_REQUEST["tab"] : validation::TYPE_VALIDATION_URGENT;

$template->assign("tab", $tab);
$template->assign('uploaderCompany', $uploaderCompany);
$template->assign('clients', $clients);
$template->assign('documents', $documents);

$template->display('validation/uploaderDocuments.tpl');
