<?php

namespace Dokify\Application\Service\Company\Detail;

use Dokify\Application;
use Dokify\Application\Service\DetailsContainer;
use Dokify\Application\Service\RequestHandlerTrait;
use Dokify\Application\Service\ScopePackageTrait;
use Dokify\Domain\Company\Company;
use Dokify\Domain\Company\CompanyUid;
use Dokify\Domain\Company\Specification\Factory as CompanySpecificationFactory;
use Dokify\Domain\Country\CountryUid;
use Dokify\Domain\File\File;
use Dokify\Domain\Profile\Profile;
use Dokify\Domain\Specification\TypeHint\ValidatorInterface as SpecificationValidatorInterface;
use empresa as LegacyCompany;
use municipio as LegacyTown;
use pais as LegacyCountry;
use provincia as LegacyState;
use Psr\Cache\CacheItemPoolInterface;

class CompanyDetailService
{
    /*
     * Injects the ::createRequest method into this service
     */
    use RequestHandlerTrait;

    /*
     * Injects the ::getScopesByPackages method into this service
     */
    use ScopePackageTrait;

    /**
     *
     * @var Application
     */
    protected $app;

    /**
     *
     * @var Dokify\Domain\Group\RepositoryInterface;
     */
    protected $groupRepository;

    /**
     *
     * @var Dokify\Domain\Company\RepositoryInterface;
     */
    protected $companyRepository;

    /**
     *
     * @var Dokify\Domain\Referrer\Commission\RepositoryInterface
     */
    protected $referrerCommissionRepository;

    /**
     * A cache implementation
     * @var Psr\Cache\CacheItemPoolInterface;
     */
    protected $cache;

    /**
     * @var CompanySpecificationFactory
     */
    private $companySpecificationFactory;

    /**
     * @var SpecificationValidatorInterface
     */
    private $specificationValidator;

    /**
     * Scopes in packages
     * @var array
     */
    protected $scopePackages = [
        DetailsContainer::SCOPE_PACKAGE_ENTITY => [
            'address',
            'agreement',
            'commercial',
            'corporation',
            'cp',
            'instanceof',
            'is_corporation',
            'is_referrer',
            'is_validation_partner',
            'is_self_controlled',
            'kind',
            'legal_rep',
            'logo',
            'name',
            'prevention_service',
            'route',
            'shortname',
            'state',
            'town',
            'type',
            'uid',
            'uuid',
            'vat',
            'header_image',
            'has_custom_login',
            'client_to_pay',
        ],
        DetailsContainer::SCOPE_PACKAGE_BASIC => [
            'can_access_aki_ldap',
            'env',
            'files_quota',
            'in_trash',
            'instanceof',
            'is_client',
            'is_contract',
            'is_corporation',
            'is_direct_client',
            'is_login',
            'is_own',
            'logo',
            'name',
            'network',
            'num_works',
            'ok',
            'type',
            'vat',
            'uid',
        ],
        DetailsContainer::SCOPE_PACKAGE_ASSIGNMENT => [
            'name',
            'uid',
            'type',
            'vat',
            'logo',
        ],
        DetailsContainer::SCOPE_PACKAGE_LIST => [
            'uid',
            'name',
            'vat',
            'route',
            'ok',
            'progress',
        ],
        DetailsContainer::SCOPE_PACKAGE_UPLOAD => [
            'uid',
            'name',
            'vat',
            'type',
            'instanceof',
        ],
        DetailsContainer::SCOPE_PACKAGE_EMAIL => [
            'uid',
            'name',
            'logo',
            'contact',
            'locale',
        ],
        DetailsContainer::SCOPE_PACKAGE_LOGIN => [
            'uid',
            'name',
            'corporation',
            'shortname',
            'license',
            'upload_limit',
            'has_own_requirements',
            'is_own',
            'instanceof',
            'is_corporation',
            'min_app_version',
            'is_referrer',
            'is_validation_partner',
            'referrer',
        ],
        DetailsContainer::SCOPE_PACKAGE_REFERRER => [
            'referrer_commissions',
        ],
        DetailsContainer::SCOPE_PACKAGE_CLIENT => [
            'is_own',
            'is_client',
        ],
        DetailsContainer::SCOPE_PACKAGE_CLIENT_LAYOUT => [
            'uid',
            'name',
            'shortname',
            'vat',
            'logo',
            'is_own',
            'is_login',
            'is_direct_client',
        ],
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->companyRepository = $app['company.repository'];
        $this->groupRepository = $app['group.repository'];
        $this->fileRepository = $app['file.repository.cache'];
        $this->referrerRepository = $app['referrer.repository'];
        $this->referrerCommissionRepository = $app['referrer_commission.repository'];
        $this->companySpecificationFactory = $app['company.specification_factory'];
        $this->specificationValidator = $app['specification.validator'];
    }

