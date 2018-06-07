<?php

class comment extends elemento implements Ielemento, Icomment
{
    const NO_ACTION = 0;
    const ACTION_ATTACH = 1;
    const ACTION_VALIDATE = 2;
    const ACTION_DELETE = 3;
    const ACTION_REJECT = 4;
    const ACTION_EMAIL = 5;
    const ACTION_CHANGE_DATE = 6;
    const ACTION_EXPIRE = 7;
    const ACTION_SIGN = 8;

    public function __construct($uid, $item = false)
    {
        if ($item instanceof solicitable) {
            $this->tipo = "comentario_". strtolower($item->getType());
            $this->tabla = constant("PREFIJO_COMENTARIOS") . strtolower($item->getType());
        } elseif (is_string($item)) {
            $this->tipo = "comentario_". $item;
            $this->tabla = constant("PREFIJO_COMENTARIOS") . $item;
        }

        $this->instance($uid);
    }

    public static function getRouteName()
    {
        return 'comment';
    }

    public function getViewData(Iusuario $user = null)
    {
        $viewData   = [];
        $commenter  = $this->getCommenter();
        $commentId  = $this->getCommentId();

        $viewData['text']       = $this->getUserVisibleName();
        $viewData['commenter']  = $commenter == null ? 'dokify' : $commenter->getName();
        $viewData['action']     = $commentId->getActionInfo();

        return $viewData;
    }

    public function getUserVisibleName()
    {
        return $this->getComment();
    }

    public function getComment()
    {
        $info = $this->getInfo();
        return $info["comment"];
    }

    public function getCommenterModule()
    {
        $info = $this->getInfo();
        return $info["uid_module_commenter"];
    }

    public function getReply()
    {
        if ($replyId = $this->obtenerDato('replyId')) {
            list($comentario, $type) = explode('_', $this->tipo);
            return new commentId($replyId, $type);
        }

        return false;
    }

    public function getReplyUser()
    {
        if ($replyCommentId = $this->getReply()) {
            return $replyCommentId->getCommenter();
        }

        return false;
    }

    public function getCommenter()
    {
        $info = $this->getInfo();
        $commenterId = $info["uid_commenter"];
        if (is_numeric($commenterId) && $commenterId != 0) {
            $module = util::getModuleName($this->getCommenterModule());
            return new $module($commenterId);
        }

        return false;
    }

    public function getElement()
    {
        $modulo = end((explode("_", $this->tabla)));

        if ($uid = $this->obtenerDato("uid_{$modulo}")) {
            $item = new $modulo($uid);
            if ($item->exists()) {
                return $item;
            }
        }

        return false;
    }

    public function getAttribute()
    {
        $info = $this->getInfo();
        $uid = $info["uid_documento_atributo"];
        if (is_numeric($uid)) {
            return new documento_atributo($uid);
        }

        return false;
    }

    public function isDeleted()
    {
        return (bool) $this->obtenerDato('deleted');
    }

    public function getAction()
    {
        $info = $this->getInfo();
        return $info["action"];
    }

    public function getDate($offset = 0)
    {
        $primary = db::getPrimaryKey($this->tabla);
        $sql = "SELECT UNIX_TIMESTAMP(date) FROM {$this->tabla} WHERE {$primary} = {$this->getUID()}";
        if ($timestamp = $this->db->query($sql, 0, 0)) {
            return $timestamp - (3600 * $offset); // adjuts timezone offset
        }

        return 0;
    }

    public function getCommentId()
    {
        list ($comentario, $type) = explode('_', $this->tipo);
        $commentId = $this->obtenerDato('commentId');
        return new commentId($commentId, $type);
    }


    public function getAgrupadorReferencia()
    {
        $info = $this->getInfo();
        $agrupadorId = $info["uid_agrupador"];
        if (is_numeric($agrupadorId) && $agrupadorId != 0) {
            return new agrupador($agrupadorId);
        }
        return false;
    }

    public function getEmpresaReferencia()
    {
        $info = $this->getInfo();
        if ($uids =  $info["uid_empresa_referencia"]) {
            if (is_numeric($uids)) {
                return new empresa($uids);
            } else {
                $list = new ArrayIntList(explode(",", $uids));
                return $list->toObjectList("empresa");
            }
        }
        return false;
    }

    public function getModuleName($uid = false)
    {
        return $this->tipo;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $arrayCampos = new FieldList();

        return $arrayCampos;
    }
}
