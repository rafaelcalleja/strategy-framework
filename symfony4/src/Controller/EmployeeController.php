<?php

namespace App\Controller;

use Dokify\Application\Service\Employee\ShowEmployeeCommand;
use Dokify\Port\Adapter\Messaging\CommandBus;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EmployeeController extends Controller
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @Route("/employee/{employee}", name="employee")
     */
    public function index()
    {
        $request = $this->get('request_stack')->getCurrentRequest();

        $command = new ShowEmployeeCommand(
            $request->attributes->get('employee')
        );

        $responseDTO = $this->commandBus->handle($command);

        return $this->render('employee/index.html.twig', [
            'controller_name' => 'EmployeeController',
            'employee' => [
                'name' => $responseDTO->name(),
                'id' => $responseDTO->employeeId()
            ]
        ]);
    }
}
