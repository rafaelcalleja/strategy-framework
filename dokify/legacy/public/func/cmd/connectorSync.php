<?php

require_once __DIR__ . '/../../config.php';

if (!$uid = @$_SERVER["argv"][1]) {
    die("We need a company");
}

$company = new empresa($uid);

$fremap = Dokify\Fremap\Connector::fromCompany($company);

$fremap->sync(false);
