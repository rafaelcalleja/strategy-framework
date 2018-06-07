<?php

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class ProjectUrlGeneratorLegacy extends Symfony\Component\Routing\Generator\UrlGenerator
{
    private static $declaredRoutes;
    private $defaultLocale;

    public function __construct(RequestContext $context, LoggerInterface $logger = null, string $defaultLocale = null)
    {
        $this->context = $context;
        $this->logger = $logger;
        $this->defaultLocale = $defaultLocale;
        if (null === self::$declaredRoutes) {
            self::$declaredRoutes = array(
        'route1' => array(array(), array('_controller' => 'MyController::fooAction'), array(), array(array('text', '/foo')), array(), array()),
        'settings-profile' => array(array(), array('_controller' => 'Legacy::show', '_before' => array('login:noChecks')), array(), array(array('text', '/settings/profile')), array(array('text', '10.10.10.10')), array('https')),
        'settings-profile-control' => array(array(), array('_controller' => 'Legacy::show', '_before' => array('login:noChecks')), array(), array(array('text', '/settings/profile')), array(array('text', '10.10.10.10')), array('https')),
        'redirect-outside' => array(array(), array('_controller' => 'Legacy::show', '_before' => array('login:noChecks')), array(), array(array('text', '/redirect/outside')), array(array('text', 'redirect.example.com')), array('https')),
    );
        }
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        $locale = $parameters['_locale']
            ?? $this->context->getParameter('_locale')
            ?: $this->defaultLocale;

        if (null !== $locale && (self::$declaredRoutes[$name.'.'.$locale][1]['_canonical_route'] ?? null) === $name) {
            unset($parameters['_locale']);
            $name .= '.'.$locale;
        } elseif (!isset(self::$declaredRoutes[$name])) {
            throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
        }

        list($variables, $defaults, $requirements, $tokens, $hostTokens, $requiredSchemes) = self::$declaredRoutes[$name];

        return $this->doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, $requiredSchemes);
    }
}
