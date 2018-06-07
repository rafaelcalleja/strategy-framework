<?php

abstract class elemento extends basic {
    protected $db;
    protected $uid;
    protected $tabla;
    protected $nombre_tabla;
    protected $tipo;
    protected $campos;
    protected $uid_modulo;
    public $error;

    // Constantes...
    const EVENT_CLICK = 1;


    const PUBLIFIELDS_MODE_INIT = "init";
    const PUBLIFIELDS_MODE_NEW = "nuevo";
    const PUBLIFIELDS_MODE_EDIT = "edit";
    const PUBLIFIELDS_MODE_TABLEDATA = "table";
    const PUBLIFIELDS_MODE_TRASH = "papelera";
    const PUBLIFIELDS_MODE_TAB = "ficha";
    const PUBLIFIELDS_MODE_CONFIG = "config";
    const PUBLIFIELDS_MODE_SEARCH = "buscador";
    const PUBLIFIELDS_MODE_IMPORT = "import";
    const PUBLIFIELDS_MODE_QUERY = "query";
    const PUBLIFIELDS_MODE_FOLDER = "folder";
    const PUBLIFIELDS_MODE_PREFS = "prefs";
    const PUBLIFIELDS_MODE_ATTR = "attr";
    const PUBLIFIELDS_MODE_DELTA = 'delta';
    const PUBLIFIELDS_MODE_TRIGGER = 'trigger'; // para los aftercreate o afterupdate
    const PUBLIFIELDS_MODE_ENDEVE = 'endeve';
    const PUBLIFIELDS_MODE_CRONCALL = 'croncall';
    const PUBLIFIELDS_MODE_VISIBILITY = 'visibility';
    const PUBLIFIELDS_MODE_SYSTEM = 'system';
    const PUBLIFIELDS_MODE_PROGRESS = 'progress';
    const PUBLIFIELDS_MODE_REFERENCIAR = 'referenciar';
    const PUBLIFIELDS_MODE_GEO = 'geo';
    const PUBLIFIELDS_MODE_MASSIVE = 'massive';
    const PUBLIFIELDS_MODE_LOGUI = 'logui';
    const PUBLIFIELDS_MODE_SIGNINREQUEST = 'signinrequest';


    protected function instance( $param, $extra = false ){
        $this->error = false;
        $param = parent::instance( $param, $extra );

        if( is_numeric($param) ){
            $this->uid = db::scape($param);
        } else {
            $class = get_class($this);

            if( is_traversable($param) ){
                $this->insert( $class::defaultData($param, (($extra instanceof Iusuario)?$extra:null)), $extra );
            } else {
                if( CURRENT_ENV == "prod" ) return false;
                throw new Exception("Imposible instanciar o crear el objeto {$class}");
            }
        }
    }


    /***
       * Aux function to convert to Array any instance
       *
       *
       */
    public function toArray ($app = null) {
        if (method_exists($this, 'getRouteName')) {
            $routeName  = $this->getRouteName();
        } else {
            $routeName = get_class($this);
        }

        $routeName = ucfirst($routeName);

        $class = "\Dokify\Controller\\" . $routeName;
        $data  = $class::toArray($this, $app);

        return $data;
    }


