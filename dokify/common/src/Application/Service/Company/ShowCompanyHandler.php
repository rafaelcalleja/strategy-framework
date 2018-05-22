<?php

namespace Dokify\Application\Service\Company;

use Dokify\Domain\Company\CompanyRepository;
use Dokify\Domain\Legacy\User\UserRepository as LegacyUserRepository;
use Dokify\Domain\Legacy\Company\CompanyRepository as LegacyCompanyRepository;
use Dokify\Domain\User\UserRepository;

class ShowCompanyHandler
{
    protected $companyRepository;

    /**
     * @var LegacyUserRepository
     */
    private $legacyUserRepository;

    /**
     * @var LegacyCompanyRepository
     */
    private $legacyCompanyRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(
        CompanyRepository $companyRepository,
        UserRepository $userRepository,
        LegacyCompanyRepository $legacyCompanyRepository,
        LegacyUserRepository $legacyUserRepository
    ) {
        $this->companyRepository = $companyRepository;
        $this->legacyUserRepository = $legacyUserRepository;
        $this->legacyCompanyRepository = $legacyCompanyRepository;
        $this->userRepository = $userRepository;
    }

    public function handle(ShowCompanyCommand $command): ShowCompanyResponse
    {
        $companyDetail = new \stdClass();
        $companyAssigns = new \stdClass();
        $detailsContainer = new \stdClass();

        $company = $this->companyRepository->ofId(
            $command->companyId()
        );

        $companyLegacy = $this->legacyCompanyRepository->ofId(
            $command->companyId()
        );

        $user = $this->userRepository->ofId(
            $command->companyId()
        );



        // prepare the detail request
        $detailRequest = $companyDetail->createRequest($company);

        // if is same company
        $userLegacy = $this->legacyUserRepository->ofId(
            $command->userId()
        );

        $userCompanyLegacy = $userLegacy->getCompany();
        $isOwnCompany = $userCompanyLegacy->compareTo($companyLegacy);

        if ($isOwnCompany) {
            // we want companies to fulfill this field
            if (null === $company->preventionService()
                && false === $user->isStaff()
                && true ===$userLegacy->canAccess($userCompanyLegacy, \Dokify\AccessActions::EDIT)
            ) {
                throw new ApplicationCompanyPreventionException();
            }

            $title = 'Home';

            // ask for alerts of the company
            //$detailRequest->useProfile($app->login->profile());
        }

        $companyData = $companyDetail->execute($detailRequest);

        if (true === $companyData['in_trash']) {
            $chains = $companyLegacy->getClientChains($userCompanyLegacy);

            if (count($chains) === 0) {
                throw new ApplicationCompanyInTrashException();
            }
        }

        $activity = $companyLegacy->getActivitySummary(2);
        $companyData['activity'] = \Dokify\Controller\Request::arrayToView($activity);

        $profile = $app->login->profile();
        $clientsOfCompanyQuery = $this->companyRepository->queryForClients($company, $profile);
        $clientsOfCompany = $clientsOfCompanyQuery->all();

        if (false === $isOwnCompany) {
            $userCompany = $userCompanyLegacy->asDomainEntity();
            $clientsAssigned = $companyAssigns->clients($company, $profile);
        }

        $clientsData = [];
        foreach ($clientsOfCompany as $clientOfCompany) {
            $clientLegacy = $clientOfCompany->getLegacyInstance();
            if ($clientLegacy->countCorpDocuments() == 0) {
                continue;
            }

            // Match the client with the visible requesters or if the requester is ourself
            if (false === $isOwnCompany
                && false === $userCompany->equals($clientOfCompany)
                && false === $clientsAssigned->contains($clientOfCompany)) {
                continue;
            }

            $clientData = $detailsContainer->resolve($clientOfCompany, [
                DetailsContainer::SCOPE_PACKAGE_ENTITY,
                DetailsContainer::SCOPE_PACKAGE_CLIENT,
            ]);

            $clientData['ok'] = $companyLegacy->isValid($app->user, ['client' => $clientLegacy]);
            $clientsData[] = $clientData;
        }

        $companyData['clients'] = $clientsData;

        if ($isOwnCompany) {
            // Get the news
            $companyData['news'] = $companyLegacy->getNews($app->user, false, 5)->map(__NAMESPACE__ . '\News::toArray', $app);
        }

        // if we should display validation queue widget
        
        if ($isOwnCompany && $user->usesValidationQueue()) {
            $query = $app['upload.repository']->queryFromOwner($company);

            if ($query->hasCountValidablesCache()) {
                $vqueue = ['total' => $query->countValidables()];
            } else {
                $widgetPath = $app->path('fileid-validation', ['format' => 'widget']);
                $vqueue = ['src' => $widgetPath];
            }

            // Count pending validations
            $companyData['vq'] = $vqueue;
        }

        if ($command->includeTour()) {
            $tour = null;

            $tourFeatureReleaseDate = new \DateTime('2018-01-22 8:00:00');

            $welcomeTourNumber = 4;
            if (true === $user->loggedInBefore($tourFeatureReleaseDate)) {
                $welcomeTourNumber = 48;
            }

            if (true === $app->user->canShowTour($welcomeTourNumber) && null === $request->query->get('start-tour')) {
                $tour = $welcomeTourNumber;
            }

            if (null !== $companyData['client_to_pay']) {
                $userActivatedPayment = LegacyLogUI::getUserFromElementAndValue($companyLegacy, 'enable_client_to_pay');

                if ($userActivatedPayment instanceof LegacyUser) {
                    $companyData['client_to_pay']['user_name'] = $userActivatedPayment->getUserVisibleName();
                }
            }
        }

        return $companyData;
    }
}
