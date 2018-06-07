<?php
/**
 * This file was created for testing purposes.
 * We are testing a RaspBerry to open a door when we return HTTP 200 OK
 *
 */

require_once __DIR__ . '/../src/config.php';


if ($URL = @$_REQUEST['url']) {
    $pieces = parse_url($URL);

    if ($pieces['host'] === 'goo.gl') {

        $ch = curl_init($URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if (!isset($info['redirect_url'])) {
            header("HTTP/1.1 404");
            exit;
        }

        $pieces = parse_url($info['redirect_url']);
    }

    // hash case
    if (isset($pieces['fragment'])) {
        $pieces = parse_url($pieces['fragment']);
    }

    if (isset($pieces['query'])) {
        parse_str($pieces['query'], $query);

        if (isset($query['e']) && ($uid = $query['e'])) {

            if ($uid) {
                $empleado = new empleado($uid);

                $status = array_keys($empleado->obtenerEstadoDocumentos(null, 0, 1));

                if (count($status) === 1 && in_array(documento::ESTADO_VALIDADO, $status)) {
                    header("HTTP/1.1 200");
                } else {
                    header("HTTP/1.1 405");
                }

                exit;
            }
        }
    }
}

header("HTTP/1.1 404");
