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

    public function __construct(string $routeName, array $parameters, int $referenceType)
    {
        $this->routeName = $routeName;
        $this->parameters = $parameters;
        $this->referenceType = $referenceType;
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
}
