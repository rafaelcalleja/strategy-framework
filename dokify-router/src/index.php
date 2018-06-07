<?php

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

require_once __DIR__ . '/../vendor/autoload.php';

$finderLegacy = new Symfony\Component\Finder\Finder();
$finderLegacy->in(__DIR__.'/../config/routes/legacy');
$finderLegacy->files()->name('*yaml');

$finderNew = new Symfony\Component\Finder\Finder();
$finderNew->in(__DIR__.'/../config/routes/new/');
$finderNew->files()->name('*yaml');

$dumperLegacy = new \Dokify\Router\Config\Dumper(
    $finderLegacy,
    __DIR__. '/../config/parameters.yaml'
);

$dumperNew = new \Dokify\Router\Config\Dumper(
    $finderNew,
    __DIR__. '/../config/parameters.yaml'
);

$dumpCacheDirectoryLegacy = __DIR__ . '/../var/cache/routes/legacy/';
$dumpCacheDirectoryNew = __DIR__ . '/../var/cache/routes/new/';
$dumperLegacy->dump(
    $dumpCacheDirectoryLegacy
);

$dumperNew->dump(
    $dumpCacheDirectoryNew
);

$requestContext = new RequestContext('/');

$routerLegacy = new Router(
    new YamlFileLoader(new FileLocator([$dumpCacheDirectoryLegacy])),
    'routes.yaml',
    [
        'cache_dir' => __DIR__.'/../var/cache',
        'generator_cache_class' => 'ProjectUrlGeneratorLegacy',
        'matcher_cache_class' => 'ProjectUrlMatcherLegacy'
    ],
    $requestContext
);

$routerNew = new Router(
    new YamlFileLoader(new FileLocator([$dumpCacheDirectoryNew])),
    'routes.yaml',
    [
        'cache_dir' => __DIR__.'/../var/cache',
        'generator_cache_class' => 'ProjectUrlGeneratorNew',
        'matcher_cache_class' => 'ProjectUrlMatcherNew'

    ],
    $requestContext
);

$router = new \Symfony\Cmf\Component\Routing\ChainRouter();
$router->add($routerNew);
$router->add($routerLegacy);

assert($router->match('/foo'));
unset($requestContext);

$requestContext = new RequestContext('/app');
$requestContext->setHost('10.10.10.10');
$requestContext->setScheme('https');
$router->setContext($requestContext);

assert($router->match('/settings/profile'));
assert('/app/settings/profile' === $router->generate('settings-profile'));
assert('https://10.10.10.10/app/settings/profile' === $router->generate('settings-profile', [], UrlGeneratorInterface::ABSOLUTE_URL));

$requestContext = new RequestContext();
$requestContext->setHost('10.10.10.10');
$requestContext->setHttpsPort(8443);
$requestContext->setScheme('https');
$router->setContext($requestContext);

assert($router->match('/settings/profile'));
assert('/settings/profile' === $router->generate('settings-profile'));
assert('https://10.10.10.10:8443/settings/profile' === $router->generate('settings-profile', [], UrlGeneratorInterface::ABSOLUTE_URL));

$requestContext = new RequestContext('/app');
$requestContext->setHost('10.10.10.10');
$requestContext->setScheme('https');
$router->setContext($requestContext);

assert($router->match('/settings/profile'));
assert('/app/settings/profile' === $router->generate('settings-profile-control'));
assert('https://10.10.10.10/app/settings/profile' === $router->generate('settings-profile', [], UrlGeneratorInterface::ABSOLUTE_URL));

$requestContext = new RequestContext('/symfony');
$requestContext->setHost('10.10.10.10');
$requestContext->setScheme('https');
$requestContext->setHttpsPort(8443);
$router->setContext($requestContext);

assert($router->match('/settings/profile'));
assert('/symfony/settings/profile' === $router->generate('settings-profile-control'));
assert('https://10.10.10.10:8443/symfony/settings/profile' === $router->generate('settings-profile', [], UrlGeneratorInterface::ABSOLUTE_URL));

$requestContext = new RequestContext('/app');
$requestContext->setHost('10.10.10.10');
$requestContext->setScheme('https');
$requestContext->setHttpsPort(8443);
$router->setContext($requestContext);

assert($router->match('/settings/profile/control'));
assert('/app/settings/profile' === $router->generate('settings-profile-control'));
assert('https://10.10.10.10:8443/app/settings/profile' === $router->generate('settings-profile', [], UrlGeneratorInterface::ABSOLUTE_URL));


$requestContext = new RequestContext('/symfony');
$requestContext->setHost('10.10.10.10');
$requestContext->setScheme('https');
$requestContext->setHttpsPort(8443);
$router->setContext($requestContext);


assert($router->match('/settings/profile/control'));
assert('/symfony/settings/profile/control' === $router->generate('settings-profile-control-2'));
assert('https://10.10.10.10:8443/symfony/settings/profile/control' === $router->generate('settings-profile-control-2', [], UrlGeneratorInterface::ABSOLUTE_URL));

var_dump($router->generate('prefix_test'));
#
#var_dump($router->match('/prefix/test'));

