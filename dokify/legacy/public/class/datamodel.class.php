<?php
    class datamodel extends elemento implements Ielemento {

        public function __construct($param, $extra = false) {
            $this->tipo = "datamodel";
            $this->tabla = TABLE_DATAMODEL;
            $this->uid_modulo = 73;

            $this->instance( $param, $extra );
        }


        public function getUserVisibleName(){
            return $this->obtenerDato("name");
        }

    public function obtenerTabla()
    {
        $uid = $this->obtenerDato("uid_modulo");
        if (!$uid) {
            return false;
        }

        $module = util::getModuleName($uid);
        return constant("TABLE_" . strtoupper($module));
    }

    public function getTable()
    {
        return $this->obtenerTabla();
    }

    public function obtenerModuloDatos()
    {
        $uid = $this->obtenerDato("uid_modulo");
        return util::getModuleName($uid);
    }

    public function getModuleData()
    {
        return $this->obtenerModuloDatos();
    }

        public function obtenerTipo(){
            $tpl = Plantilla::singleton();
            $uid = $this->obtenerDato("uid_modulo");
            return $tpl->getString(util::getModuleName($uid));
        }

        /*
        public function getModelField($item){
            $uid = ( $item instanceof datafield ) ? $item->getUID() : db::scape($item);
            $sql = "SELECT uid_modelfield FROM ". TABLE_MODELFIELD ." WHERE uid_datamodel = {$this->getUID()} AND uid_datafield = {$uid}";
            if( $uid = $this->db->query($sql, 0, 0) ){
                return new modelfield($uid);
            }
            return false;
        }*/

        public function obtenerAvailableDataFields(){
            $sql = "
                SELECT d.uid_datafield FROM ". TABLE_DATAFIELD ." d
                WHERE d.uid_modulo = {$this->obtenerDato("uid_modulo")}
                AND (
                    d.uid_datafield NOT IN (
                        SELECT uid_datafield FROM ". TABLE_MODELFIELD ." WHERE uid_datamodel = {$this->getUID()}
                    )
                    OR d.param != '0'
                )
            ";
            $array = $this->db->query($sql, "*", 0, "datafield");
            return new ArrayObjectList($array);
        }


        public function obtenerUsedDataFields($limit = NULL){
            $sql = "SELECT uid_datafield FROM ". TABLE_DATAFIELD ." INNER JOIN ". TABLE_MODELFIELD ." USING(uid_datafield) WHERE uid_datamodel = {$this->getUID()}";
            if( is_numeric($limit) ) $sql .= " LIMIT 0, $limit";

            $array = $this->db->query($sql, "*", 0, "datafield");
            return new ArrayObjectList($array);
        }


        public function obtenerModelFields($limit = NULL){
            $sql = "SELECT uid_modelfield FROM ". TABLE_MODELFIELD ."
                    INNER JOIN ". TABLE_DATAFIELD . " USING(uid_datafield)
                    WHERE uid_datamodel = {$this->getUID()}
            ";

            // Add limits by filter
            if( is_string($limit) ) $sql .= " AND " . $limit;
            // Group results
            $sql .= " ORDER BY position ";
            // Add limits by number
            if( is_numeric($limit) ) $sql .= " LIMIT 0, $limit";

            $array = $this->db->query($sql, "*", 0, "modelfield");
            return new ArrayObjectList($array);
        }

        public function getModelFields($limit = null)
        {
            return $this->obtenerModelFields($limit);
        }

        public function getInlineArray($usuarioActivo=false, $mode, $data ){
            $inline = array();
            $tpl = Plantilla::singleton();
            $modelfields = $this->obtenerModelFields(8);

            if (!$this->isOK($usuarioActivo)) {
                $inline[] =  array(
                    "img" => RESOURCES_DOMAIN . "/img/famfam/flag_red.png",
                    array( "nombre" => $tpl->getString('elemento_modelo_papelera') )
                );
            }

            if( $modelfields && count($modelfields) ){
                if( count($modelfields) > 5 ){
                    $modelfields = $modelfields->slice(5);
                    $add = " ...";
                } else {
                    $add = "";
                }


                $inline[] =  array(
                    "img" => RESOURCES_DOMAIN . "/img/famfam/application_form_magnify.png",
                    array(
                        "nombre" => implode(", ", $modelfields->getNames()) . $add,
                        "href" => "#analytics/campos.php?poid=" . $this->getUID()
                    )
                );
            }

            if ($this->isPublic()) {
                $inline[] =  array(
                    "img" => RESOURCES_DOMAIN . "/img/famfam/world_go.png",
                    array("nombre" => $tpl('publico'))
                );
            }


            return $inline;
        }

        public function isOK() {
            foreach ($this->obtenerModelFields() as $modelfield) {
                $pv = $modelfield->getParam();

                if( $pv instanceof agrupador && $pv->inTrash() ){
                    return false;
                }

                if( $pv instanceof documento_atributo && $pv->obtenerDato('activo')==0 ){
                    return false;
                }
            }

            return true;
        }

        public function isUsing($name) {
            $list = is_array($name) ? $name : array($name);
            $list = array_map(function ($a) { return "'$a'"; }, $list);

            $sql = "
                SELECT count(*) FROM ". TABLE_MODELFIELD . "
                INNER JOIN ". TABLE_DATAFIELD . " USING(uid_datafield)
                WHERE uid_datamodel = {$this->getUID()} AND name IN (". implode(',', $list) .")
            ";

            $inColumns = (bool) $this->db->query($sql, 0, 0);
            if( $inColumns ) return true;
        }

        public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()) {
            $info = parent::getInfo(true, $usuario);
            $data = array();


            $data["nombre"] =  array(
                "innerHTML" => $this->getUserVisibleName(),
                "href" => "../agd/ficha.php?m=datamodel&poid=". $this->uid,
                "className" => "box-it link"
            );

            $data["modulo"] = $this->obtenerTipo();


            return array($this->getUID() => $data);
        }


        public static function getAvailableTypes(){
            return array('1' => 'empresa', '8' => 'empleado', '14' => 'maquina');
        }

        public static function defaultData($data, Iusuario $usuario = null){
            if( $usuario instanceof Iusuario ){
                $data["uid_usuario"] = $usuario->getUID();
            }

            return $data;
        }

        static public function optionsFilter($uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){

            $condicion = array();

            if( isset($uidelemento) && $user instanceof usuario ){
                $sql = "SELECT uid_datamodel FROM " .TABLE_DATAMODEL. " WHERE uid_usuario = {$user->getUID()} AND uid_datamodel = {$uidelemento} ";
                $datamodelPropertyUser = db::get($sql, "*", 0, "datamodel");
                if (!count($datamodelPropertyUser)){
                    $condicion[] = "uid_accion NOT IN (4, 14, 34)";
                }

            }
            if( count($condicion) ){
                return " AND ". implode(" AND ", $condicion);
            }

            return false;
        }

        public function isPublic(){
            return (bool) $this->obtenerDato("is_public");
        }

        public static function getDataModels(Iusuario $usuario = NULL){
            $sql = "SELECT uid_datamodel FROM " .TABLE_DATAMODEL. " WHERE uid_usuario = {$usuario->getUID()}
                 UNION
                    SELECT uid_datamodel FROM " .TABLE_DATAMODEL. " WHERE is_public = 1 AND uid_usuario IN (SELECT uid_usuario FROM ". TABLE_PERFIL ." WHERE uid_empresa = {$usuario->getCompany()->getUID()} AND papelera = 0)
                ";

            $datamodels = db::get($sql, "*", 0, "datamodel");
            return new ArrayObjectList($datamodels);
        }

        public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
            $fields = new FieldList;

            switch( $modo ){
                case elemento::PUBLIFIELDS_MODE_INIT:
                case elemento::PUBLIFIELDS_MODE_NEW:
                case elemento::PUBLIFIELDS_MODE_EDIT:
                default:
                    $fields["name"]         = new FormField(array("tag" => "input", "type" => "text", "blank" => false ));

                    if( $modo != elemento::PUBLIFIELDS_MODE_EDIT ){
                        $fields["uid_modulo"]   = new FormField(array("tag" => "select", "data" => self::getAvailableTypes(), "blank" => false ));
                    }

                    if( $modo == elemento::PUBLIFIELDS_MODE_NEW && $usuario instanceof usuario ){
                        $fields["uid_usuario"] = new FormField;
                    }

                    $fields["is_public"] = new FormField(array('tag' => 'input', 'type' => 'checkbox', "className" => "iphone-checkbox", "info" => true));
                break;
            }

            return $fields;
        }

    }
?>
