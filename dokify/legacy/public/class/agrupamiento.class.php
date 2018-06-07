<?php

use Dokify\Events\OrganizationEvents;
use Dokify\Application\Event\Organization\Category\Works\Setted as EventCategoryWorksSetted;

class agrupamiento extends etiquetable implements Iparent, Ielemento
{
    const DEFAULT_ICON = "/img/categorias/agrupamiento.png";

    public function __construct( $param, $extra = false ){
        $this->db = db::singleton();
        $this->tabla = TABLE_AGRUPAMIENTO;
        $this->instance( $param, $extra );
    }

    public static function getRouteName () {
        return 'organization';
    }

    /**
    * A temporary method to convert a legacy class in a repo/entity class
    * @return Organization\Organization
    */
    public function asDomainEntity()
    {
        return $this->app['organization.repository']->factory($this->getInfo());
    }

    /**
     * check if this organization can be handleed by a company or a companies list
     * @param  [empresa|ArrayObjectList] $companies company or companies to check
     * @return [bool] if belongs to the @companies param or not
     */
    public function isHandledBy ($companies)
    {
        if ($companies instanceof empresa) {
            $companies = new ArrayObjectList([$companies]);
        }

        $organizations = $companies->foreachCall("obtenerAgrupamientosVisibles")->unique();
        if (count($organizations) && $organizations->contains($this)) {
            return true;
        }

        return false;
    }

    public function eliminar(Iusuario $usuario = NULL){
        if (count($this->obtenerAgrupamientosAsignados())){
            return "agrupamiento_relacion_error";
        }
        return parent::eliminar($usuario);
    }

    /***
       *
       *
       *
       *
       */
    public function getCorporationCompanies () {
        $table  = TABLE_EMPRESA . '_agrupamiento';
        $SQL    = "SELECT uid_empresa FROM {$table} WHERE uid_agrupamiento = {$this->getUID()}";

        if ($companies = $this->db->query($SQL, '*', 0, 'empresa')) {
            return new ArrayObjectList($companies);
        }

        return new ArrayObjectList;
    }

    public function canApplyHierarchy (usuario $user, categorizable $item) {
        if ($this->tieneJerarquia() == false) {
            return false;
        }

        $orgCompany     = $this->getCompany();
        $userCompany    = $user->getCompany();
        $isCorp         = $userCompany->esCorporacion();

        if ($item instanceof empresa || $item instanceof signinRequest || $item instanceof agrupador) {

            // in case we are checking an invitation, use the source company to do the calcs
            if ($item instanceof signinRequest || $item instanceof agrupador) {
                $item = $item->getCompany();
            }

            $origin     = $item->getOriginCompanies();
            $startList  = $item->getStartList();
            $myCompany  = $origin && $origin->contains($orgCompany) || $startList && $startList->contains($orgCompany);

            $origin     = $userCompany->getOriginCompanies();
            $startList  = $userCompany->getStartList();
            $myGroup    = $origin && $origin->contains($orgCompany) || $startList && $startList->contains($orgCompany);

            if ($isCorp || $myCompany || $myGroup) {
                return false;
            }
        }

        $isContact      = $this instanceof contactoempresa;
        $isUser         = $this instanceof usuario;
        $isSelfCorp     = $isCorp && $userCompany->getOriginCompanies()->contains($orgCompany);

        // For contacts and users we do not apply hierachy if the user is in the corp
        if ($isUser && $isContact && $isSelfCorp ) {
            return false;
        }

        return true;

    }


    public function getViewData (Iusuario $user = NULL) {
        $viewData = parent::getViewData($user);

        $viewData['ok'] = false;

        return $viewData;
    }

    public function onDemand(){
        return $this->configValue("al_vuelo");
    }


    /**
      * Devuelve un objeto categoria si lo hay o false si no lo hay
      */
    public function obtenerCategoria(){
        $uid = $this->obtenerDato("uid_categoria");
        if( $uid ){
            return new categoria($uid);
        }
        return false;
    }

    public function isOnDemand (){
        return (bool) $this->obtenerDato("config_al_vuelo");
    }


    public function obtenerElementosPapelera(usuario $usuario, $type){
        return $this->obtenerAgrupadores($usuario, false, array(), false, false /*array($paginacion["sql_limit_start"],$paginacion["sql_limit_end"])*/, false, true );
    }


    public function accesiblePara( $usuarioActivo ){
        $todosAgrupamientos = $usuarioActivo->getCompany()->obtenerAgrupamientosVisibles();
        return $todosAgrupamientos->contains($this);
        /*
        $arrayUIDSAgrupamientosVisibles = array();
        if( is_traversable($todosAgrupamientos) && count($todosAgrupamientos) ){
            $arrayUIDSAgrupamientosVisibles = elemento::getCollectionIds($todosAgrupamientos);
        }

        return (bool) in_array($this->getUID(), $arrayUIDSAgrupamientosVisibles);
        */
    }

    public function obtenerDocumentoAtributos($activo = false){
        $sql = "SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_modulo_origen = ". elemento::obtenerIdModulo("agrupamiento") ." AND uid_elemento_origen = $this->uid";
        if ($activo) $sql .= " AND activo = 1";
        $coleccion = $this->db->query( $sql, "*", 0, "documento_atributo" );
        return $coleccion;
    }

    public function getTypeString(){
        return "Agrupamiento";
    }

    public static function getSearchData(Iusuario $usuario, $papelera = false, $all = false){
        $searchData = array();
        if (!$usuario->accesoModulo(__CLASS__)) return false;
        $filters    = array();
        $filter     = "";

        if ($all != true) {
            $empresa = $usuario->getCompany();
            $filters[] = " uid_empresa = {$empresa->getUID()} ";
        }

        $filters[] = "ca.uid_agrupamiento = agrupamiento.uid_agrupamiento";

        if (count($filters)) $filter = implode(" AND ", $filters);

        $searchData[ TABLE_AGRUPAMIENTO ] = array(
            "type" => "agrupamiento",
            "fields" => array("nombre"),
            "limit" => "uid_agrupamiento IN (
                    SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO. " ca
                    WHERE {$filter}
            )",
            "accept" => array(
                "tipo" => "agrupamiento",
                "uid" => true,
                "list" => true
            )
        );

