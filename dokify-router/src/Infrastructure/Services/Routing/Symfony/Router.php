<?php

namespace Dokify\Router\Infrastructure\Services\Routing\Symfony;

use Symfony\Component\Routing\Router as BaseRouter;

class Router extends BaseRouter
{
    public function setOptions(array $options)
    {
        $options = array_merge(
            [
               'matcher_class' => 'Dokify\\Router\\Infrastructure\\Services\\Routing\\Symfony\\Matcher\\UrlMatcher',
               'matcher_dumper_class' => 'Dokify\\Router\\Infrastructure\\Services\\Routing\\Symfony\\Matcher\\Dumper\\CustomDumper',
            ],
            $options
        );

        parent::setOptions($options);
    }
}
