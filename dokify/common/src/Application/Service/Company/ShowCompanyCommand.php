<?php


namespace Dokify\Application\Service\Company;


class ShowCompanyCommand
{
    /**
     * @var int
     */
    private $companyId;

    public function __construct(int $companyId, bool $includeTourInfo)
    {
        $this->companyId = $companyId;
    }
}