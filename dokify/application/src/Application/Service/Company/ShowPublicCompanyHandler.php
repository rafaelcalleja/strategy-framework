<?php

namespace Dokify\Application\Service\Company;

use Dokify\Domain\Company\CompanyRepository;

class ShowPublicCompanyHandler
{
    protected $companyRepository;

    public function __construct(CompanyRepository $companyDetailService)
    {
        $this->companyRepository = $companyDetailService;
    }

    public function handle(ShowPublicCompanyCommand $command): ShowPublicCompanyResponse
    {
        $company = $this->companyRepository->ofId($command->companyUid());

        return ShowPublicCompanyResponse::makeFromDomain($company);
    }
}
