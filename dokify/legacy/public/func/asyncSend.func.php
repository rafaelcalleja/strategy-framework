<?php

function asyncSend ($out, Exception $e = null)
{
    $callback = (isset($_GET['callback']) && $cb = trim($_GET['callback'])) ? $cb : null;

    if ($callback) {
        // -- send as json
        if (is_array($out)) {
            $out = json_encode($out);
        } else {
            $out = "'". db::scape($out) ."'";
        }

        if ($e instanceof Exception) {
            $out .= ", {$e->getCode()}";
        } else {
            $out .= ", 200";
        }

        print "<script>top.{$callback}(". $out .");</script>";
    } else {
        if ($e) {
            header($_SERVER['SERVER_PROTOCOL'] .' '. $e->getCode());
        }

        // -- set header if no callback recieved
        if (is_array($out)) {
            header("Content-type: application/json");
            $out = json_encode($out);
        }

        print $out;
    }
}
