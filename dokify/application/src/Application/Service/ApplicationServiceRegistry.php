<?php

namespace Dokify\Application\Service;

use Psr\Container\ContainerInterface;

class ApplicationServiceRegistry
{
    /**
     * @var ContainerInterface
     */
    private static $container;

    private function __construct(){}

    private function __clone(){}

    private function __wakeup(){}

    /**
     * @param ContainerInterface $container
     */
    public static function setContainer(ContainerInterface $container)
    {
        self::$container = $container;
    }

    public static function commandBus()
    {
        return self::$container->get('command_bus');
    }

    public static function httpFactory()
    {
        return self::$container->get('http.factory');
    }
}
