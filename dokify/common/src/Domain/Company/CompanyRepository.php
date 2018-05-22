<?php

namespace Dokify\Domain\Company;

interface CompanyRepository
{
    public function ofId(int $id): Company;
}
