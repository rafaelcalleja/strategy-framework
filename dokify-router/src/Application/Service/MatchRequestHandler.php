<?php

namespace Dokify\Router\Application\Service;

use Dokify\Router\Domain\RepositoryInterface;
use Dokify\Router\Domain\RoutingInterface;

class MatchRequestHandler
{
    /**
     * @var RoutingInterface
     */
    private $externalRoutingService;

    public function __construct(RoutingInterface $externalRoutingService)
    {
        $this->externalRoutingService = $externalRoutingService;
    }

    public function execute(MatchRequestCommand $command)
    {
        return $this->externalRoutingService->match(
            $command->getBaseUrl(),
            $command->getMethod(),
            $command->getHost(),
            $command->getScheme(),
            $command->getHttpPort(),
            $command->getHttpsPort(),
            $command->getPath(),
            $command->getQueryString()
        );
    }
}