<?php

namespace App\Controller;

use Dokify\Application\Service\ApplicationServiceRegistry;
use Dokify\Application\Service\Employee\ShowEmployeeResponse;
use Dokify\Application\Web\Action\Employee\Show;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EmployeeController extends Controller
{
    /**
     * @Route("/employee/{employee}", name="employee")
     */
    public function index()
    {
        $actionShow = new Show(ApplicationServiceRegistry::commandBus());
        $httpFactory = ApplicationServiceRegistry::httpFactory();

        $request = $httpFactory->createRequest($this->get('request_stack')->getCurrentRequest());

        /** @var ShowEmployeeResponse $responseDTO */
        $responseDTO = $actionShow->__invoke($request);

        return $this->render('employee/index.html.twig', [
            'controller_name' => 'EmployeeController',
            'employee' => [
                'name' => $responseDTO->name(),
                'id' => $responseDTO->employeeId()
            ]
        ]);
    }
}