    //CREAR UN NUEVO ELEMENTO, A PARTIR DE LOS CAMPOS PUBLICOS QUE SON LOS UNICOS QUE SE PUEDEN DAR
    protected function insert( $data, $usuario = null ){
        $trigger = new trigger( $this->tabla, $usuario );
        $func = $this->tipo.'::publicFields';
        if( !is_callable($this->tipo.'::publicFields') ){ die("No se puede llamar a public fields de ". $this->tipo); }
        //-------------- AQUI TENEMOS LOS CAMPOS DE ESTE TIPO DE ELEMENTOS
        $fields = call_user_func($func, "nuevo", null, $usuario);

        if( $fields instanceof ArrayObject ) { $fields = $fields->getArrayCopy(); }

        //-------------- ARRAY DONDE ALMACENAREMOS LOS DATOS
        $values = $extras = $multiples = $foreigns = array();

        //-------------- RECORREMOS LOS CAMPOS DE NUESTRA TABLA
        foreach( $fields as $field => $val ){
            $value = "";
            if( strstr($field,"[]") ){
                $fname = str_replace("[]","",$field);
                if (isset($data[$fname])) {
                    $multiples[$fname] = $data[$fname];
                }
                unset($fields[$field]);
                continue;
            }

            if( isset($data[ $field ]) ){
                $value = db::scape( trim($data[$field]) );
            }
            if( !isset($value) ){die("campo_".$field."_no_blanco en $func"); }

            //---------- SI NO ESTA PERMITIDO QUE ESTE EN BLANCO LO AVISAMOS
            if( isset($val["blank"]) && $val["blank"] === false && isset($value) && !strlen(trim($value)) ){ return $this->error = "campo_".$field."_no_blanco"; }

            //por ejemplo en el caso de los cif de empresa, valida este campo
            if(isset($val["match"])){

                if (is_callable($val["match"])) {
                    if(!call_user_func($val["match"],$value)){
                        return $this->error = "campo_".$field."_no_valido";;
                    }
                } elseif (is_string($val["match"])) {
                    if (preg_match("/". $val["match"] ."/", $value) === 0) {
                        return $this->error = "campo_".$field."_no_valido";
                    }
                }
            }

            //si es un campo extra, lo guardamos en una tabla diferente
            if( isset($val["uid_campo"]) && $val["uid_campo"] ){
                unset($fields[$field]);
                $extras[$val["uid_campo"]] = utf8_decode( $value );
            } elseif( isset($val["foreign"]) ){ // de momento no los tratamos
                //$foreigns[$field] = utf8_decode( $value );
                unset($fields[$field]);
            } else {
                $value = strcasecmp($value, 'NULL') === 0 ? $value : "'". utf8_decode( $value ) ."'";
                $values[] = $value;
            }
        }

        //-------------- SI NO HAY DATOS, ES UN ERROR
        if( count($values) != count($fields) ){ return false; }
        if( !count($values) || !count($fields) ){ return false; }

        $resultInsert = array();
        $resultInsert = $values;

        if( !function_exists("mysqlFieldPrepare") ){ function mysqlFieldPrepare($string){ return "`".$string."`"; } }
        $fieldNames = array_map("mysqlFieldPrepare", array_keys($fields));

        // un array de valores sin saber lo que contienen no sirve de mucho
        $trigger->beforeCreate(array_combine($fieldNames,$values));
        $sql = "INSERT INTO $this->tabla (". implode(",", $fieldNames ) .") VALUES (". implode(",", $values) .")";
        $rs = $this->db->query( $sql );
        //el id insertado
        $idElementoInsertado = $this->uid = $this->db->getLastId();

        if(!$rs){ $this->error = $this->db->lastErrorString(); $this->errorText=$this->db->lastError(); /*"error_crear_$this->tipo";*/ }

        if( $this instanceof agrupamiento && ( !isset($data["nombre"]) || !trim($data["nombre"]) ) ){
            // No deberiamos estar aqui..
            error_log( "Se ha creado un nuevo agrupamiento [{$this->getUID()}] pero esta vacio");
            return false;
        }


        if( count($extras) ){
            foreach($extras as $key => $value){
                $campoExtra = new campo($key);
                if( !$campoExtra->setValue($this, $value) ){

                }
            }
        }


        if( $multiples && count($multiples) ){
            foreach($multiples as $campo => $values ){
                foreach( $values as $i => $value ){
                    $sql = "INSERT INTO $this->tabla"."_$campo ( uid_".$this->getType().", $campo ) VALUES ( $idElementoInsertado, '". db::scape($value) ."' )";
                    $this->db->query( $sql );
                }
            }
        }

        if( $resultInsert ){
            $class = get_class($this);
            $trigger->afterCreate( new $class($idElementoInsertado) );
            if ($usuario instanceof Iusuario && $usuario->getAppVersion() != 2) {
                $this->writeLogUI( logui::ACTION_CREATE, "", $usuario);
            }
        }


        return $idElementoInsertado;
    }

    /** FORMAR UN ARRAY CON LOS DATOS MAS COMUNES USADOS PARA CREAR GRAFICAS */
    public static function getDefaultCharData($yaxis, $xaxis){
        $yaxis = array_unique($yaxis);
        $xaxis = array_unique($xaxis);

        $output = array();

        $output["series"] = array();
        $output["data"] = array();

        $output["xaxis"] = array(
            "label" => "Dias",
            "ticks" => array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31),
            "tickOptions" => array(
                "formatString" => "%d"
            ),
            "max" => max($xaxis),
            "min" => min($xaxis)
        );

