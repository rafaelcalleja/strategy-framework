<?php

class SearchNotificationStatus extends elemento implements Ielemento
{
    const STATUS_SENDING    = 1;
    const STATUS_SEND       = 2;
    const STATUS_OPENED     = 3;

    public function __construct($param, $extra = false)
    {
        $this->tipo = "searchnotificationstatus";
        $this->tabla = TABLE_BUSQUEDA_USUARIO . "_notification_status";
        $this->instance($param, $extra);
    }


    public function getTableInfo(Iusuario $usuario = null, Ielemento $parent = null, $extraData = array())
    {
        $data = array();

        $mail = $this->obtenerDato("receipt");

        $data["receipt"] = array(
            "tagName"   => "a",
            "innerHTML" => $mail,
            "href"      => "mailto:{$mail}"
        );

        return array($this->getUID() => $data);
    }


    public function getCompany()
    {
        return new empresa($this->obtenerDato('uid_empresa'));
    }

    public function getItem()
    {
        $module = util::getModuleName($this->obtenerDato('uid_modulo'));
        $uid = $this->obtenerDato('uid_elemento');

        $item = new $module($uid);

        if ($item->exists()) {
            return $item;
        }
        return null;
    }

    public function getUpdatedTimestamp($offset = 0)
    {
        return strtotime($this->obtenerDato('updated')) - (3600 * $offset);
    }

    public function getInlineArray(Iusuario $usuario = null, $config = false, $data = null)
    {
        $tpl = Plantilla::singleton();
        $inline = array();

        $object = array("img" => RESOURCES_DOMAIN . "/img/famfam/user_suit.png");
        if ($item = $this->getItem()) {
            if ($item instanceof empleado) {
                $object[] = array('nombre' => $item->getUserVisibleName(), "href" => $item->obtenerUrlFicha());
            } else {
                $object[] = array('nombre' => $item->getUserVisibleName(), "tagName" => "span");
            }

        } else {
            $object[] = array('nombre' => "N/A", "tagName" => "span");
        }

        $inline[] = $object;

        $empresa = $this->getCompany();

        $company = array("img" => RESOURCES_DOMAIN . "/img/famfam/sitemap_color.png");
        $company[] = array(
            'nombre' => $empresa->getUserVisibleName(),
            'href'  => $empresa->obtenerUrlFicha()
        );

        $inline[] = $company;




        $statusCode = $this->obtenerDato('status');
        switch ($statusCode) {
            case self::STATUS_SENDING:
                $icon = "clock_play";
                break;

            case self::STATUS_SEND:
                $icon = "email_go";
                break;

            case self::STATUS_OPENED:
                $icon = "eye";
                break;
        }

        $statusString = self::status2string($statusCode);
        $statusString .= " (" . date('d-m-Y H:i', $this->getUpdatedTimestamp($usuario->getTimezoneOffset())) . ")";

        $status = array("img" => RESOURCES_DOMAIN . "/img/famfam/{$icon}.png");
        $status[] = array(
            'tagName'   => 'span',
            'nombre'    => $statusString
        );

        $inline[] = $status;

        return $inline;
    }



    public function getUserVisibleName()
    {
        return $this->getUID();
    }


    public function getReceipt()
    {
        return $this->obtenerDato('receipt');
    }

    public function getSearchNotification()
    {
        return new SearchNotification($this->obtenerDato('uid_usuario_busqueda_notification'));
    }

    public function send($receipt = false, $force = false)
    {
        $notification = $this->getSearchNotification();
        $company = $notification->getCompany();
        $user = $notification->getUser();
        $plantilla = new Plantilla();

        if (!$receipt) {
            $receipt = $this->getReceipt();
            $token = urlencode(base64_encode("email={$receipt}&uid={$this->getUID()}&timestamp=".time()));
            $openURL = CURRENT_DOMAIN . "/email/opened/search.php?email={$receipt}&token={$token}";
        }


        $subject = $notification->obtenerDato('subject');
        $comment = $notification->obtenerDato('comment');
        $logo = trim($logo = $company->obtenerLogo()) ? $logo : RESOURCES_DOMAIN . '/img/dokify-google-logo.png';

        if ($force) {
            $receipt = email::$developers;
        }

        // --- parsear comentarios
        $comment = util::URLToLink($comment);

        // --- asignar variables
        $plantilla->assign('subject', $subject);
        $plantilla->assign('comment', $comment);
        $plantilla->assign('logo', $logo);


        $html = $plantilla->getHTML('email/searchnotification.tpl');

        $email = new email($receipt);
        $email->establecerAsunto($subject);
        $email->establecerContenido($html);
        if (isset($openURL)) {
            $email->open($openURL);
        }
        $email->mailer->AddReplyTo($user->getEmail(), $user->getHumanName());

        if (($estado = $email->enviar()) !== true) {
            throw new Exception($estado);
        }

        return $this->setStatus(self::STATUS_SEND);
    }

    public function setStatus($status)
    {
        $sql = "UPDATE {$this->tabla} SET status = {$status} WHERE uid_usuario_busqueda_notification_status = {$this->getUID()}";
        return $this->db->query($sql);
    }

    public static function status2string($status)
    {
        $tpl = Plantilla::singleton();

        switch ($status) {
            case self::STATUS_SENDING:
                return $tpl('enviando') . "...";
                break;
            case self::STATUS_SEND:
                return $tpl('enviado');
                break;
            case self::STATUS_OPENED:
                return $tpl('abierto');
                break;
            default:
                # code...
                break;
        }
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $fields = new FieldList;

        $fields["uid_usuario_busqueda_notification"] = new FormField;
        $fields["receipt"] = new FormField;

        $fields["uid_elemento"] = new FormField;
        $fields["uid_modulo"] = new FormField;
        $fields["uid_empresa"] = new FormField;

        $fields["status"] = new FormField;
        //$fields["updated"] = new FormField;

        return $fields;
    }
}
