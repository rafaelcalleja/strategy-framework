<?php

    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /download/document?" . $_SERVER['QUERY_STRING']);
