<?php

abstract class basic
{
    const REGISTER_ACTION_EDIT = false;

    protected $db;
    protected $tabla;
    protected $uid;
    protected $tipo;
    protected $uid_modulo;
    protected $cache;
    protected $user;

    protected $dispatcher;

    protected function instance($param, $extra)
    {
        // el nombre simple de la tabla, sin la bd
        if (!isset($this->nombre_tabla)) {
            $tableExploded = new ArrayObject(explode('.', $this->tabla));
            $this->nombre_tabla = end($tableExploded);
        }

        $this->db = db::singleton();

        // la instancia del objeto donde almacenaremos datos..
        $this->cache = cache::singleton();

        // el tipo del elemento, si no esta definicio, el nombre de la tabla
        if (!$this->tipo) {
            $this->tipo = $this->nombre_tabla;
        }

        // grabar el id si es numerico
        if (is_numeric($param)) {
            $this->uid = db::scape($param);
        }

        $app = \Dokify\Application::getInstance();
        $this->app = $app;
        $this->dispatcher = $app['dispatcher'];

        return $param;
    }

    public function clearItemCache(){
        $this->cache->clear("getinfo-{$this}-*");
    }

    /** DEFINIR EL USUARIO PARA FILTRAR EN LAS FUNCIONES **/
    public function setUser($usuario){
        $this->user = $usuario;
    }

    public function exists(){
        if(!$this->uid) return false;
        $key = "uid_".  $this->nombre_tabla;
        $sql = "SELECT count($key) num FROM $this->tabla WHERE uid_$this->nombre_tabla = $this->uid";
        return (bool) $this->db->query($sql, 0, 0);
    }

    /**
      * Se usa para que nos de una url (de hash) para ver
      * este elemento en la página de la manera mas habitual posible
      * de momento redirigimos al buscador
      */
    public function obtenerUrlPreferida($full=false){
        $tipo = strtolower($this->getType());
        $href = "#buscar.php?q=tipo:$tipo%20uid:". $this->getUID();
        if($full) $href = CURRENT_DOMAIN . "/agd/" . $href;
        return $href;
    }

    public function obtenerUrlFicha($text=false){
        $tipo = strtolower($this->getType());
        $link = "../agd/ficha.php?m=$tipo&oid=".$this->getUID();
        if( $text ){
            return "<a href='$link' class='box-it'>$text</a>";
        }
        return $link;
    }

    public function obtenerUrlPerfil($text=false){
        $tipo = strtolower($this->getType());
        $link = "#profile.php?m=$tipo&oid=".$this->getUID();
        if( $text ){
            return "<a href='$link'>$text</a>";
        }
        return $link;
    }

    public function obtenerUrlPublica($usuario){
        $empresaCliente = $usuario->getCompany();
        $dominio = $empresaCliente->getURLBase();
        $tipo = strtolower($this->getType());
        return $dominio."agd/#buscar.php?q=tipo:$tipo%20uid:". $this->getUID();
    }


    public function getModuleId( $tipo = false ){
        $tipo = ( $tipo ) ? $tipo : $this->tipo;
        return util::getModuleId($tipo);
    }

    public function getModuleName( $uid = false ){
        $uid = ( is_numeric($uid) ) ? $uid : $this->getModuleId();
        return util::getModuleName($uid);
    }

    /** DEVOLVER EL ID DEL ELEMENTO */
    public function getUID(){
        return $this->uid;
    }

    /** DEVOLVER UN DATO (COLUMNA) DEL ELEMENTO */
    public function obtenerDato($dato,$force=false){
        if( strstr($dato,"[]") ){
            return $this->getMultipleFieldValues($dato);
            /*
            $campo = str_replace("[]","",$dato);
            $sql = "SELECT $campo FROM $this->tabla"."_$campo WHERE uid_". $this->getType() ." = ". $this->uid;
            $list = $this->db->query( $sql, "*", 0 );
            return $list;
            */
        } else {
            $datos = $this->getInfo(false,null,null,null,$force);
            if( isset($datos[$dato]) ){
                return $datos[$dato];
            } else {
                return false;
            }
        }
    }

    public function getMultipleFieldValues($campo){
        $campo = str_replace("[]","",$campo);
        $sql = "SELECT $campo FROM $this->tabla"."_$campo WHERE uid_". $this->getType() ." = ". $this->uid;
        $list = $this->db->query( $sql, "*", 0 );
        return $list;
    }


