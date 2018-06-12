<?php

namespace Dokify\Router\Infrastructure\Services\Routing;

use Dokify\Router\Domain\Generate\InvalidParameterException as DomainInvalidParameterException;
use Dokify\Router\Domain\Generate\MissingMandatoryParametersException as DomainMissingMandatoryParametersException;
use Dokify\Router\Domain\Match\MethodNotAllowedException as DomainMethodNotAllowedException;
use Dokify\Router\Domain\Match\NoConfigurationException as DomainNoConfigurationException;
use Dokify\Router\Domain\Router\RouteNotFoundException as DomainRouteNotFoundException;
use Dokify\Router\Domain\RoutingInterface;
use Dokify\Router\Infrastructure\Services\Routing\Symfony\Router;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
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

    /**
     * @param string $baseUrl
     * @param string $method
     * @param string $host
     * @param string $scheme
     * @param int    $httpPort
     * @param int    $httpsPort
     * @param string $path
     * @param string $queryString
     * @return array
     */
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

        try{
            return $this->router->match($path);
        }catch (\Exception $exception) {
            $exceptionClass = DomainRouteNotFoundException::class;
            if ($exception instanceof MethodNotAllowedException) {
                $exceptionClass = DomainMethodNotAllowedException::class;
            }
            if ($exception instanceof NoConfigurationException) {
                $exceptionClass = DomainNoConfigurationException::class;
            }

            throw new $exceptionClass(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

    }

    public function generate(
        string $routeName,
        array $parameters,
        int $referenceType,
        string $baseUrl = '',
        string $path = '',
        string $host = 'localhost',
        string $scheme = 'http',
        int $httpPort = 80,
        int $httpsPort = 443
    ) {
        try{

            $context = new RequestContext(
                $baseUrl,
                'GET',
                $host,
                $scheme,
                $httpPort,
                $httpsPort,
                $path
            );

            $this->router->setContext($context);

            return $this->router->generate($routeName, $parameters, $referenceType);
        }catch (\Exception $exception) {
            $exceptionClass = DomainRouteNotFoundException::class;
            if ($exception instanceof MissingMandatoryParametersException) {
                $exceptionClass = DomainMissingMandatoryParametersException::class;
            }
            if ($exception instanceof InvalidParameterException) {
                $exceptionClass = DomainInvalidParameterException::class;
            }

            throw new $exceptionClass(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }
}
