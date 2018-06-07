<?php

// parse from not loaded ini file
$file = '/etc/php/7.2/cli/conf.d/release.ini';
if ($ini = @parse_ini_file($file)) {
    return define('VKEY', $ini['dokify.release']);
}

$file = '/etc/php/7.0/cli/conf.d/release.ini';
if ($ini = @parse_ini_file($file)) {
    return define('VKEY', $ini['dokify.release']);
}

// get based on git hash
$root = __DIR__ . '/../..';
if (!$head = file_get_contents("{$root}/.git/HEAD")) {
    return define('VKEY', 'unknown');
}

$refs = substr($head, 5, -1);
$fileSHA = "{$root}/.git/{$refs}";
if (file_exists($fileSHA)) {
    return define('VKEY', trim(file_get_contents($fileSHA)));
} else {
    return define('VKEY', 'unknown');
}
