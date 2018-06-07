<?php

use Dokify\Infrastructure\Application\Silex\Container;

class empleado extends childItemEmpresa implements Ielemento, Iactivable, Iusuario, Irequestable {

    const PERIODICIDAD_CONFORMIDAD = 10;
    // TIPOS DE DOCUMENTO DE IDENTIDAD
    const ID_NIF = 1;
    const ID_NIE = 6;
    const ID_NIX = 6;
    const ID_NIS = 6;
    const ID_PAS = 2;
    // SITUACIONES PROFESIONALES
    const SITUACION_ASALARIADO_PRIVADO = 1;
    const SITUACION_ASALARIADO_PUBLICO = 2;
    const SITUACION_AUTONOMO_CON_ASALARIADOS = 3;
    const SITUACION_AUTONOMO_SIN_ASALARIADOS = 4;
    // VALORES ESPECIALES PARA HORAS DE LA JORNADA
    const JORNADA_YENDO = 0;
    const JORNADA_VOLVIENDO = 99;


    // --- Duración minima que asumimos que un empleado estará en un centro de trabajo
    const MINIMUM_PLACE_STAY = 60;
    // --- Duración máxima que asumimos que un empleado estará en un centro de trabajo
    const MAXIMUM_PLACE_STAY = 43200; // 12 hors

    // default image name
    const DEFAULT_IMAGE_NAME = 'sil';



    // clave para usar junto al buscador con near:someone
    const RESERVED_NEAR_SOMEONE = 'someone';

