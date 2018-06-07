<?php

class provincia extends elemento implements Ielemento
{
    public static $provinciasSinIVA =  array(35,38); //las palmas, santa cruz de tenerife

    public function __construct($param, $extra = false)
    {
        $this->tabla = TABLE_PROVINCIA;
        $this->nombre_tabla = "provincia";
        parent::instance($param);
    }

    public function getUserVisibleName()
    {
        return $this->obtenerDato("nombre");
    }


    public static function getFromName($name)
    {
        $cache = cache::singleton();
        $name = strtolower($name);
        if (($cacheString = "provincia-getFromName-{$name}") && ($estado = $cache->getData($cacheString)) !== null) {
            return $estado;
        }

        $db = db::singleton();
        $sql = "SELECT uid_provincia FROM ". TABLE_PROVINCIA ." WHERE nombre LIKE '%". db::scape($name) ."%'";
        $item = false;
        if ($uid = $db->query($sql, 0, 0)) {
            $item = new self($uid);
        }

        $cache->addData($cacheString, $item);
        return $item;
    }

    public static function obtenerTodos()
    {
        $db = db::singleton();
        $sql = "SELECT uid_provincia FROM ". TABLE_PROVINCIA ." WHERE 1 ORDER BY nombre";
        $provincias = $db->query($sql, "*", 0, "provincia");

        return new ArrayObjectStates($provincias);
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $fieldList = new FieldList;
        $fieldList["nombre"] = new FormField(array("tag" => "input",   "type" => "text", "blank" => false));
        return $fieldList;
    }

    public function getTableFields()
    {
        return array(
            array("Field" => "uid_provincia", "Type" => "int(11)", "Null" => "NO", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"),
            array("Field" => "uid_pais", "Type" => "int(11)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""),
            array("Field" => "codigo", "Type" => "varchar(10)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""),
            array("Field" => "nombre", "Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => "")
        );
    }
}
