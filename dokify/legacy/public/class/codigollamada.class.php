<?php

use Dokify\Controller\User;

//clase codigollamada

class codigollamada
{
    protected $uid;
    protected $code;
    protected $user;

    public function __construct($uid, $code, usuario $user)
    {
        $this->uid  = $uid;
        $this->code = $code;
        $this->user = $user;
    }

    public static function instanceFromCode($code)
    {
        $code   = db::scape($code);
        $codes  = TABLE_CODIGOLLAMADA;
        $sql    = "SELECT uid_codigollamada, codigo, uid_usuario FROM {$codes} WHERE codigo = '{$code}'";
        $row    = db::get($sql, 0, '*');

        if ($row && count($row)) {
            $uid        = $row['uid_codigollamada'];
            $code       = $row['codigo'];
            $userUid    = $row['uid_usuario'];

            return new codigollamada($uid, $code, new usuario($userUid));
        }
    }

    public static function existe($code)
    {
        $sql = "SELECT uid_usuario FROM " . TABLE_CODIGOLLAMADA . " WHERE codigo = " . $code;
        return (bool)db::get($sql, 0, 0);
    }

    public static function register($usuario, $code)
    {
        $sql = "INSERT INTO " . TABLE_CODIGOLLAMADA . " (`uid_usuario`, `codigo`) VALUES ({$usuario->getUID()}, $code)";
        return (bool)db::get($sql);
    }

    public static function genera()
    {
        return rand(100000, 999999);
    }

    public static function obtenerUsuario($code)
    {
        $sql = "SELECT uid_usuario FROM " . TABLE_CODIGOLLAMADA . " WHERE codigo = " . $code;
        $idu = db::get($sql, 0, 0);
        $miusuario = new usuario($idu);
        return $miusuario;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $fields = new FieldList;
        $fields["intro_cod_llamada"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));

        return $fields;
    }

    public function toArray($app = null)
    {
        $callCodeArray = [];

        $callCodeArray['uid']   = $this->uid;
        $callCodeArray['code']  = $this->code;
        $callCodeArray['user']  = $this->user->toArray($app);

        return $callCodeArray;
    }

    public function getCompany()
    {
        return $this->user->getCompany();
    }

    public function getUser()
    {
        return $this->user;
    }
}
