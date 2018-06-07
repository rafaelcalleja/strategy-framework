<?php

include_once DIR_CLASS."PHPMailer/class.phpmailer.php";

class email
{
    const INI_KEY_SERVER    = 'email.server';
    const INI_KEY_FROM      = 'email.from';
    const INI_KEY_USERNAME  = 'email.username';
    const INI_KEY_PASSWORD  = 'email.password';
    const STATUS_OK = 0;
    const STATUS_ERROR = 1;

    protected $to; //para quien va el email
    protected $asunto; //el asunto
    protected $body;
    public $mailer;

    public static $facturacion = array("facturacion@dokify.net","soporte@dokify.net");
    public static $developers = [
        'ldonoso@dokify.net',
        'pdelmoral@dokify.net',
    ];
    public static $support = array("soporte@dokify.net");
    public static $leads = ['leads@dokify.net'];

    public function __construct()
    {
        $args = func_get_args();
        if (count($args) == 1 && is_traversable($args[0])) {
            $list = $args[0] instanceof ArrayObject ? $args[0]->getArrayCopy() : $args[0];
            $this->to = array_map("trim", $list);
        } else {
            $this->to = array_map("trim", $args);
        }

        $this->mailer = new PHPMailer();
    }

    public function establecerDestinatarios()
    {
        $this->to = array();
        $args = func_get_args();
        if (count($args) == 1 && is_traversable($args[0])) {
            $this->to = $args[0];
        } else {
            $this->to = $args;
        }
    }

    public function establecerAsunto($asunto)
    {
        $this->asunto = $asunto;
    }

    /** ENVIAR EL EMAIL DESDE UNA PLANTILLA, O BIEN DE EMAIL O BIEN DE HTML
            · Si es una plantilla de email el segundo parametro debe ser el objeto empresa
            · Si es una Plantilla html el segundo parametro debe ser la ruta, en caso de ser plantilla html empresa
    */
    public function enviardesdePlantilla($plantillaemail, $param = false)
    {
        if ($plantillaemail instanceof plantillaemail) {
            if (!$param instanceof empresa) {
                return false;
            }

            $this->body = $plantillaemail->getFileContent($param, true);
        } elseif ($plantillaemail instanceof Plantilla) {
            if ($param == false) {
                return false;
            }

            $this->body = $plantillaemail->getHTML($param);
        }
    }

    public function adjuntar($path, $name, $encoding = "base64", $type = "application/octet-stream")
    {
        // Si nos adjuntan un fichero que no está en disco, es posible que esté en el S3
        if (!is_readable($path) && $s3 = archivo::getS3()) {
            if (archivo::is_readable($path) && $data = archivo::leer($path)) {
                $path = '/tmp/emailfile.'. md5($path) .'.'. basename($path);
                if (!file_put_contents($path, $data)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        if (strpos($name, ".") === false) {
            $name .= "." . archivo::getExtension($path);
        }

        return $this->mailer->AddAttachment($path, $name, $encoding, $type);
    }

    public function establecerContenido($contenido, $add = false)
    {
        if ($add) {
            $this->body .= $contenido;
        } else {
            $this->body = $contenido;
        }

    }

    public function obtenerContenido()
    {
        return $this->body;
    }

    public function obtenerDestinatarios()
    {
        return $this->to;
    }

    public function open($url)
    {
        $img = "<img src='{$url}' alt='' width='1px' height='1px' />";
        $this->body = $this->body . $img;
    }

    public function saveLog($uid_modulo, $uid_elemento, $status)
    {
        $sql = "INSERT INTO ". TABLE_LOG_EMAIL ." (address, subject, body, status, uid_modulo, uid_elemento) VALUES (
                '".implode(" ,", $this->to)."', '".$this->asunto."', '".db::scape($this->body)."', ".$status.", '".$uid_modulo."', '".$uid_elemento."'
            )";

        if (db::get($sql)) {
            return true;
        }

        return false;

    }

    public function getRelated($uid_modulo, $uid_elemento)
    {
        $sql = "SELECT address, subject, body, date FROM ". TABLE_LOG_EMAIL ."

                    WHERE uid_modulo = {$uid_modulo}
                    AND uid_elemento = {$uid_elemento}
                    AND status = ".self::STATUS_OK ."

                ORDER BY uid_log_email DESC limit 1 ";

        if ($resultSet = db::get($sql, true)) {
            return $resultSet;
        }

        return false;
    }

    public function getFrom()
    {
        return @trim(get_cfg_var(self::INI_KEY_FROM));
    }

    public function addReplyTo($address, $name = '')
    {
        return $this->mailer->AddReplyTo($address, $name);
    }

    public function enviar($resend = 1)
    {
        $server = @trim(get_cfg_var(self::INI_KEY_SERVER));
        $from = @trim(get_cfg_var(self::INI_KEY_FROM));
        $username = @trim(get_cfg_var(self::INI_KEY_USERNAME));
        $password = @trim(get_cfg_var(self::INI_KEY_PASSWORD));

        if (!$server || !$from || !$username || !$password) {
            error_log("No email config found on ini files");

            return false;
        }

        $encoding = "UTF-8";
        if (mb_detect_encoding($this->body, $encoding, true) != $encoding) {
            $this->body = utf8_encode($this->body);
        }

        if (mb_detect_encoding($this->asunto, $encoding, true) != $encoding) {
            $this->asunto =  utf8_encode($this->asunto);
        }

        //dump($from, $server, $password, $username);exit;
        $this->mailer->From         = $from;
        $this->mailer->FromName     = "Dokify";
        $this->mailer->Host         = $server;
        $this->mailer->Port         = "465";
        $this->mailer->ContentType  = "text/html";
        $this->mailer->CharSet      = "utf-8";
        $this->mailer->Password     = $password;
        $this->mailer->Username     = $username;
        $this->mailer->Mailer       = "smtp";
        $this->mailer->IsSMTP(true); // SMTP
        $this->mailer->SMTPAuth     = true;
        $this->mailer->SMTPDebug    = 1;
        $this->mailer->Subject      = $this->asunto;
        $this->mailer->Body         = $this->body;

        if (CURRENT_ENV == 'dev') {
            $this->mailer->FromName .= " (dev)";
        }

        //----- CREAMOS EL LOG

        $log = new log();

        $log->info("email", "Asunto: ".$this->asunto, implode(" ,", $this->to));

        foreach ($this->to as $emailEnvio) {
            if (preg_match("/^([ñÑa-zA-Z0-9_\.\-\+])+\@(([ñÑa-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/", $emailEnvio)) {
                $this->mailer->AddAddress($emailEnvio, "");
            }
        }

        ob_start();
        if ($estado = $this->mailer->Send()) {
            $log->resultado("ok", true);
            ob_end_clean();

            return true;
        } else {
            $emailTo = implode(" ,", $this->to);
            $str = ob_get_clean();
            $errorString = substr($str, strpos($str, "-> ERROR:"), strlen($str));
            $errorString = substr($errorString, 3, (strpos($errorString, "SMTP ->")-5));

            // Try again
            if ($resend && $resend < 4) {
                usleep(500000); // half-second
                error_log("[email] Retrying email sending to ($emailTo) -> $resend - $errorString");

                return $this->enviar($resend+1);
            }

            error_log("[email] Cannot send email to ($emailTo) -> $errorString");
            $log->resultado("error $errorString", true);

            return $errorString;
        }
    }
}

// Only for testing purposes
if ($email = trim(get_cfg_var("email.testing"))) {
    email::$developers = array_map("trim", explode(";", $email));
}
