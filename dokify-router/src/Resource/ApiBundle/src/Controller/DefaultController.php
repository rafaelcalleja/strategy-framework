<?php

namespace App\Controller;

use Dokify\Router\Infrastructure\Services\Routing\Symfony\Router;
use Dokify\Router\Infrastructure\Services\Routing\SymfonyRoutingService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Loader\YamlFileLoader;

class DefaultController extends Controller
{
    /**
     * @Route("/match/", name="match_route")
     */
    public function index()
    {
        /**
         * @var Request
         */
        $request = $this->get('request_stack')->getCurrentRequest();

        $command = new \Dokify\Router\Application\Service\MatchRequestCommand(
            $request->query->get('base_url', ''),
            $request->query->get('method', 'GET'),
            $request->query->get('host', ''),
            $request->query->get('scheme', 'http'),
            (int) $request->query->get('httpport', 80),
            (int) $request->query->get('httpsport', 443),
            $request->query->get('path'),
            $request->query->get('querystring', '')
        );

        $handler = new \Dokify\Router\Application\Service\MatchRequestHandler(
            $this->getExternalService()
        );

        $response = $handler->execute($command);


        return new JsonResponse($response);
    }

    /**
     * @Route("/generate/{routeName}/{referenceType}", name="generate_route")
     */
    public function generate($routeName, $referenceType = 1)
    {
        /**
         * @var Request
         */
        $request = $this->get('request_stack')->getCurrentRequest();

        $command = new \Dokify\Router\Application\Service\GenerateRequestCommand(
            $routeName,
            $request->query->get('parameters', []),
            $referenceType
        );

        $handler = new \Dokify\Router\Application\Service\GenerateRequestHandler(
            $this->getExternalService()
        );

        $response = $handler->execute($command);


        return new JsonResponse($response);
    }

    private function getExternalService()
    {
        $routesDirectoryLegacy = __DIR__ . '/../../../../../config/';

        return new SymfonyRoutingService(
            new Router(
                new YamlFileLoader(
                    new FileLocator([$routesDirectoryLegacy])
                ),
                'routes-dev.yml',
                [
                        'cache_dir' => __DIR__.'/../../../../../var/cache',
                ]
            )
        );
    }
}
