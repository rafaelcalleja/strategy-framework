<?php

class datacriterion extends modelfield implements Ielemento
{

    public function __construct ($param, $extra = false)
    {
        $this->tipo = "datacriterion";
        $this->tabla = TABLE_DATACRITERION;
        $this->uid_modulo = 77;
        $this->instance($param, $extra);
    }

    public function getSQLFilter()
    {
        $dataField = $this->getDataField();
        $name = $dataField->obtenerDato('name');
        $comparador = ($comparator = $this->obtenerDato("comparator")) ? $comparator : "=";
        $value = $this->obtenerDato("value");
        $param = $this->getParam();
        $sql = false;

        $item = $this->getItem();
        if ($item instanceof dataexport) {
            foreach (self::$specials as $specialField) {
                if ($item->isUsing($specialField)) {
                    $fname = "__special_{$specialField}";
                    if ($sql = self::$fname($name)) {
                        // solo podemos permitir uno por modelfield
                        break;
                    }
                }
            }
        }

        if (!$sql) {
            $sql = $dataField->getSQL($param);
        }

        if ($item instanceof dataexport) {
            $reference = $item->getReferenceCondition();

            // we need to make sure documento_elemento has no alias
            $reference = str_replace(
                'uid_empresa_referencia',
                'documento_elemento.uid_empresa_referencia',
                $reference
            );

            $sql = str_replace('<%reference%>', $reference, $sql);
        }

        if ($comparador == "LIKE%%") {
            $filter = "$sql LIKE '%{$value}%'";
        } else {
            if (empty($value)) {
                $uniq = 'var_' . uniqid();
                $filter = "( (@$uniq := $sql {$comparador} '{$value}') OR ( @$uniq IS NULL ) )";
            } else {
                $filter = "$sql {$comparador} '{$value}'";
            }
        }

        return $filter;
    }

    /** **/
    public function getItem()
    {
        $uidmodulo = $this->obtenerDato("uid_modulo");
        $uid = $this->obtenerDato("uid_elemento");

        $modulo = util::getModuleName($uidmodulo);

        return new $modulo($uid);
    }


    public function getParam()
    {
        $uid    = $this->getParamValue();
        $type   = $this->getDataField()->getParam();

        if (stristr($type, '_set')) {
            $type = str_replace("_set", "", $type);
        }

        if ($uid && $type) {
            if (is_traversable($uid)) {
                $list = new ArrayObjectList();
                foreach ($uid as $u) {
                    $list[] = new $type($u);
                }
                return $list;
            } else {
                return new $type($uid);
            }
        }
        return false;
    }

    public function getParamValue()
    {
        if ($val = trim($this->obtenerDato("param"))) {
            return $val;
        } elseif (is_traversable($val = $this->obtenerDato('param[]'))) {
            return $val;
        }

        return false;
    }


    public function getLineClass()
    {
        $class = array("color");

        if ($this->obtenerDato("comparator")) {
            $class[] = "green";
        } else {
            $class[] = "red";
        }

        return implode(" ", $class);
    }

    /**
     * [getInlineArray description]
     * @param  boolean $usuarioActivo [description]
     * @param  [type]  $mode          [description]
     * @param  [type]  $data          [description]
     * @return [type]                 [description]
     *
     */
    public function getInlineArray ($usuarioActivo, $mode, $data)
    {
        $inline = array();


        $datafield = $this->getDataField();
        if ($datafield instanceof datafield && $comparator = $this->obtenerDato("comparator")) {
            $value = $this->obtenerDato("value");


            $inline[] = array(
                "img" => RESOURCES_DOMAIN . "/img/famfam/application_form_magnify.png",
                array(
                    "nombre" => "{$this->getUserVisibleName()} {$comparator} {$value}"
                )
            );
        } else {
            $inline[] = array(
                "img" => RESOURCES_DOMAIN . "/img/famfam/application_form_magnify.png",
                array(
                    "nombre" => "Configurar",
                    "href" => "configurar/modificar.php?m={$this->getModuleName()}&poid={$this->getUID()}",
                    "className" => "box-it"
                )
            );
        }

        return $inline;
    }

    public function getModel()
    {
        if (($cache = "getModel-{$this}") && ($estado = $this->cache->getData($cache)) !== null) {
            return $estado;
        }

        $model = $this->getItem()->getDataModel();

        $this->cache->addData($cache, $model);
        return $model;
    }

    public function getTableInfo(Iusuario $usuario = null, Ielemento $parent = null, $extraData = [])
    {
        $data = [];

        $data["nombre"] = [
            "innerHTML" => $this->getUserVisibleName(),
            "href" => "../agd/configurar/modificar.php?m={$this->tipo}&poid=". $this->uid,
            "className" => "box-it link",
        ];

        return [$this->getUID() => $data];
    }

    /*
    public static function defaultData($data, Iusuario $usuario = null){
        return $data;
    }
    */

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $fields = new FieldList;

        switch ($modo) {
            case elemento::PUBLIFIELDS_MODE_INIT:
            case elemento::PUBLIFIELDS_MODE_NEW:
            default:

                if ($usuario instanceof usuario) {

                    if ($modo == elemento::PUBLIFIELDS_MODE_NEW) {
                        $fields["uid_datafield"] = new FormField;
                        //$fields["uid_modelfield"] = new FormField;
                        $fields["uid_elemento"] = new FormField;
                        $fields["uid_modulo"] = new FormField;
                    }
                }
                break;

            case elemento::PUBLIFIELDS_MODE_EDIT:
                if ($usuario instanceof usuario && $objeto instanceof self) {
                    $fields = parent::publicFields($modo, $objeto, $usuario, $tab);
                    unset($fields['marcar_cuando']);
                    unset($fields['label']);
                }

                if ($objeto instanceof self) {
                    $fields["comparator"] = new FormField($objeto->getDataField()->getComparatorField());
                    $fields["value"] = new FormField($objeto->getDataField()->getValueField());
                }
                break;
        }


        return $fields;
    }

    public function getTableFields()
    {
        return array(
            array("Field" => "uid_datacriterion",   "Type" => "int(11)",        "Null" => "NO",     "Key" => "PRI",     "Default" => "",    "Extra" => "auto_increment"),
            array("Field" => "uid_elemento",        "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",    "Extra" => ""),
            array("Field" => "uid_modulo",          "Type" => "int(11)",        "Null" => "NO",     "Key" => "MUL",     "Default" => "",    "Extra" => ""),
            array("Field" => "uid_datafield",       "Type" => "int(11)",        "Null" => "NO",     "Key" => "MUL",     "Default" => "",    "Extra" => ""),
            array("Field" => "param",               "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",    "Extra" => ""),
            array("Field" => "comparator",          "Type" => "varchar(10)",    "Null" => "NO",     "Key" => "",        "Default" => "",    "Extra" => ""),
            array("Field" => "value",               "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",    "Extra" => "")
        );
    }
}
