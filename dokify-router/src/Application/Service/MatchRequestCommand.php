<?php

namespace Dokify\Router\Application\Service;

class MatchRequestCommand
{
    /**
     * @var string
     */
    private $baseUrl;
    /**
     * @var string
     */
    private $method;
    /**
     * @var string
     */
    private $host;
    /**
     * @var string
     */
    private $scheme;
    /**
     * @var int
     */
    private $httpPort;
    /**
     * @var int
     */
    private $httpsPort;
    /**
     * @var string
     */
    private $path;
    /**
     * @var string
     */
    private $queryString;

    public function __construct(
        string $baseUrl = '',
        string $method = 'GET',
        string $host = 'localhost',
        string $scheme = 'http',
        int $httpPort = 80,
        int $httpsPort = 443,
        string $path = '/',
        string $queryString = ''
    ) {
        $this->baseUrl = $baseUrl;
        $this->method = $method;
        $this->host = $host;
        $this->scheme = $scheme;
        $this->httpPort = $httpPort;
        $this->httpsPort = $httpsPort;
        $this->path = $path;
        $this->queryString = $queryString;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @return int
     */
    public function getHttpPort(): int
    {
        return $this->httpPort;
    }

    /**
     * @return int
     */
    public function getHttpsPort(): int
    {
        return $this->httpsPort;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }
}