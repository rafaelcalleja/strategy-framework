<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5c0a92df7d79caf4eb59ef062f97bcb5
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
        ),
        'L' => 
        array (
            'League\\Tactician\\' => 17,
        ),
        'D' => 
        array (
            'DokifyApplication\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'League\\Tactician\\' => 
        array (
            0 => __DIR__ . '/..' . '/league/tactician/src',
        ),
        'DokifyApplication\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5c0a92df7d79caf4eb59ef062f97bcb5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5c0a92df7d79caf4eb59ef062f97bcb5::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
