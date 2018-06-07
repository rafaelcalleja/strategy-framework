<?php

    require __DIR__ . '/../config.php';
    require DIR_CLASS . '/customSession.class.php';
    $session = new CustomSession();


    // --- redirect if session is active
    if (isset($_SESSION[SESSION_USUARIO])) {
        $location = '/agd';
        if (isset($_SESSION[SESSION_TYPE]) && $_SESSION[SESSION_TYPE] != 'usuario') $location = '/' . $_SESSION[SESSION_TYPE];

        header("Location: $location"); exit;
    }


    // ESTABLECEMOS EL IDIOMA
    if (isset($_GET["lang"]) && $lang = $_GET["lang"]) {
        setcookie("lang", $lang , time()+60*60*24*30 );
        $_SESSION["lang"] = $lang;
    }

    $_SESSION[SESSION_TYPE] = 'usuario';
    $_SESSION["lang"] = Plantilla::getCurrentLocale();

    //----- creamos la instancia de la plantilla
    $template = new Plantilla();
    $log = log::singleton();
    $userExists = false;


    $token = (isset($_COOKIE["token"]) && isset($_COOKIE["username"])) || isset($_REQUEST["token"]) && isset($_REQUEST["username"]);

    //----- el usuario que se iniciara session
    if (isset($_POST["usuario"]) && isset($_POST["password"])) {
        if (isset($_SESSION['username'])) {
            unset($_SESSION['username']);
        }

        $loginUser = usuario::login($_POST["usuario"], $_POST["password"]);
        $log->info("usuario","login normal",$_POST["usuario"]);

        // login empleado
        if (!$loginUser && $loginEmployee = empleado::login($_POST["usuario"], $_POST["password"])) {
            $loginUser = $loginEmployee;
            $_SESSION[SESSION_TYPE] = 'empleado';
            $log->info("empleado", "login normal", $_POST["usuario"]);
        } else {
            $userExists = usuario::login($_POST["usuario"]);
        }

    } elseif ($token) {
        $username = isset($_REQUEST["username"]) ? $_REQUEST["username"] : $_COOKIE["username"];
        $token = isset($_REQUEST["token"]) ? $_REQUEST["token"] : $_COOKIE["token"];

        $loginUser = usuario::instanceFromCookieToken($username , $token);
        $log->info("usuario", "login cookie", $username );


        if (!$loginUser && $loginEmployee = empleado::instanceFromCookieToken($username, $token)) {
            $loginUser = $loginEmployee;
            $_SESSION[SESSION_TYPE] = 'empleado';
            $log->info("empleado", "login cookie", $username);
        }
    }

    if (isset($loginUser) && ($loginUser instanceof Iusuario) && is_numeric($loginUser->getUID()) && !isset($codeFalse)) {
        $log->resultado("ok", true);


        if ($loginUser->necesitaCambiarPassword()) {
            $_SESSION[SESSION_USUARIO."_TMP"] = $loginUser->getUID();
            header("Location: ./chgpassword.php"); exit;
        } else {

            $loginUser->checkFirstLogin();
            $token = $loginUser->getCookieToken();

            //crear o eliminar las cookies
            if (isset($_POST["rememberme"])) {
                // this cookie['uid'] is not used:
                // setcookie("uid", $loginUser->getUID(), time()+60*60*24*30, '/');
                setcookie("username", $loginUser->getUserName(), time()+60*60*24*30, '/');
                setcookie("token", $token, time()+60*60*24*30, '/');
            } else {

                // --- we already got our
                if (!$token) {
                    setcookie("username", 0, time()-3600, '/');
                    setcookie("token", 0, time()-3600, '/');
                }
            }

            unset($_SESSION["RAND1"]);
            unset($_SESSION["RAND2"]);
            unset($_SESSION["LOGIN_TRY"]);

            // Usamos la variable de sesion en el sistema
            $_SESSION[SESSION_USUARIO] = $loginUser->getUID();
            $_SESSION['ip'] = log::getIPAddress();

            session_write_close();


            if (isset($_REQUEST["goto"]) && trim($_REQUEST["goto"])) {
                if (str_replace("/", "", $_REQUEST["goto"]) != "agd") {
                    $path = '';
                    if (isset($_REQUEST["origin"]) && $origin = $_REQUEST["origin"]) {
                        $path .= '?origin=' . $origin;
                    }
                    // add /app when goto new app
                    if (false === strpos($_REQUEST["goto"], "agd")
                        && false === strpos($_REQUEST["goto"], "/app/")
                    ) {
                        $path .= "/app";
                    }

                    header("Location: ". $path . $_REQUEST["goto"]);
                    exit;
                }
            }

            // Zendesk SSO system
            if( isset($_REQUEST["return_to"]) && $URL = trim($_REQUEST["return_to"]) ){
                $timestamp = @$_REQUEST["timestamp"];
                $localeid = @$_REQUEST["locale_id"];

                $URL = $loginUser->getZendeskURL($URL, $timestamp, $localeid);

                header("Location: " . $URL);
                exit;
            }

            // Zendesk SSO system
            if (isset($_REQUEST["return"]) && $URL = trim($_REQUEST["return"])) {
                $URL = "https://dokify.uservoice.com/login_success?sso=" . $loginUser->getUserVoiceToken();

                header("Location: " . $URL);
                exit;
            }

            if (isset($_REQUEST["webservice"])) {
                die($token);
            }

            if ($loginUser instanceof empleado) {
                header("Location: ../empleado/");
            } else {
                header("Location: ./");
            }

            exit;
        }
    } elseif( isset($loginUser) /*&& $loginUser instanceof usuario*/ ){
        if( !isset($_SESSION["LOGIN_TRY"]) ){ $_SESSION["LOGIN_TRY"]=0; }
        $_SESSION["LOGIN_TRY"]++;

        $nivel = ( $_SESSION["LOGIN_TRY"] < 5 ) ? $_SESSION["LOGIN_TRY"] : 4;
        $log->nivel($nivel);
        $log->resultado("acceso fallido ".$_SESSION["LOGIN_TRY"], true);

        if( isset($_SESSION["LOGIN_TRY"]) && $_SESSION["LOGIN_TRY"] > 3 ){
            //PREGUNTA DE SEGURIDAD
            $_SESSION["RAND1"] = rand(0,30);
            $_SESSION["RAND2"] = rand(0,30);
            $template->assign( "rand1", $_SESSION["RAND1"] );
            $template->assign( "rand2", $_SESSION["RAND2"] );
            $template->assign("security", true );
        }

        if( !isset($codeFalse) ){
            $template->assign( "error", "error_usuario_password"  );
        }
    } elseif( isset($loginUser) ) {
        $template->assign( "error", "error_usuario_password"  );
    }


    // Llegados aqui tendremos que borrar las cookies
    setcookie("username", 0, time()-3600, '/');
    setcookie("token", 0, time()-3600, '/');



    if (isset($_REQUEST["webservice"])) {
        exit;
    }


    if( strpos($_SERVER["HTTP_USER_AGENT"],"MSIE") ){
        $template->assign( "ie", true );
    }


    $blacklist = array("/agd/");

    $param = ( isset($_SERVER["QUERY_STRING"]) && trim($_SERVER["QUERY_STRING"]) ) ? get_concat_char(@$_SERVER["QUERY_STRING"]) . @$_SERVER["QUERY_STRING"] : "";
    if( $error = $template->get_template_vars("error") ){
        if ($userExists) {
            if ($userExists instanceof usuario && false === $userExists->isActive()) {
                $_SESSION['username'] = $_POST["usuario"];
                $error = "error_usuario_in_trash";
            } else {
                $_SESSION['username'] = $_POST["usuario"];
                $error = "error_password";
            }
        }

        $param .= (trim($param)?"&":"?") . "loc=$error";

        if( isset($_REQUEST["return_to"]) && $URL = trim($_REQUEST["return_to"]) ){
            $param .= "&return_to=$URL";
        }

        if (isset($_REQUEST["goto"]) && $URL = trim($_REQUEST["goto"])) {
            $param .= "&goto=". urlencode($URL);
        }

        if (isset($_REQUEST["origin"]) && $origin = $_REQUEST["origin"]) {
            $param .= '&origin=' . $origin;
        }
    }

    header("Location: ../login.php$param");