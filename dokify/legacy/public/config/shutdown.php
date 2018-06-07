<?php

function manejador_de_fin($usuario)
{
    if ($usuario instanceof usuario) {
        $request = $_SERVER["REQUEST_URI"];
        if (isset($_REQUEST["type"])) {
            $url = parse_url(substr($request, (strpos($request, "agd/")+4)));
            parse_str($url["query"], $params);

            if (isset($params["type"])) {
                unset($params["type"]);
            }

            if (isset($params["ct"])) {
                unset($params["ct"]);
            }

            $page = $url["path"];
            if (count($params)) {
                $page .= "?" . http_build_query($params);
            }

            if ($_REQUEST["type"] == "ajax") {
                $page = "#$page";
            }

            if (!isset($_SESSION["USUARIO_SIMULADOR"])) {
                $usuario->addPageHistory($page);
            }
        }
    }

    exit;
}