    public function __construct( $param, $saveOnSession = false ){
        $this->tipo = "empleado";
        $this->tabla = TABLE_EMPLEADO;
        $this->instance( $param, $saveOnSession );
    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Employee\Employee
     */
    public function asDomainEntity()
    {
        $info = $this->getInfo();

        $checkinDate = ($tstmp = $info['location_timestamp']) ? new DateTime($tstmp) : null;

        if (is_numeric($checkin = $info['active_uid_checkin'])) {
            $checkin = new \Dokify\Domain\Employee\Checkin\CheckinUid($checkin);
        } else {
            $checkin = null;
        }

        // Instance the entity
        $entity = new \Dokify\Domain\Employee\Employee(
            new \Dokify\Domain\Employee\EmployeeUid($this->getUID()),
            $info['dni'],
            $info['nombre'],
            $info['apellidos'],
            $info['email'],
            $info['path_photo'],
            $checkinDate,
            (int) $info['uid_usuario_location'],
            $info['latlng'],
            $checkin
        );

        return $entity;
    }

    public static function getRouteName () {
        return 'employee';
    }


    /***
       * Return the app version number
       *
       *
       *
       * return Int
       *
       */
    public function getAppVersion () {
        return 1;
    }


    /**
     * [Tell us if the employee can do the @option over the @item (@parent is aux)]
     * @param  [mixed] $item
     * @param  [string|int] $option
     * @param  [object] $parent
     * @return [bool]
     */
    public function canAccess ($item, $option = \Dokify\AccessActions::VIEW, $parent = null)
    {
        if ($item instanceof documento) {
            if ($parent instanceof solicitable) {
                $item->elementoFiltro = $parent;
            } else {
                $item->elementoFiltro = null;
            }
        } elseif ($item instanceof solicituddocumento) {
            if ($item->isEditableBy($this)) {
                $item = $item->obtenerDocumento();
            } else {
                return false;
            }
        }

        // with this "hack" we can use $parent for test different config value
        $config = null;
        if (is_bool($parent)) {
            $config = (int) $parent;
        }


        $options = $this->getAvailableOptionsForModule($item, $option, $config, null, $parent);

        if ($options && count($options)) {

            // for view actions, check access too
            if ($option == \Dokify\AccessActions::VIEW && $item instanceof elemento) {
                return $this->accesoElemento($item);
            }

            return true;
        }

        return false;
    }


    /***
       *
       *
       *
       *
       *
       */
    public function setLocationUser (usuario $usuario = NULL) {
        $location = $usuario instanceof usuario ? $usuario->getUID() : "0";
        $time = $usuario instanceof usuario ? "NOW()" : "NULL";
        $latLng = ($usuario instanceof usuario && $userLatLang = $usuario->getLatLng()) ? "'{$userLatLang}'" : "NULL";
        $SQL = "
            UPDATE {$this->tabla}
            SET
                uid_usuario_location = {$location},
                location_timestamp = {$time},
                latlng = {$latLng}
            WHERE
                uid_empleado = {$this->getUID()}
        ";


        return $this->db->query($SQL);
    }


    /***
       *
       *
       *
       *
       *
       */
    public function getLocationUser () {
        $SQL = "SELECT uid_usuario_location FROM {$this->tabla} WHERE uid_empleado = {$this->getUID()}";

        if ($uid = $this->db->query($SQL, 0, 0)) {
            return new usuario($uid);
        }

        return false;
    }


    /***
       *
       *
       *
       *
       *
       */
    public function getLocationTimestamp () {
        $SQL = "SELECT UNIX_TIMESTAMP(location_timestamp) FROM {$this->tabla} WHERE uid_empleado = {$this->getUID()}";

        if ($time = $this->db->query($SQL, 0, 0)) {
            return $time;
        }

        return false;
    }


    /***
       *
       *
       *
       *
       *
       */
    public function setLatLng ($location) {
        $location = db::scape($location);
        $data = array("latlng" => $location, "location_timestamp" => date('Y-m-d h:i:s'), 'uid_usuario_location' => 'NULL');

        return $this->update($data, elemento::PUBLIFIELDS_MODE_GEO, $this);
    }

    /***
       *
       *
       *
       *
       *
       */
    public function getBirthDate () {
        $birth      = $this->obtenerDato('fecha_nacimiento');

        if ($birth == "0000-00-00") {
            return false;
        }

        $birthDate  = date_create($birth);

        return date_format($birthDate, "d/m/Y");
    }


    /***
       *
       *
       *
       *
       *
       */
    public function getLatLng () {
        $latLng = $this->obtenerDato('latlng');
        return $latLng;
    }


    public function getAddress () {
        return trim($this->obtenerDato('direccion'));
    }


    public function getCookieToken() {
        return $this->obtenerDato('password');
    }

    public static function instanceFromCookieToken($username, $md5pass) {
        $db = db::singleton();
        $sql = "
            SELECT uid_empleado FROM ". TABLE_EMPLEADO ."
            WHERE dni ='". db::scape($username) ."'
            AND password ='". db::scape($md5pass) ."'
        ";

        $uidUsuario = $db->query($sql,0,0);
        if ($uidUsuario) return new self($uidUsuario);

        return false;
    }


    public function obtenerPerfil() {
        return $this->getCompany();
    }

    public function getEmail(){
        return trim($this->obtenerDato('email'));
    }

    public static function isEmailInUse($email, $exclude = NULL) {
        $SQL = "SELECT count(uid_empleado) FROM ". TABLE_EMPLEADO ." WHERE email = '". db::scape($email) ."'";

        if ($exclude instanceof empleado) $SQL .= " AND uid_empleado != {$exclude->getUID()}";

        return db::get($SQL, 0, 0);
    }

    public static function isIdInUse($id = null)
    {
        if (!isset($id)) {
            return false;
        }

        $SQL = "SELECT count(uid_empleado)
                FROM ". TABLE_EMPLEADO ."
                WHERE dni = '". db::scape($id) ."'";

        return db::get($SQL, 0, 0);
    }

    public function getId(){
        return trim($this->obtenerDato('dni'));
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

    /***
       * Returns if this employee is working for a non-free company
       *
       * @return bool - whether this employee is working for a non-free company or not
       *
       */
    public function isInLicensedCompany () {
        $companies = $this->getCompanies();
        foreach ($companies as $company) {
            if ($company->isFree() === false) {
                return true;
            }
        }

        return false;
    }

    public function maxUploadSize($size=false, $reset=false) {
        if ($this->isInLicensedCompany()) {
            return usuario::DEFAULT_UPLOAD;
        }

        return usuario::LIMITED_UPLOAD;
    }

    /***
        Método flexible que permite añadir excepciones de visualización de elementos
    **/
    public function canView($item, $context, $extraData = NULL){
        $fn = array($item, 'canViewBy');
        if( is_callable($fn) ){
            return call_user_func($fn, $this, $context, $extraData);
        }
        return false;
    }

    public function obtenerCarpetas($recursive = false, $level = 0, Iusuario $usuario = NULL){
        return parent::obtenerCarpetas($recursive, $level, $usuario);
    }


    public function estaDeBaja(){
        $bajas = $this->obtenerBajas();
        foreach($bajas as $baja){
            if( $baja->isActive() ){
                return true;
            }
        }
        return false;
    }

    public function obtenerProvincia(){
        if( $uid = $this->obtenerDato("uid_provincia") ){
            return new provincia($uid);
        }
        return false;
    }

    public function obtenerPais() {
        if ($uid = $this->obtenerDato("uid_pais")) {
            return new pais($uid);
        }
        return false;
    }

    public function obtenerTipoContrato(){
        if( $uid = $this->obtenerDato("uid_tipocontrato") ){
            return new tipocontrato($uid);
        }
        return false;
    }

    public function obtenerCodigoOcupacion(){
        if( $uid = $this->obtenerDato("uid_codigoocupacion") ){
            return new tipocontrato($uid);
        }
        return false;
    }

    public function obtenerMunicipio(){
        if( $uid = $this->obtenerDato("uid_municipio") ){
            return new municipio($uid);
        }
        return false;
    }

    public function obtenerBajas($count = false){
        if( $count ){
            return $this->obtenerConteoRelacionados( TABLE_BAJA, "uid_empleado", "uid_baja");
        } else {
            $items = $this->obtenerObjetosRelacionados( TABLE_BAJA, "baja", false, "fecha_inicio DESC");
            return new ArrayObjectList($items);
        }
    }

    public function obtenerConvocatoriaMedicas($count = false){
        if( $count ){
            return $this->obtenerConteoRelacionados(  TABLE_CONVOCATORIA_MEDICA, "uid_empleado", "uid_convocatoriamedica" );
        } else {
            $items = $this->obtenerObjetosRelacionados( TABLE_CONVOCATORIA_MEDICA, "convocatoriamedica", false, "fecha_creacion DESC ");
            return new ArrayObjectList($items);
        }
    }

    public function obtenerAccidentes($count = false){
        if( $count === true){
            return $this->obtenerConteoRelacionados( TABLE_ACCIDENTE, "uid_empleado", "uid_accidente" );
        } else {
            $items = $this->obtenerObjetosRelacionados( TABLE_ACCIDENTE, "accidente", false, "fecha_accidente DESC ");
            return new ArrayObjectList($items);
        }
    }

    public function getSignUpDate(){
        if ($date = $this->obtenerDato('created')) {
            return date('d-m-Y', $date);
        }

        return false;
    }

    public function obtenerAntiguedad($hasta = false) {
        if (!$hasta) {
            $hasta = new DateTime('now');
        } else if (!($hasta instanceof DateTime)) {
            // generalmente usaremos el valor de $accidente->obtenerDato('fecha_accidente');

            $hasta = DateTime::createFromFormat('Y-m-d',$hasta);
        }
        if ($fecha_alta = $this->obtenerDato('fecha_alta_empresa')) { // Y-m-d
            $alta = DateTime::createFromFormat('Y-m-d', $fecha_alta);
            $antiguedad = $alta->diff($hasta,true);
            return $antiguedad;
            /**
            * $antiguedad es un objeto DateInterval.
            * Se puede obtener ya calculado en meses y dias con
            * $antiguedad->m y $antiguedad->d
            * pero este resultado puede ser 0 días si el número de meses es exacto.
            * con $antiguedad->days se obtiene el numero absoluto de días
            */
        }
        return null;
    }

    public function obtenerCentrocotizacion(){
        if( $uid = $this->obtenerDato("uid_centrocotizacion") ){
            return new centrocotizacion($uid);
        }
        return false;
    }

    public function obtenerCondicionDocumentosView($modulo = NULL){
        if ($this->isManager() && $empleados = $this->obtenerEmpleados() ) {
            $condicion = " AND uid_empleado IN ( "  .$empleados->merge($this)->toComaList(). " ) ";
        } else $condicion = " AND uid_empleado = ".$this->getUID();
        return $condicion;
    }

    public function enviarPapelera($parent, usuario $usuario){
        if ($this->hasPendingRequests($parent)) {
            $transferencias = $this->obtenerSolicitudesEmpresa($parent, array('estado' => solicitud::ESTADO_CREADA, 'type' => solicitud::TYPE_TRANSFERENCIA));
            $transferencias->foreachCall('cancelar',array($usuario));
            $sugerencias = $this->obtenerSolicitudesEmpresa($parent, array('estado' => solicitud::ESTADO_CREADA, 'type' => solicitud::TYPE_ASIGNAR));
            $sugerencias->foreachCall('cancelar',array($usuario));
        }

        return parent::enviarPapelera($parent, $usuario);
    }

    public function obtenerSolicitudesEmpresa(elemento $parent = null, $filter = null) {
        return solicitud::getFromItem('empresa',$this, $parent, $filter);
    }

    public function hasPendingRequests(elemento $parent = null) {
        return (bool) solicitud::getFromItem('empresa',$this,$parent,array('estado'=>empresasolicitud::ESTADO_CREADA),true);
    }

    /*
     * Solicitará a las empresas donde este este empleado la transferencia del mismo
     *
     **/
    public function solicitarTransferenciaEmpresa( empresa $empresaDestino, Iusuario $usuario ) {
        $empresasActuales = $this->getCompanies();
        $empresasSolicitadas = $solicitudesActuales = solicitud::getFromItem('empresa',$this,null,array(
            'estado' => solicitud::ESTADO_CREADA,
            'type' => solicitud::TYPE_TRANSFERENCIA,
            'uid_empresa_origen' => $empresaDestino->getUID(),
        ));

        if ($solicitudesActuales) {
            $empresasSolicitadas = $solicitudesActuales->transform('empresa');
        }

        $return = false;

        $filter = array(
            'estado' => solicitud::ESTADO_CREADA,
            'type' => solicitud::TYPE_TRANSFERENCIA,
            'uid_empresa_origen' => $empresaDestino->getUID(),
            'uid_empleado' => $this->getUID()
        );
        $solicitudesDirectasEmpleado = solicitud::getFromItem('empleado',$this, null, $filter);

        if ($email = $this->getEmail()) {
            if (!count($solicitudesDirectasEmpleado) && (!count($solicitudesActuales))) {
                $data = array(
                    // esta empresa es la que origina la solicitud, aunque es la empresa destino en la que finalmente terminará el empleado
                    'uid_empresa_origen' => $empresaDestino->getUID(),
                    'uid_usuario' => $usuario->getUID(),
                    'uid_modulo' => $this->getModuleId(),
                    'uid_elemento' => $this->getUID(),
                    'type' => solicitud::TYPE_TRANSFERENCIA,
                    'estado' => solicitud::ESTADO_CREADA,
                    'uid_empleado' => $this->getUID(),
                    'token' => md5(rand())
                );
                $empleadoSolicitud = new empleadosolicitud($data,$usuario);
                $empleadoSolicitud->sendTransferEmployeeRequest();
                $return = 'empleadosolicitud_transfer';
            } else {
                $return = 'error_solicitudes_repetidas';
            }
        } else {
            $data = array(
                // esta empresa es la que origina la solicitud, aunque es la empresa destino en la que finalmente terminará el empleado
                'uid_empresa_origen' => $empresaDestino->getUID(),
                'uid_usuario' => $usuario->getUID(),
                'uid_modulo' => $this->getModuleId(),
                'uid_elemento' => $this->getUID(),
                'type' => solicitud::TYPE_TRANSFERENCIA,
                'estado' => solicitud::ESTADO_CREADA
            );
            if (!count($solicitudesDirectasEmpleado)) {
                foreach ($empresasActuales as $empresaSolicitud) {
                    // comprobamos que no exista una solicitud abierta igual que la que vamos a crear
                    if (!$empresasSolicitadas->contains($empresaSolicitud)) {
                        $data['uid_empresa'] = $empresaSolicitud->getUID();
                        $solicitud = new empresasolicitud($data,$usuario);
                        if (!$solicitud instanceof empresasolicitud) {
                            return false;
                        }
                        $solicitud->sendTransferEmployeeRequest();
                    } else {
                        $return = 'error_solicitudes_repetidas';
                    }
                }
            } else {
                $return = 'error_solicitudes_repetidas';
            }

        }
        return ($return ? $return : true);
    }


    public function isActivable($parent = false, usuario $usuario = NULL){
        $sql = "SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa WHERE papelera = 0 AND uid_empleado = {$this->getUID()}";
        return (bool) !$this->db->query($sql, 0, 0);
    }

    public function isDeactivable($parent, usuario $usuario){
        return true;
    }

    public function needsConfirmationBeforeTrash($parent, usuario $usuario) {
        $tpl = Plantilla::singleton();
        $empresas = $this->getCompanies();

        if (count($empresas->discriminar($parent))>0) {
            return $tpl->getString('aviso_papelera_otras_empresas');
        }

        if ($this->hasPendingRequests($parent)) {
            return $tpl->getString('confirmar_enviar_papelera_transferencia');
        }

        return false;
    }

    public function obtenerEmpleados($params=true){
        $unit = $this->getUnitWork();
        if( $unit ){
            $units = $unit->getChilds($this);
            $empleados = $unit->obtenerEmpleados(false);

            foreach($units as $unit){
                $empleadosUnidad = $unit->obtenerEmpleados(true, $this);
                if( !count($empleadosUnidad) ){
                    $empleadosUnidad = $unit->obtenerEmpleados(null, $this);
                }

                if( count($empleadosUnidad) ){
                    $empleados = $empleados->merge($empleadosUnidad);
                }
            }

            return $empleados;
        }

        return false;
    }

    public function getTreeData(Iusuario $usuario){
        if( $usuario instanceof usuario ) return false;
        $img = $imgopen = RESOURCES_DOMAIN . "/img/famfam/user_green.png";
        $url = false;

        if( $this->isManager() ){
            $unit = $this->getUnitWork();
            $units = $unit->getChilds();
            if( $units instanceof ArrayObjectList || $unit->obtenerEmpleados(false) ){
                $img =  RESOURCES_DOMAIN . "/img/famfam/user_add.png";
                $imgopen =  RESOURCES_DOMAIN . "/img/famfam/user_delete.png";
                $url = $_SERVER["PHP_SELF"] . "?m=empleado&poid={$this->getUID()}";
            }
        }

        return array(
            "checkbox" => true,
            "img" => array("normal" => $img, "open" => $imgopen),
            "url" => $url
        );
    }


    /**
      * getUnitwork
      *
      * Retorna la unidad de este empleado
      * return unitwork Object
      */
    public function getUnitwork(){
        $unidad = $this->obtenerDato("unitwork");
        if( $unidad = trim($unidad) ){
            return new unitwork($unidad, $this);
        } else {
            return false;
        }
    }

    public function getManager() {
        try {
            if( $unit = $this->getUnitWork() ){
                return $unit->getManager($this);
            }
        } catch(Exception $e){
            if (CURRENT_ENV=='dev') { error_log($e->getMessage()); }
        }

        return false;
    }


    public function isManager(){
        return (bool) $this->obtenerDato("es_manager");
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


    public function opcionesDesplegable(){
        return 0;
    }


    public static function getExportSQL($usuario, $uids, $forced, $parent=false){

        $campos = array();
        if( $usuario->esStaff() ){
            $campos[] = "uid_empleado";
        }

        $campos[] = "nombre";
        $campos[] = "apellidos";
        $campos[] = "dni";

        $sql =  "SELECT ". implode(",", $campos) ." FROM ". TABLE_EMPLEADO ." WHERE 1";

        if( is_traversable($uids) && count($uids) ){
            $sql .=" AND uid_empleado in (".implode(",", $uids ).")";
        }

        if( is_traversable($forced) ){
            $list = ( count($forced) ) ? implode(",", $forced ) : 0;
            $sql .=" AND uid_empleado in ($list)";
        }

        if( is_numeric($parent) ){
            $sql .=" AND uid_empleado IN (
                SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa
                WHERE uid_empresa = $parent AND papelera = 0
            )";
        }

        return $sql;
    }


    public function getLineClass($parent, $usuario){
        $class = false;
        if( $usuario instanceof Iusuario ){
            $userCompany = $usuario->getcompany();
            // ---- Informacion de documentos, filtrado por usuario, documentos de subida y obligatorios
            $informacionDocumentos = $this->obtenerEstadoDocumentos($usuario, 0, true);
            $class = $userCompany->isSuitableItem($this) ? (( count($informacionDocumentos) == 1 && isset($informacionDocumentos[2]) ) ? 'color green':'color red') : 'color black';
        }
        return $class;
    }

    public function isOk($parent, $usuario){
        $class = false;
        if( $usuario instanceof Iusuario ){
            $userCompany = $usuario->getcompany();
            // ---- Informacion de documentos, filtrado por usuario, documentos de subida y obligatorios
            $informacionDocumentos = $this->obtenerEstadoDocumentos($usuario, 0, true);
            $class = (( count($informacionDocumentos) == 1 && isset($informacionDocumentos[2]) ) ? true : false);
        }
        return $class;
    }


    public function obtenerDireccionesReconocimiento() {
        $sql = "SELECT direccion FROM ". TABLE_CITA_MEDICA ." WHERE uid_empleado IN(
            SELECT uid_empleado FROM ". TABLE_EMPLEADO . "_empresa WHERE uid_empresa IN (
                SELECT uid_empresa FROM ". TABLE_EMPLEADO ."_empresa WHERE uid_empleado = {$this->getUID()}
            )
        ) AND direccion != '' GROUP BY direccion";

        $direcciones = $this->db->query($sql, "*", 0);

        if( count($direcciones) ){
            $direcciones = array_map("utf8_encode", $direcciones);
            $direcciones = array_combine( $direcciones, $direcciones);
            return $direcciones;
        } else {
            return false;
        }
    }

    public static function getAll($where = null) {
        $sql = 'SELECT uid_empleado FROM '.TABLE_EMPLEADO.' WHERE 1 ';
        if ($where) {
            $sql .=  stripos($where,'AND')!==false?$where:' AND '.$where;
        }
        $db = db::singleton();
        $empleados = $db->query($sql, "*", 0, 'empleado');
        return $empleados;
    }

    public static function cronCall($time, $force = false, $tipo = NULL) {
        $isTime = date("H:i", $time) == "01:00";
        $oclock = date("i") == "00";

        if ($isTime || $force) {
            // Actualizamos a la 1 de la noche
            self::calcularManagers($force);
        }

        // --- check geolocation
        if ($oclock) {
            empleado::checkLocations();
        }
    }

    public static function checkLocations()
    {
        $dbc    = db::singleton();
        $cli    = isset($_SERVER['PWD']);
        $table  = TABLE_EMPLEADO;
        $max    = self::MAXIMUM_PLACE_STAY;

        // Create the query
        $SQL = "SELECT uid_empleado
        FROM {$table}
        WHERE 1
        AND uid_usuario_location
        AND TIME_TO_SEC(TIMEDIFF(NOW(), location_timestamp)) > {$max}";

        $employees = $dbc->query($SQL, "*", 0, 'empleado');
        if (0 === count($employees)) {
            return true;
        }

        foreach ($employees as $employee) {
            if ($cli) {
                print "Marcando salida automática para el empleado {$employee->getUID()}...";
            }

            $app    = \Dokify\Application::getInstance();
            $entity = $employee->asDomainEntity();
            $event  = new \Dokify\Application\Event\Employee\Checkout($entity);
            $event->withStatus(\Dokify\Domain\Employee\Checkin\Checkin::STATUS_TIMEOUT_CHECKOUT);
            $app->dispatch(\Dokify\Events::EMPLOYEE_CHECKOUT, $event);

            $employee->writeLogUI(logui::ACTION_PLACE_LEAVE, "");
            if ($cli) {
                print "Ok\n";
            }
        }

        return true;
    }

    public static function calcularManagers($force) {
        $condicion = " and unitwork != '' ";
        if( $force ){
            // si forzamos se recalculan todas
            $condicion .= " and uid_responsable is null ";
        }

        $empleados = self::getAll($condicion);
        $total = count($empleados);

        $db = db::singleton();
        foreach ($empleados as $i => $empleado) {
            if( isset($_SERVER['PWD']) ) echo "Actualizando managers ". ($i+1) ."/$total\r";
            if ($manager = $empleado->getManager()) {
                $sql =  "UPDATE ".TABLE_EMPLEADO." SET uid_responsable = '{$manager->getUID()}' WHERE uid_empleado = '{$empleado->getUID()}' ";
                if( !$db->query($sql) ){

                }
            }
        }
    }

    public static function getSearchData(Iusuario $usuario, $papelera = false, $all = false, $network = true, $cache = true){
        $searchData = array();
        if (!$usuario->accesoModulo(__CLASS__)) return false;


        $company = $usuario->getCompany();
        $limit   = "1";

        if ($papelera) {
            $intList = $company->getStartIntList();
            $limit  .= " AND uid_empleado IN (SELECT ee.uid_empleado FROM ". TABLE_EMPLEADO ."_empresa ee WHERE ee.papelera = 1 AND ee.uid_empresa IN ({$intList}))";
        } elseif (!$all) {
            $viewIndexRepository = Container::instance()['index.repository'];
            $companyDomainEntity = $company->asDomainEntity();
            $userDomainEntity = $usuario->asDomainEntity();

            $strict = $cache ? false : true;
            if (true === $strict) {
                $viewIndexRepository->expireIndexOf(
                    'empleado',
                    $companyDomainEntity,
                    $userDomainEntity,
                    $network
                );
            }

            $indexList = $viewIndexRepository->getIndexOf(
                'empleado',
                $companyDomainEntity,
                $userDomainEntity,
                $network
            );

            if(count($indexList->elements()) >= 2000) {
                $indexTableName = Container::instance()['index.temporary_repository']->buildIndexTable(
                    'empleado',
                    $companyDomainEntity,
                    $userDomainEntity,
                    $network
                );

                $limit .= " AND uid_empleado IN (SELECT uid_empleado FROM {$indexTableName})";
            }else{
                $indexAsString = (string) $indexList;
                $limit .= " AND uid_empleado IN ({$indexAsString})";
            }
        }


        if( $usuario->isViewFilterByGroups() ){
            // Es posible que nos pasen un perfil por parámetro
            if( $usuario instanceof perfil ) $usuario = $usuario->getUser();

            $userCondition = $usuario->obtenerCondicion(false, "uid_empleado");
            $limit .= " AND uid_empleado IN ($userCondition)";
        }


        $searchData[ TABLE_EMPLEADO ] = array(
            "type" => "empleado",
            "fields" => array("concat(nombre,' ',apellidos)", "email", "dni"),
            "limit" => $limit,
            "accept" => array(
                "tipo" => "empleado",
                "uid" => true,
                "conformidad" => true,
                "docs" => true,
                "empresa" => true,
                "maquina" => true,
                "list" => true,
                "created" => true
            )
        );

        $searchData[TABLE_EMPLEADO]['accept']['own'] = array(__CLASS__, 'onSearchByOwn');
        $searchData[TABLE_EMPLEADO]['accept']['empresa'] = array(__CLASS__, 'onSearchByCompany');
        $searchData[TABLE_EMPLEADO]['accept']['completed'] = array(__CLASS__, 'onSearchByCompleted');
        $searchData[TABLE_EMPLEADO]['accept']['asignado'] = array(__CLASS__, 'onSearchByAsignado');

        $searchData[ TABLE_EMPLEADO ]['accept']['provincia'] = function($data, $filter, $param, $query){
            $value = reset($filter);
            $SQL = false;

            if( is_string($value) ){
                $SQL = " ( uid_provincia IN (
                    SELECT uid_provincia FROM ". TABLE_PROVINCIA ." WHERE nombre LIKE '%". db::scape(utf8_decode($value)) ."%'
                ) ) ";
            }

            return $SQL;
        };


