<?php

namespace Dokify\Application\Service\Employee;

use Dokify\Domain\Employee\EmployeeRepository;

class ShowEmployeeHandler
{
    protected $employeeRepository;

    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function handle(ShowEmployeeCommand $command): ShowEmployeeResponse
    {
        $employee = $this->employeeRepository->ofId($command->employeeId());

        return ShowEmployeeResponse::makeFromDomain($employee);
    }
}