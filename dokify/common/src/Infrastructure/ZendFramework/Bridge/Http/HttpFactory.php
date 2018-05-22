<?php

namespace Dokify\Infrastructure\ZendFramework\Bridge\Http;

use Dokify\Port\Adapter\Http\Message\RequestDecorator;
use Dokify\Port\Adapter\Http\Message\RequestInterface;
use Zend\Http\Request as ZendRequest;
use Zend\Mvc\MvcEvent;
use Zend\Psr7Bridge\Psr7ServerRequest;

class HttpFactory
{
    /**
     * @var MvcEvent
     */
    private $mvcEvent;

    public function __construct(MvcEvent $mvcEvent)
    {
        $this->mvcEvent = $mvcEvent;
    }

    public function createRequest(ZendRequest $zendRequest): RequestInterface
    {
        $requestDecorator = new RequestDecorator(
            Psr7ServerRequest::fromZend($zendRequest)
        );


        foreach ($this->mvcEvent->getRouteMatch()->getParams() as $name => $value) {
            $requestDecorator = $requestDecorator->withAttribute($name, $value);
        }

        return $requestDecorator;
    }
}
