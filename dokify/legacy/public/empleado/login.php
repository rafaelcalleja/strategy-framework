<?php

$redirect = '/login.php';

if (isset($_SERVER['QUERY_STRING']) && $query = trim($_SERVER['QUERY_STRING'])) {
	$redirect .= '?' . $query;
}


header("HTTP/1.1 301 Moved Permanently");
header("Location: $redirect");
exit;