    /*
     * OBTENER UN ARRAY DE VALORES DEFINIDOS COMO PUBLICOS CON SUS VALORES ACTUALES
     *
     * Si formMode es true, mantendremos el formato de campos que nos devuelve la funcion ::publicFields() de cada elemento
     * El valor comeFrom se la pasa a la funcion publicFields para que sepa de que manera devolver los datos
     */
    public function getPublicFields( $formMode = false, $comeFrom = null, $usuario = false, $tab = false ){
        $func = $this->tipo.'::publicFields';
        if( !is_callable($func) ){ die("Error en getPublicFields #1 para ". $this->tipo); }
        $publics = call_user_func( $func, $comeFrom, $this, $usuario, $tab);
        $values =  $this->getInfo(false, $comeFrom, $usuario );


        foreach( $publics as $name => $public ){
            if( isset($public["uid_campo"]) && $public["uid_campo"] ){
                $campo = new campo( $public["uid_campo"] );
                $datos = $campo->getData();

                if( $datos && $campo->getTag() != "select" ){
                    $values[$name] =  $datos[ $campo->getValue($this) ];
                } else {
                    $values[$name] = $campo->getValue( $this );
                }
            }

            if( $formMode ){
                if( $comeFrom == 'edit' && isset($values[$name]) && is_traversable($values[$name]) && isset($values[$name]["innerHTML"]) ){
                    $publics[$name]["value"] = $values[$name]["innerHTML"];
                } else {
                    if (isset($values[$name])){
                        $publics[$name]["value"] = $values[$name];
                    }

                }

                if( strstr($name,"[]") ){
                    $fname = str_replace("[]","",$name);
                    // esto indica que es multiple, pero que guarda los datos en una lista separada por ,
                    if( isset($publics[$name]["list"]) && $publics[$name]["list"] ){
                        //$publics[$name]["value"] = $values[$fname];
                        $publics[$name]["value"] = $publics[$name]["multiple"] = explode(",", $values[$fname]);
                    } else {
                        if( isset($publics[$name]["extra"]) && $publics[$name]["extra"] ){
                            $sql = "SELECT * FROM $this->tabla"."_$fname WHERE uid_". $this->getType()." = ". $this->getUID();
                            $datos = $this->db->query($sql, true);
                            if( is_array($datos) && count($datos) ){
                                $publics[$name]["multiple"] = $datos;
                            }
                        }
                    }
                }
            } else {
                if( isset($values[$name]) ){
                    $publics[$name] = utf8_decode($values[$name]);
                } else {
                    unset( $publics[$name] );
                }
            }

            if( isset($publics[$name]) && isset($publics[$name]["value"]) && $publics[$name]["value"] ){
                if( isset($publics[$name]["format"]) ){
                    $publics[$name]["value"] = sprintf($publics[$name]["format"], $publics[$name]["value"]);
                }

                if( isset($publics[$name]["date_format"]) ){
                    $format = str_replace("%", '', $publics[$name]["date_format"]);
                    if( is_numeric($publics[$name]["value"]) ){
                        $publics[$name]["value"] = date($format, $publics[$name]["value"]);

                    // Contemplamos solo escribir fecha y hora (no  hay que hacer nada...)
                    } elseif ( $publics[$name]["date_format"] == "%H:%M") {

                    } else {
                        if ($publics[$name]["value"] != '0000-00-00') {
                            $createFormat = 'Y-m-d';
                            //contemplamos date y datetime
                            if ( stripos($publics[$name]['value'],':') !== false) $createFormat = 'Y-m-d H:i:s';
                            $fecha = date_create_from_format($createFormat, $publics[$name]["value"]);

                            if ($fecha instanceof DateTime) {
                                $publics[$name]["value"] = date_format($fecha, $format);
                            }
                        } else {
                            $publics[$name]["value"] = null;
                        }
                    }
                }
            }
        }


        if( !is_traversable($publics) || !count($publics) ){ die("Error en getPublicFields #2 para  ". $this->tipo); }
        return $publics;
    }

    /** Solo un alias para updateWithRequest que nos permite ir actualizando la API de forma escalonada **/
    public function updateWithRequest($data=false, $fieldsMode=false, Iusuario $usuario = NULL){
        return $this->update($data, $fieldsMode, $usuario);
    }