    /**
     * Set a CacheItemPoolInterface implementation
     * @param Psr\Cache\CacheItemPoolInterface
     */
    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Tell us if app is configured with the url_generator service and the "path" trait
     * @return boolean
     */
    private function isPathAvailable()
    {
        return isset($this->app['url_generator']) && is_callable([$this->app, 'path']);
    }

    /**
     * Get the number of alerts for a company filtered by profile
     * @param  Company $company
     * @param  Profile $profile
     * @return int
     */
    private function getAlertCount(Company $company, Profile $profile)
    {
        if ($this->cache) {
            $cacheItem = $this->cache->getItem('company-' . $company->uid() . '-detail-alert-count-' . $profile->uid());

            if (true === $cacheItem->isHit()) {
                return $cacheItem->get();
            }
        }

        $legacy = $company->getLegacyInstance();
        $count = $legacy->getAlertCount($profile->getLegacyInstance());

        if ($this->cache) {
            $cacheItem->set($count);
            $cacheItem->setExpiration(60);

            $this->cache->save($cacheItem);
        }

        return $count;
    }

    /**
     * Reverse the detail into an entity
     * @param  array  $detail
     * @throws InvalidArgumentException If it is not a valid detail array
     * @return Company
     */
    public function factory(array $detail)
    {
        if (false === isset($detail['uid']) || false === is_numeric($detail['uid'])) {
            throw new \InvalidArgumentException('Missing or wrong uid');
        }

        if (false === isset($detail['type']) || $detail['type'] !== 'company') {
            throw new \InvalidArgumentException('Missing or wrong type');
        }

        if (false === isset($detail['name'])) {
            throw new \InvalidArgumentException('Missing name');
        }

        if (false === isset($detail['vat'])) {
            throw new \InvalidArgumentException('Missing vat');
        }

        if (false === isset($detail['license']['is_enterprise'])) {
            throw new \InvalidArgumentException('Missing license:is_enterprise');
        }

        if (false === isset($detail['is_corporation'])) {
            throw new \InvalidArgumentException('Missing is_corporation');
        }

        if (false === isset($detail['commercial'])) {
            throw new \InvalidArgumentException('Missing commercial');
        }

        if (false === isset($detail['legal_rep'])) {
            throw new \InvalidArgumentException('Missing legal_rep');
        }

        if (false === isset($detail['country'])) {
            throw new \InvalidArgumentException('Missing country_uid');
        }

        if (false === isset($detail['state'])) {
            throw new \InvalidArgumentException('Missing state');
        }

        if (false === isset($detail['town'])) {
            throw new \InvalidArgumentException('Missing town');
        }

        if (false === isset($detail['cp'])) {
            throw new \InvalidArgumentException('Missing zip_code');
        }

        if (false === isset($detail['address'])) {
            throw new \InvalidArgumentException('Missing address');
        }

        if (false === isset($detail['is_referrer'])) {
            throw new \InvalidArgumentException('Missing is_referrer');
        }

        if (false === isset($detail['is_validation_partner'])) {
            throw new \InvalidArgumentException('Missing is_validation_partner');
        }

        if (false === isset($detail['kind']['kind'])) {
            throw new \InvalidArgumentException('Missing kind:kind');
        }

        if (false === isset($detail['agreement'])) {
            throw new \InvalidArgumentException('Missing agreement');
        }

        $uid = new CompanyUid($detail['uid']);

        $countryUid = new CountryUid($detail['country']);

        $company = new Company(
            $uid,
            $detail['name'],
            $detail['vat'],
            $detail['license']['is_enterprise'],
            $detail['is_corporation'],
            $detail['commercial'],
            $detail['legal_rep'],
            $countryUid,
            $detail['state'],
            $detail['town'],
            $detail['cp'],
            $detail['address'],
            $detail['is_referrer'],
            $detail['is_validation_partner'],
            $detail['kind']['kind'],
            $detail['agreement']
        );

        return $company;
    }

