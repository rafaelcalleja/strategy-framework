<?php

// Test if PHP has loaded dokify.ini
if (!$env = get_cfg_var('dokify.env')) {
    http_response_code(501);
    exit;
}

// Test if PHP has loaded release.ini
if (!$vkey = get_cfg_var('dokify.release')) {
    http_response_code(502);
    exit;
}

// Test if env lib is already
if (!@require_once('AWSSDKforPHP/sdk.class.php')) {
    http_response_code(503);
    exit;
}

// Test the db connection
$dbuser = get_cfg_var('mysqli.default_user');
$dbpass = get_cfg_var('mysqli.default_pw');
$dbhost = get_cfg_var('mysqli.default_host');


if (!mysqli_real_connect(mysqli_init(), $dbhost, $dbuser, $dbpass)) {
    http_response_code(504);
    exit;
}

// check redis server
if ($redisServer = get_cfg_var('redis.server')) {

    try {
        $redis = new Redis;
        $redis->connect($redisServer);
        $redis->ping();
    } catch (Exception $e) {
        error_log("Redis server is down in {$redisServer}: {$e->getMessage()}");
        http_response_code(418);
    }
}
