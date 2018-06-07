<?php

    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /login?" . $_SERVER['QUERY_STRING']);
