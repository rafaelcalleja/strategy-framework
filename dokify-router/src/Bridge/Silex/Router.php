<?php

namespace Dokify\Router\Bridge\Silex;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class Router implements RouterInterface, RequestMatcherInterface
{
    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router, RouteCollection $routes)
    {
        $this->routes = $routes;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        $match = $this->router->match($pathinfo);
        // injects the route into the route collection, so Silex will keep its behavior
        $route = $this->getRouteFromMatch($match, $pathinfo);
        $this->routes->add($match['_route'], $route);
        return $match;
    }

    private function getRouteFromMatch($match, $pathinfo)
    {
        $options = $this->getOptions($match);
        return new Route($pathinfo, $match, $requirements = [], $options, $host = '', $schemes = [], $methods = [], $condition = '');
    }

    private function getOptions($match)
    {
        $options = $match;

        if (isset($options['_controller'])) {
            unset($options['_controller']);
        }

        if (isset($options['_route'])) {
            unset($options['_route']);
        }

        if (isset($options['_before'])) {
            $options['_before_middlewares'] = $match['_before'];
            unset($options['_before']);
        }

        if (isset($options['_after'])) {
            $options['_after_middlewares'] = $match['_after'];
            unset($options['_after']);
        }

        if (isset($options['_convert'])) {
            $options['_converters'] = $match['_convert'];
            unset($options['_convert']);
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->router->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request)
    {
        return $this->router->matchRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        return $this->router->getRouteCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->router->generate($name, $parameters, $referenceType);
    }
}
