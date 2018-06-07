<?php
class util
{
    const PRIVATE_KEY_ENCRYPT = "er!3m039%$2·21dd%9";

    public static function doPost($URL, $data)
    {
        $URLInfo = parse_url($URL);
        // extract host and path:
        $host = $URLInfo['host'];
        $path = $URLInfo['path'];

        $post = http_build_query($data);

        // Cabeceras de la peticion
        $header  = "POST $path HTTP/1.1\r\n";
        $header .= "Host: $host\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Connection: close\r\n";
        $header .= "Content-Length: " . strlen($post) . "\r\n\r\n";

        $fp = fsockopen($host, 80, $errno, $errstr, 30);
        if ($fp) {
            fputs($fp, $header.$post);
            $response = "";
            while (!feof($fp)) {
                $response .= fgets($fp, 1024);
            }

            $response = explode("\r\n\r\n", $response, 2);

            $header = isset($response[0]) ? $response[0] : "";
            $content = isset($response[1]) ? $response[1] : "";

            return (object) array("header" => $header, "content" => $content);
        }
    }

    /***
       *
       * Busca URLs y las reemplaza por <a>
       *
       *
       *
       */
    public static function URLToLink($text)
    {
        $regexp = "/<a[^>]+href\s*=\s*[\"']([^\"']+)[\"'][^>]*>(.*?)<\/a>/mis";
        $regexp = "/((https?):((\/\/)|(\\\\))+[\w\d:#@%\/;$()~_?\+-=\\\.&]*)/";
        $offset = 0;

        while (preg_match($regexp, $text, $match, PREG_OFFSET_CAPTURE, $offset)) {
            list ($link, $offset) = $match[0];

            $uri = parse_url($link);
            $name = rawurldecode(basename($uri['path']));

            $html = "<a href='{$link}'>{$name}</a>";
            $text = substr_replace($text, $html, $offset, strlen($link));

            // move to next link
            $offset += strlen($html);
        }

        return $text;

    }

