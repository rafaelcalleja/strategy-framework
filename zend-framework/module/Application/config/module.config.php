<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Dokify\Application\Service\Employee\ShowEmployeeHandler;
use Dokify\Infrastructure\Persistence\InMemory\Employee\InMemoryEmployeeRepository;
use Dokify\Infrastructure\ZendFramework\Bridge\Http\HttpFactory;
use Interop\Container\ContainerInterface;
use Zend\Router\Http\Literal;
use Zend\Router\Http\Segment;
use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'employee' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/employee/:employee',
                    'constraints' => [
                        'employee' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\EmployeeController::class,
                        'action'     => 'show',
                    ],
                ],
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'command_bus' => \Dokify\Infrastructure\ZendFramework\Bridge\Messaging\CommandBusFactory::class,
            'http.factory' => function(ContainerInterface $container) {
                return new HttpFactory($container->get('Application')->getMvcEvent());
            },
            \Dokify\Application\Service\Employee\ShowEmployeeHandler::class => function(ContainerInterface $container) {
                return new ShowEmployeeHandler(
                    $container->get('inmemory_employee.repository')
                );
            },
            'inmemory_employee.repository' => function(ContainerInterface $container) {
                return new InMemoryEmployeeRepository();
            }
        ]
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\EmployeeController::class => InvokableFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
