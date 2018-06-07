<?php

namespace Dokify\Infrastructure\ZendFramework\Bridge\Messaging;

use Dokify\Application\Service\Employee\ShowEmployeeCommand;
use Dokify\Application\Service\Employee\ShowEmployeeHandler;
use Dokify\Infrastructure\Tactician\Bridge\CommandBusDecorator;
use Interop\Container\ContainerInterface;
use League\Tactician\CommandBus;
use League\Tactician\Container\ContainerLocator;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use Zend\ServiceManager\Factory\FactoryInterface;

class CommandBusFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $inflector = new HandleInflector();

        $commandsMapping = [
            ShowEmployeeCommand::class => ShowEmployeeHandler::class,
        ];

        $locator = new ContainerLocator($container, $commandsMapping);

        $nameExtractor = new ClassNameExtractor();

        $commandHandlerMiddleware = new CommandHandlerMiddleware(
            $nameExtractor,
            $locator,
            $inflector
        );

        $commandBus = new CommandBus([
            $commandHandlerMiddleware,
        ]);

        return new CommandBusDecorator($commandBus);
    }
}