        return $searchData;
    }

    public function getAssignOptionsFor($elemento, $usuario){
        $defaultURL = $_SERVER["PHP_SELF"] ."?m={$elemento->getModuleName()}&poid={$elemento->getUID()}&oid={$this->getUID()}";
        $options = array();

        if( $elemento instanceof agrupador ){
            $options[] = array(
                "tagName" => "input",
                "type" => "checkbox",
                "className" => "post",
                "text" => "No asignar estos elementos de rebote",
                "href" => $defaultURL."&action=rebote",
                "checked" => $elemento->configuracionAgrupamiento($this,"rebote")
            );
        }

        if( $elemento instanceof empresa ){
            $options[] = array(
                "tagName" => "input",
                "type" => "checkbox",
                "className" => "post",
                "text" => "Bloquear todas las asignaciones",
                "href" => $defaultURL."&action=lock",
                "checked" => $elemento->isAllLocked($this)
                //"checked" => $elemento->configuracionAgrupamiento($this,"rebote")
            );
        }

        return $options;
    }

    public function obtenerAgrupamientosAsignados(){
        $sql = "SELECT uid_agrupamiento_inferior
                FROM ". TABLE_AGRUPAMIENTO ."_agrupamiento
                WHERE uid_agrupamiento_superior = ". $this->getUID() ."
        ";
        $uids = $this->db->query($sql, "*", 0);

        $agrupamientos = array();
        foreach($uids as $uid ){
            $agrupamientos[] = new agrupamiento($uid);
        }

        return $agrupamientos;
    }


    public function asignadoPara(agrupamiento $agrupamiento){
        $sql = "SELECT uid_agrupamiento_agrupamiento
                FROM ". TABLE_AGRUPAMIENTO ."_agrupamiento
                WHERE uid_agrupamiento_superior = ". $agrupamiento->getUID() ."
                AND uid_agrupamiento_inferior = ". $this->getUID() ."
        ";
        $uid = $this->db->query($sql, 0, 0);
        return ( $uid ) ? true : false;
    }

    public function asignarAgrupamiento(agrupamiento $agrupamiento){
        $sql = "INSERT INTO ". TABLE_AGRUPAMIENTO ."_agrupamiento ( uid_agrupamiento_superior, uid_agrupamiento_inferior )
        VALUES ( $this->uid, ". $agrupamiento->getUID() .")";

        return $this->db->query($sql);
    }

    public function quitarAgrupamiento(agrupamiento $agrupamiento){
        $sql = "DELETE FROM ". TABLE_AGRUPAMIENTO ."_agrupamiento
                WHERE uid_agrupamiento_superior = ". $this->getUID() ."
                AND uid_agrupamiento_inferior = ". $agrupamiento->getUID() ;

        return $this->db->query($sql);
    }

    /**
        Retorna una coleccion de objetos clientes asociados a este agrupador
    */
    public function getEmpresasClientes(){
        // Objeto donde guardaremos todo
        $coleccionClientes = array();
        $sql = "SELECT uid_empresa FROM ". TABLE_AGRUPAMIENTO ." ta INNER JOIN ". TABLE_EMPRESA." using(uid_empresa)
                 WHERE ta.uid_agrupamiento = {$this->getUID()}";

        $data = $this->db->query($sql, "*", 0, 'empresa');

        return new ArrayObjectList($coleccionClientes);
    }

    /** Aunque un agrupamiento puede tener varios clientes, esta funcion devuelve el primario **/
    public function getCompanies(){
        return reset($this->getEmpresasClientes());
    }

    /** Nos devuelve el objeto empresa propietaria de este agrupamiento **/
    public function getCompany(){
        if( $uid = $this->obtenerDato("uid_empresa") ){
            return new empresa($uid);
        }
        return false;
    }

    public function obtenerModulosDisponibles(){
        $asignados  = $this->obtenerModulos();
        $todos      = self::getModulesCategorizables();
        $category   = $this->obtenerCategoria();

        if ($category && in_array($category->getUID(), categoria::$automaticAssignOrganization) && $key = array_search("signinRequest", $todos)) {
            unset($todos[$key]); //removing invitation posibility from those groups categorized as TIPO_EMPRESA
        }

        return array_diff($todos, $asignados);
    }

    public function desasignarModulo($idModulo){
        $idModulo = db::scape($idModulo);
        $modulo = util::getModuleName($idModulo);
        $todos = self::getModulesCategorizables();
        if (!in_array($modulo, $todos)) return "modulo_no_existe";

        $sql = "DELETE FROM ".$this->tabla."_modulo WHERE uid_modulo = $idModulo AND uid_". $this->nombre_tabla." = $this->uid";

        if( !$this->db->query($sql) ){
            return $this->db->lastErrorString();
        } else {
            $this->cache->delete(__CLASS__.'-obtenerModulos-'.$this);
            return true;
        }
    }

    public function asignarModulo($idModulo){
        $idModulo = db::scape($idModulo);
        $modulo = util::getModuleName($idModulo);
        $todos = self::getModulesCategorizables();
        if (!in_array($modulo, $todos)) return "modulo_no_existe";

        $sql = "INSERT INTO ".$this->tabla."_modulo ( uid_".$this->nombre_tabla.", uid_modulo ) VALUES (
            $this->uid, $idModulo
        )";

        if( !$this->db->query($sql) ){
            return $this->db->lastErrorString();
        } else {
            $this->cache->delete(__CLASS__.'-obtenerModulos-'.$this);
            return true;
        }
    }

    public function obtenerModulos(){
        $cacheKey = __CLASS__.'-'.__FUNCTION__.'-'.$this;
        if (($value = $this->cache->getData($cacheKey)) !== NULL) return explode(",",   $value);

        $SQL = "SELECT uid_modulo FROM ".$this->tabla."_modulo WHERE uid_". $this->nombre_tabla ." = ". $this->getUID();
        $uids = $this->db->query($SQL, "*", 0);
        $allModulos = self::getModulesCategorizables();

        $names = array();
        foreach($uids as $uid) {
            $modulo = util::getModuleName($uid);
            if (in_array($modulo, $allModulos)){
                $names[] = $modulo;
            }
        }

        $this->cache->addData($cacheKey, implode(",",$names));
        return $names;
    }

    public function obtenerNumeroAgrupadores($usuario, $condicion = false){
        return $this->obtenerAgrupadores($usuario, $condicion, array(), true);
    }

    /**
     * Get the organization groups
     * @param  [type]  $usuario
     * @param  boolean $condicion
     * @param  array   $fields
     * @param  boolean $number
     * @param  boolean $limit
     * @param  boolean $order
     * @param  boolean $papelera
     * @param  boolean $applyGroupFilter If true only filter groups with their organization have active config_filter
     * @return ArrayAgrupadorList
     */
    public function obtenerAgrupadores(
        $usuario = null,
        $condicion = false,
        $fields = array(),
        $number = false,
        $limit = false,
        $order = false,
        $papelera = false,
        $applyGroupFilter = false
    ) {
        $arrayObjetos = new ArrayAgrupadorList();
        $fields[] = "uid_agrupador";
        if ($number) {
            $fields = array(" count(uid_agrupador) ");
        }


        $sql = "
            SELECT ". implode(",", $fields) ."
            FROM ". TABLE_AGRUPADOR ." a
            INNER JOIN " . TABLE_AGRUPAMIENTO . "
            USING (uid_agrupamiento)
            WHERE uid_agrupamiento = {$this->uid}
        ";

        // Si hay una condicion explicita y no se debe filtrar por usuario
        //if( $condicion && !$usuario instanceof usuario){
        //  $sql .= " AND $condicion";
        //} else{

        if ($usuario instanceof Iusuario) {
            // Filtro por agrupadores
            if ($usuario->isViewFilterByGroups()) {
                $userCompany = $usuario->getCompany();
                $groups = $usuario->obtenerAgrupadores();
                $list = count($groups) ? $groups->toComaList() : '0';

                if ($corp = $userCompany->perteneceCorporacion()) {
                    $originCompanies = [$userCompany->getUID(), $corp->getUID()];
                    $companyList = implode(',', $originCompanies);
                } else {
                    $companyList = $userCompany->getStartIntList()->toComaList();
                }

                if (true === $applyGroupFilter) {
                    $sql .= " AND (
                        (
                            (uid_agrupador IN ($list)
                            AND a.uid_empresa IN ($companyList)
                            AND config_filter = 1
                            )
                            OR
                            (a.uid_empresa IN ({$companyList})
                            AND config_filter = 0)
                            )
                            OR (
                            a.uid_empresa NOT IN ($companyList)
                        )
                    )";
                } else {
                    $sql .= " AND (
                        (uid_agrupador IN ($list)
                        AND a.uid_empresa IN ($companyList)
                        )
                        OR (
                        a.uid_empresa NOT IN ($companyList)
                        )
                    )";
                }
            }

            // Filtro por etiquetas
            if ($usuario->isViewFilterByLabel()) {
                $etiquetasUsuario = $usuario->obtenerEtiquetas()->toIntList()->getArrayCopy();
                $groupTagsTable = TABLE_AGRUPADOR . "_etiqueta";
                $userCompany = $usuario->getCompany();
                $baseUserFilter = "uid_agrupamiento IN (
                    SELECT uid_agrupamiento
                    FROM agd_data.agrupamiento org
                    WHERE org.uid_empresa != {$userCompany->getUID()}
                    OR (
                        org.uid_empresa = {$userCompany->getUID()}
                        AND org.config_filter = 0
                    )
                )";

                if (is_traversable($etiquetasUsuario) && count($etiquetasUsuario)) {
                    $comaList = implode(",", $etiquetasUsuario);
                    $sql .= " AND ({$baseUserFilter} OR uid_agrupador IN (
                        SELECT uid_agrupador
                        FROM {$groupTagsTable}
                        WHERE uid_etiqueta IN ({$comaList})
                    ))";
                } else {
                    $sql .= " AND ({$baseUserFilter} OR uid_agrupador NOT IN (
                        SELECT uid_agrupador
                        FROM {$groupTagsTable}
                        WHERE uid_agrupador = a.uid_agrupador
                    ))";
                }
            }

            if ($this->tieneJerarquia() /*&& !$usuario->isEnterprise()*/) {
                $empresaUsuario = $usuario->getCompany();
                $agrupadores = new ArrayAgrupadorList;
                if ($empresaUsuario->esCorporacion()) {
                    $empresasCorporacion = $empresaUsuario->obtenerEmpresasInferiores();
                    foreach ($empresasCorporacion as $empresaCorporacion) {
                        $agrupadores = $empresaCorporacion->obtenerAgrupadores()->merge($agrupadores);
                    }
                } else {
                    $agrupadores = $empresaUsuario->obtenerAgrupadores(null, false, $this);
                }


                $relations = $agrupadores->obtenerAgrupadoresRelacionados($empresaUsuario);
                $agrupadores = $agrupadores->merge($relations);

                $list = count($agrupadores) ? $agrupadores->toComaList() : "0";

                if ($corp = $empresaUsuario->perteneceCorporacion()) {
                    $originCompanies = [$empresaUsuario->getUID(), $corp->getUID()];
                    $companyList = implode(',', $originCompanies);
                } else {
                    $companyList = $empresaUsuario->getStartIntList()->toComaList();
                }

                $sql .= " AND (
                    uid_agrupador IN ({$list})
                    OR a.uid_empresa IN ({$companyList})
                    OR uid_agrupamiento IN
                        (SELECT uid_agrupamiento
                        FROM " . TABLE_AGRUPAMIENTO . " agrupamiento
                        WHERE agrupamiento.uid_agrupamiento = a.uid_agrupamiento
                        AND agrupamiento.self_assignable = 1)
                    )";
            }
        }

        // Si hay una condicion explicita
        if ($condicion) {
            if (false === strpos($condicion, "=")) {
                $search = db::scape(mb_convert_encoding($condicion, 'ISO-8859-1'));

                $sql .= " AND a.nombre LIKE '%{$search}%'";
            } else {
                $sql .= " AND $condicion";
            }
        }

        if (is_bool($papelera)) {
            $sql .= " AND a.papelera = ". (int) $papelera;
        }


        $order = ( $order ) ? "a." . $order : "a.nombre";

        if ($categoria = $this->obtenerCategoria()) {
            if ($categoria->getUID() == categoria::TYPE_TAREAS) {
                $order = "( SELECT if(count(*), 1, 0) FROM ". TABLE_AGRUPADOR ."_elemento WHERE uid_agrupador = a.uid_agrupador ), $order";
            }
        }

        $sql .= " ORDER BY $order ";


        if (is_array($limit)) {
            $sql .= " LIMIT ". $limit[0] . ", ". $limit[1];
        }

        if ($number) {
            return $this->db->query($sql, 0, 0);
        }

        $datos = $this->db->query($sql, true);

        if (is_array($datos) && count($datos)) {
            foreach ($datos as $dato) {
                $agrupador = new agrupador($dato["uid_agrupador"]);

                //---- marcamos como auto, si se mira la asignacion y no esta
                if (isset($dato["asignado"]) && !$dato["asignado"] && $agrupador->autoasignar()) {
                    $agrupador->auto = true;
                } else if (isset($dato["asignado"]) && !$dato["asignado"]) {
                    //El agrupador no esta marcado como auto. Continuamos
                    continue;
                }

                //---- marcamos como auto, si se mira la asignacion y no esta
                if (isset($dato["rebote"]) && $dato["rebote"]) {
                    $agrupadorRebote = new agrupador($dato["rebote"]);
                    $agrupador->rebote = ($agrupadorRebote->exists()) ? $agrupadorRebote : null;
                }

                $arrayObjetos->append($agrupador);
            }
        }

        return $arrayObjetos;
    }


    public function obtenerAgrupadoresAsignadosRelacion(elemento $elemento, Iusuario $usuario = NULL){
        $sql = "SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ."_elemento_agrupador
                WHERE uid_agrupador_elemento IN (
                    SELECT uid_agrupador_elemento FROM ". TABLE_AGRUPADOR ."_elemento
                    WHERE uid_modulo = {$elemento->getModuleId()} AND uid_elemento = {$elemento->getUID()}
                )
                AND uid_agrupador IN (
                    SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ." WHERE uid_agrupamiento = {$this->getUID()}
                )
        ";


        $coleccion = $this->db->query($sql, "*", 0, "agrupador");
        return new ArrayAgrupadorList($coleccion);
    }


    public function obtenerAgrupadoresAsignados( $elemento, $usuario=null, $numero=false ){
        if( $elemento instanceof usuario ){ $elemento = $elemento->perfilActivo(); }

        if ($elemento instanceof empresa) {
            $elementIntList = $elemento->getStartIntList();
        } else {
            $elementIntList = $elemento->getUID();
        }

        $condicion = "(
            a.uid_agrupador IN (
                SELECT uid_agrupador
                FROM ". TABLE_AGRUPADOR ."_elemento
                WHERE uid_elemento IN (". $elementIntList .")
                AND uid_modulo = ". $elemento->getModuleId()."
            ) OR (
                a.uid_agrupador IN (
                    SELECT a.uid_agrupador
                    FROM ". TABLE_AGRUPADOR ." a
                    WHERE a.autoasignacion = 1
                )
            )
        )
        ";

        $autoasignado = "if((SELECT if( se.uid_agrupador is null, 0, 1 )
            FROM ". TABLE_AGRUPADOR ."_elemento se
            WHERE a.uid_agrupador = se.uid_agrupador
            AND se.uid_elemento IN (". $elementIntList .")
            AND se.uid_modulo = ". $elemento->getModuleId()." limit 1
        ) is null,0,1) as asignado";

        $rebotado = "(
            SELECT ae.rebote
            FROM ". TABLE_AGRUPADOR ."_elemento ae
            WHERE a.uid_agrupador = ae.uid_agrupador
            AND ae.uid_elemento IN (". $elementIntList .")
            AND ae.uid_modulo = ". $elemento->getModuleId()." limit 1
        ) as rebote";

