<?php

namespace Dokify\Router\Routing;

use Symfony\Component\Routing\Router as BaseRouter;

class Router extends BaseRouter
{
    public function setOptions(array $options)
    {
        $options = array_merge(
            [
               'matcher_class' => 'Dokify\\Router\\Matcher\\UrlMatcher',
               'matcher_dumper_class' => 'Dokify\\Router\\Matcher\\Dumper\\CustomDumper',
            ],
            $options
        );

        parent::setOptions($options);
    }
}
