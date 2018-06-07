<?php

namespace Dokify\Infrastructure\Persistence\InMemory\Company;

use Dokify\Domain\Company\Company;
use Dokify\Domain\Company\CompanyRepository;

class InMemorCompanyRepository implements CompanyRepository
{
    public function ofId(int $id): Company
    {
        return new Company(
            'Symfony Company'
        );
    }
}
