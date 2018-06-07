<?php

namespace Dokify\Infrastructure\Application\Bridge\DokifyLegacy\Service;

use Dokify\Common\Port\Adapter\Messaging\CommandBus;
use Dokify\Application\Service\Company\ShowDetailCommand;
use Dokify\Legacy\Application\Service\Company\ShowDetailCommand as LegacyCommand;

class CompanyDetailService
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function __invoke(ShowDetailCommand $command)
    {
        return $this->commandBus->handle(
            new LegacyCommand(
                $command->param1(),
                $command->param2(),
                $command->param3(),
                $command->param4()
            )
        );
    }


}
