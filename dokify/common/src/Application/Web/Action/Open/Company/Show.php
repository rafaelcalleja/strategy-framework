<?php

namespace Dokify\Application\Web\Action\Open\Company;

use Dokify\Application\Service\Company\ShowPublicCompanyHandler;
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
        $command = new ShowPublicCompanyHandler(
            $request->getAttribute('company')
        );

        return $this->getCommandBus()->handle($command);
    }
}
