<?php

namespace Dokify\Legacy\Application\Service\Company;

class ShowDetailHandler
{

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

        $company    = new \empresa($command->companyUid());
        $info       = $company->getInfo();

        $profile = null;

        if (true === $command->isAuthenticated()) {
            $user = new \usuario($command->AutehnthicatedUserUid());
            $userCompany = new \empres($command->AutehnthicatedCompanyUid());
            $isSelf = $userCompany->compareTo($company);

        } elseif (null !== $command->profileUid()) {
            $profile = new \profile($command->profileUid());
            $user           = $profile->getLegacyInstance()->getUser();
            $userCompany    = $user->getCompany();
            $isSelf         = $userCompany->compareTo($company);
        } else {
            $user           = null;
            $userCompany    = null;
            $isSelf         = null;
        }

        if (true === $command->address()) {
            $detail['address'] = (string) $info['direccion'];
        }

        if (true === $command->agreement()) {
            $detail['agreement'] = (string) $info['convenio'];
        }

        // alerts needs a profile
        if (true === $command->alerts()) {
            $detail['alerts'] = 0;

            if (null !== $profile) {
                $detail['alerts'] = $this->getAlertCount($company, $profile);
            }
        }

        $progressScope  = $command->progress();
        $completedScope = $command->completed();
        $docsScope      = $command->docs();
        $okScope        = $command->ok();

        // any of this scopes will need the $summary
        if ($progressScope || $completedScope || $docsScope) {
            $summary = $company->getReqTypeSummary(['viewer' => $user]);
        }

        if ($progressScope) {
            $detail['progress'] = $summary->toArray();
        }

        if ($completedScope) {
            $detail['completed'] = $summary->getProgress();
        }

        if ($docsScope) {
            $detail['docs'] = $summary->getCounts();
        }

        if ($okScope) {
            $mandatorySummary = $company->getReqTypeSummary([
                'viewer' => $user,
                'mandatory' => true,
            ]);

            $detail['ok'] = $mandatorySummary->allAreValids();
        }


        if ($command->connection() && $userCompany) {
            // Connection data
            $connection = array();

            if (false === $isSelf) {
                $degrees = $userCompany->getConnectionDegrees($company);

                // We dont need all the connections, just one of each distance
                $connection = array_unique($degrees);
            }

            $detail['connection'] = $connection;
        }

        if ($command->contact()) {
            $detail['contact'] = [];

            // if a contact exists, we can't recall to Contact::toArray because of the recursion
            // we should fix this with scopes
            if ($contact = $company->obtenerContactoPrincipal()) {
                $contactInfo = $contact->getInfo();
                $language = $contact->getLanguage();
                $localeMap = getLocaleMap();
                $locale = $localeMap[$language].".utf8";

                $detail['contact'] = [
                    'uid' => $contact->getUID(),
                    'name' => trim($contactInfo['nombre']),
                    'fullname' => trim($contactInfo['nombre'] . " " . $contactInfo['apellidos']),
                    'email' => trim($contactInfo['email']),
                    'phone' => trim($contactInfo['telefono']),
                    'mobile' => trim($contactInfo['movil']),
                    'language' => $language,
                    'locale' => $locale,
                ];
            }
        }

        $request = new \stdClass();

        $request->scopes = function($method) use ($command) {
          return $command->{$method};
        };

        if ($request->scopes('contracts')) {
            $detail['contracts'] = $this->getContractsData($company, true);
        }

        if ($request->scopes('country')) {
            $detail['country'] = [];

            if ($info['uid_pais']) {
                $country = new \country($info['uid_pais']);

                $detail['country']['uid'] = (int) $country->getUID();
                $detail['country']['name']  = $country->getUserVisibleName();
            }
        }

        if ($request->scopes('cp')) {
            $detail['cp'] = (string) $info['cp'];
        }

        if ($request->scopes('env')) {
            $detail['env'] = $this->getEnvData($company);

            if ($isSelf && $info['activo_corporacion'] && $this->isPathAvailable()) {
                // ::getEnvData to expensive here
                $total = $company->countItems('empresa', $user, false);
                $src   = $app->path('company-counter', ['company' => $entity->uid(), 'type' => 'company']);

                $detail['env']['companies'] = [
                    'total' => $total,
                    'src'   => $src
                ];
            }
        }

        if ($request->scopes('exports')) {
            $detail['exports'] = [];

            if ($exports = $company->getPublicDataExports($user)) {
                foreach ($exports as $export) {
                    $model = $export->getDataModel();

                    if (false === $model->isOk()) {
                        continue;
                    }

                    $export = [
                        'name'  => $export->getUserVisibleName(),
                        'uid'   => $export->getUID()
                    ];

                    $detail['exports'][] = $export;
                }
            }
        }

        if ($request->scopes('files_quota')) {
            $detail['files_quota'] = [
                'used' => $this->fileRepository->queryFromCompany($entity)->size(),
                'total' => ($company->isFree()) ? File::FREE_FILE_QUOTA : 0,
            ];
        }

        if ($request->scopes('has_own_requirements')) {
            $detail['has_own_requirements'] = $company->countOwnDocuments() > 0;
        }

        if ($request->scopes('has_partner')) {
            $detail['has_partner'] = 0 < count($company->getPartners());
        }