/*
        if( $this->configValue("jerarquia") ){
            if( $elemento instanceof empleado || $elemento instanceof maquina ){
                $empresa = $elemento->obtenerEmpresaContexto();
                $condicion .= " AND uid_agrupador IN (
                    SELECT uid_agrupador FROM
                    ". TABLE_AGRUPADOR ."_elemento
                    WHERE uid_elemento = ". $empresa->getUID() ."
                    AND uid_modulo = ". util::getModuleId("empresa") ."
                )
                ";
            }
        }
*/
        return $this->obtenerAgrupadores($usuario, $condicion, array($autoasignado, $rebotado), $numero );
    }

    /*
            ACCIONES -

                Eliminar asignaciones que jerarquicamente ya no deban estar de empresa para [ empleado , maquina ]
    */
    public function cronCall($time, $force = false){
        define("NO_CACHE_OBJECTS", TRUE);

        $hora = date("H", $time);
        $minuto = date("i", $time);

        if ($force === false) {
            if ($hora != 3 || $minuto != 10) return true;
        }

        $db = db::singleton();
        $sql = "SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ." WHERE config_jerarquia = 1";

        $uids = $db->query($sql, "*", 0);
        if( !count($uids) ){ return "Nada que hacer"; }


        foreach( $uids as $uid ){
            //if( $uid != 84 ){ continue; }
            echo "\n";
            echo "\t\t\tEncontrado el agrupamiento $uid con la jerarquia activada...";

            $sql = "SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ." WHERE uid_agrupamiento = $uid";
            $uidAgrupadores = $db->query($sql, "*", 0);
            $listaAgrupadores = implode(",",$uidAgrupadores);

            $modulos = array("empleado" => 8, "maquina" => 14);

            if( trim($listaAgrupadores) ){
                foreach($modulos as $modulo => $idmodulo ){
                    $tmp = uniqid();
                    $obtable = constant( "TABLE_". strtoupper($modulo) );
                    $table = DB_TMP. ".$tmp";


                    // Crear la tabla manualmente y con ENGINE=MyISAM consigue una mejora de 2x en el tiempo de ejecución
                    $sql = "CREATE TEMPORARY TABLE $table (
                        `uid_agrupador_elemento` int(10) NOT NULL AUTO_INCREMENT,
                        `uid_agrupador` int(10) NOT NULL,
                        `uid_modulo` int(10) NOT NULL,
                        `uid_elemento` int(10) NOT NULL,
                        PRIMARY KEY (`uid_agrupador_elemento`),
                        UNIQUE KEY `UNIQUE` (`uid_agrupador`,`uid_modulo`,`uid_elemento`),
                        KEY `ca_uid_agrupador` (`uid_agrupador`),
                        KEY `uid_elemento` (`uid_elemento`)
                        ) ENGINE=MyISAM ;
                    ";

                    if( !$db->query($sql) ){
                        echo "\n\t\t\tError en sql $sql: ". $db->lastError()."\n";
                    } else {
                        $sql = "
                            INSERT INTO $table (uid_agrupador_elemento, uid_agrupador, uid_modulo, uid_elemento)
                            SELECT ae.uid_agrupador_elemento, ae.uid_agrupador, $idmodulo, ae.uid_elemento
                            FROM (
                                SELECT uid_agrupador_elemento, uid_elemento, uid_agrupador
                                FROM ". TABLE_AGRUPADOR ."_elemento
                                WHERE uid_modulo = $idmodulo
                                AND uid_agrupador IN ( $listaAgrupadores )
                            ) as ae
                            INNER JOIN ". $obtable ."_empresa ee
                            ON ae.uid_elemento = ee.uid_$modulo
                            WHERE papelera=0
                            AND (
                                uid_empresa NOT IN (
                                    SELECT uid_elemento
                                    FROM ". TABLE_AGRUPADOR ."_elemento sae
                                    WHERE uid_modulo = 1
                                    AND uid_elemento = uid_empresa
                                    AND sae.uid_agrupador = ae.uid_agrupador
                                )
                                AND uid_empresa NOT IN (
                                    SELECT rel.uid_elemento
                                    FROM ". TABLE_AGRUPADOR ."_elemento rel
                                    INNER JOIN ". TABLE_AGRUPADOR ."_elemento_agrupador aea
                                    USING(uid_agrupador_elemento)
                                    WHERE aea.uid_agrupador = ae.uid_agrupador
                                    AND rel.uid_modulo = 1
                                    AND rel.uid_elemento = uid_empresa
                                )
                            )
                            GROUP BY uid_agrupador, uid_elemento
                        ";

                        if( !$db->query($sql) ){
                            echo "\n\t\t\tError en sql $sql: ". $db->lastError()."\n";
                        } else {


                            // si tiene mas de una empresa... mejor tomamos el mas permisivo
                            $dobles = "DELETE $table FROM $table
                                INNER JOIN {$obtable}_empresa
                                ON uid_$modulo = uid_elemento
                                INNER JOIN ( SELECT uid_agrupador, uid_elemento as uid_empresa FROM ". TABLE_AGRUPADOR ."_elemento WHERE uid_modulo = 1 ) as assignempresa
                                USING( uid_empresa, uid_agrupador )
                                WHERE uid_modulo = $idmodulo
                            ";
                            $db->query($dobles);

                            $sql = "UPDATE {$obtable} SET updated = 0 WHERE uid_{$modulo} IN (
                                SELECT ae.uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento ae
                                INNER JOIN $table USING (uid_agrupador_elemento)
                            )";

                            $db->query($sql);

                            $sql = "DELETE ". TABLE_AGRUPADOR ."_elemento FROM ". TABLE_AGRUPADOR ."_elemento INNER JOIN $table USING (uid_agrupador_elemento)";

                            if ($db->query($sql)) {
                                $sqlDelAssignments = "SELECT uid_agrupador_elemento
                                FROM $table";
                                $uidAssignments = $db->query($sqlDelAssignments, "*", 0);

                                if (count($uidAssignments)) {
                                    $delAssignmentsList = implode(",", $uidAssignments);
                                    echo "\n\t\t\tAsignaciones eliminadas (" . $delAssignmentsList . ")\n";
                                }

                                $sql = "DROP TABLE IF EXISTS $table";
                                if (!$db->query($sql)) {
                                    echo "\n\t\t\tError en sql $sql: ". $db->lastError()."\n";
                                } else {
                                    echo "\n\t\t\tModulo $modulo ok";
                                }

                            } else {
                                echo "\n\t\t\tError en sql $sql: ". $db->lastError()."\n";
                            }

                        }
                    }
                }
            }


        }

        echo "\n";
        return true;
    }

    public function obtenerAgrupadoresDisponibles( $elemento, $usuario ){
        if( $elemento instanceof usuario ){ $elemento = $elemento->perfilActivo(); }

        $condicion = " ( uid_agrupador NOT IN (
                SELECT uid_agrupador
                FROM ". TABLE_AGRUPADOR ."_elemento
                WHERE uid_elemento = ". $elemento->getUID() ."
                AND uid_modulo = ". $elemento->getModuleId()."
        ) AND uid_agrupador NOT IN (
            SELECT a.uid_agrupador
            FROM ". TABLE_AGRUPADOR ." a
            WHERE a.autoasignacion = 1
        ) )
        ";

        if( $this->configValue("jerarquia") ){
            if( $elemento instanceof empleado || $elemento instanceof maquina ){
                $empresa = $elemento->obtenerEmpresaContexto();
                $condicion .= "
                AND (
                    uid_agrupador IN (
                        SELECT uid_agrupador FROM
                        ". TABLE_AGRUPADOR ."_elemento
                        WHERE uid_elemento = ". $empresa->getUID() ."
                        AND uid_modulo = ". util::getModuleId("empresa") ."
                    ) OR uid_agrupador IN (
                        SELECT aea.uid_agrupador FROM ". TABLE_AGRUPADOR ."_elemento_agrupador aea
                        INNER JOIN ". TABLE_AGRUPADOR ."_elemento ae
                        USING ( uid_agrupador_elemento )
                        WHERE uid_elemento = ". $empresa->getUID() ."
                        AND uid_modulo = ". util::getModuleId("empresa") ."
                    )
                )
                ";
            }
        }

        if( $usuario instanceof usuario && $this->configValue("al_vuelo") ){
            $empresaUsuario = $usuario->getCompany();
            if( !$empresaUsuario->isEnterprise() ){
                $condicion .= "
                    AND (
                        uid_agrupador IN (
                            SELECT uid_agrupador FROM
                            ". TABLE_AGRUPADOR ."_elemento
                            WHERE uid_elemento = ". $usuario->getCompany()->getUID() ."
                            AND uid_modulo = ". util::getModuleId("empresa") ."
                        )
                    )
                ";
            }
        }

        return $this->obtenerAgrupadores($usuario, $condicion);
    }


    public function getInlineArray($usuarioActivo=false, $mode = null , $data){
        $inline = array();

        if( $categoria = $this->obtenerCategoria() ){
            $categoriainline = array();
            $categoriainline["img"] = RESOURCES_DOMAIN . "/img/famfam/layout_content.png";
            $categoriainline[] = array(
                "nombre" => $categoria->getUserVisibleName(),
                "tagName" => "span"
            );

            $inline[] = $categoriainline;
        }

        return $inline;
    }


    public function getUserVisibleName(){

        $locale = Plantilla::getCurrentLocale();
        if( $locale != "es" ){
            $agrupadorIdioma = new traductor( $this->getUID(), $this );
            $nombre = $agrupadorIdioma->getLocaleValue( $locale );
        }else{
            $info = $this->getInfo();
            $nombre = $info["nombre"];
        }

        if( !isset($nombre) || !trim($nombre) ){
            $nombre = utf8_encode($this->db->query("SELECT nombre FROM $this->tabla WHERE uid_agrupamiento = $this->uid", 0, 0));
        }

        return $nombre;
    }


    public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = NULL, $parent = false, $force = false){
        $info = parent::getInfo($publicMode, $comeFrom, $usuario);
        // Por si tenemos idiomas

        if( ( $locale = Plantilla::getCurrentLocale() ) != "es" ){
            $agrupamientoIdioma = new traductor( $this->getUID(), $this );
            $aliasLocale = $agrupamientoIdioma->getLocaleValue($locale);
            if( trim($aliasLocale) ){
                 $info[$this->getUID()]["nombre"] = $aliasLocale;
            }
        }

        return $info;
    }


    /** DESCRIPCION DEL ELEMENTO */
    public function getMainDescription(){
        $info = $this->getInfo();
        return $info["descripcion"];
    }

    public function getType(){
        return $this->tipo;
    }

    /** PASANDO EL NOMBRE DE UNA OPCION DE CONFIGURACION NOS DICE SI EL USUARIO LA TIENE ACTIVA (true) O NO (false) */
    public function configValue($value){
        $cacheString = "agrupamiento-configvalue-{$this->getUID()}-$value";
        if( ($estado = $this->cache->getData($cacheString)) !== null ){
            return $estado;
        }

        $datos = $this->getInfo();
        if( isset($datos["config_$value"]) ){
            $value = ($datos["config_$value"]) ? true : false;
        }

        $this->cache->addData( $cacheString, $value );
        return $value;
    }

    public function getIcon($htmlimg=true){
        $cacheString = 'icono-agrupamiento-'. $this->uid .'-'.$htmlimg;
        $estado = $this->cache->getData($cacheString);
        if( $estado !== null ){
            return $estado;
        }

        $info = $this->getInfo();
        $icon = $info["icono"];

        // En la bbdd guardamos la url completa, debemos eliminar esto y solo guardar la ruta relativa
        // de momento mientras hacemos el cambio y para hacer facil el cambio reemplazamos cualquier dominio por el RESOURCES_DOMAIN
        if( $host = parse_url($icon, PHP_URL_HOST) ){
            $protocol = parse_url($icon, PHP_URL_SCHEME);
            $domain = "{$protocol}://{$host}/res";
            $icon = str_replace($domain, RESOURCES_DOMAIN, $icon);
        }

        if( !trim($icon) ){ $icon = RESOURCES_DOMAIN . agrupamiento::DEFAULT_ICON; }

        if( $htmlimg ){
            $result = "<img src='$icon' title='". $this->getUserVisibleName()."'/>";
        } else {
            $result = $icon;
        }

        $this->cache->addData( $cacheString, $result );

        return $result;
    }


    public function esProyecto(){
        return ( $this->configValue("proyecto") ) ? true : false;
    }

    public function esAnclaje(){
        return ( $this->configValue("anclaje") ) ? true : false;
    }


    public static function crearNuevo($informacion){
        return self::_crear($informacion, __CLASS__ );
    }

    //----- DEVUELVE LOS ELEMENTOS(EMPELADO/MAQUINA) DE UNA EMPRESA, QUE NO TIENEN AGRUPADORES DE AGRUPAMIENTOS ORGANIZATIVOS ASIGNADOS
    //------ OBJSUPERIOR :empresa, INFERIOR: nombre modulo, empleado/maquinaria
    //------ AGRUPACION : Agrupacion de elementos no asignados
    public function obtenerElementosDeAgrupadoresInactivos($usuario=false, $objSuperior, $inferior, $agrupacion=false, $limit=false){

        $condicion = ( is_array($limit) ) ? (" LIMIT ".$limit[0].", ".$limit[1]."") : "" ;

        $filtro = ""; // posible filtro SQL

        if( $usuario->isViewFilterByGroups() ){
            // $empresa = $usuario->getCompany();
            $modo = $usuario->perfilActivo()->obtenerDato("limiteagrupador_modo");
            switch( $modo ){
                case usuario::FILTER_VIEW_GROUP:
                    //SI EL USUARIO ACTIVO TIENE AL MENOS UN AGRUPADOR DEL AGRUPAMIENTO MARCADO COMO ORGANIZADOR
                    $filtro = " AND
                                (   SELECT count(uid_agrupador)
                                    FROM  ".TABLE_AGRUPADOR."_elemento
                                    INNER JOIN ". TABLE_AGRUPADOR ." USING(uid_agrupador)
                                    WHERE 1
                                    AND uid_modulo = ".util::getModuleId("perfil")."
                                    AND uid_elemento = ".$usuario->perfilActivo()->getUID()."

                                ) >= 1
                    ";
                break;
                default:
                    $idModuloBuscado = util::getModuleId($inferior);
                    $agrupadores = $usuario->obtenerAgrupadores();
                    $list = count($agrupadores) ? $agrupadores->toComaList() : '0';

                    //SI EL USUARIO ACTIVO TIENE AL MENOS UN AGRUPADOR DEL AGRUPAMIENTO MARCADO COMO ORGANIZADOR
                    $filtro = " AND uid_".$inferior." IN (
                                    SELECT uid_elemento
                                    FROM ". TABLE_AGRUPADOR ."_elemento
                                    WHERE uid_modulo = $idModuloBuscado AND uid_agrupador IN ($list)
                                )
                    ";
                break;
            }

        }

        $sql = "
            SELECT uid_".$inferior."
            FROM ".DB_DATA.".".$inferior."_empresa ee
            WHERE 1
            AND ee.uid_empresa = ".$objSuperior->getUID()."
            AND ee.papelera = 0
            AND ee.uid_".$inferior." NOT IN (
                    SELECT uid_elemento
                    FROM ".DB_DATA.".agrupador_elemento
                    WHERE 1
                    AND uid_agrupador IN (
                            SELECT uid_agrupador
                            FROM " .TABLE_AGRUPADOR. "
                            WHERE 1
                            AND uid_agrupamiento = ".$this->getUID()."
                        )
                    AND uid_modulo = ".basic::getModuleId($inferior)."
                )
            ".$filtro."
            ".$condicion."
        ";

        if( $agrupacion /*&& !isset($modo)*/ ){
            $total = $this->db->query($sql, true);

            if( count($total) ){

                $template = Plantilla::singleton();
                $datosGrupo = array();
                $url = "#".$inferior."/listado.php?poid=".$objSuperior->getUID()."&g=-1";

                $datosGrupo["lineas"] = array( 0 => array(
                                                        "nombre" => array(
                                                                "innerHTML" => $template->getString("sin_asignar")." - ".$this->getUserVisibleName()."",
                                                                "title" => "Otros ".$this->getUserVisibleName().""
                                                                        ),
                                                        "abbr" => "".ucfirst($inferior)."s ".$template->getString("sin_asignar")
                                                        )
                                            );

                $datosGrupo["inline"][] = array( "img" => RESOURCES_DOMAIN."/img/famfam/group_go.png",
                                                array( "nombre" => "".count($total)." ".$inferior."(s)",
                                                        "href" => $url
                                                )
                                            );

                $datosGrupo["href"] = $url;
                return $datosGrupo;
            }
        }

        $datos = $this->db->query($sql, "*",0,"empleado");
        return $datos;
    }



    //--- INFERIOR: LO USAMOS PARA CUANDO TENEMOS QUE SACAR LOS AGRUPADORES QUE TIENEN ALGUN ELEMENTO ASIGNADO...CON LO QUE NECESITAMOS OBJETO SUPERIO E OBJETO INFERIOR
    public function obtenerAgrupadoresActivos($condicion, $fields=array(), $number=false, $limit=false, $order=false, $inferior=false, $usuario=false ){
        $arrayObjetos = array();
        $fields[] = "uid_agrupador";
        if( $number ){
            $fields = array( " count(uid_agrupador) " );
        }
        $sql = "SELECT ". implode(",", $fields) ."
            FROM ". TABLE_AGRUPAMIENTO ." a
            INNER JOIN ". TABLE_AGRUPADOR ." USING( uid_agrupamiento )
            WHERE a.uid_". $this->nombre_tabla." = ".$this->uid;

        if($condicion instanceof empresa && ($inferior == "empleado" || $inferior == "maquina" ) ){
            $sql .= "
                AND uid_agrupador IN (
                        SELECT uid_agrupador
                        FROM ".TABLE_AGRUPADOR."_elemento
                        WHERE 1
                        AND uid_modulo = ".basic::getModuleId($inferior)."
                        AND uid_elemento IN (
                            SELECT uid_".$inferior."
                            FROM agd_data.".$inferior."_empresa
                            WHERE 1
                                AND uid_empresa = ".$condicion->getUID()."
                                AND papelera = 0
                        )
                    )
            ";
        }else{
            return array();
        }

        if($usuario instanceof usuario && $usuario->isViewFilterByGroups() ){
            $sql .= " AND ( uid_agrupador IN (". $usuario->obtenerAgrupadores()->toComaList() .") ) ";
        }

        $order = ( $order ) ? $order : "a.nombre";
        $sql .= " ORDER BY $order ";

        if( is_array($limit) ){
            $sql .= " LIMIT ". $limit[0] . ", ". $limit[1];
        }

        if( $number ){
            return $this->db->query($sql, 0, 0);
        }

        $datos = $this->db->query($sql, true);

        if( is_array($datos) && count($datos) ){
            foreach( $datos as $dato ){
                $agrupador = new agrupador( $dato["uid_agrupador"] );

                //---- marcamos como auto, si se mira la asignacion y no esta
                if( isset($dato["asignado"]) && !$dato["asignado"] ){
                    $agrupador->auto = true;
                }

                //---- marcamos como auto, si se mira la asignacion y no esta
                if( isset($dato["rebote"]) && $dato["rebote"] ){
                    $agrupador->rebote = new agrupador( $dato["rebote"] );
                }

                $arrayObjetos[] = $agrupador;
            }
        }

        return $arrayObjetos;
    }


    public static function getModulesReplicables(){
        return array("empleado", "maquina");
    }


    public static function getModulesCategorizables() {
        return array("empresa", "empleado", "maquina", "usuario", "contactoempresa", "agrupador", "signinRequest");
    }

    public static function getModulesToApplyMandatory() {
        $modules = array("empresa", "empleado", "maquina", "signinRequest");
        return new ArrayObjectList($modules);
    }

    public function establecerReplica( $data ){
        foreach($data as $field => $value){
            $sql = "UPDATE ".TABLE_AGRUPAMIENTO." SET $field = $value  WHERE uid_agrupamiento = ".$this->getUID();
            $result = $this->db->query($sql);
            if( $result != 1 ){ return false; }
        }
        return $result;
    }

    /**
     * It will assign the replicated groups of the company
     * $empresa to the item $objetoAsignacion
     * @param  empresa          $empresa
     * @param  childItemEmpresa $objetoAsignacion
     * @param  Iusuario|null    $usuario           If present AssignmentStoreEvent will be triggered
     */
    public function asignarAgrupamientosAsignadosConReplica ($empresa, $objetoAsignacion, Iusuario $usuario = null)
    {
        $arrayUIDagrupadoresAsignados = [];
        $agrupadoresAsignados = $this->obtenerAgrupadoresAsignados($empresa);
        foreach ($agrupadoresAsignados as $agrupadorAsignado) {
            $arrayUIDagrupadoresAsignados[] = $agrupadorAsignado->getUID();
        }

        if (0 === count($arrayUIDagrupadoresAsignados)) {
            return;
        }

        $assigneds = $objetoAsignacion->asignarAgrupadores($arrayUIDagrupadoresAsignados, $usuario, 0, true);

        if (!$usuario) {
            return $assigneds;
        }

        foreach ($assigneds as $group) {
            if (!$assignment = $objetoAsignacion->getAssignment($group)) {
                continue;
            }

            $entity         = $assignment->asDomainEntity();
            $userEntity     = $usuario->asDomainEntity();
            $companyEntity  = $usuario->getCompany()->asDomainEntity();

            $event = new \Dokify\Application\Event\Assignment\Store($entity, $companyEntity, $userEntity);
            $this->app->dispatch(\Dokify\Events::POST_ASSIGNMENT_STORE, $event);
        }

        return $assigneds;
    }


    public function tieneJerarquia(){
        return $this->configValue("jerarquia");
    }


    /** ALIAS PARA SHOW COLUMNS DE ESTA TABLA **/
    public static function getTableFields()
    {
        return [
            ["Field" => "uid_agrupamiento",           "Type" => "int(10)",        "Null" => "NO", "Key" => "PRI", "Default" => "",    "Extra" => "auto_increment"],
            ["Field" => "nombre",                     "Type" => "varchar(45)",    "Null" => "NO", "Key" => "",    "Default" => "",    "Extra" => ""],
            ["Field" => "descripcion",                "Type" => "varchar(255)",   "Null" => "NO", "Key" => "",    "Default" => "",    "Extra" => ""],
            ["Field" => "icono",                      "Type" => "varchar(255)",   "Null" => "NO", "Key" => "",    "Default" => "",    "Extra" => ""],
            ["Field" => "patron",                     "Type" => "varchar(250)",   "Null" => "NO", "Key" => "",    "Default" => "",    "Extra" => ""],
            ["Field" => "uid_categoria",              "Type" => "int(11)",        "Null" => "NO", "Key" => "MUL", "Default" => "",    "Extra" => ""],
            ["Field" => "uid_empresa",                "Type" => "int(11)",        "Null" => "NO", "Key" => "MUL", "Default" => null,  "Extra" => ""],
            ["Field" => "config_readonly",            "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "config_anclaje",             "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "config_filter",              "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "config_documentos",          "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "config_al_vuelo",            "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "config_jerarquia",           "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "config_pago",                "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "config_menu",                "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "config_replica_empleado",    "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "",    "Extra" => ""],
            ["Field" => "config_replica_maquina",     "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "",    "Extra" => ""],
            ["Field" => "config_mandatory",           "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "organizador",                "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "visible_self",               "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => 1,     "Extra" => ""],
            ["Field" => "visible_others",             "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => 1,     "Extra" => ""],
            ["Field" => "updated",                "Type" => "timestamp",      "Null" => "NO",     "Key" => "",    "Default" => "0000-00-00 00:00:00",                                     "Extra" => "on update CURRENT_TIMESTAMP"],
            ["Field" => "has_geolocation",            "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "self_assignable", "Type" => "tinyint(1)", "Null" => "NO", "Key" => "", "Default" => "0", "Extra" => ""],
            ["Field" => "has_duration",            "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "is_cloneable",            "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
            ["Field" => "bounce_assign_user",      "Type" => "int(1)",         "Null" => "NO", "Key" => "",    "Default" => "0",   "Extra" => ""],
        ];
    }

    public function updateOwner(empresa $empresa) {
        $SQL = "UPDATE ". TABLE_AGRUPADOR ." SET uid_empresa = {$empresa->getUID()} WHERE uid_agrupamiento = {$this->getUID()}";
        if (!$this->db->query($SQL)) {
            error_log("Cant move agrupadores");
            return false;
        }

        $SQL = "UPDATE ". TABLE_DOCUMENTO_ATRIBUTO ." SET uid_empresa_propietaria = {$empresa->getUID()} WHERE uid_modulo_origen = 12 AND uid_elemento_origen = {$this->getUID()}";
        if (!$this->db->query($SQL)) {
            error_log("Cant move documento_atributo from agrupamiento");
            return false;
        }

        $SQL = "UPDATE ". TABLE_DOCUMENTO_ATRIBUTO ." SET uid_empresa_propietaria = {$empresa->getUID()} WHERE uid_modulo_origen = 11 AND uid_elemento_origen IN (
            SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ." WHERE uid_agrupamiento = {$this->getUID()}
        )";
        if (!$this->db->query($SQL)) {
            error_log("Cant move documento_atributo from agrupador");
            return false;
        }

        return true;
    }

    public static function defaultData($data, Iusuario $usuario = null) {
        $data["uid_empresa"] = $usuario->getCompany()->getUID();
        return $data;
    }

    public function update($data = false, $fieldsMode = false, Iusuario $usuario = null)
    {
        $currentCategoryUid = (int) $this->obtenerDato("uid_categoria");

        $parentReturn = parent::update($data, $fieldsMode, $usuario);

        $changes = ($data) ? $data : $_REQUEST;
        $categoryUid = false === empty($changes["uid_categoria"]) ? (int) $changes["uid_categoria"] : false;

        if (true === $parentReturn && $currentCategoryUid !== $categoryUid && $categoryUid === 10) {
            $organizationEntity = $this->asDomainEntity();

            $event = new EventCategoryWorksSetted($organizationEntity);
            $this->dispatcher->dispatch(
                OrganizationEvents::ORGANIZATION_CATEGORY_WORKS_SETTED,
                $event
            );
        }

        return $parentReturn;
    }

    public function updateData($data, Iusuario $usuario = NULL, $mode = NULL) {
        // Nos aseguramos de que si se intenta modificar la empresa, solo un usuario staff puede hacerlo
        if (isset($data["uid_empresa"])) {
            if ($usuario instanceof usuario && $usuario->esStaff()) {
                $empresa = $this->getCompany();

                $newUIDEmpresa = @$data["uid_empresa"];
                if( is_numeric($newUIDEmpresa) && $empresa->getUID() != $newUIDEmpresa) {
                    $newEmpresa = new empresa($newUIDEmpresa);
                    $this->updateOwner($newEmpresa);
                }

            }
        }

        $categoryID = isset($data["uid_categoria"]) ? $data["uid_categoria"] : false;
        if ($categoryID && in_array($categoryID, categoria::$automaticAssignOrganization)) {
            // If the new category is in the set of 'automatic assign organizations' we have to uncheck the module invitation
            $this->desasignarModulo(92);
        }

        if (
            (isset($data['self_assignable']) && $data['self_assignable']
            && $this->configValue('al_vuelo'))
            || (isset($data['config_al_vuelo']) && $data['config_al_vuelo']
            && (bool)$this->obtenerDato("self_assignable"))
        ) {
            throw new Exception(_("You can't configure self assignable and 'on the fly'"));
        }


        return $data;
    }

    /***
       * Return bool - If the @param $user can edit any group on this organization when assign in @param $element
       *
       *
       * @user - the user to check if can edit
       * @element - the requestable item to check
       *
       */
    public function isEditable (Iusuario $user, Ielemento $element) {
        if (!$user->accesoAccionConcreta($element, 153)) {
            return false;
        }

        // user info
        $userCompany = $user->getCompany();
        $startList = $userCompany->getStartList();
        $userProfile = $user->perfilActivo();

        // categorizable item info
        $elementCompany = ($element instanceof empresa) ? $element : $element->getCompany($user);
        $isOwnItem = $startList->contains($elementCompany);

        // this org is is our own organization (or from our corp)
        $company = $this->getCompany();
        $isOwnOrg = $company->compareTo($userCompany);

        if ($userProfile->asDomainEntity()->isCorporationProfile()) {
            $isOwnOrg = $userCompany->getOriginCompanies()->contains($company);
        }

        // if we are editing our own items
        if ($isOwnItem) {
            // to our ver y own company we can only edit our own orgs
            if ($element instanceof empresa) {
                return $isOwnOrg;
            }

            return true;
        }

        // if it is a company, check if it is a contract.
        // in contracts we can edit everything we can see
        if ($element instanceof empresa) {
            if ($elementCompany->esContrata($startList)) {
                return true;
            }
        }

        return false;
    }

    public function mustBeSuggested (Iusuario $user, Ielemento $element) {
        if (!$element instanceof childItemEmpresa) return false;

        $company        = $element->getCompany($user);
        $origin         = $company->getOriginCompanies();
        $userCompany    = $user->getCompany();

        if (false === $origin->contains($userCompany)
        && true === $company->esContrata($userCompany)) {
            return true;
        }

        return false;
    }

    public static function conditionSQL($conditions = [])
    {
        $filters = [];
        $organizationsTable = TABLE_AGRUPAMIENTO;
        $groupsTable = TABLE_AGRUPADOR;
        $organizationTagsTable = TABLE_AGRUPAMIENTO . "_etiqueta";
        $assignsTable = TABLE_AGRUPADOR . "_elemento";

        if (is_traversable($conditions) && count($conditions)) {
            foreach ($conditions as $key => $condition) {
                // Si la condicion es un un objeto
                if ($condition instanceof elemento) {
                    switch (get_class($condition)) {
                        case 'usuario':
                            $userCompany = $condition->getCompany();
                            $baseUserFilter = "uid_agrupamiento IN (
                                SELECT uid_agrupamiento
                                FROM agd_data.agrupamiento org
                                WHERE org.uid_empresa != {$userCompany->getUID()}
                                OR (
                                    org.uid_empresa = {$userCompany->getUID()}
                                    AND org.config_filter = 0
                                )
                            )";

                            if ($condition->isViewFilterByLabel()) {
                                $etiquetasUsuario = $condition->obtenerEtiquetas()->toIntList()->getArrayCopy();

                                if (is_array($etiquetasUsuario) && count($etiquetasUsuario)) {
                                    $tagList = implode(",", $etiquetasUsuario);

                                    $filters[] = "({$baseUserFilter}
                                        OR uid_agrupamiento IN (
                                            SELECT uid_agrupamiento
                                            FROM {$organizationTagsTable} tags
                                            INNER JOIN agd_data.agrupamiento org USING (uid_agrupamiento)
                                            WHERE org.uid_empresa = {$userCompany->getUID()}
                                            AND org.config_filter = 1
                                            AND tags.uid_etiqueta IN ({$tagList})
                                        )
                                    )";
                                } else {
                                    $filters[] = "({$baseUserFilter}
                                        OR uid_agrupamiento NOT IN (
                                            SELECT uid_agrupamiento
                                            FROM {$organizationTagsTable}
                                            INNER JOIN agd_data.agrupamiento org USING (uid_agrupamiento)
                                            WHERE uid_agrupamiento = uid_agrupamiento
                                            AND org.uid_empresa = {$userCompany->getUID()}
                                            AND org.config_filter = 1
                                        )
                                    )";
                                }
                            }

                            if ($condition->isViewFilterByGroups()) {
                                $groups = $condition->obtenerAgrupadores();
                                $groupList = count($groups) ? $groups->toComaList() : "0";

                                $filters[] = "({$baseUserFilter}
                                    OR uid_agrupamiento IN (
                                        SELECT uid_agrupamiento
                                        FROM agd_data.agrupador gr
                                        INNER JOIN agd_data.agrupamiento org USING (uid_agrupamiento)
                                        WHERE org.uid_empresa = {$userCompany->getUID()}
                                        AND org.config_filter = 1
                                        AND gr.uid_agrupador IN ({$groupList})
                                    )
                                )";
                            }

                            break;
                        case 'categoria':
                            $filters[] = "uid_categoria = {$condition->getUID()}";

                            break;

                        // Empresa a la que vamos a asignar
                        case 'empresa':
                            if ($key === 'asignado') {
                                $filters[] = "uid_agrupamiento IN (
                                    SELECT uid_agrupamiento FROM {$groupsTable}
                                    INNER JOIN {$assignsTable} USING (uid_agrupador)
                                    WHERE uid_modulo = 1
                                    AND uid_elemento = {$condition->getUID()}
                                )";
                            } else {
                                $ref = ($corp = $condition->perteneceCorporacion()) ? $corp : $condition;
                                $selfList = $ref->getStartIntList()->toComaList();
                                if (isset($conditions['modulo']) && 'signinRequest' === $conditions['modulo']) {
                                    // Visibilidad del agrupamiento para las invitaciones de esta empresa
                                    $filters[] = "(uid_empresa IN ({$selfList}) AND visible_others = 1)";
                                } else {
                                    // Visibilidad del agrupamiento para esta empresa
                                    $filters[] = "(
                                        (uid_empresa IN ({$selfList}) AND visible_self = 1)
                                        OR
                                        (uid_empresa NOT IN ({$selfList}) AND visible_others = 1)
                                    )";
                                }
                            }

                            break;
                        default:
                            break;
                    }

                // Si son clave valor
                } elseif (is_string($condition)) {
                    $compare = "=";

                    // los  filtros directos de columna=valor no aplican a este metodo, sirven para agrupador
                    if (strpos($condition, '=')) {
                        continue;
                    }

                    // negative condition
                    if (0 === strpos($condition, '!')) {
                        $condition = substr($condition, 1);
                        $compare = "!=";
                    }

                    switch ((string) $key) {
                        case 'modulo':
                            $condition = is_numeric($condition) ? $condition : util::getModuleId($condition);
                            $conditionSQL = "uid_modulo = {$condition}";
                            $filters[] = $conditionSQL;
                            break;

                        case 'self_assignable':
                            $filters[] = "self_assignable = {$condition}";
                            break;

                        default:
                            if (strpos($condition, "config_") === 0) {
                                $condition = substr($condition, 7);
                            }

                            $filters[] = " config_$condition {$compare} 1 ";
                            break;
                    }
                }
            }
        }

        if (count($filters)) {
            return implode(" AND ", $filters);
        }
    }

    static public function optionsFilter($uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){
        $condicion = array();

        if (is_numeric($uidelemento) && $user instanceof usuario) {
            $agrupamiento = new self($uidelemento);

            $agrupamientosPropios = $user->getCompany()->obtenerAgrupamientosPropios(array($user));

            if (!$agrupamientosPropios->contains($agrupamiento)) {
                $condicion[] = " uid_accion NOT IN (4,12,13,14,49,58) ";
            }

            /*
            if ((!is_traversable($agrupamientosPropios) || !count($agrupamientosPropios)) || (is_traversable($agrupamientosPropios) && count($agrupamientosPropios) && !$agrupamientosPropios->contains($agrupamiento))){
                $condicion[] = " uid_accion NOT IN (4,12,13,14,49) ";
            }*/
        }

        if( count($condicion) ){
            return " AND ". implode(" AND ", $condicion);
        }
        return false;
    }

    public function isMandatory () {
        return (bool) $this->obtenerDato("config_mandatory");
    }

    /**
     * @return bool
     */
    public function isSelfAssignable(): bool
    {
        return (bool) $this->obtenerDato("self_assignable");
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $campos = new FieldList();

        $campos["nombre"]       = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
        $campos["descripcion"]  = new FormField(array("tag" => "textarea" ));

        switch ($modo) {
            case elemento::PUBLIFIELDS_MODE_NEW:
                $campos["uid_empresa"] = new FormField();
                $campos["config_jerarquia"] = new FormField();
            break;
            case elemento::PUBLIFIELDS_MODE_VISIBILITY:
                $campos = new FieldList();

                $campos["visible_self"] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox"));
                $campos["visible_others"] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox"));
            break;
            case elemento::PUBLIFIELDS_MODE_EDIT:
                $campos["config_menu"] = new FormField(array("tag" => "input", "type" => "checkbox",  "className" => "iphone-checkbox" ));

                $campos["icono"] = new FormField(array("tag" => "a",    "className" => "line-block img-picker a-extend", "format" => "<img src='%s' />", "default" => RESOURCES_DOMAIN . agrupamiento::DEFAULT_ICON, "href" => "agrupamiento/iconos.php?folder=categorias"));
                if( $usuario instanceof usuario && $usuario->esStaff() ){
                    $campos["patron"] = new FormField(array("tag" => "input", "type" => "text" ));
                    $campos["uid_categoria"] = new FormField(array("tag" => "select", "data" => categoria::getAll(), "default" => "sin_categoria" ));
                }
                $campos["config_filter"] = new FormField(array("tag" => "input", "type" => "checkbox",  "className" => "iphone-checkbox" ));
                $campos["config_documentos"] = new FormField(array("tag" => "input", "type" => "checkbox",  "className" => "iphone-checkbox" ));

                $campos["config_readonly"] = new FormField(array("tag" => "input", "type" => "checkbox",  "className" => "iphone-checkbox" ));
                $campos["config_mandatory"] = new FormField(array("tag" => "input", "innerHTML" => "mandatory_assign", "type" => "checkbox",  "className" => "iphone-checkbox"));
                $campos["has_geolocation"] = new FormField(array("tag" => "input", "innerHTML" => "has_geolocation", "type" => "checkbox",  "className" => "iphone-checkbox"));
                $campos["has_duration"] = new FormField(array("tag" => "input", "innerHTML" => "has_duration", "type" => "checkbox",  "className" => "iphone-checkbox"));
                $campos["is_cloneable"] = new FormField(array("tag" => "input", "innerHTML" => "is_cloneable", "type" => "checkbox",  "className" => "iphone-checkbox"));

                if ($usuario instanceof usuario && $usuario->esStaff()) {
                    $campos["self_assignable"] = new FormField(["tag" => "input", "innerHTML" => "self_assignable", "type" => "checkbox",  "className" => "iphone-checkbox"]);
                    $campos["bounce_assign_user"] = new FormField(["tag" => "input", "innerHTML" => "bounce_assign_user", "type" => "checkbox",  "className" => "iphone-checkbox"]);
                }
                // Si se puede, damos la opcion de mover el agrupamiento de empresas
                // es útil cuando un cliente pasa de ser una simple empresa a ser una corporacion
                if ($usuario instanceof usuario && $objeto instanceof self && $usuario->esAdministrador()) {
                    // Posibles empresas propietarias
                    $empresa = $objeto->getCompany();
                    $list = ($corp = $empresa->perteneceCorporacion()) ? $corp->getStartIntList() : $empresa->getStartIntList();
                    $empresas = $list->toObjectList('empresa');

                    $airLiquideMedicinal = new empresa(33731);
                    if (false === $empresas->contains($airLiquideMedicinal)) {
                        // Add AL Medicinal
                        $empresas[] = $airLiquideMedicinal;
                    }

                    // Si podemos tener varias empresas donde mover este agrupamiento
                    if (count($empresas)>1) {
                        $campos["uid_empresa"] = new FormField(array("tag" => "select", "data" => $empresas, "innerHTML" => "empresa"));
                    }
                }

            break;
            case elemento::PUBLIFIELDS_MODE_ATTR:
                $campos["config_anclaje"] = new FormField(array("tag" => "input", "type" => "checkbox" ));
                $campos["config_jerarquia"] = new FormField(array("tag" => "input", "type" => "checkbox" ));
                $campos["config_al_vuelo"] = new FormField(array("tag" => "input", "type" => "checkbox" ));
                $campos["config_pago"] = new FormField(array("tag" => "input", "type" => "checkbox" ));
            break;
        }

        return $campos;
    }
}
