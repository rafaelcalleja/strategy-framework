<?php

class usuario extends categorizable implements Iactivable, Icategorizable, Iparent, Ielemento, Iusuario
{

    //TIEMPOS EN SEGUNDOS PARA PASAR A INACTIVIDAD
    const USER_INACTIVE_TIME = 60;
    const USER_OFFLINE_TIME = 500;


    // POSIBLES VALORES DE CONEXION DE LOS USUARIOS
    const USER_OFFLINE = 0;
    const USER_ONLINE = 1;
    const USER_INACTIVE = 2;
    const USER_LOCKED = 3;


    // MODOS DE FILTRO PARA USUARIOS
    const FILTER_VIEW_EXACTLY = 1;
    const FILTER_VIEW_USER = 2;
    const FILTER_VIEW_GROUP = 3;


    const PUBLIFIELDS_MODE_TIMEZONE     = 'timezone';
    const PUBLIFIELDS_MODE_CONFORMIDAD  = 'conformidad';

    const DEFAULT_UPLOAD = 52428800;
    const LIMITED_UPLOAD = 3145728;

    const APACHE2_REALM = 'Private Area';

    const MESSAGE_MAX_DAYS = 60;

    /**
     * Interval when users are considerated actives
     *
     */
    const RECENTLY_INTERVAL = 'interval -5 month';

    // Si el objeto se serializa podremos verficar el tiempo transcurrido desde el acceso
    protected $time;

    // CONTRASEÑA DEL USUARIO
    public $password;

    /***
        CONSTRUYE EL OBJETO USUARIO, LLAMANDO A "instance" DE LA CLASE ELEMENTO
        SAVE ON SESSION INDICA SI AL ELIMINAR EL ELEMENTO, LO GUARDARA EN LA SESSION
    */
    public function __construct( $param , $extra = false ){
        $this->tipo = "usuario";
        $this->tabla = TABLE_USUARIO;
        $this->uid_modulo = 2;

        $this->instance( $param, $extra );
    }


    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return User\User
     */
    public function asDomainEntity()
    {
        $info = $this->getInfo();
        return $this->app['user.repository']->factory($info);
    }

    public static function getRouteName () {
        return 'user';
    }


    public function getLastActionTimestamp()
    {
        return (int) $this->obtenerDato("fecha_accion");
    }

    /***
       * Called from buscador.class when search for employees or machines with "empresa" filter
       *
       *
       *
       */
    public static function onSearchByCompany ($data, $filter, $param, $query) {
        $value  = reset($filter);
        $SQL    = false;
        $table  = TABLE_PERFIL;

        if (is_numeric($value)) {
            $users = "SELECT uid_usuario FROM {$table} p WHERE p.uid_usuario = usuario.uid_usuario AND uid_empresa = {$value}";
        } else {
            $companies = TABLE_EMPRESA;
            $users = "SELECT uid_usuario
            FROM {$table} p
            INNER JOIN {$companies} e USING (uid_empresa)
            WHERE p.uid_usuario = usuario.uid_usuario
            AND (e.nombre LIKE '%{$value}%' OR e.cif LIKE '%{$value}%')";
        }

        $SQL  = "uid_usuario IN ({$users})";

        return $SQL;
    }

    /***
       * Return the number of users allowed to change to the new app
       *
       *
       *
       * return Int
       *
       */
    public function countAllowed () {
        $table  = TABLE_USUARIO;
        $SQL    = "SELECT count(uid_usuario) FROM {$table} WHERE newapp_allowed = 1";

        return (int) db::get($SQL, 0, 0);
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
        if (is_mobile_device()) {
          return 2;
        }

        return (int) $this->obtenerDato('app_version');
    }




    /***
       * Change the user app version
       *
       *
       *
       * return Bool
       *
       */
    public function setAppVersion ($version) {
        if (is_numeric($version) === false) {
            return false;
        }

        $SQL = "UPDATE {$this->tabla} SET app_version = {$version} WHERE uid_usuario = {$this->getUID()}";
        return (bool) $this->db->query($SQL);
    }


    /***
       * Tell us if the user is a beta tester or not.
       *
       *
       *
       * return Bool
       *
       */
    public function isBetatester () {
        return (bool) $this->configValue('betatester');
    }



    /***
       * Add or remove as beta tester
       *
       *
       *
       * return Bool
       *
       */
    public function setBetatester ($set = true) {
        $set = (int) $set;
        $SQL = "UPDATE {$this->tabla} SET config_betatester = {$set} WHERE uid_usuario = {$this->getUID()}";

        return $this->db->query($SQL);
    }

    /***
       * Tell us if the user have tours active.
       *
       *
       *
       * return Bool
       *
       */
    public function hasToursEnabled()
    {
        if ($this->esStaff()) {
            return (bool) $this->configValue('tours');
        } else {
            return true;
        }
    }

    /***
       * Tell us if the user can do the @option over the @item (@parent is aux)
       *
       *
       *
       *
       */
    public function canAccess ($item, $option = \Dokify\AccessActions::VIEW, $parent = null, $ref = null)
    {
        // if the user can edit the request you have to check the reqtype permission
        if ($item instanceof solicituddocumento) {
            $ActionsWhiteList = [\Dokify\AccessActions::FILTER, \Dokify\AccessActions::SHOW_TRASH];
            if ($item->isEditableBy($this) || in_array($option, $ActionsWhiteList)) {
                $item = $item->obtenerDocumento();
            } else {
                return false;
            }
        }

        if ($item instanceof documento) {
            if ($parent instanceof solicitable) {
                $item->elementoFiltro = $parent;
            } else {
                $item->elementoFiltro = null;
            }
        }

        // with this "hack" we can use $parent for test different config value
        $config = null;
        if (is_bool($parent)) {
            $config = (int) $parent;
        }


        $options = $this->getAvailableOptionsForModule($item, $option, $config, $ref, $parent);

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
    public static function getActives ($SQLOptions = []) {
        $table = TABLE_USUARIO;
        $where = isset($SQLOptions['where']) ? 'AND ' . $SQLOptions['where'] : '';


        $SQL = "SELECT uid_usuario
            FROM {$table}
            WHERE 1
            AND DATEDIFF(NOW(), FROM_UNIXTIME(fecha_ultimo_acceso)) < 150
            {$where}
            ORDER BY usuario
        ";

        if ($result = db::get($SQL, "*", 0, "empresa")) {
            return new ArrayObjectList($result);
        }

        return new ArrayObjectList;
    }



    /***
       *
       *
       *
       *
       *
       */
    public function getViewData (Iusuario $user = NULL) {
        $viewData = parent::getViewData($user);

        $status = $this->verEstadoConexion(true);
        $viewData['status'] = usuario::getColorFromStatus($status);

        return $viewData;
    }


    /***
       *
       *
       *
       *
       *
       */
    public function setUserAgent ($ua, $locale) {
        return $this->update(array('user_agent' => $ua, 'locale' => $locale), Iusuario::PUBLIFIELDS_MODE_USERAGENT, $this);
    }


    /***
       *
       *
       *
       *
       *
       */
    public function getUserAgentData () {
        if ($ua = $this->obtenerDato('user_agent')) {
            // custom func
            return parse_ua($ua);
        }
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
        $data = array("latlng" => $location, "location_timestamp" => date('Y-m-d h:i:s'));

        return $this->update($data, elemento::PUBLIFIELDS_MODE_GEO, $this);
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
        return $this->obtenerDato('direccion');
    }

    public function getAnalyticsReadonlyCondition () {
        $readonly = "( readonly = 0";
        if ($this->esStaff()) {
            $readonly .= " OR name = 'agrupador_asignado_bool'";
        }

        $readonly .= ")";

        return $readonly;
    }

    public function getCookieToken()
    {
        return $this->obtenerDato('pass_apache2', true);
    }

    public function refreshCookieToken($password = null)
    {
        $app = \Dokify\Application::getInstance();
        $username = $this->getUsername();

        if (null === $password) {
            $password = self::randomPassword();
        }
        $token = $app['encoder.apache2']->encode($username, $password.time());

        $table = $this->tabla;
        $userUid = $this->getUID();

        $sql = "UPDATE {$table}
        SET pass_apache2 = '{$token}'
        WHERE uid_usuario = {$userUid}";

        if (!$this->db->query($sql)) {
            error_log($this->db->lastError());
        }
    }

    public function setTimezoneOffset ($offset) {
        return $this->update(array('timezone_offset' => $offset), self::PUBLIFIELDS_MODE_TIMEZONE, $this);
    }

    public function getTimezoneOffset () {
        return (int) $this->obtenerDato('timezone_offset');
    }

    public function getTimeZone()
    {
        try {
            return new DateTimeZone($this->obtenerDato('timezone'));
        } catch (Exception $e) {
            return new DateTimeZone('Europe/Madrid');
        }
    }

    public function getCurrentTime(){
        $timestamp = time();
        $timestamp = $timestamp - (3600 * $this->getTimezoneOffset());
        return $timestamp;
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

    public function canValidateFor(empresa $empresa) {
        $empresaUsuario = $this->getCompany();
        $corp = $empresaUsuario->perteneceCorporacion();

        $same = $empresa->compareTo($empresaUsuario);
        $fromCorp = $corp && $empresa->compareTo($corp);
        $viewAll = $this->configValue("viewall");
        $companyOfMyGroup = $empresaUsuario->esCorporacion() && $empresaUsuario->getStartIntList()->contains($empresa->getUID());
        $companiesToValidate = $empresaUsuario->getValidationCompanies();

        return $same || $fromCorp || $viewAll || $companyOfMyGroup || $companiesToValidate->contains($empresa);
    }

    public function jumpTo(empresa $empresa)
    {
        $perfil = $this->perfilActivo();
        $fields = ["uid_empresa" => $empresa->getUID()];

        if ($this->esStaff()) {
            if ($empresa->esCorporacion()) {
                $corporation = $empresa;
            } else {
                $corporation = $empresa->perteneceCorporacion();
            }

            if ($corporation) {
                $fields['uid_corporation'] = $corporation->getUID();
            } else {
                $fields['uid_corporation'] = 'NULL';
            }
        }

        $done = $perfil->update($fields, elemento::PUBLIFIELDS_MODE_ATTR, $this);
        $this->cache->delete('empresa-perfil-'.$perfil->getUID());
        $this->cache->clear ("usuario-getCompany-{$this->getUID()}-{$perfil}");
        $this->cache->clear ('usuario-accesoElemento-'.$perfil.'-*');

        return $done;
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

    public function getZendeskURL($URL = false, $timestamp = false, $localeid = 2){
        $key       = "HOfP0KzwynOIfdDHzF58m9uLIDQLXoMoRDrcVMsdT37Cszpx";
        $subdomain = "dokify";
        $now       = time();
        $name = $this->obtenerDato("nombre") . " " . $this->obtenerDato("apellidos");
        $email = $this->obtenerDato("email");

        $token = array(
          "jti"   => md5($now . rand()),
          "iat"   => $now,
          "name"  => $name,
          "email" => $email,
          "external_id" => $this->getUID()
        );

        $jwt = JWT::encode($token, $key);


        return "https://" . $subdomain . ".zendesk.com/access/jwt?jwt=" . $jwt;
    }

    /**
     * Recuperar un ArrayObjectList de objetos "empresasolicitud" en estado pendiente de acción por parte del usuario
     * @param  String $type
     * @param  int    $status
     * @return ArrayObjectList | empresasolicitud
     */
    public function getEmpresaSolicitudPendientes($type = false, $status = solicitud::ESTADO_CREADA)
    {
        $companyIntList = $this->getCompany()->getStartIntList()->toComaList();

        $tableCompany = TABLE_EMPRESA;
        $tableEmployee = TABLE_EMPLEADO;
        $tableMachine = TABLE_MAQUINA;
        $tableNotification = TABLE_EMPRESA . "_solicitud";

        $companyExists = "SELECT uid_empresa
        FROM {$tableCompany}
        WHERE uid_empresa = uid_elemento";

        $employeeExists = "SELECT uid_empleado
        FROM {$tableEmployee}
        WHERE uid_empleado = uid_elemento";

        $machineExists = "SELECT uid_maquina
        FROM {$tableMachine}
        WHERE uid_maquina = uid_elemento";

        $sql = "SELECT uid_empresa_solicitud FROM {$tableNotification}
        WHERE uid_empresa
        IN ({$companyIntList})
        AND uid_empresa_origen IS NOT NULL
        AND IF (uid_modulo = 1, uid_elemento IN ($companyExists), 1)
        AND IF (uid_modulo = 8, uid_elemento IN ($employeeExists), 1)
        AND IF (uid_modulo = 14, uid_elemento IN ($machineExists), 1)";

        if (is_numeric($status)) {
            $sql .= " AND estado = " . $status;
        }

        if ($type !== false) {
            $type = db::scape($type);
            $sql .= " AND type = '{$type}' ";
        } else {
            $types = solicitud::getAllTypes();
            $typeList = implode("','", $types);
            $sql .= " AND type IN ('{$typeList}')";
        }

        $items = $this->db->query($sql, "*", 0, 'empresasolicitud');
        return new ArrayObjectList($items);
    }


    public function crearSolicitud(empresa $empresa, Ielemento $elemento, $type, $data = null, $message = null)
    {
        $data = array(
            "data" => json_encode($data),
            "uid_empresa" => $empresa->getUID(),
            "uid_elemento" => $elemento->getUID(),
            "uid_modulo" => $elemento->getModuleId(),
            "uid_usuario" => $this->getUID(),
            "type" => $type,
            "uid_empresa_origen" => $this->getCompany()->getUID()
        );

        if (isset($message)) {
            $data["message"] = $message;
        }

        return new empresasolicitud($data, $this);
    }

    public function perteneceCorporacion(){
        return $this->getCompany()->perteneceCorporacion();
    }

    /********* INTERFAZ ETIQUETABLE ****************/
    public static function referenceEtiquetable(){
        return "perfil";
    }

    public function obtenerElementosPapelera(usuario $usuario, $type){
        return $this->obtenerPerfiles(true);
    }

    public function enviarPapelera($parent, usuario $usuario){
        return $this->bloquearPerfil($parent, $usuario);
    }

    public function restaurarPapelera($parent, usuario $usuario){
        return $this->desbloquearPerfil($parent, $usuario);
    }

    public function isActive() {
        return $this->obtenerPerfil()->isActive();
    }

    public function obtenerElementosActivables(usuario $usuario = NULL){
        return $this->getCompanies();
    }

    public function obtenerElementosSuperiores(){
        return $this->getCompanies();
    }

    public function isDeactivable($parent, usuario $usuario){
        return true;
    }

    public function obtenerDataModels(){
        return datamodel::getDataModels($this);
    }

    public function obtenerDataExports(){
        return $this->obtenerObjetosRelacionados(TABLE_DATAEXPORT, "dataexport");
    }

    public function obtenerDataImports(){
        return $this->obtenerObjetosRelacionados(TABLE_DATAIMPORT, "dataimport");
    }

    // Sirve para saber que el usuario esta online
    public function touch(){
        $segundosDesdeAcceso = "TIME_TO_SEC( TIMEDIFF( NOW(), FROM_UNIXTIME(fecha_accion) ) )";
        $inactiveTime = self::USER_INACTIVE_TIME - 5;
        $sql = "
        UPDATE ". $this->tabla ."
        SET
            conexion = if( $segundosDesdeAcceso > $inactiveTime,
                if( conexion = ".self::USER_ONLINE." OR conexion = ".self::USER_INACTIVE.",
                    ".self::USER_INACTIVE.",
                ".self::USER_ONLINE." ),
            ". self::USER_ONLINE. "),

            fecha_accion = " . time() ."
        WHERE uid_usuario = ".  $this->getUID();

        return $this->db->query($sql);
    }

    public function getImage($real = true){
        // cargamos este recurso que sabemos siempre esta aqui si no hay imagen de usuario
        $alt = "https://d2uqhr26ya75ul.cloudfront.net/img/silhouette.png";
        $email = $this->getEmail();

        return 'https://www.gravatar.com/avatar/' . md5(trim($email)) .'?d=' . urlencode($alt);
    }


    /**
     * Set first login timestamp if applies, and change to new app if allowed
     *
     */
    public function checkFirstLogin()
    {
        if ($this->getFirstLogin() != 0) {
          return null;
        }

        // update app version if user can access when first login
        $appVersion = $this->getAppVersion();
        if (1 === $appVersion) {
          $appVersion = 2;
        }

        $now = time();

        $sql = "UPDATE {$this->tabla}
        SET fecha_primer_acceso = {$now},
        app_version = {$appVersion}
        WHERE uid_usuario = {$this->getUID()}";

        $this->db->query($sql);
    }

    public function getFirstLogin(){
        return $this->obtenerDato("fecha_primer_acceso");
    }

    public function getMostrarAsistente(){
        return $this->obtenerDato("flag_asistente");
    }

    /** NOS INDICA SI ES UN USUARIO QUE ESTA ACCEDIENDO RECIENTEMENTE A LA APLICACION **/
    public function isNew(){
        $time = $this->getFirstLogin();
        if( (time()-$time) < ( 60 * 60 * 24 * 3 ) || $time == 0 ){
            return true;
        }
        return false;
    }

    public static function getValidUserName($name){
        $aux = archivo::cleanFilenameString($name);
        $search = array(" ",".","ñ","&","-",")","(", utf8_encode("Ç"),"}","{");
        $replace = array("","","n","","_","","","","","");
        $name = str_replace($search,$replace,$aux);

        if( strlen($name) > 45 ){
            $name = substr( $name, 0, 45);
        }

        // Vamos a ver si el usuario ya existe
        $exists = usuario::login($name);
        if( $exists instanceof usuario ){
            $add = substr( md5(uniqid()), 0, 4);
            return self::getValidUserName($name.$add);
        }

        /// Check valid..
        $reg = self::getUserRegExp();



        // El nombre válido
        return $name;
    }

    public static function getSearchData(Iusuario $usuario, $papelera = false, $all = false, $network = true)
    {
        $searchData = array();
        if (!$usuario->accesoModulo(__CLASS__)) {
            return $searchData;
        }

        $userCompany        = $usuario->getCompany();
        $sameCompanyUsers   = "SELECT uid_usuario FROM agd_data.perfil WHERE 1
        AND uid_usuario = perfil.uid_usuario
        AND perfil.uid_empresa IN ({$userCompany->getStartIntList()->toComaList()})";

        if ($usuario->esStaff()) {
            $limit = "uid_usuario IN (
            SELECT uid_usuario FROM agd_data.perfil
            WHERE uid_usuario = perfil.uid_usuario
            ";

            if (!$all) $limit .= " AND uid_empresa IN (<%companies%>)";
            if (!$network) $limit .=  " AND uid_usuario IN ({$sameCompanyUsers})";

            if (is_bool($papelera)) $limit .= " AND perfil.papelera = ". ((int) $papelera);
            $limit .= ")";
        } else {
            if (is_bool($papelera)) {
                $limit = " uid_usuario IN (
                SELECT uid_usuario
                FROM agd_data.perfil
                WHERE uid_usuario = perfil.uid_usuario
                AND perfil.papelera = " . ((int) $papelera) .
                " AND uid_empresa IN ({$userCompany->getStartIntList()->toComaList()})
                )";
            } else {
                $limit = "uid_usuario IN ({$sameCompanyUsers})";
            }
        }




        $data = array(
            "type"      => "usuario",
            "fields"    => array("usuario", "concat(nombre,' ',apellidos)", "email"),
            "limit"     => $limit,
            "accept"    => array(
                "tipo"      => "usuario",
                "uid"       => true,
                "empresa"   => true,
                "list"      => true,
                "rol"       => true
            )
        );

        $data['accept']['empresa'] = array(__CLASS__, 'onSearchByCompany');

        $data['accept']['rol'] = function($data, $filter, $param, $query){
            $value = db::scape(utf8_decode(reset($filter)));
            if (empty($value)) {
                return null;
            }

            if (is_numeric($value)) {
                return "uid_usuario IN (SELECT uid_usuario FROM ".TABLE_PERFIL." WHERE rol='{$value}')";
            }

            return "uid_usuario IN (SELECT p.uid_usuario FROM ".TABLE_ROL." r INNER JOIN ".TABLE_PERFIL." p ON r.uid_rol=p.rol WHERE r.nombre LIKE '%{$value}%')";
        };

        $data['accept']['etiqueta'] = function($data, $filter, $param, $query){
            $value = db::scape(utf8_decode(reset($filter)));

            $SQL =" uid_usuario IN ( SELECT uid_usuario FROM agd_data.perfil WHERE uid_perfil IN (
                        SELECT uid_perfil FROM agd_data.perfil_etiqueta WHERE uid_etiqueta IN (
                            SELECT uid_etiqueta FROM agd_data.etiqueta WHERE nombre like '%$value%'
                        )
                    )) ";
            return $SQL;
        };

        $data['accept']['usuario'] = function($data, $filter, $param, $query){
            $value  = reset($filter) ? '1' : '0';
            $SQL    = ' ( conexion = '. $value .' ) ';
            //dump($SQL);
            return $SQL;
        };

        $searchData[ TABLE_USUARIO ] = $data;

        return $searchData;
    }