        if ($request->scopes('has_transfer_pending')) {
            $detail['has_transfer_pending'] = (bool) $company->hasTransferPending();
        }

        if ($request->scopes('instanceof')) {
            $detail['instanceof'] = 'empresa';
        }

        if ($request->scopes('invoices_count')) {
            $detail['invoices_count'] = 0;

            if ($isSelf) {
                // only provide this number when is login
                $detail['invoices_count'] = (int) $company->obtenerInvoices(['count' => true]);
            }
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

        if ($request->scopes('is_contract') && $userCompany) {
            $detail['is_contract'] = (bool) $company->esContrata($userCompany);
        }

        if ($request->scopes('is_corporation')) {
            $detail['is_corporation'] = (bool) $info['activo_corporacion'];
        }

        if ($request->scopes('is_login')) {
            $detail['is_login'] = $isSelf;
        }


        if ($request->scopes('in_trash') && $userCompany) {
            $detail['in_trash'] = $isSelf === false && $company->inTrash($userCompany, $user);
        }

        if ($request->scopes('legal_rep')) {
            $detail['legal_rep'] = (string) $info['representante_legal'];
        }

        if ($request->scopes('town')) {
            $detail['town'] = [];

            if ($info['uid_municipio']) {
                $town = new \ciudad($info['uid_municipio']);

                $detail['town']['uid']  = (int) $town->getUID();
                $detail['town']['name'] = $town->getUserVisibleName();
            }
        }

        if ($request->scopes('min_app_version')) {
            $detail['min_app_version'] = (int) $company->obtenerDato('min_app_version');
        }

        if ($request->scopes('locale')) {
            $detail['locale'] = $company->getLocale();
        }

        if ($request->scopes('license')) {
            $detail['license'] = [
                'duration'      => 0,
                'expiration'    => 0,
                'is_enterprise' => false,
                'is_free'       => false,
                'is_premium'    => false,
                'is_temporary'  => false,
                'is_renovable'  => false,
                'is_expired'    => false,
                'item_count'    => 0,
                'name'          => 'free',
                'timestamp'     => 0,
                'type'          => 'free',
            ];

            if ($company->isEnterprise()) {
                $detail['license']['type']          = LegacyCompany::LICENSE_ENTERPRISE;
                $detail['license']['name']          = 'enterprise';
                $detail['license']['is_enterprise'] = true;
            } elseif ($company->isPremium()) {
                $detail['license']['type']          = LegacyCompany::LICENSE_PREMIUM;
                $detail['license']['name']          = 'premium';
                $detail['license']['is_premium']    = true;
            } else {
                $detail['license']['type']      = LegacyCompany::LICENSE_FREE;
                $detail['license']['name']      = 'free';
                $detail['license']['is_free']   = true;
            }

            if ($isSelf && $detail['license']['is_premium']) {
                $paid = $company->getPaidInfo();

                $detail['license']['duration']     = $paid->daysValidLicense;
                $detail['license']['timestamp']    = strtotime($paid->date);
                $detail['license']['item_count']   = $paid->items;

                $expiration = $detail['license']['timestamp'] + (60*60*24*($paid->daysValidLicense + 1));
                $detail['license']['expiration']   = $expiration;

                $detail['license']['is_temporary'] = $company->isTemporary();
                $detail['license']['is_renovable'] = $company->timeFreameToRenewLicense();
                $detail['license']['is_expired']   = $company->hasExpiredLicense();
            }
        }

        if ($request->scopes('module')) {
            $detail['module'] = 1;
        }

        if ($request->scopes('name')) {
            $detail['name'] = $company->getUserVisibleName();
        }

        if ($request->scopes('network')) {
            $detail['network'] = $this->getNetworkData($company);
        }

        if ($request->scopes('num_requirements')) {
            // to-do: move logic here
            $detail['num_requirements'] = 0;
        }

        // read_only has a in_trash_dependency
        if ($request->scopes(['read_only', 'in_trash']) && $userCompany) {
            if ($detail['in_trash']) {
                $chains = $company->getClientChains($userCompany);
                $detail['read_only'] = count($chains) === 0;
            } else {
                $detail['read_only'] = false;
            }
        }

        if ($request->scopes('shortname')) {
            $detail['shortname'] = $company->getShortName();
        }

        if ($request->scopes('state')) {
            $detail['state'] = [];

            if ($info['uid_provincia']) {
                $state = new LegacyState($info['uid_provincia']);

                $detail['state']['uid']  = (int) $state->getUID();
                $detail['state']['name'] = $state->getUserVisibleName();
            }
        }

        if ($request->scopes('vat')) {
            $detail['vat'] = (string) $info['cif'];
        }

        return $detail;
    }

    /**
     * Get the number of alerts for a company filtered by profile
     * @param  Company $company
     * @param  Profile $profile
     * @return int
     */
    private function getAlertCount(\empresa $company, \profile $profile)
    {
        if ($this->cache) {
            $cacheItem = $this->cache->getItem('company-' . $company->uid() . '-detail-alert-count-' . $profile->getUID());

            if (true === $cacheItem->isHit()) {
                return $cacheItem->get();
            }
        }

        $count  = $company->getAlertCount($profile);

        if ($this->cache) {
            $cacheItem->set($count);
            $cacheItem->setExpiration(60);

            $this->cache->save($cacheItem);
        }

        return $count;
    }
}
