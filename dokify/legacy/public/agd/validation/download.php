<?php

require_once("../../api.php");
ini_set('memory_limit', '256M');

$uid = $_REQUEST["fileId"];
$module = $_REQUEST["module"];

if ($uid && $module) {
    $fileId = new fileId($uid, $module);
    if ($fileId) {
        $file = $fileId->getFile();
        if ($file) {
            return archivo::descargar($file, $fileId->getDocument()->getUserVisibleName());
        }
    }
}

header('HTTP/1.1 404 Not Found');
