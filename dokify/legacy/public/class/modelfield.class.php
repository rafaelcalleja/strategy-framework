<?php

class modelfield extends elemento implements Ielemento {

    const IN_USE = 1;

    protected static $specials = array(

        'estado_contratacion', 'cadena_contratacion_cumplimentada', 'cadena_contratacion',
        'la_contrata', 'la_subcontrata', 'la_subcontrata_segundo',
        'la_contrata_cif', 'la_subcontrata_cif', 'la_subcontrata_segundo_cif',
        'nombre_empresa_superior', 'nombre_empresa_cliente', 'cif_empresa_superior', 'cif_cliente_final',
        'papelera'
    );

    public function __construct($param, $extra = false) {
        $this->tipo = "modelfield";
        $this->tabla = TABLE_MODELFIELD;
        $this->uid_modulo = 75;

        $this->instance( $param, $extra );
    }


    public static function defaultData($data, Iusuario $usuario = NULL){

        if (isset($data["uid_datamodel"]) && is_numeric($data["uid_datamodel"])) {
            $model = new dataModel($data["uid_datamodel"]);
            $modelFields = $model->obtenerModelFields();
            $data["position"] = count($modelFields);
        }

        return $data;
    }

    public function eliminar(Iusuario $usuario = NULL){

        if (get_class($this) === __CLASS__) {
            $model = $this->getModel();
            $modelFields = $model->obtenerModelFields();
            $position = $this->getPosition();
            if ($position < count($modelFields)) {
                foreach ($modelFields as $modelField) {
                    $positionField = $modelField->getPosition();
                    if ($positionField > $position) {
                        $modelField->update(array("position"=>$positionField-1), elemento::PUBLIFIELDS_MODE_ATTR);
                    }
                }
            }
        }

        return parent::eliminar($usuario);
    }

    public function getColumn(){
        if( ($cache = "getColumn-{$this}") && ($estado = $this->cache->getData($cache)) !== null ){
            return $estado;
        }

        $col = $this->getDataField()->obtenerDato("column");
        if( $param = $this->getParamValue() ){
            if (is_array($param)) {
                $col .= "_". implode("_", $param);
            } else {
                $col .= "_$param";
            }
        }

        $this->cache->addData($cache, $col);
        return $col;
    }

    public function getModel(){
        if( ($cache = "getModel-{$this}") && ($estado = $this->cache->getData($cache)) !== null ){
            return $estado;
        }

        $model = new datamodel($this->obtenerDato('uid_datamodel'));

        $this->cache->addData($cache, $model);
        return $model;
    }

    public function getDataField(){
        $uid = $this->obtenerDato("uid_datafield");
        return new datafield($uid);
    }


