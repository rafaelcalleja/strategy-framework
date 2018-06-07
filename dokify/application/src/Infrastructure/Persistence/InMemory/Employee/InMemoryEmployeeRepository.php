<?php

namespace Dokify\Infrastructure\Persistence\InMemory\Employee;

use Dokify\Domain\Employee\Employee;
use Dokify\Domain\Employee\EmployeeRepository;

class InMemoryEmployeeRepository implements EmployeeRepository
{
    public function ofId(int $id): Employee
    {
        return new Employee(
            'Symfony Employee'
        );
    }
}