        $output["yaxis"] = array(
            "show" => true,
            "tickOptions" => array(
                "formatString" => "%d"
            ),

            "max" => max($yaxis) + 20,
            "min" => "0"
        );

        return $output;
    }

    public static function getFromWhere($field, $value, $tipo, $condicion = NULL){
        $db = db::singleton();
        $tabla = constant("TABLE_". strtoupper($tipo));
        $sql = "SELECT uid_$tipo FROM $tabla WHERE $field = '". db::scape($value). "'";
        if( $condicion ){ $sql .= " AND $condicion"; }

        $uids = $db->query($sql, "*", 0);
        if( !is_array($uids) || count($uids) == 0 ) return false;

        $coleccion = new ArrayObjectList();
        foreach( $uids as $uid ){
            $coleccion[] = new $tipo($uid);
        }

        return $coleccion;
    }


    public static function getTotalCount(){
        $db = db::singleton();
        $tipo = get_called_class();
        $tabla = constant("TABLE_". strtoupper($tipo));
        $sql = "SELECT count(uid_$tipo) FROM $tabla WHERE 1";
        return (int) $db->query($sql, 0, 0);
    }

    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
        return $this->getInfo(true, elemento::PUBLIFIELDS_MODE_TABLEDATA, $usuario, $extraData);
    }

    public function getHTMLDocumentName($corto=false){
        $tpl = Plantilla::singleton();
        $html = "";
        if( $this instanceof agrupador ){
            if( !$corto ) $html .= implode(", ", $this->getCompanies()->getNames()) . " &raquo; ";
            if( !$corto ) $html .= $this->getNombreTipo();
            if($this->referencia){
                if( !$corto ) $html .=  " &raquo; ";
                $html .= $this->referencia->getUserVisibleName();
            }
            if($this->empresa){
                if( !$corto ) $html .=  " &raquo; ";
                $html .= $this->empresa->getUserVisibleName();
                $html .=  " &raquo; ";
            }
            if( !$corto ) $html .= " &raquo; ";
        } else {
            $html .=  "<span class='ucase'>". $tpl->getString($this->getType()) ."</span>";
            $html .= " &raquo; ";
        }


        $html .= $this->getUserVisibleName();
        return $html;
    }

    public function getType(){
        return $this->tipo;
    }

    public function getViewData (Iusuario $user = NULL) {
        $class = get_called_class();
        $route = $class::getRouteName();

        $viewData = array();
        $viewData['uid']    = (int) $this->getUID();
        $viewData['uuid']   = $route . '-' . $this->getUID();
        $viewData['route']  = array(
            'name'      => $route,
            'params'    => array($route => $this->getUID())
        );


        $pieces = explode('-', $route);

        $viewData['name']   = $this->getUserVisibleName();
        $viewData['type']   = end($pieces);
        $viewData['checkbox'] = true;

        $options = $this->getAvailableOptions($user, true);
        foreach ($options as $i => $op) {
            $options[$i] = array(
                'innerHTML' => $op['innerHTML'],
                'img'       => $op['img'],
            );
        }

        $viewData['options'] = $options;

        // var_dump($options);exit;
        return $viewData;
    }

    /** DEVOLVER EL NOMBRE ABREVIADO **/
    public function getUserVisibleNameAbbr(){
        if (method_exists($this, "getAbbr")) {
            if( ($abbr = $this->getAbbr()) && trim($abbr) ){
                return $abbr;
            }
        }
        return $this->getUserVisibleName();
    }


    /**
        ACTUALIZA CON LOS DATOS ENVIADOS POR LA PLANTILLA ASIGNAR LOS DATOS
        DE UNA TABLA RELACIONAL
            @param $tabla -> tabla a actualizar
            @param $tipo -> tipo de elemento relacionado [maquina, cliente]
            @param $inverso -> Se relacionaran los que no estan asignados en vez de lo usual
            @param $data -> Si queremos sobreescribir el $_REQUEST
    */
    public function actualizarTablaRelacional($tabla, $tipo, $inverso=false, $data = null){

        $keyAsignados = ( $inverso ) ? "elementos-disponibles" : "elementos-asignados";
        $data = ( is_traversable($data) ) ? $data : @$_REQUEST[$keyAsignados];

        $campo = "uid_".strtolower($this->getType());
        $camporelacion = "uid_$tipo";
        $currentUIDElemento = $this->getUID();//obtener_uid_seleccionado();

        $sql = "DELETE FROM $tabla WHERE $campo = ".$currentUIDElemento;
        if( $estado = $this->db->query( $sql ) ){
            if( empty($data) ){
                if( $estado ){ return true; } else { return $this->db->lastErrorString(); }
            }

            $idItems = array_map( "db::scape", $data);

            $inserts = array();

            foreach( $idItems as $idItem ){
                $inserts[] = "( $currentUIDElemento, $idItem )";
            }
            $sql = "INSERT INTO $tabla ( $campo, $camporelacion ) VALUES ". implode(",", $inserts);
            $estado = $this->db->query( $sql );
            if( $estado ){ return true; } else { return $this->db->lastErrorString(); }
        } else {
            return $this->db->lastErrorString();
        }

    }


    public function getAvailableOptions(Iusuario $user = NULL, $publicMode = false, $config = 0, $groups=true, $ref=false, $extraData = null){
        //FILTRAMOS LOS ELEMENTOS DE MENU QUE NO DEBEN APARECER, POR JEARARQUIA
        //SI NO TIENES ACCESO A VER EL MENU DE EMPLEADOS, TAMPOCO DEBES VER LA OPCION
        if( !$ref ){
            $ref = ( isset($this->referencia) ) ? $this->referencia : false;
        }
        return config::obtenerOpciones( $this->uid, $this->getModuleId(), $user, $publicMode, $config, 1, $groups, $ref);
    }


    public function getLogUIEntries($count = false, $offset = 0, $limit = 100, $since = null, Iusuario $usuario = NULL)
    {
        $commonWhere = TABLE_LOGUI ." WHERE uid_modulo = {$this->getModuleId()} AND uid_elemento = {$this->getUID()} ";

        if ($since) {
            $commonWhere .= " AND UNIX_TIMESTAMP(fecha) > {$since}";
        }

        if ($usuario) {
            $company = $usuario->getCompany();
            $commonWhere .= " AND (uid_empresa IS NULL OR uid_empresa = {$company->getUID()})";
        }

        if ($count) {
            $sql = "SELECT count(uid_logui) FROM {$commonWhere}";
            return $this->db->query($sql, 0, 0);
        }

        $sql = "SELECT uid_logui FROM $commonWhere  ORDER BY fecha DESC LIMIT $offset, $limit";
        $list = $this->db->query($sql, "*", 0, "logui");
        return new ArrayObjectList($list);
    }


    /* FUNCIONES PROTEGIDAS */


    /* EXPLICACION
     *
     *  tabla -> la tabla relacional
     *  campo -> el campo a actualizar
     *  valor -> el nuevo valor del campo
     * campo -> el campo de la tabla donde se encuentra nuestro uid
     * campo -> el campo condicional donde buscaremos el id dado
     * valor -> el valor que tiene que tener el campo condicional
     */
    protected function actualizarRelacion( $table, $campoactualizar, $valornuevo, $campoactual, $campobuscado, $valorbuscado){
        $sql = "UPDATE $table SET $campoactualizar = $valornuevo WHERE $campoactual = $this->uid AND $campobuscado = $valorbuscado";
        if( $this->db->query( $sql ) ){
            if( $this->db->getAffectedRows() ){
                return true;
            }
        }
        return false;
    }

    protected function eliminarRelacion( $table, $campobuscado, $valorbuscado, $campoactual ){
        $sql = "DELETE FROM $table WHERE $campoactual = $this->uid AND $campobuscado = $valorbuscado";
        if( $this->db->query( $sql ) ){
            if( $this->db->getAffectedRows() ){
                return true;
            }
        }
        return false;
    }




    /*
     * OBTENER LOS ELEMENTOS DE UNA TABLA RELACIONAL, indicamos la tabla (empleado_empresa), el campo en el que
     * se encuentra el uid actual ( id_empleado ) y el campo de los uid buscados ( id_empresa )
     * [ ejemplo para ver las empresas a las que pertenece un empleado ]
     *
     */
    protected function obtenerRelacionados($tabla, $actual, $buscado, $condicion = false, $order = false, $returnSQL = false)
    {
        $tblKey = $tabla;
        if (strpos($tabla, '.') !== false) {
            $tableExploded = new ArrayObject(explode('.', $tabla));
            $tblKey = end($tableExploded);
        }

        if (strpos($tabla, ' ') !== false) {
            $tableExploded = new ArrayObject(explode(' ', $tabla));
            $tblKey = reset($tableExploded);

            $tblKeyExploded = new ArrayObject(explode('.', $tblKey));
            $tblKey = end($tblKeyExploded);
        }


        $campos = array($buscado);
        if( !$returnSQL ){
            $campos[] = "uid_$tblKey";
            $campos[] = $actual;
        }

        //montamos la sql
        $sql = " SELECT ". implode(",", $campos) ." FROM $tabla WHERE $actual = ".$this->uid ." ";
        if( $condicion ){
            $sql .= " AND $condicion ";
        }


        // Definir el ORDEN de los resultados
        if( $order ){
            if( strpos($sql, "LIMIT") === false ){
                $sql .= " GROUP BY $buscado ORDER BY $order";
            } else {
                $sql = str_replace("LIMIT", "GROUP BY $buscado ORDER BY $order LIMIT", $sql);
            }
        }

        // Si no queremos ejecutar...
        if( $returnSQL === true ) return $sql;
        //lanzamos la query
        $arrayUID = $this->db->query( $sql, true );
        /*if( $this->db->lastError() ){
            die("No se pueden extraer los elementos relacionados");
        }*/

        return $arrayUID;
    }
    /** Contar numero de relaciones **/
    protected function obtenerConteoRelacionados($tabla, $actual, $buscado, $condicion = false)
    {
        $tblKey = $tabla;
        if (strpos($tabla, '.') !== false) {
            $tableExploded = new ArrayObject(explode('.', $tabla));
            $tblKey = end($tableExploded);
            if ($tblKey == 'perfil_empresa') {
                //es la unica que no cumple
                $tblKey = 'perfil';
                $tabla = TABLE_PERFIL;
            }
        }

        if (strpos($tabla, ' ') !== false) {
            $tableExploded = new ArrayObject(explode(' ', $tabla));
            $tblKey = reset($tableExploded);

            $tblKeyExploded = new ArrayObject(explode('.', $tblKey));
            $tblKey = end($tblKeyExploded);
        }

        //montamos la sql
        $sql = " SELECT count(uid_$tblKey) as cuenta FROM $tabla WHERE $actual = ".$this->uid;
        if( $condicion ){
            $sql .= " AND $condicion ";
        }

        //lanzamos la query
        $num = $this->db->query( $sql, 0, 0 );
        $result = ( is_numeric($num) ) ? $num : false;
        return $result;
    }

    protected function obtenerObjetosRelacionados($tabla, $tipo, $condicion = false, $order = false, $param = false ){
        // $coleccionObjetos = array();
        $coleccionObjetos = new ArrayObjectList();
        $tipobuscado = strtolower($tipo);
        $tipoactual = strtolower($this->getType());
        $actual = "uid_$tipoactual";
        $buscado = "uid_$tipobuscado";
        $lineasRelacion = $this->obtenerRelacionados($tabla, $actual, $buscado, $condicion, $order);
        foreach( $lineasRelacion as $linea ){
            $coleccionObjetos[] = new $tipo($linea[$buscado], $param);
        }
        return $coleccionObjetos;
    }

    /** CREARÁ UNA RELACION DE 2 ELEMENTOS EN LA TABLA INDICADA */
    protected function crearRelacion( $tabla, $actual, $idactual, $asignar, $idasignar ){
        $sql = "SELECT $actual, $asignar FROM $tabla WHERE $actual = $idactual AND $asignar = $idasignar";
        $resultset = $this->db->query( $sql );

        //buscamos la relacion, si existe la eliminamos, por si hay algun valor extra no definido, es decir dejar en by default
        //esta funcion digamos que hace un isert on duplicate key restore
        if( $resultset && $this->db->getNumRows($resultset) ){
            $sql = "DELETE FROM $tabla WHERE $actual = $idactual AND $asignar = $idasignar";
            $this->db->query( $sql );
        }

        //montamos la sql
        $sql = "INSERT INTO $tabla ($actual, $asignar) VALUES ($idactual, $idasignar)";
        //lanzamos la query
        return $this->db->query( $sql );
    }

    /**
        PASAMOS POR PARAMETRO LA TABLA DE LA RELACION Y EL RESTO ES AUTOMAGICO
            -   SE UTILIZAN LOS DATOS DEL OBJETO ACTUAL
            -   LA TABLA DE LA RELACION DEBE SER EN EL FORMATO $tipoobjeto_$tipoobjetorelacion
            -   SE ELIMINAN PRIMERO LOS REGISTROS Y SE GRABAN LOS NUEVOS
    */
    protected function actualizarRelacionRequest($tabla){
        $primaryKey = "uid_". $this->nombre_tabla;
        $foreing = end( new ArrayObject(explode("_",$tabla)) );

        //---- eliminamos todos los registros
        $sql = "DELETE FROM $tabla WHERE $primaryKey = ". $this->getUID();
        if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }

        //---- asignamos los que se han indicado en el formulario si es que se ha asignado algo
        if( isset($_REQUEST["elementos-asignados"]) && is_array($_REQUEST["elementos-asignados"]) ){
            $inserts = array();
            $asignados = $_REQUEST["elementos-asignados"];
            foreach( $asignados as $idAsignado ){
                $inserts[] = "(". $this->getUID() .", ". $idAsignado .")";
            }
            $sql = "INSERT INTO $tabla ( $primaryKey, uid_$foreing ) VALUES ". implode(",", $inserts);
            if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }
        }

        //----- si todo va bien
        return true;
    }

    public function inTrash($parent) {
        $sql = "SELECT papelera FROM {$this->tabla}
                WHERE uid_{$this->tipo} = {$this->getUID()} ";
         return !!$uid = $this->db->query($sql, 0, 0);
    }

    public function isActivable($parent = false, usuario $usuario = NULL){
        return true;
    }

    /** NOS INDICA LOS OBJETOS SUPERIRORES DEL ACTUAL EN EL QUE ESTAN CONTENIDOS

    **/
    public static function getSubModules(){
        return false;
    }

    /** NOS INDICA LOS OBJETOS INFERIORES DEL ACTUAL EN EL QUE ESTAN CONTENIDOS
            es un array de tipo array ( idmodulo => metodo de ubicacion )
    **/
    public static function getSupModules(){
        return false;
    }

    protected static function importBasics($usuario, $file, $modulo, $key = false ){
        $db = db::singleton();
        $campos = call_user_func(array($modulo,"publicFields"), elemento::PUBLIFIELDS_MODE_IMPORT, NULL, $usuario );
        $campos = ( $campos instanceof ArrayObject ) ? array_keys($campos->getArrayCopy()) : array_keys($campos);

        $tabla = constant("TABLE_". strtoupper($modulo));
        $tmptabla = "tmp_$modulo"."_import_".$usuario->getUID().uniqid();
        $temporal = DB_TMP .".$tmptabla";

        $result = array();
            $result["tmp_table"] = $temporal;

        $reader = new dataReader($tmptabla , $file["tmp_name"], archivo::getExtension($file["name"]) );
        if( $reader->error ){
            throw new Exception($reader->error);
        }

        $camposFichero = array_map( "strtolower", $reader->leerCampos());
        $camposFichero = array_map( "trim", $camposFichero );

        if( end($camposFichero) == "parent" ){
            $parent = true;
            $campos[] = "parent";
        }


        if( count($campos) != count($camposFichero) ){
            throw new Exception("El numero de campos del fichero (". count($camposFichero) .") no coincide. Deben ser ". count($campos));
        }

        foreach($camposFichero as $i => $campoFichero){
            if( $campos[$i] != $campoFichero ){
                throw new Exception("Debes especificar los nombres de los campos en la primera fila. La columna <strong>" . $campos[$i] ."</strong> no es correcta");
            }
        }

        if( $reader->cargar(true, $key) ){
            $result["fichero"] = $reader->contarLineas();

            $currentAuto = db::getNextAutoincrement($tabla);

            if( isset($parent) && $parent ){
                array_pop($campos);
            }

            $sql = "
                INSERT IGNORE INTO $tabla (". implode(",", $campos) .")
                SELECT ". implode(",", $campos) ." FROM ". $temporal
            ;


            if( $db->query($sql) ){
                $numInserted = $db->getAffectedRows();

                if( $key ){
                    $fields = array("uid_$modulo");
                    if (isset($parent) && $parent) $fields[] = "parent";

                    $sql = "SELECT ". implode(",", $fields) ." FROM $tabla INNER JOIN $temporal USING($key) WHERE $tabla.uid_$modulo >= $currentAuto";
                    if(isset($parent) && $parent){
                        $lines = $db->query($sql, true);
                        if( is_array($lines) ){
                            $result["uids"] = $result["parents"] = array();
                            foreach($lines as $line){
                                $parentID = trim($line["parent"]);
                                $result["uids"][] = $line["uid_$modulo"];

                                if( !isset($result["parents"][$parentID]) ) $result["parents"][$parentID] = array();
                                $result["parents"][$parentID][] = trim($line["uid_$modulo"]);
                            }
                            $numInserted = count($result["uids"]);

                        } else {
                            throw new Exception( $db->lastError() );
                        }
                    } else {
                        $uids = $db->query($sql,"*", 0);
                        if( is_array($uids) ){
                            $result["uids"] = $uids;
                            $numInserted = count($uids);
                        } else {
                            throw new Exception( $db->lastError() );
                        }
                    }
                }

                if( !$numInserted ){
                    throw new Exception( "Parece que todos los elementos ya existen en el sistema" );
                }

                $result["insertados"] = $numInserted;
                $result["uid_nuevos"] = array_keys(array_fill($currentAuto,$numInserted,0));

                return $result;
            } else {
                    throw new Exception( $db->lastError() );
            }
        }  else {
            throw new Exception( $db->lastError() ? $db->lastError() : $reader->error );
        }
    }

    public static function importFromFile($file, $empresa, $usuario, $post = null)
    {
        // Importamos los elementos a la tabla
        return self::importBasics($usuario, $file, get_called_class() );
    }


    /* FUNCIONES ESTATICAS */
    public static function getCollectionIds($collection){
        if( is_array($collection) ){
            $uidreturn = create_function('$o', 'return $o->getUID();');
            return array_map($uidreturn, $collection);
        } elseif($collection instanceof ArrayObject) {
            return $collection->toIntList()->getArrayCopy();
        } else {
            return array();
        }
    }

    /** OBTENER EN ARRAY TODOS LOS MODULOS DE LA APLICACION *
    public static function getAllModules( $condicion = null,  $toList = false ){
        $cache = cache::singleton();
        $cacheString = 'allmodules-'. (str_replace(array("'"," "),"",$condicion)) ."-".$toList;
        if( ( $condicion && strpos($condicion, "SELECT") === false ) && ($estado = $cache->getData($cacheString)) !== null ){
            return $estado;
        }

        $db = db::singleton();
        $condicion = ( $condicion ) ? $condicion : 1;
        $sql = "SELECT uid_modulo, nombre, documentos, icononly FROM ". TABLE_MODULOS ." WHERE $condicion";

        if( $toList ){
            $data = $db->query( $sql, "*", 0 );
        } else {
            $data = $db->query( $sql, true );
        }

        $cache->addData($cacheString, $data);
        return $data;
    }/**/

    /** OBTENER EN ARRAY TODOS LOS NOMBRES DE MODULOS DE LA APLICACION *
    public static function getAllModulesNames( $condicion = null){
        $nombres = array();
        $modulos = self::getAllModules( $condicion );
        if( is_array($modulos) && count($modulos) ){
            foreach( $modulos as $modulo ){
                $nombres[] = strtolower($modulo["nombre"]);
            }
        }
        return $nombres;
    }

    public static function getModulesData( $condicion = null ){
        $data = array();
        $modulos = self::getAllModules( $condicion );
        if( is_array($modulos) && count($modulos) ){
            foreach( $modulos as $modulo ){
                $data[ $modulo["uid_modulo"] ] = strtolower($modulo["nombre"]);
            }
        }
        return $data;
    }*/

    /** PASANDO UN COJUNTO DE OBJETOS COMO PRIMER PARAMETRO ELIMINA DE EL CONJUNTO LOS QUE SE ENCUENTREN EN EL SEGUNDO PARAMETRO */
    public static function discriminarObjetos($coleccion, $extraer){
        $resultado = $idsActuales = array();

        if( is_object($extraer) && !is_traversable($extraer) ){ $extraer = array( $extraer ); }

        if( is_traversable($extraer) && count($extraer) ){
            foreach($extraer as $objeto){
                $idsActuales[] = $objeto->getUID();
            }
        }

        if( !count($idsActuales) || !count($extraer) ){ return $coleccion; }

        if( is_traversable($coleccion) && count($coleccion) ){
            foreach($coleccion as $objeto){
                if( !in_array($objeto->getUID(), $idsActuales) ){
                    $resultado[] = $objeto;
                }
            }
        }
        return $resultado;
    }

    /**
        DEVOLVER LOS OBJETOS ORDENADOS
    */
    public static function orderObjects($arrayObjects){
        function cmp($a, $b){
            return strcmp($a->getUserVisibleName(), $b->getUserVisibleName());
        }

        usort($arrayObjects, "cmp");
        return $arrayObjects;
    }


    /** CONSTRUYE UN WHERE CUANDO SE HACEN LOS SELECT */
    public static function construirCondicion( $eliminadas, $limit = false ){
        if( isset($eliminadas ) ){
            $condicion = "1";
            if( $eliminadas === true ){     $condicion = "papelera = 1"; }
            elseif( $eliminadas === false ){    $condicion = "papelera = 0"; }
            else { $condicion = $eliminadas; }

        } else {
            $condicion = 1;
        }

        if( is_array($limit) && is_numeric($limit[0]) && is_numeric($limit[1]) ){
            $condicion = " $condicion LIMIT ".$limit[0].", ".$limit[1];
        }

        return $condicion;
    }

    public static function getAll() {
        $class = get_called_class();
        $table = constant('TABLE_'. strtoupper($class));
        $uid = "uid_{$class}";

        $items = db::get('SELECT '.$uid.' FROM '.$table .' WHERE 1', "*", 0, "empresa");
        return $items;
    }

    /** SE INVOCARÁ CUANDO SE NECESITE CREAR UN ELEMENTO NUEVO. SOLO TIENE SENTIDO SI ES SOBREESCRITA **/
    public static function defaultData($data, Iusuario $usuario = null) {
        $data['created'] = time();
        return $data;
    }

    public function getSelectName($fn=false){
        $childrenFn = array($this,"getUserVisibleName");
        if( is_callable($childrenFn) ){
            return call_user_func( $childrenFn, $fn );
        }
    }

    public function getAssignName($fn, $parent = NULL){
        $childrenFn = array($this, "getUserVisibleName");
        if( is_callable($childrenFn) ){
            return call_user_func( $childrenFn, $fn );
        }
    }

    public function getListName($fn = false){
        $childrenFn = array($this,"getUserVisibleName");
        if( is_callable($childrenFn) ){
            return call_user_func( $childrenFn, $fn );
        }
    }

    public function getFullTableName(){
        return $this->tabla;
    }

    public static function getNumberRegExp() {
        return '^(0|[1-9][0-9]*)$';
    }

    public static function getEmailRegExp(){
        return "^([ñÑa-zA-Z0-9_\.\-\+])+\@(([ñÑa-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$";
    }

    public function needsConfirmationBeforeTrash($parent, usuario $usuario) {
        return false;
    }


    /**
      * Maps column names to objets
      *
      */
    public function getColMap ($col) {
        $list = array(
            'uid_empresa_referencia' => 'empresa'
        );

        return isset($list[$col]) ? $list[$col] : false;
    }

    public function __destruct(){
        unset($this->cache);
        foreach($this as $varname => $val ){
            unset( $this->$varname );
        }
    }

    public function __wakeup($restore=true){
        if($restore){
            $this->cache = cache::singleton();
            $this->db = db::singleton();
        }
    }

    public function logError($where, $string = "") {
        $referer = isset($_SERVER['HTTP_REFERER']) ? "Referer: ".$_SERVER['HTTP_REFERER'] : "no referer";
        error_log("Error: $where $string. item: {$this->getUID()} from module {$this->getModuleName()}. $referer.");
    }

    public function clearCache ($key) {
        return $this->cache->clear($key);
    }
}