    public function getSQL (dataexport $dataExport = null, $transform = false)
    {
        if (($cache = "getSQL-{$this}-$dataExport-$transform") && ($estado = $this->cache->getData($cache)) !== null) {
            return $estado;
        }

        $sqlMarcarCuando = null;
        if ($marcarCuando = utf8_decode(trim($this->obtenerDato('marcar_cuando')))) {
            $sqlMarcarCuando = str_replace("%v", $marcarCuando, "(select if((%s)='%v','X',''))");
        }

        $dataField = $this->getDataField();
        // $dataModel = $this->getModel();
        $name = $dataField->obtenerDato('name');

        $sqlFinal = false;
        if ($dataExport instanceof dataexport) {
            foreach (self::$specials as $specialField) {
                if ($dataExport->isUsing($specialField)) {
                    $fname = "__special_{$specialField}";
                    if ($sqlFinal = self::$fname($name, $this, $dataExport)) {
                        // solo podemos permitir uno por modelfield
                        break;
                    }
                }
            }
        }


        if (!$sqlFinal) {
            $reference = $dataExport instanceof dataexport ? $dataExport->getReferenceCondition() : "1";

            switch ($dataField->obtenerDato('param')) {
                case 'datafield':
                    $dataFieldParam = new datafield($this->getParamValue());
                    $sqlParam = $dataFieldParam->getSQL();
                    $variables = array('%s','%v');
                    $valores = array($sqlParam,$this->obtenerDato('value'));
                    $sql = $dataField->obtenerDato('sql');

                    // apply reference filer if needed
                    $sql = str_replace('<%reference%>', $reference, $sql);

                    $sqlFinal = str_replace($variables, $valores, $sql);
                    //$this->cache->addData($cache, $sqlFinal);
                    //return $sqlFinal;
                    break;

                case 'agrupador_set':
                    $valores = $this->obtenerDato('param[]');
                    $sql = $dataField->obtenerDato('sql');

                    // we should filte the referce if this is the case
                    if ($dataExport->isUsing(dataexport::getHierarchyFields())) {
                        // we need to make sure documento_elemento has no alias
                        $reference = str_replace(
                            'uid_empresa_referencia',
                            'documento_elemento.uid_empresa_referencia',
                            $reference
                        );
                    }

                    // apply reference filer if needed
                    $sql = str_replace('<%reference%>', $reference, $sql);

                    $subsql = array();
                    $sqlFinal = null;
                    switch ($name) {
                        case 'asignado_en_conjunto_de_agrupadores':
                        case 'valido_conjunto_agrupadores':
                        case 'valido_conjunto_agrupadores_solo_asignados':
                        case 'valido_conjunto_agrupadores_seleccionados':
                            foreach ($valores as $valor) {
                                $subsql[] = "((". str_replace('%s', $valor, $sql) .") = 1)";
                            }

                            $sqlFinal = '( IF( '. implode(' AND ', $subsql) .',\'Si\',\'No\') )';

                            break;

                        case 'valido_algun_agrupador':
                            foreach ($valores as $valor) {
                                $subsql[] = "((". str_replace('%s', $valor, $sql) .") = 1)";
                            }

                            $sqlFinal = '( IF( '. implode(' OR ', $subsql) .',\'Si\',\'No\') )';
                            break;

                        case 'mostrar_agrupador_asignado': case 'trabajos': case 'codigo_agrupador_valido':
                            $sqlFinal = str_replace('%s', implode(',', $valores), $sql);
                            break;
                    }

                    //$sqlFinal = $marcarCuando ? str_replace('%s',$sqlFinal,$sqlMarcarCuando) : $sqlFinal;
                    //$this->cache->addData($cache, $sqlFinal);
                    //return $sqlFinal;
                    break;

                default:
                    if ($sql = trim($this->getDataField()->getSQL())) {
                        // we need to make sure documento_elemento has no alias
                        $reference = str_replace(
                            'uid_empresa_referencia',
                            'documento_elemento.uid_empresa_referencia',
                            $reference
                        );

                        // apply reference filer if needed
                        $sql = str_replace('<%reference%>', $reference, $sql);

                        //$UIDmodulo = $this->obtenerDato("uid_modulo");
                        if ($paramValue = $this->getParamValue()) {
                            $sqlFinal = str_replace("%s", $paramValue, $sql);
                        } else {
                            $sqlFinal = $sql;
                        }

                        //$sqlFinal = $marcarCuando ? str_replace('%s',$sql,$sqlMarcarCuando) : $sql;
                        //$this->cache->addData($cache, $sqlFinal);
                        //return $sqlFinal;
                    }
                    break;
            }
        }

        // Si tenemos nuestra SQL todo va bien...
        if (isset($sqlFinal)) {
            if ($transform === true) {
                // Los datos usados en publicFields para el campo "value" del modelfield
                $formField = $this->getDataField()->getValueField();

                // Si el campo tiene una lista de opciones, debemos transformar la salida para que muestre el dato
                if (isset($formField["data"])) {
                    // $reverse = array_map(utf8_decode, array_flip($formField["data"]));

                    $sqlFinal = "CASE $sqlFinal";
                    foreach ($formField["data"] as $when => $then) {
                        $sqlFinal .= " WHEN '{$when}' THEN '". utf8_decode($then) ."' ";
                    }

                    $sqlFinal .= " ELSE '' END";
                }
            }

            if ($marcarCuando) {
                $sqlFinal = str_replace('%s', $sqlFinal, $sqlMarcarCuando);
            } elseif ($this->getDataField()->isBool()) {
                $tpl        = Plantilla::singleton();
                $yes        = utf8_decode($tpl("si"));
                $no         = $tpl("no");
                $sqlFinal   = " IF({$sqlFinal}, '{$yes}', '{$no}') ";
            }

            $this->cache->addData($cache, $sqlFinal);
            return $sqlFinal;
        }

        return false;
    }

