<?php

namespace Dokify\Infrastructure\Tactician\Bridge;

use Dokify\Port\Adapter\Messaging\CommandBus;

class CommandBusDecorator implements CommandBus
{
    /**
     * @var \League\Tactician\CommandBus
     */
    private $commandBus;

    public function __construct(\League\Tactician\CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle($command)
    {
        return $this->commandBus->handle($command);
    }
}