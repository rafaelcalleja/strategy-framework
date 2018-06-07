<?php

class categoria extends elemento implements Ielemento
{
    const TYPE_CLIENTES = 1;
    const TYPE_PROYECTOS = 2;
    const TYPE_TAREAS = 3;
    const TYPE_PRODUCTOS = 4;
    const TYPE_TIPOEMPRESA = 5;
    const TYPE_GRUPODERIESGO = 6;
    const TYPE_PUESTO = 7;
    const TYPE_TIPOMAQUINARIA = 8;
    const TYPE_INTRANET = 9;
    const TYPE_OBRAS = 10;

    public static $automaticAssignOrganization = array(
        self::TYPE_TIPOEMPRESA,
        self::TYPE_GRUPODERIESGO,
        self::TYPE_PUESTO,
        self::TYPE_TIPOMAQUINARIA
    );

    public function __construct($param, $extra = false)
    {
        $this->tipo = $this->nombre_tabla = "categoria";
        $this->tabla = TABLE_CATEGORIA;
        $this->instance($param, $extra);
    }


    public function getUserVisibleName()
    {
        $tpl = Plantilla::singleton();
        return $tpl->getString($this->obtenerDato("nombre"));
    }

    public static function getAll()
    {
        $sql = "SELECT uid_categoria FROM ". TABLE_CATEGORIA ." WHERE 1";
        $categorias = db::get($sql, "*", 0, "categoria");
        return new ArrayObjectList($categorias);
    }

    // simplemente para tener la lista de las categorias que solicitan EPIs en un solo lugar
    public static function solicitanEpis()
    {
        return array(categoria::TYPE_PUESTO,categoria::TYPE_GRUPODERIESGO);
    }


    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $fieldList = new FieldList;
        return $fieldList;
    }

    public function getTableFields()
    {
        return array (
            array ("Field" => "uid_categoria",  "Type" => "int(11)",        "Null" => "NO",     "Key" => "PRI", "Default" => "",    "Extra" => "auto_increment"),
            array ("Field" => "nombre",         "Type" => "varchar(100)",   "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => ""),
            array ("Field" => "descripcion",    "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => ""),
        );
    }
}
