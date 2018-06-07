<?php

namespace Dokify\Application\Service\Company;

use Dokify\Domain\Company\Company;

class ShowPublicCompanyResponse
{
    /**
     * @var int
     */
    protected $companyUid;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param int $companyUid
     * @param string $name
     */
    public function __construct(int $companyUid, string $name)
    {
        $this->companyUid = $companyUid;
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function companyUid(): int
    {
        return $this->companyUid;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    public static function makeFromDomain(Company $company)
    {
        return new self(rand(), $company->name());
    }
}
