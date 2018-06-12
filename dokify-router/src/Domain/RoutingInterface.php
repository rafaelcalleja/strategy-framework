<?php

namespace Dokify\Router\Domain;

interface RoutingInterface
{
    /**
     * Generates an absolute URL, e.g. "http://example.com/dir/file".
     */
    const ABSOLUTE_URL = 0;

    /**
     * Generates an absolute path, e.g. "/dir/file".
     */
    const ABSOLUTE_PATH = 1;

    /**
     * Generates a relative path based on the current request path, e.g. "../parent-file".
     */
    const RELATIVE_PATH = 2;

    /**
     * Generates a network path, e.g. "//example.com/dir/file".
     * Such reference reuses the current scheme but specifies the host.
     */
    const NETWORK_PATH = 3;

    public function match(
        string $baseUrl = '',
        string $method = 'GET',
        string $host = 'localhost',
        string $scheme = 'http',
        int $httpPort = 80,
        int $httpsPort = 443,
        string $path = '/',
        string $queryString = ''
    );

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     *
     * Parameters that reference placeholders in the route pattern will substitute them in the
     * path or host. Extra params are added as query string to the URL.
     *
     * When the passed reference type cannot be generated for the route because it requires a different
     * host or scheme than the current one, the method will return a more comprehensive reference
     * that includes the required params. For example, when you call this method with $referenceType = ABSOLUTE_PATH
     * but the route requires the https scheme whereas the current scheme is http, it will instead return an
     * ABSOLUTE_URL with the https scheme and the current host. This makes sure the generated URL matches
     * the route in any case.
     *
     * If there is no route with the given name, the generator must throw the RouteNotFoundException.
     *
     * The special parameter _fragment will be used as the document fragment suffixed to the final URL.
     *
     * @param string $routeName     The name of the route
     * @param mixed  $parameters    An array of parameters
     * @param int    $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string The generated URL
     *
     * @throws RouteNotFoundException              If the named route doesn't exist
     * @throws MissingMandatoryParametersException When some parameters are missing that are mandatory for the route
     * @throws InvalidParameterException           When a parameter value for a placeholder is not correct because
     *                                             it does not match the requirement
     */
    public function generate(
        string $routeName,
        array $parameters,
        int $referenceType,
        string $baseUrl = '',
        string $path = '',
        string $host = '',
        string $scheme = '',
        int $httpPort = 80,
        int $httpsPort = 443
    );
}
