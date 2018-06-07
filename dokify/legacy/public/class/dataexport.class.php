<?php
    class dataexport extends elemento implements Ielemento {

        public function __construct($param, $extra = false) {
            $this->tipo = "dataexport";
            $this->tabla = TABLE_DATAEXPORT;
            $this->uid_modulo = 76;
            $this->instance( $param, $extra );
        }


        public function getUserVisibleName(){
            return $this->obtenerDato("name");
        }

        public function getDataModel(){
            $uid = $this->obtenerDato("uid_datamodel");
            return new datamodel($uid);
        }

        public function getReferenceCondition ()
        {
            $lastInChain    = db::getLastFromSet('uid_empresa_referencia');
            $reference      = "1";

            if ($this->isUsing(self::getHierarchyFields())) {
                $chain = db::implode(['n1', 'n2', 'n3', 'n4']);

                $reference = "CASE
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_COMPANY ." THEN (
                uid_empresa_referencia = jerarquia.uid_empresa
                )
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_CHAIN ." THEN (
                uid_empresa_referencia = {$chain}
                )
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_CONTRACTS ." THEN (
                1
                ) ELSE (
                uid_empresa_referencia = 0
                )
                END";
            }

            return $reference;
        }


        /**
         * [getHierarchyFields get the dataField names wich cause a join to contract chain]
         * @return [arra] [the array with the names]
         */
        public static function getHierarchyFields ()
        {
            return array(
                'estado_contratacion', 'cadena_contratacion_cumplimentada', 'cadena_contratacion',
                'la_contrata', 'la_subcontrata', 'la_subcontrata_segundo',
                'la_contrata_cif', 'la_subcontrata_cif', 'la_subcontrata_segundo_cif',
                'nombre_empresa_superior', 'nombre_empresa_cliente', 'cif_empresa_superior', 'cif_cliente_final'
            );
        }

        public function getJoin(){
            if( ($cacheString = "dataexport-{$this}") && ($estado = $this->cache->getData($cacheString)) !== null ){
                return $estado;
            }

            $join = false;

            $hierarchyJoinFields = self::getHierarchyFields();

            $model  = $this->getDataModel();
            $module = $model->obtenerModuloDatos();
            switch($module){
                case "empleado": case "maquina":
                    $chainTable = '_jerarquia';
                    if (true === $this->isUsing('estado_contratacion')) {
                        $chainTable.='_visibilidad';
                    }

                    $chainJoin = $this->isUsing($hierarchyJoinFields);
                    $table = constant('TABLE_' . strtoupper($module));
                    if ($chainJoin) {
                        $join = "INNER JOIN {$table}{$chainTable} as jerarquia
                        ON n1 IN (<%startlist%>)
                        AND {$module}.uid_{$module} = jerarquia.uid_{$module}";
                    }

                    if ($this->isUsing('papelera')) {
                        $join = " INNER JOIN (
                            SELECT uid_empresa, uid_{$module}, papelera FROM {$table}_empresa
                        ) as {$module}_empresa ON {$module}.uid_{$module} = {$module}_empresa.uid_{$module} ";
                    }
                break;
                case "empresa":
                    $chainJoin = $this->isUsing($hierarchyJoinFields);

                    if ($chainJoin) {
                        $join = "INNER JOIN ". TABLE_EMPRESA ."_jerarquia as jerarquia
                        ON n1 IN (<%startlist%>)
                        AND empresa.uid_empresa = jerarquia.uid_empresa";
                    }
                break;
            }


            $this->cache->addData($cacheString, $join);
            return $join;
        }


        public function isUsing($name){
            if ($this->getDataModel()->isUsing($name)) {
                return true;
            }

            $list = is_array($name) ? $name : array($name);
            $list = array_map(function ($a) { return "'$a'"; }, $list);

            $sql = "
                SELECT count(*) FROM ". TABLE_DATACRITERION . " dc
                INNER JOIN ". TABLE_DATAFIELD . " USING(uid_datafield)
                WHERE dc.uid_modulo = {$this->getModuleId()} AND dc.uid_elemento = {$this->getUID()}
                AND name IN (". implode(',', $list) .")
            ";

            return (bool) $this->db->query($sql, 0, 0);
        }


        public function obtenerAvailableDataFields(){
            $model = $this->getDataModel();
            $sql = "
                SELECT d.uid_datafield FROM ". TABLE_DATAFIELD ." d
                WHERE d.uid_modulo = {$model->obtenerDato("uid_modulo")}
                AND (
                    d.uid_datafield NOT IN (
                        SELECT uid_datafield FROM ". TABLE_DATACRITERION ." WHERE uid_modulo = {$this->getModuleId()} AND uid_elemento = {$this->getUID()}
                    )
                    OR d.param != '0'
                    OR d.multiple_criterion = '1'
                )
            ";
            $array = $this->db->query($sql, "*", 0, "datafield");
            return new ArrayObjectList($array);
        }

    public function obtenerDataCriterions($limit = null)
    {
        // añadido el ORDER BY para establecer OR en los criterios multiples
        $sql = "SELECT uid_datacriterion FROM ". TABLE_DATACRITERION ." WHERE uid_modulo = {$this->getModuleId()}
            AND uid_elemento = {$this->getUID()}
            ORDER BY uid_datafield";
        if (is_numeric($limit)) {
            $sql .= " LIMIT 0, $limit";
        }

        $array = $this->db->query($sql, "*", 0, "datacriterion");
        return new ArrayObjectList($array);
    }

    public function getDataCriterions($limit = null)
    {
        return $this->obtenerDataCriterions($limit);
    }

    public function dataCriterionsAreOk()
    {
        foreach ($this->getDataCriterions() as $dataCriterion) {
            if (false != $dataCriterion->getDataField()->getParam() && false == $dataCriterion->getParamObject()) {
                return false;
            }
        }

        return true;
    }

    public function obtenerExportHeaders($limit = null)
    {
        $sql = "SELECT uid_exportheader FROM ". TABLE_EXPORTHEADER ." WHERE uid_dataexport = {$this->getUID()}";
        if (is_numeric($limit)) {
            $sql .= " LIMIT 0, $limit";
        }

        $array = $this->db->query($sql, "*", 0, "exportheader");
        return new ArrayObjectList($array);
    }

    public function getExportHeaders($limit = null)
    {
        return $this->obtenerExportHeaders($limit);
    }

        public function getInlineArray(Iusuario $usuario = NULL, $config = false, $data = NULL){
            $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;
            $comefrom = isset($data[Ilistable::DATA_COMEFROM]) ? $data[Ilistable::DATA_COMEFROM] : false;


            $tpl = Plantilla::singleton();
            $inline = array();

            if (!$this->getDataModel()->isOK()) {
                $inline[] =  array(
                    "img" => RESOURCES_DOMAIN . "/img/famfam/flag_red.png",
                    array( "nombre" =>  $tpl('elemento_informe_papelera') )
                );
            }

            $inline[] = array(
                "title" => $tpl->getString("descargar"),
                "img" => RESOURCES_DOMAIN . "/img/famfam/drive_web.png",
                array( "nombre" => $tpl("descargar") . ' Excel', "href" => "analytics/descargar.php?poid={$this->getUID()}", "target" => "async-frame" )
            );

            $inline[] = array(
                "title" => $tpl->getString("descargar"),
                "img" => RESOURCES_DOMAIN . "/img/famfam/page_white_text_width.png",
                array( "nombre" => $tpl("descargar") . ' CSV', "href" => "analytics/descargar.php?poid={$this->getUID()}&filetype=csv", "target" => "async-frame" )
            );

            // Si pedimos el getinline array desde el home, solo nos interesa los icono de descarga
            if( $comefrom == "home" ) return $inline;

            $criterions = $this->obtenerDataCriterions(8);
            $info = implode(", ", $criterions->getNames());
            if( $criterions && count($criterions) ){
                $inline[] =  array(
                    "title" => $tpl->getString("filtros"),
                    "img" => RESOURCES_DOMAIN . "/img/famfam/application_form_magnify.png",
                    array(
                        "nombre" => string_truncate($info, 60),
                        "title" => $info,
                        "href" => "#analytics/criterios.php?m=dataexport&poid=" . $this->getUID()
                    )
                );
            }

            return $inline;
        }

        public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = NULL, $extra = array(), $force = false){
            $data = parent::getInfo($publicMode,  $comeFrom , $usuario);
            if ($comeFrom === 'ficha'){
                $datamodel = new datamodel($data[$this->getUID()]["uid_datamodel"]);
                $data[$this->getUID()]["uid_datamodel"] = $datamodel->getUserVisibleName();
            }
            return $data;
        }


        public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
            $info = parent::getInfo(true, $usuario);
            $data = array();


            $data["nombre"] =  array(
                "innerHTML" => $this->getUserVisibleName(),
                "href" => "../agd/ficha.php?m=dataexport&poid=". $this->uid,
                "className" => "box-it link"
            );

            //$data["modulo"] = $this->obtenerTipo();

            return array($this->getUID() => $data);
        }


        /**
          * Nos indica si este informe es público o no
          *
          * @return bool
          */
        public function isPublic(){
            return (bool) $this->obtenerDato("is_public");
        }

        /**
          * Recuperar el objeto usuario que creó el informe
          *
          * @return usuario
          */
        public function getUser(){
            return new usuario($this->obtenerDato("uid_usuario"));
        }


        public static function defaultData($data, Iusuario $usuario = null){
            if( $usuario instanceof Iusuario ){
                $data["uid_usuario"] = $usuario->getUID();
            }

            return $data;
        }

        public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
            $fields = new FieldList;

            switch( $modo ){
                case elemento::PUBLIFIELDS_MODE_INIT:
                case elemento::PUBLIFIELDS_MODE_NEW:
                case elemento::PUBLIFIELDS_MODE_EDIT:
                default:
                    $fields["name"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false ));

                    if( $usuario instanceof usuario ){
                        $fields["uid_datamodel"]    = new FormField(array("tag" => "select", "data" => $usuario->obtenerDataModels(), "blank" => false ));

                        if( $modo == elemento::PUBLIFIELDS_MODE_NEW ){
                            $fields["uid_usuario"] = new FormField;
                        }
                    }


                    $fields["is_public"] = new FormField(array('tag' => 'input', 'type' => 'checkbox', "className" => "iphone-checkbox", "info" => true));
                    $fields["show_titles"] = new FormField(array('tag' => 'input', 'type' => 'checkbox', "className" => "iphone-checkbox", "info" => true));
                break;
            }

            return $fields;
        }

        public function getTableFields(){
            return array (
                array ("Field" => "uid_dataexport", "Type" => "int(11)",        "Null" => "NO",     "Key" => "PRI", "Default" => "",        "Extra" => "auto_increment"),
                array ("Field" => "uid_datamodel",  "Type" => "int(11)",        "Null" => "NO",     "Key" => "MUL", "Default" => "",        "Extra" => ""),
                array ("Field" => "uid_usuario",    "Type" => "int(11)",        "Null" => "NO",     "Key" => "MUL", "Default" => "",        "Extra" => ""),
                array ("Field" => "name",           "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
                array ("Field" => "show_titles",    "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "1",       "Extra" => ""),
                array ("Field" => "is_public",      "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => "")
            );
        }
    }
?>
