<?php

namespace Dokify\Infrastructure\Symfony\Bridge\Http;

use Dokify\Port\Adapter\Http\Message\RequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Dokify\Port\Adapter\Http\Message\RequestDecorator;

class HttpFactory
{
    public function createRequest(Request $symfonyRequest): RequestInterface
    {
        $psr7Factory = new DiactorosFactory();
        $psrRequest = $psr7Factory->createRequest($symfonyRequest);

        return new RequestDecorator(
            $psrRequest
        );
    }
}
