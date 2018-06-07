<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class ProjectUrlMatcherNew extends Symfony\Component\Routing\Matcher\UrlMatcher
{
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function match($rawPathinfo)
    {
        $allow = $allowSchemes = array();
        $pathinfo = rawurldecode($rawPathinfo);
        $context = $this->context;
        $requestMethod = $canonicalMethod = $context->getMethod();
        $host = strtolower($context->getHost());

        if ('HEAD' === $requestMethod) {
            $canonicalMethod = 'GET';
        }

        switch ($pathinfo) {
            default:
                $routes = array(
                    '/settings/profile' => array(array('_route' => 'settings-profile', '_controller' => '\\Dokify\\Controller\\Settings\\Profile::show', '_before' => array('login:noChecks')), '10.10.10.10', array('GET' => 0), array('https' => 0)),
                    '/settings/profile/control' => array(array('_route' => 'settings-profile-control-2', '_controller' => '\\Dokify\\Controller\\Settings\\Profile::show', '_before' => array('login:noChecks')), '10.10.10.10', array('GET' => 0), array('https' => 0)),
                    '/app/prefix/prefix/test' => array(array('_route' => 'prefix_test', '_controller' => '\\Dokify\\Controller\\Settings\\Profile::show', '_before' => array('login:noChecks')), null, array('GET' => 0), array('https' => 0)),
                );

                if (!isset($routes[$pathinfo])) {
                    break;
                }
                list($ret, $requiredHost, $requiredMethods, $requiredSchemes) = $routes[$pathinfo];

                if ($requiredHost) {
                    if ('#' !== $requiredHost[0] ? $requiredHost !== $host : !preg_match($requiredHost, $host, $hostMatches)) {
                        break;
                    }
                    if ('#' === $requiredHost[0] && $hostMatches) {
                        $hostMatches['_route'] = $ret['_route'];
                        $ret = $this->mergeDefaults($hostMatches, $ret);
                    }
                }

                $hasRequiredScheme = !$requiredSchemes || isset($requiredSchemes[$context->getScheme()]);
                if ($requiredMethods && !isset($requiredMethods[$canonicalMethod]) && !isset($requiredMethods[$requestMethod])) {
                    if ($hasRequiredScheme) {
                        $allow += $requiredMethods;
                    }
                    break;
                }
                if (!$hasRequiredScheme) {
                    $allowSchemes += $requiredSchemes;
                    break;
                }

                return $ret;
        }

        if ('/' === $pathinfo && !$allow) {
            throw new Symfony\Component\Routing\Exception\NoConfigurationException();
        }

        throw $allow ? new MethodNotAllowedException(array_keys($allow)) : new ResourceNotFoundException();
    }
}