    /** ACTUALIZAR A TRAVES DE LOS DATOS ENVIADOS POR UN FORMULARIO */
    public function update($data=false, $fieldsMode=false, Iusuario $usuario = NULL){
        $fieldsMode = ( $fieldsMode ) ? $fieldsMode : "edit";
        //CAMPOS PUBLICOS, SON LOS ÚNICOS QUE SE HAN PODIDO MODIFICAR NORMALMENTE
        $publics = $this->getPublicFields(true, $fieldsMode, $usuario);
        //ARRAY DE NUEVOS DATOS DEL ELEMENTO
        $newValues = $multiples = array();
        //SI NO HAY NINGUN UPDATE EXTRA, ESTA VALOR NO CAMBIARA
        $updateExtras = $suposeUpdate = $updateMultiples = $updateForeign = false;

        $trigger = new trigger( $this->tabla, $usuario );

        $get = ( $data ) ? $data : $_REQUEST;

        $class = get_class($this);
        $get = $this->updateData($get, $usuario, $fieldsMode);

        $preProcess = false;

        //BUSCAMOS LOS CAMPOS QUE TIENEN CAMBIOS
        foreach( $publics as $field => $data ){
            if (isset($get[$field]) && $get[$field] === true) {
                $preProcess = true;
                continue;
            }

            if( isset($data["tag"]) && $data["tag"] == "span" ){ continue; }
            $value = @$data["value"];
            $fname = str_replace("[]","",$field);
            if( strstr($field,"[]") && isset($get[$fname]) && is_array($get[$fname]) ){
                //$field = str_replace("[]","",$field);
                //if( isset($get[$field])/* && $get[$field] != $value*/ ){
                $requestValue = $get[$fname];
                $multiples[$fname] = $requestValue;
                $updateMultiples = true;
                //}
            } else {
                // -- prevent errors when same name is used for multiple and normal fields
                if (is_array(@$get[$field])) continue;

                if( strstr($field,"[]") ){
                    $field = str_replace("[]","",$field);
                    //if( isset($get[$field]) && is_array($get[$field]) ){ continue; } // si llegamos aqui, es error, deberíamos estar en el if anterior
                }

                if( isset($get[$field]) && $get[$field] != $value ){
                    $requestValue = $get[$field];
                    //ESTO NOS INDICA QUE ES UN CAMPO DINAMICO EL QUE SE QUIERE ACTUALIZAR Y SE MODIFICA DE DIFERENTE MANERA
                    if( isset($data["uid_campo"]) && $data["uid_campo"] && $this->tipo != "campo" ){
                        $suposeUpdate = true;
                        $campo = new campo( $data["uid_campo"] );
                        $status = $campo->setValue( $this, db::scape( $requestValue ) );
                        if( $status === true ){ $updateExtras = true; }
                    } elseif( isset($data["foreign"]) && $foreign = $data["foreign"] ){
                        $primaryKey = db::getPrimaryKey($foreign["table"]);
                        $sql = "UPDATE {$foreign["table"]} SET $field = $requestValue WHERE $primaryKey = '{$foreign["key"]}'";
                        if( $this->db->query($sql) ){  $updateForeign = true;  }
                    } else {
                        if( isset($data["className"]) && strpos($data["className"], "datepicker") !== false ){
                            $aux = explode("/", $requestValue);
                            if( count($aux) === 3 && strlen($aux[2]) === 4 && strlen($aux[1]) === 2 && strlen($aux[0]) === 2 ){
                                $requestValue = "{$aux[2]}-{$aux[1]}-{$aux[0]}";
                            }
                        }

                        $value = $requestValue === 'NULL' ? $requestValue : "'" . utf8_decode( db::scape( $requestValue ) ). "'";
                        $newValues[] = $field . " = " . $value;
                    }
                }
            }
        }
        //si no hay valores y se supone que no se debe actualizar o si se supone que se
        //debe actualizar y no se ha actualizado....

        if( ( !count($newValues) && ( ($suposeUpdate && !$updateExtras) || !$suposeUpdate ) && !$updateMultiples ) && !$updateForeign ){
            if ($preProcess) return true;
            return null;
        }

        $resultadosExtra = $extraUnique = array();
        foreach( $publics as $field => $data ){
            //COMPROBAMOS QUE EL CAMPO TIENE ALGUN CAMPO EXTRA QUE CONDICIONE EL UPDATE
            if (isset($data["extra"])) {
                foreach ($data["extra"] as $ext) {
                    if (strstr($field,"[]")) {
                        if ($ext["type"] == "checkbox") {
                            $extraUnique[] = $ext;  // nos da igual que sobreescriba, solo queremos uno por input[name] diferente
                        } else {
                            $extraIndex = str_replace("[]","[". $ext["name"] ."]", $field);
                            $extraUnique[] = $ext;
                        }
                    } else {
                        if (isset($get[$ext["name"]])) {
                            /*FUNCION QUE SE EJECUTA CUANDO ALGUN CAMPO DEL PUBLIC FIELDS DEPENDE DE UNO ADICIONAL PARA SU COMPROBACION. EJ:configuracion>documentos>modificar*/
                            //PARAMETROS : $this, campo,valor del campo donde está el extraline,array de valores de extra
                            $resultadosExtra[$field] = call_user_func( array($this->getModuleName(),$ext["callback"]),$this, $field, $get[$field], $ext);
                        }
                    }
                }
            }
        }


        if (count($multiples)) {
            foreach ($multiples as $campo => $values) {
                $resultadosMultiples = array();

                // Actualizamos los datos directamente
                $table = "$this->tabla"."_$campo";
                $sql = "DELETE FROM $table WHERE uid_".$this->getType()." = ". $this->getUID();
                if( $this->db->query($sql) ){
                    foreach( $values as $i => $value ){
                        $sql = "INSERT INTO $table ( uid_".$this->getType().", $campo ) VALUES ( ". $this->getUID() .", '". db::scape($value) ."' )";
                        if( $this->db->query( $sql ) ){
                            $resultadosMultiples[] = $this->db->getLastId();
                        }
                    }
                }

                foreach ($values as $i => $value) {

                    if (count($extraUnique)) {

                        foreach ($extraUnique as $j => $ext) {
                            $val = array();

                            //$ext = $extraUnique[$field];
                            $rowIndex = $resultadosMultiples[$i];
                            $reqIndex = $ext["name"];

                            if ($ext["type"] == "checkbox" || $ext["type"] == "radio") {


                                $val = isset($get[$reqIndex][$i]) ? $get[$reqIndex][$i] : NULL;

                                $campo = str_replace("[]","", $field);
                            } else {
                                preg_match ("((.*)\[(.+)?\])" , $field, $matches);
                                @list($string, $nombrecampo, $rowindex) = $matches;

                                if ($nombrecampo != $campo) { continue; } // por si hay varios..

                                if (isset($get[$reqIndex][$i])) $val[$rowindex] = $get[$reqIndex][$i];
                            }

                            if (isset($ext["callback"])) {
                                $resultadosExtra[$reqIndex] = call_user_func(array($this->getModuleName(),$ext["callback"]), $this, $campo."[".$rowIndex."]", $val, $ext);
                            }
                        }

                    }
                }
            }
        }


        $trigger->beforeUpdate($this, $get);

        $returnSql = false;
        if (count($newValues)){
            $sql = "UPDATE $this->tabla SET ". implode(", ", $newValues) ." WHERE uid_".$this->nombre_tabla." = ".$this->uid;
            $returnSql = $this->db->query( $sql );
        }

        if(  (isset($resultadosMultiples) && count($resultadosMultiples) && !$newValues) || $returnSql || ($suposeUpdate && $updateExtras) || $updateForeign === true ){
            $trigger->afterUpdate( $this, $newValues, $fieldsMode);
            $this->clearItemCache();
            if($usuario instanceof Iusuario) {
                $this->writeLogUI( logui::ACTION_EDIT, implode(",", $newValues), $usuario);
            }
            return $resultadosExtra = empty($resultadosExtra) ? true : $resultadosExtra;
        } else {
            $this->error = $this->db->lastErrorString();
            return false;
        }
    }

