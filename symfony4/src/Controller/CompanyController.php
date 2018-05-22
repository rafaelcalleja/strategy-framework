<?php

namespace App\Controller;

use Dokify\Application\Service\ApplicationServiceRegistry;
use Dokify\Application\Service\Company\ApplicationCompanyInTrashException;
use Dokify\Application\Service\Company\ApplicationCompanyPreventionException;
use Dokify\Application\Web\Action\Company\Show;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CompanyController extends Controller
{
    /**
     * @Route("/company/{company}", name="company")
     */
    public function index(AuthorizationCheckerInterface $authChecker)
    {
        if (false === $authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('public_company');
        }

        $actionShow = new Show(ApplicationServiceRegistry::commandBus());
        $httpFactory = ApplicationServiceRegistry::httpFactory();

        $request = $httpFactory->createRequest($this->get('request_stack')->getCurrentRequest());

        try {
            /** @var ShowCompanyResponse $responseDTO */
            $responseDTO = $actionShow->__invoke($request);

        }catch(ApplicationCompanyPreventionException $exception) {

            $url = $this->generateUrl('company-legal');

            if ($request->async) {
                return $this->json(['location' => $url]);
            }

            // prevent mobile api break
            if (false === $request->json) {
                return $this->redirectToRoute($url);
            }

        }catch(ApplicationCompanyInTrashException $exception)
        {
            return $this->redirectToRoute('company-trash', ['company' => $request->getAttribute('company')]);
        }

        $dismissed = ($request->cookies->get('broadcast-message') == 1);
        $broadcast = $dismissed ? null : @\system::getAvisoHome();

        return $this->render('company/index.html.twig', [
            'controller_name' => 'CompanyController',
            'title' => 'Home',
            'company' => $responseDTO->companyData(),
            'tour' => $responseDTO->tourData(),
            'broadcast' => $broadcast,
        ]);
    }

    /**
     * @Route("/public/company/{company}", name="public_company")
     */
    public function publicShowAction(AuthorizationCheckerInterface $authChecker)
    {
        $actionShow = new \Dokify\Application\Web\Action\Open\Company\Show(ApplicationServiceRegistry::commandBus());
        $httpFactory = ApplicationServiceRegistry::httpFactory();

        $request = $httpFactory->createRequest($this->get('request_stack')->getCurrentRequest());

        /** @var ShowPublicCompanyResponse $responseDTO */
        $responseDTO = $actionShow->__invoke($request);

        return $this->render('company/index.html.twig', [
            'controller_name' => 'CompanyController',
            'company' => [
                'name' => $responseDTO->name(),
                'id' => $responseDTO->companyUid(),
            ],
        ]);
    }


}
