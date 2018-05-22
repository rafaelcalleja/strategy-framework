<?php

namespace App\Controller;

use Dokify\Application\Service\ApplicationServiceRegistry;
use Dokify\Application\Web\Action\Employee\Show;
use Dokify\Common\Infrastructure\Port\Adapter\Http\TraitController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EmployeeController extends Controller
{
    use TraitController;

    /**
     * @Route("/employee/{employee}", name="employee")
     */
    public function index()
    {
        $request = $this->get('request_stack')->getCurrentRequest();

        $responseDTO = $this->handle($request);

        return $this->render('employee/index.html.twig', [
            'controller_name' => 'EmployeeController',
            'employee' => [
                'name' => $responseDTO->name(),
                'id' => $responseDTO->employeeId()
            ]
        ]);
    }
}