    public function writeLogUI($texto, $value = "", Iusuario $usuario = null, empresa $company = null)
    {
        $constant = get_class($this) . "::NO_REGISTER_CREATION";
        if (!$this instanceof logui && !defined($constant)) {
            if ($usuario instanceof Iusuario || $usuario === null) {
                $uidModulo = $this->getModuleId();
                if (!$uidModulo) {
                    $log = new log();
                    $log->info(get_class($this), "log sin modulo ",  $this->getUID(), "ok", true);
                }

                $user 		= ($usuario instanceof Iusuario) ? $usuario->getUID() : 0;
                $usertype 	= ($usuario instanceof Iusuario) ? $usuario->getModuleId() : 0;
                $profileUid = ($usuario instanceof Iusuario) ? $usuario->idPerfilActivo() : 0;

                $data = [
                    "uid_elemento"	=> $this->getUID(),
                    "uid_modulo"  	=> $uidModulo,
                    "uid_usuario" 	=> $user,
                    "user_type"   	=> $usertype,
                    "texto"       	=> $texto,
                    "valor" 		=> $value,
                    "uid_perfil" 	=> $profileUid
                ];

                if ($usuario instanceof usuario) {
                    $data['uid_empresa'] = $usuario->getCompany()->getUID();
                } else if ($company instanceof empresa) {
                    $data['uid_empresa'] = $company->getUID();
                }

                return new logui($data, $usuario);
            } else {
                if ( CURRENT_ENV === "dev") {
                    trace();
                }
                die("No hay usuario al registrar el log  de cambios en el objeto {$this->getType()} ($texto)" );
            }
        }
    }



