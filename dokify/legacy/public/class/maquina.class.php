<?php

use Dokify\Infrastructure\Application\Silex\Container;

class maquina extends childItemEmpresa implements Iactivable, Ielemento {

    public function __construct( $param, $saveOnSession = true ){
        $this->tipo = "maquina";
        $this->tabla = TABLE_MAQUINA;
        $this->instance( $param, $saveOnSession );
    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Machine\Machine
     */
    public function asDomainEntity()
    {
        return $this->app['machine.repository']->factory($this->getInfo());
    }

    public static function getRouteName () {
        return 'machine';
    }

    public function getId(){
        return trim($this->obtenerDato('serie'));
    }

    public function getMarkModel(){
        return trim($this->obtenerDato('marca_modelo'));
    }

    public function getPlate(){
        return trim($this->obtenerDato('matricula'));
    }

    /**
     * Get a timestamp of the database field "fabricacion" or false if not exits
     * @return timestamp|boolean The UNIX timestamp or false if not exits
     */
    public function getManufactureTimestamp()
    {
        if (!$dateString = $this->obtenerDato('fabricacion')) {
            return false;
        }

        if ($dateString === '0000-00-00') {
            return false;
        }

        $date   = new DateTime($dateString);

        // check if there is any error
        $errors = DateTime::getLastErrors();
        if ($errors['error_count'] !== 0) {
            return false;
        }

        return $date->getTimestamp();
    }

    /**
      * Devuelve true o false si la empresa pertenece a una corporacion
      **/
    public function perteneceCorporacion(usuario $usuario = NULL){
        $empresa = reset($this->getCompanies());
        if( $empresa instanceof empresa && $empresa->perteneceCorporacion($usuario) ){
            return true;
        }

        return false;
    }

    public function obtenerCarpetas($recursive = false, $level = 0, Iusuario $usuario = NULL){
        return parent::obtenerCarpetas($recursive, $level, $usuario);
    }

    public function getEmployees(usuario $user = NULL){
        return $this->obtenerEmpleados($user);
    }

    public function obtenerEmpleados(usuario $usuario = NULL){
        $sql = "SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_maquina WHERE uid_maquina = $this->uid";
        if( $usuario instanceof usuario ){
            $empresaUsuario = $usuario->getCompany();
            $sql .= " AND uid_maquina IN (
                SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa WHERE papelera = 0
                AND uid_empresa IN ({$empresaUsuario->getStartIntList()->toComaList()})
            )";
        }

        $coleccionEmpleados = $this->db->query($sql, "*", 0, "empleado");

        // TODO aqui hay que añadir un arrayobjectlist cuando compruebe todas las llamadas
        if (is_array($coleccionEmpleados)) return new ArrayObjectList($coleccionEmpleados);

        return new ArrayObjectList;
    }

    /**
     * [defaultData check the serial and fabrication fields]
     * @param  [array] $data    [data used to store the machine]
     * @param  [usuario] $usuario [the user who performs the action]
     * @return [array]          [final data to store]
     */
    public static function defaultData($data, Iusuario $usuario = null)
    {
        if (isset($data['serie']) && maquina::isIdInUse($data['serie']) == true) {
            throw new Exception(_('The serial number is already in use'));
        }

        if (isset($data['fabricacion']) && strpos($data['fabricacion'], '/') !== false) {
            if (!$date = DateTime::createFromFormat('d/m/Y', $data['fabricacion'])) {
                throw new Exception(_('Invalid date'));
            }

            $data['fabricacion'] = $date->format('Y-m-d');
        }

        if (!isset($data['marca_modelo']) || trim($data["marca_modelo"]) == "") {
            throw new Exception(_("Error, brand/model field is required"));
        }

        return $data;
    }

    public function updateData($data, Iusuario $usuario = null, $mode = null)
    {
        if (in_array($mode, [usuario::PUBLIFIELDS_MODE_SYSTEM])) {
            return $data;
        }

        if (true === $usuario->esStaff()
            || $mode !== elemento::PUBLIFIELDS_MODE_EDIT
        ) {
            if (false === isset($data['serie'])) {
                throw new Exception(_("Error, serial number field is required"));
            } else if ($this->getId() != $data['serie'] && true === maquina::isIdInUse($data['serie'])) {
                throw new Exception(_('The serial number is already in use'));
            }
        }

        if ($mode === elemento::PUBLIFIELDS_MODE_EDIT) {
            if (!isset($data['nombre']) || trim($data["nombre"]) == "") {
                throw new Exception(_("Error, name field is required"));
            }

            if (!isset($data['marca_modelo']) || trim($data["marca_modelo"]) == "") {
                throw new Exception(_("Error, brand/model field is required"));
            }
        }

        return $data;
    }

