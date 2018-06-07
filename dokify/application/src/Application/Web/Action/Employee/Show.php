<?php

namespace Dokify\Application\Web\Action\Employee;

use Dokify\Application\Service\Employee\ShowEmployeeCommand;
use Dokify\Application\Web\Action\ActionInterface;
use Dokify\Port\Adapter\Http\Message\RequestInterface;
use Dokify\Port\Adapter\Messaging\CommandBus;
use Dokify\Port\Adapter\Messaging\CommandBusAwareTrait;

class Show implements ActionInterface
{
    use CommandBusAwareTrait;

    public function __construct(CommandBus $commandBus)
    {
        $this->setCommandBus($commandBus);
    }

    public function __invoke(RequestInterface $request)
    {
        $command = new ShowEmployeeCommand(
            $request->getAttribute('employee')
        );

        return $this->getCommandBus()->handle($command);
    }
}
