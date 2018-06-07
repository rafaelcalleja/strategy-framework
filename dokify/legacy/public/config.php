<?php

// config files
require_once __DIR__ . "/config/release.php";
require_once __DIR__ . "/config/defines.php";
require_once __DIR__ . "/config/error.php";
require_once __DIR__ . "/config/ie.php";
require_once __DIR__ . "/config/modules.php";
require_once __DIR__ . "/config/opera.php";
require_once __DIR__ . "/config/shutdown.php";
require_once __DIR__ . "/config/webkit.php";

// functions
require_once __DIR__ . "/func/array.func.php";
require_once __DIR__ . "/func/asyncSend.func.php";
require_once __DIR__ . "/func/bool2str.inc.php";
require_once __DIR__ . "/func/client_url.inc.php";
require_once __DIR__ . "/func/color.inc.php";
require_once __DIR__ . "/func/color_assoc.inc.php";
require_once __DIR__ . "/func/compress.php";
require_once __DIR__ . "/func/date.inc.php";
require_once __DIR__ . "/func/display.func.php";
require_once __DIR__ . "/func/doget.inc.php";
require_once __DIR__ . "/func/get.inc.php";
require_once __DIR__ . "/func/getclientversion.func.php";
require_once __DIR__ . "/func/getfilecontent.inc.php";
require_once __DIR__ . "/func/git.func.php";
require_once __DIR__ . "/func/ismobile.func.php";
require_once __DIR__ . "/func/istouch.inc.php";
require_once __DIR__ . "/func/pagination.func.php";
require_once __DIR__ . "/func/parameters.func.php";
require_once __DIR__ . "/func/parseua.func.php";
require_once __DIR__ . "/func/playthesound.inc.php";
require_once __DIR__ . "/func/request.func.php";
require_once __DIR__ . "/func/string.func.php";
require_once __DIR__ . "/func/sumario.php";
require_once __DIR__ . "/func/text2html.inc.php";
require_once __DIR__ . "/func/time.func.php";
require_once __DIR__ . "/func/urlexists.inc.php";
require_once __DIR__ . "/func/utilurls.php";
require_once __DIR__ . "/func/words.inc.php";
require_once __DIR__ . "/func/xml2array.php";

// libs
require_once __DIR__ . "/../src/lib/browselanguage.php";
require_once __DIR__ . "/class/quaderno/quaderno_load.php";

// new app config
require_once __DIR__ . '/../src/config.php';
