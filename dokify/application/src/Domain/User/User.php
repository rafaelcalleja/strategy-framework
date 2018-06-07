<?php

namespace Dokify\Domain\User;

class User
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    private function setName(string $name)
    {
        if (empty($name)) {
            throw new \RuntimeException('Domain Exception, name is required');
        }

        $this->name = $name;
    }
}
