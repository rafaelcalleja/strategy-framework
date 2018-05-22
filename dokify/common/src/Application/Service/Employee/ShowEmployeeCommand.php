<?php

namespace Dokify\Application\Service\Employee;

class ShowEmployeeCommand
{
    /**
     * @var int
     */
    private $employeeId;

    public function __construct(int $employeeId)
    {
        $this->employeeId = $employeeId;
    }

    /**
     * @return int
     */
    public function employeeId(): int
    {
        return $this->employeeId;
    }
}