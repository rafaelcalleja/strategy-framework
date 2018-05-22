<?php

namespace Dokify\Application\Service\Company;

class ShowPublicCompanyCommand
{
    /**
     * @var int
     */
    private $companyUid;

    /**
     * @param int $companyUid
     */
    public function __construct(int $companyUid)
    {
        $this->companyUid = $companyUid;
    }

    /**
     * @return int
     */
    public function companyUid(): int
    {
        return $this->companyUid;
    }
}
