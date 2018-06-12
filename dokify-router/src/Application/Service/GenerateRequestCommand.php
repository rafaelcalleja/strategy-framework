<?php

namespace Dokify\Router\Application\Service;

class GenerateRequestCommand
{
    /**
     * @var string
     */
    private $routeName;
    /**
     * @var array
     */
    private $parameters;
    /**
     * @var int
     */
    private $referenceType;
    /**
     * @var string
     */
    private $baseUrl;
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

    public function __construct(
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
        $this->routeName = $routeName;
        $this->parameters = $parameters;
        $this->referenceType = $referenceType;
        $this->baseUrl = $baseUrl;
        $this->path = $path;
        $this->host = $host;
        $this->scheme = $scheme;
        $this->httpPort = $httpPort;
        $this->httpsPort = $httpsPort;
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
    public function getRouteName(): string
    {
        return $this->routeName;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return int
     */
    public function getReferenceType(): int
    {
        return $this->referenceType;
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
}