        $searchData[TABLE_EMPLEADO]['accept']['near'] = function($data, $filter, $param, $query){
            $uid = reset($filter);

            // Convert username to uid
            if (!is_numeric($uid)) {

                // Nos vale con que esté al lado de alguien
                if ($uid === empleado::RESERVED_NEAR_SOMEONE) {
                    $SQL = "(uid_usuario_location != 0)";

                    return $SQL;
                }

                if (!$user = usuario::instanceFromUsername($uid)) {
                    return false;
                }

                $uid = $user->getUID();
            }


            if (is_numeric($uid)) {
                $SQL = "(uid_usuario_location = {$uid})";

                return $SQL;
            }

            return false;
        };

        $searchData[ TABLE_EMPLEADO ]['accept']['numero'] = function($data, $filter, $param, $query){
            $value = reset($filter);
            $SQL = false;

            $SQL = " ( numero_empleado = '". db::scape($value) ."' ) ";

            return $SQL;
        };


        $searchData[ TABLE_EMPLEADO ]['accept']['sap'] = function($data, $filter, $param, $query){
            $value = reset($filter);
            $SQL = false;

            $SQL = " ( sap = '". db::scape($value) ."' ) ";

            return $SQL;
        };

        $searchData[TABLE_EMPLEADO]['accept']['docs'] = array('empleado', 'onSearchByDocs');

        $searchData[TABLE_EMPLEADO]['accept']['estado'] = array('empleado', 'onSearchByStatus');

        $searchData[ TABLE_EMPLEADO ]['accept']['aptitud'] = function($data, $filter, $param, $query){
            $value = reset($filter);

            $sqlUnsuitableCompanies = "SELECT uid_item FROM ". TABLE_EMPRESA ."_item ei WHERE ei.uid_modulo = 8 AND suitable = 0 ";

            switch ($value) {
                case 'si':
                    $SQL = " ( uid_empleado NOT IN ($sqlUnsuitableCompanies) ) ";
                    break;

                case 'no':
                    $user = $data['usuario'];
                    $userCompany = $user->getCompany();
                    $startCompany = $userCompany->getStartList();
                    $clients = $userCompany->obtenerEmpresasCliente();
                    $listCompanies = count($clients) ? $clients->merge($startCompany)->toComaList() : $startCompany->toComaList();
                    $sqlUnsuitableCompanies .= "AND uid_empresa IN ($listCompanies)";
                    $SQL = " ( uid_empleado IN ($sqlUnsuitableCompanies) ) ";
                    break;

                default:
                    return false;
                    break;
            }

            return $SQL;
        };

