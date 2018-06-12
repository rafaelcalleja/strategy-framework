<?php

namespace Dokify\Router\Application\Service;

use Dokify\Router\Domain\Router\RouterException;
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
        try{

            if (in_array($command->getReferenceType(), [RoutingInterface::ABSOLUTE_URL,RoutingInterface::NETWORK_PATH]) && empty($command->getHost())) {
                throw new RouterException('Absolute/Network URL require hostname context');
            }

            return $this->externalRoutingService->generate(
                $command->getRouteName(),
                $command->getParameters(),
                $command->getReferenceType(),
                $command->getBaseUrl(),
                $command->getPath(),
                $command->getHost(),
                $command->getScheme(),
                $command->getHttpPort(),
                $command->getHttpsPort()
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
