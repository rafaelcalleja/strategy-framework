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
    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function showAction()
    {
        $command = new ShowEmployeeCommand(
            $this->getParam('employee')
        );

        $responseDTO = $this->commandBus->handle($command);

        return new ViewModel(
            [
                'employee' => ['name' => $responseDTO->name()],
            ]
        );
    }
}