    /**
     * Aux method to get the organizations of a company
     * @param  Company $company
     * @return array
     */
    public function organizations(Company $company)
    {
        $organizations = $company->getLegacyInstance()->obtenerAgrupamientosVisibles();

        $organizationsData = [];
        foreach ($organizations as $org) {
            $organizationsData[] = [
                'name' => $org->getUserVisibleName(),
                'type' => 'organization',
                'uid' => $org->getUID(),
            ];
        }

        return $organizationsData;
    }

    /**
     * Excute the service request
     * @param  Request $request
     * @return array An array containing the company detail
     */
    public function execute(Request $request)
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

        $app = $this->app;
        $entity = $request->company;
        $company = $entity->getLegacyInstance();
        $info = $company->getInfo();

        // http request specific values
        if (isset($app->login)) {
            $user = $app->login->user()->getLegacyInstance();
            $userCompany = $app->login->company()->getLegacyInstance();
            $isSelf = $userCompany->compareTo($company);
        } elseif ($profile = $request->contextProfile()) {
            $user = $profile->getLegacyInstance()->getUser();
            $userCompany = $user->getCompany();
            $isSelf = $userCompany->compareTo($company);
        } else {
            $user = null;
            $userCompany = null;
            $isSelf = null;
        }

        if ($request->scopes('activity')) {
            // to-do: move logic here
            $detail['activity'] = '';
        }

        if ($request->scopes('address')) {
            $detail['address'] = (string) $info['direccion'];
        }

        if ($request->scopes('agreement')) {
            $detail['agreement'] = (string) $info['convenio'];
        }

        // alerts needs a profile
        if ($request->scopes('alerts')) {
            $detail['alerts'] = 0;

            if ($profile = $request->contextProfile()) {
                $detail['alerts'] = $this->getAlertCount($entity, $profile);
            }
        }

        if ($request->scopes('can_access_aki_ldap')) {
            $detail['can_access_aki_ldap'] = false;

            // Only AKI company can access to aki ldap
            if (isset($app->login) && 95354 === $this->app->login->company()->uid()) {
                $detail['can_access_aki_ldap'] = true;
            }
        }

        if ($request->scopes('checkbox')) {
            $detail['checkbox'] = true;
        }

        if ($request->scopes('client_to_pay')) {
            $clientToPay = null === $entity->clientToPay() ? null : (int) $entity->clientToPay()->getAsNumber();

            if (null !== $clientToPay) {
                $detail['client_to_pay']['uid_client'] = $clientToPay;
            }
        }

        if ($request->scopes('commercial')) {
            $detail['commercial'] = (string) $info['nombre_comercial'];
        }

        $progressScope = $request->scopes('progress');
        $completedScope = $request->scopes('completed');
        $docsScope = $request->scopes('docs');
        $okScope = $request->scopes('ok');

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

        $detail['prevention_service'] = $entity->preventionService();
        $detail['is_referrer'] = $entity->isReferrer();
        $detail['is_validation_partner'] = $entity->isValidationPartner();

        if ($request->scopes('connection') && $userCompany) {
            // Connection data
            $connection = [];

            if (false === $isSelf) {
                $degrees = $userCompany->getConnectionDegrees($company);

                // We dont need all the connections, just one of each distance
                $connection = array_unique($degrees);
            }

            $detail['connection'] = $connection;
        }

