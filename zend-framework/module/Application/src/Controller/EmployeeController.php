<?php

namespace Application\Controller;

use Dokify\Application\Service\ApplicationServiceRegistry;
use Dokify\Application\Service\Employee\ShowEmployeeResponse;
use Dokify\Application\Web\Action\Employee\Show;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class EmployeeController extends AbstractActionController
{
    public function showAction()
    {
        $actionShow = new Show(ApplicationServiceRegistry::commandBus());
        $httpFactory = ApplicationServiceRegistry::httpFactory();

        $request = $httpFactory->createRequest($this->getRequest());

        /** @var ShowEmployeeResponse $responseDTO */
        $responseDTO = $actionShow->__invoke($request);

        return new ViewModel(
            [
                'employee' => ['name' => $responseDTO->name()],
            ]
        );
    }
}
