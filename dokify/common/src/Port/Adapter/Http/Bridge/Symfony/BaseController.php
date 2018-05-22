<?php

namespace Dokify\Common\Infrastructure\Port\Adapter\Http\Bridge\Symfony;

use Dokify\Port\Adapter\Messaging\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as SymfonyController;

abstract class BaseController extends SymfonyController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @return CommandBus
     */
    public function getCommandBus(): CommandBus
    {
        return $this->commandBus;
    }
}