        return $searchData;
    }

    /** COMENZAR A TRABJAR PARA UNA EMPRESA */
    public function asignarEmpresa( $empresa ){
        if( $empresa instanceof empresa ){ $empresa = $empresa->getUID(); }
        return $this->crearRelacion( $this->tabla."_empresa", "uid_empresa", $empresa, "uid_empleado", $this->uid );
    }

    public function getMachines(usuario $user = NULL){
        return $this->obtenerMaquinas($user);
    }

    public function obtenerMaquinas(usuario $usuario = NULL){
        $sql = "SELECT uid_maquina FROM $this->tabla"."_maquina WHERE uid_empleado = $this->uid";
        if( $usuario instanceof usuario ){
            $empresaUsuario = $usuario->getCompany();
            $sql .= " AND uid_maquina IN (
                SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa WHERE papelera = 0
                AND uid_empresa IN ({$empresaUsuario->getStartIntList()->toComaList()})
            )";
        }

        $coleccionMaquinas = $this->db->query($sql, "*", 0, "maquina");

        // TODO aqui hay que añadir un arrayobjectlist cuando compruebe todas las llamadas
        if (is_array($coleccionMaquinas)) return new ArrayObjectList($coleccionMaquinas);

        return new ArrayObjectList;
    }


    public function actualizarMaquinas(){
        return $this->actualizarTablaRelacional($this->tabla ."_maquina", "maquina");
    }


    /**
      * RETORNA LOS ELEMENTOS QUE "TIPICAMENTE" MOSTRAMOS DE ESTE ELEMENTO PARA VER EN MODO INLINE
      * @param = $usuario, debe ser el objeto usuario logeado actualmente, para filtrar si es necesario
      */
    public function getInlineArray(Iusuario $usuario = NULL, $config = false, $data = NULL){
        $dataString = is_array($data) ? json_encode($data) : false;
        $cacheKey = implode('-', array($this, __FUNCTION__, $usuario->obtenerPerfil(), $config, $dataString));
        if (($value = $this->cache->get($cacheKey)) !== NULL) return json_decode($value, true);

        $tpl = Plantilla::singleton();
        $inlineArray = array();


        //------------- INFORMACIÓN RÁPIDA DE LOS DOCUMENTOS
        if( $usuario instanceof usuario ){
            $inlineArray[] = parent::getDocsInline($usuario);
        }

        //--------- Empresas donde trabaja el empleado
        if ($config !== Ilistable::DATA_CONTEXT_HOME && $usuario instanceof usuario) {
            $staff = $usuario->esStaff();

            $companiesArray = array("img" => RESOURCES_DOMAIN . "/img/famfam/sitemap_color_inverse.png");

            $includeTrash = $staff ? null : false;
            $empresasEmpleado = $this->getCompanies($includeTrash, $usuario);
            foreach( $empresasEmpleado as $empresaEmpleado ){
                $className = $empresaEmpleado->getLineClass($empresaEmpleado, $usuario);
                $name = $title = $empresaEmpleado->getUserVisibleName();
                if ($staff && $this->inTrash($empresaEmpleado)){
                    $className .= " light";
                    $title = "{$this->getUserVisibleName()} está en la papelera de {$empresaEmpleado->getUserVisibleName()}";
                }

                $companiesArray[] = array(
                    "title" => $title,
                    "nombre" => $name,
                    "img" => $empresaEmpleado->getStatusImage($usuario),
                    "href"  => $empresaEmpleado->obtenerUrlFicha(),
                    "className" => $className
                );
            }

            $inlineArray[] = $companiesArray;


            if (isset($data["search"]) && strpos($data["search"],"created:") !== false) {


                $inlineArray[] = array(
                    "img" => RESOURCES_DOMAIN . "/img/famfam/clock.png",
                    "title" => $tpl("creado_en"),
                    array( "nombre" => $this->getCreationDate() )
                );
            } else {
                // Iconos de los agrupadores asignados
                if ($icons = $this->getInlineIcons($usuario)) $inlineArray[] = $icons;
            }

            if ($usuario->accesoAccionConcreta('maps', 21)) {
                if ($this->getLatLng() && $locationUser = $this->getLocationUser()) {
                    $inlineArray[] = array(
                        "img" => array(
                            "className" => "clickable link pointer",
                            "src" => RESOURCES_DOMAIN . "/img/common/map-pin-red.png",
                            "title" => $tpl("ver_en_mapa"). " - " .$locationUser->getUserVisibleName(),
                            "href" => "#maps.php?m=empleado&poid={$this->getUID()}"
                        ),
                        array()
                    );
                } else {
                    if ($userLocation = $this->getLocationUser()) {
                        $userCompany = $usuario->getCompany();
                        if ($userCompany instanceof empresa && $userCompany->hasUser($userLocation)) {
                            $inlineArray[] = array(
                                "img" => array(
                                    "className" => "box-it pointer",
                                    "src" => RESOURCES_DOMAIN . "/img/famfam/user_gray.png",
                                    "title" => $tpl("geolocate_user"). " - " .$userLocation->getUserVisibleName(),
                                    "href" => "usuario/modificar.php?poid=". $userLocation->getUID() . "&edit=direccion",
                                ),
                                array()
                            );
                        } else {
                            $inlineArray[] = array(
                                "img" => array(
                                    "src" => RESOURCES_DOMAIN . "/img/common/map-pin-gray.png",
                                    "title" => $tpl("waiting_geolocation"). " - " .$userLocation->getUserVisibleName(),
                                ),
                                array()
                            );
                        }
                    }
                }
            }
        }


        $this->cache->set($cacheKey, json_encode($inlineArray), 60*60*15);
        return $inlineArray;
    }

    public function getEmailFor($plantilla){
        return $this->obtenerEmpresaContexto()->obtenerEmailContactos($plantilla);
    }

    public function getUserName(){
        return $this->obtenerDato("dni");
    }

    public function getName(){
        return $this->obtenerDato("nombre");
    }

    /** PATH DE LA FOTOGRAFÍA DEL EMPLEADO */
    public function getPhoto(){
        $photo = DIR_ROOT . "res/img/silhouette.png";
        if( $photopath = $this->obtenerDato("path_photo") ){
            $photopath =  DIR_FILES . $photopath;
            if( archivo::is_readable($photopath) ){
                $photo = $photopath;
            }
        }

        return $photo;
    }

    public function putPhoto ($fileName, $coordsSize, $extension = false){

        $whiteList = array("jpg", "jpeg", "gif", "png");
        if ($extension === false) {
            $extension = archivo::getExtension($fileName);
        }

        if (!in_array(strtolower($extension), $whiteList)) throw new Exception("Error_tipo_archivo");

        $uniqueId = uniqid();
        $fileDBName = $uniqueId. ".$extension";
        $temporaryFile = "/tmp/".$fileName;

        $relativePath = $this->getModuleName() . "/uid_" . $this->getUID() . "/";
        $rutaCarpeta = DIR_FILES . $relativePath;
        $rutaArchivo = $rutaCarpeta . $fileDBName;
        $sqlFileName = $relativePath . $fileDBName;

        // Recover temporary file
        if (!$filedata = archivo::tmp($fileName)) throw new Exception("error_copiar_archivo");

        // Write to temp
        if (!archivo::escribir($temporaryFile, $filedata)) throw new Exception("error_copiar_archivo");

        $mimeType = mime_content_type($temporaryFile);
        if ($mimeType != archivo::getMimeEquivalent($extension)) {

            $newExtension = substr($mimeType, 6);
            $fileDBName = $uniqueId. ".$newExtension";
            $rutaArchivo = $rutaCarpeta . $fileDBName;
            $sqlFileName = $relativePath . $fileDBName;

            $infoImage = pathinfo($temporaryFile);
            $temporaryFile = "/tmp/".$infoImage['filename'] . '.' . $newExtension;

            // rewrite to temp
            if (!archivo::escribir($temporaryFile, $filedata)) throw new Exception("error_copiar_archivo");

        }


        if (!$imageSize = getimagesize($temporaryFile)) throw new Exception("archivo_no_legible");
        if (filesize($temporaryFile) && is_readable($temporaryFile)) {
            // Recortar la imagen a las coordenadas especificadas por el usuario si se encuentra..
            if (isset($coordsSize) && is_array($coordsSize) && $size = reset($coordsSize)) {
                list($originalWidth, $originalHeight) = $imageSize;
                if ($originalHeight > archivo::PHOTO_HEIGHT_LIMIT_PX) {
                    $scale = $originalHeight/archivo::PHOTO_HEIGHT_LIMIT_PX;
                    $size["x"] = $size["x"]*$scale;
                    $size["y"] = $size["y"]*$scale;
                    $size["width"] = $size["width"]*$scale;
                    $size["height"] = $size["height"]*$scale;
                }

                try {
                    $app = \Dokify\Application::getInstance();
                    $imageEditorCrop = $app['image_editor.crop'];

                    $imageEditorCrop->execute(
                        $imageEditorCrop->createRequest(
                            $temporaryFile,
                            $size['x'],
                            $size['y'],
                            $size['width'],
                            $size['height']
                        )
                    );
                } catch (Exception $e) {
                    throw new Exception('error_ver_archivo', 0, $e);
                }
            }

        } else {
            throw new Exception("error_leer_archivo");
        }

        if (!archivo::escribir($rutaArchivo, file_get_contents($temporaryFile), true)) {
            throw new Exception("error_copiar_archivo");
        }

        if( !archivo::is_readable($rutaArchivo) ){ throw new Exception("error_leer_archivo"); }

        $sql = "UPDATE $this->tabla SET path_photo = '$sqlFileName' WHERE uid_empleado = {$this->getUID()}";
        if (!$this->db->query($sql)) {
            throw new Exception("error updating image: ".$this->db->lastError());
        }

        archivo::tmp($fileName, NULL, true); //deleting file from tmp

        return true;
    }

    // /** DESASIGNAR DE UNA EMPRESA */
    // public function desasignarEmpresa( $empresa ){
    //  if( $empresa instanceof empresa ){ $empresa = $empresa->getUID(); }
    //  return $this->eliminarRelacion( $this->tabla."_empresa", "uid_empresa", $empresa, "uid_empleado" );
    // }

    public function removeParent(elemento $parent, usuario $usuario = null) {
        if ($parent instanceof empresa) {
            return $this->eliminarRelacion ($this->tabla.'_empresa','uid_empresa',$parent->getUID(),'uid_empleado');
        }
    }

    public function obtenerCondicionDocumentos(){
        return "";
    }

    public function obtenerDistancia($empresa=false, $toString=false, $inferiores=false,  $process=0 ){
        if( $empresa instanceof empresa ){
            $empresas = $this->getCompanies();

            //si solo hay una empresa, sin problema
            if( count($empresas) > 1 ){
                //dump("Hay mas de una empresa para este objeto.. veremos como resulta");
                //si hay mas, ya veremos
                //buscamos si hay una empresa activa


                /** Si el empleado trabaja directamente para esta empresa vamos a usar el metodo obtener distancia sobre la misma
                  * empresa para facilitar el trabajo ya que internamente es lo primero que comprueba
                  */
                if( in_array($empresa->getUID(), $empresas->toIntList()->getArrayCopy()) ){
                    return $empresa->obtenerDistancia($empresa, $toString, false);
                }
                /**/

                if( isset($_SESSION["OBJETO_EMPRESA"]) ){
                    //rescatamos el objeto de la empresa activa
                    $empresaActiva = unserialize($_SESSION["OBJETO_EMPRESA"]);
                    //comparamos si es una de las empresas del empleado actual
                    foreach($empresas as $empresaEmpleado){
                        if( $empresaActiva->getUID() == $empresaEmpleado->getUID() ){
                            return $empresaEmpleado->obtenerDistancia($empresa, $toString, false);
                        }
                    }
                }
            }

            //si no resultan concordancias, o bien solo hay una empresa o bien nos vemos obligados a tomar la primera
            //esto puede tener consecuencias de solicitud de documentos no deseadas, por lo tanto, mejor retornamos false
            if( count($empresas) == 1 ){
                $empresaEmpleado = reset($empresas);
                return $empresaEmpleado->obtenerDistancia($empresa, $toString, false);
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

    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
        $tpl = Plantilla::singleton();
        $info = parent::getInfo(true);
        $infoEmpleado =& $info[$this->uid];
        $userCompany = $usuario->getCompany();

        $innerHTML = "{$infoEmpleado["nombre"]} {$infoEmpleado["apellidos"]}";
        $title = "{$infoEmpleado["nombre"]} {$infoEmpleado["apellidos"]}";

        $infoEmpleado["nombre"] = array(
            "innerHTML" => $innerHTML,
            "href" => "../agd/ficha.php?m=empleado&poid={$this->uid}",
            "className" => "box-it link add-icon",
            "title" => $title
        );

        if ($usuario->accesoAccionConcreta(8, 10, null,'dni')) {
            $dni = $this->obtenerDato('dni');
            $infoEmpleado["nombre"]["title"] .= " - $dni";
        }

        if (count($userCompany->getUnsuitableItemClient($this))) {
            $infoEmpleado["nombre"]["innerHTML"] .= "<img src=\"".RESOURCES_DOMAIN ."/img/famfam/bell_error.png\"/>";
            $infoEmpleado["nombre"]["title"] .= ". ". $tpl('title_warning');
        }

        unset($infoEmpleado["apellidos"]);
        unset($infoEmpleado["dni"]);

        if ($usuario instanceof empleado && $unit = $this->getUnitwork()) {
            $infoEmpleado["unidad"] = array(
                "innerHTML" => $unit->getKey()
            );
        }

        return $info;
    }

    public function getUserVisibleName(){
        $info = $this->getInfo();
        return $info["nombre"]. " " . $info["apellidos"];
    }

    // ------------ FUNCIONES PARA EPIs
    public function obtenerEpis( $eliminadas = false, $limit = false, $estado = false, $tipoEpi = false, $count = false, $filters = null ){
        $condicion = elemento::construirCondicion( $eliminadas , $limit );
        $haylimit = strpos($condicion,'LIMIT');
        $aux = explode("LIMIT", $condicion);

        $epiSQL = "SELECT uid_epi FROM ". TABLE_EPI ." WHERE {$aux[0]}";
        if ($limit instanceof usuario) {
            $empresaUsuario = $limit->getCompany();
            $intList = $empresaUsuario->getAllCompaniesIntList();
            $list = count($intList) ? $intList->toComaList() : "0";
            $epiSQL .= " AND uid_empresa IN ({$list})";
        }
        $condicion = " uid_epi IN ({$epiSQL}) ";

        if ( $haylimit !== false) {
            $condicion .= " LIMIT {$aux[1]} ";
        }

        if( isset($filters) ){
            foreach ($filters as $key => $filter) {
                switch($key){
                    case 'alias':
                        $condicion = " uid_epi IN (  SELECT uid_epi FROM ". TABLE_TIPO_EPI ." INNER JOIN ". TABLE_EPI ." USING(uid_tipo_epi) WHERE (descripcion LIKE '%".$filter."%') OR (nserie LIKE '%".$filter."%')) AND " . $condicion;
                    break;
                    default:
                        $condicion = $key." = ".$filter." AND " . $condicion;
                    break;
                }
            }
        }

        if( $count === true ){
            return $this->db->query( "SELECT count(uid_epi) FROM ". TABLE_EPI ." WHERE 1 AND {$condicion}", 0, 0);
        }

        $epis = $this->obtenerObjetosRelacionados( TABLE_EMPLEADO_EPI, "epi", $condicion);
        return new ArrayObjectList($epis);
    }

    public function obtenerNumeroEpis($eliminadas = false, $limit = false, $estado = false, $tipoEpi = false){
        return $this->obtenerEpis( $eliminadas, $limit, $estado, $tipoEpi, true );
    }

    public function asignarEpi( epi $epi ){
        $sql = "INSERT INTO ". TABLE_EMPLEADO_EPI. " (uid_empleado, uid_epi, fecha_asignacion, fecha_entrega) VALUES( {$this->getUID()}, {$epi->getUID()}, NOW(), NOW())";
        return $this->db->query( $sql );
    }


    public function obtenerTiposEpiSolicitados($filters = null) {
        $agrupadores = $this->obtenerAgrupadores(NULL, false, false);
        if( count($agrupadores) ){

            $SQL = "SELECT uid_tipo_epi FROM ". TABLE_AGRUPADOR . "_tipo_epi WHERE uid_agrupador IN ({$agrupadores->toComaList()}) ";

            if( isset($filters) ){
                foreach ($filters as $key => $filter) {
                    switch($key){
                        case 'alias':
                            $SQL .= " uid_tipo_epi IN (  SELECT uid_tipo_epi FROM ". TABLE_TIPO_EPI ." INNER JOIN ". TABLE_AGRUPADOR . "_tipo_epi USING(uid_tipo_epi) WHERE descripcion LIKE '%".$filter."%') ";
                        break;
                        default:
                            $SQL .= " AND ". $key ." = ". $filter;
                        break;
                    }
                }
            }
            // Discriminar solo las que se pueden usar
            $episAsignadas = $this->obtenerEpis();
            $episValidas = new ArrayObjectList();
            foreach($episAsignadas as $epi){
                if( in_array($epi->obtenerEstado(), epi::estadosValidos()) ) $episValidas[] = $epi;
            }


            if( count($episValidas) ){
                $tipos = $episValidas->foreachCall('obtenerTipoepi')->unique();
                $SQL .= " AND uid_tipo_epi NOT IN ({$tipos->toComaList()}) ";
            }

            $SQL .= " GROUP BY uid_tipo_epi";
            $items = $this->db->query($SQL, "*", 0, "tipo_epi");
            if( is_traversable($items) ) return new ArrayObjectList($items);
        }

        return null;
    }

    public function obtenerSolicitudEpis() {
        return ( $tipos = $this->obtenerTiposEpiSolicitados() ) ? $tipos->foreachCall('toSolicitudEpi') : null;
    }


    public function obtenerResumenEstadoEpis( $estado = false ){
        $arrObjEpis = $this->obtenerEpis();
        $estados = array();
        foreach($arrObjEpis as $objEpi){
            $estados[] = $objEpi->obtenerEstado(false);
        }

        return $estados;
    }

    public function resumenEpis($wrap = false, $headers = false) {
        $template = Plantilla::singleton();
        $resumenEpis = null;
        if ($wrap) {
            $headers = true;
        }
        $epis = $this->obtenerEpis();

        if (!count($epis)) {
            return null;
        }

        foreach ($epis as $epi) {
            $resumenEpis .= $epi->resumen();
        }

        if (!$resumenEpis) {
            return null;
        }

        $template->assign('elemento',$this);
        $template->assign('headers',$headers);
        $template->assign('wrap',$wrap);
        $template->assign('resumen',$resumenEpis);
        $resumenEmpleado = $template->getHTML('epi/empleado.tpl');
        return $resumenEmpleado;
    }

    /**
      * Nos retornará true o false si el empleado ha dado su conformidad en los periodos preestablecidos
      *
      */
    public function hasConfirmed(){
        $sql = "SELECT DATEDIFF(NOW(), fecha_conformidad) as d FROM {$this->tabla} WHERE uid_empleado = {$this->getUID()}";
        $diff = $this->db->query($sql, 0, 0);
        if( is_numeric($diff) && $diff >= 0 && $diff < self::PERIODICIDAD_CONFORMIDAD ){
            return true;
        } else {
            return false;
        }
    }


    /**
      * Nos devuelve un timestamp de la ultima fecha de conformidad
      *
      */
    public function getConfirmationTime(){
        $sql = "SELECT UNIX_TIMESTAMP(fecha_conformidad) FROM {$this->tabla} WHERE uid_empleado = {$this->getUID()}";
        return $this->db->query($sql, 0, 0);
    }


    /**
      * Devuelve en formato timestamp la siguiente fecha de validacion
      *
      */
    public function nextConfirmationTime(){
        $sql = "SELECT if(fecha_conformidad, UNIX_TIMESTAMP(DATE_ADD(fecha_conformidad, INTERVAL ". self::PERIODICIDAD_CONFORMIDAD. " DAY)), CURRENT_TIMESTAMP) as d FROM {$this->tabla} WHERE uid_empleado = {$this->getUID()}";
        return $this->db->query($sql, 0, 0);
    }


    /*****************************************
     ******** INTERFACE Iusuario *************
     *****************************************/

    public function accesoAccionConcreta($idModulo, $accion, $config=null, $ref=null){
        if( !$idModulo instanceof elemento ){
            if( !is_numeric($idModulo) ) $idModulo = util::getModuleId($idModulo);
            if( !$idModulo ) return false;
        }

        $datosAccion = $this->getAvailableOptionsForModule($idModulo,  $accion/*UID DE LA ACCION DE ACCESO*/, $config, $ref);
        if( !is_array($datosAccion) || !count($datosAccion) ){ return false; }

        $datosAccion = reset($datosAccion);
        //$datosAccion["uid_modulo"] = $idModulo;

        return $datosAccion;
    }

    public function accesoModificarElemento(Ielemento $elemento, $config=0){
        $datosAccion = $this->getAvailableOptionsForModule($elemento->getModuleId(), 4/*UID DE LA ACCION DE MODIFICAR*/, $config);

        if( count($datosAccion) && is_traversable($datosAccion) ){ return true; }
        return false;
    }

    public function isViewFilterByGroups(){
        return false;
    }

    public function isViewFilterByLabel(){
        return false;
    }

    public function perfilActivo(){
        return $this;
    }

    public function idPerfilActivo(){
        return $this->getUID();
    }

    public function getAvailableOptionsForModule( $idModulo, $idAccion = false, $config = null, $referencia = null, $parent = NULL, $type = NULL ){
        $uidelemento = null;
        if( !is_numeric($idModulo) ){
            if ($idModulo instanceof documento && $idModulo->elementoFiltro) {
                $parent = $idModulo;
                $uidelemento = $idModulo->getUID();
                $moduleName = "documento";
                $idModulo = util::getModuleId($idModulo->elementoFiltro->getModuleName()."_".$moduleName);
            } elseif ($idModulo instanceof elemento) {
                $uidelemento = $idModulo->getUID();
                $idModulo = $idModulo->getModuleId();
            } else {
                $idModulo = util::getModuleId($idModulo);
            }
        }

        $fields = array("uid_accion", "alias", "alias as innerHTML", "concat('". RESOURCES_DOMAIN ."', icono) as icono", "concat('". RESOURCES_DOMAIN ."', icono) as img",  "href", "prioridad", "class" );

        $sql = "SELECT ". implode(", ", $fields) ."
        FROM ". TABLE_ACCIONES ." INNER JOIN ". TABLE_MODULOS ."_accion USING( uid_accion )
        WHERE uid_modulo = ". $idModulo ." AND activo = 1";

        if( $config === 0 || $config === 1 ){ $sql .= " AND config = $config"; }
        else { $sql .= " AND config = 0"; }

        if( $idAccion ){
            if( is_numeric($idAccion) ) $sql .= " AND uid_accion = $idAccion";
            else $sql .= " AND uid_accion = ( SELECT uid_accion FROM ". TABLE_ACCIONES ." WHERE alias = '". db::scape($idAccion) ."' ) ";
        }

        if( $referencia ) $sql .= " AND referencia = '$referencia' ";
        else $sql .= " AND referencia = '' ";


        if( is_numeric($type) ){ $sql .= "AND tipo = $type "; }


        $modulename = util::getModuleName($idModulo);
        if( $modulename == "empleado_documento" || $modulename == "empresa_documento" || $modulename == "maquina_documento" ) $modulename = "documento";
        if( is_readable( DIR_CLASS . "/$modulename.class.php" ) ){
            $func = $modulename.'::optionsFilter';
            if( is_callable($func) ){
                $filter = call_user_func( $func, false, $idModulo, $this, true, $config, 1, $parent);
                if( $filter !== false ){ $sql .= " $filter"; }
            }
        }

        if( $rol = $this->getRol() ){
            $sql .= " AND modulo_accion.uid_modulo_accion IN (
                SELECT l.uid_modulo_accion FROM ". TABLE_ROL ."_accion as l WHERE l.uid_rol = {$rol->getUID()}
            )";
        }

        return $this->db->query( $sql, true );
    }

    public function getRol(){
        $roles = rol::obtenerRolesGenericos(false, rol::TIPO_NORMAL, true);
        if( count($roles) ){
            return reset($roles);
        }
        return false;
    }

    public function configValue($value){
        return true;
    }

    public function esAdministrador(){
        return false;
    }

    public function esSATI(){
        return false;
    }

    public function getHelpers($href=false){
        return false;
    }

    public function esStaff(){
        return false;
    }

    public function accesoModulo($idModulo, $config=null){
        return $this->getAvailableOptionsForModule($idModulo, 21, $config);
    }

    public function getOptionsMultipleFor($modulo, $config=0, Ielemento $parent = NULL){
        return false;
    }

    public function getOptionsFastFor($modulo, $config=0, Ielemento $parent = NULL){
        return $this->getAvailableOptionsForModule($modulo, false, $config, null, $parent, 3);
    }

    public function buscarPerfilAcceso(Ielemento $objeto){
        return false;
    }

    public function accesoElemento( Ielemento $elemento, empresa $empresa = NULL, $papelera = false, $bucle = 0  ){
        switch( get_class($elemento) ){
            case "empresa":
                $empresas = $this->getCompanies();
                foreach ($empresas as $empresa) {
                    if ($corp = $empresa->perteneceCorporacion()) {
                        $grupo = $corp->getStartList();
                        $empresas = $empresas->merge($grupo);
                    }
                }

                return (bool) $empresas->contains($elemento);
            break;
            case "empleado":
                if( $elemento->compareTo($this) ) return true;

                if( $this->isManager() && $unitwork = $elemento->getUnitwork() ){
                    if( $unitwork->isChildOf($elemento) ){
                        return true;
                    }
                }
            break;
            case "agrupamiento":
                $empresa = $this->getCompany();
                $categoriaProyectos = new categoria(categoria::TYPE_PROYECTOS);
                $categoriaIntranet = new categoria(categoria::TYPE_INTRANET);

                $agrupamientosProyecto = $empresa->obtenerAgrupamientosPropios(array($this, $categoriaProyectos));
                $agrupamientosIntranet = $empresa->obtenerAgrupamientosVisibles(array($this, $categoriaIntranet));

                $totalAgr = new ArrayObjectList();
                if ($agrupamientosProyecto && $agrupamientosIntranet) {
                    $totalAgr = $agrupamientosProyecto->merge($agrupamientosIntranet);
                } elseif ($agrupamientosProyecto) {
                    $totalAgr = $agrupamientosProyecto;
                } elseif ($agrupamientosIntranet) {
                    $totalAgr = $agrupamientosIntranet;
                }

                return (bool) $totalAgr->contains($elemento);
            break;
            case "agrupador":
                return $this->accesoElemento( $elemento->obtenerAgrupamientoPrimario(), $empresa, $papelera);
            break;
            default:

            break;
        }

        return false;
    }

    public function getHumanName(){
        return $this->getUserVisibleName();
    }

    public function getImage(){
        return CURRENT_DOMAIN . "/agd/empleado/foto.php?poid={$this->getUID()}&t=". time();
    }

    /**
     * Get the path of the employee logo
     * @return string|boolean Path of the eployee logo or false if the path is empty or unreadable
     */
    public function getLogo()
    {
        $logoName   = $this->obtenerDato("path_photo");

        if ((bool) $logoName === false) {
            return false;
        }

        $photopath  =  DIR_FILES . $logoName;

        if (archivo::is_readable($photopath) === false) {
            return false;
        }

        return $photopath;

    }

    public function necesitaCambiarPassword(){
        return false;
    }

    public function checkFirstLogin(){
        return time();
    }

    public function getUnreadAlerts(){
        return false;
    }

    public function getEmpresaSolicitudPendientes($type = false, $status = solicitud::ESTADO_CREADA){
        return false;
    }

    public function touch(){
        return false;
    }

    public function esValidador(){
        return false;
    }

    public function getLastPage(){
        return false;
    }

    public function verEstadoConexion(){
        return false;
    }

    public function obtenerBusquedas($filter=false){
        return false;
    }

    public function getDocumentSignature (solicituddocumento $solicitud) {
        $lang = Plantilla::singleton();
        $attr = $solicitud->obtenerDocumentoAtributo();
        $signature = '';


        $signature .= $this->getHumanName() . ' '. $lang('con_dni') .' '.$this->getId();

        $signature .= ' ' . sprintf($lang('firmo_a_fecha'), $attr->getUserVisibleName()) . ' ';
        $signature .= date('Y-m-d H:i:s');
        return $signature;
    }

    public function obtenerElementosMenu(){
        $modulosDisponibles = array();

        $modulosDisponibles[] = array(
            "name" => "home",
            "icononly" => true,
            "href" => "#home.php"
        );


        $modulosDisponibles[] = array(
            "name" => "mis_documentos",
            "icononly" => false,
            "href" => "#documentos.php?m=empleado&poid={$this->getUID()}&menu=mis_documentos"
        );

        if( $this->isManager() && ($empleados=$this->obtenerEmpleados()) ){
            $modulosDisponibles[] = array(
                "name" => "empleado",
                "icononly" => false,
                "href" => "#list.php?m=empleado"
            );
        }

        $empresa = $this->getCompany();
        $categoriaProyectos = new categoria(categoria::TYPE_PROYECTOS);
        $categoriaIntranet = new categoria(categoria::TYPE_INTRANET);

        $agrupamientosProyecto = $empresa->obtenerAgrupamientosPropios(array($this, $categoriaProyectos));
        $agrupamientosIntranet = $empresa->obtenerAgrupamientosVisibles(array($this, $categoriaIntranet));

        $totalAgr = new ArrayObjectList();
        if ($agrupamientosProyecto && $agrupamientosIntranet) {
            $totalAgr = $agrupamientosProyecto->merge($agrupamientosIntranet);
        } elseif ($agrupamientosProyecto) {
            $totalAgr = $agrupamientosProyecto;
        } elseif ($agrupamientosIntranet) {
            $totalAgr = $agrupamientosIntranet;
        }

        if( count($totalAgr) && $this->accesoModulo("agrupador") ){
            foreach( $totalAgr as $agrupamiento ){
                $modulosDisponibles[] = array(
                    "name" => $agrupamiento->getUserVisibleName(),
                    "href" => "#agrupamiento/listado.php?poid=".$agrupamiento->getUID(),
                    "imgpath" => $agrupamiento->getIcon(false)
                );
            }
        }

        return $modulosDisponibles;
    }


    /** CAMBIAR EL PASSWORD AL EMPLEADO COMPROBANDO SI ES POSIBLE */
    public function cambiarPassword($password, $marcarParaRestaurar=false){

        $marcarParaRestaurar = ( $marcarParaRestaurar ) ? 1 : 0;
        $sql = "UPDATE {$this->tabla} SET password = MD5('$password') WHERE uid_empleado = " . $this->getUID();

        if( $this->db->query($sql) ){
            return true;
        } else {
            return $this->db->lastErrorString();
        }
    }

    /** CAMBIAR EL TOKEN DE PASSWORD AL EMPLEADO Y MARCAR PASS PARA RESTAURAR */
    public function cambiarToken($token){

        $sql = "UPDATE $this->tabla SET
            token_password = '$token',
            fecha_token = ".time()."
        WHERE uid_empleado = ".$this->getUID();
        if( $this->db->query($sql) ){
            return true;
        } else {
            return $this->db->lastErrorString();
        }
    }

    /** ELIMINAR EL TOKEN DE PASSWORD AL USUARIO Y MARCAR PASS PARA RESTAURAR */
    public function borrarToken(){
        $sql = "UPDATE $this->tabla SET
            token_password = NULL,
            fecha_token = NULL
        WHERE uid_empleado = ".$this->getUID();
        if( $this->db->query($sql) ){
            return true;
        } else {
            return false;
        }
    }

    // Comprobar que token de seguridad y email son correctos para adjudicar uid usuario y cambiar password
    public static function uidTokenEmail($token, $email = false) {
        $db=db::singleton();

        $SQL = "SELECT uid_empleado FROM ". TABLE_EMPLEADO ." WHERE token_password = '". db::scape($token) ."'";
        if( $email ){
            $SQL .= " AND email = '". db::scape($email) ."'";
        }

        $uid = $db->query($SQL, 0, 0);

        if( is_numeric($uid) ){
            return new empleado($uid);
        }

        return false;
    }

    /**
     * Return the QR endpoint
     * @return string url to access when the QR is readed
     */
    public function getQRURL($host = null, $ssl = true)
    {
        $uid = $this->getUID();

        if (CURRENT_ENV === 'dev') {
            if ($host) {
                $protocol = $ssl ? 'https://' : 'http://';
                $link = $protocol . $host . "/qr/{$uid}";
            } else {
                $link = CURRENT_DOMAIN . "/qr/{$uid}";
            }
        } else {
            $link = "https://dokify.net/qr/{$uid}";
        }

        return $link;
    }


    public function getQRImageData ($host = null, $ssl = true) {
        $link = $this->getQRURL($host, $ssl);

        require_once __DIR__ . "/../../src/lib/qrlib.php";

        ob_start();
        QRcode::png($link, null, QRSPEC_VERSION_MAX, 10, 0);
        $qrData = ob_get_clean();

        return $qrData;
    }


    public static function generateQR($employees, usuario $user, $each = null)
    {
        set_time_limit(0);
        ini_set("memory_limit", "512M");

        $userCompany    = $user->getCompany();
        $startList      = $userCompany->getStartList();
        $total          = count($employees);

        if (0 === $total) {
            return pdfHandler::merge([]);
        }

        $filesTmp   = array();
        $twig       = new \Dokify\TwigTemplate('employee/carnet.html');

        foreach ($employees as $i => $uid) {
            $item = $uid instanceof empleado ? $uid : new empleado($uid);

            // prevenir acceso de otras empresas
            if (!$item->getCompanies()->match($startList)) {
                continue;
            }

            $name = toCamelCase($item->obtenerDato('nombre'));
            $surname = toCamelCase($item->obtenerDato('apellidos'));

            $itemData['name'] = strlen($name) > 18 ? substr($name, 0, 17)."." : $name;
            $itemData['surname'] = strlen($surname) > 18 ? substr($surname, 0, 17)."." : $surname;
            $itemData['vat'] = $item->obtenerDato('dni');


            $photoPath  = $item->getPhoto();
            $qrData     = $item->getQRImageData();


            $itemData['photo'] = 'data:image/png;base64,' . base64_encode(archivo::leer($photoPath));
            $itemData['qr'] = 'data:image/png;base64,' . base64_encode($qrData) ;


            $viewData['items'][] = $itemData;
            if ((($i+1) % 8 == 0) || ($i +1 == $total)) {
                $tmpfname = tempnam(sys_get_temp_dir(), 'qr') . '.pdf';
                $filesTmp[] = $tmpfname;
                $html = $twig->render($viewData);
                $output = pdfHandler::htmlToPdf($html);
                file_put_contents($tmpfname, $output);
                $viewData['items'] = array();
            }

            if (is_callable($each)) {
                call_user_func($each, $i, $total, $uid);
            }
        }

        if (1 === count($filesTmp)) {
            return reset($filesTmp);
        }

        $carnets = pdfHandler::merge($filesTmp);
        return $carnets;
    }


    public function sendRestoreEmail(){
        $log = new log();
        $log->info("empleado", "resetear clave", $this->getUserVisibleName() );

        $token = usuario::randomPassword();
        $token = MD5($token);
        $this->cambiarToken($token);

        $mailTemplate = new Plantilla();
        $mailTemplate->assign("usuario", $this );
        $mailTemplate->assign("token", $token );
        $mailTemplate->assign("tipo", 'empleado');
        $html = $mailTemplate->getHTML("email/passreset.tpl");

        if( $direccion = trim($this->obtenerDato("email")) ){
            $email = new email($direccion);
            $email->establecerAsunto("Restaurar password");
            $email->establecerContenido($html);

            $estado = $email->enviar();
            if( $estado !== true ){
                $log->resultado("error $estado", true);
                throw new Exception($estado);
            }

            $log->resultado("ok ", true);
            return true;
        }

        throw new Exception("no_email_destino_empleado");
    }

    /** COMPARAR UN STRING CON EL PASSWORD DEL USUARIO */
    public function compararPassword($password, $func = "md5" ){
        $sql = "SELECT password FROM $this->tabla WHERE uid_empleado = ".$this->getUID();
        $MD5pass = $this->db->query($sql, 0, 0);
        if( $func && is_callable($func) ){
            $password = call_user_func($func, $password);
        }
        return ( $MD5pass == $password ) ? true : false;
    }

    static public function login($dni, $password = false){

        if( $password === false ){
            //$username es el id si no hay password
            if( !is_string($dni) ){
                $sql = "SELECT uid_empleado FROM ". TABLE_EMPLEADO . " WHERE uid_empleado = '". db::scape($dni) ."' AND password != ''";
            } else {
                $sql = "SELECT uid_empleado FROM ". TABLE_EMPLEADO . " WHERE dni = '". db::scape($dni) ."' ";
            }
        } else {
            $sql = "SELECT uid_empleado FROM ". TABLE_EMPLEADO . " WHERE dni = '". db::scape($dni) ."' AND password != '' AND password = MD5('". db::scape($password)."') ";
        }


        $uid = db::get($sql, 0, 0);

        if( is_numeric($uid) ){
            $empleado = new empleado($uid);
            $empleado->password = $password;
            return $empleado;
        }

        return false;
    }

    /** NOS INDICA DONDE BUSCAR LAS EMPRESAS SUPERIORES **/
    static public function getParentTable(){
        return TABLE_EMPLEADO . "_empresa";
    }
    /** NOS INDICA EL CAMPO QUE REPRESENTA LA EMPRESA EN LA TABLA DE RELACIONES **/
    static public function getParentRelationalField(){
        return "uid_empleado";
    }
    /** NOS INDICA EL CAMPO QUE REPRESENTA LA EMPRESA EN LA TABLA DE RELACIONES **/
    static public function getParentTableRelationalField(){
        return "uid_empresa";
    }


    public static function importFromFile($file, $empresa, $usuario, $post = null)
    {
        // Objeto database
        $db = db::singleton();

        // Importamos los elementos a la tabla
        $results = self::importBasics($usuario, $file, "empleado","dni");
        if( count($results["uids"]) ){

            if( isset($results["parents"]) ){
                foreach($results["parents"] as $parentID => $list ){
                    $sql = "SELECT count(uid_empresa) FROM ". TABLE_EMPRESA ." WHERE uid_empresa = " . db::scape($parentID);
                    $num = (int) $db->query($sql, 0, 0);

                    if( $num === 1 ){
                        //Relacionamos los elementos con nuestra empresa
                        $sql = "INSERT IGNORE INTO ". TABLE_EMPLEADO ."_empresa ( uid_empresa, uid_empleado )
                        SELECT $parentID, uid_empleado FROM ". TABLE_EMPLEADO ." WHERE uid_empleado IN (". implode(",", $list) .")
                        ";

                        if( !$db->query($sql) ){
                            throw new Exception( "Error al tratar de relacionar" );
                            //if( !isset($results["parent_not_foud"]) ){ $results["parent_not_foud"] = array(); }
                            //$results["parent_not_foud"][] = "#$parentID";
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
                $sql = "INSERT IGNORE INTO ". TABLE_EMPLEADO ."_empresa ( uid_empresa, uid_empleado )
                SELECT ". $empresa->getUID() .", uid_empleado FROM ". TABLE_EMPLEADO ." WHERE uid_empleado IN (". implode(",", $results["uids"]) .")
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

    public static function obtenerHorasJornada() {
        // Valores: entre ‘1’ y 24. El ‘0’ (“En el trayecto al ir al trabajo”) y el ‘99’ (“En el trayecto al volver del trabajo”).
        // sacado de FormatoRemesasPAT.pdf:27

        return array(self::JORNADA_YENDO =>'En el trayecto al ir al trabajo',
            '1'=>'1',
            '2'=>'2',
            '3'=>'3',
            '4'=>'4',
            '5'=>'5',
            '6'=>'6',
            '7'=>'7',
            '8'=>'8',
            '9'=>'9',
            '10'=>'10',
            '11'=>'11',
            '12'=>'12',
            '13'=>'13',
            '14'=>'14',
            '15'=>'15',
            '16'=>'16',
            '17'=>'17',
            '18'=>'18',
            '19'=>'19',
            '20'=>'20',
            '21'=>'21',
            '22'=>'22',
            '23'=>'23',
            '24'=>'0',
            self::JORNADA_VOLVIENDO =>'En el trayecto al volver del trabajo');
    }


    static public function obtenerSituacionesProfesionales(){
        return array(   self::SITUACION_ASALARIADO_PRIVADO => "Asalariado sector privado",
                        self::SITUACION_ASALARIADO_PUBLICO => "Asalariado sector publico",
                        self::SITUACION_AUTONOMO_CON_ASALARIADOS => "Autonomo con asalariados",
                        self::SITUACION_AUTONOMO_SIN_ASALARIADOS => "Autonomo sin asalariados");
    }

    static public function obtenerRegimenesSS(){
        return array(   "01" => "General",
                        "05" => "Trabajadores autónomos",
                        "06" => "Agrario cuenta ajena",
                        "07" => "Agrario cuenta propia",
                        "08" => "Trabajadores del mar",
                        "09" => "Mineria del carbón");
    }

    static public function obtenerTallasPrenda(){
        return array(   "S" => "Small",
                        "M" => "Medium",
                        "L" => "Large",
                        "XL" => "Extra large");
    }

    static public function obtenerTallasPie(){
        $tallasPie = array();
        for($i=50;$i>=30;$i--){
            $tallasPie[$i] = $i;
        }
        return $tallasPie;
    }

    static public function obtenerSexos(){
        return array("Masculino" => "Masculino", "Femenino" => "Femenino");
    }

    static public function obtenerAgrupadoresCliente($usuario){
        if( $usuario instanceof usuario ){
            $categoryClient = new categoria(categoria::TYPE_CLIENTES);
            $agrupamientos = $usuario->getCompany()->obtenerAgrupamientosVisibles($categoryClient);
            $agrupadores = new ArrayObjectList();
            foreach($agrupamientos as $agrupamiento){
                $agrupadores = $agrupadores->merge($agrupamiento->obtenerAgrupadores($usuario));
            }
            if(count($agrupadores)){
                return $agrupadores;
            }
        }
        return false;
    }

    static public function obtenerCedidosCliente($usuario){
        $cedidosCliente = array();

        $agrupadores = self::obtenerAgrupadoresCliente($usuario);
        if($agrupadores && count($agrupadores)){
            foreach($agrupadores as $agrupador){
                $cedidosCliente[$agrupador->getUID()] = $agrupador->obtenerDato("nombre");
            }
            return $cedidosCliente;
        }
    }

    public static function fieldTabs(Iusuario $usuario) {
        $tabs = new extendedArray();
        $tabs[] = (object) array("name" => "generales", "icon" => "famfam/application_view_columns.png");
        $tabs[] = (object) array("name" => "rrhh", "icon" => "famfam/group_go.png");
        $tabs[] = (object) array("name" => "puesto", "icon" => "famfam/cup.png");
        $tabs[] = (object) array("name" => "manager", "icon" => "famfam/user_add.png");
        if ($usuario->accesoAccionConcreta('empleado','epis')){
            $tabs[] = (object) array("name" => "epis", "icon" => "famfam/package_link.png");
        }

        return $tabs;
    }

    static public function getTelefonoRegExp(){
        return "^[0-9]{9,13}$";
    }

    static public function getCodigopostalRegExp(){
        return "^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$";
    }

    public static function optionsFilter($uid, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){
        $condiciones = array();


        if( is_numeric($uid) && $modulo = util::nombreModulo($uidmodulo) ){
            $item = new $modulo($uid);

            if( $uidmodulo && $user instanceof empleado ){
                if( $user->compareTo($item) ){
                    // No se podrá: modificar, eliminar, papelera, visibilidad RESPECTIVAMENTE cuando el empelado se vea a sí mismo
                    $condiciones[] = " ( uid_accion NOT IN (4, 14, 53, 52) ) ";
                }
            }

            if( $user instanceof usuario ){
                if( $empresas = $item->getCompanies() ){
                    $intList = $empresas->toIntList();
                    $empresaUsuario = $user->getCompany();

                    // no hay opción visibilidad, ni papelera, ni modificar, ni guardar asignaciones
                    $reservadas = "52, 5, 4, 123, 126, 153";
                    if( $user->esStaff() ) $reservadas = "52"; // no way

                    if( !$empresaUsuario->getStartIntList()->match($intList) ){
                        $condiciones[] = " ( uid_accion NOT IN ($reservadas) ) ";
                    }

                    if(!$empresaUsuario->isEnterprise()){
                        $condiciones[] = " ( uid_accion NOT IN (123,126) ) ";
                    }
                }
            }

        } elseif ($tipo == 3 || $tipo == 2) {
            $empresaUsuario = $user->getCompany();
            $empresaCorp = $empresaUsuario->esCorporacion() && $empresaUsuario->obtenerEmpresasInferiores()->contains($parent) ? TRUE : FALSE;

            // 22:crear, 23:verpapelera, 91:enviar a papelera
            $reservadas = "22, 23, 91";
            if( $user->esStaff() ) $reservadas = "22"; // no way

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




    public function triggerAfterUpdate($usuario, $data){
        $log = log::singleton();
        $log->info("empleado", "afterUpdate", $this->getUserVisibleName() );

        try {
            if( ($unitwork=$this->getUnitWork()) && ($manager = $unitwork->getManager($this)) ) {
                $sql = " UPDATE ".TABLE_EMPLEADO." SET uid_responsable = '{$manager->getUID()}' WHERE uid_empleado = '{$this->getUID()}' ";
                if ($this->db->query($sql)) {
                    $log->resultado("ok ", true);
                } else {
                    $log->resultado("error {$db->lastErrorString()}",true);
                }
            }
        } catch (Exception $e) {
            $log->resultado("error {$e->getMessage()}", true);
            return null;
        }

    }

    public function triggerAfterCreate($usuario, $item) {
        $log = log::singleton();
        $log->info("empleado", "afterCreate", $this->getUserVisibleName() );
        try {
            $unitwork = $this->getUnitWork();
            if ( $unitwork && $manager = $unitwork->getManager() ) {
                $sql = "UPDATE ".TABLE_EMPLEADO." SET uid_responsable = '{$manager->getUID()}' WHERE uid_empleado = '{$item->getUID()}' ";
                if($this->db->query($sql)) {
                    $log->resultado("ok ", true);
                } else {
                    $log->resultado("error {$db->lastErrorString()}",true);
                }
            }
        } catch (Exception $e) {
            $log->resultado("error {$e->getMessage()}", true);
            return null;
        }

    }

    public function triggerAfterDelete($usuario) {
        foreach($solicitudes = $this->obtenerSolicitudesEmpresa() as $solicitud) {
            $solicitud->eliminar();
        }
    }


    public static function tipoDocumentoIdentificacion($ipf) {
        if (vat::isValidSpainId($ipf)) {
            switch(1) {
                case preg_match(vat::getNIFRegExp(),$ipf): return self::ID_NIF; break;
                case preg_match(vat::getNIERegExp(),$ipf): return self::ID_NIE; break;
                case preg_match(vat::getNISRegExp(),$ipf): return self::ID_NIS; break;
                case preg_match(vat::getNIXRegExp(),$ipf): return self::ID_NIX; break;
                case length($ipf)==9: default: return self::ID_PAS; break;
            }
        }
    }

    public static function defaultData($data, Iusuario $usuario = null)
    {
        $fieldsParent = parent::defaultData($data, $usuario);
        if ($fieldsParent && count($fieldsParent)) {
            $data = array_merge($data, $fieldsParent);
        }

        if (isset($data['dni'])) {
            if (empleado::isIdInUse($data['dni'])) {
                throw new Exception(_('The ID is already in use'));
            }

            $data['dni'] = mb_strtoupper($data['dni']);
        }

        $country = false;
        if (isset($data["uid_pais"])) {
            if ($data["uid_pais"] == 0) {
                throw new Exception(_("Please, specify a valid country"));
            }

            if (($idCountry = db::scape($data["uid_pais"])) && is_numeric($idCountry)) {
                $country = new pais($idCountry);
            }
        }

        if (!vat::checkIdHuman(db::scape($data["dni"]), $country)) {
            throw new Exception("error_dni_invalido");
        }

        if (isset($data["email"]) && ($data["email"] != "")) {
            if (self::isEmailInUse($data["email"], null)) {
                throw new Exception(_("The email is already in use"));
            }

            if (StringParser::isEmail($data['email']) === false) {
                throw new Exception(_("The email address is not valid"));
            }
        }

        return $data;
    }

    public function updateData($data, Iusuario $usuario = null, $mode = null)
    {
        if (in_array($mode, [usuario::PUBLIFIELDS_MODE_TIMEZONE, usuario::PUBLIFIELDS_MODE_CONFORMIDAD, usuario::PUBLIFIELDS_MODE_SYSTEM])) {
            return $data;
        }

        if ($mode === elemento::PUBLIFIELDS_MODE_EDIT) {
            if (isset($data["nombre"])) {
                if (trim($data["nombre"]) == "") {
                    throw new Exception(_('Error, name field is required'));
                }
            }

            if (isset($data["apellidos"])) {
                if (trim($data["apellidos"]) == "") {
                    throw new Exception(_('Error, surname field is required'));
                }
            }
        }

        if (isset($data['dni'])) {
            if (mb_strtolower($this->getId()) !== mb_strtolower($data['dni']) && empleado::isIdInUse($data['dni']) == true) {
                throw new Exception(_('The ID is already in use'));
            }

            $data['dni'] = mb_strtoupper($data['dni']);
        }

        if (isset($data["email"]) && $email = trim($data["email"])) {
            if (self::isEmailInUse($email, $this)) {
                throw new Exception(_("The email is already in use"));
            }

            if (StringParser::isEmail($email) === false) {
                throw new Exception(_("The email address is not valid"));
            }
        }

        if (isset($data["email_secretaria"]) && false === empty($data['email_secretaria'])) {
            if (StringParser::isEmail($data['email_secretaria']) === false) {
                throw new Exception(_("The email address is not valid"));
            }
        }

        if (isset($data["uid_pais"])) {
            $idCountry = db::scape($data["uid_pais"]);

            if ($idCountry && is_numeric($idCountry)) {
                $country = new pais($idCountry);
            } else {
                throw new Exception(_('Please, choose a country'));
            }
        } else {
            $country = $this->obtenerPais();
        }

        if (isset($country) && isset($data["dni"])) {
            if (vat::checkIdHuman(db::scape($data["dni"]), $country) === false) {
                throw new Exception("error_dni_invalido");
            }
        }


        $validTown  = isset($data["uid_municipio"]) && is_numeric($data["uid_municipio"]);
        $validState = isset($data["uid_provincia"]) && is_numeric($data["uid_provincia"]);
        if ($validState && $validTown) {
            $municipio = new municipio($data["uid_municipio"]);
            $municipiosProvincia = municipio::obtenerPorProvincia($data["uid_provincia"]);
            if (!$municipiosProvincia->contains($municipio)) {
                throw new Exception("error_municipio");
            }
        } else {
            if ($validTown === false) {
                $data["uid_municipio"] = null;
            }
            if ($validState === false) {
                $data["uid_provincia"] = null;
            }
        }

        // if the country is not spain, unset the state and town
        if (isset($data["uid_pais"]) && $data["uid_pais"] != pais::SPAIN_CODE) {
            $data["uid_provincia"] = '0';
            $data["uid_municipio"] = '0';
        };

        $country = isset($data["uid_pais"]) && is_numeric($data["uid_pais"]) ? new pais($data["uid_pais"]) : $this->obtenerPais();
        $compareVat = isset($data["dni"]) ? db::scape($data["dni"]) : $this->getId();

        if ($country) {
            if (!vat::checkIdHuman($compareVat, $country)) {
                throw new Exception("error_dni_invalido");
            }
        }

        return $data;
    }

    public function getUserVoiceToken () {
        $account_key = "dokify";
        $api_key = "159fa92ec758e238d20a5e229c83c54f";

        $salted = $api_key . $account_key;
        $hash = hash('sha1',$salted,true);
        $saltedHash = substr($hash,0,16);
        $iv = "OpenSSL for Ruby";

        $user_data = array(
          "guid" => $this->getUID(),
          //"expires" => "2011-01-12 22:56:42",
          "display_name" => $this->getHumanName(),
          "email" => $this->getEmail()
          //"url" => "http://example.com/users/1234",
          //"avatar_url" => "http://example.com/users/1234/avatar.png"
        );

        if ($this->getUID() == 1) {
            $user_data["trusted"] = true;
        }

        $data = json_encode($user_data);

        // double XOR first block
        for ($i = 0; $i < 16; $i++)
        {
            $data[$i] = $data[$i] ^ $iv[$i];
        }

        $pad = 16 - (strlen($data) % 16);
        $data = $data . str_repeat(chr($pad), $pad);

        $cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128,'','cbc','');
        mcrypt_generic_init($cipher, $saltedHash, $iv);
        $encryptedData = mcrypt_generic($cipher,$data);
        mcrypt_generic_deinit($cipher);

        return $encryptedData = urlencode(base64_encode($encryptedData));
    }


    public function setTimezoneOffset ($offset) {
        return $this->update(array('timezone_offset' => $offset), usuario::PUBLIFIELDS_MODE_TIMEZONE, $this);
    }

    public function getTimezoneOffset () {
        return (int) $this->obtenerDato('timezone_offset');
    }


    public function watchingThread($element, $requirements){

        $moduleName = $element->getModuleName();
        $moduleId = util::getModuleId($moduleName);
        $requirementsList = ($requirements) ? $requirements->toComaList() : 0;

        $sql = "
            SELECT uid_watch_comment_{$moduleName} FROM ". TABLE_WATCH_COMMENT ."_{$moduleName} wc
            INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." de
                ON  uid_elemento_destino = wc.uid_{$moduleName}
                    AND wc.uid_documento_atributo = de.uid_documento_atributo
                    AND wc.uid_agrupador = de.uid_agrupador
                    AND wc.uid_empresa_referencia = de.uid_empresa_referencia
                    AND de.uid_modulo_destino = {$moduleId}
                WHERE uid_watcher = {$this->getUID()}
                AND uid_module_watcher = {$this->getModuleId()}
                AND uid_documento_elemento IN ($requirementsList)
                GROUP BY uid_watcher, uid_module_watcher
            ";

        $wachingComment = db::get($sql, 0, 0);
        if ($wachingComment) return new watchComment($wachingComment, $moduleName);
        return false;
    }


    public function unWatchThread($element, $requirements){
        $reqType = new requirementTypeRequest($requirements, $element);
        return $reqType->unWatchThread($this);
    }


    public function wacthThread($element, $requirements){

        $moduleName = $element->getModuleName();
        foreach ($requirements as $requirement) {
            $documentoAtributo = $requirement->obtenerDocumentoAtributo();
            $agrupador = ($agrupador = $requirement->obtenerAgrupadorReferencia()) ? $agrupador->getUID() : 0;

            $sql = "INSERT IGNORE INTO ". TABLE_WATCH_COMMENT ."_$moduleName
                ( uid_documento_atributo, uid_$moduleName, uid_agrupador, uid_empresa_referencia,
                 uid_watcher, uid_module_watcher, assigned)
             VALUES
                ({$documentoAtributo->getUID()}, {$element->getUID()}, '{$agrupador}',
                '{$requirement->obtenerIdEmpresaReferencia()}', {$this->getUID()}, {$this->getModuleId()},
                '". watchComment::MANUALLY ."')";
            $this->db->query($sql);
        }
    }

    public function sendEmailWithParams($asunto, $tpl, array $params, array $logParams, $replyTo = false) {
        set_time_limit(0);
        $plantilla = new Plantilla();
        $log = log::singleton();

        $address = $this->getEmail();
        if (!$address) return false;

        $method = array($log,'info');
        call_user_func_array($method, $logParams);

        foreach ($params as $key => $value) {
            $plantilla->assign($key, $value );
        }

        $plantilla->assign("lang", $lang);

        if (CURRENT_ENV == 'dev') {
            $address = strpos($address, "@dokify.net") ? $address : email::$developers;
        }

        if ($address) {
            $email = new email($address);
            if ($replyTo) $email->addReplyTo($replyTo);

            $htmlPath ='email/'.$tpl.'.tpl';
            $html = $plantilla->getHTML($htmlPath);
            $email->establecerContenido($html);

            $lang = $this->getCompany()->getCountry()->getLanguage();
            if (!is_array($asunto)) {
                $subject = $plantilla->getString($asunto, $lang);
            } else {
                $asunto[0] = $plantilla->getString($asunto[0], $lang);
                $subject = call_user_func_array("sprintf", $asunto);
            }
            $subject = (strlen($subject) > 103) ? substr($subject,0,100).'...' : $subject;
            $email->establecerAsunto($subject);

            $estado = $email->enviar();

            if( $estado !== true ){
                $estado = $estado && trim($estado) ? trim($estado) : $plantilla('error_desconocido');
                $log->resultado("error $estado", true);
                throw new Exception($estado);
            }

            $log->resultado("ok ", true);
            return true;
        }

        return false;
    }

    public function canShowTour($tour){

        if (!$tour) return false;

        $sql = "SELECT uid_element FROM ". ELEMENT_TOUR ."
                WHERE uid_element = {$this->getUID()}
                AND uid_module = {$this->getModuleId()}
                AND uid_tour = $tour";

        $uidElement = $this->db->query($sql, 0, 0);
        if ($uidElement) return false;
        return true;

    }

    public function setTour($tour){

        if (!$tour) return false;

        $sql = "INSERT INTO ". ELEMENT_TOUR ." (uid_element, uid_module, uid_tour)
                VALUES ({$this->getUID()}, {$this->getModuleId()}, '". db::scape($tour) ."')";

        return $this->db->query($sql);
    }

    public function canModifyVisibilityOfUsers () {
        return false;
    }

    public function getUserLimiter (empresa $company) {
        return false;
    }

    public function setCompanyWithHiddenDocuments (empresa $company, $hide = true, usuario $usuario = NULL) {
        return false;
    }

    public function setVisibilityForAllCompanies () {
        return false;
    }

    /**
    * {@inheritDoc}
    */
    public function isActiveWatcher()
    {
        $companyEmployees = TABLE_EMPLEADO_EMPRESA;

        $sql = "
            SELECT COUNT(uid_empleado_empresa)
            FROM {$companyEmployees}
            WHERE uid_empleado = {$this->uid}
            AND papelera = 0
        ";

        return (bool) $this->db->query($sql, 0, 0);
    }

    static public function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $arrayCampos = new FieldList();
        $language = Plantilla::getCurrentLocale();

        if ($modo === elemento::PUBLIFIELDS_MODE_GEO) {
            $arrayCampos["latlng"] = new FormField;
            $arrayCampos["location_timestamp"] = new FormField;
            $arrayCampos["uid_usuario_location"] = new FormField;
            return $arrayCampos;
        }

        if( $usuario instanceof usuario ){
            $camposExtra = $usuario->getCompany()->obtenerCamposDinamicos(8);
        }

        if ( isset($modo) && $modo == usuario::PUBLIFIELDS_MODE_CONFORMIDAD ) {
            $arrayCampos["fecha_conformidad"] = new FormField;
            return $arrayCampos;
        }

        if( $tab == false || $tab->name == "generales" ) {
            if ( $usuario instanceof usuario && ( $usuario->accesoAccionConcreta(8,10,null,'dni') || ($usuario->accesoAccionConcreta(8,22) && $modo == elemento::PUBLIFIELDS_MODE_NEW )) ) {
                // si tiene permiso para crear, necesita introducir el dni...
                $arrayCampos["dni"] = new FormField(array("tag" => "input", "type" => "text", /*"onblur" => "return agd.inputs.dni(this, event||false)",*/ "blank" => false ));
            }
            $arrayCampos["nombre"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
            $arrayCampos["apellidos"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
        }

        if (isset($modo) && ($modo == elemento::PUBLIFIELDS_MODE_INIT || $modo == elemento::PUBLIFIELDS_MODE_NEW)) {
            $arrayCampos["email"] = new FormField(array("tag" => "input", "type" => "text"));
        }

        if ($modo == elemento::PUBLIFIELDS_MODE_INIT || $modo == elemento::PUBLIFIELDS_MODE_NEW) {
            $arrayCampos["uid_pais"] = new FormField(array("tag" => "select", "type" => "text", "default" => "Seleccionar", "data" => pais::obtenerTodos(), "blank" => false));
        }

        if (isset($modo) && ($modo == elemento::PUBLIFIELDS_MODE_NEW || $modo === elemento::PUBLIFIELDS_MODE_TAB )) {
            $arrayCampos['created'] = new FormField(array("date_format" => "%d/%m/%Y"));
        }

        // Campos obligatorios en Portugal
        if ($language === Plantilla::PORTUGAL_LANGUAGE ) {

            if ($modo == elemento::PUBLIFIELDS_MODE_INIT || $modo == elemento::PUBLIFIELDS_MODE_NEW) {
                $arrayCampos["nif"] = new FormField(array("tag" => "input", "type" => "text", "innerHTML" => "empleado_nif", "blank" => false));
                $arrayCampos["direccion"] = new FormField(array("tag" => "textarea", "placeholder" => "provincia_municipio_calle", "blank" => false));
                $arrayCampos["numero_seguridad_social"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                $arrayCampos["categoria_profesional"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
            }

            if ($modo == elemento::PUBLIFIELDS_MODE_NEW) {
                $arrayCampos["uid_pais"] = new FormField(array("tag" => "select", "type" => "text", "default" => "Seleccionar", "data" => pais::obtenerTodos(), "blank" => false));
            }
        }


        if( $objeto instanceof empleado ){
            $pais = $objeto->obtenerPais();
            switch ($modo) {
                case elemento::PUBLIFIELDS_MODE_TAB:
                    $arrayCampos["telefono"] = new FormField(array("tag" => "input", "type" => "text"));
                    $arrayCampos["movil"] = new FormField(array("tag" => "input", "type" => "text"));
                    $arrayCampos["email"] = new FormField(array("tag" => "input", "type" => "text"));
                    if( $objeto instanceof empleado ){
                        if( trim($objeto->obtenerDato("numero_empleado") )) $arrayCampos["numero_empleado"] = new FormField(array("tag" => "input", "type" => "text"));
                        if( trim($objeto->obtenerDato("sap") ))$arrayCampos["sap"] = new FormField(array("tag" => "input", "type" => "text"));
                    }

                    $arrayCampos["uid_provincia"] = new FormField(array("tag" => "select", "type" => "text", "objeto" => "provincia"));
                break;

                case elemento::PUBLIFIELDS_MODE_EDIT:
                    // Para la tab Recursos Humanos
                    if( $tab == false || $tab->name == "rrhh" ){
                        if( $pais && $pais->getUID() == pais::SPAIN_CODE ){
                            $arrayCampos["uid_cnae"] = new FormField(array("tag" => "select", "type" => "text", "search" => true, "default" => "Seleccionar", "data" => cnae::obtenerTodos()));
                            $arrayCampos["uid_tipocontrato"] = new FormField(array("tag" => "select", "type" => "text",  "default" => "Seleccionar", "data" => tipocontrato::obtenerTodos()));
                            $arrayCampos["situacion_profesional"] = new FormField(array("tag" => "select", "type" => "text", "default" => "Seleccionar", "data" => self::obtenerSituacionesProfesionales()));
                        }


                        $regimenes = ( $pais && $pais->getUID() == pais::SPAIN_CODE ) ? self::obtenerRegimenesSS() : NULL;
                        $arrayCampos["regimen_seguridad_social"] = new FormField(array("tag" => ($regimenes?"select":"input"), "type" => "text", "default" => ($regimenes?"Seleccionar":""), "data" => $regimenes));
                        $arrayCampos["numero_seguridad_social"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));


                        $arrayCampos["fecha_alta_empresa"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "date_format" => "%d/%m/%Y"));
                        $arrayCampos["fecha_baja_empresa"] = new FormField(array("tag" => "input", "type" => "text", "className" => "datepicker", "date_format" => "%d/%m/%Y"));

                        if( $pais && $pais->getUID() == pais::SPAIN_CODE ){
                            $arrayCampos["ceco"] = new FormField(array("tag" => "input", "type" => "text"));
                        }

                        $arrayCampos["sap"] = new FormField(array("tag" => "input", "type" => "text", "match" => self::getNumberRegExp()));
                        $arrayCampos["numero_empleado"] = new FormField(array("tag" => "input", "type" => "text", "match" => self::getNumberRegExp()));
                        $arrayCampos["ett"] = new FormField(array("tag" => "input", "type" => "checkbox"));
                        $arrayCampos["objeto_contrato"] = new FormField(array("tag" => "textarea"));
                    }

                    // Para la tab de datos relacionados con el puesto de trabajo
                    if( $tab == false || $tab->name == "puesto" ){
                        $arrayCampos["descripcion_puesto"] = new FormField(array("tag" => "input", "type" => "text" ));
                        $arrayCampos["categoria_profesional"] = new FormField(array("tag" => "input", "type" => "text"));

                        $centros        = [];
                        $companyObject  = $objeto->getCompany($usuario);
                        if ($companyObject) {
                            $centros = $companyObject->obtenerCentroCotizacions();
                        }

                        $arrayCampos["uid_centrocotizacion"] = new FormField(array("tag" => "select", "type" => "text", "default" => "Seleccionar", "data" => $centros));
                        $arrayCampos["uid_codigoocupacion"] = new FormField(array("tag" => "select", "type" => "text", "search" => true, "default" => "Seleccionar", "data" => codigoocupacion::obtenerTodos()));


                        $arrayCampos["planta_modulo"] = new FormField(array("tag" => "input", "type" => "text"));
                        $arrayCampos["edificio"] = new FormField(array("tag" => "input", "type" => "text"));
                        $arrayCampos["alias"] = new FormField(array("tag" => "input", "type" => "text"));
                        $arrayCampos["uid_agrupador_cliente"] = new FormField(array("tag" => "select", "type" => "text", "default" => "Seleccionar" , "data" => self::obtenerCedidosCliente($usuario) ));
                        $arrayCampos["es_responsable_trabajos"] = new FormField(array("tag" => "input", "type" => "checkbox"));
                        $arrayCampos["delegado_prevencion"] = new FormField(array("tag" => "input", "type" => "checkbox"));
                        $arrayCampos["delegado_ubicacion"] = new FormField(array("tag" => "input", "type" => "text" ));
                        $arrayCampos["fecha_alta_teletrabajo"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "date_format" => "%d/%m/%Y"));
                    }

                    // Para la tab relacionada con la información del manager
                    if( $tab == false || $tab->name == "manager" ){
                        $arrayCampos["persona_contacto"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                        $arrayCampos["es_manager"] = new FormField(array("tag" => "input", "type" => "checkbox"));
                        $arrayCampos["unitwork"] = new FormField(array("tag" => "input", "type" => "text"));
                        $arrayCampos["email_secretaria"] = new FormField(array("tag" => "input", "type" => "text"));
                    }

                    // Para la tab relacionada con la información de tallas y demás para EPIs
                    if( $tab == false || $tab->name == "epis" ){
                        $arrayCampos["talla_pantalon"] = new FormField(array("tag" => "select", "type" => "text", "default" => "Seleccionar", "data" => self::obtenerTallasPrenda()));
                        $arrayCampos["talla_camisa"] = new FormField(array("tag" => "select", "type" => "text", "default" => "Seleccionar", "data" => self::obtenerTallasPrenda()));
                        $arrayCampos["numero_pie"] = new FormField(array("tag" => "slider", "match" => "^([30-50])$", "count" => "50", "min" => 30 ));
                    }



                    // Para el tab Generales
                    if( $usuario instanceof usuario && ($tab == false || $tab->name == "generales") ){

                        if ($language === Plantilla::PORTUGAL_LANGUAGE ) {
                            $arrayCampos["nif"] = new FormField(array("tag" => "input", "type" => "text", "innerHTML" => "empleado_nif", "blank" => false));
                        }

                        $arrayCampos["fecha_nacimiento"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "date_format" => "%d/%m/%Y"));
                        $arrayCampos["sexo"] = new FormField(array("tag" => "select", "type" => "text", "default" => "Seleccionar", "data" => self::obtenerSexos()));
                        $arrayCampos["uid_pais"] = new FormField(array("tag" => "select", "type" => "text", "default" => "Seleccionar", "data" => pais::obtenerTodos(), "blank" => false));
                        $arrayCampos["email"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                        $arrayCampos["telefono"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "match" => self::getTelefonoRegExp()));
                        $arrayCampos["movil"] = new FormField(array("tag" => "input", "type" => "text", "match" => self::getTelefonoRegExp()));
                        $arrayCampos["direccion"] = new FormField(array("tag" => "input", "type" => "text"));
                        $arrayCampos["uid_provincia"] = new FormField(array("tag" => "select", "type" => "text", "depends" => array("uid_pais", pais::SPAIN_CODE), "default" => "Seleccionar", "data" => provincia::obtenerTodos()));

                        $srcMunicipios = "m=empleado&field=uid_municipio";
                        if( $objeto instanceof empleado ){ $srcMunicipios .= "&poid={$objeto->getUID()}"; }
                        $arrayCampos["uid_municipio"] = new FormField(array("tag" => "select",
                            "type" => "text",
                            "default" => "Seleccionar",
                            "async" => $srcMunicipios,
                            "data" => 'municipio::obtenerPorProvincia',
                            "depends" => "uid_provincia"));

                        $arrayCampos["cp"] = new FormField(array("tag" => "input", "type" => "text", "match" => self::getCodigopostalRegExp()));
                        if( is_traversable($camposExtra) && count($camposExtra) ){
                            foreach($camposExtra as $campoExtra){
                                $arrayCampos[ $campoExtra->getFormName() ] = new FormField(array(
                                    "tag" => $campoExtra->getTag(),
                                    "type" => $campoExtra->getFieldType(),
                                    "uid_campo" => $campoExtra->getUID(),
                                    "data" => $campoExtra->getData()
                                ));
                            }
                        }
                    }
                break;
                case elemento::PUBLIFIELDS_MODE_DELTA:
                    $arrayCampos = array('apellidos','nombre','numero_seguridad_social','fecha_alta_empresa',
                        'sexo','fecha_nacimiento','uid_pais','dni','situacion_profesional','uid_codigoocupacion',
                        'regimen_seguridad_social','direccion','uid_provincia');
                break;

                case usuario::PUBLIFIELDS_MODE_TIMEZONE:
                    $arrayCampos = new FieldList;
                    $arrayCampos["timezone_offset"] = new FormField();
                break;

                case elemento::PUBLIFIELDS_MODE_PREFS:
                    $arrayCampos = new FieldList;
                    $arrayCampos["dissmiss_tour_comments"] = new FormField();
                break;

                case elemento::PUBLIFIELDS_MODE_SYSTEM:
                    $arrayCampos['updated']= new FormField(array());
                    return $arrayCampos;
                break;
            }
        }


        //AL EDITAR, NO MIRAMOS SI EL DNI EXISTE
        if( isset($modo) && $modo == elemento::PUBLIFIELDS_MODE_EDIT && isset($arrayCampos["dni"]) && !$usuario->esStaff() ){
            $arrayCampos["dni"]["tag"] = "span";
        }
        return $arrayCampos;
    }

    public function getTableFields()
    {
        return array(
            array("Field" => "uid_empleado",                "Type" => "int(10)",        "Null" => "NO",     "Key" => "PRI",     "Default" => "",                    "Extra" => "auto_increment"),
            array("Field" => "nombre",                      "Type" => "varchar(100)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "apellidos",                   "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "dni",                         "Type" => "varchar(18)",    "Null" => "NO",     "Key" => "UNI",     "Default" => "",                    "Extra" => ""),
            array("Field" => "nif",                         "Type" => "varchar(15)",    "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "fecha_conformidad",           "Type" => "timestamp",      "Null" => "NO",     "Key" => "",        "Default" => "0000-00-00 00:00:00", "Extra" => ""),
            array("Field" => "password",                    "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "token_password",              "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "fecha_token",                 "Type" => "int(16)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "updated",                     "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "path_photo",                  "Type" => "varchar(500)",   "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_cnae",                    "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "descripcion_puesto",          "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "categoria_profesional",       "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "es_manager",                  "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "unitwork",                    "Type" => "varchar(100)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "fecha_nacimiento",            "Type" => "date",           "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "sexo",                        "Type" => "varchar(20)",    "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_pais",                    "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "1",                   "Extra" => ""),
            array("Field" => "telefono",                    "Type" => "varchar(50)",    "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "movil",                       "Type" => "varchar(20)",    "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "direccion",                   "Type" => "varchar(225)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_provincia",               "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_municipio",               "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "cp",                          "Type" => "int(10)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_tipocontrato",            "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "situacion_profesional",       "Type" => "varchar(200)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "regimen_seguridad_social",    "Type" => "varchar(200)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "numero_seguridad_social",     "Type" => "varchar(200)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "fecha_alta_empresa",          "Type" => "date",           "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "fecha_baja_empresa",          "Type" => "date",           "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "fecha_alta_teletrabajo",      "Type" => "varchar(50)",    "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "delegado_prevencion",         "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "delegado_ubicacion",          "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "ett",                         "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "ceco",                        "Type" => "varchar(200)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_centrocotizacion",        "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_codigoocupacion",         "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "planta_modulo",               "Type" => "varchar(200)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "edificio",                    "Type" => "varchar(200)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "alias",                       "Type" => "varchar(200)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_agrupador_cliente",       "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "email_secretaria",            "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "talla_pantalon",              "Type" => "varchar(150)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "talla_camisa",                "Type" => "varchar(150)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "numero_pie",                  "Type" => "varchar(150)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "email",                       "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "persona_contacto",            "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "es_responsable_trabajos",     "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "sap",                         "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "numero_empleado",             "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "objeto_contrato",             "Type" => "varchar(2048)",  "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "created",                     "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_responsable",             "Type" => "int(11)",        "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "timezone_offset",             "Type" => "double",         "Null" => "NO",     "Key" => "",        "Default" => "-1",                  "Extra" => ""),
            array("Field" => "uid_usuario_location",        "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "latlng",                      "Type" => "varchar(40)",    "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "location_timestamp",          "Type" => "timestamp",      "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "active_uid_checkin",          "Type" => "int(11)",        "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => "")
        );
    }
}