    public function getLogUI($action = false, $count = false){
        $field = ( $count ) ? "count(uid_logui)" : "uid_logui";
        $sql = "SELECT $field FROM ". TABLE_LOGUI ." WHERE uid_modulo = {$this->getModuleId()} AND uid_elemento = {$this->getUID()}";
        if( $action ) $sql .= " AND texto = '". db::scape($action) ."' ";

        if( $count ) return $this->db->query($sql, 0, 0);

        $coleccion = $this->db->query($sql, "*", 0, "logui");
        if( count($coleccion) ){
            return new ArrayObjectList($coleccion);
        } else {
            return false;
        }
    }

    /**TODOS LOS CAMPOS DEL ELEMENTO*/
    public function getFields( $diferentTable = false ){
        $tabla = ( $diferentTable ) ? $diferentTable : $this->tabla;

        $cacheKey = __CLASS__.'-'.__FUNCTION__.'-'.$tabla;
        if (($value = $this->cache->getData($cacheKey)) !== NULL) return json_decode($value, true);

        $class = get_class($this);
        if (method_exists($class, "getTableFields")) {
            return $class::getTableFields();
        } else {
            $campos = $this->db->query("SHOW COLUMNS FROM $tabla", true);
            //error_log("SHOW COLUMNS FROM $tabla");
            //print "tabla: {$tabla} - clase {$class}\n";
        }

        $this->cache->addData($cacheKey, json_encode($campos));
        return $campos;
    }


    public function getIcon($size=false){
        $size = ( $size ) ? "_$size" : "";
        return RESOURCES_DOMAIN . "/img/class/". strtolower($this->getType()) . "$size.png";
    }

