<?php

class rol extends elemento implements Ielemento
{
    const TIPO_NORMAL = 0;
    const TIPO_ESPECIFICO = 1;
    const ROL_DEFAULT = 'default';

    const ONLY_COMPANIES = 1532;
    const ONLY_EMPLOYEES = 1540;
    const ONLY_VIEW      = 1530;
    const DEFAULT_ID     = 1513;

    /**
        CONSTRUIR EL OBJETO, LLAMA AL METODO INSTANCE DE LA CLASE ELEMENTO
    */
    public function __construct($param, $extra=false){
        $this->tipo = "rol";
        $this->tabla = TABLE_ROL;

        $this->instance( $param, $extra );
    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Role\Role
     */
    public function asDomainEntity()
    {
        $info = $this->getInfo();

        // Instance the entity
        $entity = new \Dokify\Domain\Role\Role(
            new \Dokify\Domain\Role\RoleUid($this->getUID()),
            $info['nombre']
        );

        return $entity;
    }

    public function getUserVisibleName(){
        $info = $this->getInfo();
        return $info["nombre"];
    }


    static public function optionsFilter($uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){
        $condicion = array();

        if( $uidelemento ){
            $rol = new self($uidelemento);
            $info = $rol->getInfo();
            if ($info["nombre"] == 'default'){
                $condicion[] = " ( uid_accion != 14 ) ";
            }
        }

        if( count($condicion) ){
            return " AND ". implode(" AND ", $condicion);
        }

        return false;
    }

    /** 
        APLICA LOS PERMISOS DE EL ROL ACTUAL AL PERFIL PASADO POR PARAMETRO, 
            @param $persistent -> si sera asignar en vez de aplicar...
    **/
    public function actualizarPerfil($uidPerfil, $persistent=false ) {
        $datosOpcionesRol = $this->obtenerOpcionesDisponibles();
        if( $uidPerfil instanceof perfil ){ $uidPerfil = $uidPerfil->getUID(); }
        if( $uidPerfil instanceof usuario ){ $uidPerfil = $uidPerfil->idPerfilActivo(); }

        //PRIMERO BORRAMOS DE LA TABLAPERFIL_ACCION TODO DE ESE PERFIL          
        if ( count($datosOpcionesRol) ) {
            
            //opciones extra del rol
            /*$campos = $this->obtenerOpcionesExtra();
            foreach($campos as $field => $value){
                $this->db->query("UPDATE ". TABLE_PERFIL ." SET $field = $value WHERE uid_perfil = ". $uidPerfil);
                $opcionesRelacionadas = $this->obtenerOpcionesRelacionadas($field);
                foreach($opcionesRelacionadas as $subCampo){
                    $this->db->query("UPDATE ". TABLE_PERFIL ." SET ". $subCampo["name"] ." = ". $subCampo["value"] ." WHERE uid_perfil = ". $uidPerfil);
                }
            }*/

            $sql = "DELETE FROM ". TABLE_PERFIL ."_accion WHERE uid_perfil = ". $uidPerfil;
            if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }

            $inserts = array();
            foreach( $datosOpcionesRol as $accion=>$valor ) {
                $inserts[] = "( ". $uidPerfil .", ". $accion ." )";
            }
            $sql = "INSERT INTO ". TABLE_PERFIL ."_accion ( uid_perfil, uid_modulo_accion ) VALUES ". implode(", ",$inserts);
            if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }


            $this->cache->deleteData("getinfo-{$uidPerfil}-perfil-". TABLE_PERFIL ."--");
        
            if( !$result = $this->actualizarVinculacion($uidPerfil, $persistent) ){
                return $result;
            }
            return true;
        } else {
            return false;
        }
    }

    /** VINCULAR/DESVINCULAR UN ROL CON UN PERFIL */
    public function actualizarVinculacion($uidPerfil, $vincular=true){
        $vinculo =  ( $vincular ) ? $this->uid : 0;
        $sql = "UPDATE " . TABLE_PERFIL . " SET rol = $vinculo  WHERE uid_perfil = $uidPerfil";
        if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }
        return true;
    }

    /**
     * Get all the raw action ids
     * @return array
     */
    public function getActions()
    {
        $roleActions    = TABLE_ROL ."_accion";
        $actions        = TABLE_MODULOS ."_accion";

        $sql = "SELECT uid_modulo_accion
        FROM {$roleActions}
        INNER JOIN {$actions}
        USING(uid_modulo_accion)
        WHERE uid_rol = {$this->uid}
        AND activo = 1
        ORDER BY uid_modulo_accion ASC";

        $actions = $this->db->query($sql, "*", 0);

        return $actions;
    }

    /** ACTUALIZARÁ CON LOS DATOS ACTUALES DEL ROL, TODOS LOS PERFILES QUE TENGAN ASIGNADO PERSISTENTEMENTE EL ROL **/
    public function actualizarPerfilesVinculados($cb = NULL) {
        $SQL        = "SELECT uid_modulo_accion FROM ". TABLE_ROL ."_accion WHERE uid_rol = {$this->uid}";
        $actions    = $this->db->query($SQL, "*", 0);
        $actionList = count($actions) ? implode(',', $actions) : '0';


        $SQL        = "SELECT uid_perfil FROM ". TABLE_PERFIL ." WHERE rol = {$this->uid}";
        $list       = $this->db->query($SQL, "*", 0);
        $total      = count($list);
        $updated    = 0;

        if ($list && count($list)) foreach ($list as $i => $uid) {
            $SQLDelete  = "DELETE FROM ". TABLE_PERFIL ."_accion WHERE uid_perfil = {$uid} AND uid_modulo_accion NOT IN ({$actionList})";
            if (!$this->db->query($SQLDelete)) {
                return $this->db->lastErrorString();
            }

            $SQLInsert = "INSERT IGNORE INTO ". TABLE_PERFIL ."_accion (uid_perfil, uid_modulo_accion)
            SELECT {$uid}, uid_modulo_accion FROM ". TABLE_ROL ."_accion WHERE uid_rol = {$this->uid}";


            if (!$this->db->query($SQLInsert)) {
                return $this->db->lastErrorString();
            }

            if (is_callable($cb)) call_user_func($cb, $i, $total);
            $updated++;
        }

        return $updated;
    }



    public function actualizarOpcionesExtra($perfiles=false){
        $campos = $this->obtenerOpcionesExtra();
        $updates = array();
        foreach($campos as $campo => $valor ){
            if( isset($_REQUEST[$campo]) && trim($_REQUEST[$campo]) ){
                $updates[] = $campo . " = 1";
            } else {
                $updates[] = $campo . " = 0";
            }
            $sub = $this->obtenerOpcionesRelacionadas($campo);
            foreach($sub as $i => $subCampo){
                if( isset($_REQUEST[$subCampo["name"]]) && trim($_REQUEST[$subCampo["name"]]) ){
                    $val = ( $_REQUEST[$subCampo["name"]] == "on" ) ? 1 : db::scape($_REQUEST[$subCampo["name"]]);
                    $updates[] = $subCampo["name"] . " = $val";
                } else {
                    $updates[] = $subCampo["name"] . " = 0";
                }
            }
        }

        if( $perfiles === true ){
            $sql = "UPDATE ". TABLE_PERFIL ." SET ". implode(",", $updates) ." WHERE rol = $this->uid";
        } else {
            $sql = "UPDATE $this->tabla SET ". implode(",", $updates) ." WHERE uid_rol = $this->uid";
        }
        return $this->db->query($sql);
    }

    public function obtenerOpcionesExtra(){
        $camposExtra = array();
        $informacionPerfil = $this->getInfo();

        foreach($informacionPerfil as $campo => $valor){
            if( strpos($campo,"config_") !== false ){
                $camposExtra[ $campo ] = $valor;
            }
        }

        return $camposExtra;
    }

    /*MARCOS  *** JOSE: ES LA MISMA FUNCION QUE PERFIL->obtenerOpcionesRelacionadas **** */
    public function obtenerOpcionesRelacionadas($campo){
        $info = $this->getInfo();
        $campoArray = explode("_", $campo);
        $optName = end($campoArray);

        $subCamposExtra = array();
        $camposTabla = $this->getFields();
        foreach($camposTabla as $i => $data ){
            if( $campo != $data["Field"] && stripos($data["Field"], $optName) !== false ){

                // Este campo es una excepcion y no lo hacemos directamente de bbdd
                if( $data["Field"] == "limiteagrupador_modo" ){
                    $subCamposExtra[] = array(
                        "tagName" => "select",
                        "name" => $data["Field"],
                        "value" => $info[$data["Field"]],
                        "options" => array( 
                            0 => "no_limitar",
                            usuario::FILTER_VIEW_EXACTLY => "limiteagrupador_modo_extacto",
                            usuario::FILTER_VIEW_USER => "limiteagrupador_modo_usuario",
                            usuario::FILTER_VIEW_GROUP => "limiteagrupador_modo_agrupador"
                        )
                    );

                // Para cualquier otro campo...
                } else {
                    $subCamposExtra[] = array(
                        "tagName" => "input",
                        "name" => $data["Field"],
                        "value" => $info[$data["Field"]],
                        "type" => "checkbox"
                    );
                }
            }
        }

        return $subCamposExtra;
    }
    /*MARCOS*/

    public function comprobarAccesoOpcion($UIDOpciones){

        if( !is_array($UIDOpciones) ){ $UIDOpciones = array($UIDOpciones); }
        $datosOpciones = $this->obtenerOpcionesDisponibles();
        $datosOpciones = array_map("array_limite_first", $datosOpciones);
        $valid = true;
        foreach( $UIDOpciones as $UIDOpcion ){
            if( !in_array($UIDOpcion, $datosOpciones) ){
                $valid = false;
            }
        }
        return $valid;
    }

    public function obtenerOpcionesDisponibles(){
        $sql = "SELECT uid_modulo_accion as oid, uid_accion, uid_modulo, config, alias, icono, href, string
        FROM ". TABLE_ACCIONES ." INNER JOIN ". TABLE_MODULOS ."_accion USING( uid_accion )
        INNER JOIN ". $this->tabla ."_accion USING( uid_modulo_accion )
        WHERE ( uid_rol = ". $this->getUID() . " AND activo = 1 )";
    
        /*
        if( $this->getUser()->configValue("admin") || $this->getUser()->configValue("sati") ){
            $sql = "SELECT uid_modulo_accion as oid, uid_accion, uid_modulo, config, alias, icono, href, string
            FROM ". TABLE_ACCIONES ." INNER JOIN ". TABLE_MODULOS ."_accion USING( uid_accion ) WHERE activo = 1 ";
        }
        */
        $datos = array();
        $info = $this->db->query( $sql, true );
        foreach( $info as $key => $val ){
            $datos[ $val["oid"] ] = $val;
        }
        
        return $datos;
    }


    public function actualizarOpciones($arrayUIDOpciones){
        $sql = "DELETE FROM ". $this->tabla ."_accion WHERE uid_rol = ". $this->getUID();
        if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }
        if( count($arrayUIDOpciones) ){
            $inserts = array();
            foreach($arrayUIDOpciones as $UIDOpcion){
                $inserts[] = "( ". $this->getUID() .", ". $UIDOpcion ." )";
            }

            $sql = "INSERT INTO ". $this->tabla ."_accion ( uid_rol, uid_modulo_accion ) VALUES ". implode(", ",$inserts);
            if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }
        }

        return true;
    }


    public function getInlineArray(Iusuario $usuarioActivo = NULL, $mode = null , $data = false){
        $inlinearray = array();
        $tpl = Plantilla::singleton();

        if( $this->obtenerDato("empleados") ){
            $empleados = array();
                $empleados["img"] = RESOURCES_DOMAIN . "/img/famfam/asterisk_orange.png";
                $empleados[] = array( "nombre" => $tpl->getString("empleados") );
            $inlinearray[] = $empleados;    
        }

        return $inlinearray;
    }

    public function getRolType(){
        return $this->obtenerDato("tipo");
    }

    public static function crearNuevo($informacion){
        $db = db::singleton();
        $fields = self::publicFields("edit");
        $datos = array_keys( $fields->getArrayCopy() );
        $values = array();

        if (rol::obtenerRolesGenericos($informacion['nombre'])){
            /* Comprobamos que el nomobre del rol genérico no se repite. Los roles genericos son aquellos que no están en la tabla cliente_rol.
           Un rol generico va a tener un nombre único, no vamos a dejar crear roles que tengan un nombre que ya exista. */
            throw new Exception("error_creando_rol_nombre" );
        }

        foreach( $datos as $campo ){
            if( isset($informacion[$campo]) )
                $values[] = "'". utf8_decode(db::scape($informacion[$campo])) ."'";
        }
         
        //Creamos el rol
        $sql = "INSERT INTO ". TABLE_ROL ." ( ". implode(",",$datos) ." ) VALUES (". implode(",",$values) .")";
        if( !$db->query($sql) ){ throw new Exception("error_creando_rol"); }

        return true;
    }

    public static function obtenerTipos(){
        $lang = Plantilla::singleton();

        $tipos = array( 
            rol::TIPO_NORMAL => $lang->getString("rol_tipo_". (string) rol::TIPO_NORMAL ), 
            rol::TIPO_ESPECIFICO => $lang->getString("rol_tipo_". (string) rol::TIPO_ESPECIFICO )
        );
        return $tipos;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        //RECOGEMOS DATOS QUE NOS HACEN FALTA
        // $modo = func_get_args(); $modo = ( isset($modo[0]) ) ? $modo[0] : null;
        $arrayCampos = new FieldList;
        switch( $modo ){
            case elemento::PUBLIFIELDS_MODE_EDIT: case elemento::PUBLIFIELDS_MODE_NEW:
                $arrayCampos["nombre"]      = new FormField( array("tag" => "input", "type" => "text", "blank" => false) );
                $arrayCampos["empleados"]   = new FormField( array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox") );
                $arrayCampos["tipo"]        = new FormField( array("tag" => "select", "data" => self::obtenerTipos()) );
            break;
            default:
                $arrayCampos["nombre"]  = new FormField( array("tag" => "input", "type" => "text", "blank" => false) );
            break;
        }
        return $arrayCampos;
    }

    public static function obtenerRolesGenericos($rolName=false,$tipo=rol::TIPO_NORMAL,$empleados=false){
        /* Si $rolName es False devuelve todos los roles genericos de la applicacion */
        /* Si $rolName es True solo devuelve el rol generico filtrando por nombre.
           Nota, los roles genericos se comparten a lo largo de la aplicación, no vamos a dejar
                  que el administrador cree diferentes roles con el mismo nombre. Simplemente porque 
                  no tiene sentido.
        */
        $db = db::singleton();

        $where = " tipo = $tipo ";
        if( $rolName ) $where .= " AND nombre = '".$rolName."' ";

        if ($empleados === true) $where .= " AND empleados = 1 ";
        elseif ($empleados === false) $where .= " AND empleados = 0 ";

        if ($rolName){
            $SQL = "SELECT uid_rol FROM ". TABLE_ROL ."  WHERE $where LIMIT 1";
            if ($uid = $db->query($SQL, 0, 0)){
                return new rol($uid);
            }

            return false;
        } else {
            $SQL = "SELECT uid_rol FROM ". TABLE_ROL ."  WHERE $where ORDER BY uid_rol ASC";
            $coleccionRoles = $db->query($SQL, "*", 0, "rol");

            return new ArrayObjectList($coleccionRoles);
        }
    }

    public function update($data=false, $fieldsMode=false, Iusuario $usuario = NULL){
        if ( (isset($_REQUEST['nombre']) && $_REQUEST['nombre'] === self::ROL_DEFAULT) || $this->getUserVisibleName() === self::ROL_DEFAULT){
            return false;
        }
        return parent::update($data,$fieldsMode,$usuario);
    }

    public function getTableFields(){
        return array(
            array("Field" => "uid_rol",                 "Type" => "int(11)",        "Null" => "NO",     "Key" => "PRI",     "Default" => "",        "Extra" => "auto_increment"),
            array("Field" => "nombre",                      "Type" => "varchar(150)",   "Null" => "NO",     "Key" => "MUL",     "Default" => "",        "Extra" => ""),
            array("Field" => "tipo",                        "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",       "Extra" => ""),
            array("Field" => "empleados",                   "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",       "Extra" => ""),
            array("Field" => "config_limiteetiqueta",       "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",       "Extra" => ""),
            array("Field" => "config_limiteagrupador",      "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",       "Extra" => ""),
            array("Field" => "limiteagrupador_modo",        "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",       "Extra" => ""),
            array("Field" => "config_limitecliente",        "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",       "Extra" => "")
        );
    }
}
