<?php

namespace Dokify\Application\Web\Action;

use Dokify\Port\Adapter\Messaging\CommandBus;

interface ActionInterface
{
    public function getCommandBus(): CommandBus;
}