    public function obtenerAccionesRelacion(agrupamiento $agrupamiento, Iusuario $usuario){
        $template = Plantilla::singleton();
        $acciones = array();

        if( $usuario->esStaff() ){
            $acciones[] = array(
                "innerHTML" => $template->getString("configurar_permisos_especificos"),
                "className" => "box-it",
                "img" => RESOURCES_DOMAIN . "/img/famfam/cog_edit.png",
                "href" => "usuario/permisosespecificos.php?m=agrupador&poid=" . $this->getUID() . "&o=%s"
            );
        }

        return $acciones;

    }


    public function obtenerEmpresasClientesActivas(){
        $empresas = new ArrayObjectList();

        $perfiles = $this->obtenerPerfiles();
        foreach($perfiles as $perfil){
            $empresas[] = $perfil->getCompany();
        }

        return $empresas->unique();
    }

    public function isEnterprise(){
        return $this->getCompany()->isEnterprise();
    }


    public function obtenerConteoValidacion($start=false, $end=false){
        $sql = "SELECT count(uid_validacion)
                FROM ". TABLE_VALIDACION ."
                WHERE uid_usuario_validacion = ". $this->getUID() ."
        ";
        if( $start ) { $sql .= " AND fecha_validacion > $start"; }
        if( $end ) { $sql .= " AND fecha_validacion < $end"; }

        if( !$start && !$end ){ // por defecto hoy
            $sql .= " AND date_format(fecha_validacion,'%Y-%m-%d') = '". date('Y-m-d') ."'";
        }

        return $this->db->query($sql, 0, 0);
    }


    /**
        NOS RETORNA OBJETOS HELPER QUE ESTE USUARIO DEBE VISUALIZAR...
    **/
    public function getHelpers($href=false){
        $sql = "SELECT uid_helper, helper
            FROM ". TABLE_HELPER ." h
            WHERE papelera = 0
            AND uid_helper NOT IN (
                SELECT uid_helper FROM ". TABLE_USUARIO ."_helper uh
                WHERE uh.uid_helper = h.uid_helper
                AND uid_usuario = $this->uid
            )";

        $data = $this->db->query($sql, true);

        $coleccion = array();
        foreach($data as $line){
            $helper = new helper($line["helper"], $href);
            $coleccion[] = $helper;
        }

        return $coleccion;
    }


    public function createContact($usuario){
        $newdata = array();
        $data = $this->getInfo();
        $data["uid_empresa"] = $this->getCompany()->getUID();

        $contacto = new contactoempresa($data, $usuario);
        if( $contacto->getUID() && $contacto->exists() ){
            $contacto->hacerPrincipal();
            return $contacto;
        } else {
            return false;
        }
    }


    /**
        COMPRUEBA SI ESTE USUARIO ES VISIBLE POR OTROS
    */
    public function accesiblePara( $usuarioActivo ){
        $empresas = $this->getCompanies();
        foreach( $empresas as $empresa ){
            if( $usuarioActivo->accesoElemento( $empresa ) ){
                //dump( "El usuario actual tiene permiso para ". $empresa->getUID() );
                return true;
            }
        }
        return false;
    }

    /** RETORNA TRUE O FALSE SI EL USUARIO ES EL ADMINISTRADOR DEL SISTEMA */
    public function esAdministrador(){
        return $this->configValue("admin");
    }

    /** RETORNA TRUE O FALSE SI EL USUARIO ES EL DE SATI DEL SISTEMA */
    public function esSATI(){
        return $this->configValue("sat") || $this->isAgent();
    }

    /** RETORNA TRUE O FALSE SI EL USUARIO ES EL DE SATI DEL SISTEMA */
    public function isAgent(){
        return (bool) $this->configValue("agent");
    }

    public function getAgentActionsBlackList() {
        // solo uso las keys del array para identificar mejor las acciones
        return array(
            "editar" => 4,
            "papelera" => 5,
            "anular" => 6,
            "anexar" => 7,
            "validar" => 9,
            "configurar" => 11,
            "Etiquetar" => 12,
            "Atributos" => 13,
            "eliminar" => 14,
            "crear-nuevo" => 22,
            "Borrar Relacion" => 29,
            "Plantilla" => 30,
            "Evaluacion Riesgos" => 31,
            "Activar" => 36,
            "Asignar Maquinas" => 38,
            "Idiomas" => 40,
            "filtrar" => 41,
            "Estructura" => 48,
            "Avisos" => 49,
            "aptitud" => 50,
            "Reanexar" => 56,
            "Borrar" => 57,
            "Formatos" => 59,
            "Importar" => 67,
            "Hacer Principal" => 71,
            "Enviar Papelera" => 91,
            "Mover" => 127,
            "Revisar" => 131,
            "guardar-asignacion" => 153,
            "firmar" => 173
        );
    }

    /** NOS INDICA SI ES VALIDADOR O NO **/
    public function esValidador(){
        return (($this->configValue("validador") && $this->getCompany()->isPartner(empresaPartner::TYPE_VALIDATOR)));
    }

    /** RETORNA TRUE O FALSE SI EL USUARIO INTERNO DE LA COMPAÑIA (SATI O ADMIN) */
    public function esStaff(){
        if( $this->esAdministrador() || $this->esSATI() ){
            return true;
        }
        return false;
    }

