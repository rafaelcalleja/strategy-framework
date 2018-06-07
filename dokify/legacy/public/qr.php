<?php

require_once __DIR__ . '/config.php';

set_time_limit(0);
ini_set("memory_limit", "512M");

$tpl = Plantilla::singleton();

if (isset($_GET['e']) && $e = $_GET['e']) {
    header("Location: /qr/{$e}");
    exit;
}

if (isset($_GET["link"]) && $link = trim($_GET["link"])) {
    require_once __DIR__ . "/../src/lib/qrlib.php";
    QRcode::png($link, false, QR_ECLEVEL_L, 5, 0);
    exit;
}

require_once __DIR__ . '/api.php';


$userCompany    = $usuario->getCompany();
$startList      = $userCompany->getStartList();

if (!($list = obtener_uids_seleccionados()) && $uid = obtener_uid_seleccionado()) {

    $company = new empresa($uid);

    if ($startList->contains($company)) {

        // descargar desde el buscador
        if (isset($_REQUEST['q']) && $search = $_REQUEST['q']) {
            $search .= " empresa:{$userCompany->getUID()}";
            $list = buscador::export($search, $usuario);

        // descargar desde empresa
        } elseif ($employees = $company->obtenerEmpleados(false, false, $usuario)) {
            $list = $employees->toIntList();
        }

    } else {
        die("Inaccesible");
    }
}


$twig = new \Dokify\TwigTemplate('employee/carnet.html');
$viewData = array('items' => array());


$total = count($list);
$filesTmp = array();
$progress = sprintf($tpl->getString("generar_carnet"), '0');
customSession::set('progress', $progress);

if ($list) {
    require_once __DIR__ . "/../src/lib/qrlib.php";
    foreach ($list as $i => $uid) {
        if (CURRENT_ENV === 'dev') {
            $link = CURRENT_DOMAIN . "/qr/{$uid}";
        } else {
            $link = "http://dokify.net/qr/{$uid}";
        }

        $item = new empleado($uid);

        // prevenir acceso de otras empresas
        if (!$item->getCompanies()->match($startList)) {
            continue;
        }

        $name = toCamelCase($item->obtenerDato('nombre'));
        $surname = toCamelCase($item->obtenerDato('apellidos'));

        $itemData['name'] = strlen($name) > 18 ? substr($name, 0, 17)."." : $name;
        $itemData['surname'] = strlen($surname) > 18 ? substr($surname, 0, 17)."." : $surname;
        $itemData['vat'] = $item->obtenerDato('dni');


        $photoPath = $item->getPhoto();
        //$qrPath = CURRENT_DOMAIN .'/qr.php?link='. urlencode($link);

        ob_start();
        QRcode::png($link, false, QRSPEC_VERSION_MAX, 10, 0);
        $qrData = ob_get_clean();

        $itemData['photo'] = 'data:image/png;base64,' . base64_encode(archivo::leer($photoPath));
        $itemData['qr'] = 'data:image/png;base64,' . base64_encode($qrData) ;


        $viewData['items'][] = $itemData;
        if ((($i+1) % 8 == 0) || ($i +1 == $total)) {
            $tmpfname = tempnam(sys_get_temp_dir(), 'qr');
            $filesTmp[] = $tmpfname;
            $html = $twig->render($viewData);
            $output = pdfHandler::htmlToPdf($html);
            file_put_contents($tmpfname, $output);
            $viewData['items'] = array();
            $percent = round(($i * 100) / $total, 2);
            $progress = sprintf($tpl->getString("generar_carnet"), $percent);
            customSession::set('progress', $progress);
        }
    }
}

customSession::set('progress', "-1");
$carnets = pdfHandler::merge($filesTmp);

if (count($filesTmp) == 0 || !is_readable($carnets)) {
    die('<script>alert("'. $tpl('error_desconocido') .'");</script>');
}

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"carnets.pdf\";");
header("Content-Transfer-Encoding: binary");
header("Content-Length: ". filesize($carnets));
header("X-Accel-Redirect: ". $carnets);
//archivo::descargar($carnets, "carnets.pdf");
