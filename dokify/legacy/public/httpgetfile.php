<?php

    include 'config.php';
    $usuario = usuario::httpauth();
    $script_path = dirname( dirname(__FILE__) ).'/dev-tools/';

    if( isset($_REQUEST["file"]) && $file = trim($_REQUEST["file"]) ){
        $log = new log(1, $usuario);
        $log->info('file', 'download file '.$file, $usuario->getUserName(), 'ok', true);
        $path = "/home/dokify/".$file;
    }

    if( isset($_REQUEST["custom"]) && $custom = trim($_REQUEST["custom"]) ){
        $peticiones = explode(" ", $custom);
        exec($script_path.'customexport delete');
        foreach ($peticiones as $key => $peticion) {
            @list($database,$table) = explode(".", $peticion);
            $log = new log(1, $usuario);
            $log->info('custom', 'download file '.$custom, $usuario->getUserName(), 'ok', true);
            exec($script_path.'customexport '.$database.' '.$table);
        }
        exec($script_path.'customexport compress');

        $path = "/home/dokify/dump/custom.bz2";
    }

    if (isset($path) && is_readable($path)) {
        $ext = archivo::getExtension($path);
        switch($ext) 
        {
        case html:
                echo readfile($path);
        break;
        case txt:
                header("Content-type: text/plain");
                echo readfile($path);
        break;
        default:
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: public");
                header("Content-Description: File Transfer");
                header("Content-Transfer-Encoding: binary");
                header("Content-Length: ".filesize($path));
                header("Content-Disposition: attachment; filename=". basename($path));
                readfile($path);
        break;
        }
    }
