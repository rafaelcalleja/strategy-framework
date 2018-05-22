<?php

namespace Application\Controller;

use Dokify\Application\Service\ApplicationServiceRegistry;
use Dokify\Application\Service\Employee\ShowEmployeeCommand;
use Dokify\Application\Service\Employee\ShowEmployeeResponse;
use Dokify\Application\Web\Action\Employee\Show;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class EmployeeController extends AbstractActionController
{
    public function showAction()
    {
        $command = new ShowEmployeeCommand(
            $this->getParam('employee')
        );

        $responseDTO = $this->getCommandBus()->handle($command);

        return new ViewModel(
            [
                'employee' => ['name' => $responseDTO->name()],
            ]
        );
    }
}