        if ($request->scopes('contact')) {
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

        if ($request->scopes('contracts')) {
            $detail['contracts'] = $this->getContractsData($company, true);
        }

        if ($request->scopes('corporation')) {
            $corporationUid = $entity->corporation() ? $entity->corporation()->getAsNumber() : null;

            $detail['corporation'] = [
                'uid' => $corporationUid,
            ];
        }

        if ($request->scopes('country')) {
            $detail['country'] = [];

            if ($info['uid_pais']) {
                $country = new LegacyCountry($info['uid_pais']);

                $detail['country']['uid'] = (int) $country->getUID();
                $detail['country']['name'] = $country->getUserVisibleName();
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
                $src = $app->path('company-counter', ['company' => $entity->uid(), 'type' => 'company']);

                $detail['env']['companies'] = [
                    'total' => $total,
                    'src' => $src,
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
                        'name' => $export->getUserVisibleName(),
                        'uid' => $export->getUID(),
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
                $detail['is_client'] = false;
                $detail['is_direct_client'] = false;
            } else {
                $userEntity = $user->asDomainEntity();
                $userCompanyEntity = $userCompany->asDomainEntity();

                $companiesOfCompany = $this->companyRepository->queryFromCompanyNetwork($entity, $userEntity);
                $isDirectClient = $companiesOfCompany->isContractOf($userCompanyEntity, $active = true);

                if ($request->scopes('is_client')) {
                    $isIndirectClient = $companiesOfCompany->isSubcontractOf($userCompanyEntity);
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

        if ($request->scopes('is_own') && $userCompany) {
            $specificationCompanyIsOwn = $this->companySpecificationFactory->createCompanyIsOwn(
                $userCompany->asDomainEntity()
            );

            $detail['is_own'] = $this->specificationValidator->validate($specificationCompanyIsOwn, $entity);
        }

        if ($request->scopes('is_self_controlled')) {
            $detail['is_self_controlled'] = $entity->isSelfControlled();
        }

        if ($request->scopes('has_custom_login')) {
            $detail['has_custom_login'] = $entity->hasCustomLogin();
        }

        if ($request->scopes('header_image')) {
            $detail['header_image'] = $entity->headerImage();
        }

        if ($request->scopes('in_trash') && $userCompany) {
            $detail['in_trash'] = $isSelf === false && $company->inTrash($userCompany, $user);
        }

        if ($request->scopes('kind')) {
            // this an aux var we already use for handle details
            // in shared view files. We left here because they are compatible.

            // but we should change things since there is no reason both "kinds"
            // should be togheter
            $detail['kind'] = ['company', 'requestable'];

            $detail['kind']['kind'] = $entity->kind();
            $detail['kind']['is_company'] = $entity->isKindCompany();
            $detail['kind']['is_self_employed'] = $entity->isKindSelfEmployed();
            $detail['kind']['is_temp_agency'] = $entity->isKindTempAgency();
        }

        if ($request->scopes('legal_rep')) {
            $detail['legal_rep'] = (string) $info['representante_legal'];
        }

        if ($request->scopes('town')) {
            $detail['town'] = [];

            if ($info['uid_municipio']) {
                $town = new LegacyTown($info['uid_municipio']);

                $detail['town']['uid'] = (int) $town->getUID();
                $detail['town']['name'] = $town->getUserVisibleName();
            }
        }

        if ($request->scopes('labels')) {
            // to-do: move logic here
            $detail['labels'] = [];
        }

        if ($request->scopes('logo')) {
            $logo = $entity->logo();
            $detail['logo'] = false;
            if ($logo === false || strpos($logo, 'http') !== false) {
                $detail['logo'] = $logo;
            } else {
                $detail['logo'] = $company->obtenerLogo(false);
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
                'duration' => 0,
                'expiration' => 0,
                'is_enterprise' => false,
                'is_free' => false,
                'is_premium' => false,
                'is_temporary' => false,
                'is_renovable' => false,
                'is_expired' => false,
                'item_count' => 0,
                'name' => 'free',
                'timestamp' => 0,
                'type' => 'free',
            ];

            if ($company->isEnterprise()) {
                $detail['license']['type'] = LegacyCompany::LICENSE_ENTERPRISE;
                $detail['license']['name'] = 'enterprise';
                $detail['license']['is_enterprise'] = true;
            } elseif ($company->isPremium()) {
                $detail['license']['type'] = LegacyCompany::LICENSE_PREMIUM;
                $detail['license']['name'] = 'premium';
                $detail['license']['is_premium'] = true;
            } else {
                $detail['license']['type'] = LegacyCompany::LICENSE_FREE;
                $detail['license']['name'] = 'free';
                $detail['license']['is_free'] = true;
            }

            if ($isSelf && $detail['license']['is_premium']) {
                $paid = $company->getPaidInfo();

                $detail['license']['duration'] = $paid->daysValidLicense;
                $detail['license']['timestamp'] = strtotime($paid->date);
                $detail['license']['item_count'] = $paid->items;

                $expiration = $detail['license']['timestamp'] + (60 * 60 * 24 * ($paid->daysValidLicense + 1));
                $detail['license']['expiration'] = $expiration;

                $detail['license']['is_temporary'] = $company->isTemporary();
                $detail['license']['is_renovable'] = $company->timeFreameToRenewLicense();
                $detail['license']['is_expired'] = $company->hasExpiredLicense();
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

        // If the company is a referrer
        if ($request->scopes('referrer')) {
            if ($entity->isReferrer()) {
                $referrer = $this->referrerRepository->findByCompany($entity);
                $detail['referrer'] = [
                    'uid' => $referrer->getAsNumber(),
                ];
            }
        }

        // If the company is referred by a referrer we set the summary of the commissions generated
        if ($request->scopes('referrer_commissions')) {
            if ($referrerUid = $entity->referrer()) {
                $commissionsOfReferrerQuery = $this->referrerCommissionRepository->queryFromReferrer($referrerUid);
                $commissions = $commissionsOfReferrerQuery->queryFromClient($entity)->all();

                $detail['referrer_commissions'] = [
                    'amount' => $commissions->amount(),
                    'contracts' => $commissions->contracts(),
                ];
            }
        }

        if ($request->scopes('requests')) {
            $detail['requests'] = [
                'labels' => [],
            ];
        }

        if ($request->scopes('route')) {
            $routeName = 'company';
            $routeParams = ['company' => $entity->uid()];
            $route = ['name' => $routeName, 'params' => $routeParams];

            $detail['route'] = $route;
        }

        if ($request->scopes('shortname')) {
            $detail['shortname'] = $company->getShortName();
        }

        if ($request->scopes('state')) {
            $detail['state'] = [];

            if ($info['uid_provincia']) {
                $state = new LegacyState($info['uid_provincia']);

                $detail['state']['uid'] = (int) $state->getUID();
                $detail['state']['name'] = $state->getUserVisibleName();
            }
        }

        if ($request->scopes('type')) {
            $detail['type'] = 'company';
        }

        if ($request->scopes('uid')) {
            $detail['uid'] = $entity->uid();
        }

        if ($request->scopes('upload_limit') && $request->scopes('license')) {
            $detail['upload_limit'] = Company::UPLOAD_LIMIT;

            if ($detail['license']['is_free']) {
                $detail['upload_limit'] = Company::FREE_UPLOAD_LIMIT;
            }
        }

        if ($request->scopes('uuid')) {
            $detail['uuid'] = 'company-' . $entity->uid();
        }

        if ($request->scopes('validation_config')) {
            // to-do: move logic here
            $detail['validation_config'] = [];
        }

        if ($request->scopes('vat')) {
            $detail['vat'] = (string) $info['cif'];
        }

        if ($request->scopes('visible')) {
            // to-do: move logic here
            $detail['visible'] = true;
        }

        if ($request->scopes('num_works')) {
            $query = $this->groupRepository->queryFromParticipantCompany($entity);

            $detail['num_works'] = $query->countWorksCategory();
        }

        if ($request->scopes('num_clients')) {
            $query = $this->companyRepository->queryForClients($entity);

            $detail['num_clients'] = $query->countAll();
        }

        return $detail;
    }

    /***
     * Get company network array
     *
     *
     *
     */
    private function getNetworkData(LegacyCompany $company)
    {
        $app = $this->app;

        // try to fill with something semantic even if it won't be used
        if (false === $this->isPathAvailable()) {
            return [
                'companies' => [],
                'employees' => [],
                'machines' => [],
            ];
        }

        $userCompany = $app->user->getCompany();

        // Do not perform the count here, takes a lot of time
        // $numCompanies   = $company->countItems('empresa', $app->user, true);
        // $numEmployees   = $company->countItems('empleado', $app->user, true);
        // $numMachines    = $company->countItems('maquina', $app->user, true);

        // Get the data async
        $src = $app->path('company-counter', ['company' => $company->getUID(), 'network' => 'true']);

        $network = [
            'companies' => [
                'total' => null,
                'src' => $src . '&type=company',
            ],
            'employees' => [
                'total' => null,
                'src' => $src . '&type=employee&own=false',
            ],
            'machines' => [
                'total' => null,
                'src' => $src . '&type=machine&own=false',
            ],
        ];

        if ($userCompany->compareTo($company)) {
            // count the number of companies in this network!
            $numCompanies = $company->countItems('empresa', $app->user, true);

            $network['companies']['total'] = $numCompanies;
        }

        return $network;
    }

    /***
     * Get company contracts array
     *
     *
     *
     */
    private function getContractsData(LegacyCompany $company, $empty = false)
    {
        $app = $this->app;

        // try to fill with something semantic even if it won't be used
        if (false === $this->isPathAvailable()) {
            return [
                'companies' => [],
                'employees' => [],
                'machines' => [],
            ];
        }

        $route = 'company-counter';

        $contracts = [
            'companies' => [
                'src' => $app->path($route, ['company' => $company->getUID(), 'type' => 'company']),
            ],
            'employees' => [
                'src' => $app->path($route, [
                    'type' => 'employee',
                    'company' => $company->getUID(),
                    'network' => 'true',
                    'contracts' => 'true',
                ]),
            ],
            'machines' => [
                'src' => $app->path($route, [
                    'type' => 'machine',
                    'company' => $company->getUID(),
                    'network' => 'true',
                    'contracts' => 'true',
                ]),
            ],
        ];

        if ($empty) {
            return $contracts;
        }

        return $contracts;
    }

    /***
     * Get company env array
     *
     *
     *
     */
    public function getEnvData(LegacyCompany $company, $empty = false)
    {
        $app = $this->app;

        // try to fill with something semantic even if it won't be used
        if (false === $this->isPathAvailable()) {
            return [
                'companies' => [],
                'employees' => [],
                'machines' => [],
            ];
        }

        $route = 'company-counter';

        $env = [
            'companies' => [
                'src' => $app->path($route, ['company' => $company->getUID(), 'type' => 'company']),
            ],
            'employees' => [
                'src' => $app->path($route, ['company' => $company->getUID(), 'type' => 'employee']),
            ],
            'machines' => [
                'src' => $app->path($route, ['company' => $company->getUID(), 'type' => 'machine']),
            ],
        ];

        if ($empty) {
            return $env;
        }

        if ($this->cache) {
            $cacheItem = $this->cache->getItem('company-' . $company->getUID() . '-detail-env-data-' . $app->login->profile()->uid());

            if (true === $cacheItem->isHit()) {
                $json = $cacheItem->get();

                // this value is stored in json format, so deserealize it and return as array
                return json_decode($json, true);
            }
        }

        $numCompanies = $company->countItems('empresa', $app->user, false);
        $numEmployees = $company->countItems('empleado', $app->user, false);
        $numMachines = $company->countItems('maquina', $app->user, false);

        $env['companies']['total'] = $numCompanies;
        $env['employees']['total'] = $numEmployees;
        $env['machines']['total'] = $numMachines;

        $opts['mandatory'] = true;
        $opts['viewer'] = $app->user;

        $summary = $company->getReqTypeSummary($opts);

        $employees = $company->getNumberOfDocumentsByStatusOfChilds($app->user, 'empleado');
        $machiness = $company->getNumberOfDocumentsByStatusOfChilds($app->user, 'maquina');

        $summary->merge($employees);
        $summary->merge($machiness);

        $env['docs'] = $summary->getCounts();
        $env['progress'] = $summary->getProgress();
        $env['ok'] = $summary->allAreValids();

        if ($this->cache) {
            $cacheItem->set(json_encode($env));
            $cacheItem->setExpiration(60);

            $this->cache->save($cacheItem);
        }

        return $env;
    }
}