    public static function encrypt($str)
    {
        return trim(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::PRIVATE_KEY_ENCRYPT, $str, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }

    public static function decrypt($str)
    {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::PRIVATE_KEY_ENCRYPT, $str, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }

    public static function comparar($ob1, $ob2)
    {
        if (!is_object($ob1) || !is_object($ob2)) {
            return false;
        }

        if ($ob1->getType() === $ob2->getType() && $ob1->getUID() === $ob2->getUID()) {
            return true;
        }

        return false;
    }

    public static function cadenaValida($string)
    {
        $blocklist = array("del","los","las","por");
        $len = strlen($string);
        if ($len < 3 || in_array($string, $blocklist)) {
            // si la palabra es de 3 letras, no sirve para nada..
            return false;
        }

        $string = archivo::cleanFilenameString($string); // reutilizamos la funcion
        $blockList = array("documento");

        if (in_array(strtolower($string), $blockList)) {
            // si esta en nuestra lista, no la usamos
            return false;
        }

        return true;
    }

    public static function datetime2human($d)
    {
        if ($fecha = DateTime::createFromFormat ( 'Y-m-d H:i:s' , $d )) {
            return $fecha->format('d/m/Y');
        }

        return false;
    }

    public static function getAllModules($flip=false)
    {
        $array = array(
            "1" => "empresa",
            "2" => "usuario",
            "3" => "empresa_documento",
            "0" => "nucleo",
            "5" => "documento_atributo",
            "6" => "plugin",
            "7" => "etiqueta",
            "8" => "empleado",
            "9" => "empleado_documento",
            "10" => "plantilla",
            "11" => "agrupador",
            "12" => "agrupamiento",
            "13" => "home",
            "14" => "maquina",
            "15" => "maquina_documento",
            "16" => "perfil",
            "20" => "configurar",
            "18" => "documento",
            "19" => "tipodocumento",
            "22" => "contactoempresa",
            "23" => "campo",
            "24" => "cliente",
            "25" => "carpeta",
            "26" => "fichero",
            "27" => "estructura",
            "28" => "rol",
            "29" => "sistema",
            "30" => "buscador",
            "31" => "inicio",
            "32" => "llamada",
            "33" => "alarma",
            "34" => "eventdate",
            "35" => "certificacion",
            "37" => "comentario_anulacion",
            "41" => "anexo_empresa",
            "42" => "anexo_historico_empresa",
            "51" => "anexo_empleado",
            "52" => "anexo_historico_empleado",
            "60" => "anexo_historico_maquina",
            "61" => "anexo_maquina",
            "62" => "tipo_epi",
            "63" => "epi",
            "64" => "itemtype",
            "65" => "categoria",
            "66" => "baja",
            "67" => "convocatoriamedica",
            "68" => "citamedica",
            "69" => "centrocotizacion",
            "70" => "accidente",
            "71" => "adjunto",
            "72" => "noticia",
            "73" => "datamodel",
            "74" => "datafield",
            "75" => "modelfield",
            "76" => "dataexport",
            "77" => "datacriterion",
            "78" => "dataimport",
            "79" => "importaction",
            '81' => "solicituddocumento",
            '82' => 'mis_archivos',
            '83' => 'solicitud_epi',
            '84' => 'exportacion_masiva',
            '91' => 'validacion',
            '92' => 'signinRequest',
            '93' => 'empresaPartner',
            '94' => 'validation',
            '95' => 'validationStatus',
            '96' => 'invoice',
            '97' => 'invoiceItem',
            '98' => 'exportheader',
            '99' => 'headercolumn',
            '100' => 'calendar',
            '101' => 'message',
            '102' => 'usuarioMessage',
            '103' => 'searchnotification',
            '104' => 'searchnotificationstatus',
            '105' => 'maps',
            '106' => 'paypalLicense',
            '107' => 'pais',
            '108' => 'provincia',
            '109' => 'municipio',
            '110' => 'referrer'
        );

        return $flip ? array_flip($array) : $array;
    }
    /***
        RETORNAR LOS IDS DE LOS MODULOS SIN PASAR POR LAS BBDD
    */
    public static function nombreModulo($uid)
    {
        $array = self::getAllModules();

        return ( isset($array[$uid]) ) ? $array[$uid] : false;
    }

    public static function getModuleName($uid)
    {
        $array = self::getAllModules();

        return ( isset($array[$uid]) ) ? $array[$uid] : false;
    }

    public static function getModuleId($name)
    {
        $array = self::getAllModules(true);

        return (isset($array[$name])) ? $array[$name] : false;
    }

    public static function getDateFormat($time, Iusuario $user = null)
    {
        $template = new Plantilla();
        $month = array("", $template("enero"), $template("febrero"), $template("marzo"), $template("abril"), $template("mayo"), $template("junio"), $template("julio"), $template("agosto"), $template("septiembre"), $template("octubre"),  $template("noviembre"), $template("diciembre"));

        return date("j", $time) . " "  . $template("de") .  " " . $month[date("n",$time)] . " "  . $template("de") .  " " . date("Y",$time);

    }

    public static function base64_email_encode($string)
    {
        return strtr(base64_encode($string), '=/+', '-._');
    }

    public static function base64_email_decode($string)
    {
        return base64_decode(strtr($string, '-._', '=/+'));
    }

    public static function secsToHuman($secs, $seconds = false)
    {
        $tpl = Plantilla::singleton();
        $now = new DateTime();
        $given = new DateTime();

        $now->setTimestamp(0);
        $given->setTimestamp($secs);
        $interval = $now->diff($given);

        $time = array(
            'dia' => $interval->d,
            'hora' => $interval->h,
            'minuto' => $interval->i
        );

        if ($seconds) {
            $time['segundo'] = $interval->s;
        }

        $humanTime = "";
        foreach ($time as $prop => $val) {
            if ($val) {
                $str = ($val > 1) ? "{$prop}s" : "{$prop}";
                $str = strtolower($tpl($str));
                $notEmpty = ($humanTime != "");
                if ($notEmpty && $prop == "minuto") {
                    $humanTime .= " {$tpl("preposition_y")} ";
                } elseif ($notEmpty && $prop != "dia") {
                    $humanTime .= ', ';
                }

                $humanTime .= "$val $str";
            }
        }

        return $humanTime;
    }

    public static function secsToDateTime($seconds)
    {
        if ($seconds == 0) {
            return false;
        }

        $initTime    = new DateTime("@0");
        $secondsTime = new DateTime("@$seconds");

        return $initTime->diff($secondsTime);
    }

    public static function getCoordsFromAddress($address, $attempts = 0)
    {
        $url = "http://maps.google.com/maps/api/geocode/json?address=". urlencode($address) ."&sensor=false";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_PROXYPORT, 3128);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);

        if (!isset($response->results[0])) {
            if ($attempts < 2) {
                $attempts += 1;

                return self::getCoordsFromAddress($address, $attempts);
            } else {
                return false;
            }
        }

        return (object) array(
            'latitude' => $response->results[0]->geometry->location->lat,
            'longitude' => $response->results[0]->geometry->location->lng
        );
    }
}
