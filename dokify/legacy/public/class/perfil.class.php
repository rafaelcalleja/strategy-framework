<?php

class perfil extends categorizable implements Iusuario, Iactivable, Ielemento {

    public function __construct( $param , $saveOnSession = false ){
        $this->tipo = "perfil";
        $this->tabla = TABLE_PERFIL;

        $this->instance( $param, $saveOnSession );
    }

    public function getMatchingRoles ()
    {
        $user           = $this->getUser();
        $matches        = new ArrayObjectList;

        if ($user->esStaff()) {
            $matches[] = new rol(1513);
            return $matches;
        }

        $profileActions = $this->getActions();
        $profileCount   = count($profileActions);

        // no role exists with too less permissions
        if ($profileCount < 36) {
            return $matches;
        }

        // $roles = rol::obtenerRolesGenericos();
        $roles = [
            new rol(1513), // default
            new rol(1534), // administrar sin configurar
            new rol(1530), // solo ver
            new rol(1531), // ver sin descargar
            new rol(1533), // ver y asignar
            new rol(1541), // ver solo documentos
            new rol(1537), // anexar y validar
        ];

        foreach ($roles as $role) {
            $roleActions = $role->getActions();
            $roleCount   = count($roleActions);

            // never add more permissions
            if ($profileCount < $roleCount) {
                continue;
            }

            $diff = array_diff($roleActions, $profileActions);
            $matches[$role->getUID()] = count($diff);
        }

        if (count($matches)) {
            $matchesArray = $matches->getArrayCopy();
            arsort($matchesArray);

            $matches = new ArrayObjectList;
            foreach (array_keys($matchesArray) as $uid) {
                $matches[] = new rol($uid);
            }
        }

        return $matches;
    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Profile\Profile
     */
    public function asDomainEntity()
    {
        $info = $this->getInfo();
        return $this->app['profile.repository']->factory($info);
    }

    /***
       *
       *
       *
       *
       *
       */
    public function setLatLng ($location) {
        return $this->getUser()->setLatLng($location);
    }


    /**
     * [Tell us if the user can do the @option over the @item (@parent is aux)]
     * @param  [mixed] $item
     * @param  [string|int] $option
     * @param  [object] $parent
     * @return [bool]
     */
    public function canAccess ($item, $option = \Dokify\AccessActions::VIEW, $parent = null)
    {
        $user = $this->getUser();
        return $user->canAccess($item, $option, $parent);
    }


    /***
       *
       *
       *
       *
       *
       */
    public function getLatLng () {
        return $this->getUser()->getLatLng();
    }

    /***
       *
       *
       *
       *
       *
       */
    public function getAddress () {
        return $this->getUser()->getAddress();
    }


    public function getCompaniesWithHiddenDocuments($corps = false, $forCompanyAdmin = false)
    {
        $sql = "SELECT uid_empresa FROM ". TABLE_PERFIL . "_empresa WHERE uid_perfil = {$this->getUID()}";

        if (true === $forCompanyAdmin) {
            $sql .= " AND uid_usuario IS NOT NULL";
        }

        if ($list = $this->db->query($sql, '*', 0, 'empresa')) {
            $companies = new ArrayObjectList($list);

            // -- si queremos obtener tambien las corporaciones debemos hacer comprobaciones extra
            if ($corps) {
                // -- buscamos todos los clientes visibles para esta empresa
                $companiesClients = $this->getCompany()->obtenerEmpresasClienteConDocumentos();

                // -- vemos las corporaciones que SI tenemos que mostrar
                $corps = new ArrayObjectList;
                foreach ($companiesClients as $companyClient) {
                    if (!$companies->contains($companyClient) && $corp = $companyClient->perteneceCorporacion()) {
                        $corps[] = $corp;
                    }
                }

                $hiddenCompanies = $companies; // hacemos esto para evitar añadir indices dentro del foreach
                foreach ($hiddenCompanies as $company) {
                    if (($corp = $company->perteneceCorporacion()) && !$corps->contains($corp)) {
                        $companies[] = $corp;
                    }
                }

                $companies = $companies->unique();
            }

            return $companies;
        }

        return new ArrayObjectList;
    }

    public function setCompaniesWithHiddenDocuments (ArrayObjectList $companies) {
        $values = array();

        if (count($companies)) {
            foreach ($companies as $company) {
                $values[] = "({$this->getUID()}, {$company->getUID()})";
            }


            $SQL = "INSERT IGNORE INTO ". TABLE_PERFIL . "_empresa (uid_perfil, uid_empresa ) VALUES ". implode(',', $values);
            if (!$this->db->query($SQL)) return false;
        }


        $SQL = "DELETE FROM ". TABLE_PERFIL . "_empresa WHERE uid_perfil = {$this->getUID()}";
        if (count($companies)) {
            $SQL .= " AND uid_empresa NOT IN ({$companies->toComaList()})";
        }

        return $this->db->query($SQL);
    }

    public function setCompanyWithHiddenDocuments (empresa $company, $hide = true, usuario $usuario = NULL) {
        if ($hide) {
            $user   = ($usuario) ? $usuario->getUID() : 'NULL';
            $SQL    = "INSERT IGNORE INTO ". TABLE_PERFIL . "_empresa (uid_perfil, uid_empresa, uid_usuario) VALUES ({$this->getUID()}, {$company->getUID()}, $user)";
            return $this->db->query($SQL);
        } else {
            $SQL = "DELETE FROM ". TABLE_PERFIL . "_empresa WHERE uid_perfil = {$this->getUID()} AND uid_empresa = {$company->getUID()}";
            return $this->db->query($SQL);
        }
    }

    /** ES UN ALIAS DE obtenerCondicionDocumentos QUE ADAPTA LA SQL PARA HACERLA COMPATIBLE CON CIERTAS VISTAS **/
    public function obtenerCondicionDocumentosView($module){

        $cacheString = __CLASS__ . '-' . __FUNCTION__ . '-'.  $module .'-' . $this;
        if( null !== ($dato = $this->cache->getData($cacheString)) ){
            return $dato;
        }

        $condicion  = $this->obtenerCondicionDocumentos();
        $condicion  = self::transformSQLForView ($condicion, $module);


        $this->cache->addData( $cacheString, $condicion );
        return $condicion;
    }


    /***
       *
       *
       *
       *
       */
    public static function transformSQLForView ($SQL, $module) {
        $uidModulo  = util::getModuleId($module);
        $modules    = solicitable::getModules();
        $diff       = array_diff($modules, array($module));
        // Si vamos a comprobar solo un módulo debemos modificar la SQL para que omita los filtros
        // del WHERE que hacen referencia a los otros
        foreach($diff as $exclude){
            // Reemplazamos la SQL generada en perfil::obtenerCondicionDocumentos
            $idm = util::getModuleId($exclude);

            $SQL = preg_replace('!--\ start-'.$exclude.'.*?--\ end-'.$exclude.'!s', '', $SQL);
            //$condicion = str_replace("da.uid_modulo_destino = $idm AND uid_elemento_destino IN", "0 IN", $condicion);
            //$condicion = str_replace("da.uid_modulo_destino = $idm AND uid_elemento_destino", "0", $condicion);
        }

        // Actualizamos los nombres de los campos, y eliminamos columnas redundantes
        $SQL = str_replace("da.uid_modulo_destino = $uidModulo", "1", $SQL);
        $SQL = str_replace("uid_elemento_destino = ", "view.uid_$module = ", $SQL);
        $SQL = str_replace("uid_elemento_destino IN", "view.uid_{$module} IN", $SQL);
        $SQL = str_replace(" = uid_elemento_destino", " = view.uid_$module", $SQL);

        // Método muy chapucero, pero muy seguro. Evita que al dejar solo porciones de la SQL inicial queden varios OR consecutivos
        preg_match_all('!--\ start-'.$module.'.*?--\ end-'.$module.'!s', $SQL, $matches,PREG_SET_ORDER);

        foreach ($matches as $block) {
            $block = reset($block);
            $aux = array_map('trim', explode("\n", $block));
            if( $aux[1][0] === 'O' && $aux[1][1] === 'R' ) $aux[1] = substr($aux[1], 2);
            $SQL = str_replace($block, implode("\n", $aux), $SQL);
        }

        return $SQL;
    }

    public function obtenerCondicionDocumentos()
    {
        $cacheString = __CLASS__ . '-' . __FUNCTION__ . '-' . $this;

        if (null !== ($dato = $this->cache->getData($cacheString))) {
            return $dato;
        }

        $empresa = $this->getCompany();
        $usuario = $this->getUser();
        $staff = $usuario->esStaff();

        $isViewFilterByGroups = $this->isViewFilterByGroups();
        $isViewFilterByLabel = $this->isViewFilterByLabel();
        $sql = "";

        // ---- solo cuando el usuario es un partner
        if ($usuario->esValidador() && $empresa->isPartner()) {
            $companiesToValidate = $empresa->getValidationCompanies();
            $companiesToValidatelist = $companiesToValidate && count($companiesToValidate) ? $companiesToValidate->toComaList() : '0';
            $contractsCompanies = $companiesToValidate->foreachCall("getAllCompaniesIntList")->unique();

            if ($contractsCompanies && count($contractsCompanies)) {
                return " AND (
                    uid_documento_atributo IN (
                        SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
                        INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." USING (uid_documento_atributo)
                        WHERE uid_empresa_propietaria IN ({$companiesToValidatelist})
                        )  AND (
                            -- start-empresa
                            ( da.uid_modulo_destino = 1 AND uid_elemento_destino IN ({$contractsCompanies}))
                            -- end-empresa

                            -- start-empleado
                            OR ( da.uid_modulo_destino = 8 AND uid_elemento_destino IN (
                                SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa WHERE uid_empresa IN ({$contractsCompanies}) AND papelera = 0
                            ))
                            -- end-empleado

                            -- start-maquina
                            OR ( da.uid_modulo_destino = 14 AND uid_elemento_destino IN (
                                SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa WHERE uid_empresa IN ({$contractsCompanies}) AND papelera = 0
                            ))
                            -- end-maquina

                        )
                )";
            } else {
                return " AND 0";
            }
        }

        $userCompany = $usuario->getCompany();
        $isNotRequirementOfUserCompany = "uid_documento_atributo IN (
            SELECT uid_documento_atributo
            FROM agd_docs.documento_atributo req
            WHERE req.uid_empresa_propietaria != {$userCompany->getUID()}
        )";

