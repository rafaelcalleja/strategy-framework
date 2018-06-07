<?php

namespace Dokify\Application\Service\Company;

use Dokify\Infrastructure\Application\Bridge\DokifyLegacy\Service\CompanyDetailService;

class ShowDetailHandler
{
    /**
     * @var CompanyDetailService
     */
    private $legacyService;

    public function __construct(CompanyDetailService $legacyService)
    {
        $this->legacyService = $legacyService;
    }

    public function handle(ShowDetailCommand $command)
    {
        $detail = [
            'activity' => null,
            'address' => null,
            'agreement' => null,
            'alerts' => null,
            'can_access_aki_ldap' => null,
            'checkbox' => null,
            'commercial' => null,
            'completed' => null,
            'connection' => null,
            'contact' => null,
            'contracts' => null,
            'corporation' => null,
            'country' => null,
            'cp' => null,
            'env' => null,
            'exports' => null,
            'files_quota' => null,
            'has_own_requirements' => null,
            'has_partner' => null,
            'has_transfer_pending' => null,
            'in_trash' => null,
            'instanceof' => null,
            'invoices_count' => null,
            'is_client' => null,
            'is_contract' => null,
            'is_corporation' => null,
            'is_direct_client' => null,
            'is_login' => null,
            'is_own' => null,
            'is_referrer' => null,
            'is_self_controlled' => null,
            'is_validation_partner' => null,
            'kind' => null,
            'labels' => null,
            'legal_rep' => null,
            'license' => null,
            'locale' => null,
            'logo' => null,
            'min_app_version' => null,
            'module' => null,
            'name' => null,
            'network' => null,
            'num_works' => null,
            'num_clients' => null,
            'ok' => null,
            'prevention_service' => null,
            'progress' => null,
            'read_only' => null,
            'referrer' => null,
            'referrer_commissions' => null,
            'requests' => null,
            'shortname' => null,
            'state' => null,
            'town' => null,
            'type' => null,
            'uid' => null,
            'upload_limit' => null,
            'uuid' => null,
            'validation_config' => null,
            'vat' => null,
            'visible' => null,
            'header_image' => null,
            'has_custom_login' => null,
            'client_to_pay' => null,
        ];

        $detail = array_merge(
            $detail,
            $this->legacyService->__invoke($command)
        );

        $request = new \stdClass();

        $request->scopes = function($method) use ($command) {
          return $command->{$method};
        };


        if ($request->scopes('instanceof')) {
            $detail['instanceof'] = 'empresa';
        }


        if (($request->scopes('is_client') || $request->scopes('is_direct_client')) && $userCompany) {
            if ($isSelf) {
                $detail['is_client']        = false;
                $detail['is_direct_client'] = false;
            } else {
                $userEntity        = $user->asDomainEntity();
                $userCompanyEntity = $userCompany->asDomainEntity();

                $companiesOfCompany  = $this->companyRepository->queryFromCompanyNetwork($entity, $userEntity);
                $isDirectClient      = $companiesOfCompany->isContractOf($userCompanyEntity, $active = true);

                if ($request->scopes('is_client')) {
                    $isIndirectClient    = $companiesOfCompany->isSubcontractOf($userCompanyEntity);
                    $detail['is_client'] = $isDirectClient || $isIndirectClient;
                }

                if ($request->scopes('is_direct_client')) {
                    $detail['is_direct_client'] = $isDirectClient;
                }
            }
        }

        if ($request->scopes('is_login')) {
            $detail['is_login'] = $isSelf;
        }

        if ($request->scopes('module')) {
            $detail['module'] = 1;
        }

        if ($request->scopes('num_requirements')) {
            // to-do: move logic here
            $detail['num_requirements'] = 0;
        }

        return $detail;
    }
}
