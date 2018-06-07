<?php

class message extends elemento implements Ielemento
{
    public function __construct($param, $extra = false)
    {
        $this->tipo = "message";
        $this->tabla = TABLE_MESSAGE;
        $this->instance($param, $extra);
    }

    public static function getRouteName()
    {
        return 'message';
    }

    public function toArray($app = null)
    {
        $company = $this->getCompany();
        $routeName = self::getRouteName();
        $uid = (int) $this->getUID();
        $html = $this->getHTML();

        $companyData = [
            'name' => $company->getUserVisibleName(),
        ];

        $route = [
            'name' => $routeName,
            'params' => [$routeName => $uid],
        ];

        $data = [
            'company' => $companyData,
            'html' => $html,
            'route' => $route,
            'uid' => $uid,
        ];

        return $data;
    }

    public function getUserVisibleName()
    {
        $info = $this->getInfo();
        return $info["titulo"];
    }

    public function getTableInfo(Iusuario $usuario = null, Ielemento $parent = null, $extraData = array())
    {
        $data = [];
        $data["titulo"] = $this->getUserVisibleName();

        return [$data];
    }

    public function getHTML()
    {
        $info = $this->getInfo();
        return $info["message_es"];
    }

    public function getHref()
    {
        $info = $this->getInfo();
        return $info["action_href"];
    }

    public function getCompany()
    {
        $info = $this->getInfo();
        return new empresa($info["uid_empresa"]);
    }

    public function getUser()
    {
        $info = $this->getInfo();
        return new empresa($info["uid_usuario"]);
    }

    public function getTitle()
    {
        $info = $this->getInfo();
        return $info["titulo"];
    }

    public function getAction()
    {
        $tpl = Plantilla::singleton();
        $info = $this->getInfo();
        $action = $info["action"];

        if (!$action) {
            $action = "entendido_no_volver_mostrar";
        }

        return "<img src='". RESOURCES_DOMAIN ."/img/famfam/tick.png' /> " . $tpl->getString($action);
    }

    public function actualizarTitulo($titulo)
    {
        $sql = "UPDATE $this->tabla SET titulo = '". db::scape($titulo) ."' WHERE uid_message = ". $this->getUID();
        return $this->db->query($sql);
    }

    public function actualizarTexto($contenido)
    {
        $sql = "UPDATE $this->tabla SET message_es = '". db::scape($contenido) ."' WHERE uid_message = ". $this->getUID();
        return $this->db->query($sql);
    }

    public static function defaultData($data, Iusuario $usuario = null)
    {
        if (isset($data["poid"]) && !isset($data["uid_empresa"])) {
            $data["uid_empresa"] = $data["poid"];
        }

        if ($usuario instanceof usuario) {
            $data["uid_usuario"] = $usuario->getUID();
        }

        $now = new \DateTime('now');
        $data['createdAt'] = $now->format('Y-m-d H:i:s');

        return $data;
    }

    public function obtenerMessagesLog($condicion = false, Iusuario $usuario = null, $latest = false)
    {
        $sql = "SELECT uid_usuario_message  FROM ". TABLE_USUARIO ."_message WHERE uid_message = {$this->getUID()} ORDER BY fecha DESC";
        $coleccion = $this->db->query($sql, "*", 0, "usuarioMessage");
        if ($coleccion) {
            return new ArrayObjectList($coleccion);
        }
        return new ArrayObjectList();
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $fields = new FieldList;

        switch ($modo) {
            case elemento::PUBLIFIELDS_MODE_INIT:
            case elemento::PUBLIFIELDS_MODE_NEW:
            case elemento::PUBLIFIELDS_MODE_EDIT:
                $fields['titulo'] = new FormField([
                    'tag' => 'input',
                    'type' => 'text',
                    'blank' => false,
                ]);
                $fields['visible_usuarios'] = new FormField([
                    'tag' => 'input',
                    'type' => 'checkbox',
                    'className' => 'iphone-checkbox',
                ]);
                $fields['visible_usuarios_contratas'] = new FormField([
                    'tag' => 'input',
                    'type' => 'checkbox',
                    'className' => 'iphone-checkbox',
                ]);

                if ($modo === elemento::PUBLIFIELDS_MODE_NEW) {
                    $fields['uid_empresa'] = new FormField([
                        'tag' => 'input',
                        'type' => 'text',
                        'blank' => false,
                    ]);
                    $fields['uid_usuario'] = new FormField([
                        'tag' => 'input',
                        'type' => 'text',
                        'blank' => false,
                    ]);
                    $fields['createdAt'] = new FormField([
                        'tag' => 'input',
                        'type' => 'text',
                        'blank' => true,
                    ]);
                }
                break;
        }
        return $fields;
    }
}
