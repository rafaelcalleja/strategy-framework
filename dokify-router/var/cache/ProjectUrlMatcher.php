<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class ProjectUrlMatcher extends Symfony\Component\Routing\Matcher\UrlMatcher
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
            case '/settings/profile':
                // settings-profile
                if ('%silex_host%' === $host) {
                    $ret = array('_route' => 'settings-profile', '_controller' => 'Legacy::show', '_before' => array('login:noChecks'));
                    $requiredSchemes = array('https' => 0);
                    $hasRequiredScheme = isset($requiredSchemes[$context->getScheme()]);
                    if (!isset(($a = array('GET' => 0))[$canonicalMethod])) {
                        if ($hasRequiredScheme) {
                            $allow += $a;
                        }
                        goto not_settingsprofile;
                    }
                    if (!$hasRequiredScheme) {
                        $allowSchemes += $requiredSchemes;
                        goto not_settingsprofile;
                    }

                    return $ret;
                }
                not_settingsprofile:
                // settings-profile-control
                if ('%symfony_host%' === $host) {
                    $ret = array('_route' => 'settings-profile-control', '_controller' => 'Legacy::show', '_before' => array('login:noChecks'));
                    $requiredSchemes = array('https' => 0);
                    $hasRequiredScheme = isset($requiredSchemes[$context->getScheme()]);
                    if (!isset(($a = array('GET' => 0))[$canonicalMethod])) {
                        if ($hasRequiredScheme) {
                            $allow += $a;
                        }
                        goto not_settingsprofilecontrol;
                    }
                    if (!$hasRequiredScheme) {
                        $allowSchemes += $requiredSchemes;
                        goto not_settingsprofilecontrol;
                    }

                    return $ret;
                }
                not_settingsprofilecontrol:
                break;
            default:
                $routes = array(
                    '/foo' => array(array('_route' => 'route1', '_controller' => 'MyController::fooAction'), null, null, null),
                    '/redirect/outside' => array(array('_route' => 'redirect-outside', '_controller' => 'Legacy::show', '_before' => array('login:noChecks'), '_httpsPort' => 8443), null, array('GET' => 0), array('https' => 0)),
                );

                if (!isset($routes[$pathinfo])) {
                    break;
                }
                list($ret, $requiredHost, $requiredMethods, $requiredSchemes) = $routes[$pathinfo];
                
                if (isset($ret['_httpsPort']) && (int) $this->context->getHttpsPort() !== (int) $ret['_httpsPort']){
                    break;
                }            

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
