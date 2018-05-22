<?php

namespace Dokify\Common\Infrastructure\Port\Adapter\Http\Bridge\Zend;

use Dokify\Port\Adapter\Messaging\CommandBus;
use Zend\Mvc\Controller\AbstractActionController as ZendController;

abstract class BaseController extends ZendController
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