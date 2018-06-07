<?php

namespace Dokify\Domain\Employee;

interface EmployeeRepository
{
    public function ofId(int $id): Employee;
}