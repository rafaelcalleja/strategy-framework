<?php

namespace Dokify\Controller\Company;

class Company
{
    /**
     * @param HttpRequest $request
     * @param Application $app
     * @param Model $company
     * @return mixed
     */
    public static function show(HttpRequest $request, Application $app, Model $company)
    {
        $companyLegacy = $company;

        $companyAssigns = $app['company.assigns'];
        $companyDetail = $app['company.detail'];
        $companyRepository = $app['company.repository'];
        $detailsContainer = $app['details_container'];

        $title = $companyLegacy->getUserVisibleName();
        $company = $companyLegacy->asDomainEntity();

        if (false === isset($app->user)) {
            $companyData = $companyDetail->execute(
                $companyDetail->createRequest($company, ['name', 'logo'])
            );

            $goto = $request->getPathInfo();
            if (null !== $queryString = $request->getQueryString()) {
                $goto .= '?' . $queryString;
            }

            return $app->render('web/company.html', [
                'title' => $title,
                'company' => $companyData,
                'goto' => $goto,
            ]);
        }

        if ($company->isCorporation()) {
            $location = $app->path('corporation', ['company' => $company->uid()]);

            if ($request->async) {
                return $app->json(['location' => $location]);
            }

            return $app->redirect($location);
        }

        // prepare the detail request
        $detailRequest = $companyDetail->createRequest($company);

        // if is same company
        $userCompanyLegacy = $app->user->getCompany();
        $isOwnCompany = $userCompanyLegacy->compareTo($companyLegacy);

        if ($isOwnCompany) {
            // we want companies to fulfill this field
            if (null === $company->preventionService()
                && false === $app->login->user()->isStaff()
                && true === $app->user->canAccess($userCompanyLegacy, \Dokify\AccessActions::EDIT)
            ) {
                $url = $app->path('company-legal');

                if ($request->async) {
                    return $app->json(['location' => $url]);
                }

                // prevent mobile api break
                if (false === $request->json) {
                    return $app->redirect($url);
                }
            }

            $title = 'Home';

            // ask for alerts of the company
            $detailRequest->useProfile($app->login->profile());
        }

        $companyData = $companyDetail->execute($detailRequest);

        if (true === $companyData['in_trash']) {
            $chains = $companyLegacy->getClientChains($userCompanyLegacy);

            if (count($chains) === 0) {
                return $app->render('app/company/intrash.html', [
                    'title' => $title,
                    'company' => $companyData,
                ]);
            }
        }

        $activity = $companyLegacy->getActivitySummary(2);
        $companyData['activity'] = \Dokify\Controller\Request::arrayToView($activity);

        $profile = $app->login->profile();
        $clientsOfCompanyQuery = $companyRepository->queryForClients($company, $profile);
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
        if ($isOwnCompany && $app->login->user()->usesValidationQueue()) {
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

        $dismissed = ($request->cookies->get('broadcast-message') == 1);
        $broadcast = $dismissed ? null : @\system::getAvisoHome();

        if ($request->json) {
            return $app->json($companyData);
        }

        $tour = null;

        $tourFeatureReleaseDate = new \DateTime('2018-01-22 8:00:00');

        $welcomeTourNumber = 4;
        if (true === $app->login->user()->loggedInBefore($tourFeatureReleaseDate)) {
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

        return $app->render('app/company/show.html', [
            'title' => 'Home',
            'company' => $companyData,
            'tour' => $tour,
            'broadcast' => $broadcast,
        ]);
    }
}
