<?php

namespace Dokify\Router\Infrastructure\Services\Routing;

use Dokify\Router\Domain\InvalidParameterException;
use Dokify\Router\Domain\MissingMandatoryParametersException;
use Dokify\Router\Domain\RouteNotFoundException;
use Dokify\Router\Domain\RoutingInterface;
use Dokify\Router\Infrastructure\Services\Routing\Symfony\Router;
use Symfony\Component\Routing\RequestContext;

class SymfonyRoutingService implements RoutingInterface
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function match(
        string $baseUrl = '',
        string $method = 'GET',
        string $host = 'localhost',
        string $scheme = 'http',
        int $httpPort = 80,
        int $httpsPort = 443,
        string $path = '/',
        string $queryString = ''
    ) {
        $context = new RequestContext(
            $baseUrl,
            $method,
            $host,
            $scheme,
            $httpPort,
            $httpsPort,
            $path,
            $queryString
        );

        $this->router->setContext($context);

        return $this->router->match($path);
    }

    public function generate(string $routeName, array $parameters, int $referenceType)
    {
        return $this->router->generate($routeName,$parameters, $referenceType);
    }
}
