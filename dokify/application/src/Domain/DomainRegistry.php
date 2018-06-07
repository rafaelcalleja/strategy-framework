<?php

namespace Dokify\Domain;

use Psr\Container\ContainerInterface;

class DomainRegistry
{
    /**
     * @var ContainerInterface
     */
    private static $container;

    private function __construct(){}

    public static function setContainer(ContainerInterface $container)
    {
        self::$container = $container;
    }

    public function employeeRepository()
    {
        return self::$container->get('employee.repository');
    }
}
