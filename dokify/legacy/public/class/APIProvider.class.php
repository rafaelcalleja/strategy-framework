<?php

class APIProvider extends OAuthProvider
{
    const RESPONSE_ACCEPT = "accept";
    const RESPONSE_DENY = "deny";

    // Constantes para indentificar el tipo de API que se quiere usar. Corresponde con los modulos de AGD
    const API_TYPE_EMPLEADO = 8;
    const API_TYPE_USUARIO = 2;


    const CURRENT_VERSION = 1;

    const SERVER_ERROR_TOKEN_NO_CREATED = 1;

    public $verifier = null;
    public $consumer;
    public $user;
    public $usertype;

    private $loginURL;

    public function __construct()
    {
        parent::__construct();

        $this->loginURL = CURRENT_DOMAIN .'/api/?action=login';


        // Invocado desde APIProvider::checkOAuthRequest con prioridad 1
        $this->consumerHandler(function ($provider) {
            $consumer = OauthConsumer::findByKey($provider->consumer_key);

            if ($consumer instanceof OauthConsumer) {
                if (!$consumer->isActive()) {
                    return OAUTH_CONSUMER_KEY_REFUSED;
                } else {
                    $provider->consumer = $consumer;
                    $provider->consumer_secret = $consumer->getSecretKey();
                    return OAUTH_OK;
                }
            }

            return OAUTH_CONSUMER_KEY_UNKNOWN;
        });


        // Invocado desde APIProvider::checkOAuthRequest con prioridad 2
        $this->timestampNonceHandler(function ($provider) {
            if ($provider->timestamp < time() - 5*60) {
                return OAUTH_BAD_TIMESTAMP;
            } elseif ($provider->consumer->hasNonce($provider->nonce, $provider->timestamp)) {
                return OAUTH_BAD_NONCE;
            } else {
                $provider->consumer->addNonce($provider->nonce);
                return OAUTH_OK;
            }
        });

        // Invocado desde APIProvider::checkOAuthRequest con prioridad 3
        $this->tokenHandler(function ($provider) {
            $token = Token::findByToken($provider->token);
            if (!$token instanceof Token) { // token not found
                error_log('Access token rejected ['. $provider->token .']');
                return OAUTH_TOKEN_REJECTED;
            } elseif ($token->getType() == 1 && $token->getVerifier() != $provider->verifier) { // bad verifier for request token
                return OAUTH_VERIFIER_INVALID;
            } else {
                if ($token->getType() == 2) {
                    /* if this is an access token we register the user to the provider for use in our api */
                    //$provider->usertype = $token->getUserType();
                    $provider->user = $token->getUserID();
                }
                $provider->token_secret = $token->getSecret();
                return OAUTH_OK;
            }
        });

    }

    /**
      * Genera un string en formato url que actua como token de solicitud
      *
      */
    public function generateRequestToken()
    {
        $token = APIProvider::generateTokenString();
        $token_secret = APIProvider::generateTokenString();


        if (Token::createRequestToken($this->consumer, $token, $token_secret, $this->callback)) {
            return "authentification_url={$this->loginURL}&oauth_token={$token}&oauth_token_secret={$token_secret}&oauth_callback_confirmed=true";
        } else {
            APIProvider::throwError(APIProvider::SERVER_ERROR_TOKEN_NO_CREATED);
        }



        return false;
    }


    /**
      * Genera un string en formato url que actua como token de acceso
      *
      */
    public function generateAccessToken()
    {
        $access_token = APIProvider::generateTokenString();
        $secret = APIProvider::generateTokenString();

        $token = Token::findByToken($this->token);

        if ($token->changeToAccessToken($access_token, $secret)) {
            return "oauth_token={$access_token}&oauth_token_secret={$secret}";
        }

        return false;
    }


    /**
      * Establece el proveedor en modo para enviar un token de solicitud
      * Básicamente, para usar junto con generateRequestToken para definir el comportamiento
      * de APIProvider::checkOAuthRequest
      *
      */
    public function setRequestTokenMode()
    {
        $this->isRequestTokenEndpoint(true);
        $this->addRequiredParameter("oauth_callback");
        return $this;
    }

