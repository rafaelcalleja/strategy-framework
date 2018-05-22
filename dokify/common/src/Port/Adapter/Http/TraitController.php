<?php

namespace Dokify\Common\Infrastructure\Port\Adapter\Http;

use Dokify\Application\Service\Employee\ShowEmployeeCommand;
use Dokify\Port\Adapter\Messaging\CommandBus;

trait TraitController
{
    private $commandBus;

    private $commandFactory;


    public function getCommandFromRequest(RequestInterface $request): CommandFactory
    {
        return new ShowEmployeeCommand(
            $request->getAttribute('id')
        );

    }

    public function handle($request)
    {
        $command = $this->getCommandFromRequest($request);

        return $this->getCommandBus()->handle($command);
    }

    public function getCommandBus(): CommandBus
    {
        return $this->commandBus;
    }

    /**
     * @param mixed $commandBus
     */
    public function setCommandBus($commandBus): void
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @param mixed $commandFactory
     */
    public function setCommandFactory($commandFactory): void
    {
        $this->commandFactory = $commandFactory;
    }
}