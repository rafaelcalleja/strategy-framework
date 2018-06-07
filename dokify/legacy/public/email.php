<?php

use Dokify\Application;
use Dokify\I18NString;
use Dokify\TwigTemplate;

require __DIR__ . '/../src/config.php';

$app = Application::getInstance();

$user = $app['user.repository']->find(1);

// using a translated string
$subject    = _("Summary Dokify");
$subject    = new I18NString($subject, $user);
$template   = "email/profile/summary.html";

$twig = new TwigTemplate($template);
$html = $twig->render([
    'user'        => (array) $user,
    'company'    => [
        'name'        => 'Camiones Roma S.L.',
        'alerts'    => 10
    ]
]);

print $html;