    /**
      * Esta es solo un alias para que retorne el objeto actual y permita concatenar
      *         Internamente el proceso es:
      *         1 - APIProvider::consumerHandler
      *         2 - APIProvider::timestampNonceHandler
      *         3 - ( si no se ha utilizado APIProvider::setRequestTokenMode ) APIProvider::tokenHandler
      */

    public function checkOAuthRequest($uri = null, $method = null)
    {
        parent::checkOAuthRequest();
        return $this;
    }


    public function getUserID()
    {
        if ($this->user) {
            return $this->user;
        } else {
            throw new Exception("Error. No se encuentra el usuario");
        }
    }

    public function getOauthConsumer()
    {
        $sql = "SELECT uid_consumer FROM ". OauthConsumer::TABLE ." WHERE consumer_key = '{$this->consumer_key}'";
        $uid = db::get($sql, 0, 0);
        if (is_numeric($uid) && $uid) {
            return new OauthConsumer($uid);
        } else {
            return false;
        }
    }


    public function parse($URI, $method = OAUTH_HTTP_METHOD_GET)
    {
        $URI = trim(str_replace("/api/", "", $URI));

        $request = explode("/", $URI);
        $version = array_shift($request);
        $URI = '/' . implode("/", $request);


        //$app = $this->getUserType(); // nos indica de que funciones debemos proveer
        $array = array("time" => time(), "request" => $URI, "version" => $version); // Por defecto siempre mandamos la hora local


        $app = $this->getOauthConsumer()->getUserModule();
        if ($app == APIProvider::API_TYPE_EMPLEADO) {
            include_once DIR_CLASS . "/api/empleadoAPI.class.php";
            $usuario = new empleadoAPI($this->getUserID());

            /*
            switch($method){
                case OAUTH_HTTP_METHOD_GET:
                    $array["id"] = $this->getUserID();
                break;
                case OAUTH_HTTP_METHOD_POST:
                    $array["error"] = "method_post_not_implemented";
                break;
                default:
                    $array["error"] = "method_not_implemented";
                break;
            }
            */

        } elseif ($app == APIProvider::API_TYPE_USUARIO) {
            include_once DIR_CLASS . "/api/usuarioAPI.class.php";
            $usuario = new usuarioAPI($this->getUserID());

            /*
            switch($method){
                case OAUTH_HTTP_METHOD_GET:
                    $array["id"] = $this->getUserID();
                break;
                case OAUTH_HTTP_METHOD_POST:
                    $array["error"] = "method_post_not_implemented";
                break;
                default:
                    $array["error"] = "method_not_implemented";
                break;
            };
            */
        } else {

            // No implementado
            $array["error"] = "api_type_not_found";
        }


        $fname = strtolower($method);
        if ($URI && count($request)) {
            $name = array_shift($request);
            //$parts = parse_url($query);
            if ($name) {
                $fname .= "_" . $name;
            }
        }


        //$fn = array($usuario, $fname);
        if (method_exists($usuario, $fname)) {
            $response = call_user_func_array(array($usuario,$fname), $request);
            $array = array_merge($array, $response);
        } else {
            //header('HTTP/1.0 404 Not Found');
            $array["error"] = 404; //"method_not_implemented";
            //$array["method"] = $fname;
        }

        header("Content-type: application/json");
        print json_encode($array);
    }


    final public static function exceptionMessage(OauthException $exception)
    {
        $errorString = parent::reportProblem($exception);
        parse_str($errorString, $error);

        if (isset($error["oauth_parameters_absent"])) {
            $error["oauth_parameters_absent"] = explode("&", $error["oauth_parameters_absent"]);
        }

        // En este caso no se especifica ningún parametro
        if (isset($error["oauth_parameters_absent"]) && count($error["oauth_parameters_absent"]) == 6) {
            header("HTTP/1.0 200 OK");

            print $errorString;
            /*$tpl = Plantilla::singleton();
            $tpl->display("api/index.tpl");*/
        } else {
            print $errorString;
        }
    }


    public static function throwError($code = 0)
    {
        die( OAuthProvider::reportProblem(new OAuthException("generic_error", $code)) );
    }

    public static function generateTokenString($length = 20)
    {
        return sha1(OAuthProvider::generateToken($length, true));
    }
}