    /**RETORNA TODOS LOS DATOS DE EL ELEMENTO
            COMEFROM SE LE PASARA AL METODO ESTATICO DE CADA OBJETO PUBLICFIELDS || SI ES TRUE, DEVOLVERA UN ARRAY SIMPLE
    */
    public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = NULL, $extra = array(), $force = false){

        if( !is_numeric($this->getUID()) ){ die("Objeto ". __CLASS__ ." no instanciado: ". __LINE__); }

        $cacheString = "getinfo-{$this}-{$publicMode}-{$comeFrom}-{$usuario}";
        if( !$force ){
            if( ($estado = $this->cache->getData($cacheString)) !== null ){
                return $estado ? json_decode($estado, true) : false;
            }
        }

        $multiples = $camposSQL = array(); 		//array donde almacenar los datos


        //para cada campo (es un array), solo utilizamos el nombre
        if ($publicMode && method_exists($this->tipo, 'publicFields')) {

            $publicFields = call_user_func( $this->tipo.'::publicFields', $comeFrom, $this, $usuario);

            foreach($publicFields as $key => $campo){

                if( isset($campo["nodb"]) ){
                    //$camposSQL[$key] = $campo["innerHTML"];
                    continue;
                }

                // si es multiple, en otra tabla
                if( strstr($key,"[]") ){
                    if( isset($campo["list"]) ){
                        $key = str_replace("[]", "", $key);
                    } else {
                        $multiples[] = $key;
                        continue;
                    }
                }

                if( ( isset($campo["uid_campo"]) && $campo["uid_campo"] ) || isset($campo["nodb"]) ){
                    // no es necesario añadir a camposSQL
                    continue;
                }


                if( isset($campo["foreign"]) && $foreign = $campo["foreign"] ){
                    $primaryKey = db::getPrimaryKey($foreign["table"]);
                    $val = $this->db->query("SELECT $key FROM {$foreign["table"]} WHERE $primaryKey = '{$foreign["key"]}'", 0, 0);
                    $camposSQL["'". utf8_decode($val) . "' as $key"] = $publicFields[$key];
                    unset($camposSQL[$key]);
                }

                // Mostrar los campso data correctamente!
                if( $comeFrom == elemento::PUBLIFIELDS_MODE_TAB ){
                    if( isset($campo["objeto"]) ){
                        $nkey = ( strpos(" as ", $key) === false ) ? "`$key`" : $key;
                        $val = $this->db->query("SELECT $nkey FROM $this->tabla WHERE uid_".$this->nombre_tabla." = $this->uid", 0, 0);
                        $ob = new $campo["objeto"]($val);
                        $camposSQL["'". utf8_decode(db::scape($ob->getUserVisibleName())) . "' as $key"] = $publicFields[$key];
                        unset($camposSQL[$key]);
                        continue;
                    }

                    if( isset($campo["date_format"]) ){
                        $camposSQL["IF($key, FROM_UNIXTIME($key, '{$campo["date_format"]}'), '') as $key"] = $publicFields[$key];
                        continue;
                    }
                }

                if (isset($campo['innerHTML'])) {
                    $key = $key .= " as '{$campo['innerHTML']}'";
                }

                $camposSQL[$key] = $campo;
            }

            $camposSQL = ( $camposSQL instanceof ArrayObject ) ? array_keys($camposSQL->getArrayCopy()) : array_keys($camposSQL);
        } else {
            //si no se saben los campos
            if( !isset($this->campos) || !count($this->campos) ){
                $campos = $this->getFields();
            }

            //los buscamos
            foreach( $campos as $campo ){
                $camposSQL[] = "`".$campo["Field"]."`";
            }

            if (method_exists($this->tipo, 'publicMultipleFields')) {

                $publicMultipleFields = call_user_func( $this->tipo.'::publicMultipleFields', $comeFrom, $this, $usuario);

                foreach($publicMultipleFields as $key => $campo){
                    if( strstr($key,"[]") && !isset($campo["list"]) ){
                        $multiples[] = $key;
                        continue;
                    }
                }
            }



        }

        //preparamos la query
        $sql = "SELECT ". implode(", ",$camposSQL) .
                 " FROM " . $this->tabla .
                 " WHERE ". $this->tabla . ".uid_" . $this->nombre_tabla  . " = ". $this->uid ;

        if( $publicMode ){
            $arrayResultado = array();
            //obtenemos los datos
            $datos = $this->db->query( $sql, true );

            if( !is_traversable($datos) || !$datos ){
                return false;
                die("No hay datos para el objeto ". $this->tipo ." no instanciado. Puede deberse a que los campos no son correctos. ". $this->getUID());
            }
            $datos = reset( $datos );


            if( $datos && count($datos) ){
                //recorremos los campos, solo de la primera linea, debe ser la unica
                foreach( $datos as $field => $value ){
                    //si es el identificador de la tabla
                    if( $field == "uid_".$this->nombre_tabla && (!isset($publicFields) || is_array($publicFields) && !in_array($field, array_keys($publicFields))) ){
                        unset( $datos[ $field ] );
                        $datos = array_map("utf8_encode", $datos );
                        //$datos = array_map("utf8_decode", $datos );
                        $resultado = array( $value => $datos );

                        // Guardar y devolver
                        //$this->cache->addData( $cacheString, $resultado );
                        return $resultado;
                    }
                }


                $data 	= array();
                foreach($publicFields as $key => $campo){
                    if (isset($campo["nodb"])) {
                        $data[$key] = $campo["innerHTML"];
                    } elseif (isset($datos[$key])) {
                        $data[$key] = $datos[$key];
                    }
                }
                $datos = array_map("utf8_encode", $data);



                if( count($multiples) ){

                    foreach($multiples as $campo ){
                        $values = $this->getMultipleFieldValues($campo);
                        if( is_traversable($values) && count($values) ){
                            $datos[$campo] = array();
                            foreach($values as $val){
                                if( $comeFrom == elemento::PUBLIFIELDS_MODE_TAB && isset($publicFields[$campo]["objeto"])){
                                    $objeto= new $publicFields[$campo]["objeto"]($val);
                                    $val= $objeto->getUserVisibleName();
                                }
                                $datos[$campo][] = $val;
                            }
                        }
                    }
                }

                if( $comeFrom === true ){
                    $this->cache->addData($cacheString, json_encode($datos));
                    return $datos;
                }

                $resultado = array($this->getUID() => $datos);

                // Guardar y devolver
                $this->cache->addData($cacheString, json_encode($resultado));
                return $resultado;
            }
        } else {
            $linea = $this->db->query( $sql, 0, "*" );

            if( is_array($linea) ){
                $linea = array_map("utf8_encode", $linea );
            }

            if( count($multiples) ){
                foreach($multiples as $campo ){
                    $values = $this->getMultipleFieldValues($campo);
                    if( is_traversable($values) && count($values) ){
                        $linea[$campo] = array();
                        foreach($values as $val){
                            if( $comeFrom == elemento::PUBLIFIELDS_MODE_TAB && isset($publicFields[$campo]["objeto"])){
                                $objeto= new $publicFields[$campo]["objeto"]($val);
                                $val= $objeto->getUserVisibleName();
                            }
                            $linea[$campo][] = $val;
                        }
                    }
                }
            }
            // Guardar y devolver
            $cacheData = $linea ? json_encode($linea) : false;
            $this->cache->addData($cacheString, $cacheData);

            //retornamos la primera linea, que debe ser la unica
            return $linea;
        }
    }


    /** ELIMINAR EL ELEMENTO ACTUAL DEFINITIVAMENTE */
    public function eliminar(Iusuario $usuario = NULL){
        $trigger = new trigger( $this->tabla, $usuario );

        $trigger->beforeDelete($this);
        $deleted = config::eliminarElemento( $this->getUID() , $this->tabla );
        if( $deleted ){
            $this->cache->clear();
            $trigger->afterDelete($this);
        }
        return $deleted;
    }


    public function compareTo($item){
        if( !is_object($item) ){ return false; }
        if( $item->__toString() === $this->__toString() ){
            return true;
        }
        return false;
    }

    public static function _crear($informacion, $clase){
        $db = db::singleton();
        $datos = array_keys( call_user_func( $clase.'::publicFields', "new" ) );
        $values = array();

        foreach( $datos as $campo ){
            if( isset($informacion[$campo]) ){
                $values[] = "'". utf8_decode(db::scape($informacion[$campo])) ."'";}
        }

        $tabla = constant('TABLE_' . strtoupper($clase));
        $sql = "INSERT INTO $tabla (". implode(",",$datos) .")
        VALUES (". implode(",",$values) .")";
        if( !$db->query($sql) ){ return $db->lastErrorString(); }

        return new $clase( $db->getLastId() );
    }


    public static function obtenerNombreModulo( $id ){
        return util::nombreModulo($id);
    }

    public static function obtenerIdModulo($nombre) {
        $modulos = util::getAllModules(true);
        if (!@$modulos[strtolower($nombre)]) return @$modulos[$nombre]; // If we do not found the module in lowercase
        return @$modulos[strtolower($nombre)];
    }

    public static function factory($string, $param = false){
        @list($item, $props) = explode('|', $string);
        @list($uid, $class) = explode('-', $item);

        if ($uid && $class && class_exists($class)) {
            $elemento = new $class($uid, $param);

            if ($props && $props = explode(';', $props)) {
                foreach ($props as $prop) {
                    @list($key, $val) = explode('=', $prop);
                    $elemento->$key = self::factory($val);
                }
            }

            return $elemento;
        }

        return false;
    }

    public function __toString(){
        if (method_exists($this, 'getModuleName')) {
            return $this->getUID(). "-". $this->getModuleName();
        }

        return $this->getUID()."-".get_called_class();
    }

    /** SE INVOCARÁ CUANDO SE MODIFIQUE UN ELEMENTO EXISTENTE. SOLO TIENE SENTIDO SI ES SOBREESCRITA **/
    public function updateData($data, Iusuario $usuario = NULL, $mode = NULL) {
        return $data;
    }
}
