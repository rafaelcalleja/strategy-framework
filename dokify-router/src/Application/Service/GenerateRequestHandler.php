<?php

namespace Dokify\Router\Application\Service;

use Dokify\Router\Domain\RoutingInterface;

class GenerateRequestHandler
{
    /**
     * @var RoutingInterface
     */
    private $externalRoutingService;

    public function __construct(RoutingInterface $externalRoutingService)
    {
        $this->externalRoutingService = $externalRoutingService;
    }

    public function execute(GenerateRequestCommand $command)
    {
        return $this->externalRoutingService->generate(
            $command->getRouteName(),
            $command->getParameters(),
            $command->getReferenceType()
        );
    }
}
