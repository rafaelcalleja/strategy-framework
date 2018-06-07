<?php

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('zend.assertions', 1);
ini_set('assert.exception .assertions ', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
assert_options(ASSERT_QUIET_EVAL, 0);


/*assert_options(ASSERT_CALLBACK, function($file, $line, $code)
{
    echo "<hr>Assertion Failed:
        File '$file'<br />
        Line '$line'<br />
        Code '$code'<br /><hr />";

});*/

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Dokify\Router\Routing\Router;




function getRouterUsingCache()
{
    $routesDirectoryLegacy = __DIR__ . '/../config/routes/legacy/';

    return new Router(
        new YamlFileLoader(new FileLocator([$routesDirectoryLegacy])),
        'routes.yaml',
        [
            'cache_dir' => __DIR__.'/../var/cache',
            'generator_cache_class' => 'ProjectUrlGeneratorLegacy',
            'matcher_cache_class' => 'ProjectUrlMatcherLegacy'
        ],
        new RequestContext()
    );
}



function getRouterWithoutCache()
{
    $routesDirectoryLegacy = __DIR__ . '/../config/routes/legacy/';

    return new Router(
        new YamlFileLoader(new FileLocator([$routesDirectoryLegacy])),
        'routes.yaml',
        [
            'generator_cache_class' => 'ProjectUrlGeneratorLegacy',
            'matcher_cache_class' => 'ProjectUrlMatcherLegacy'
        ],
        new RequestContext()
    );
}


function testMatchingRouteWithSamePort(Router $router)
{
    clearCacheDir();

    $requestContext = new RequestContext('/app');
    $requestContext->setHost('10.10.10.10');
    $requestContext->setScheme('https');
    $requestContext->setHttpsPort(8443);
    $router->setContext($requestContext);

    $match = $router->match('/redirect/outside');
    assert(true === (bool) $match);
}

function testMatchingRouteWithOtherPort(Router $router)
{

    clearCacheDir();

    $requestContext = new RequestContext('/app');
    $requestContext->setHost('10.10.10.10');
    $requestContext->setScheme('https');
    $requestContext->setHttpsPort(443);
    $router->setContext($requestContext);

    $exception = false;
    try{
       $router->match('/redirect/outside');
    }catch(\Symfony\Component\Routing\Exception\ResourceNotFoundException $e)
    {
        $exception = true;
    }

    assert(true === $exception);
}

function testGenerateRoute(Router $router) {
    assert('/app/redirect/outside' === $router->generate('redirect-outside'));
}

function clearCacheDir()
{
    $dir = __DIR__.'/../var/cache/';
    $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
    $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ( $ri as $file ) {
        $file->isDir() ?  rmdir($file) : unlink($file);
    }

}

$routerWithoutCache = getRouterWithoutCache();
testMatchingRouteWithSamePort($routerWithoutCache);
testMatchingRouteWithOtherPort($routerWithoutCache);
testGenerateRoute($routerWithoutCache);

$router = getRouterUsingCache();
testMatchingRouteWithSamePort($router);
testMatchingRouteWithOtherPort($router);
testGenerateRoute($router);
