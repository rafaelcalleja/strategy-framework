<?php

require __DIR__ . '/api.php';

// --- remove next commet for dev testing without S3
// sleep(2); view::asyncSend('http://dokify.net');exit;

$callback = (isset($_GET['callback']) && $cb = trim($_GET['callback'])) ? $cb : null;

try {
    $file = archivo::getUploadedFile('file', $usuario->maxUploadSize());

    if ($link = archivo::getPublicLink($file->path, $file->name)) {
        if ($callback) {
            die("<script>top.{$callback}({$link}, 200);</script>");
        }

        die($link);
    }
} catch (Exception $e) {
    if ($callback) {
        $json = json_encode($response);
        die("<script>top.{$callback}({$json}, {$e->getMessage()});</script>");
    }

    header($_SERVER['SERVER_PROTOCOL'] .' '. $e->getCode());
    exit;
}

if ($callback) {
    die("<script>top.{$callback}('', 500);</script>");
}

// --- something went wrong!
header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
