<?php

require_once "config.php";

use Dokify\Dav\Root as RootDirectory;
use Dokify\Dav\Auth;
use Sabre\DAV;
use Sabre\DAV\Server as DavServer;
use Sabre\DAV\Auth\Plugin as AuthPlugin;

$auth = new Auth;
$root = new RootDirectory($auth);

$server = new DavServer($root);
$root->setServer($server);

// We're required to set the base uri, it is recommended to put your webdav server on a root of a domain
// var_dump('/app' . $request->getPathInfo()); exit;
$server->setBaseUri('/dav/');

// Adding the plugin to the server
$server->addPlugin(new AuthPlugin($auth, usuario::APACHE2_REALM));

// The lock manager is reponsible for making sure users don't overwrite
// each others changes.
$lockBackend = new DAV\Locks\Backend\File('data/locks');
$lockPlugin = new DAV\Locks\Plugin($lockBackend);
$server->addPlugin($lockPlugin);


$tffp = new DAV\TemporaryFileFilterPlugin('/tmp');
$server->addPlugin($tffp);

if (CURRENT_ENV == 'dev') {
    $server->addPlugin(new DAV\Browser\Plugin());
}

// And off we go!
$server->exec();

/*
    OLD VERSION, SHOULD BE RE-IMPLEMENTED

    require_once "config.php";
    require_once DIR_CLASS . '/Sabre/Sabre.includes.php';
    require_once DIR_CLASS . '/Sabre/Custom/DirectoryController.class.php';
    require_once DIR_CLASS . '/Sabre/Custom/FileController.class.php';
    require_once DIR_CLASS . '/Sabre/Custom/AuthController.class.php';


    //file_put_contents("/home/jandres/test/file.txt", "REQUEST: ". print_r($_REQUEST, true) ."\n", FILE_APPEND );
    $authBackend = new AuthController();
    $authPlugin = new Sabre_DAV_Auth_Plugin($authBackend, usuario::APACHE2_REALM);

    $root = new DirectoryController("", $authBackend);

    // The server object is responsible for making sense out of the WebDAV protocol
    $server = new Sabre_DAV_Server($root);
    $server->addPlugin($authPlugin);

    // If your server is not on your webroot, make sure the following line has the correct information
    // $server->setBaseUri('/~evert/mydavfolder'); // if its in some kind of home directory
    // $server->setBaseUri('/dav/index.php/'); // if you can't use mod_rewrite, use index.php as a base uri
    // ideally, SabreDAV lives on a root directory with mod_rewrite sending every request to index.php
    $server->setBaseUri('/dav');

    // The lock manager is reponsible for making sure users don't overwrite
    // each others changes. Change 'data' to a different
    // directory, if you're storing your data somewhere else.
    $lockBackend = new Sabre_DAV_Locks_Backend_File('data/locks');
    $lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);

    $server->addPlugin($lockPlugin);



    // All we need to do now, is to fire up the server
    $server->exec();
 */