        if ($isViewFilterByLabel) {
            $etiquetas = $this->obtenerEtiquetas();
            if (is_traversable($etiquetas) && count($etiquetas)) {
                $hasAnyUserTag = "uid_documento_atributo IN (
                    SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta WHERE uid_etiqueta IN ({$etiquetas->toComaList()})
                )";

                $sql .= " AND ({$isNotRequirementOfUserCompany} OR {$hasAnyUserTag})";
            } else {
                $hasNotAnyTag = "uid_documento_atributo NOT IN (
                    SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta WHERE uid_documento_atributo = uid_documento_atributo
                )";

                $sql .= " AND ({$isNotRequirementOfUserCompany} OR {$hasNotAnyTag})";
            }
        }

        if ($isViewFilterByGroups) {
            $groupBlackList = $usuario->groupBlackListForDocuments();

            $groupBlackList = 0 < count($groupBlackList) ? $groupBlackList->toComaList() : "0";

            $originIsNotInBlackGroupList = "(
                (   uid_modulo_origen = 11
                    AND uid_elemento_origen NOT IN ({$groupBlackList})
                ) OR uid_modulo_origen != 11
            )";

            $sql .= " AND ({$isNotRequirementOfUserCompany} OR {$originIsNotInBlackGroupList})";
        }

        if ($staff && $usuario->configValue("viewall")) {
            // en este caso no tenemos que aplicar filtro, modo soporte
        } elseif ($companySQL = $empresa->getRequestFilter(null, $this)) {
            $sql .= " AND {$companySQL}";
        }


        // si el usuario solo puede ver solicitudes del cliente actual
        $forceCurrentCompany = $this->configValue("limitecliente") || !$this->configValue("view");

        if ($forceCurrentCompany) {
            if ($empresa->esCorporacion()) {
                $sql .= " AND uid_empresa_propietaria IN ({$empresa->getStartIntList()->toComaList()})";
            } else {
                $sql .= " AND uid_empresa_propietaria = {$empresa->getUID()}";
            }
        }


        $this->cache->addData($cacheString, $sql);

        return $sql;
    }


    public function enviarPapelera($parent, usuario $usuario){
        return $this->bloquearPerfil($parent, $usuario);
    }

    public function restaurarPapelera($parent, usuario $usuario){
        return $this->desbloquearPerfil($parent, $usuario);
    }

    public function getUserVisibleName(){
        $user = $this->getUser();
        $name = $this->shortName();

        if ($this->configValue("limitecliente")) {
            $name .= " (local)";
        }

        if ($rol = $this->getActiveRol()) {
            $rolName = $rol->getUserVisibleName();

            if ($rolName != rol::ROL_DEFAULT) $name .= " ({$rolName})";
        }



        return $name;
    }

    public function getUserName(){
        return $this->getUser()->getUserName();
    }

    public function shortName(){
        if ($empresa = $this->getCompany()) {
            return $empresa->getUserVisibleName();
        }
        return false;
    }

    // Elimina activando otro perfil del usuario
    public function eliminar(Iusuario $usuario = NULL){
        $usuario = $this->getUser();

        $estat = parent::eliminar();

        $perfilesRestantes = $usuario->obtenerPerfiles();
        if( !count($perfilesRestantes) ){
            if( $usuario->eliminar() ){
                return $estat;
            } else {
                dump("Error al borrar el usuario, no tenia mas perfiles");
            }
        } else {
            $perfilesRestantes[0]->activate();
        }

        return $estat;
    }


    public function getCompany () {
        if ($uid = $this->obtenerDato('uid_empresa')) {
            return new empresa ($uid);
        }

        throw new Exception('No company for profile ' . $this->getUID());
    }


    public function getUser(){
        $informacionPerfil = $this->getInfo();
        $usuario = new usuario( $informacionPerfil["uid_usuario"] );

        return $usuario;
    }

    public function obtenerElementosActivables(usuario $usuario = NULL){
        return array( $this->getUser() );
    }

    public function obtenerElementosSuperiores(){
        return $this->getCompanies();
    }

    public function isDeactivable($parent, usuario $usuario){
        return true;
    }

    /** NOS RETORNA LOS PLUGINS DEL USUARIO EN UN ARRAY DE DATOS */
    public function obtenerPlugins(){
        function prepare($key){return "p.".$key;}
        $campos = array_map("prepare", plugin::getFields() );

        $sql = "
        SELECT p.uid_plugin
        FROM ". TABLE_PLUGINS ." p
        INNER JOIN ". $this->tabla ."_plugin pu
        USING( uid_plugin )
        INNER JOIN " . $this->tabla ." u
        USING( uid_$this->tipo )
        WHERE uid_$this->tipo = ".$this->getUID();

        //ejecutamos la query
        $datos = $this->db->query( $sql, true );


        $coleccionPlugins = array();
        foreach($datos as $dato){
            $coleccionPlugins[] = new plugin($dato["uid_plugin"]);
        }

        return $coleccionPlugins;
    }

    public function actualizarPlugins(){

        $tabla = $this->tabla ."_plugin";
        $campo = "uid_perfil";
        $currentUIDElemento = obtener_uid_seleccionado();

        $sql = "DELETE FROM $tabla WHERE $campo = ".$currentUIDElemento;
        if( $estado = $this->db->query( $sql ) ){
            if( !isset($_REQUEST["elementos-asignados"]) ){
                if( $estado ){ return true; } else { return $this->db->lastErrorString(); }
            }

            $idPlugins = array_map( "db::scape", $_REQUEST["elementos-asignados"]);
            $inserts = array();

            foreach( $idPlugins as $idPlugin ){
                $inserts[] = "( $currentUIDElemento, $idPlugin )";
            }
            $sql = "INSERT INTO $tabla ( $campo, uid_plugin ) VALUES ". implode(",", $inserts);

            $estado = $this->db->query( $sql );
            if( $estado ){ return true; } else { return $this->db->lastErrorString(); }
        } else {
            return $this->db->lastErrorString();
        }
    }

    public function esStaff(){
        return $this->getUser()->esStaff();
    }

    public function isViewFilterByGroups(){
        return (bool) $this->configValue("limiteagrupador");
    }

    public function isViewFilterByLabel(){
        return (bool) $this->configValue("limiteetiqueta");
    }

    /** PASAMOS OTRO PERFIL DIFERENTE COMO PARAMETRO, SI SUMAR ES TRUE SE AÑADIRAN LOS PERMISOS, SI NO, SE COPIARAN INTACTOS */
    public function actualizarConPerfil(perfil $perfil, $sumar=false){
        if( !$sumar ){
            $sql = "DELETE FROM ". $this->tabla ."_accion WHERE uid_perfil = ". $this->getUID();
            if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }
        }

        $sql = "INSERT IGNORE INTO ". $this->tabla ."_accion ( uid_perfil, uid_modulo_accion )
        SELECT ". $this->getUID() .", uid_modulo_accion FROM ". $this->tabla ."_accion WHERE uid_perfil = ". $perfil->getUID();
        if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }

        return true;
    }

    public function actualizarOpciones($arrayUIDOpciones){
        $sql = "DELETE FROM ". $this->tabla ."_accion WHERE uid_perfil = ". $this->getUID();
        if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }

        if( count($arrayUIDOpciones) ){
            $inserts = array();
            foreach($arrayUIDOpciones as $UIDOpcion){
                $inserts[] = "( ". $this->getUID() .", ". $UIDOpcion ." )";
            }

            $sql = "INSERT INTO ". $this->tabla ."_accion ( uid_perfil, uid_modulo_accion ) VALUES ". implode(", ",$inserts);
            if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }
        }

        return true;
    }

    /* Activar este perfil para su usuario */
    public function activate(){
        return $this->getUser()->cambiarPerfil( $this );
    }

    public function isActive() {
        return $this->obtenerDato('papelera') ? false : true;
    }


    /** RETORNA UN OBJETO ROL, SI ESTA MARCADO COMO PERSISTENTE */
    public function getActiveRol(){
        $uidrol = $this->obtenerDato("rol");
        if( $uidrol && is_numeric($uidrol) ){
            return new rol($uidrol);
        }
        return false;
    }

    public function unlinkRol(){
        $this->cache->deleteData("getinfo-{$this}-{$this->tabla}--");
        return $this->update(array("rol" => "0"), elemento::PUBLIFIELDS_MODE_CONFIG, NULL);
    }

    public function actualizarOpcionesExtra($context = false){
        $campos = $this->obtenerOpcionesExtra($context);

        $updates = array();
        foreach($campos as $campo => $valor ){

            $val = (isset($_REQUEST[$campo]) && trim($_REQUEST[$campo]) ) ? 1 : 0;
            $updates[] = $campo . " = $val";
            $this->cache->set("configvalue-". $this->getUID() ."-". str_replace("config_", "", $campo), $val);

            $sub = $this->obtenerOpcionesRelacionadas($campo);
            foreach($sub as $i => $subCampo){
                if( isset($_REQUEST[$subCampo["name"]]) && trim($_REQUEST[$subCampo["name"]]) ){
                    $val = ( $_REQUEST[$subCampo["name"]] == "on" ) ? 1 : db::scape($_REQUEST[$subCampo["name"]]);
                    $updates[] = $subCampo["name"] . " = $val";
                    $this->cache->set("configvalue-". $this->getUID() ."-". str_replace("config_", "",$subCampo["name"]), $val );
                } else {
                    $updates[] = $subCampo["name"] . " = 0";
                    $this->cache->set("configvalue-". $this->getUID() ."-". str_replace("config_", "",$subCampo["name"]), 0 );
                }
            }
        }

        $sql = "UPDATE $this->tabla SET ". implode(",", $updates) ." WHERE uid_perfil = $this->uid";
        return $this->db->query($sql);
    }

    /**
        LAS CARACTERISTICAS DE UN PERFIL
    */
    public function obtenerOpcionesExtra($context = false){
        $camposExtra = array();
        $informacionPerfil = $this->getInfo();
        foreach($informacionPerfil as $campo => $valor){
            if( strpos($campo,"config_") !== false ){
                $camposExtra[ $campo ] = $valor;
            }
        }

        switch ($context) {
            case 'perfil': case 'usuario':
                unset($camposExtra["config_view"]);
                break;

            default:
            break;
        }
        return $camposExtra;
    }

    /** SUB-OPCIONES DE UNA OPCION DE UN PERFIL - (por darle un nombre)**/
    public function obtenerOpcionesRelacionadas($campo){
        $info = $this->getInfo();
        $campoExploded = explode("_", $campo);
        $optName = end($campoExploded);

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

    public function comprobarAccesoOpcion($UIDOpciones, $extra=null){
        if( !is_array($UIDOpciones) ){ $UIDOpciones = array($UIDOpciones); }
        $datosOpciones = $this->obtenerOpcionesDisponibles($extra);
        $datosOpciones = array_map("array_limite_first", $datosOpciones);
        //dump($datosOpciones);
        $valid = true;
        foreach( $UIDOpciones as $UIDOpcion ){
            if( !in_array($UIDOpcion, $datosOpciones) ){
                return $valid = false;
            }
        }
        return $valid;
    }

    /** PASANDO EL NOMBRE DE UNA OPCION DE CONFIGURACION NOS DICE SI EL USUARIO LA TIENE ACTIVA (true) O NO (false) */
    public function configValue($value){
        $datos = $this->getInfo();
        if( isset($datos["config_$value"]) ){
            return ($datos["config_$value"]) ? true : false;
        }
        return null;
    }

    /**
     * Get all the raw action ids
     * @return array
     */
    public function getActions()
    {
        $profileActions     = TABLE_PERFIL ."_accion";
        $actions            = TABLE_MODULOS ."_accion";

        $sql = "SELECT uid_modulo_accion
        FROM {$profileActions}
        INNER JOIN {$actions}
        USING(uid_modulo_accion)
        WHERE uid_perfil = {$this->uid}
        AND activo = 1
        ORDER BY uid_modulo_accion ASC";

        $actions = $this->db->query($sql, "*", 0);

        return $actions;
    }

    /**
        Obtener las opciones disponibles para un usuario
            @param $extra = false - bool  - Puede ser true o false lo que hará que se filtre por este campo.
            @param $type = "Si queremos que se filtre por un tipo de opcion
            @param $exclude = Si queremos excluir una lista de acciones...
    **/
    public function obtenerOpcionesDisponibles($extra=false, $type=false, $exclude=false){
        $sql = "SELECT uid_modulo_accion as oid, uid_accion, uid_modulo, config, alias, icono, href, string
        FROM ". TABLE_ACCIONES ." INNER JOIN ". TABLE_MODULOS ."_accion USING( uid_accion )
        INNER JOIN ". $this->tabla ."_accion USING( uid_modulo_accion )
        WHERE ( uid_perfil = ". $this->getUID() . " AND activo = 1 )";

        if( $this->getUser()->esStaff() ){
            $sql = "SELECT uid_modulo_accion as oid, uid_accion, uid_modulo, config, alias, icono, href, string
            FROM ". TABLE_ACCIONES ." INNER JOIN ". TABLE_MODULOS ."_accion USING( uid_accion ) WHERE activo = 1 ";
        }

        if( is_array($exclude) ){
            $sql .= " AND uid_accion NOT IN (". implode(",", $exclude) .") ";
        }

        if( is_numeric($type) ){
            $sql .= " AND tipo = $type";
        }

        if( is_bool($extra) ){
            if( $extra ){
                $sql .= " AND uid_extra";
            } else {
                $sql .= " AND !uid_extra";
            }
        } elseif ( is_object($extra) ){
            // Si nuestro filto es un objeto rol de tipo especifico mostramos los grupos que tienen esta caracteristica disponible, no todos
            if( $extra instanceof rol && $extra->getRolType() == rol::TIPO_ESPECIFICO ){
                $list = elemento::getAllModules("specific_permissions=1", true);
                $sql .= " AND uid_modulo IN (". implode(", ", $list) .") AND tipo AND !config";
            }
        }

        $datos = array();
        $info = $this->db->query( $sql, true );
        foreach( $info as $key => $val ){
            $datos[ $val["oid"] ] = $val;
        }
        //dump($datos);
        return $datos;
    }


    public function guardarOpcionesObjeto($objeto, $acciones){
        $sql = "DELETE FROM ". TABLE_PERFIL ."_accion_elemento
                WHERE uid_perfil = ". $this->getUID() ."
                AND uid_modulo = ". $objeto->getModuleId() ."
                AND uid_elemento = ". $objeto->getUID() ."
        ";
        if( !$this->db->query($sql) ){
            return $this->db->lastError();
        }


        $sql = "INSERT INTO ". TABLE_PERFIL ."_accion_elemento ( uid_perfil, uid_accion, uid_elemento, uid_modulo ) VALUES ";
        foreach( $acciones as $accion ){
            $inserts[] = "( ".$this->getUID().", $accion, ". $objeto->getUID() .", ". $objeto->getModuleId() ." )";
        }

        if( !count($inserts) ){
            return true;
        }

        $sql .= implode(", ", $inserts);

        if( !$this->db->query($sql) ){
            return $this->db->lastError();
        }
        return true;
    }

    public function obtenerOpcionesObjeto($param, $active=false, $parent=false){
        if( is_object($param) && !is_callable(array($param,"getModuleId")) ){ return false; } // un objeto que no nos vale

        $uidModulo = ( is_object($param) ) ? $param->getModuleId() : $param;
        $nombreModulo = util::getModuleName($uidModulo);
        $gruposOpciones = $opciones = $list = array();


        $ref = ( !is_object($param) && is_object($parent) ) ? $parent : $param;

        if( is_object($ref) && $active ){
            $sql = "SELECT uid_accion FROM ". TABLE_PERFIL ."_accion_elemento
                WHERE uid_perfil = ". $this->getUID() ." AND uid_modulo = ". $ref->getModuleId() ."  AND uid_elemento = ". $ref->getUID() ."
            ";
            $list = $this->db->query($sql, "*", 0);
        }

        $type = ( is_object($param) ) ? 1 : false;
        $opcionesUsuario = $this->obtenerOpcionesDisponibles(false, $type, array(21));
        foreach( $opcionesUsuario as $uid => $opcion ){
            if( $uidModulo == $opcion["uid_modulo"] && ( !$active || in_array($uid, $list) ) ){
                $opciones[$uid] = $opcion;
            }
        }



        $gruposOpciones[$nombreModulo] = $opciones;



        if( is_object($param) ){
            $relatedFunction = array( $param->getType(), "getSubModules" );
            if( is_callable($relatedFunction) ){
                if( $modules = call_user_func($relatedFunction) ){
                    foreach($modules as $module){
                        $nombremodulo = util::getModuleName($module);
                        $gruposOpciones[ $nombremodulo ] = $this->obtenerOpcionesObjeto($module, $active, $param);
                    }
                }
            }
        } else {
            return $opciones;
        }


        return $gruposOpciones;
    }

    public function obtenerOpcionesDisponiblesPorGrupos($extra=false){
        $arrayOpciones = array();
        $opcionesUsuario = $this->obtenerOpcionesDisponibles($extra);
        foreach( $opcionesUsuario as $opcionUsuario ){
            $configMode = ( $opcionUsuario["config"] ) ? "configuracion" : "normal";
            if( !isset($arrayOpciones[ $configMode ]) ){ $arrayOpciones[ $configMode ] = array(); }
            if( !isset($arrayOpciones[ $configMode ][ $opcionUsuario["uid_modulo"] ]) ){ $arrayOpciones[ $configMode ][ $opcionUsuario["uid_modulo"] ] = array(); }
            $arrayOpciones[ $configMode ][ $opcionUsuario["uid_modulo"] ][] = $opcionUsuario;
        }
        return $arrayOpciones;
    }

    /*ENVIAR UN PERFIL A LA PAPELERA*/
    function bloquearPerfil( $param = false ){
        if( $param instanceof empresa ){ $param = $this->getUID(); }
        return $this->actualizarEstadoPerfil($param,1);
    }

    function desbloquearPerfil( $param = false ){
        if( $param instanceof empresa ){ $param = $this->getUID(); }
        return $this->actualizarEstadoPerfil($param,0);
    }

    protected function actualizarEstadoPerfil($idPerfilAlternativo = false, $estado = 1){
        $idPerfil = ( is_numeric($idPerfilAlternativo) ) ? $idPerfilAlternativo : $this->getUID();
        $sql = "UPDATE ". TABLE_PERFIL ." SET papelera = $estado WHERE uid_perfil = $idPerfil;";
        if( $this->db->query($sql) ){
            return true;
        } else {
            return $this->db->lastErrorString();
        }
    }


    /*Implementamos intefaz de usuario*/
    public function esValidador(){
        $usuario = $this->getUser();
        return $usuario->esValidador();
    }

    public function getEmail(){
        $usuario = $this->getUser();
        return $usuario->getEmail();
    }

    public static function login($usuario, $password = false) {
        $usuario = $this->getUser();
        return $usuario->login($usuario, $password);
    }

    public function sendRestoreEmail() {
        $usuario = $this->getUser();
        return $usuario->sendRestoreEmail();
    }

    public function necesitaCambiarPassword(){
        $usuario = $this->getUser();
        return $usuario->necesitaCambiarPassword();
    }

    public function cambiarPassword($password, $marcarParaRestaurar=false){
        $usuario = $this->getUser();
        return $usuario->cambiarPassword($password, $marcarParaRestaurar);
    }

    public function checkFirstLogin(){
        $usuario = $this->getUser();
        return $usuario->checkFirstLogin();
    }

    public function getHumanName(){
        $usuario = $this->getUser();
        return $usuario->getHumanName();
    }

    public function getImage(){
        $usuario = $this->getUser();
        return $usuario->getImage();
    }

    public function obtenerElementosMenu(){
        $usuario = $this->getUser();
        return $usuario->obtenerElementosMenu();
    }



    public function accesoElemento(Ielemento $elemento, empresa $empresa = NULL, $papelera = false, $bucle = 0){

        $user           = $this->getUser();
        $class          = get_class($elemento);
        $profileCompany = $this->getCompany();
        $return         = false;


        switch ($class) {
            case 'empleado': case 'maquina':

                $uid = (int) $elemento->getUID();

                $this->app['index.repository']->expireIndexOf(
                    $class,
                    $profileCompany->asDomainEntity(),
                    $user->asDomainEntity(),
                    true
                );

                $indexList = (string) $this->app['index.repository']->getIndexOf(
                    $class,
                    $profileCompany->asDomainEntity(),
                    $user->asDomainEntity(),
                    true
                );

                $indexCollection = explode(',', $indexList);

                if (true === in_array($uid, $indexCollection)) {
                    $return = true;
                }


                $isViewFiltered = $this->isViewFilterByGroups();

                // If the user is not filtered
                if ($isViewFiltered === false) {

                    $accesible = false;
                    $companies = $elemento->getCompanies();
                    foreach ($companies as $company) {

                        if ($this->accesoElemento($company)) {
                            $accesible = true;
                            break;
                        }
                    }

                    // if we cant acces to any of the item companies
                    if ($accesible === false) $return = false;
                }


                # code...
                break;


            case 'empresa':
                if ($profileCompany->compareTo($elemento)) {
                    $return = true;
                } else {

                    // Buscamos todas las empresas visibles por este usuario
                    $list = buscador::getCompaniesIntList($this, $papelera);
                    if ($papelera === NULL) $list = $list->merge(buscador::getCompaniesIntList($this, true));

                    if ($list->contains($elemento->getUID())) {
                        $return = true;
                    }
                }
                break;


            default:
                $return = $user->accesoElemento($elemento, $empresa, $papelera, $bucle);
                break;
        }

        return $return;
    }

    public function esAdministrador(){
        $usuario = $this->getUser();
        return $usuario->esAdministrador();
    }

    public function esSATI(){
        $usuario = $this->getUser();
        return $usuario->esSATI();
    }

    public function accesoModulo($idModulo, $config = NULL){
        $usuario = $this->getUser();
        return $usuario->accesoModulo($idModulo, $config);
    }

    public function accesoModificarElemento(Ielemento $elemento, $config=0){
        $usuario = $this->getUser();
        return $usuario->accesoModificarElemento($elemento, $config);
    }

    public function getOptionsMultipleFor($modulo, $config=0, Ielemento $parent = NULL){
        $usuario = $this->getUser();
        return $usuario->getOptionsMultipleFor();
    }

    public function getOptionsFastFor($modulo, $config=0, Ielemento $parent = NULL){
        $usuario = $this->getUser();
        return $usuario->getOptionsFastFor($modulo, $config,$parent);
    }

    public function getAvailableOptionsForModule($idModulo, $idAccion = false, $config = NULL, $referencia = NULL, $parent = NULL, $type = NULL){
        $usuario = $this->getUser();
        return $usuario->getAvailableOptionsForModule($idModulo, $idAccion, $config, $referencia, $parent, $type);
    }

    public function accesoAccionConcreta($idModulo, $accion, $config = NULL, $ref = NULL){
        $usuario = $this->getUser();
        return $usuario->accesoAccionConcreta($idModulo, $accion, $config, $ref);;
    }

    public function opcionesDesplegable(){
        $usuario = $this->getUser();
        return $usuario->opcionesDesplegable();
    }

    public function buscarPerfilAcceso(Ielemento $objeto){
        $usuario = $this->getUser();
        return $usuario->buscarPerfilAcceso($objeto);
    }


    public function getUnreadAlerts(){
        $usuario = $this->getUser();
        return $usuario->getUnreadAlerts();
    }


    public function obtenerBusquedas($filter = false){
        $usuario = $this->getUser();
        return $usuario->obtenerBusquedas();
    }

    public function touch(){
        $usuario = $this->getUser();
        return $usuario->touch();
    }


    public function getLastPage(){
        $usuario = $this->getUser();
        return $usuario->getLastPage();
    }

    public function verEstadoConexion(){
        $usuario = $this->getUser();
        return $usuario->verEstadoConexion();
    }

    public function getEmpresaSolicitudPendientes($type = false, $status = solicitud::ESTADO_CREADA){
        $usuario = $this->getUser();
        return $usuario->getEmpresaSolicitudPendientes($type, $status);
    }

    public function maxUploadSize($size=false, $reset=false){
        $usuario = $this->getUser();
        return $usuario->maxUploadSize($size, $reset);
    }

    public function obtenerPerfil(){
        $usuario = $this->getUser();
        return $usuario->obtenerPerfil();
    }

    public static function instanceFromCookieToken ($username, $cookiepass){
        $usuario = $this->getUser();
        return $usuario->instanceFromCookieToken($username, $cookiepass);
    }

    public function getCookieToken(){
        $usuario = $this->getUser();
        return $usuario->verEstadoConexion();
    }


    public function canView($item, $context, $extraData){
        $usuario = $this->getUser();
        return $usuario->canView($item, $context, $extraData);
    }

    public function getHelpers($href = false){
        $usuario = $this->getUser();
        return $usuario->getHelpers($href);
    }

    public function setTimezoneOffset ($offset) {
        $user = $this->getUser();
        return $user->setTimezoneOffset($offset);
    }

    public function getTimezoneOffset () {
        $user = $this->getUser();
        return $user->getTimezoneOffset();
    }

    public function watchingThread($element, $requirements){
        $user = $this->getUser();
        return $user->watchingThread($element, $requirements);
    }

    public function unWatchThread($element, $requirements){
        $user = $this->getUser();
        return $user->unWatchThread($element, $requirements);
    }

    public function wacthThread($element, $requirements){
        $user = $this->getUser();
        return $user->wacthThread($element, $requirements);
    }

    public function canModifyVisibilityOfUsers () {
        $user = $this->getUser();
        if (!$user instanceof usuario) return false;
        $sql = "SELECT count(uid_perfil_empresa) FROM ". TABLE_PERFIL . "_empresa WHERE
        uid_perfil = {$this->getUID()}
        AND uid_usuario != {$user->getUID()}";

        $count = $this->db->query($sql, 0, 0);
        return  ($count) ? true : false;
    }

    public function getUserLimiter(empresa $company) {

        $sql = "SELECT uid_usuario FROM ". TABLE_PERFIL . "_empresa WHERE
        uid_perfil = {$this->getUID()}
        AND uid_empresa = {$company->getUID()}
        LIMIT 1";

        if ($resultId = $this->db->query($sql, 0, 0)) {
            if (is_numeric($resultId)) return new usuario($resultId);
        }

        return  false;
    }

    public function hideAllDocumentsBut (empresa $company)
    {
        $ownCompany = $this->getCompany();
        $clients    = $ownCompany->obtenerEmpresasClienteConDocumentos();
        $clients[]  = $ownCompany;

        $hidden     = new ArrayObjectList;
        foreach ($clients as $client) {
            if (false === $client->compareTo($company)) {
                $hidden[] = $client;
            }
        }

        if (count($hidden) === 0) {
            return true;
        }

        return $this->setCompaniesWithHiddenDocuments($hidden);
    }

    public function setVisibilityForAllCompanies($onlyForUser = false)
    {
        $companyProfileTable = TABLE_PERFIL . "_empresa";
        $sql = "DELETE FROM {$companyProfileTable} WHERE uid_perfil = {$this->getUID()}";
        if (true === $onlyForUser) {
            $sql .= " AND uid_usuario IS NULL";
        }

        return $this->db->query($sql);
    }

    /**
    * {@inheritDoc}
    */
    public function isActiveWatcher()
    {
        return $this->isActive();
    }

    /** ALIAS PARA SHOW COLUMNS DE ESTA TABLA **/
    public function getTableFields()
    {
        return array(
            array("Field" => "uid_perfil",             "Type" => "int(10)",        "Null" => "NO", "Key" => "PRI", "Default" => "",    "Extra" => "auto_increment"),
            array("Field" => "uid_empresa",            "Type" => "int(10)",        "Null" => "NO", "Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "uid_corporation",        "Type" => "int(10)",        "Null" => "YES","Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "uid_manager",            "Type" => "int(10)",        "Null" => "YES","Key" => "MUL", "Default" => "",    "Extra" => ""),
            array("Field" => "uid_usuario",            "Type" => "int(10)",        "Null" => "NO", "Key" => "MUL", "Default" => "",    "Extra" => ""),
            array("Field" => "lopd",                   "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "alias",                  "Type" => "varchar(50)",    "Null" => "NO", "Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "rol",                    "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "papelera",               "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "config_limiteetiqueta",  "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "config_limiteagrupador", "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "limiteagrupador_modo",   "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "config_limitecliente",   "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "config_view",            "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "1",   "Extra" => ""),
            array("Field" => "config_asig_empleados",  "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "config_asig_maquinas",   "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "date_summary",           "Type" => "datetime",       "Null" => "YES","Key" => "",    "Default" => "",    "Extra" => "")
        );
    }

    public function removeParent(elemento $parent, usuario $usuario = null) {
        return false;
    }

    /** DATOS VISIBLES DE LAS EMPRESAS */
    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false) {
        $arrayCampos = new FieldList();

        switch( $modo ){
            case elemento::PUBLIFIELDS_MODE_NEW:
                $arrayCampos["uid_empresa"]     = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                $arrayCampos["uid_corporation"] = new FormField(array("tag" => "input", "type" => "text"));
                $arrayCampos["uid_usuario"]     = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                $arrayCampos["alias"]           = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
            break;
            case elemento::PUBLIFIELDS_MODE_CONFIG:
                $arrayCampos["rol"]         = new FormField(array("tag" => "input", "type" => "text"));
            break;
            case elemento::PUBLIFIELDS_MODE_EDIT:
                $arrayCampos["alias"]       = new FormField(array("tag" => "input", "size" => 50, "type" => "text", "blank" => false));
                //$arrayCampos["config_limiteetiqueta"]     = new FormField(array("tag" => "input", "type" => "checkbox" ));
                //$arrayCampos["config_limiteagrupador"]    = new FormField(array("tag" => "input", "type" => "checkbox" ));
            break;
            case elemento::PUBLIFIELDS_MODE_ATTR:
                if ($usuario instanceof usuario && $usuario->esStaff()) {
                    $arrayCampos["uid_empresa"]     = new FormField();
                    $arrayCampos["uid_corporation"] = new FormField();
                }
            break;
        }

        return $arrayCampos;
    }
}