    public static function isIdInUse($id = null)
    {
        if (!isset($id)) {
            return false;
        }

        $SQL = "SELECT count(uid_maquina)
                FROM ". TABLE_MAQUINA ."
                WHERE serie = '". db::scape($id) ."'";

        return db::get($SQL, 0, 0);
    }

    public function isActivable($parent = false, usuario $usuario = NULL){
        return true;
    }

    public function isDeactivable($parent, usuario $usuario){
        return true;
    }

    public function accesiblePara( $usuarioActivo ){
        $empresas = $this->getCompanies();
        foreach( $empresas as $empresa ){
            if( $usuarioActivo->accesoElemento( $empresa ) ){
                return true;
            }
        }
        return false;
    }

    public function getLineClass($parent, $usuario){
        $class = false;
        if( $usuario instanceof usuario ){
            // ---- Informacion de documentos, filtrado por usuario, documentos de subida y obligatorios
            $informacionDocumentos = $this->obtenerEstadoDocumentos($usuario, 0, true);
            $class = $parent->isSuitableItem($this) ? (( count($informacionDocumentos) == 1 && isset($informacionDocumentos[2]) ) ? 'color green':'color red') : 'color black';
        }

        return $class;
    }

    public function isOk($parent, $usuario){
        $class = false;
        if( $usuario instanceof usuario ){
            // ---- Informacion de documentos, filtrado por usuario, documentos de subida y obligatorios
            $informacionDocumentos = $this->obtenerEstadoDocumentos($usuario, 0, true);
            $class = (( count($informacionDocumentos) == 1 && isset($informacionDocumentos[2]) ) ? true : false);
        }

        return $class;
    }


    public function getEmailFor($plantilla){
        return $this->obtenerEmpresaContexto()->obtenerEmailContactos($plantilla);
    }

    /**
        @Override from basic
    */
    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
        $info = parent::getInfo(true);
        $data = $info[ $this->uid ];
        $linedata = array();
        $matricula = empty($data['matricula'])?'':'('.$data['matricula'].') ';
        $longName = $this::getUserVisibleName();
        $linedata["nombre"] =  array(
            "innerHTML" => $longName . " " . $matricula. $data["marca_modelo"],
            "href" => "ficha.php?m=maquina&poid=". $this->uid,
            "className" => "box-it link",
        );

