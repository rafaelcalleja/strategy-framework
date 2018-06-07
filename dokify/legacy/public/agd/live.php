<?php

    ob_start();
    require __DIR__ . '/../api.php';

    if (isset($session)) {
        $t = time();

        // only set session presence sometimes...
        if ($t % 2) {
            $session->presence = false; // not use this for set presence
        }
    }



    $db = db::singleton(false, true);
    $usuarioReal = isset($_SESSION[SESSION_USUARIO_SIMULADOR]) ? new usuario($_SESSION[SESSION_USUARIO_SIMULADOR]) : null;
    $exit = isset($_SESSION['EXIT']) ? $_SESSION['EXIT'] : false;

    $system = new system(1);
    $dataArray = array(time());

    if ($usuario->esValidador()) include("live/validation.php");
    session_write_close();

    include("live/messages.php");
    include("live/functions.php");
    include("live/remote.php");
    include("live/upload.php");
    include("live/process.php");

    if ($usuario->accesoAccionConcreta(29,10,1)) include("live/solicitud.php");
    if ($exit) $dataArray['exit'] = $exit;


    ob_end_clean();
    header("Content-type: application/json");
    print json_encode($dataArray);