    /* NORMALMENTE ES LLAMADA DESDE LA FUNCION register_shutdown_function PARA REGISTRAR EL PASO DEL USUARIO*/
    public function addPageHistory($page){
        $sql = "INSERT INTO ". DB_CORE .".usuario_pagina ( uid_usuario, pagina ) VALUES (
            $this->uid, '". db::scape($page) ."'
        )";
        return $this->db->query($sql);
    }

    /* OBTENER LA ULTIMA PAGINA VISITADA POR EL USUARIO... */
    public function getLastPage($hash=false){
        $sql = "SELECT pagina FROM ". DB_CORE .".usuario_pagina WHERE uid_usuario = $this->uid";
        if( $hash ){
            $sql .= " AND INSTR(pagina,'#')";
        }
        $sql .= " ORDER BY fecha DESC LIMIT 1";
        $pagina = $this->db->query($sql,0,0);
        return $pagina;
    }

    /***
       * alias for self::getUnreadMessages use these instead
       *
       *
       *
       *
       */
    public function getUnreadAlerts () {
        $tpl        = Plantilla::singleton();
        $messages   = $this->getUnreadMessages();
        $colection  = array();

        foreach($messages as $message){
            $msgdata = array();
            $msgdata["uid"] = $message->getUID();
            $msgdata["action"] = $message->getAction();
            $msgdata["message"] = $message->getHTML();
            if ($href = $message->getHref()) $msgdata["href"] = $href;
            if ($title = $message->getTitle()) $msgdata["title"] = $title;
            if ($company = $message->getCompany()) $msgdata["companyInviter"] = $tpl->getString("de"). ": <strong>" .substr($company->getUserVisibleName(),0, 40)."</strong>";

            $colection[] = $msgdata;
        }

        return $colection;
    }

    /***
       * return ArrayObjectList of company-to-company messages
       *
       *
       *
       *
       */
    public function getUnreadMessages() {
        $userCompany    = $this->getCompany();
        $origin         = $userCompany->getOriginCompanies();
        $messages       = TABLE_MESSAGE;
        $reads          = TABLE_USUARIO ."_message";
        $maxDays        = self::MESSAGE_MAX_DAYS;

        $empresasSuperiores = $userCompany->obtenerEmpresasSolicitantes();
        $empresasSuperiores = count($empresasSuperiores) ? $empresasSuperiores->toComaList() : '0';

        $readed = "SELECT uid_message FROM {$reads} WHERE uid_usuario = {$this->getUID()}";
        $sql = "SELECT uid_message FROM {$messages} WHERE 1
            AND activo = 1
            AND message_es != ''
            AND uid_usuario != {$this->getUID()}
            AND uid_message NOT IN ({$readed})
            AND (ADDDATE(createdAt, {$maxDays}) >= CURRENT_TIMESTAMP OR createdAt is null )
            AND (
                (
                    uid_empresa IN ({$origin->toComaList()})
                    AND visible_usuarios = 1
                )
                OR
                (
                    uid_empresa IN ($empresasSuperiores)
                    AND uid_empresa NOT IN ({$origin->toComaList()})
                    AND visible_usuarios_contratas = 1
                )
            )
        ";

        if ($messages = $this->db->query($sql, "*", 0, "message")) {
            return new ArrayObjectList($messages);
        }

        return new ArrayObjectList;
    }

    public function markAlertAsReaded($idmessage){
        if ($idmessage instanceof message) {
            $idmessage = $idmessage->getUID();
        }

        $sql = "INSERT INTO ". TABLE_USUARIO ."_message ( uid_usuario, uid_message ) SELECT {$this->getUID()}, uid_message FROM ". DB_DATA .".message WHERE uid_message = ". db::scape($idmessage);
        return $this->db->query($sql);
    }

    /***
        TRUE O FALSE, PUEDE TENER LA OPCION DE DESPLEGABLES O SIMPLEMENETE MOSTRAR LOS ICONOS
    */
    public function opcionesDesplegable(){
        return $this->configValue("tipoopciones");
    }

    public function getIcon($size=false){
        $info = $this->getInfo();
        $icon = $info["icon"];
        if( trim($icon) ){
            return RESOURCES_DOMAIN ."/$icon";
        } else {
            return RESOURCES_DOMAIN ."/img/famfam/thumb_up.png";
        }
    }

    /** PASANDO EL NOMBRE DE UNA OPCION DE CONFIGURACION NOS DICE SI EL USUARIO LA TIENE ACTIVA (true) O NO (false) */
    /** SI EL PARAMETRO ES UN ARRAY, PROCEDEMOS A MODIFICAR SU VALOR, SE PASA NOMBRE_DEL_CAMPO => NUEVO_VALOR */
    public function configValue($value){

        if( is_array($value) && count($value) ){
            $ok = true;
            foreach($value as $field => $newValue){
                $sql = "UPDATE ".TABLE_PERFIL." SET config_".$field." = ".$newValue." WHERE uid_perfil = ".$this->idPerfilActivo();
                if( !$this->db->query( $sql ) ){
                    $ok = false;
                } else {
                    $this->cache->deleteData("configvalue-{$this}-$field");
                }
            }
            return $ok;
        }

        $cacheString = "configvalue-{$this}-{$value}";
        $estado = $this->cache->getData($cacheString);
        if( $estado !== null ){
            return $estado;
        }

        $datos = $this->getInfo();
        if( isset($datos["config_$value"]) ){
            $value = ($datos["config_$value"]) ? true : false;
            $this->cache->addData( $cacheString, $value );
        } else {
            $value = $this->perfilActivo()->configValue($value);
        }

        return $value;
    }

    /**     RETORNA UN ARRAY CON LOS UID DE TODAS LAS EMPRESAS EN LAS QUE TIENE PERFIL  */
    public function obtenerIdEmpresas($eliminadas = false){
        $condicion = elemento::construirCondicion($eliminadas);

        $arrayIDS = array();
        $arrayRelaciones = $this->obtenerRelacionados( TABLE_PERFIL , "uid_usuario", "uid_empresa", $condicion);
        foreach( $arrayRelaciones as $datosRelacion ){
            $arrayIDS[] = $datosRelacion["uid_empresa"];
        }
        return $arrayIDS;
    }

    /** RETORNA UN ARRAY CON LOS OBJETOS REFERENTES A TODOAS LAS EMPRESAS DONDE TIENE UN PERFIL */
    public function getCompanies($eliminadas = false){
        $arrayObjetos = array();
        $arrayUIDS = $this->obtenerIdEmpresas($eliminadas);
        foreach($arrayUIDS as $uidEmpresa){
            $arrayObjetos[] = new empresa( $uidEmpresa, false );
        }
        return new ArrayObjectList($arrayObjetos);
    }

    /***
        SABER SI UN USUARIO PUEDE VER UN SOLICITANTE DETERMINADO
            @param $solicitante -> objeto que solicita un documento
            @param $documento -> el documento que se solicita
    */
    public function accesoSolicitante($solicitante, $documento){
        $solicitantes = $documento->obtenerSolicitantes( $this );
        foreach( $solicitantes as $solicitanteVisible ){
            if( util::comparar($solicitanteVisible, $solicitante) ){
                return true;
            }
        }
        return false;
    }

    /***
        ¡¡¡¡ CUIDADO !!! Esta funcion borra la cache de proceso para chequear valores en bbdd nuevamente.
        NOS INDICA SI UN USUARIO DETERMINADO TIENE POSIBILIDAD DE VER UN ELEMENTO EN OTRO DE SUS PERFILES
    **/
    public function buscarPerfilAcceso(Ielemento $objeto){
        /** la mejor manera es simular la cache de la funcion perfilActivo para que nos devuelva el resultado deseado **/
        $current = $this->perfilActivo();
        if( $this->accesoElemento($objeto, null, null) ){
            return $this->perfilActivo();
        } else {
            $perfiles = $this->obtenerPerfiles();
            foreach($perfiles as $perfil ){
                if( $perfil->getUID() != $this->perfilActivo()->getUID() ){ // este no puede ser en ningun caso
                    $perfil->activate();
                    $this->cache->clear();
                    $this->cache->addData('perfil-usuario-'.$this->uid, $perfil );
                    if( $this->accesoElemento($objeto, null, null) ){
                        $current->activate();
                        $this->cache->clear();
                        return $perfil;
                    }
                }
            }
            $current->activate();
        }
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

    public function clearAccessData()
    {
      $company = $this->getCompany();

      // clean cache
      $this->cache->clear('buscador-getCompaniesIntList-*');
      $this->cache->clear('empresa-getAllCompaniesIntList-*');
      $this->cache->clear('getViewIndexTable-*');
      $this->cache->clear('usuario-accesoElemento-*');

      // force the index reload
      $this->app['index.repository']->expireIndexOf(
          'empresa',
          $company->asDomainEntity(),
          $this->asDomainEntity(),
          true
      );
    }

    /***
        RETORNARA TRUE SI TIENE ACCESO AL ELEMENTO INDICADO Y FALSE SI NO

    */
    public function accesoElemento(Ielemento $elemento, empresa $empresa = NULL, $papelera = false, $bucle = 0  ){
        if (!$elemento instanceof Ielemento) return false;

        $resultado = false;

        $trash = is_bool($papelera) ? (int) $papelera : 'null';
        // Guardar y extraer datos de la cache
        $cacheString = implode('-', array(__CLASS__, __FUNCTION__, $this->obtenerPerfil(), $elemento, $empresa, $trash));
        if (($dato = $this->cache->getData($cacheString)) !== null) {
            return $dato;
        }

        $userCompany = $this->getCompany();
        $tipo = $elemento->getType();
        switch( $tipo ){
            case 'tipo_epi':
                return $this->accesoModulo($tipo,true);
            break;
            case 'exportacion_masiva':
                return $this->accesoElemento($elemento->getCompany(),$empresa,$papelera,$bucle);
            break;
            case 'datacriterion':
                if ($datamodel = $elemento->getItem()->getDataModel()) {
                    return $this->accesoElemento($datamodel, $empresa, $papelera, $bucle);
                }
            break;
            case "modelfield":
                if( $datamodel = $elemento->getModel() ){
                    return $this->accesoElemento($datamodel, $empresa, $papelera, $bucle);
                }
            break;
            case "datafield":
                $resultado = true;
            break;
            case "dataexport": case "dataimport":
                if( $datamodel = $elemento->getDataModel() ){
                    return $this->accesoElemento($datamodel, $empresa, $papelera, $bucle);
                }
            break;
            case "datamodel":
                if( in_array($elemento->getUID(), $this->obtenerDataModels()->toIntList()->getArrayCopy() ) ){
                    $resultado = true;
                }
            break;
            case "adjunto":
                $resultado = true;
            break;
            case "citamedica": case "convocatoriamedica": case "accidente":
                return $this->accesoElemento( $elemento->obtenerEmpleado(), $empresa, $papelera, $bucle );
            break;
            case "buscador":
                $busquedas = $this->obtenerBusquedas(NULL);
                if( in_array($elemento->getUID(), elemento::getCollectionIds($busquedas)) ){
                    $resultado = true;
                } else {
                    $busquedasCompartidas = $this->obtenerBusquedasCompartidas();
                    if( in_array($elemento->getUID(), elemento::getCollectionIds($busquedasCompartidas)) ){
                        $resultado = true;
                    } else {
                        $resultado = false;
                    }
                }
            break;
            case 'contactoempresa':
                $resultado = true;
            break;
            case "tipodocumento":
                if( count($this->getAvailableOptionsForModule(19,3,1)) ){
                    $resultado = true;
                } else {
                    $resultado = false;
                }
            break;
            case "documento":

                if( $elemento instanceof documento_atributo ){
                    if( $this->accesoModulo("documento_atributo", 1) ){
                        $resultado = true;

                        $owner = $elemento->getCompany();
                        if (!$userCompany->getStartList()->contains($owner)) $resultado = false;
                    } else {
                        $resultado = false;
                    }
                }
            break;
            case "rol":
                if( count($this->getAvailableOptionsForModule("rol",21,1)) && count($this->getAvailableOptionsForModule("rol",11,1)) ) {
                    $resultado = true;
                }
                if( !isset($resultado) ){
                    $resultado = false;
                }
            break;
            case "agrupamiento":
                $agrupamientosAccesibles = $this->getCompany()->obtenerAgrupamientosVisibles();
                foreach( $agrupamientosAccesibles as $agrupamiento ){
                    if( $elemento->getUID() == $agrupamiento->getUID() ){
                        $resultado = true;
                    }
                }
                if( !isset($resultado) ){
                    $resultado = false;
                }
            break;
            case "agrupador":
                $resultado = $elemento->accesiblePara($this);
            break;
            case "empresa":
                if (true === $this->getCompany()->compareTo($elemento) || true === $this->esValidador()) {
                    $resultado = true;
                } else {
                    // Buscamos todas las empresas visibles por este usuario
                    $list = buscador::getCompaniesIntList($this, $papelera);
                    if ($papelera === null) $list = $list->merge(buscador::getCompaniesIntList($this, true));

                    if ($list->contains($elemento->getUID())) {
                        $resultado = true;
                    }
                }
            break;
            case "empleado": case "maquina":
                $table = constant("TABLE_". strtoupper($tipo));

                $companies = $elemento->getCompanies($papelera);
                $empresaUsuario = $this->getCompany();
                foreach ($companies as $company) {
                    if ($empresaUsuario->compareTo($company)) {
                        $inTrash = $elemento->inTrash($company);

                        if (is_bool($papelera)) {
                            $resultado = ($inTrash === $papelera);
                        } else {
                            $resultado = true;
                        }
                        break;
                    }

                    $list = $empresaUsuario->getStartIntList()->toComaList();
                    $SQL = "SELECT count(uid_$tipo) FROM $table WHERE uid_$tipo = {$elemento->getUID()} AND (
                            uid_$tipo IN ( SELECT v.uid_$tipo FROM {$table}_visibilidad v WHERE uid_empresa IN ($list)  )
                        OR  uid_$tipo IN ( SELECT v.uid_$tipo FROM {$table}_empresa v WHERE uid_empresa IN ($list)  )
                    ) GROUP BY uid_$tipo";

                    if ($this->accesoElemento($company) && $this->db->query($SQL,0,0) == 1) {
                        $resultado = true;
                    }
                }

                if ($this->isViewFilterByGroups()) {
                    if (false === $condicion = $this->obtenerCondicion($elemento, "uid_$tipo")) {
                        $resultado = false;
                    } else {
                        $sql = "SELECT count(uid_{$tipo})
                        FROM $table
                        WHERE uid_$tipo IN ($condicion)
                        AND uid_$tipo = {$elemento->getUID()}";

                        $assigned = (bool) $this->db->query($sql, 0, 0);

                        if (false === $assigned) {
                            $resultado = false;
                        }
                    }
                }

                if (true === $this->esValidador()) {
                    $resultado = true;
                }
            break;
            case 'carpeta':
                $elementoContainer = $elemento->getContainer();
                $tipoContainer = $elementoContainer->getType();
                switch( $tipoContainer ){
                    case 'agrupador':
                        if ( $agrupadores = $this->getCompany()->obtenerAgrupadoresPropios() )  {
                            return $resultado = $agrupadores->contains($elemento->obtenerAgrupadorContenedor());
                        } else $resultado = false;
                    break;
                    case 'empresa': case 'empleado': case 'maquina':
                        $todasEmpresas = $this->getCompany()->getStartIntList();
                        $resultado = $todasEmpresas->contains( $elementoContainer->getUID() ) || $todasEmpresas->contains( $elemento->obtenerEmpresaReferencia()->getUID() );
                    break;
                    default:
                        $resultado = false;
                    break;
                }
            break;
            case "perfil":
                $resultado = $this->accesoElemento($elemento->getCompany(), $empresa, $papelera);
            break;
            case "usuario":
                if( $this->getUID() == $elemento->getUID() && 0 ){
                    $resultado = true;
                } elseif( ( $this->esAdministrador() || $this->esSATI() ) && 0){
                    $resultado = true;
                } elseif ( $this->isViewFilterByGroups() ){
                    $resultado = false;
                    $perfiles = $elemento->obtenerPerfiles();
                    foreach( $perfiles as $perfil ){
                        if( $perfil->getCompany()->getUID() == $this->getCompany()->getUID() ){
                            $resultado = true;
                            break;
                        }
                    }
                } else {
                    $empresaCliente = $elemento->getCompany();
                    if( !($empresaCliente instanceof empresa) ){ return false; }

                    $perfiles = $elemento->obtenerPerfiles();

                    $acceso = false;
                    foreach( $perfiles as $perfil ){
                        if( $this->accesoElemento($perfil, $empresa, $papelera) ){
                            $acceso = true;
                            break;
                        }
                    }

                    $usuarios = $empresaCliente->getAllUsers();
                    $idUsuariosHermanos = elemento::getCollectionIds($usuarios);


                    // Quizas tengamos que afinar mas la busqueda de la empresa propia.. por que aunque
                    // no tenga la empresa activa, puede que la tenga (la de nuestro contexto)
                    if( $acceso && in_array($elemento->getUID(), $idUsuariosHermanos) ){
                        $resultado = true;
                    } else {
                        $resultado = false;
                    }
                }
            break;
            case "epi":
                if( $this->accesoModulo("epi") && $this->accesoElemento($elemento->getCompany()) ){
                    $resultado = true;
                } else {
                    $resultado = false;
                }
            break;
            case "certificacion":
                if( $this->accesoModulo("certificacion") && $this->accesoElemento($elemento->getCompany()) ){
                    $resultado = true;
                } else {
                    $resultado = false;
                }
            break;
            case "cliente":
                $resultado = false;
                if( $this->esStaff() ){
                    $resultado = true;
                }
            break;
            case 'invoice':
                $invoiceCompany = $elemento->getCompany();

                if ($userCompany->getStartList()->contains($invoiceCompany)) {
                    return $this->accesoModulo($tipo, false);
                } else {
                    return false;
                }
            break;
            case 'message':
                return $this->accesoModulo($tipo,true);
            case 'empresaPartner':
                return $this->accesoModulo($tipo,true);
            break;
            case 'signinRequest':
                $empresaUsuario = $this->getCompany();
                if ($empresaUsuario) {
                    $companyList = $empresaUsuario->getStartIntList()->toComaList();
                    $sql = "SELECT count(uid_signin_request) FROM ". TABLE_SIGNINREQUEST ." WHERE uid_empresa IN ($companyList) AND uid_signin_request = ". $elemento->getUID();
                    return $this->accesoModulo($tipo) && $this->db->query($sql, 0, 0);
                }
                return false;
            break;
        }

        $this->cache->set($cacheString, $resultado, 60*60);
        return $resultado;
    }

    /** NOS DA TODOS LOS USUARIOS DE LA MISMA EMPRESA QUE EL USUARIO ACTUAL EXCEPTUANDO A ESTE */
    public function obtenerHermanos(){
        $brothers = array();
        $usuarios = $this->getCompany()->obtenerUsuarios(false, false, $this);

        foreach($usuarios as $i => $usuario){
            if ($usuario->getUID() == $this->getUID()) continue;
            $brothers[] = $usuario;
        }

        return $brothers;
    }

    public function getCompany () {
        $profile = $this->obtenerPerfil();
        return $profile->getCompany();
    }


    /** NOS RETORNA UNA COLECCION DE OBJETOS PERFIL */
    public function obtenerPerfiles($eliminadas=false, $limit=false){
        $condicion = elemento::construirCondicion( $eliminadas , $limit );

        $coleccionObjetos = new ArrayObjectList();
        $datos = $this->obtenerRelacionados( TABLE_PERFIL, "uid_usuario", "uid_perfil", $condicion );
        foreach( $datos as $lineaRelacionPerfil ){
            $coleccionObjetos[] = new perfil( $lineaRelacionPerfil["uid_perfil"] );
        }

        return $coleccionObjetos;
    }

    public function getProfileCount (empresa $company = NULL) {

        $SQL = "SELECT count(uid_perfil) FROM ". TABLE_PERFIL . " WHERE uid_usuario = {$this->getUID()} AND papelera = 0";
        if ($company instanceof empresa) {
            $SQL .= " AND uid_empresa = {$company->getUID()}";
        }


        $num = $this->db->query($SQL, 0, 0);

        return $num;
    }

    /** ESTADO DE LA CONEXION DEL USUARIO A LA APLICACION EN TEXTO */
    public function verEstadoConexion ($numeric = false) {
        $profile    = $this->obtenerPerfil();
        $trash      = $profile->obtenerDato('papelera');

        if ($trash) {
            if ($numeric) {
                return self::USER_LOCKED;
            }

            return self::status2String(self::USER_LOCKED);
        }


        $connection = $this->obtenerDato('conexion');

        if ($numeric) {
            return $connection;
        }

        return self::status2String($connection);
    }


    public function establecerEstadoConexion($estado=0, $index = false){
        $ip = log::getIPAddress();
        $sql = "UPDATE {$this->tabla} SET ip = '".$ip."', conexion = $estado";

        if( $estado == 1 ){
            $currentAddress = db::scape($_SERVER["REQUEST_URI"]);
            $sql .= ", fecha_accion = '". time() ."', last_page = '$currentAddress'";


            $curCompany = $this->getCompany();
            if( $curCompany instanceof empresa ){
                $updateEmpresa = "UPDATE ". TABLE_EMPRESA . " SET accion = NOW() WHERE uid_empresa = ". $curCompany->getUID();
                $this->db->query($updateEmpresa);
            }
        }

        $sql .= " WHERE uid_usuario = ".$this->getUID();
        return $this->db->query( $sql );
    }

    public static function httpauth(){
        if( ($usuario = usuario::login($_SERVER['PHP_AUTH_USER'], @$_SERVER['PHP_AUTH_PW'])) && $usuario->esAdministrador() ){
            return $usuario;
        } else {
            header('WWW-Authenticate: Basic realm="Dokify Password"');
            header('HTTP/1.0 401 Unauthorized');
            die('Por favor haz login');
        }
        return false;
    }

    /**
     * return a user if data is valid
     * @param  [string|int] $username   the username or the uid of the user
     * @param  boolean      $password   the user password or false
     * @return [usuario|false] the user if data is valid or false if not
     */
    public static function login ($username, $password = false)
    {
        $dba = db::singleton();

        $userTable = TABLE_USUARIO;
        $profileTable = TABLE_PERFIL;

        $sql = "SELECT u.uid_usuario
        FROM {$userTable} u
        INNER JOIN {$profileTable} p ON u.perfil = p.uid_perfil
        WHERE 1";

        $username = utf8_decode(db::scape($username));

        if ($password === false) {
            if (StringParser::isEmail($username)) {
                $sql .= " AND u.email = '{$username}'";
            } else {
                $sql .= " AND u.usuario = '{$username}'";
            }
        } else {
            $sql .= " AND p.papelera = 0";
            $password  = db::scape($password);

            if (StringParser::isEmail($username)) {
                $sql .= " AND u.email = '{$username}' AND u.pass = MD5('{$password}')";
            } else {
                $sql .= " AND u.usuario = '{$username}' AND u.pass = MD5('{$password}')";
            }
        }

        $uid = $dba->query($sql, 0, 0);
        if (is_numeric($uid)) {
            $usuario = new usuario($uid);

            if ($password) {
                $usuario->refreshCookieToken($password);
            }

            // apaño para tener este dato accesible
            if (isset($_SESSION)) {
                $_SESSION["USUARIO_LAST_ACCESS"] = $usuario->obtenerDato("fecha_ultimo_acceso");
            }

            $sql = "UPDATE ". TABLE_USUARIO ."
            SET ip = '". log::getIPAddress() ."',
            conexion = 1,
            fecha_ultimo_acceso = ".time()."
            WHERE uid_usuario = $uid;";

            if ($dba->query($sql)) {
                if ($password) {
                    $usuario->password = $password;
                }

                $company = $usuario->getCompany();
                $sql = "UPDATE ". TABLE_EMPRESA ." SET is_idle = 0 WHERE uid_empresa = {$company->getUID()}";
                $dba->query($sql);

                return $usuario;
            }
        }

        return false;
    }

    /***
        COMPRUEBA EL ACCESO AL MODULO DE CONFIGURACION
    */
    public function accesoConfiguracion(){
        return $this->getAvailableOptionsForModule(20,21);
    }

    /***
        NOS DEVOLVERÁ UN ARRAY DE LA OPCION DE ACCESO CON SUS PROPIEDADES
    */
    public function accesoModulo($idModulo, $config=null){
        return $this->accesoAccionConcreta($idModulo, 21, $config);
    }

    /***
        NOS DEVOLVERÁ UN ARRAY DE LA OPCION DE ACCESO CON SUS PROPIEDADES
    */
    public function accesoModuloMenu($idModulo, $config=null){
        if( $this->accesoAccionConcreta($idModulo, 21, $config) ){
            return $this->accesoAccionConcreta($idModulo, 63, $config);
        }
    }


    /***
        A PARTIR DE ESTA FUNCION SE CREARAN ALIAS PARA COMPROBAR ACCESO A FUNCIONALIDADES
        QUE SE UTILICEN MUCHO, PARA FACILITAR EL DESARROLLO
    */
    public function accesoAccionConcreta($idModulo, $accion, $config=null, $ref=null){
        if( !$idModulo instanceof elemento ){
            if( !is_numeric($idModulo) ) $idModulo = util::getModuleId($idModulo);
            if( !$idModulo ) return false;
        }

        $datosAccion = $this->getAvailableOptionsForModule($idModulo, $accion/*UID DE LA ACCION DE ACCESO*/, $config, $ref, false);

        if( !is_array($datosAccion) || !count($datosAccion) ){ return false; }

        $datosAccion = reset($datosAccion);
        //$datosAccion["uid_modulo"] = $idModulo;

        return $datosAccion;
    }



    public function accesoModificarElemento(Ielemento $elemento, $config=0){
        if( $this->esStaff() ){
            return true;
        }

        //if( !$this->accesoElemento($elemento) ){ return false; }
        $idModulo = $elemento->getModuleId();


        $accionModificar = 4;

        //Quizas algunos modulos son diferentes
        switch( $idModulo ){
            case 34: //eventos
                $empresa = $elemento->getCompany();
                if( $empresa->getUID() == $this->getCompany()->getUID() ){
                    return true;
                } else {
                    return false;
                }
            break;
            case 75: // Módulo modelfield
                $accionModificar = 13; // Accion atributos
            break;
        }

        $datosAccion = $this->getAvailableOptionsForModule($idModulo, $accionModificar/*UID DE LA ACCION DE MODIFICAR*/, $config);

        if( !$datosAccion || !is_array($datosAccion) || !count($datosAccion) ){ return false; }

        if( $elemento->getType() == "empresa" && $elemento->getUID() == $this->getCompany()->getUID() ){
            return false;
        }
        return true;
    }

    public function accesoEliminarElemento($elemento, $config=0){
        if( $this->esStaff() ){
            return true;
        }

        //if( !$this->accesoElemento($elemento) ){ return false; }
        $idModulo = $elemento->getModuleId();


        //Quizas algunos modulos son diferentes
        switch( $idModulo ){
            case 34: //eventos
                $empresa = $elemento->getCompany();
                if( $empresa->getUID() == $this->getCompany()->getUID() ){
                    return true;
                } else {
                    return false;
                }
            break;
        }

        $datosAccion = $this->getAvailableOptionsForModule($idModulo, 14/*UID DE LA ACCION DE ELIMINAR*/, $config);
        if( !$datosAccion || !is_array($datosAccion) || !count($datosAccion) ){ return false; }

        if( $elemento->getType() == "empresa" && $elemento->compareTo($this->getCompany()) ){
            return false;
        }

        return true;
    }

    /***
        NOS RETORNARÁ UN ARRAY DE OPCIONES  PARA EL MODULO INDICADO
    */
    public function getAvailableOptionsForModule( $idModulo, $idAccion = false, $config = null, $referencia = null, $parent = NULL, $type = NULL ){
        $uidelemento = null;
        if( !is_numeric($idModulo) ){
            if ($idModulo instanceof documento && $idModulo->elementoFiltro) {
                $parent = ($parent === false) ? false : $idModulo;
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

        $sql = "SELECT uid_accion, alias, concat('". RESOURCES_DOMAIN ."', icono) as icono, href, prioridad, class
        FROM ". TABLE_ACCIONES ." INNER JOIN ". TABLE_MODULOS ."_accion USING( uid_accion )
        INNER JOIN ". TABLE_PERFIL ."_accion USING( uid_modulo_accion )
        WHERE uid_modulo = ". $idModulo ." AND activo = 1 AND uid_perfil = ". $this->perfilActivo()->getUID();


        if( $this->esStaff() ){
            $sql = "SELECT uid_accion, alias, concat('". RESOURCES_DOMAIN ."', icono) as icono, href, prioridad, class
            FROM ". TABLE_ACCIONES ." INNER JOIN ". TABLE_MODULOS ."_accion USING( uid_accion )
            WHERE activo = 1 AND uid_modulo = ". $idModulo ."  ";
            // Si el usuario es agente tiene capacidad total de visualización, pero no de gestion
            if ($this->isAgent()) {
                $blacklist = $this->getAgentActionsBlackList();
                $sql .= " AND uid_accion NOT IN (". implode(", ", $blacklist) .")";
            }
        }

        if( $config == 0 || $config == 1 ) {
            $config = (int) db::scape($config);
            $sql .= " AND config = {$config}";
        }
        else { $sql .= " AND config = 0"; }

        if( $idAccion ){
            if( is_numeric($idAccion) ){
                $sql .= " AND uid_accion = $idAccion";
            } else {
                $sql .= " AND uid_accion = ( SELECT uid_accion FROM ". TABLE_ACCIONES ." WHERE alias = '". db::scape($idAccion) ."' ) ";
            }
        }

        if( $referencia ){
            $sql .= " AND referencia = '$referencia' ";
        } else {
            $sql .= " AND referencia = '' ";
        }

        // Filter options
        $moduleName = isset($moduleName) ? $moduleName : util::getModuleName($idModulo);
        $func = $moduleName.'::optionsFilter';

        if (method_exists($moduleName, "optionsFilter")) {
            $filter = call_user_func($func, $uidelemento, $idModulo, $this, true, $config, $type, $parent );
            if( $filter !== false ){ $sql .= " $filter"; }
        }
        return $this->db->query( $sql, true );
    }

    public function getOptionsMultipleFor($modulo, $config=0, Ielemento $parent = NULL){
        return  config::obtenerOpciones(null, $modulo, $this, true, $config, 2, true, false, $parent);
    }

    public function getOptionsFastFor($modulo, $config=0, Ielemento $parent = NULL){
        return  config::obtenerOpciones(null, $modulo, $this, true, $config, 3, true, false, $parent);
    }

    /***
       * DEVUELVE UN ARRAY LISTO PARA CREAR EL MENU
       *
       *
       *
       **/
    public function obtenerElementosMenu($mobile = false) {
        $isTablet = get_client_version() === 'tablet';
        $modulosDisponibles = array();

        // home, empresa, usuario, empleado, maquina, agrupamiento
        $iconOnly = array(13, 100, 105);
        $modulos = array(13, 100, 105, 2, 1, 8, 14, 12);


        if ($mobile) {
            $modulos = array(13, 105, 1, 8, 14);
        }

        if ($isTablet) $iconOnly = $modulos;


        foreach($modulos as $modulo){
            $datosAccion = $this->accesoModuloMenu($modulo);
            if (isset($datosAccion["href"])) {
                $modulosDisponibles[] = array(
                    "name" => strtolower(util::getModuleName($modulo)),
                    "icononly" => in_array($modulo, $iconOnly),
                    "href" => $datosAccion["href"]
                );
            }
        }

        if ($mobile) {
            return $modulosDisponibles;
        }


        if( $empresaCliente = $this->getCompany() ){
            $agrupamientos = $empresaCliente->obtenerAgrupamientosVisibles(array("menu"));
            if( count($agrupamientos) && $this->accesoModulo("agrupador") ){
                $last = array_pop($modulosDisponibles);
                if ($agrupamientos && count($agrupamientos)) {
                    foreach( $agrupamientos as $agrupamiento ){
                        $module = array(
                            "name" => $agrupamiento->getUserVisibleName(),
                            "href" => "#agrupamiento/listado.php?poid=".$agrupamiento->getUID(),
                            "imgpath" => $agrupamiento->getIcon(false)
                        );

                        if ($isTablet) $module['icononly'] = true;

                        $modulosDisponibles[] = $module;
                    }
                }
                $modulosDisponibles[] = $last;
            }
        }


        return $modulosDisponibles;
    }

    public static function crearNuevo($datos = null, $appVersion = 1)
    {
        // --- AQUI TENEMOS LOS CAMPOS DE ESTE TIPO DE ELEMENTOS
        $fields     = usuario::publicFields(self::PUBLIFIELDS_MODE_NEW);
        $request    = $_GET;
        $database   = db::singleton();

        if ($datos && is_array($datos)) {
            $request = array_merge_recursive($request, $datos);

            foreach ($datos as $campo => $valor) {
                $request[$campo] = $valor;
            }
        }

        // --- RECORREMOS LOS CAMPOS DE NUESTRA TABLA
        foreach ($fields as $field => $val) {
            if (isset($request[$field])) {
                $value = db::scape(trim($request[$field]));

                // --- SI NO ESTA PERMITIDO QUE ESTE EN BLANCO LO AVISAMOS
                if (isset($val["blank"]) && !$val["blank"] && !strlen($value)) {
                    return "campo_".$field."_no_blanco";
                }

                if (isset($val["match"]) && $val["match"]) {
                    if (!preg_match("/".$val["match"]."/", $value)) {
                        return "campo_".$field."_no_valido";
                    }
                }
            }
        }

        if (isset($request["nombre"])) {
            if (trim($request["nombre"]) == "") {
                return "error_nombre_vacio";
            }
        }

        if (isset($request["apellidos"])) {
            if (trim($request["apellidos"]) == "") {
                return "error_apellido_vacio";
            }
        }

        if (isset($request["telefono"])) {
            if (trim($request["telefono"]) == "") {
                return "error_telefono_vacio";
            }
        }

        if (isset($request["email"])) {
            if (trim($request["email"]) == "") {
                return "error_email_vacio";
            }
        }

        if (!isset($request["usuario"])) {
            return "error_usuario_vacio";
        }

        if (!isset($request["pass"]) || !strlen($request["pass"])) {
            return "error_pass_vacio";
        }

        $usuario = trim(db::scape(utf8_decode($request["usuario"])));

        // --- Si el usurio ya existe devolvemos el error mysql de forma manual
        $sql = "SELECT uid_usuario FROM ". TABLE_USUARIO . " WHERE usuario = '{$usuario}'";
        if ($database->query($sql, 0, 0)) {
            return _('Username already exists');
        }

        $password   = trim(db::scape($request["pass"]));
        $email      = trim(db::scape($request["email"]));

        if (self::isEmailInUse($email, null)) {
            return _("The email is already in use");
        }

        // -- id es el vat number
        $id = isset($request["id"]) ? trim(db::scape($request["id"])) : false;
        $nombre = db::scape(utf8_decode($request["nombre"]));
        $telefono = db::scape(utf8_decode($request["telefono"]));
        $apellidos = db::scape(utf8_decode($request["apellidos"]));
        $direccion = db::scape(utf8_decode(@$request["direccion"]));

        $locale = 'es';
        $supportedLocales = array_keys(getLocaleMap());

        if (true === isset($request["locale"]) && true === in_array($request["locale"], $supportedLocales)) {
            $locale = db::scape($request["locale"]);
        }

        $latlng = "";
        if ($direccion) {
            $coords = \util::getCoordsFromAddress($direccion);
            if ($coords && isset($coords->latitude) && isset($coords->longitude)) {
                $latlng = ($coords->latitude.",".$coords->longitude);
            }
        }

        $password2 = isset($request["pass2"]) ? db::scape($request["pass2"]) : false;

        if ($password !== $password2) {
            return "error_pass_no_coincide";
        }


        $users          = TABLE_USUARIO;
        $time           = time();
        $apacheRealm    = self::APACHE2_REALM;

        $sql = "
        INSERT INTO {$users} (
            usuario, id, fecha_alta, pass, pass_sha1,
            pass_apache2, actualizar_pass, email,
            nombre, apellidos, telefono, app_version, direccion, latlng, location_timestamp, locale)
        VALUES (
            '$usuario', '$id', {$time}, MD5('$password'),  SHA1('$password'),
            MD5('$usuario:{$apacheRealm}:$password'), 1, '$email',
            '$nombre', '$apellidos', '$telefono', {$appVersion}, '$direccion', '$latlng', NOW(), '$locale');
        ";

        $resultset = $database->query($sql);

        if ($resultset) {
            return new self($database->getLastId());
        } else {
            return $database->lastErrorString();
        }
    }

    /*
     * Overwrite parent's update to add latlng position data
     *
     */
    public function update($data = false, $fieldsMode = false, Iusuario $usuario = null)
    {
        if (isset($data["direccion"])) {
            $coords = \util::getCoordsFromAddress($data["direccion"]);
            if ($coords && isset($coords->latitude) && isset($coords->longitude)) {
                $data["latlng"]             = ($coords->latitude.",".$coords->longitude);
                $data['location_timestamp'] = date("Y-m-d H:i:s");
            }
        }
        return parent::update($data, $fieldsMode, $usuario);
    }

    /***
        BLOQUEA UN ID-PERFIL DADO. COMO PARAMETRO SE PUEDE PASAR UNA EMPRESA Y SE BUSCARA EL ID-PERFIL ASOCIADO
    */
    function bloquearPerfil( $param = false ){
        if( $param instanceof empresa ){ $param = $this->perfilEmpresa($param)->getUID(); }
        return $this->actualizarEstadoPerfil($param,1);
    }
    /***
        DESBLOQUEA ID-PERFIL DADO. COMO PARAMETRO SE PUEDE PASAR UNA EMPRESA Y SE BUSCARA EL ID-PERFIL ASOCIADO
    */
    function desbloquearPerfil( $param = false ){
        if( $param instanceof empresa ){
            $perfil = $this->perfilEmpresa($param);
            if( $perfil instanceof perfil ){
                $param = $perfil->getUID();
            } else {
                return false;
            }
        }
        return $this->actualizarEstadoPerfil($param,0);
    }

    protected function actualizarEstadoPerfil($idPerfilAlternativo = false, $estado = 1){
        $idPerfil = ( is_numeric($idPerfilAlternativo) ) ? $idPerfilAlternativo : $this->perfilActivo()->getUID();
        $sql = "UPDATE ". TABLE_PERFIL ." SET papelera = $estado WHERE uid_perfil = $idPerfil;";
        if( $this->db->query($sql) ){
            return true;
        } else {
            return $this->db->lastErrorString();
        }
    }


    function crearPerfil( usuario $usuarioActivo, $empresa = false, $asignar = false){

        if( !$empresa ){
            $idempresa = $usuarioActivo->getCompany()->getUID();
        } else {
            $idempresa = $empresa->getUID();
        }
        $uidusuario = $this->getUID();

        $alias = utf8_decode(db::scape($empresa->getUserVisibleName()));
        $uidCorporation = $empresa->esCorporacion() ? $idempresa : 'NULL';

        //CREAMOS PRIMERO EL PERFIL
        $sql = "INSERT INTO ". TABLE_PERFIL ." ( uid_empresa, uid_corporation, uid_usuario, alias )
        VALUES (
            $idempresa , $uidCorporation, $uidusuario, '$alias')";

        if( $this->db->query( $sql ) ){
            $newuidperfil = $this->db->getLastId();
            if( $asignar ){
                if( !$this->cambiarPerfil( $newuidperfil ) ){
                    return $this->db->lastErrorString();
                }
            }
            return $newuidperfil;
        } else {
            return $this->db->lastErrorString();
        }
    }

    public function obtenerOpcionesDisponibles($UIDOpciones = false){
        return $this->perfilActivo()->obtenerOpcionesDisponibles($UIDOpciones);
    }

    public function comprobarAccesoOpcion($UIDOpciones, $extra=false){
        if( $this->esStaff() ) return true;
        return $this->perfilActivo()->comprobarAccesoOpcion($UIDOpciones, $extra);
    }

    /***
        Obtener las opciones accesibles por el usuario en formato de grupos para mostrarlas mas fácilmente
            @param $extra bool, si queremos que se filtro por datos extra
    **/
    public function obtenerOpcionesDisponiblesPorGrupos($extra=false){
        return $this->perfilActivo()->obtenerOpcionesDisponiblesPorGrupos($extra);
    }


    public function quitarPermisoDePerfil( $idPerfil, $idModuloAccion ){
        $sql = "DELETE FROM ". DB_CORE .".perfil_accion WHERE uid_modulo_accion = $idModuloAccion AND uid_perfil = $idPerfil";
        if( $this->db->query( $sql ) ){
            return true;
        } else {
            return $this->db->lastErrorString();
        }
    }

    public function asignarPermisoAPerfil( $idPerfil, $idModuloAccion ){
        $sql = "INSERT INTO ". DB_CORE .".perfil_accion ( uid_modulo_accion, uid_perfil ) VALUES ($idModuloAccion, $idPerfil)";
        if( $this->db->query( $sql ) ){
            return true;
        } else {
            return $this->db->lastErrorString();
        }
    }

    /** NOS DA EL PERFIL CORRESPONDIENTE PASANDO EL OBJETO EMPRESA COMO PARAMETRO */
    public function perfilEmpresa($empresa, $buscarEmpresasInferiores = true)
    {
        if ($empresa instanceof empresa) {
            $perfilesUsuario = $this->obtenerPerfiles(null); // sin filtrar
            foreach ($perfilesUsuario as $perfil) {
                if ($empresa->getUID() === $perfil->getCompany()->getUID()) {
                    return $perfil;
                }
            }

            if ($empresa->esCorporacion() && $buscarEmpresasInferiores) {
                $empresasInferiores = $empresa->obtenerEmpresasInferiores();

                do {
                    $perfil = $this->perfilEmpresa($empresasInferiores->getFirst());
                    $empresasInferiores = $empresasInferiores->shift();
                } while (!$perfil && count($empresasInferiores));

                if ($perfil) {
                    return $perfil;
                }
            }
        }
        return false;
    }

    public function obtenerAgrupamientosAsignados(
        $usuario = false,
        $includeRelations = false,
        $categories = [],
        $excludeCategories = false,
        $forceCurrentClient = false
    ) {
        $agrupamientos = array();
        $agrupadores = $this->obtenerAgrupadores(null, $this, false, false, $forceCurrentClient);

        $agrupamientos = $agrupadores->foreachCall('obtenerAgrupamientoPrimario')->unique();

        return  new ArrayAgrupamientoList($agrupamientos);
    }

    public function obtenerAgrupamientosWithFilter($usuario = false, $forceCurrentClient=false, $filter = 'config_filter'){
        $agrupamientos = $this->obtenerAgrupamientosAsignados($usuario, $forceCurrentClient);
        $agrupamientosWithFilter = array();

        foreach ($agrupamientos as $agrupamiento) {
            if ($agrupamiento->obtenerDato($filter)) $agrupamientosWithFilter[] = $agrupamiento;
        }

        return  new ArrayAgrupamientoList($agrupamientosWithFilter);
    }

    public function obtenerAgrupadores(
        $recursividad = null,
        $usuario = false,
        $agrupamientos = false,
        $condicion = false,
        $forceCurrentClient = false,
        $categories = null,
        $sqlOptions = [],
        $applyGroupFilter = false
    ) {
        return $this->perfilActivo()->obtenerAgrupadores($recursividad, $usuario, $agrupamientos, $condicion, $forceCurrentClient);
    }

    public function getAssignments (Iusuario $user, $opts = []) {
        return $this->perfilActivo()->getAssignments($user, $opts);
    }

    public function asignarAgrupadores(
        $arrayIDS,
        $usuario = false,
        $rebote = 0,
        $replicar = false,
        $doBounce = true
    ) {
        return $this->perfilActivo()->asignarAgrupadores($arrayIDS, $usuario, $rebote, $replicar);
    }

    public function quitarAgrupadores ($arrayIDS, Iusuario $usuario = NULL, $asignados=false ){
        return $this->perfilActivo()->quitarAgrupadores($arrayIDS, $usuario, $asignados );
    }

    public function getDuracionValue (agrupador $group) {
        return $this->perfilActivo()->getDuracionValue($group);
    }

    public function setDuracionValue (agrupador $group, $duracion, $startDate) {
        return $this->perfilActivo()->setDuracionValue($group, $duracion, $startDate);
    }

    public function getStartDate(agrupador $group) {
        return $this->perfilActivo()->getStartDate($group);
    }

    /***
        GENERA LA CONDICION EN FORMATO SQL PARA SABER SI CORRESPONDEN LOS AGRUPADORES
            @param el objeto para el que se usara la query (no importa el uid)
            @param el campo para comparar... (uid_empresa_inferior para subcontratas)
            @param el campo que se extra de la tabal por defecto "uid_elemento" pero puede ser "uid_agrupador"
            @param si es filtro para elementos o para documentos
    **/
    public function obtenerCondicion($objeto, $campoComparacion, $campoReferencia="uid_elemento", $elemento = true){


        // Por defecto el mismo tipo
        $obTipo = str_replace("uid_","", $campoComparacion);
        // Este caso sirve cuando hablamos de documentos
        if( $elemento !== true ){ $obTipo = $elemento; }
        // Si sigue teniendo _ debemos extrar la primera porcion
        if (strpos($obTipo,"_") !== false) {
          $obtipoFilter = explode("_",$obTipo);
          $obTipo = reset($obtipoFilter);
        }
        // si es en tablas compartidas el tipo del objeto
        if( $obTipo == "elemento" ){ $obTipo = $objeto->getType(); }
        // si se pasa la tabla de referencia completa
        if( $obTipo == "agd" ){ $obTipo = explode(".",$campoComparacion); $obTipo = $obTipo[1];  }
        //por si ponemos un alias a la variable
        if( strpos($obTipo,".") !== false ) { $obTipo = explode(".",$obTipo); $obTipo = $obTipo[1]; }

        $uidTipo = util::getModuleId($obTipo);

        $profile = $this->obtenerPerfil();

        $cacheString = "condicion-". $this->getUID() ."-". $profile->getUID() . "-". $obTipo ."-". $uidTipo."-".$campoComparacion;
        $estado = $this->cache->getData($cacheString);
        if( $estado !== null ){
            return $estado;
        }

        $agrupadoresUsuarioActivo = $this->obtenerAgrupadores();

        $ids = $agrupadoresUsuarioActivo->toIntList()->getArrayCopy();
        if( count($ids) ){
            $perfilActivo = $this->perfilActivo();

            // Si el usuario debe estar filtrado por comparacion exacta...
            if ($modo = $perfilActivo->obtenerDato("limiteagrupador_modo")) {
                $moduloPerfil = util::getModuleId("perfil");
                $moduloUsuario = util::getModuleId("usuario");

                if( $elemento === true ){
                    $subsql = "SELECT aa.uid_agrupador FROM ". TABLE_AGRUPADOR ." aa
                        INNER JOIN ". TABLE_AGRUPAMIENTO ."_modulo am USING( uid_agrupamiento )
                        INNER JOIN ". TABLE_AGRUPAMIENTO ." a USING( uid_agrupamiento )
                        WHERE a.config_filter = 1
                        AND am.uid_modulo = $moduloUsuario
                        AND aa.uid_agrupador = elm.uid_agrupador
                        AND papelera = 0
                        AND aa.uid_empresa = ". $this->getCompany()->getUID();
                } else {
                    $subsql = "SELECT aa.uid_agrupador FROM ". TABLE_AGRUPADOR ." aa
                        INNER JOIN ". TABLE_AGRUPAMIENTO ."_modulo am USING( uid_agrupamiento )
                        INNER JOIN ". TABLE_AGRUPAMIENTO ." ta USING( uid_agrupamiento )
                        WHERE am.uid_modulo = $moduloUsuario AND aa.uid_agrupador = elm.uid_agrupador
                        AND papelera = 0
                        AND aa.uid_empresa = ". $this->getCompany()->getUID();
                }


                switch ($modo) {
                    case usuario::FILTER_VIEW_GROUP:

                        $sql = "
                            SELECT aa.uid_agrupamiento
                            FROM ". TABLE_AGRUPADOR ."_elemento ae
                            INNER JOIN ". TABLE_AGRUPADOR ." aa ON ae.uid_agrupador = aa.uid_agrupador
                            INNER JOIN ".TABLE_AGRUPAMIENTO." a USING(uid_agrupamiento)
                            WHERE a.config_filter = 1
                            AND ae.uid_modulo = ".$moduloPerfil."
                            AND ae.uid_elemento = ".$this->perfilActivo()->getUID()."
                            AND papelera = 0
                            GROUP BY aa.uid_agrupamiento
                        ";
                        $agrupamientos = $this->db->query($sql,"*",0,"agrupamiento");

                        if (count($agrupamientos)) {
                            $filters = array();
                            foreach($agrupamientos as $agrupamiento){
                                $agrupadores = $agrupamiento->obtenerAgrupadores($this);
                                $list = count($agrupadores) ? $agrupadores->toComaList() : '0';
                                $filters[] = " i.uid_{$obTipo} IN (
                                    SELECT sub.uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento sub
                                    WHERE sub.uid_modulo = $uidTipo
                                    AND sub.uid_agrupador IN ({$list})
                                )";
                            }

                            $table = constant("TABLE_". strtoupper($obTipo));
                            $condicion = "
                                SELECT i.uid_{$obTipo}
                                FROM $table i
                                WHERE 1
                                AND ". implode(" AND ", $filters ) ."
                            ";

                            $array = $this->db->query($condicion, "*", 0);
                            $list = is_array($array) && count($array) ? implode(",", $array) : "0";
                            $condicion = $list;
                            /*
                            $condicion = "
                                SELECT elm.uid_elemento
                                FROM ".TABLE_AGRUPADOR."_elemento elm
                                WHERE 1
                                AND elm.uid_elemento = ".$campoComparacion."
                                AND elm.uid_modulo = ".$uidTipo."
                                AND ". implode(" AND ", $filters ) ."
                            ";*/
                        } else { $condicion = " 0 "; }

                    break;

                    case usuario::FILTER_VIEW_USER:

                        $sql = "
                                SELECT uid_agrupador
                                FROM ". TABLE_AGRUPADOR ."_elemento
                                WHERE uid_modulo = $moduloPerfil
                                AND uid_elemento = ". $this->perfilActivo()->getUID() ."
                                AND uid_agrupador IN (
                                    SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ."
                                    INNER JOIN ". TABLE_AGRUPAMIENTO ." USING(uid_agrupamiento)
                                    WHERE config_filter = 1
                                )
                        ";
                        $agrupadores = $this->db->query($sql,"*",0,"agrupador");
                        if( count($agrupadores) ){
                            $filters = array();
                            foreach( $agrupadores as $agrupador ){
                                $filters[] = " elm.uid_elemento IN (
                                    SELECT sub.uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento sub
                                    WHERE sub.uid_elemento = elm.uid_elemento AND sub.uid_modulo = $uidTipo
                                    AND sub.uid_agrupador = ". $agrupador->getUID() ."
                                )";
                            }

                            $condicion = "
                                SELECT elm.uid_elemento
                                FROM ". TABLE_AGRUPADOR ."_elemento elm
                                WHERE elm.uid_elemento = $campoComparacion
                                AND ". implode(" AND ", $filters ) ."
                                AND elm.uid_modulo = $uidTipo
                            ";

                        } else { $condicion = " 0 "; }

                    break;
                    default: // usuario::FILTER_VIEW_EXACTLY

                        $condicion = "
                            SELECT elm.uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento elm
                            WHERE elm.uid_elemento = $campoComparacion
                            AND elm.uid_modulo = $uidTipo
                            AND elm.uid_agrupador IN ($subsql)
                            GROUP BY elm.uid_elemento
                            HAVING GROUP_CONCAT(elm.uid_agrupador ORDER BY uid_agrupador DESC) = (
                                SELECT GROUP_CONCAT(elm.uid_agrupador ORDER BY uid_agrupador DESC) FROM ". TABLE_AGRUPADOR ."_elemento elm
                                WHERE elm.uid_modulo = $moduloPerfil
                                AND elm.uid_elemento = ". $this->perfilActivo()->getUID() ."
                                AND elm.uid_agrupador IN ($subsql)
                                GROUP BY elm.uid_elemento
                            )
                        ";

                    break;
                }

            // Si el filtro es como minimo lo del usuario..
            } else {

                // sql que nos dice los agrupadores que tienen filtro de visibilidad
                $filterVisibility = "SELECT aa.uid_agrupador FROM ". TABLE_AGRUPADOR ." aa INNER JOIN ". TABLE_AGRUPAMIENTO ." a USING ( uid_agrupamiento )
                WHERE aa.uid_agrupador = uid_agrupador AND papelera = 0 AND config_filter = 1 ";

                $condicion = "
                    SELECT $campoReferencia FROM ". TABLE_AGRUPADOR ."_elemento ae
                    WHERE ae.uid_elemento = $campoComparacion
                    AND ae.uid_modulo = $uidTipo
                    AND (
                        (
                            ae.uid_agrupador IN (". implode(",", $ids) .")
                            ". ($elemento ? " AND ae.uid_agrupador IN ($filterVisibility)" : "") . "
                        )

                        OR
                        (
                            ae.uid_agrupador_elemento IN (
                                SELECT aea.uid_agrupador_elemento FROM ". TABLE_AGRUPADOR ."_elemento
                                INNER JOIN ". TABLE_AGRUPADOR ."_elemento_agrupador aea
                                USING (uid_agrupador_elemento)
                                WHERE uid_modulo = $uidTipo AND uid_elemento = $campoComparacion
                                ". ($elemento ? " AND aea.uid_agrupador IN ($filterVisibility)" : "") . "
                                AND aea.uid_agrupador IN (". implode(",", $ids) .")
                            )
                        )
                    )
                ";


                $condicion .= " GROUP BY uid_elemento";
            }

        } else {
            $condicion = false;
        }

        $this->cache->addData( $cacheString, $condicion );
        return $condicion;
    }

    public function getCondition($objeto, $campoComparacion, $campoReferencia = "uid_elemento", $elemento = true)
    {
        return $this->obtenerCondicion($objeto, $campoComparacion, $campoReferencia = "uid_elemento", $elemento = true);
    }

    /**
        DADO UN ELEMENTO REAL COMO PARAMETRO NOS DEVUELVE UN STRING
        QUE SE APLICA A LAS TABLA da COMO agd_docs.documento_atributo PARA FILTRAR LO QUE EL USUARIO ACTUAL DEBE VER
    **/
    public function obtenerCondicionDocumentos(){
        return $this->perfilActivo()->obtenerCondicionDocumentos();
    }

    public function obtenerCondicionDocumentosView($module){
        return $this->perfilActivo()->obtenerCondicionDocumentosView($module);
    }

    /** DEVUELVE TRUE O FALSE SI LA VISTA ESTA LIMITADA POR AGRUPADORES **/
    public function isViewFilterByGroups(){
        return (bool) $this->configValue("limiteagrupador");
    }


    public function isViewFilterByLabel(){
        return (bool) $this->configValue("limiteetiqueta");
    }

    /** NOS DA LA EMPRESA DE UN PERFIL */
    public function empresaPerfil( $idPerfil ){
        $perfil = new perfil( $idPerfil );
        return $perfil->getCompany();
    }

    /** NOS DA EL PERFIL ACTIVO*/
    public function obtenerPerfil(){
        return $this->perfilActivo();
    }

    /** NOS DA EL PERFIL ACTIVO*/
    public function perfilActivo(){
        $idPerfilActivo = $this->idPerfilActivo();
        if( is_numeric($idPerfilActivo) ){
            $perfil = new perfil($idPerfilActivo);
            return $perfil;
        } else {
            if( isset($_SERVER["PWD"]) && $_SERVER["PWD"] ){ // LINEA DE COMANDOS
                die("Error: No se encuentra el perfil activo para el usuario " . $this->uid ."\n");
            } else {
                log_error("Error: No se encuentra el perfil activo para el usuario " . $this->uid);
                $loc = '/salir.php?loc=sinperfil';
                if( isset($_GET["type"]) ){
                    if( $_GET["type"] == "ajax" ){ die( json_encode(array("action"=>array("go"=>$loc))) ); }
                    if( $_GET["type"] == "modal" ){ die("<script>location.href='$loc';</script>"); }
                } else {
                    header("Location: $loc"); exit;
                }
            }
        }
    }

    public function activeProfile()
    {
        return $this->perfilActivo();
    }

    /**
      * RETORNA LOS ELEMENTOS QUE "TIPICAMENTE" MOSTRAMOS DE ESTE ELEMENTO PARA VER EN MODO INLINE
      * @param = $usuarioActivo, debe ser el objeto usuario logeado actualmente, para filtrar si es necesario
      */
    public function getInlineArray($usuarioActivo=false, $mode=false, $data=false ){
        $comefrom = ( isset($data["comefrom"]) ) ? $data["comefrom"] : false;
        $inlineArray = array();

        $inlineArray[] =  array(
            "img" => RESOURCES_DOMAIN . "/img/famfam/group.png",
            array(
                "nombre" => $this->perfilActivo()->getUserVisibleName("utf8_decode"),
                "tagName" => "span"
            )
        );


        $estado = $this->verEstadoConexion(true);
        $color = usuario::getColorFromStatus($estado);
        $estado =self::status2string($estado);

        $inlineArray[] =  array(
            "img" => RESOURCES_DOMAIN . "/img/famfam/tag_$color.png",
            array(
                "nombre" => $estado,
                "tagName" => "span"
            )
        );


        if( $usuarioActivo instanceof usuario && $comefrom != "empresa" ){
            $perfiles = $this->obtenerPerfiles();
            $empresas = array();
            $empresas["img"] = RESOURCES_DOMAIN . "/img/famfam/sitemap_color.png";
            foreach( $perfiles as $perfil ){
                $empresaPerfil = $perfil->getCompany();
                if( !$usuarioActivo->accesoElemento($empresaPerfil) ){ continue; }

                $empresas[] = array(
                    "nombre" => $empresaPerfil->getUserVisibleName(),
                    "tipo"  => $empresaPerfil->getType(),
                    "oid"   => $empresaPerfil->getUID()
                );
            }
            $inlineArray[] = $empresas;
        }

        if ($usuarioActivo instanceof usuario) {
            if ($usuarioActivo->esStaff()) {
                $inlineArray[] =  array(
                    "img" => RESOURCES_DOMAIN . "/img/famfam/application_cascade.png",
                    array(
                        "nombre" => $this->getAppVersion(),
                        "tagName" => "span"
                    )
                );
            }

            if ($usuarioActivo->esStaff() && $ua = $this->getUserAgentData()) {
                $inlineArray[] =  array(
                    "img" => RESOURCES_DOMAIN . "/img/famfam/world.png",
                    array(
                        "nombre" => "{$ua->name} {$ua->version} ({$ua->platform})",
                        "tagName" => "span"
                    )
                );
            }
        }


        return $inlineArray;
    }


    /**

    */
    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
        $info = parent::getInfo(true, elemento::PUBLIFIELDS_MODE_TABLEDATA );

        $linedata =& $info[ $this->uid ];

        $linedata["nombre"] =  array(
            "innerHTML" => $linedata["nombre"]." ".$linedata["apellidos"]." - @".$linedata["usuario"],
            "href" => "ficha.php?m=usuario&poid=". $this->uid,
            "className" => "box-it link",
        );

        if (isset($linedata["email"])) {
            $linedata["email"] =  array(
                "innerHTML" => $linedata["email"],
                "href" => "mailto:".$linedata["email"]
            );
        } else {
            $linedata["email"] =  array(
                "innerHTML" => ""
            );
        }

        unset($linedata["usuario"]);
        unset($linedata["apellidos"]);

        $info["className"] = $this->getLineClass($parent, $usuario);

        return $info;
    }


    public function getLineClass($parent, $usuario){
        $estado = $this->verEstadoConexion(true);
        return $class = "color " . usuario::getColorFromStatus($estado);
    }

    /** INFORMACIÓN ACERCA DEL PERFIL ACTIVO */
    public function datosPerfilActivo(){
        return $this->perfilActivo()->getInfo();
    }

    /** INFORMACION ACERCA DE UN PERFIL */
    public function datosPerfil( $idPerfil ){
        $perfil = new perfil( $idPerfil );
        return $perfil->getInfo();
    }

    /** NOMBRE DEL PERFIL ACTIVO */
    public function nombrePerfilActivo(){
        return $this->perfilActivo()->getUserVisibleName();
    }


    public function sendWelcomeEmail(empresa $empresa){
        $password = usuario::randomPassword();

        $plantilla = new Plantilla();
        $plantilla->assign("lang", plantilla::getCurrentLocale());

        $plantilla->assign('elementoNombre', $this->obtenerDato("nombre"));
        $plantilla->assign('empresaNombre', $empresa->getUserVisibleName());
        $plantilla->assign('usuario',  $this->getUserName());
        $plantilla->assign('password', $password);
        $plantilla->assign('email', $this->getEmail());
        $plantilla->assign('elemento_logo', RESOURCES_DOMAIN . '/img/dokify-google-logo.png');

        $this->cambiarPassword($password, true );

        $html = $plantilla->getHTML('email/nuevousuario.tpl');

        $address = $this->getEmail();
        if (CURRENT_ENV == 'dev') $address = email::$developers;

        $email = new email($address);
        $email->establecerContenido($html);
        $email->establecerAsunto( utf8_decode( $plantilla("email_bienvenida_titulo") ));

        return $email->enviar();
    }

    public function sendRestoreEmail(){
        // esto va al passreset.tpl {$usuario->obtenerDato('token_password')}?
        //      <a href="{$smarty.const.CURRENT_DOMAIN}/agd/chgpassword.php?">Pulse aquí para modificar su contraseña </a>
        $log = new log();
        $log->info("usuario", "resetear clave", $this->getUserVisibleName() );


        $token = usuario::randomPassword();
        $token = MD5($token);
        $this->cambiarToken($token);

        $mailTemplate = new Plantilla();
        $mailTemplate->assign("usuario", $this );
        $mailTemplate->assign("token", $token );
        $mailTemplate->assign("tipo", 'usuario' );
        $html = $mailTemplate->getHTML("email/passreset.tpl");

        $email = new email( $this->getEmail() );
        $email->establecerAsunto("Restaurar password");
        $email->establecerContenido($html);

        $estado = $email->enviar();
        if( $estado !== true ){
            $estado = $estado && trim($estado) ? trim($estado) : $mailTemplate('error_desconocido');
            $log->resultado("error $estado", true);
            throw new Exception($estado);
        }

        $log->resultado("ok ", true);
        return true;
    }

    /***
        CAMBIA EL PERFIL ACTIVO
        @param = [ uid perfil, Object perfil ]
    */
    public function cambiarPerfil( $perfil ){
        if( $perfil instanceof perfil ){ $uidperfil = $perfil->getUID(); }
        if( is_numeric($perfil) ){ $uidperfil = $perfil; }

        if( is_numeric($uidperfil) ){
            $uidperfil = db::scape( $uidperfil );
            $sql = "UPDATE $this->tabla SET perfil = $uidperfil WHERE uid_usuario = ". $this->getUID();
        }


        $this->cache->set('uid-perfil-usuario-'.$this->uid, $uidperfil, true);
        $this->cache->clear('getinfo-'.$this->getUID().'-usuario--*');
        return $this->db->query( $sql );
    }

    public function removeParent(elemento $parent, usuario $usuario = null) {
        return false;
    }

    public static function importFromFile($file, $empresa, $usuario, $post = null)
    {
        $tpl = new Plantilla;
        $log = new log();
        $tmptabla = "tmp_table_usuario_import_".$usuario->getUID().uniqid();
        $temporal = DB_TMP .".$tmptabla";
        $reader = new dataReader($tmptabla , $file["tmp_name"], archivo::getExtension($file["name"]) );

        if ($reader->cargar(true)) {

            /* Comprobamos que los emails que quieren importar no esten repetidos en el fichero */
            $sql = "SELECT email FROM $temporal GROUP BY email HAVING count(email) > 1 ";
            $repitedEmailFile = db::get($sql, "*", 0);

            if ($repitedEmailFile) {
                $reader->borrar();
                throw new Exception( $tpl->getString('import_error_email_repeated'). implode(", ",$repitedEmailFile) );
            }

            /* Comprobamos que el email no este registrado ya como una petición válida  */
            $sql = "SELECT email FROM " .TABLE_USUARIO. " sr INNER JOIN $temporal USING(email) GROUP BY sr.email";
            $repitedEmailBD = db::get($sql, "*", 0);

            if ($repitedEmailBD) {
                $reader->borrar();
                throw new Exception( $tpl->getString('import_error_email_repeated'). implode(", ",$repitedEmailBD) );
            }

            $reader->borrar();

        }

        // Objeto database
        $db = db::singleton();

        // Importamos los elementos a la tabla
        $results = self::importBasics($usuario, $file, "usuario","usuario");

        $usersSQL = "SELECT usuario FROM ". $results["tmp_table"] ." t WHERE t.usuario IN (
            SELECT u.usuario FROM ". TABLE_USUARIO ." u WHERE u.uid_usuario NOT IN (". implode(",", $results["uids"]) .")
        )";
        if( ( $names = $db->query($usersSQL, "*", 0) ) === false ){
            throw new Exception( "Error al comprobar usuarios ya existentes" );
        }
        if( is_array($names) && count($names) ){
            $results["comentario"] = "Los usuarios siguientes no han sido importados, el nombre de usuario ya existe :". implode(", ",$names);
        }


        if( count($results["uids"]) && $results["insertados"] ){
            $rol = rol::DEFAULT_ID;

            if (isset($post['rol'])) {
              $rol = $post['rol'];
            }

            $rol = new rol($rol);

            $empresaCliente = $usuario->getCompany();
            $corporationUid = $empresa->esCorporacion() ? $empresa->getUID() : 'NULL';
            $sql = "INSERT IGNORE INTO ". TABLE_PERFIL ." ( uid_empresa, uid_corporation, uid_usuario, alias)
                SELECT ". $empresa->getUID() .", ". $corporationUid .", uid_usuario, 'Perfil ". $empresa->getUserVisibleName()."'
                FROM ". TABLE_USUARIO ."
                WHERE uid_usuario IN (". implode(",", $results["uids"]) .")
            ";
            if( $db->query($sql) ){

                $sql = "UPDATE ". TABLE_USUARIO ." u INNER JOIN ". TABLE_PERFIL ." p USING(uid_usuario)
                SET u.perfil = p.uid_perfil, u.app_version = 2
                WHERE uid_usuario IN (". implode(",", $results["uids"]) .")
                ";
                if( !$db->query($sql) ){
                    throw new Exception( "Error al activar perfiles" );
                }

                foreach( $results["uids"] as $uidUsuario ){
                    $newUser = new usuario($uidUsuario);
                    $profile = $newUser->perfilActivo();

                    $rol->actualizarPerfil($profile->getUID(), true);
                    if( !$newUser->sendWelcomeEmail($empresa) ){
                        dump("<span style='white-space:nowrap'>Error al mandar el email a ". $newUser->getUserVisibleName() . " ({$newUser->getEmail()})</span>" );
                    }
                }

                return $results;
            } else {
                throw new Exception( "Error al tratar de relacionar" );
            }
        } else {
            throw new Exception( "No hay elementos para relacionar" );
        }
    }

    /** NOS DA EL ID DEL PERFIL ACTIVO */
    public function idPerfilActivo(){
        return $this->obtenerDato('perfil');
    }

    /** RETORNARA UN CADENA DE TEXTO PARA INDENTIFICAR A UN USUARIO ATOMAAAGICAMENTE*/
    public function getPublicToken( $callback=false ){
        $infoUsuario = $this->getInfo();
        $pass = $infoUsuario["pass"];
        $arrayToken = array("u"=>$this->getUsername(),"p"=>$pass);
        $stringToken = base64_encode( serialize($arrayToken) );
        if( $callback && is_callable($callback) ){
            $stringToken = call_user_func($callback, $stringToken);
        }
        return $stringToken;
    }


    /** NOS RETORNA LOS PLUGINS DEL USUARIO EN UN ARRAY DE DATOS ( LOS DE EL PERFIL ACTIVO )*/
    public function obtenerPlugins(){
        return $this->perfilActivo()->getCompany()->obtenerPlugins();
    }

    public function accesoPlugin($param){
        if( $param instanceof plugin ){
            $uidPlugin = $param->getUID();
        } elseif( is_numeric($param) ){
            $uidPlugin = $param;
        } else {
            $plugin = new plugin($param);
            $uidPlugin = $plugin->getUID();
        }

        $pluginsUsuario = $this->obtenerPlugins(true);
        if (!$pluginsUsuario) return false;

        foreach( $pluginsUsuario as $plugin ){
            if( $plugin->getUID() == $uidPlugin ){
                return $plugin;
            }
        }
        return false;
    }

    /** ACTUALIZAR LOS PLUGINS DISPONIBLES PARA ESTE USUARIO ( PARA PERFIL ACTIVO )*/
    public function actualizarPlugins(){
        $this->perfilActivo()->actualizarPlugins();
    }

    /** OBTENER TODAS LAS ETIQUETAS ASIGNADAS A ESTE USUARIO EN FORMATO ARRAY */
    public function obtenerEtiquetas(){
        return $this->perfilActivo()->obtenerEtiquetas();
    }


    /** OBTENER EL EMAIL PRINCIPAL DEL USUARIO */
    public function getEmail(){
        $info = $this->getInfo();
        return trim($info["email"]);
    }

    public function getId(){
        $info = $this->getInfo();
        return trim($info["id"]);
    }

    /** OBTENER EL NOMBRE DE USUARIO */
    public function getUsername(){
        $username = $this->obtenerDato("usuario");
        if( !trim($username) ) return "SIN USUARIO";
        return $username;
    }

    /** OBTENER EL NOMBRE COMPLETO DE ESTE ELEMENTO */
    public function getUserVisibleName(){
        $info = $this->getInfo();
        return $info["nombre"] ." ". $info["apellidos"] ." @".$info["usuario"];
    }

    /** OBTENER EL NOMBRE COMPLETO DE ESTE ELEMENTO */
    public function getHumanName(){
        $info = $this->getInfo();
        if (!isset($info["usuario"])) {
            $tpl = Plantilla::singleton();
            return $tpl('user_unknow');
        }

        return $info["nombre"] ." ". $info["apellidos"];
    }

    public function getName(){
        $info = $this->getInfo();
        if (!isset($info["usuario"])) {
            $tpl = Plantilla::singleton();
            return $tpl('user_unknow');
        }

        return $info["nombre"];
    }

    public function getSurname(){
        $info = $this->getInfo();
        return $info["apellidos"];
    }

    public function getPhone(){
        $info = $this->getInfo();
        return $info["telefono"];
    }


    /** CAMBIAR EL PASSWORD AL USUARIO COMPROBANDO SI ES POSIBLE */
    public function cambiarPassword($password, $marcarParaRestaurar=false){
        //COMPROBAMOS QUE NO SE INTENTE INSERTAR UNA QUE YA SE USO
        $sql = "SELECT password
            FROM ". TABLE_USUARIOS_PASSWORD ."
            WHERE uid_usuario = ". $this->getUID() ." AND password = MD5('$password')";
        $resultset = $this->db->query( $sql );

        //SI NO ES ASI, CONTINUAMOS
        if( !$this->db->getNumRows( $resultset ) ){
            $sqlCurrentPass = "SELECT pass FROM $this->tabla WHERE pass IS NOT NULL AND uid_usuario = ".$this->getUID();
            $resultset = $this->db->query($sqlCurrentPass);

            if ($this->db->getNumRows($resultset) > 0) {
              //LA DEJAMOS EN EL HISTORICO PARA COMPROBAR QUE NO SE REPITA
              $sql = "INSERT INTO ". TABLE_USUARIOS_PASSWORD ." (password,uid_usuario) VALUES (
              ( SELECT pass FROM $this->tabla WHERE uid_usuario = ".$this->getUID()." )
              , ".$this->getUID().")";
              $this->db->query($sql);
            }

            $marcarParaRestaurar = ( $marcarParaRestaurar ) ? 1 : 0;
            $sql = "UPDATE $this->tabla SET
                pass = MD5('$password'),
                pass_sha1 = SHA1('$password'),
                pass_apache2 = MD5(concat(usuario,':". self::APACHE2_REALM .":$password')),
                actualizar_pass = $marcarParaRestaurar
            WHERE uid_usuario = ".$this->getUID();
            if( $this->db->query($sql) ){
                return true;
            } else {
                return $this->db->lastErrorString();
            }

        } else {
            return "error_clave_repetida";
        }
    }

    /** CAMBIAR EL TOKEN DE PASSWORD AL USUARIO Y MARCAR PASS PARA RESTAURAR */
    public function cambiarToken($token){
        $sql = "UPDATE $this->tabla SET
            token_password = '$token',
            fecha_token = ".time()."
        WHERE uid_usuario = ".$this->getUID();
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
        WHERE uid_usuario = ".$this->getUID();
        if( $this->db->query($sql) ){
            return true;
        } else {
            return false;
        }
    }

    /** OBTENER EL MAXIMO DE UPLOAD DEL USUARIO O ESTABLECERLO
      * Reset establecera si despues de la carga de un archivo el limite de carga volvera al estado por defecto del cliente
      */
    public function maxUploadSize($size=false, $reset=false){
        $company = $this->getCompany();
        if ($company->needsPay()) {
            return self::LIMITED_UPLOAD;
        }

        return self::DEFAULT_UPLOAD;
    }

    /** COMPARAR UN STRING CON EL PASSWORD DEL USUARIO */
    public function compararPassword($password, $func = "md5" ){
        $sql = "SELECT pass FROM $this->tabla WHERE uid_usuario = ".$this->getUID();
        $MD5pass = $this->db->query($sql, 0, 0);
        if( $func && is_callable($func) ){
            $password = call_user_func($func, $password);
        }
        return ( $MD5pass == $password ) ? true : false;
    }

    /** COMPROBAR SI EL USUARIO NECESITA CAMBIAR EL PASSWORD */
    public function necesitaCambiarPassword(){
        $sql = "SELECT actualizar_pass FROM $this->tabla WHERE uid_usuario = ".$this->getUID();

        $necesitaActualizar = $this->db->query($sql, 0, 0);
        return ( $necesitaActualizar ) ? true : false;
    }

    /** CAMBIAR OBLIGATORIEDAD DE ACTUALIZAR PASSWORD */
    public function changePasswordNotRequired(){

        $sql = "UPDATE ". $this->tabla ."
            SET actualizar_pass = 0
            WHERE uid_usuario = ".$this->getUID();

        if( $this->db->query($sql) ){
            return true;
        }

        return false;
    }

    /** NOS RETORNA EL OBJETO USUARIO GUARDADO EN EL TOKEN */
    public static function instaceFromToken($token){
        $arrayToken = unserialize( base64_decode($token) );
        if( isset($arrayToken["u"]) && isset($arrayToken["p"]) ){
            $usuario = self::login( $arrayToken["u"] );
            if( $usuario->compararPassword($arrayToken["p"], null) ){
                return $usuario;
            }
        }
        return false;
    }

    /** INSTANCIAR UN USUARIO DESDE EL NOMBRE DE USUARIO*/
    static public function fromUserName($username){
        $db = db::singleton();
        $sql = "SELECT uid_usuario FROM ". TABLE_USUARIO ." WHERE usuario = '". db::scape($username) ."'";

        $uidUsuario = $db->query($sql, 0, 0);
        if( is_numeric($uidUsuario) ){
            return new self($uidUsuario);
        } else {
            return false;
        }
    }

    /** COMPROBAR SI UN PASSWORD ES VÁLIDO */
    static public function comprobarPassword($pass1, $pass2=false){
        if (!preg_match("/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/", $pass1)) {
             return "error_pass_demasiado_debil";
        } else {
            if( $pass2 ){
                if( $pass1 === $pass2 ){
                    return true;
                } else {
                    return "error_pass_no_coincide";
                }
            } else {
                return true;
            }
        }

    }

    /** GENERAR UN PASSWORD AUTOMATICAMENTE */
    static public function randomPassword(){
        return substr(md5(rand(0,100000).time()), 1,10 );
    }

    public function obtenerBusquedas($filter=false, $count = false){
        return buscador::obtenerBusquedas($this, false, $filter, $count);
    }

    public function obtenerBusquedasCompartidas($count = false){
        return buscador::obtenerBusquedas($this, true, false, $count);
    }

    public function updateData($data, Iusuario $usuario = null, $mode = null)
    {
        if (isset($data["email"])) {

            if (StringParser::isEmail($data['email']) === false) {
                throw new Exception(_("The email address is not valid"));
            }

            if (isset($data["nocheck"])) {
                unset($data["nocheck"]);
            } else {
                if (self::isEmailInUse($data["email"], $this)) {
                    throw new Exception(_("The email is already in use"));
                }
            }
        }

        if ($mode === elemento::PUBLIFIELDS_MODE_EDIT) {

            if (isset($data["email"])) {
                if (trim($data["email"]) == "") {
                    throw new Exception("error_email_vacio", 1);
                }
            }

            if (isset($data["nombre"])) {
                if (trim($data["nombre"]) == "") {
                    throw new Exception("error_nombre_vacio", 1);
                }
            }

            if (isset($data["apellidos"])) {
                if (trim($data["apellidos"]) == "") {
                    throw new Exception("error_apellido_vacio", 1);
                }
            }

            if (isset($data["telefono"])) {
                if (trim($data["telefono"]) == "") {
                    throw new Exception("error_telefono_vacio", 1);
                }
            }
        }

        return $data;
    }


    public static function getExportSQL($usuario, $uids, $forced, $parent=false){
        $campos = array();
        if( $usuario->esSATI() || $usuario->esAdministrador() ){
            $campos[] = "uid_usuario";
        }

        $campos[] = "usuario";
        $campos[] = "concat(nombre, ' ', apellidos) as nombre";
        $campos[] = "email";

        $campos[] = "(
            SELECT
            (SELECT nombre FROM ". TABLE_ROL ." WHERE rol.uid_rol = perfil.rol) as rol
            FROM ". TABLE_PERFIL ." INNER JOIN ". TABLE_USUARIO ." u
            ON u.perfil = perfil.uid_perfil
            WHERE u.uid_usuario = usuario.uid_usuario
        )";

        $sql =  "SELECT ". implode(",", $campos) ." FROM ". TABLE_USUARIO ." WHERE 1";

        if( is_array($uids) && count($uids) ){
            $sql .=" AND uid_usuario in (". implode(",", $uids ) .")";
        } else {
            if( is_numeric($parent) ){
                $sql .=" AND uid_usuario in ( SELECT uid_usuario FROM ". TABLE_PERFIL ." WHERE uid_empresa = $parent )";
            }
        }

        $sql .=" AND uid_usuario IN (". implode(",", $forced) .")";


        return $sql;
    }

    public static function getColorFromStatus($status){
        switch($status){
            case self::USER_OFFLINE:
                return "black";
            break;
            case self::USER_ONLINE:
                return "green";
            break;
            case self::USER_INACTIVE:
                return "orange";
            break;
            case self::USER_LOCKED:
                return "red";
            break;
        }
        return "";
    }

    /** CONVERTIR LOS ESTADOS NUMERICOS EN CADENAS DE TEXTO */
    static public function status2String( $uidestado ){
        $lang = Plantilla::singleton();
        switch ($uidestado) {
            case 0: return $lang->getString("desconectado"); break;
            case 1: return $lang->getString("conectado"); break;
            case 2: return $lang->getString("inactivo"); break;
            case 3: return $lang->getString("bloqueado"); break;
        }
    }

    static public function getCurrent(){
        if( isset($_SESSION["OBJETO_USUARIO"]) ){
            return unserialize( $_SESSION["OBJETO_USUARIO"] );
        } else {
            return false;
        }
    }

    public static function instanceFromCookieToken($username, $apache2token) {
        $db = db::singleton();
        $sql = "
            SELECT uid_usuario FROM ". TABLE_USUARIO ."
            WHERE usuario ='". db::scape($username) ."'
            AND pass_apache2 ='". db::scape($apache2token) ."'
        ";

        $uidUsuario = $db->query($sql,0,0);
        if ($uidUsuario) return new usuario($uidUsuario);

        return false;
    }

    public static function instanceFromUsername($name) {
        $db=db::singleton();
        $sql= "SELECT uid_usuario FROM ".TABLE_USUARIO." WHERE usuario ='". utf8_decode(db::scape($name)) ."'";
        $uidUsuario = $db->query($sql,0,0);
        if( $uidUsuario ) {
            return new usuario($uidUsuario);
        }
        return false;
    }

    // Comprobar que token de seguridad y email son correctos para adjudicar uid usuario y cambiar password
    public static function uidTokenEmail($token, $email = false) {
        $db=db::singleton();

        $SQL = "SELECT uid_usuario FROM ". TABLE_USUARIO ." WHERE token_password = '". db::scape($token) ."'";
        if( $email ){
            $SQL .= " AND email = '". db::scape($email) ."'";
        }

        $uid = $db->query($SQL, 0, 0);

        if( is_numeric($uid) ){
            return new usuario($uid);
        }

        return false;
    }

    public function obtenerAgrupadoresVisibles() {
        $agrupamientosVisibles = $this->getCompany()->obtenerAgrupadoresVisibles(array($this));
        return $agrupamientosVisibles;
    }

    public function obtenerAgrupamientosVisibles($condicion = null) {
        $condicion[] = $this;
        $agrupamientosVisibles = $this->getCompany()->obtenerAgrupamientosVisibles($condicion);
        return $agrupamientosVisibles;
    }

    public function hasDuplicateEmail() {
        $num = self::isEmailInUse($this->getEmail(), $this);
        return (bool) $num;
    }


    /***
       * Indicates the url to redirect the user if it needs to go to a payment page
       *
       */
    public function needsRedirectToPayment () {
        $isStaff    = $this->esStaff();

        if ($isStaff) {
            return false;
        }

        $userCompany = $this->getCompany();

        return $userCompany->needsRedirectToPayment();
    }

    public function getCountry() {
        return $this->getCompany()->getCountry();
    }


    public static function cronCall($time, $force = false, $items = null)
    {
        $minute = date("i", $time);
        $hour = date("H", $time);
        $dayOfWeek = date("w", $time);

        // This block is temporary commented because nowadays we do not expire passwords
        // if (($hour === '00' && $minute === '00') || $force) {
        //     $updates = self::caducarPassword();
        // }

        if ($hour === '01' && $minute === '00' && $dayOfWeek === '00' || $force) {
            self::caducarToken();
        }

        return true;
    }

    public static function caducarPassword(){
        $shell = isset($_SERVER["PWD"]);
        $db = db::singleton();

        $dateField = "(SELECT fecha FROM ". TABLE_USUARIO ."_password up WHERE up.uid_usuario = u.uid_usuario ORDER BY fecha DESC LIMIT 1)";
        $SQL = "
            SELECT uid_usuario
            FROM ". TABLE_USUARIO ." u
            WHERE fecha_primer_acceso != 0 AND actualizar_pass = 0
            AND datediff(now(), if(@fecha := $dateField, @fecha, FROM_UNIXTIME(fecha_primer_acceso)) ) > 365
            ORDER BY uid_usuario ASC
            LIMIT 15
        ";

        $uids = $db->query($SQL, '*', 0);

        // --- si no tenemos uids es que no hay que caducar nada
        if (!$uids) {
            if ($shell) echo "Ningun usuario necesita cambiar su password\n";
            return true;
        }

        $list = implode(',', $uids);
        if ($shell) echo "Vamos a caducar la password de ". count($uids) . " usuarios [{$list}] ... ";

        $SQL = "UPDATE ". TABLE_USUARIO ." SET actualizar_pass = 1 WHERE uid_usuario IN ({$list})";


        if( $db->query($SQL) ){
            if ($shell) echo " OK!\n";
            return $db->getAffectedRows();
        } elseif($err = $db->lastError()) {
            if ($shell) echo " Error: {$err}!\n";
            error_log($err);
        }


        return false;
    }

    public static function caducarToken()
    {
        $database = db::singleton();

        $tableUser = TABLE_USUARIO;
        $sql = "UPDATE {$tableUser}
        SET token_password = NULL, fecha_token = NULL
        WHERE fecha_token != 0
        AND datediff(NOW(), FROM_UNIXTIME(fecha_token)) > 7";

        if ($database->query($sql)) {
            return $database->getAffectedRows();
        }

        return false;
    }

    public static function getLastLogin(){
        if( isset($_SESSION["USUARIO_LAST_ACCESS"]) ){
            return date("d/m/Y H:i", $_SESSION["USUARIO_LAST_ACCESS"] );
        }
    }

    static public function getAllStaff(){
        $sql = "SELECT uid_usuario FROM ". TABLE_USUARIO ." WHERE config_sat = 1";
        return db::get($sql, "*", 0, "usuario");
    }

    static public function getUserRegExp(){
        return "^[a-zA-Z0-9_\.\-]+$";
    }


    public static function isEmailInUse($email, usuario $exclude = NULL) {
        $SQL = "SELECT count(uid_usuario) FROM ". TABLE_USUARIO ." WHERE email = '". db::scape($email) ."'";

        if ($exclude instanceof usuario) $SQL .= " AND uid_usuario != {$exclude->getUID()}";

        return db::get($SQL, 0, 0);
    }

    public static function isIdInUse($id) {
        $SQL = "SELECT count(uid_usuario) FROM ". TABLE_USUARIO ." WHERE  id = '". db::scape($id) ."'";
        return db::get($SQL, 0, 0);
    }

    public function obtenerDistancia($empresa=false, $toString=false, $inferiores=false,  $process=0 ){
        return $this->getCompany()->obtenerDistancia($empresa, $toString, $inferiores,  $process);
    }

    public function getValidations(empresa $partner, $firstDate = null, $endDate = null)
    {
        $validationTable = TABLE_VALIDATION;
        $validationStatusTable = TABLE_VALIDATION_STATUS;
        $filters = [];

        $filters[] = " uid_partner = {$partner->getUID()}";

        if ($firstDate) {
            $filters[] = "date >= FROM_UNIXTIME($firstDate)";
        }

        if ($endDate) {
            $filters[] = "date <= FROM_UNIXTIME($endDate)";
        }

        $filterCondition = implode(" AND ", $filters);

        $ownersCompanies = $partner->getCompaniesValidatedByPartner($firstDate, $endDate);

        if (0 === count($ownersCompanies)) {
            return new ArrayValidationStats([], $this);
        }

        $ownersCompanies = $ownersCompanies->toComaList();

        $sql = "SELECT SUM(CAST((1/num_anexos) AS DECIMAL(10, 6))) as count, language, uid_partner, uid_empresa_propietaria
        FROM {$validationTable}
        INNER JOIN {$validationStatusTable} USING(uid_validation)
        WHERE uid_usuario = {$this->getUID()}
        AND uid_empresa_propietaria IN ({$ownersCompanies})
        AND {$filterCondition}
        GROUP BY uid_empresa_propietaria, language
        ";

        $validationsUser = db::get($sql, true, false, "ArrayValidationStats");

        return new ArrayValidationStats($validationsUser, $this);
    }

    public function watchingThread($element, $requirements)
    {
        if (!$requirements || 0 === count($requirements)) {
            return false;
        }

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
        if ($wachingComment) {
            return new watchComment($wachingComment, $moduleName);
        }

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
        //we use the language of the country as the user language
        $lang = $this->getCompany()->getCountry()->getLanguage();
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
            if ($replyTo) $email->addReplyTo($replyTo, 'dokify');

            $htmlPath ='email/'.$tpl.'.tpl';
            $html = $plantilla->getHTML($htmlPath);
            $email->establecerContenido($html);

            if (!is_array($asunto)) {
                $subject = $plantilla->getString($asunto, $lang);
            } else {
                $asunto[0] = $plantilla->getString($asunto[0], $lang);
                $subject = call_user_func_array("sprintf", $asunto);
            }

            $subject = (strlen($subject) > 103) ? substr($subject, 0, 100) .'...' : $subject;
            $email->establecerAsunto($subject);

            $estado = $email->enviar();

            if ($estado !== true) {
                $estado = $estado && trim($estado) ? trim($estado) : $plantilla('error_desconocido');
                $log->resultado("error $estado", true);
                throw new Exception($estado);
            }

            $log->resultado("ok ", true);
            return true;
        }

        return false;
    }

    static public function fromEmail($email) {

        $sql = "SELECT uid_usuario FROM ". TABLE_USUARIO ." WHERE email = '". db::scape($email) ."' limit 1";
        $uid = db::get($sql, 0, 0);
        if (is_numeric($uid)) return new usuario($uid);
        return false;

    }

    public function canShowTour($tour)
    {
        if (!is_numeric($tour)) {
            return false;
        }

        if ($this->hasToursEnabled() === false) {
            return false;
        }

        $sql = "SELECT uid_element FROM ". ELEMENT_TOUR ."
                WHERE uid_element = {$this->getUID()}
                AND uid_module = {$this->getModuleId()}
                AND uid_tour = $tour";

        $uidElement = $this->db->query($sql, 0, 0);
        if ($uidElement) {
            return false;
        }
        return true;
    }

    /**
     * Check if one tour has been shown after a createdAt
     * @param int $tour
     * @param \DateTime $createdAt
     * @return bool
     */
    public function isShownTourAfterOf($tour, \DateTime $createdAt)
    {
        if (!is_numeric($tour)) {
            return false;
        }

        $sql = "SELECT uid_element FROM ". ELEMENT_TOUR ."
                WHERE uid_element = {$this->getUID()}
                AND uid_module = {$this->getModuleId()}
                AND uid_tour = $tour
                AND created >= '{$createdAt->format('Y-m-d H:i:s')}'";

        $uidElement = $this->db->query($sql, 0, 0);

        return (bool) $uidElement;
    }

    public function setTour ($tour) {
        if (!$tour || is_numeric($tour) === false) {
            return false;
        }

        $sql = "INSERT IGNORE INTO ". ELEMENT_TOUR ." (uid_element, uid_module, uid_tour)
                VALUES ({$this->getUID()}, {$this->getModuleId()}, '". db::scape($tour) ."')";

        return $this->db->query($sql);
    }

    public function updateCheckedEmployees () {

        $latLng = ($userLatLang = $this->getLatLng()) ? "'{$userLatLang}'" : "NULL";
        $SQL = "
            UPDATE ". TABLE_EMPLEADO ."
            SET
                location_timestamp = NOW(),
                latlng = {$latLng}
            WHERE
                uid_usuario_location = '{$this->getUID()}'
        ";

        return $this->db->query($SQL);

    }


    public function canModifyVisibilityOfUsers () {
        $perfil = $this->perfilActivo();
        if ($perfil) return $perfil->canModifyVisibilityOfUsers();
        return false;
    }

    public function getUserLimiter (empresa $company) {
        $perfil = $this->perfilActivo();
        if ($perfil) return $perfil->getUserLimiter($company);
        return false;
    }

    static public function optionsFilter($uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){
        $condicion = array();
        if ($user instanceof usuario) {
            if ($user->canModifyVisibilityOfUsers()) $condicion[] = "uid_accion NOT IN (52)"; // removing visibility from options
            if (is_numeric($uidelemento)) {
                $optionUser = new self($uidelemento);
                if ($optionUser instanceof usuario && $optionUser->compareTo($user)) {
                    $condicion[] = "uid_accion NOT IN (52)";
                }
            }
        }

        if( count($condicion) ){
            return " AND ". implode(" AND ", $condicion);
        }

        return false;
    }

    public function setCompanyWithHiddenDocuments (empresa $company, $hide = true, usuario $usuario = NULL) {
        $perfil = $this->perfilActivo();
        if ($perfil) return $perfil->setCompanyWithHiddenDocuments($company, $hide, $usuario);
        return false;
    }

    public function getCompaniesWithHiddenDocuments($corps = false, $forCompanyAdmin = false)
    {
        $perfil = $this->perfilActivo();
        if ($perfil) {
            return $perfil->getCompaniesWithHiddenDocuments($corps, $forCompanyAdmin);
        }

        return false;
    }

    public function setVisibilityForAllCompanies($onlyForUser = false)
    {
        $perfil = $this->perfilActivo();
        if ($perfil) {
            return $perfil->setVisibilityForAllCompanies($onlyForUser);
        }
    }

    public function hideAllDocumentsBut (empresa $company)
    {
      if ($perfil = $this->obtenerPerfil()) {
        return $perfil->hideAllDocumentsBut($company);
      }

      return false;
    }

    public function getGlobalStatusForClient () {
        return NULL;
    }

    public function getAssignment (agrupador $group) {

        $profile = $this->perfilActivo();

        $sql = "SELECT uid_agrupador_elemento
        FROM ". TABLE_AGRUPADOR ."_elemento
        WHERE uid_elemento  = {$profile->getUID()}
        AND uid_modulo      = {$profile->getModuleId()}
        AND uid_agrupador   = {$group->getUID()}";

        if ($id = $this->db->query($sql, 0, 0)) {
            return new \Dokify\Assignment($id);
        }
    }

    /**
     * Return true if the user can upload documents to he given client
     * @param  empresa $client
     * @return boolean
     */
    public function canUploadDocumentsTo(empresa $client)
    {
        $userCompany = $this->getCompany();
        $needsPay = $userCompany->needsPay();
        $clientsWithPayment = $userCompany->pagoPorSubcontratacion();
        $payForClient = $clientsWithPayment && $clientsWithPayment->contains($client);

        return !($needsPay && $payForClient) || $userCompany->hasOptionalPayment();
    }

    /** CAMPOS DE LA TABLA USUARIO PARA DIFERENTES VISTAS */
    static public function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $arrayCampos = new FieldList;

        if ($modo != elemento::PUBLIFIELDS_MODE_EDIT) {
            $arrayCampos["usuario"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "match" => self::getUserRegExp() ));
        } else {
            $arrayCampos["usuario"] = new FormField(array("tag" => "span"));
        }

        $arrayCampos["nombre"]              = new FormField(array("tag" => "input", "type" => "text", "blank" => false ));
        $arrayCampos["apellidos"]           = new FormField(array("tag" => "input", "type" => "text", "blank" => false ));
        $arrayCampos["telefono"]            = new FormField(array("tag" => "input", "type" => "text", "blank" => false ));
        $arrayCampos["email"]               = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "match" => elemento::getEmailRegExp() ));
        $arrayCampos["id"]                  = new FormField(array("tag" => "input", "type" => "text", "innerHTML" => "dni"));


        switch ($modo) {
            case elemento::PUBLIFIELDS_MODE_GEO:
                $arrayCampos = new FieldList;
                $arrayCampos["latlng"] = new FormField;
                $arrayCampos["location_timestamp"] = new FormField;

                return $arrayCampos;
            case Iusuario::PUBLIFIELDS_MODE_USERAGENT:
                $arrayCampos = new FieldList;
                $arrayCampos["user_agent"] = new FormField;
                $arrayCampos["locale"] = new FormField;

                return $arrayCampos;
            case elemento::PUBLIFIELDS_MODE_SEARCH:
                $arrayCampos = new FieldList;

                $arrayCampos["usuario"] = new FormField();
                $arrayCampos["concat(nombre, ' ', apellidos)"] = new FormField();
                $arrayCampos["email"] = new FormField();

                return $arrayCampos;
            break;
            case elemento::PUBLIFIELDS_MODE_TABLEDATA:
                unset($arrayCampos["id"]);
                unset($arrayCampos["telefono"]);
            break;
            case elemento::PUBLIFIELDS_MODE_EDIT:

                if ($usuario instanceof usuario && $usuario->esStaff()) {
                    $arrayCampos["config_economicos"] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox" ));
                    //$arrayCampos["upload"] = new FormField(array("tag" => "slider",   "match" => "^[0-9]+$", "className" => "slider", "count" => "52428800", "divide" => "1048576"));
                }

                if ($usuario instanceof usuario && ($usuario->esStaff() || $usuario->esValidador())) {
                    $arrayCampos["config_validador"] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox" ));
                }

                if ($usuario instanceof usuario && $usuario->esAdministrador()) {
                    $arrayCampos["config_auditor"] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox" ));
                }

                if ($usuario instanceof usuario && 2 === $usuario->getAppVersion()) {
                    $arrayCampos["config_externo"]  = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox" ));
                }

                if ($usuario instanceof usuario && $usuario->esStaff()) {
                    $arrayCampos["config_externo"]  = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox" ));
                    $arrayCampos["newapp_allowed"]  = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox" ));
                    $arrayCampos["config_tours"]    = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox" ));
                }

                $arrayCampos["config_betatester"]   = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox" ));
                $arrayCampos["direccion"]           = new FormField(array("tag" => "textarea"));

                if ($usuario instanceof usuario && $usuario->getAppVersion() >= 2) {
                    $arrayCampos["latlng"]              = new FormField(array("tag" => "input", "type" => "hidden"));
                    $arrayCampos["location_timestamp"]  = new FormField(array("tag" => "input", "type" => "hidden"));
                    $arrayCampos["is_security"]         = new FormField;
                }

            break;
            case elemento::PUBLIFIELDS_MODE_PREFS:
                $arrayCampos = new FieldList;
                $arrayCampos["config_viewall"]          = new FormField(array("tag" => "input", "type" => "checkbox" ));
                $arrayCampos["flag_asistente"]          = new FormField(array("tag" => "input", "type" => "checkbox" ));
                $arrayCampos["dissmiss_tour_comments"]  = new FormField;

                // newapp
                $arrayCampos["newapp_allowed"] = new FormField;
            break;

            case self::PUBLIFIELDS_MODE_TIMEZONE:
                $arrayCampos = new FieldList;
                $arrayCampos["timezone_offset"] = new FormField();
            break;
        }

        return $arrayCampos;
    }

    public function obtenerEmpresasSolicitantes(){
        return $this->getCompanies()->unique();
    }

    /*
     * Return if the user is an auditor
     */
    public function isAuditor()
    {
        return (bool) $this->configValue('auditor');
    }

    /*
     * Reset the screen user from the attachments which this user has on his screen
     */
    public function clearValidationQueue()
    {
        try {
            $modules = solicitable::getModules();

            foreach ($modules as $module) {
                $table = PREFIJO_ANEXOS . "{$module}";
                $sql = "UPDATE {$table} SET screen_uid_usuario = NULL, screen_time_seen = 0 WHERE screen_uid_usuario = ({$this->getUID()})";
                $this->db->query($sql);
            }
        } catch (Exception $e) {
            error_log('Error clearing validation queue for a validator');
        }
    }

    /*
     * Reset the audit screen user from the validations which this user has on his screen
     */
    public function clearAuditValidationQueue()
    {
        $validationTable = TABLE_VALIDATION;

        $sql = "UPDATE {$validationTable}
        SET screen_audit_uid_usuario = NULL
        ,   screen_audit_time_seen = 0
        WHERE screen_audit_uid_usuario = {$this->getUID()}";

        $this->db->query($sql);
    }

    /**
    * {@inheritDoc}
    */
    public function isActiveWatcher()
    {
        $profiles = TABLE_PERFIL;

        $sql = "
            SELECT COUNT(uid_perfil)
            FROM {$profiles}
            WHERE uid_usuario = {$this->uid}
            AND papelera = 0
        ";

        return (bool) $this->db->query($sql, 0, 0);
    }

    private function groupBlackList($filterType = 'elements')
    {
        $filterField = 'config_filter';
        if ('documents' === $filterType) {
            $filterField = 'config_documentos';
        }

        $groupsTable = TABLE_AGRUPADOR;
        $organizationsTable = TABLE_AGRUPAMIENTO;
        $groupTagsTable = TABLE_AGRUPADOR . "_etiqueta";

        $groupBlackList = new ArrayAgrupadorList();
        $ownCompany = $this->getCompany();

        if (true == $corp = $ownCompany->perteneceCorporacion()) {
            $originCompanies = [$ownCompany->getUID(), $corp->getUID()];
            $companyList = implode(',', $originCompanies);
        } else {
            $companyList = $ownCompany->getStartIntList()->toComaList();
        }

        if (true === $this->isViewFilterByGroups()) {
            $assignedGroups = $this->obtenerAgrupadores();
            $assignedGroupsList = count($assignedGroups) > 0 ? $assignedGroups->toComaList() : '0';

            $sql = "SELECT grp.uid_agrupador
            FROM {$groupsTable} grp
            INNER JOIN {$organizationsTable} org USING(uid_agrupamiento)
            WHERE org.uid_empresa IN ($companyList)
            AND org.{$filterField} = 1
            AND grp.uid_agrupador NOT IN ($assignedGroupsList)
            ";

            $groupBlackList = $groupBlackList->merge($this->db->query($sql, "*", 0, "agrupador"));

        }

        // Filtro por etiquetas
        if (true === $this->isViewFilterByLabel()) {
            $userTags = $this->obtenerEtiquetas()->toIntList()->getArrayCopy();

            if (true === is_traversable($userTags) && count($userTags) > 0) {
                $userTagsList = implode(",", $userTags);

                $sql = "SELECT grp.uid_agrupador
                FROM {$groupsTable} grp
                INNER JOIN {$organizationsTable} org USING(uid_agrupamiento)
                LEFT JOIN {$groupTagsTable} tag USING(uid_agrupador)
                WHERE org.uid_empresa IN ($companyList)
                AND org.{$filterField} = 1
                AND IFNULL(tag.uid_etiqueta, 0) NOT IN ({$userTagsList})
                ";
            } else {
                $sql = "SELECT tag.uid_agrupador
                FROM {$groupTagsTable} tag
                INNER JOIN {$groupsTable} grp USING(uid_agrupador)
                INNER JOIN {$organizationsTable} org USING(uid_agrupamiento)
                WHERE org.uid_empresa IN ($companyList)
                AND org.{$filterField} = 1
                ";
            }

            $groupBlackList = $groupBlackList->merge($this->db->query($sql, "*", 0, "agrupador"));
        }

        return $groupBlackList;
    }

    public function groupBlackListForElements()
    {
        return $this->groupBlackList('elements');
    }

    public function groupBlackListForDocuments()
    {
        return $this->groupBlackList('documents');
    }

    /** ALIAS PARA SHOW COLUMNS DE ESTA TABLA **/
    public function getTableFields()
    {
        return array(
            array("Field" => "uid_usuario",             "Type" => "int(10)",            "Null" => "NO",     "Key" => "PRI", "Default" => "",        "Extra" => "auto_increment"),
            array("Field" => "usuario",                 "Type" => "varchar(45)",        "Null" => "NO",     "Key" => "UNI", "Default" => "",        "Extra" => ""),
            array("Field" => "pass",                    "Type" => "varchar(120)",       "Null" => "YES",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "pass_sha1",               "Type" => "varchar(255)",       "Null" => "YES",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "pass_apache2",            "Type" => "varchar(255)",       "Null" => "YES",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "perfil",                  "Type" => "int(10) unsigned",   "Null" => "YES",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "nombre",                  "Type" => "varchar(45)",        "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "apellidos",               "Type" => "varchar(45)",        "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "id",                      "Type" => "varchar(45)",        "Null" => "YES",    "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "telefono",                "Type" => "varchar(200)",       "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "actualizar_pass",         "Type" => "int(1) unsigned",    "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "token_password",          "Type" => "varchar(255)",       "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "fecha_token",             "Type" => "int(16)",            "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "fecha_alta",              "Type" => "int(16) unsigned",   "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "fecha_primer_acceso",     "Type" => "int(16)",            "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "fecha_ultimo_acceso",     "Type" => "int(16) unsigned",   "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "fecha_accion",            "Type" => "int(16)",            "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "last_page",               "Type" => "varchar(500)",       "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "conexion",                "Type" => "int(1) unsigned",    "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "upload",                  "Type" => "varchar(45)",        "Null" => "NO",     "Key" => "",    "Default" => "5242880", "Extra" => ""),
            array("Field" => "email",                   "Type" => "varchar(255)",       "Null" => "YES",    "Key" => "UNI", "Default" => "",        "Extra" => ""),
            array("Field" => "config_autologin",        "Type" => "int(1) unsigned",    "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "ip",                      "Type" => "varchar(15)",        "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "icon",                    "Type" => "varchar(100)",       "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "avatar",                  "Type" => "varchar(512)",       "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "user_agent",              "Type" => "varchar(256)",       "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "config_tipoopciones",     "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "config_admin",            "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "config_resetupload",      "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "config_sat",              "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "config_agent",            "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "config_viewall",          "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "config_validador",        "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "config_auditor",          "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "config_economicos",       "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "config_uploader",         "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "config_betatester",       "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "config_receive_summary",  "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "1",       "Extra" => ""),
            array("Field" => "config_tours",            "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "1",       "Extra" => ""),
            array("Field" => "show_all_documents",      "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "is_security",             "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "flag_asistente",          "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "1",       "Extra" => ""),
            array("Field" => "timezone",                "Type" => "varchar(254)",       "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "timezone_offset",         "Type" => "double",             "Null" => "NO",     "Key" => "",    "Default" => "-1",      "Extra" => ""),
            array("Field" => "latlng",                  "Type" => "varchar(40)",        "Null" => "YES",    "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "location_timestamp",      "Type" => "timestamp",          "Null" => "YES",    "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "config_externo",          "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "direccion",               "Type" => "varchar(1024)",      "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "app_version",             "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "1",       "Extra" => ""),
            array("Field" => "newapp_allowed",          "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
            array("Field" => "locale",                  "Type" => "varchar(5)",         "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "uses_vq",                 "Type" => "int(1)",             "Null" => "NO",     "Key" => "",    "Default" => "0",       "Extra" => ""),
        );
    }
}
