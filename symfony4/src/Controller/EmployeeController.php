<?php

namespace App\Controller;

use Dokify\Application\Service\ApplicationServiceRegistry;
use Dokify\Application\Service\Employee\ShowEmployeeCommand;
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
        $request = $this->get('request_stack')->getCurrentRequest();

        $command = new ShowEmployeeCommand(
            $request->attributes->get('employee')
        );

        $responseDTO = $this->getCommandBus()->handle($command);

        return $this->render('employee/index.html.twig', [
            'controller_name' => 'EmployeeController',
            'employee' => [
                'name' => $responseDTO->name(),
                'id' => $responseDTO->employeeId()
            ]
        ]);
    }
}
