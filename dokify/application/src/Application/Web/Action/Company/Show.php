<?php

namespace Dokify\Application\Web\Action\Company;

use Dokify\Application\Service\Company\ShowCompanyHandler;
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
        $command = new ShowCompanyHandler(
            $request->getAttribute('company'),
            $request->hasHeader('json')
        );

        return $this->getCommandBus()->handle($command);
    }
}
