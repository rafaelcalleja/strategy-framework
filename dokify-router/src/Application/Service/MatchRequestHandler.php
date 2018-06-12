<?php

namespace Dokify\Router\Application\Service;

use Dokify\Router\Domain\Match\MatchException;
use Dokify\Router\Domain\Router\RouterException;
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
        try{
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
        }catch (RouterException $e) {
            return [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
