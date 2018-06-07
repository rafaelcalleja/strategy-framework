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

$dumperLegacy = new \Dokify\Router\Config\Dumper(
    $finderLegacy,
    __DIR__. '/../config/parameters.yaml'
);

$dumpCacheDirectoryLegacy = __DIR__ . '/../var/cache/routes/legacy/';
$dumperLegacy->dump(
    $dumpCacheDirectoryLegacy
);

$requestContext = new RequestContext('/app');
$requestContext->setHost('10.10.10.10');
$requestContext->setScheme('https');

$router = new Router(
    new YamlFileLoader(new FileLocator([$dumpCacheDirectoryLegacy])),
    'routes.yaml',
    [
        'cache_dir' => __DIR__.'/../var/cache',
        'generator_cache_class' => 'ProjectUrlGeneratorLegacy',
        'matcher_cache_class' => 'ProjectUrlMatcherLegacy'
    ],
    $requestContext
);

function testRedirectToAnotherBaseUrl($router)
{
    $expected = 'https://redirect.example.com/symfony/redirect/outside';

    assert($expected === $router->generate('redirect-outside', [], UrlGeneratorInterface::ABSOLUTE_URL));
}

testRedirectToAnotherBaseUrl($router);