    public function getParam(){
        $type = $this->getDataField()->getParam();
        if (!$type) return false;
        // contemplamos la excepción para el tipo de datafield 'agrupador_set-N'
        if (stristr($type,'agrupador')) {
            $type = 'agrupador';
        }

        if( $uid = $this->getParamValue() ){
            if( is_traversable($uid) ){
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

    public function getParamValue(){
        if ( $val = trim($this->obtenerDato("param")) ){
            return $val;
        } elseif (is_traversable($val = $this->obtenerDato('param[]'))) {
            return $val;
        }
        return false;
    }

    public function getParamObject(){
        switch ($param = $this->getDataField()->getParam()) {
            case 'documento_atributo': case 'documento': case 'agrupador': case 'agrupamiento':
                if ($uid = $this->getParamValue()) {
                    return new $param($uid);
                }
            break;
            case 'agrupador_set':
                if ($list = $this->getParamValue()) {
                    $intList = new ArrayIntList($list);
                    return new ArrayAgrupadorList($intList->toObjectList('agrupador'));
                }
            break;
            default:
                return false;
            break;
        }
        return false;
    }

    public function getValueField() {
        $formField = new FormField(array('tag'=>'input', 'data'=> array($this->obtenerDato('value') => 'X')));
    }

    public function getUserVisibleName($truncate=true){
        //$tpl = Plantilla::singleton();
        if ($label = $this->obtenerDato('label')) {
            $name = $label;
        } else {
            $name = $this->getDataField()->getUserVisibleName();


            if ($this->getDataField()->getParam()) {
                if($item = $this->getParamObject()) {
                    if (is_traversable($item)) {
                        $name .= ' ('.implode(', ',$item->getNames()).')';
                    } elseif (is_object($item)) {
                        $name .= ' ('. $item->getUserVisibleName() .')';
                    }
                } else {
                    $name .= " (Sin Seleccionar)";
                }
            }
        }

        if($truncate) $name = string_truncate($name, 180);
        return $name;
    }

    public function getInlineArray($usuarioActivo=false, $mode, $data ){
        $inline = array();
        $tpl = Plantilla::singleton();
        $uid = $this->getParamValue();
        $type = $this->getDataField()->getParam();

        if (stristr($type,'agrupador')) {
            $type = 'agrupador';
        }

        if( $type ){
            if( $uid ){
                $inline[] =  array("img" => RESOURCES_DOMAIN . "/img/famfam/arrow_switch.png");

                // El uid será un conjunto...
                if( is_traversable($uid) ){
                    foreach($uid as $id){
                        $item = new $type($id);
                        $inline[] = array("nombre" => $item->getUserVisibleName());
                    }
                } else {
                    $item = new $type($uid);
                    $inline[] = array("nombre" => $item->getUserVisibleName());
                }

            } else {
                $inline[] =  array(
                    "img" => RESOURCES_DOMAIN . "/img/famfam/arrow_switch.png",
                    "className" => "color red",
                    array( "nombre" => "Seleccionar..", "href" => "configurar/modificar.php?m={$this->tipo}&poid={$this->getUID()}" )
                );
            }
        }

        if ($data["tipo"] == self::IN_USE){

            $model = $this->getModel();
            $order = $this->getPosition();
            $numberFileds = count($model->obtenerModelFields()) - 1;
            if ($order == $numberFileds && $numberFileds > 0) {
                $inline[] =  array(
                        'img' => RESOURCES_DOMAIN.'/img/famfam/arrow_up.png',
                        array('nombre' => $tpl->getString('subir'),
                            'className' => "send-info",
                            'href' => "analytics/campos.php?action=order&dir=up&oid={$model->getUID()}&poid={$this->getUID()}&order=$order")
                );
            } elseif ($order == 0 && $numberFileds > 0) {
                $inline[] = array(
                            'img' => RESOURCES_DOMAIN.'/img/famfam/arrow_down.png',
                            array(
                                'nombre' => $tpl->getString('bajar'),
                                'className' => "send-info",
                                'href' => "analytics/campos.php?action=order&dir=down&oid={$model->getUID()}&poid={$this->getUID()}&order=$order"
                            )
                        );
            } elseif ($numberFileds > 0){
                $inline[] = array(
                            'img' => RESOURCES_DOMAIN.'/img/famfam/arrow_updown.png',
                            array(
                                'nombre' => $tpl->getString('subir'),
                                'className' => "send-info",
                                'href' => "analytics/campos.php?action=order&dir=up&oid={$model->getUID()}&poid={$this->getUID()}&order=$order"
                            ),
                            array(
                                'nombre' => $tpl->getString('bajar'),
                                'className' => "send-info",
                                'href' => "analytics/campos.php?action=order&dir=down&oid={$model->getUID()}&poid={$this->getUID()}&order=$order"
                            )
                        );
            }
        }

        $inlineDatafield = $this->getDataField()->getInlineArray($usuarioActivo,$mode,$data);
        if ($inlineDatafield) {
            $inline = array_merge($inline,$inlineDatafield);
        }

        return $inline;
    }


    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
        $data = array();

        $data["nombre"] =  array(
            "innerHTML" => $this->getUserVisibleName(),
            "href" => "../agd/ficha.php?m={$this->tipo}&poid=". $this->uid,
            "className" => "box-it link"
        );

        return array($this->getUID() => $data);
    }


    public function getPosition(){
        $info = $this->getInfo();
        return $info["position"];
    }
    // public static function optionsFilter($uid, $uidmodulo, $user, $publicMode, $config, $tipo, $parent){
    //  $condiciones = array();
    //
    //  if( $uid && $uidmodulo ){
    //      $item = new self($uid);
    //      if( !$item->getDataField()->getParam() ){
    //          $condiciones[] = " ( uid_accion NOT IN (13) ) ";
    //      }
    //  }
    //
    //  if( count($condiciones) ){
    //      return "AND " . implode(" AND ", $condiciones);
    //  }
    //  return false;
    // }

    // public static function defaultData($data, Iusuario $usuario = null){
        // $parametros = array();
        // foreach ($data as $k => $v) {
        //  if (stristr($k,'mparam')) {
        //      $parametros[] = $data[$k];
        //      unset($data[$k]);
        //  }
        // }
        // $data['param'] = implode(',',$parametros);
        // return $data;
    // }

    // public function updateData($data, Iusuario $usuario = null) {
        // $parametros = array();
        // foreach ($data as $k => $v) {
        //  if (stristr($k,'mparam')) {
        //      $parametros[] = $data[$k];
        //      unset($data[$k]);
        //  }
        // }
        // $data['param'] = implode(',',$parametros);
        // return $data;

    // }



    /* CAMPOS ESPECIALES QUE REQUIEREN DE ADAPTAR Y MODIFICAR LOS CAMPOS */
    protected function __special_cadena_contratacion_cumplimentada($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_cadena_contratacion ($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_la_contrata ($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_la_subcontrata ($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_la_subcontrata_segundo ($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_la_contrata_cif ($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_la_subcontrata_cif ($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_la_subcontrata_segundo_cif ($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_nombre_empresa_superior ($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_nombre_empresa_cliente ($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_cif_empresa_superior ($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_cif_cliente_final ($name){
        return self::__special_estado_contratacion($name);
    }

    protected function __special_estado_contratacion ($name){
        // $first = "if(n4 IS NOT NULL AND n4, n4, if( n3 IS NOT NULL AND n3, n3, if(n2 IS NOT NULL AND n2, n2, jerarquia.uid_empresa) ) )";
        $first = "jerarquia.uid_empresa";
        //$second = "if(n4 IS NOT NULL AND n4, n3, if( n3 IS NOT NULL AND n3, n2, NULL ) )";
        $second = "if(n3 = $first OR (n3 = 0 OR n3 IS NULL), if(n2 = $first, n1, if((n2 = 0 OR n2 IS NULL), NULL, n2) ), n3 )";

        //$third = "if(n4 IS NOT NULL AND n4, n2, if(n1=$second OR n1=$first, NULL, n1) )";
        $third = "if((n4 != 0 AND n4 IS NOT NULL), n2, if(n2 = $first OR (n2 = 0 OR n2 IS NULL) OR (n3 != 0 AND n3 IS NOT NULL), if(n1=$second OR n1 = $first, NULL, n1), n2))";

        switch($name){
            case "mi_empresa":
                return $sqlFinal = "( SELECT nombre FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = n1 LIMIT 1 )";
            break;
            case "la_contrata":
                return $sqlFinal = "( SELECT nombre FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = n2 LIMIT 1 )";
            break;
            case "la_subcontrata":
                return $sqlFinal = "( SELECT nombre FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = n3 LIMIT 1 )";
            break;
            case "la_subcontrata_segundo":
                return $sqlFinal = "( SELECT nombre FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = n4 LIMIT 1 )";
            break;
            case "mi_empresa_cif":
                return $sqlFinal = "( SELECT cif FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = n1 LIMIT 1 )";
            break;
            case "la_contrata_cif":
                return $sqlFinal = "( SELECT cif FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = n2 LIMIT 1 )";
            break;
            case "la_subcontrata_cif":
                return $sqlFinal = "( SELECT cif FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = n3 LIMIT 1 )";
            break;
            case "la_subcontrata_segundo_cif":
                return $sqlFinal = "( SELECT cif FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = n4 LIMIT 1 )";
            break;


            case "nombre_empresa":
                return $sqlFinal = "( SELECT nombre FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = $first LIMIT 1 )";
            break;
            case "nombre_empresa_superior":
                return $sqlFinal = "( SELECT nombre FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = $second LIMIT 1 )";
            break;
            case "nombre_empresa_cliente":
                return $sqlFinal = "( SELECT nombre FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = $third LIMIT 1 )";
            break;
            case "cif_empresa":
                return $sqlFinal = "( SELECT cif FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = $first LIMIT 1 )";
            break;
            case "cif_empresa_superior":
                return $sqlFinal = "( SELECT cif FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = $second LIMIT 1 )";
            break;
            case "cif_cliente_final":
                return $sqlFinal = "( SELECT cif FROM ". TABLE_EMPRESA . " empresa WHERE empresa.uid_empresa = $third LIMIT 1 )";
            break;
        }

        return false;
    }

    protected function __special_papelera($name) {
        if ($name == 'papelera') {
            $tpl = Plantilla::singleton();
            $sqlFinal = " ( SELECT nombre FROM ". TABLE_EMPRESA ." WHERE uid_empresa = empleado_empresa.uid_empresa) as '{$tpl->getString('empresa')}', empleado_empresa.papelera ";
            return $sqlFinal;
        }
        return false;
    }

    public static function isSpecial($name) {
        return in_array($name,self::$specials);
    }


    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $fields = new FieldList;

        switch( $modo ){

            case elemento::PUBLIFIELDS_MODE_EDIT:
                if($usuario instanceof usuario && $objeto instanceof self){
                    $empresaUsuario = $usuario->getCompany();
                    switch ($param = $objeto->getDataField()->getParam()) {
                        case 'agrupamiento':
                            $fields["param"] = new FormField(array("tag" => "select", "data" => $empresaUsuario->obtenerAgrupamientosVisibles(), "search" => true ));
                        break;
                        case 'agrupador':
                            $fields["param"] = new FormField(array("tag" => "select", "data" => $empresaUsuario->obtenerAgrupadoresVisibles('papelera=0'), "search" => true ));
                        break;
                        case 'agrupador_set':
                            $fields['param'] = new FormField();

                            $current = [];
                            if ($param = $objeto->getParam()) {
                                $current = $param->toIntList()->getArrayCopy();
                            }

                            $fields['param[]'] = new FormField(array("tag" => "select", "value" => $current, "data" => $empresaUsuario->obtenerAgrupadoresVisibles('papelera=0') ));
                        break;
                        case 'documento_atributo':
                            $idModulo = util::getModuleId($objeto->getModel()->obtenerModuloDatos());
                            $fields['param'] = new FormField(array("tag" => "select", "data" => $empresaUsuario->getAttributesDocuments(array("uid_modulo_destino=$idModulo", "activo=1"), 'alias', false, true), "search" => true ));
                        break;
                        case 'documento':
                            $idModulo = util::getModuleId($objeto->getModel()->obtenerModuloDatos());
                            $fields['param'] = new FormField(array("tag" => "select", "data" => $empresaUsuario->getVisibleDocuments(array("uid_modulo_destino=$idModulo"), 'alias', true), "search" => true ));
                        break;
                        case 'datafield':
                            // aqui consultar si el datafield parametro es uno de tipo 'estado en agrupador' para poder poner los valores como true o false.
                            $fields['param'] = new FormField(array('tag' => 'select', 'data' => $objeto->getModel()->obtenerAvailableDataFields(), 'search' => true ));
                            $fields['value'] = new FormField(array('tag' => 'input','blank'=>false));
                            $fields['boolvalue'] = new FormField(array('tag' => 'select', 'data' => array('1' =>'SI', '2'=>'NO'), 'search' => false, 'depends' => array('param',31) ));
                        break;
                    }

                    $fields['marcar_cuando'] = new FormField(array('tag'=>'input'));
                    $fields['label'] = new FormField(array('tag'=>'input'));


                }
            break;
            case elemento::PUBLIFIELDS_MODE_ATTR:
                $fields['position'] = new FormField;
                break;
            case elemento::PUBLIFIELDS_MODE_INIT:
            case elemento::PUBLIFIELDS_MODE_NEW:
            default:
                $fields["uid_datamodel"] = new FormField(array("blank"=>false, 'objeto' => 'datamodel'));
                $fields["uid_datafield"] = new FormField(array("blank"=>false, 'objeto' => 'datafield'));
                $fields['position'] = new FormField;
            break;
        }

        return $fields;
    }

    public function getTableFields()
    {
        return array(
            array("Field" => "uid_modelfield",  "Type" => "int(11)",        "Null" => "NO",     "Key" => "PRI", "Default" => "",        "Extra" => "auto_increment"),
            array("Field" => "uid_datamodel",   "Type" => "int(11)",        "Null" => "NO",     "Key" => "MUL", "Default" => "",        "Extra" => ""),
            array("Field" => "uid_datafield",   "Type" => "int(11)",        "Null" => "NO",     "Key" => "MUL", "Default" => "",        "Extra" => ""),
            array("Field" => "param",           "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "value",           "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "marcar_cuando",   "Type" => "varchar(255)",   "Null" => "YES",    "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "label",           "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "position",        "Type" => "int(1)",         "Null" => "YES",    "Key" => "",    "Default" => "",        "Extra" => "")
        );
    }
}