        return array($this->getUID() => $linedata);
    }


    public static function cronCall($time, $force = false, $tipo = NULL){
        return true;
    }


    public static function getSearchData(Iusuario $usuario, $papelera = false, $all = false, $network = true, $cache = true){
        $searchData = array();
        if (!$usuario->accesoModulo(__CLASS__)) return false;

        $empresa = $usuario->getCompany();
        $limit   = "1";


        if ($papelera) {
            $intList    = $empresa->getStartIntList();
            $limit      .= " AND uid_maquina IN (SELECT me.uid_maquina FROM ". TABLE_MAQUINA ."_empresa me WHERE me.papelera = 1 AND me.uid_empresa IN ({$intList}))";
        } elseif (!$all) {
            $viewIndexRepository = Container::instance()['index.repository'];

            $userDomainEntity = $usuario->asDomainEntity();
            $companyDomainEntity = $empresa->asDomainEntity();

            $strict = $cache ? false : true;
            if (true === $strict) {
                $viewIndexRepository->expireIndexOf(
                    'maquina',
                    $companyDomainEntity,
                    $userDomainEntity,
                    $network
                );
            }

            $indexList = (string) $viewIndexRepository->getIndexOf(
                'maquina',
                $companyDomainEntity,
                $userDomainEntity,
                $network
            );

            $limit .= " AND uid_maquina IN ({$indexList})";
        }



        if( $usuario->isViewFilterByGroups() ){
            // Es posible que nos pasen un perfil por parámetro
            if( $usuario instanceof perfil ) $usuario = $usuario->getUser();

            $userCondition = $usuario->obtenerCondicion(false, "uid_maquina");
            $limit .= " AND uid_maquina IN ($userCondition)";
        }

        $searchData[ TABLE_MAQUINA ] = array(
            "type" => "maquina",
            "fields" => array("nombre", "serie", "matricula"),
            "limit" => $limit,
            "accept" => array(
                "tipo" => "maquina",
                "uid" => true,
                "docs" => true,
                "empleado" => true,
                "empresa" => true,
                "list" => true,
                "created" => true
            )
        );

        $searchData[TABLE_MAQUINA]['accept']['own'] = array(__CLASS__, 'onSearchByOwn');
        $searchData[TABLE_MAQUINA]['accept']['empresa'] = array(__CLASS__, 'onSearchByCompany');
        $searchData[TABLE_MAQUINA]['accept']['completed'] = array(__CLASS__, 'onSearchByCompleted');
        $searchData[TABLE_MAQUINA]['accept']['asignado'] = array(__CLASS__, 'onSearchByAsignado');
        $searchData[TABLE_MAQUINA]['accept']['docs'] = array('maquina', 'onSearchByDocs');
        $searchData[TABLE_MAQUINA]['accept']['estado'] = array('maquina', 'onSearchByStatus');

        $searchData[ TABLE_MAQUINA ]['accept']['empleado'] = function($data, $filter, $param, $query){
            $value = reset($filter);

            $employeeMachines = TABLE_EMPLEADO ."_maquina";
            if (is_numeric($value)) {
                $SQL = " uid_maquina IN (SELECT uid_maquina FROM {$employeeMachines} WHERE uid_empleado = $value) ";
            } else {
                $employees = TABLE_EMPLEADO;
                $value = db::scape($value);

                $SQL = " uid_maquina IN (
                    SELECT uid_maquina FROM {$employeeMachines}
                    INNER JOIN {$employees}
                    USING (uid_empleado)
                    WHERE (
                        concat(nombre, ' ', apellidos) LIKE '%{$value}%'
                        OR dni LIKE '%{$value}%'
                    )
                ) ";
            }
            return $SQL;
        };

        return $searchData;
    }

    public static function getExportSQL($usuario, $uids, $forced, $parent=false){
        $campos = array();
        if( $usuario->esStaff() ){
            $campos[] = "uid_maquina";
        }

        $campos[] = "serie";
        $campos[] = "nombre";

        $sql =  "SELECT ". implode(",", $campos) ." FROM ". TABLE_MAQUINA ." WHERE 1";

        if( is_array($uids) && count($uids) ){
            $sql .=" AND uid_maquina in (".implode(",", $uids ).")";
        }

        if( is_array($forced) ){
            $list = ( count($forced) ) ? implode(",", $forced ) : 0;
            $sql .=" AND uid_maquina in ($list)";
        }

        if( is_numeric($parent) ){
            $sql .=" AND uid_maquina IN (
                SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa
                WHERE uid_empresa = $parent
            )";
        }

        return $sql;
    }


    /**
    * RETORNA LOS ELEMENTOS QUE "TIPICAMENTE" MOSTRAMOS DE ESTE ELEMENTO PARA VER EN MODO INLINE
    * @param = $usuarioActivo, debe ser el objeto usuario logeado actualmente, para filtrar si es necesario
    */
    public function getInlineArray(Iusuario $usuario = NULL, $config = false, $data = NULL){
        $dataString = is_array($data) ? json_encode($data) : false;
        $cacheKey = implode('-', array($this, __FUNCTION__, $usuario->obtenerPerfil(), $config, $dataString));
        if (($value = $this->cache->get($cacheKey)) !== NULL) return json_decode($value, true);


        $inlineArray = array();

        //------------- INFORMACIÓN RÁPIDA DE LOS DOCUMENTOS
        if( $usuario instanceof usuario ){

            $inlineArray[] = parent::getDocsInline($usuario);
            if( $config !== "home" ){
                $inlineArray["1"] = array();
                $inlineArray["1"]["img"] = RESOURCES_DOMAIN . "/img/famfam/sitemap_color_inverse.png";
                $empresasEmpleado = $this->getCompanies(false, $usuario);
                foreach( $empresasEmpleado as $empresaEmpleado ){
                    $inlineArray["1"][] = array(
                        "nombre" => $empresaEmpleado->getUserVisibleName(),
                        "img" => $empresaEmpleado->getStatusImage($usuario),
                        "href"  => $empresaEmpleado->obtenerUrlFicha(),
                        "className" => $empresaEmpleado->getLineClass($empresaEmpleado, $usuario)
                    );
                }


                if( isset($data["search"]) && strpos($data["search"],"created:") !== false ){
                    $tpl = Plantilla::singleton();

                    $inlineArray["2"] = array(
                        "img" => RESOURCES_DOMAIN . "/img/famfam/clock.png",
                        "title" => $tpl("creado_en"),
                        array( "nombre" => $this->getCreationDate() )
                    );
                }
            }
        }

        $this->cache->set($cacheKey, json_encode($inlineArray), 60*60*15);
        return $inlineArray;
    }

    /**
    * Devuelve la información que se muestra en la ficha de empleado.
    */
    public function getMiniArray(Iusuario $usuario = null) {
        $miniArray = array();
        $miniArray['nombre'] = $this->getUserVisibleName();
        $miniArray['href'] = $this->obtenerUrlFicha();
        $miniArray['inlineArray'] = reset($this->getInlineArray($usuario)); // solo nos interesa el primer elemento
        $miniArray['hrefdocs'] = '#documentos.php?m=maquina&poid='.$this->getUID();
        $miniArray['imgdocs'] = RESOURCES_DOMAIN . "/img/famfam/folder.png";
        $miniArray['estado'] = $this->getStatusImage($usuario);
        return $miniArray;
    }


    // public function desasignarEmpresa( $empresa ){
    //  if ( $empresa instanceof empresa ) { $empresa = $empresa->getUID(); }
    //  return $this->eliminarRelacion( $this->tabla."_empresa", "uid_empresa", $empresa, "uid_".$this->tipo );
    // }

    public function removeParent(elemento $parent, usuario $usuario = null) {
        if ( $parent instanceof empresa ) {
            return $this->eliminarRelacion( $this->tabla."_empresa", "uid_empresa", $parent->getUID(), "uid_".$this->tipo );
        }
    }


    public function asignarEmpresa( $empresa ){
        $sql = "INSERT INTO ". $this->tabla ."_empresa ( uid_empresa, uid_maquina ) VALUES (
            ". $empresa->getUID() .", ". $this->getUID() ."
        )";
        return $this->db->query($sql);
    }

    /**
        LA VERSION DEL SERVIDOR ESTA ARROJANDO UN PROBLEMA CON ESTA FUNCION
        ASI QUE LA COPIO DESDE LA CLASE EMPLEADO TAL CUAL PARA CHEQUEAR SI FUNCIONA
    */

    public function obtenerDistancia($empresa=false, $toString=false, $inferiores=false,  $process=0 ){
        if( $empresa instanceof empresa ){
            $empresas = $this->getCompanies();

            //si solo hay una empresa, sin problema
            if( count($empresas) > 1 ){
                //si hay mas, ya veremos
                //buscamos si hay una empresa activa
                if( isset($_SESSION["OBJETO_EMPRESA"]) ){
                    //rescatamos el objeto de la empresa activa
                    $empresaActiva = unserialize($_SESSION["OBJETO_EMPRESA"]);
                    //comparamos si es una de las empresas del empleado actual
                    foreach($empresas as $empresaMaquina){
                        if( $empresaActiva->getUID() == $empresaMaquina->getUID() ){
                            return $empresaMaquina->obtenerDistancia($empresa, $toString, false);
                        }
                    }
                }
            }

            //si no resultan concordancias, o bien solo hay una empresa o bien nos vemos obligados a tomar la primera
            //esto puede tener consecuencias de solicitud de documentos no deseadas, por lo tanto, mejor retornamos false
            if( count($empresas) == 1 ){
                $empresaMaquina = reset($empresas);
                return $empresaMaquina->obtenerDistancia($empresa, $toString, false);
            } else {
                return false;
            }
        } elseif( $empresa instanceof agrupador ){
            $agrupamiento = reset( $empresa->obtenerAgrupamientosContenedores() ); // tomamos el inicial
            $empresa = reset($agrupamiento->getEmpresasClientes()); //tomamos el inicial de nuevo
            return $this->obtenerDistancia( $empresa, $toString, $inferiores,  $process );
        } else {
            //buscamos la distnacia con nuestra empresa actual
            $usuario = usuario::getCurrent();
            if( $usuario instanceof usuario ){
                return $this->obtenerDistancia( $usuario->getCompany(), $toString, $inferiores,  $process );
            } else {
                return empresa::DEFAULT_DISTANCIA;
            }
        }

        return null;

    }


    /** NOS INDICA DONDE BUSCAR LAS EMPRESAS SUPERIORES **/
    static public function getParentTable(){
        return TABLE_MAQUINA . "_empresa";
    }
    /** NOS INDICA EL CAMPO QUE REPRESENTA LA EMPRESA EN LA TABLA DE RELACIONES **/
    static public function getParentRelationalField(){
        return "uid_maquina";
    }
    /** NOS INDICA EL CAMPO QUE REPRESENTA LA EMPRESA EN LA TABLA DE RELACIONES **/
    static public function getParentTableRelationalField(){
        return "uid_empresa";
    }


    // public function triggerAfterCreate($usuario, $item) {
    //  header("Location: ../seleccionarcliente.php?m={$item->getType()}&poid={$item->getUID()}&comefrom=nuevo");
    // }

    public static function importFromFile($file, $empresa, $usuario, $post = null)
    {
        // Objeto database
        $db = db::singleton();

        // Importamos los elementos a la tabla
        $results = self::importBasics($usuario,$file, "maquina", "serie");

        if( count($results["uids"]) ){
            if( isset($results["parents"]) ){
                foreach($results["parents"] as $parentID => $list ){
                    $sql = "SELECT count(uid_empresa) FROM ". TABLE_EMPRESA ." WHERE uid_empresa = " . db::scape($parentID);
                    $num = (int) $db->query($sql, 0, 0);

                    if( $num === 1 ){
                        //Relacionamos los elementos con nuestra empresa
                        $sql = "INSERT IGNORE INTO ". TABLE_MAQUINA ."_empresa ( uid_empresa, uid_maquina )
                        SELECT $parentID, uid_maquina FROM ". TABLE_MAQUINA ." WHERE uid_maquina IN (". implode(",", $list) .")
                        ";

                        if( !$db->query($sql) ){
                            throw new Exception( "Error al tratar de relacionar" );
                        }
                    } else {
                        if( !isset($results["parent_not_foud"]) ){ $results["parent_not_foud"] = array(); }
                        $results["parent_not_foud"][] = "#$parentID";
                    }
                }

                if( isset($results["parent_not_foud"]) ){ $results["parent_not_foud"] = implode(", ", $results["parent_not_foud"]); }

                return $results;
            } else {
                //Relacionamos los elementos con nuestra empresa
                $sql = "INSERT IGNORE INTO ". TABLE_MAQUINA ."_empresa ( uid_empresa, uid_maquina )
                SELECT ". $empresa->getUID() .", uid_maquina FROM ". TABLE_MAQUINA ." WHERE uid_maquina IN (". implode(",", $results["uids"]) .")
                ";
                if( $db->query($sql) ){
                    return $results;
                } else {
                    throw new Exception( "Error al tratar de relacionar" );
                }
            }
        } else {
            throw new Exception( "No hay elementos para relacionar" );
        }
    }


    /***
       * Just for compatibility with employees
       *
       *
       *
       */
    public function getImage ()
    {
        $path = "/img/icons/34/drill.png";

        if (CURRENT_ENV == 'dev') {
            return CURRENT_DOMAIN . "/assets/dev/" . $path;
        }

        return WEBCDN . $path;
    }


    public function  getUserVisibleName($short = false){
        /*
         * if $short = true: returns the shortname (only "name")
         * else it returns the concat. of name (if present and serial) separated with an '-'
         * */
        $datos = $this->getInfo();

        if ($short === true) {
            return $datos["nombre"];
        }

        $name = '';

        if($datos['nombre']){
            $name = $datos['nombre'];
        }
        if($datos['serie']){
            if($name !=''){
                $name = $name . " - ";
            }
            $name = $name . $datos['serie'];
        }
        return $name;
    }

    public static function getFromSerial($serial)
    {
        $db = db::singleton();

        $sql = "SELECT uid_maquina FROM ". TABLE_MAQUINA ."
                WHERE serie LIKE '{$serial}'
        ";

        $uid = $db->query($sql, 0, 0);

        if ($uid) return new maquina($uid);

        return false;
    }

    public function obtenerAgrupamientosEmpresaMaquina($elemento, $usuario){
        $agrupamientos = $elemento->obtenerAgrupamientos($usuario);
        return $agrupamientos;
    }

    public static function optionsFilter($uid, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){
        $condiciones = array();

        if( $condicionesEmpleado = empleado::optionsFilter($uid, $uidmodulo, $user, $publicMode, $config, $tipo, $parent) ){
            $condiciones[] = substr($condicionesEmpleado, 3); // eliminamos el AND ...
        }

        if( $tipo == 3 && !$user->esStaff() ){
            $empresaUsuario = $user->getCompany();
            $empresaCorp = $empresaUsuario->esCorporacion() && $empresaUsuario->obtenerEmpresasInferiores()->contains($parent) ? TRUE : FALSE;

            // 22:crear, 23:verpapelera
            $reservadas = "22, 23";
            if( !$empresaUsuario->compareTo($parent) && !$empresaCorp ){
                // No se pueden crear elementos ...
                $condiciones[] = " ( uid_accion NOT IN ($reservadas) ) ";
            }
        }


        if( count($condiciones) ){
            return "AND " . implode(" AND ", $condiciones);
        }

        return false;
    }

    static public function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        if( isset($_SESSION) && !($usuario instanceof usuario) ){
            $usuario = usuario::getCurrent();
        }
        if( $usuario instanceof usuario ){
            $camposExtra = $usuario->getCompany()->obtenerCamposDinamicos(14);
        }


        $arrayCampos = new FieldList;
        $arrayCampos["serie"]           = new FormField(array("tag" => "input",     "type" => "text", "blank" => false ));
        $arrayCampos["nombre"]          = new FormField(array("tag" => "input",     "type" => "text", "blank" => false ));
        $arrayCampos["marca_modelo"]    = new FormField(array("tag" => "input",     "type" => "text" ));
        $arrayCampos["fabricacion"]     = new FormField(array("tag" => "input",     "type" => "text", "className" => "datepicker", "size" => 20 ));

        if( $modo != elemento::PUBLIFIELDS_MODE_SEARCH ){
            $arrayCampos['matricula'] = new FormField(array('tag' => 'input', 'type' => 'text'));
            // alter table `agd_data`.`maquina` add `matricula` varchar(100) default null after `serie`
        }

        switch( $modo ){
            case elemento::PUBLIFIELDS_MODE_EDIT:
                if ($usuario instanceof usuario && false === $usuario->esStaff()) {
                    $arrayCampos->offsetUnset("serie");
                }
                // Don't use break command! It is necessary to execute the same code as in the creation.
            case elemento::PUBLIFIELDS_MODE_NEW:
                if (isset($camposExtra) && is_traversable($camposExtra) && count($camposExtra)) {
                    foreach($camposExtra as $campoExtra){
                        $arrayCampos[ $campoExtra->getFormName() ] = new FormField(array(
                            "tag" => $campoExtra->getTag(),
                            "type" => $campoExtra->getFieldType(),
                            "uid_campo" => $campoExtra->getUID(),
                            "data" => $campoExtra->getData()
                        ));
                    }
                }
            break;

            case elemento::PUBLIFIELDS_MODE_SYSTEM:
                $arrayCampos['updated']= new FormField(array());
                return $arrayCampos;
            break;
        }



        if ($modo == elemento::PUBLIFIELDS_MODE_NEW || $modo === elemento::PUBLIFIELDS_MODE_TAB ) {
            $arrayCampos['created'] = new FormField(array("date_format" => "%d/%m/%Y"));
        }

        return $arrayCampos;
    }

    public function getTableFields(){
        return array(
            array("Field" => "uid_maquina", "Type" => "int(10)",        "Null" => "NO",     "Key" => "PRI", "Default" => "",    "Extra" => "auto_increment"),
            array("Field" => "serie",           "Type" => "varchar(100)",   "Null" => "NO",     "Key" => "UNI", "Default" => "",    "Extra" => ""),
            array("Field" => "matricula",       "Type" => "varchar(100)",   "Null" => "YES",    "Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "nombre",          "Type" => "varchar(100)",   "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "marca_modelo",    "Type" => "varchar(500)",   "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "fabricacion",     "Type" => "date",       "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "updated",     "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "created",     "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => "")
        );
    }
}
