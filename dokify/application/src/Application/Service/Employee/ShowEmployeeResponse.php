<?php

namespace Dokify\Application\Service\Employee;

use Dokify\Domain\Employee\Employee;

class ShowEmployeeResponse
{
    /**
     * @var int
     */
    protected $employeeId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param int $employeeId
     * @param string $name
     */
    public function __construct(int $employeeId, string $name)
    {
        $this->employeeId = $employeeId;
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function employeeId(): int
    {
        return $this->employeeId;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    public static function makeFromDomain(Employee $employee)
    {
        return new self(rand(), $employee->name());
    }
}
