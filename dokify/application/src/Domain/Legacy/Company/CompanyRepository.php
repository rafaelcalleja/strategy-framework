<?php

namespace Dokify\Domain\Legacy\Company;

interface CompanyRepository
{
    public function ofId(int $id): \empresa;
}
