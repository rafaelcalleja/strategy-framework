<?php
    class agrupador extends categorizable implements Iactivable, Ielemento, IfolderContainer, Ilistable {
        public $auto;
        public $rebote;

        public function __construct( $UIDAgrupamientolista, $extra = false ){
            $this->auto = false;
            $this->rebote = false;
            //$this->db = db::singleton();
            $this->tabla = TABLE_AGRUPADOR;
            $this->instance( $UIDAgrupamientolista, $extra );
        }

        public static function getRouteName () {
            return 'group';
        }

        /**
         * A temporary method to convert a legacy class in a repo/entity class
         * @return Group\Group
         */
        public function asDomainEntity()
        {
            return $this->app['group.repository']->find($this->getUID());
        }

        /***
           * Return bool
           *
           * Indica si este grupo tiene algun rebote para este elemento, esto es util para detectar futuros rebotes al eliminar una asignación
           *
           *
           *
           */
        public function canBounceTo(categorizable $element, \Dokify\Domain\Company\Company $company)
        {
            $tableGroup = TABLE_AGRUPADOR;

            $groupUid = $this->getUID();
            $companyUid = $company->uid();
            $elementUid = $element->getUID();
            $module = $element->getModuleId();

            $bounceOrigin = "SELECT uid_elemento
            FROM {$tableGroup}_elemento
            WHERE uid_modulo = 11
            AND uid_agrupador = {$groupUid}";

            $sql = "SELECT uid_agrupador
            FROM {$tableGroup}_elemento
            INNER JOIN agd_data.assignment_version
            ON uid_agrupador_elemento = uid_assignment
            WHERE uid_modulo = {$module}
            AND uid_company = {$companyUid}
            AND uid_elemento = {$elementUid}
            AND uid_agrupador IN ({$bounceOrigin})
            LIMIT 1";

            if ($uid = $this->db->query($sql, 0, 0)) {
                return new self($uid);
            }

            return false;
        }


        /***
           * Return bool
           *
           * Indica si nuestro usuario puede crear o borrar asignaciones basadas en $this para @param $element
           *
           *
           *
           */
        public function isAssignEditable (Iusuario $user, categorizable $element)
        {
            // --- user data
            $userCompany    = $user->getCompany();
            $userOrigin     = $userCompany->getOriginCompanies();
            $userStartList  = $userCompany->getStartList();

            // --- assign data
            $company            = $element instanceof empresa ? $element : $element->getCompany($user);

            if (!$company instanceof empresa) {
                return false;
            }

            $groupCompany       = $this->getCompany();
            $isOwnGroup         = $userOrigin->contains($groupCompany);

            // This is our group
            if ($isOwnGroup) {
                // Our own company
                if ($userOrigin->contains($company) || $userStartList->contains($company)) {
                    return true;
                }
            // The not our group, but still we can do things
            } else {
                // Check if the current element has assigned the group
                $hasGrpoup = $element->getAssignment($this);
                if ($hasGrpoup && $userOrigin->contains($company)) {
                    return true;
                }

                // Check if the user can assign the group to the element
                $clientsId = $company->obtenerIdEmpresasSuperiores();
                if ($clientsId->contains($groupCompany->getUID() && $userOrigin->contains($company))) {
                    return true;
                }

                $belongsMyCorp = ($corp = $company->perteneceCorporacion()) && $corp->compareTo($userCompany);
                if ($belongsMyCorp) {
                    return true;
                }
            }

            // Edit other comany
            if ($element instanceof empresa) {
                if ($userCompany->esCorporacion()) {
                    $contracts = $userCompany->obtenerEmpresasInferiores(false, false, false, 1);
                } else {
                    $contracts = $userCompany->obtenerEmpresasInferioresMasActual();
                }

                // The company must be a direct contract (no subcontract)
                if ($contracts->contains($element)) {
                    return true;
                }
            }

            return false;
        }

        public function getCategory () {
            if ($agrupamiento = $this->obtenerAgrupamientoPrimario()) {
                return $agrupamiento->obtenerCategoria();
            }
        }


        /** Nos devuelve el objeto empresa propietaria de este agrupamiento **/
        public function getCompany(){
            if( $uid = $this->obtenerDato("uid_empresa") ){
                return new empresa($uid);
            }
            return false;
        }


        public function autoasignar () {
            return (bool) $this->obtenerDato("autoasignacion");
        }


        /***
           * Bool - if this group have a referenced attrs inside
           *
           * @param $target = NULL | array() of target modules to test
           *
           */
        public function hasReferencedAttrs ($target = null) {
            $SQL = "SELECT count(uid_documento_atributo)
                    FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
                    WHERE uid_modulo_origen = 11
                    AND uid_elemento_origen = {$this->getUID()}
                    AND (
                        referenciar_empresa = ". documento_atributo::REF_TYPE_COMPANY ."
                        OR referenciar_empresa = ". documento_atributo::REF_TYPE_CHAIN ."
                    )
            ";

            if ($target) $SQL .= " AND uid_modulo_destino IN (". implode(',', $target) .")";

            return (bool) $this->db->query($SQL, 0, 0);
        }

        public function getFromParams($name, $empresaUID, $category = false){
            $cache = cache::singleton();
            if( ($cacheString = __CLASS__ . '-' .__FUNCTION__ .'-'.$name.'-'.$empresaUID.'-'.$category) && ($estado = $cache->getData($cacheString)) !== null ){
                return $estado;
            }
            $empresas = new ArrayObjectList();
            $db = db::singleton();

            if (is_numeric($empresaUID = db::scape($empresaUID))) {
                $empresa = new empresa($empresaUID);
                if ($corp = $empresa->perteneceCorporacion()) {
                    $empresas[] = $corp;
                }
                $empresas[] = $empresa;
            } else return false;

            $sql = "SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ." WHERE nombre = '". db::scape($name). "' AND uid_empresa in ({$empresas->toComaList()})";

            if ($category) {
                $sql .= " AND uid_agrupamiento =
                    (SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ."
                        WHERE
                        uid_categoria = '".$category."'
                        AND uid_empresa in ({$empresas->toComaList()}))";
            }

            $agrupadores = db::get($sql, "*", 0, "agrupador");


            if ($agrupadores) $agrupador = reset($agrupadores);
            else $agrupador = false;

            $cache->addData($cacheString, $agrupador);
            return $agrupador;
        }


        public function removeParent(elemento $parent, usuario $usuario = null) {
            return $this->eliminar();
        }


        public function getTreeData(Iusuario $usuario, $extraData = array()){
            if (isset($extraData[Ilistable::DATA_CONTEXT])){
                if (isset($extraData[Ilistable::DATA_ELEMENT])){
                    $elemento = $extraData[Ilistable::DATA_ELEMENT];
                }
                return array(
                    "img" => array(
                        "normal" => RESOURCES_DOMAIN ."/img/famfam/folder.png",
                        "open" => RESOURCES_DOMAIN ."/img/famfam/folder_table.png"
                    ),
                    "url" => "../agd/list.php?comefrom={$elemento->getType()}&m=agrupador&action=CarpetasDocumentosDescargables&poid={$elemento->getUID()}&data[context]=descargables&data[parent]=$elemento&params[]={$this}&params[]={$usuario}&options=0"
                );
            } else return false;
        }

        public function inTrash($parent = NULL){
            $SQL = "SELECT papelera FROM {$this->tabla} WHERE uid_agrupador = {$this->uid}";
            return (bool) $this->db->query($SQL, 0, 0);
        }


        /** CIERTOS AGRUPADORES POR SUS CARACTERISTICAS NUNCA PODRAN SER PRESENTADOS COMO "EN MAL ESTADO" DE FORMA AUTOMATICA, ESTA FUNCION LOS EXCLUYE */
        public function isErrorCalculable(){
            $blacklist = array("comunitarios");
            $name = $this->getUserVisibleName();
            foreach($blacklist as $exclude){
                if( stripos($name, $exclude) !== false ) return false;
            }

            return true;
        }

        public function getRelatedData($usuario){
            $tpl = Plantilla::singleton();
            $data = array();

            $elementos = $this->obtenerElementosAsignados("empleado");
            $data[] = array(
                'innerHTML' => sprintf( $tpl('hay_s_empleados_asignados'), count($elementos) ),
            );

            return $data;
        }


        public function getLineClass($parent, $usuario, $data = array()){
            $class = false;
            $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;
            $categoria = $this->obtenerAgrupamientoPrimario()->obtenerCategoria();
            switch($context){
                case Ilistable::DATA_CONTEXT_DESCARGABLES:
                    return $class;
                break;
                default:
                if( $categoria instanceof categoria ){
                    switch($categoria->getUID()){
                        case categoria::TYPE_TAREAS:

                            $class = array('color');

                            if( $elementos = $this->obtenerElementosAsignados('empleado') ){
                                if( $this->getNumberOfExceptionMessages() ){
                                    $class[] = 'red';
                                } else {
                                    $class[] = 'green';
                                }
                            } else {
                                $class[] = 'black';
                            }

                        break;
                    }
                }

                if( is_array($class) ) return implode(' ', $class);
                return $class;
                break;
            }
        }

        public function getNumberOfExceptionMessages(usuario $usuario = NULL){
            return $this->obtenerDato("alerts");
        }

        public function getExceptionMessages(usuario $usuario = NULL){
            $tpl = Plantilla::singleton();
            $elementos = $this->obtenerElementosAsignados("empleado");
            $similares = $this->getSimilars();
            //echo "{$item->getUID()} - {$name}\n";

            $messages = array();

            if( count($elementos) && $similares ){
                //echo "\tHay ".   count($similares) ." tareas similares. EJ: (". implode(", ", array_slice($similares->toIntList()->getArrayCopy(), 0, 3)) .")\n";
                //echo "\tCon ".  $similares->getAVG() ." empleados de media VS ". count($elementos) . " de esta\n";

                if( $puestos = $similares->obtenerAsignadosElementos("puestos") ){
                    //$isOk = true;
                    foreach($puestos as $puesto){
                        if( !$puesto->isErrorCalculable() ) continue;

                        $percent = $puesto->obtenerPorcentajeAsignacion($similares);
                        $cur = $puesto->obtenerPorcentajeAsignacion($this);

                        if( $percent > $cur && ($diff = ($percent-$cur)) > 15 ){
                            $str  = sprintf($tpl('faltan_empleados_puesto'), $puesto->getUserVisibleName());
                            if( $usuario instanceof usuario && $usuario->esStaff() ){
                                $str .= " ({$puesto->getUID()}) [diff {$diff}%]";
                            }
                            //"Parece que faltan empleados con el puesto <strong>{$puesto->getUserVisibleName()}</strong>. ";
                            //

                            $messages[] = new Exception($str);
                            //$isOk = false;
                            //echo "\t\tEl puesto {$puesto->getUserVisibleName()} ({$puesto->getUID()}) lo tienen el {$percent}% de empleados en tareas similares\n";
                            //echo "\t\tLa tarea actual tiene el {$cur} (diff {$diff})\n";
                        }
                    }

                    // if( $isOk ) echo "\tParece que este item está OK!!\n";
                    //echo "\tPuestos habituales: {$puestos->toComaList()}\n";
                }
            } else {
                //echo "\tParece que no hay empleados realizando esta tarea!!\n";
            }

            $this->updateAlerts( count($messages) );
            return $messages;
        }


        public function getSimilars($n = NUll){
            $name = $this->getUserVisibleName();
            $name = str_replace(array("."), array(" "), $name);
            $parts = explode(" ", $name);

            // Buscar las palabras clave relevantes
            $relevantParts = array_filter($parts, "isRelevantWord");
            foreach($relevantParts as $string){
                $relevantParts = array_merge($relevantParts, getSimilarWords($string));
            }
            $relevantParts = array_unique($relevantParts);
            $relevantParts = array_map('db::scape', $relevantParts);



            if( count($relevantParts) ){
                // Fulltext temporary search
                $MyISAM = uniqid() . time();
                $SQL = "
                    CREATE TEMPORARY TABLE IF NOT EXISTS `{$MyISAM}` (
                        `uid` int(11) NOT NULL AUTO_INCREMENT,
                        `nombre` varchar(300) NOT NULL,
                        PRIMARY KEY (`uid`),
                        FULLTEXT KEY `nombre` (`nombre`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1
                ";
                if( !$this->db->query($SQL) ){ die("ERROR #1"); }


                $uidEmpresaPropietaria = $this->obtenerDato("uid_empresa");
                $SQL = "INSERT INTO `{$MyISAM}` SELECT agrupador.uid_agrupador, agrupador.nombre FROM ". TABLE_AGRUPADOR . " WHERE uid_empresa = $uidEmpresaPropietaria ";
                if( !$this->db->query($SQL) ){ die("ERROR #2"); }


                $match = "MATCH (nombre) AGAINST ('".implode(" ", $relevantParts)."')";
                $SQL = "
                    SELECT uid, $match as m
                    FROM `{$MyISAM}` WHERE 1
                    AND $match > 2
                    AND uid != {$this->getUID()}
                    ORDER BY $match DESC
                ";


                if( is_numeric($n) ) $SQL .= " LIMIT $n";

                //dump($SQL, $db->query($SQL, true), $db);
                $relacionados = $this->db->query($SQL, "*", 0, "agrupador");
                if( $relacionados === false ){ die("ERROR #3"); }


                $SQL = "DROP TABLE `{$MyISAM}`";
                if( !$this->db->query($SQL) ){ die("ERROR #4"); }

                try {
                    return new ArrayAgrupadorList($relacionados);
                } catch(InvalidArgumentException $e){
                    if( CURRENT_ENV == 'dev'){ dump($e->getMessage(), $this->db); exit; }
                }
            }

            return false;
        }



        public function obtenerPorcentajeAsignacion($param){
            if( $param instanceof ArrayAgrupadorList ){
                if( !$list = $param->obtenerIdElementosAsignados(8) ){ return false; }
            } elseif( $param instanceof agrupador ){
                $list = new ArrayObjectList($param->obtenerElementosAsignados(8));
                if( !is_traversable($list) || !count($list) ){ return false; }
            }


            $SQL = "SELECT count(uid_elemento) as c FROM ". TABLE_AGRUPADOR ."_elemento
            WHERE uid_elemento IN ({$list->toComaList()}) AND uid_agrupador = {$this->getUID()}
            GROUP BY uid_agrupador
            ";


            $n = $this->db->query($SQL, 0, 0);
            $n = $n ? $n : 0;


            return round(($n*100)/count($list));

        }

        public function enviarPapelera($parent, usuario $usuario){
            $disabled = $this->updateWithRequest( array("papelera" => 1 ), "papelera", $usuario );

            if ($disabled) {
                $entity = $this->asDomainEntity();
                $event  = new \Dokify\Application\Event\Group\Disable($entity);
                $this->app->dispatch(\Dokify\Events::POST_GROUP_DISABLE, $event);
            }

            return $disabled;
        }

        public function restaurarPapelera($parent, usuario $usuario){
            return $this->updateWithRequest( array("papelera" => 0 ), "papelera", $usuario );
        }

        public function eliminar(Iusuario $usuario = null)
        {
            $group   = $this->asDomainEntity();
            $deleted = parent::eliminar($usuario);

            if ($deleted) {
                $event = new \Dokify\Application\Event\Group\Delete($group);
                $this->app->dispatch(\Dokify\Events::POST_GROUP_DELETE, $event);
            }

            return $deleted;
        }

        /** (La ultima "s" no es un error, es por compatibilidad
          *
          *
          *
          */
        public function obtenerCentroCotizacions($count = false){
            if( $count ){
                $sql = "SELECT count(uid_elemento) FROM ". TABLE_CENTRO_COTIZACION ." WHERE uid_elemento = {$this->getUID()} AND uid_modulo = {$this->getModuleId()}";
                return $this->db->query($sql, 0, 0);
            } else {
                $sql = "SELECT uid_centrocotizacion FROM ". TABLE_CENTRO_COTIZACION ." WHERE uid_elemento = {$this->getUID()} AND uid_modulo = {$this->getModuleId()}";
                $items = $this->db->query($sql, "*", 0, "centrocotizacion");
                return new ArrayObjectList($items);
            }
        }


        /** PARA MOSTRAR UN NOMBRE ALTERNATIVO EN LAS CAJAS DE ASIGNAR **/
        public function getAssignName($usuario=false, $elemento=false){
            $name = $this->getUserVisibleName();

            if( $usuario instanceof usuario && $elemento instanceof certificacion && $this->esPago() ){ // si vamos a asignar certificaciones...
                $coste = $this->getPrecioUnitario($elemento);
                return $name . " - ".$coste."€";
            }

            if( $usuario instanceof usuario && $elemento == "categorizable" ){
                return $this->getNombreTipo() . " - " . $name;
            }

            return $name;
        }

        public function getPrecioUnitario($elemento){
            if( $parametro = reset($elemento->getCompany()->obtenerParametrosDeRelacion($this) ) ){
                $coste = $parametro->obtenerDato("precio_unitario");
            } else {
                $coste = $this->obtenerDato("precio_unitario");
            }
            return $coste;
        }

        public function closest($agrupamiento, $contendorInicial=null, $exclude=array()){
            $agrupamientoInicio = ( $contendorInicial ) ? $contendorInicial : $this->obtenerAgrupamientoPrimario();

            $sql = "
                SELECT uid_elemento, uid_agrupamiento FROM ". TABLE_AGRUPADOR ."_elemento ae
                INNER JOIN ". TABLE_AGRUPAMIENTO ."_agrupador aa
                ON ae.uid_elemento = aa.uid_agrupador
                WHERE 1
                    AND ae.uid_agrupador = ". $this->getUID() ."
                    AND aa.uid_agrupamiento != ". $agrupamientoInicio->getUID() ."
                    AND uid_modulo = 11
                    AND ae.uid_agrupador != ae.uid_elemento";
            if( count($exclude) ){
                $sql .= " AND ae.uid_elemento NOT IN (". implode(",",$exclude) .")";
            }


            $lineas = $this->db->query($sql, true);

            $exclude[] = $this->getUID();
            $agrupadores = array();

            /***
            dump($sql);
            dump( "Buscando superiores para ". $this->getUserVisibleName() );
            foreach($lineas as $linea){
                $agrupador = new agrupador($linea["uid_elemento"]);
                dump( "\tEncontrado ". $agrupador->getUserVisibleName());
            }
            */

            foreach($lineas as $linea){
                $agrupador = new agrupador($linea["uid_elemento"]);

                if( $linea["uid_agrupamiento"] === $agrupamiento->getUID() ){
                    //dump("El agrupador ". $agrupador->getUserVisibleName() ." pertence a ". $agrupamiento->getUserVisibleName() );
                    $agrupadores[] = $agrupador;
                } else {
                    $agrupadoresSuperiores = $agrupador->closest($agrupamiento, $agrupamientoInicio, $exclude );
                    $agrupadores = array_merge_recursive($agrupadores, $agrupadoresSuperiores);
                }
            }

            $agrupadores = array_unique($agrupadores);
            return $agrupadores;
        }

        public function configuracionAgrupamiento($agrupamiento, $campo, $valor=false){
            $campo = db::scape($campo);
            $tabla = TABLE_AGRUPADOR . "_agrupamiento";
            if( $valor === false ){
                $sql = "SELECT $campo FROM $tabla WHERE uid_agrupamiento = ". $agrupamiento->getUID() ." AND uid_agrupador = ".$this->getUID();
                return $this->db->query($sql, 0, 0);
            } else {
                $sql = "INSERT INTO $tabla ( uid_agrupador, uid_agrupamiento, $campo ) VALUES (
                    ". $this->getUID() .", ". $agrupamiento->getUID() .", '$valor'
                ) ON DUPLICATE KEY UPDATE $campo = '$valor' ";

                $this->db->query($sql);
                return ( !$this->db->lastError() ) ? true : false;
            }
        }

        public function cancelarReboteDesde(agrupador $agrupador){
            return $agrupador->configuracionAgrupamiento( $this->obtenerAgrupamientoPrimario(), "rebote" );
        }

        public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $data = array()){
            $info = parent::getInfo(true);
            $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

            switch($context){
                case Ilistable::DATA_CONTEXT_DESCARGABLES:

                    $lineData = array("nombre" => $this->getNombreTipo(true)
                        );

                    return array( $this->getUID() => $lineData );
                break;

                default:
                    $name = $this->getUserVisibleName();
                    $categoria = $this->obtenerAgrupamientoPrimario()->obtenerCategoria();
                    $lineData = array();
                    $lineData["nombre"] =  array(
                        "innerHTML" => $name,
                        "className" => "link",
                        "title" => $name,
                        "href" => "#profile.php?m=agrupador&poid={$this->getUID()}"
                    );

                    if( $categoria instanceof categoria ){
                        switch($categoria->getUID()){
                            case categoria::TYPE_INTRANET:
                                if ($usuario instanceof empleado) {
                                    $lineData["nombre"]["href"] = "#carpeta/listado.php?m=agrupador&poid={$this->getUID()}";
                                }

                            break;
                            default:break;
                        }
                    }




                    return array( $this->getUID() => $lineData );
                break;
            }
        }

        public function getCadenaAgrupamiento(){
            $cadena = array();
            $cadena[] = $this->obtenerAgrupamientoPrimario()->getUserVisibleName();
            $cadena[] = $this->getUserVisibleName();
            $cadenaString = implode(' - ', $cadena);
            return $cadenaString;
        }

        public static function getSearchData(Iusuario $usuario, $papelera = false, $all = false){
            $searchData = array();
            if (!$usuario->accesoModulo(__CLASS__)) return false;

            $usuario = ( $usuario instanceof perfil ) ? $usuario->getUser() /* $usurio es un perfil */ : $usuario;

            $filters = array();
            if( $all != true ){
                $empresa = $usuario->getCompany();

                $filters[] = " ( uid_empresa = {$empresa->getUID()} ) ";
            }

            if( is_bool($papelera) ){
                $filters[] = "papelera = ". ((int) $papelera);
            }

            if ($usuario->isViewFilterByLabel()) {
                $etiquetas = $usuario->obtenerEtiquetas();
                if( $etiquetas && count($etiquetas) ){
                    $filters[] = "uid_agrupador IN (
                        SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ."_etiqueta WHERE uid_etiqueta IN ({$etiquetas->toComaList()})
                    )";
                } else {
                    $filters[] = "uid_agrupador NOT IN (SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ."_etiqueta)";
                }
            }

            $filter = "uid_empresa AND uid_agrupamiento";
            // Si las comprobaciones anteriores nos dan algun filtro
            if( count($filters) ) $filter .= " AND " . implode(" AND ", $filters);



            $searchData[ TABLE_AGRUPADOR ] = array(
                "type" => "agrupador",
                "fields" => array("nombre"),
                "limit" => $filter,
                "accept" => array(
                    "tipo" => "agrupador",
                    "uid" => true,
                    "clase" => true,
                    "list" => true
                )
            );

            $searchData[TABLE_AGRUPADOR]['accept']['asignado'] = array(__CLASS__, 'onSearchByAsignado');

            $searchData[ TABLE_AGRUPADOR ]['accept']['estado'] = function($data, $filter, $param, $query){
                $value = reset($filter);

                $SQL = "";

                $subSql = "(
                            Select uid_agrupador from ". TABLE_AGRUPADOR ."_elemento ae INNER JOIN ". TABLE_EMPLEADO ."_empresa em
                            where uid_elemento = uid_empleado AND em.papelera = 0  AND ae.uid_modulo = 8 AND
                            ae.uid_agrupador = agrupador.uid_agrupador
                        ) ";

                if( $value == 'ok' || $value == 1 ){
                    $SQL .= " ( alerts = 0 ) AND uid_agrupador IN $subSql ";
                } else if( $value == 'error' || $value == 2 ) {
                    $SQL .= " ( alerts > 0 ) AND uid_agrupador IN $subSql ";
                } else if( $value == 'sin-asignar' || $value == 3) {
                    $SQL = "  uid_agrupador NOT IN $subSql ";
                }

                $SQL .= " AND uid_agrupamiento IN (
                        SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ."
                            WHERE uid_categoria = ". categoria::TYPE_TAREAS ." AND agrupamiento.uid_agrupamiento = agrupador.uid_agrupamiento
                        )";


                return $SQL;
            };

            return $searchData;
        }

        public function obtenerEmpresasCertificadas(){
            $sql = "SELECT uid_empresa FROM ". TABLE_CERTIFICACION . " WHERE uid_agrupador = ". $this->getUID() ." GROUP BY uid_empresa";
            $data = $this->db->query($sql, "*", 0);

            $empresas = array();
            if( count($data) ){
                foreach( $data as $uid ){
                    $empresas[] = new empresa($uid);
                }
            }
            return $empresas;
        }


        public function getCompanies(){
            $coleccion = new ArrayObjectList();

            $agrupamiento = $this->obtenerAgrupamientoPrimario();
            $coleccion[] = $agrupamiento->getCompany();

            return $coleccion;
        }

        public function obtenerEmpresasSolicitantes(usuario $usuario = null)
        {
            if (null === $usuario) {
                $company = $this->getCompany();
                return new ArrayObjectList([$company]);
            }

            if ($usuario->esStaff()) {
                return $usuario->getCompany()->obtenerEmpresasSolicitantes();
            } else {
                return new ArrayObjectList(array($usuario->getCompany()));
            }
        }

        /** OBTENER LOS ELEMENTOS ASIGNADOS A UN AGRUPADOR DADO **/
        public function obtenerElementosAsignados($modulo = false, $filtro = false, $limit = false, $usuario = null, $count = false)
        {
            $fields = $count ? "count(uid_elemento)" : "uid_elemento, uid_modulo";

            $sql = "SELECT {$fields}
            FROM ". TABLE_AGRUPADOR ."_elemento
            WHERE uid_agrupador = ". $this->getUID();

            if ($modulo) {
                if (is_string($modulo)) {
                    $uidmodulo = util::getModuleId($modulo);
                } else {
                    $uidmodulo = $modulo;
                }
                $sql .= " AND uid_modulo = $uidmodulo";
            }

            $userAsDomainEntity = $legacyUser = null;

            if ($usuario instanceof usuario) {
                $legacyUser = $usuario;
            }

            if ($usuario instanceof perfil) {
                $legacyUser = $usuario->getUser();
            }

            if ($legacyUser instanceof usuario) {
                $userAsDomainEntity = $legacyUser->asDomainEntity();
            }

            if ($modulo == 'empleado' || $modulo == 'maquina') {
                $relationtable = constant('TABLE_'.strtoupper($modulo)) . '_empresa';

                // Si se nos indica un filtro, y es una empresa, y los modulos a "recuperar" son empleado o empresa..
                if (isset($filtro) && $filtro instanceof empresa) {
                    $superior = $filtro->getModuleName();

                    $sql .= "
                        AND uid_elemento IN (
                            SELECT uid_{$modulo}
                            FROM {$relationtable}
                            WHERE uid_empresa = ".$filtro->getUID()."
                            AND papelera = 0
                        )
                    ";

                } else {

                    $owner = $this->getCompany();
                    $this->app['index.repository']->expireIndexOf(
                        $modulo,
                        $owner->asDomainEntity(),
                        $userAsDomainEntity,
                        true
                    );

                    $indexList = (string) $this->app['index.repository']->getIndexOf(
                        $modulo,
                        $owner->asDomainEntity(),
                        $userAsDomainEntity,
                        true
                    );

                    $sql .= " AND uid_elemento IN ({$indexList})";
                }
            } else {
                if (isset($filtro)) {
                    if ($filtro instanceof empresa) {
                        $sql .= " AND (
                                ( uid_modulo = 8 AND uid_elemento IN (SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa emp WHERE uid_empresa = {$filtro->getUID()} AND emp.uid_empleado = uid_elemento GROUP BY uid_empleado) )
                            OR  ( uid_modulo = 14 AND uid_elemento IN (SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa emp WHERE uid_empresa = {$filtro->getUID()} AND emp.uid_maquina = uid_elemento GROUP BY uid_maquina) )
                        )
                        ";
                    } elseif ($filtro === true) {
                        $owner = $this->getCompany();

                        $this->app['index.repository']->expireIndexOf(
                            'empresa',
                            $owner->asDomainEntity(),
                            $userAsDomainEntity,
                            true
                        );

                        $indexList = (string) $this->app['index.repository']->getIndexOf(
                            'empresa',
                            $owner->asDomainEntity(),
                            $userAsDomainEntity,
                            true
                        );

                        $sql .= " AND uid_elemento IN ({$indexList})";
                    }
                }

            }

            if ($usuario instanceof usuario) {
                if ($usuario->isViewFilterByGroups()) {
                    $modo = $usuario->perfilActivo()->obtenerDato("limiteagrupador_modo");

                    switch ($modo) {
                        case usuario::FILTER_VIEW_GROUP:
                            $sqlAgrupamientosUsuario = "
                                SELECT aa.uid_agrupamiento
                                FROM ". TABLE_AGRUPADOR ."_elemento ae
                                INNER JOIN ".TABLE_AGRUPAMIENTO."_agrupador aa ON ae.uid_agrupador = aa.uid_agrupador
                                WHERE 1
                                AND ae.uid_modulo = ".util::getModuleId("perfil")."
                                AND ae.uid_elemento = ".$usuario->perfilActivo()->getUID()."
                                GROUP BY aa.uid_agrupamiento
                            ";
                            $agrupamientos = $this->db->query($sqlAgrupamientosUsuario, "*", 0, "agrupamiento");

                            if (count($agrupamientos)) {
                                $filters = array();
                                foreach ($agrupamientos as $agrupamiento) {
                                    $filters[] = " uid_elemento IN (
                                        SELECT sub.uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento sub
                                        WHERE sub.uid_elemento = uid_elemento AND sub.uid_modulo = $uidmodulo
                                        AND sub.uid_agrupador IN (".$agrupamiento->obtenerAgrupadores($usuario)->toComaList().")
                                    )";
                                }
                            }
                            break;
                     }

                }
            }

            if (isset($filters) && is_array($filters)) {
                $sql .=" AND ". implode("AND", $filters);
            }

            if (is_array($limit)) {
                $sql .= " LIMIT ". $limit["sql_limit_start"] . ", ". $limit["sql_limit_end"];
            }

            if ($count) {
                return (int) $this->db->query($sql, 0, 0);
            }

            $coleccion = array();
            $data = $this->db->query($sql, true);

            $coleccion = array_map(function($line) {
                $m = util::getModuleName($line["uid_modulo"]);
                return new $m($line["uid_elemento"]);
            }, $data);

            return $coleccion;
        }

        private function obtenerClaveRelacion($objeto){
            $sql = "SELECT uid_agrupador_elemento
                            FROM ". TABLE_AGRUPADOR ."_elemento
                            WHERE uid_agrupador = $this->uid
                            AND uid_elemento = ". $objeto->getUID() ."
                            AND uid_modulo = ". $objeto->getModuleId() ."
            ";
            return $this->db->query($sql, 0, 0);
        }

        public function asignarRelacion($objeto, $uids){
            if( !count($uids) ) return true;
            $uidRelacion = $this->obtenerClaveRelacion($objeto);

            $inserts = $agrupadores = array();
            foreach( $uids as $uid ){
                $agrupador = new agrupador($uid);
                if( $objeto->asignarAgrupadores( elemento::getCollectionIds($agrupador->obtenerAgrupadores()), false, $uid) === false ){
                    return "error_asignar_rebotes";
                }
                $inserts[] = "( $uidRelacion, $uid  )";
            }


            $sql = "INSERT IGNORE INTO  ". TABLE_AGRUPADOR ."_elemento_agrupador ( uid_agrupador_elemento, uid_agrupador ) VALUES ". implode(", ", $inserts);
            return $this->db->query($sql);
        }


        /** ALIAS DE establecerBloqueoRelacion ( $elemento, 0 ); **/
        public function desBloquearRelacion($elemento){
            return $this->establecerBloqueoRelacion($elemento, 0);
        }

        /** ALIAS DE establecerBloqueoRelacion ( $elemento, 1 ); **/
        public function bloquearRelacion($elemento){
            return $this->establecerBloqueoRelacion($elemento, 1);
        }


        /** CAMBIA DE ESTADO BLOQUEADO / NO BLOQUEADO A LA RELACION ENTRE UN AGRUPADOR Y UN ELEMENTO **/
        protected function establecerBloqueoRelacion($elemento, $estado){
            $sql = "UPDATE ". TABLE_AGRUPADOR ."_elemento
                SET bloqueado = $estado
                WHERE uid_elemento = ". $elemento->getUID() ."
                AND uid_modulo = ". $elemento->getModuleId() ."
                AND uid_agrupador = ". $this->getUID() ."
            ";
            return $this->db->query($sql);
        }


        public function esBloqueado($elemento){
            $sql = "SELECT bloqueado FROM ". TABLE_AGRUPADOR ."_elemento
                WHERE uid_elemento = ". $elemento->getUID() ."
                AND uid_modulo = ". $elemento->getModuleId() ."
                AND uid_agrupador = ". $this->getUID() ."
            ";
            return $this->db->query($sql,0,0);
        }

        public function quitarRelacion($objeto, $uids){
            if( !count($uids) ) return true;
            $uidRelacion = $this->obtenerClaveRelacion($objeto);

            foreach( $uids as $uid ){
                $agrupador = new agrupador($uid);
                $asignadosAgrupador = $agrupador->obtenerAgrupadores();
                if( is_traversable($asignadosAgrupador) && count($asignadosAgrupador) ){
                    $objeto->quitarAgrupadores( elemento::getCollectionIds($asignadosAgrupador) );
                }
            }

            $sql = "DELETE FROM ". TABLE_AGRUPADOR ."_elemento_agrupador
                    WHERE uid_agrupador_elemento = $uidRelacion
                    AND uid_agrupador IN (". implode(", ", $uids) .")";
            return $this->db->query($sql);
        }

        public function obtenerAgrupadoresRelacionados(categorizable $objeto, agrupamiento $agrupamiento = NULL){
            $uid = $this->obtenerClaveRelacion($objeto);
            if (!$uid) return array();
            $sql = "SELECT uid_agrupador
                    FROM ". TABLE_AGRUPADOR ."_elemento_agrupador
                    WHERE uid_agrupador_elemento = $uid
            ";

            if( $agrupamiento instanceof agrupamiento ){
                $sql .= " AND uid_agrupador IN (
                    SELECT uid_agrupador
                    FROM ". TABLE_AGRUPADOR ."
                    WHERE uid_agrupamiento = ". $agrupamiento->getUID() ."
                )";
            }

            if( $coleccion = $this->db->query($sql, "*", 0, "agrupador") ){
                return new ArrayObjectList($coleccion);
            } else {
                return array();
            }
        }

        public function updateAlerts($n=false){
            try {
                $n = is_numeric($n) ? $n : count($this->getExceptionMessages());
                $update = $this->update( array("alerts" => $n), elemento::PUBLIFIELDS_MODE_CRONCALL, null );
                return true;
            } catch(Exception $e){
                return' Error! ' . $e->getMessage();
            }
        }

        public static function cronCall($time, $force = false){
            $m = date("i", $time);
            $h = date("H", $time);

            // Solo lanzar 1 vez cada hora a los 10min
            if( $m != 10 && $force === false ){ return true; }

            // self::desasignarAgrupadoresPapelera();

            define("NO_CACHE_OBJECTS", TRUE);

            $sql = "SELECT uid_agrupador FROM ". TABLE_AGRUPADOR . " WHERE papelera = 0 ORDER BY uid_agrupador DESC";
            $items = db::get($sql, "*", 0, "agrupador");

            foreach($items as $i => $agrupador){
                echo $i ."[{$agrupador->getUID()}]: ". $agrupador->getUserVisibleName() . "..";

                if( $h == 20 || $force ){
                    $updateAlerts = $agrupador->updateAlerts();
                    if( $updateAlerts !== true ) return $updateAlerts;
                }

                if( !$agrupador->aplicarRebotes() ){
                    echo " no! \n";
                } else {
                    echo " ok! \n";
                }
            }

            echo "\n";

            return true;
        }

        public static function desasignarAgrupadoresPapelera() {
            $sql = " SELECT uid_agrupador FROM ".TABLE_AGRUPADOR." WHERE papelera=1 ";
            $db = db::singleton();
            $agrupadores = new ArrayObjectList($db->query($sql,'*',0,'agrupador'));
            $agrupadores->foreachCall('clear');
        }

        public function aplicarRebotes(){

            // Actualizamos los rebotes por relacion
            $sql = "INSERT IGNORE INTO ". TABLE_AGRUPADOR ."_elemento ( uid_agrupador, uid_modulo, uid_elemento, rebote )
                SELECT agr.uid_agrupador, 1, ae.uid_elemento, aea.uid_agrupador
                    FROM ". TABLE_AGRUPADOR ."_elemento ae
                    INNER JOIN ". TABLE_AGRUPADOR ."_elemento_agrupador aea
                    USING (uid_agrupador_elemento)
                    INNER JOIN ". TABLE_AGRUPADOR ."_elemento agr
                    ON aea.uid_agrupador = agr.uid_elemento
                WHERE aea.uid_agrupador = " . $this->getUID() ."
                AND agr.uid_modulo = 11";

            if( !$this->db->query($sql) ){ echo "<br>Error al asignar los rebotes!!<br><br>"; return false; }

            $asignados = $this->obtenerAgrupadores();

            $moduloEmpleado = util::getModuleId("empleado");
            $moduloMaquina = util::getModuleId("maquina");

            $empresasPropiasEmpleado = "SELECT uid_empresa FROM ". TABLE_EMPLEADO ."_empresa where uid_empleado = uid_elemento AND papelera = 0";
            $empresasPropiasMaquina = "SELECT uid_empresa FROM ". TABLE_MAQUINA ."_empresa where uid_maquina = uid_elemento AND papelera = 0";

            $empresasVisiblesEmpleado = "SELECT uid_empresa FROM ". TABLE_EMPLEADO ."_visibilidad
                                                    where uid_empleado = uid_elemento
                                        UNION
                                        SELECT if(startIntList.uid_empresa_inferior IS NULL, v.uid_empresa, startIntList.uid_empresa) FROM ". TABLE_EMPLEADO ."_visibilidad v
                                            LEFT JOIN (
                                                SELECT empresa.uid_empresa, uid_empresa_inferior FROM ". TABLE_EMPRESA ."_relacion er
                                                INNER JOIN ". TABLE_EMPRESA."  ON empresa.uid_empresa = er.uid_empresa_superior where activo_corporacion = 1
                                            ) as startIntList ON v.uid_empresa = startIntList.uid_empresa_inferior
                                            where uid_empleado = uid_elemento
                                        ";


            $empresasVisiblesMaquina = "SELECT uid_empresa FROM ". TABLE_MAQUINA ."_visibilidad
                                                    where uid_maquina = uid_elemento
                                        UNION
                                        SELECT if(startIntList.uid_empresa_inferior IS NULL, v.uid_empresa, startIntList.uid_empresa) FROM ". TABLE_MAQUINA ."_visibilidad v
                                            LEFT JOIN (
                                                SELECT empresa.uid_empresa, uid_empresa_inferior FROM ". TABLE_EMPRESA ."_relacion er
                                                INNER JOIN ". TABLE_EMPRESA."  ON empresa.uid_empresa = er.uid_empresa_superior where activo_corporacion = 1
                                            ) as startIntList ON v.uid_empresa = startIntList.uid_empresa_inferior
                                            where uid_maquina = uid_elemento
                                        ";

            $visibilidadEmpleado = " (uid_modulo = $moduloEmpleado AND (a.uid_empresa IN ($empresasVisiblesEmpleado) OR a.uid_empresa IN ($empresasPropiasEmpleado))) ";
            $visibilidadMaquina = " (uid_modulo = $moduloMaquina AND (a.uid_empresa IN ($empresasVisiblesMaquina) OR a.uid_empresa IN ($empresasPropiasMaquina))) ";
            $others = " (uid_modulo != $moduloEmpleado AND uid_modulo != $moduloMaquina) ";

            $organization = $this->getOrganization();
            $hasBounceAssignToUser = (bool) $organization->obtenerDato('bounce_assign_user');

            if ($asignados && count($asignados)) {
                foreach($asignados as $asignado){

                    // no asignamos los de anclaje
                    if ($asignado->esAnclaje()) {
                        continue;
                    }

                    $sql = "INSERT IGNORE INTO ". TABLE_AGRUPADOR ."_elemento ( uid_agrupador, uid_elemento, uid_modulo, rebote )
                        SELECT ".$asignado->getUID().", uid_elemento, uid_modulo, ". $this->getUID() ."
                        FROM ". TABLE_AGRUPADOR ."_elemento ae
                        INNER JOIN ". TABLE_AGRUPADOR ." a using(uid_agrupador)
                        WHERE uid_agrupador = ". $this->getUID() ."
                        AND ( ( uid_modulo = 11 && uid_elemento != ".$asignado->getUID()." ) OR ( uid_modulo != 11 ) )
                        AND ( $visibilidadEmpleado OR $visibilidadMaquina OR $others )
                    ";

                    if ($hasBounceAssignToUser) {
                        $sql .= " AND uid_modulo != 16";
                    }
                    if( !$this->db->query($sql) ){ echo "<br>Error al asignar los rebotes #2!!<br><br>"; return false; }
                }
            }

            return true;
        }

        /**
            BORRA TODAS LAS ASIGNACIONES DE ESTE AGRUPADOR PARA SUS ELEMENTOS
        */
        public function clear(){
            //quitar rebotes de las relaciones
            $sql = "DELETE ". TABLE_AGRUPADOR ."_elemento FROM  ". TABLE_AGRUPADOR ."_elemento INNER JOIN (
                SELECT (
                    SELECT sub.uid_agrupador_elemento
                    FROM  ". TABLE_AGRUPADOR ."_elemento sub
                    WHERE sub.uid_elemento = ae.uid_elemento
                    AND sub.uid_agrupador = agr.uid_agrupador
                ) as uid_agrupador_elemento
                FROM  ". TABLE_AGRUPADOR ."_elemento ae
                INNER JOIN  ". TABLE_AGRUPADOR ."_elemento_agrupador aea
                USING (uid_agrupador_elemento)
                INNER JOIN  ". TABLE_AGRUPADOR ."_elemento agr
                ON agr.uid_elemento = aea.uid_agrupador
                WHERE ae.uid_agrupador = ". $this->getUID() ."
            ) t USING ( uid_agrupador_elemento )
            WHERE bloqueado = 0;
            ";
            if( !$this->db->query($sql) ){
                die( $this->db->lastError() );
            }

            // rebotes directos
            $sql = "DELETE FROM ". TABLE_AGRUPADOR ."_elemento WHERE rebote = ".   $this->getUID() . " AND bloqueado = 0";
            $this->db->query($sql);

            //las propias asignaciones
            $sql = "DELETE FROM ". TABLE_AGRUPADOR ."_elemento WHERE uid_agrupador = ". $this->getUID() ." AND bloqueado = 0";
            $result = $this->db->query($sql);
            return ( $result ) ? $this->db->getAffectedRows() : false;
        }


        public function obtenerDocumentoAtributos($activo = false, $filter = null){
            $array = $this->obtenerDocumentos($activo, $filter);
            $attrs = new ArrayObjectList();
            foreach( $array as $line ){
                $attrs[] = new documento_atributo($line["uid_documento_atributo"]);
            }
            return $attrs;
        }

        public function obtenerNumeroDocumentos ($activo, $filter) {
            return $this->obtenerDocumentos($activo, $filter, true);
        }

        public function obtenerDocumentos ($activo = false, $filter = NULL, $count = false) {
            $fcache = ($filter) ? json_encode($filter) : '';

            if (($cacheKey = implode('-', array(__FUNCTION__, $this, $activo, $count, $fcache))) && ($val = $this->cache->getData($cacheKey)) !== null) {
                if ($count) return $val;
                return $val ? json_decode($val, true) : false;
            }

            $tpl = Plantilla::singleton();


            if ($count) {
                $fields = array("count(uid_documento_atributo) as num");
            } else {
                $nocaduca = db::scape($tpl('no_caduca'));
                $obligatorio = db::scape($tpl('obligatorio'));
                $opcional = db::scape($tpl('opcional'));
                $subir = db::scape($tpl('subir'));
                $descargar = db::scape($tpl('descargar'));

                $fields = array(
                    "uid_documento_atributo",
                    "alias",
                    "if (obligatorio, '{$obligatorio}', '{$opcional}') as obligatorio",
                    "if (descargar, '{$descargar}', '{$subir}') as descargar",
                    "uid_elemento_origen",
                    "uid_modulo_origen",
                    "uid_modulo_destino",
                    "if (duracion , duracion, '{$nocaduca}') duracion"
                );
            }

            $sql = "
                SELECT ". implode(", ", $fields) ." FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
                WHERE (uid_modulo_origen = 11 AND uid_elemento_origen = {$this->getUID()})
            ";

            if ($activo) $sql .= " AND activo = 1";

            if (is_traversable($filter)) {
                foreach($filter as $field => $value ){
                    $val = is_numeric($value) ? $value : '$value';
                    $sql .= " AND $field = {$val}";
                }
            }

            if ($filter instanceof usuario) {

                if ($filter->isViewFilterByLabel()) {
                    $labels = $filter->obtenerEtiquetas();
                    $table  = TABLE_DOCUMENTO_ATRIBUTO . '_etiqueta';

                    if (count($labels)) {
                        $list = $labels->toComaList();

                        $sql .= " AND uid_documento_atributo IN (
                            SELECT uid_documento_atributo FROM {$table} WHERE uid_etiqueta IN ({$list})
                        )";
                    } else {
                        $sql .= " AND uid_documento_atributo NOT IN (
                            SELECT uid_documento_atributo FROM {$table}
                        )";
                    }
                }

            }

            // si solo es contar, return
            if ($count) {
                $num = $this->db->query($sql, 0, 0);
                $this->cache->set($cacheKey, $num, 60*60*15);
                return $num;
            }

            $info = $this->db->query($sql, true);

            $locale = Plantilla::getCurrentLocale();
            if ($locale != "es") {
                $i=0;
                foreach ($info as $dato){

                    $objetoDocumento = new documento_atributo($dato["uid_documento_atributo"]);
                    $documentoIdioma = new traductor( $dato["uid_documento_atributo"], $objetoDocumento );
                    $aliasLocale = $documentoIdioma->getLocaleValue($locale);

                    if (trim($aliasLocale)) {
                        $alias = $aliasLocale;
                        $info[$i]["alias"] = $alias;
                    }

                    $i=$i+1;
                }
            }


            if (!is_array($info)) $return = array();
            else $return = array_map("utf8_multiple_encode", $info);

            $this->cache->set($cacheKey, json_encode($return), 60*60*15);
            return $return;
        }

        public function getClickURL(Iusuario $usuario = NULL, $config = false, $data = NULL){
        }

        /* */
        public function getInlineArray(Iusuario $usuario = NULL, $config = false, $data = NULL){
            $inlineArray = array();
            $deep=0;
            $contador=0;
            $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

            switch($context){
                case Ilistable::DATA_CONTEXT_DESCARGABLES:
                    $lang = Plantilla::singleton();
                    $inlineArray[] = array(

                            "style" => "text-align: left",
                            "img" =>RESOURCES_DOMAIN . "/img/famfam/arrow_right.png",
                            array(
                                "tagName" => "span",
                                "nombre" => $this->getCompany()->obtenerDato('nombre')
                            )
                        );
                    return $inlineArray;
                break;

                default:
                if( $usuario instanceof usuario){
                    if( $numeroAlarmas = $this->getAlarmCount($usuario) ){
                        $alarms = array( "img" => RESOURCES_DOMAIN . "/img/famfam/bell.png" );
                        $options = $usuario->getAvailableOptionsForModule($this->getModuleId(), "carpetas");
                        if( $accion = reset($options) ){
                            $href = $accion["href"] . "&show=alarmas&poid=" . $this->getUID() ;

                            $alarms[] = array(
                                "nombre" =>  "Alarmas: $numeroAlarmas",
                                "href" => $href
                            );

                            $inlineArray["0"] = $alarms;
                        }
                    } else {
                        $inlineArray["0"] = array(array()); //no descuadrar la tabla
                    }


                    if( $asignacionesAsignadas = $this->obtenerAgrupadores() ){
                        $arrayAsignado = array(
                            "img" => RESOURCES_DOMAIN . "/img/famfam/table_relationship.png",
                            "className" => "extended-cell",
                            "href" => "agrupamiento/infoasignaciones.php?oid=" . $this->getUID()
                        );

                        foreach( $asignacionesAsignadas as $agrupador ){
                            // se verifica que el usuario tenga acceso al elemento que va a clickar
                            if( $usuario->accesoElemento($agrupador) ){
                                if( $deep<2 ){
                                    $deep++;
                                    $arrayAsignado[] = array( "nombre" =>  $agrupador->getUserVisibleName() );
                                } else {
                                // jcal 12-05-2011 -> si un agrupador tiene más asignaciones asignadas de las que se muestran por línea:
                                    $contador++;
                                }
                            }
                        }

                        if( $contador != 0 ){
                            $arrayAsignado[] = array( "nombre" =>  "+" . $contador );
                        }

                        $inlineArray["1"] = $arrayAsignado;
                    } else {
                        $inlineArray["1"] = array(array()); //no descuadrar la tabla
                    }
                }
                return $inlineArray;
                break;
            }
        }

        /* DEPRECATED: Usar getNombreTipo en vez de esta */
        public function getTypeString(){
            return $this->getNombreTipo();
            /*
            $agrupamientos = $this->obtenerAgrupamientosContenedores();
            $names = array();
            foreach($agrupamientos as $agrupamiento){
                $names[] = $agrupamiento->getUserVisibleName();
            }
            return implode(", ", $names);
            */
        }


        public function getUserVisibleName(){
            // performance only
            if (isset($this->name)) {
                return $this->name;
            }

            $locale = Plantilla::getCurrentLocale();
            if( $locale != "es" ){
                $agrupadorIdioma = new traductor( $this->getUID(), $this );
                $nombre = $agrupadorIdioma->getLocaleValue( $locale );
            }else{
                $info = $this->getInfo();
                $nombre = $info["nombre"];
            }

            if( !isset($nombre) || !trim($nombre) ){
                $nombre = $this->obtenerDato('nombre');
            }

            return $nombre;
        }

        /**
         * Get description of agrupador
         * @return string
         */
        public function getDescription()
        {
            return $this->obtenerDato('description');
        }

        /*
        public function getInfo( $publicMode = false, $comeFrom = null, $usuario=false, $force = false){
            $info = parent::getInfo( $publicMode, $comeFrom, $usuario, $force );

            if( $comeFrom == "table" ){
                $data =& $info[ $this->uid ];
                $data["nombre"] = array(
                    "innerHTML" => string_truncate($data["nombre"], 100),
                    "title" => $data["nombre"],
                    "translate" => true
                );
            }

            return $info;
        }*/

        public function accesiblePara(usuario $usuario){
            if( $usuario instanceof usuario ){

                if( $usuario->isViewFilterByGroups() ){
                    $agrupadores = $usuario->obtenerAgrupadores();
                    //$agrupadores->toUL();exit;
                    if( !in_array($this->getUID(), $agrupadores->toIntList()->getArrayCopy() ) ){
                        return false;
                    }
                }


                // El agrupador tiene nuestras etiquetas?
                if( $usuario->isViewFilterByLabel() ){
                    $etiquetas = $usuario->obtenerEtiquetas();
                    if( count($etiquetas) ){
                        $arrayEtiquetas = $etiquetas->toIntList()->getArrayCopy();
                        $etiquetasAgrupador = $this->obtenerEtiquetas();
                        $pass = false;

                        foreach($etiquetasAgrupador as $etiquetaAgrupador){
                            if( in_array($etiquetaAgrupador->getUID(), $arrayEtiquetas) ){
                                $pass = true;
                                break;
                            }
                        }

                        if( $pass === false ) return false;
                    }
                }

                return $this->obtenerAgrupamientoPrimario()->accesiblePara($usuario);
            }

            return false;
        }

        public function obtenerAgrupamientosContenedores(){
            $sql = "SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ."_agrupador WHERE uid_agrupador = '". $this->getUID() ."'";
            $datosAgrupamientos = $this->db->query($sql, true);
            if( is_array($datosAgrupamientos) && count($datosAgrupamientos) ){
                $agrupamientos = array();
                foreach($datosAgrupamientos as $lineaAgrupamiento){
                    $agrupamientos[] = new agrupamiento($lineaAgrupamiento["uid_agrupamiento"]);
                }
                return $agrupamientos;
            }
            return false;
        }

        public function isDeactivable($parent, usuario $usuario){
            return true;
        }

        public function obtenerElementosActivables(usuario $usuario = NULL){
            return array($this->obtenerAgrupamientoPrimario());
        }

        public function obtenerElementosSuperiores(){
            return array($this->obtenerAgrupamientoPrimario());
        }

        public function getAbbr(){
            $info = $this->getInfo();
            return $info["abbr"];
        }

        public function getType(){
            return $this->tipo;
        }



        public function aplicarEn($elemento, $usuario){
            $carpetas = $this->obtenerCarpetas();

            foreach( $carpetas as $carpeta ){
                if( !$this->replicacionRecursiva($carpeta, $elemento, $usuario) ){
                    // deberia arrojar una excepcion
                }
            }

            return true;
        }


        /** COPIA UNA ESTRUCTURA A UN AGRUPADOR, RECURSIVAMENTE */
        private function replicacionRecursiva($carpeta, $padre, $usuario){
            $informacionCarpeta = $carpeta->getInfo();
            array_shift( $informacionCarpeta );

            $nuevaCarpeta = new carpeta( $informacionCarpeta, $usuario );
            if( !$nuevaCarpeta->guardarEn( $padre ) ){
                $nuevaCarpeta->eliminar();
                return false;
            }

            if( $documentos = $carpeta->obtenerDocumentos() ){
                //$uids = elemento::getCollectionIds($documentos);
                foreach( $documentos as $documento ){
                    if( !$this->crearRelacion( TABLE_CARPETA . "_documento", "uid_carpeta", $nuevaCarpeta->getUID(), "uid_documento", $documento->getUID() ) ){
                        //dump("Error al crear la relacion");exit;
                    }
                }
            }

            //dump("Carpeta ". $nuevaCarpeta->getUID() . " creada en ". $padre->getType() ." con id ". $padre->getUID());

            $ficheros = $carpeta->obtenerFicheros();
            foreach( $ficheros as $fichero ){
                $informacionFichero = array();
                $informacionFichero = $fichero->getInfo();
                array_shift( $informacionFichero );

                $nuevoFichero = new fichero( $informacionFichero, $usuario );
                if( !$nuevoFichero->guardarEn( $nuevaCarpeta ) ){
                    $nuevoFichero->eliminar();
                    return false;
                }
            }


            $subcarpetas = $carpeta->obtenerCarpetas();
            foreach( $subcarpetas as $subcarpeta ){
                if( !$this->replicacionRecursiva($subcarpeta, $nuevaCarpeta, $usuario) ){
                    return false;
                }
            }

            return true;
        }

        public function getSelectName($fn=false){
            //$tpl = Plantilla::singleton();
            $name = $this->getUserVisibleName();
            return $name . " - ". $this->getTypeString();
        }

        public function getAllFiles($returnSQL=false, $alarm=false, Iusuario $usuario = null){
            $carpetas = $this->obtenerCarpetas(true, 0, $usuario);
            if( !count($carpetas) ){ return false; }


            $sql = "SELECT uid_fichero FROM ". TABLE_FICHERO ."_carpeta WHERE uid_carpeta IN (
                ". implode(",", elemento::getCollectionIds($carpetas) ) ."
            )";

            //modulo 26 - Fichero
            if( $alarm ){
                $sql .= " AND uid_fichero IN (
                    SELECT uid_elemento FROM ". TABLE_ALARMA_ELEMENTO ." WHERE uid_modulo = 26
                )";
            }

            $sql .= " GROUP BY uid_fichero";

            if( $returnSQL ) { return $sql; }

            $uids = $this->db->query($sql, "*", 0);

            $ficheros = array();
            foreach($uids as $uid){
                $fichero = new fichero($uid);
                $ficheros[] = $fichero;
            }
            return $ficheros;
        }

        public function getAlarmCount(Iusuario $usuario = NULL){
            $sqlFicheros = $this->getAllFiles(true, false, $usuario);

            if( !$sqlFicheros ){ return false; }

            $sql = "
                SELECT count(a.uid_alarma_elemento) FROM ".TABLE_ALARMA_ELEMENTO." a
                WHERE a.uid_modulo = 26
                AND a.uid_elemento IN ( $sqlFicheros )
            ";
            $alarmas = $this->db->query($sql, 0, 0);

            return $alarmas;
        }


        public function getEmpresaCliente(){
            $cacheString = __CLASS__ . "-" . __METHOD__ . "-{$this}";
            if( ($value = $this->cache->get($cacheString)) !== null ) return new empresa($value);

            $agrupamiento = $this->obtenerAgrupamientoPrimario();
            $empresa = reset($agrupamiento->getEmpresasClientes());

            $this->cache->addData($cacheString, $empresa->getUID());
            return $empresa;
        }

        /* Función alias que necesitamos para estandarizar */
        public function obtenerEmpresaContexto($usuario){
            return reset($this->obtenerEmpresasSolicitantes($usuario));
        }

        /** NOS RETORNARA UN CONJUNTO DE OBJETOS CARPETA... */
        public function obtenerCarpetas($recursive=false, $level=0, Iusuario $usuario = NULL){
            $coleccionCarpetas = array();

            $sql = "SELECT uid_carpeta FROM ". TABLE_CARPETA ."_agrupador
                    INNER JOIN ". TABLE_CARPETA ."
                    USING( uid_carpeta )
                    WHERE uid_agrupador = $this->uid
                    ORDER BY nombre
            ";
            $ids = $this->db->query($sql, "*", 0);

            foreach($ids as $uid){
                $carpeta = new carpeta($uid);
                if( $recursive ){
                    $subCarpetas = $carpeta->obtenerCarpetas(true, ($level+1), $usuario);
                    $coleccionCarpetas = array_merge($subCarpetas->getArrayCopy(), $coleccionCarpetas);
                }
                $coleccionCarpetas[] = $carpeta;
            }

            $coleccionCarpetas = carpeta::filtrarNoVisibles($coleccionCarpetas, $usuario);
            if( $recursive ){
                // $coleccionCarpetas = elemento::
            }
            return $coleccionCarpetas;
        }

        public function getNombreTipo($self = false){
            $nombreTipo = $this->obtenerAgrupamientoPrimario()->getUserVisibleName();
            $nombreTipo = $self ? $nombreTipo . " - " . $this->getUserVisibleName() : $nombreTipo;
            return $nombreTipo;
        }

        /* NOS RETORNA EL PRIMERO AGRUPAMIENTO AL QUE PERTENECE */
        public function obtenerAgrupamientoPrimario(){
            $uid = $this->obtenerDato("uid_agrupamiento");
            return new agrupamiento($uid);
        }

        public function getOrganization(){
            return $this->obtenerAgrupamientoPrimario();
        }

        /**
         * Get the group coordinator
         * @return false|empresa Returns the cordinator company, false if the group hasn't coordinator
         */
        public function getCoordinator()
        {
            $database   = db::singleton();
            $groups     = TABLE_AGRUPADOR;

            $sql = "SELECT uid_coordinator_company
            FROM {$groups}
            WHERE uid_agrupador = {$this->uid}";

            $uid = $database->query($sql, 0, 0);

            // the group hasn't coordinator
            if ($uid === null) {
                return false;
            }

            return new empresa($uid);
        }

        /**
            DEPRECATED!!!!! XD
            ACTUALIZADO uid_manager POR uid_usuario
        **/
        public function obtenerManager(){
            $uid = $this->obtenerDato("uid_manager");
            if( $uid ){
                return new usuario($uid);
            }
            return false;
        }

        public function getIcon($htmlimg=true, $title = false){
            $cacheString = 'icono-agrupador-'. $this->uid .'-'.$htmlimg;
            $estado = $this->cache->getData($cacheString);
            if( $estado !== null ){
                return $estado;
            }

            $info = $this->getInfo();
            $icon = $info["icono"];


            // En la bbdd guardamos la url completa, debemos eliminar esto y solo guardar la ruta relativa
            // de momento mientras hacemos el cambio y para hacerlo facil reemplazamos cualquier dominio por el RESOURCES_DOMAIN
            if( $icon ){
                $icon = RESOURCES_DOMAIN . substr($icon, strpos($icon, '/img/'));
            }

            if ($title !== null) {
                $title = ($title) ? $title : $this->getUserVisibleName();
            }

            if( $htmlimg ){
                $result = "<img src='$icon' ". ($title?"title='". $title ."'":"") ." width='16' height='16' />";
            } else {
                $result = $icon;
            }

            $this->cache->addData( $cacheString, $result );

            return $result;
        }

        public function availableForModule($uidModulo){
            $sql = "SELECT count(uid_agrupador)
                        FROM ". TABLE_AGRUPAMIENTO ."_agrupador aa
                        INNER JOIN ". TABLE_AGRUPAMIENTO ."_modulo am
                        USING ( uid_agrupamiento )
                        WHERE aa.uid_agrupador = $this->uid
                        AND uid_modulo = $uidModulo";
            return ( $this->db->query($sql) ) ? true : false;
        }
        /*
        public static function crear($informacion){
            return self::_crear($informacion, __CLASS__ );
        }
        */
        public static function getExportSQL($usuario, $uids, $forced, $parent=false){

            $campos = array();
            if( $usuario->esStaff() ){
                $campos[] = "uid_agrupador";
            }

            $campos[] = "nombre";
            $campos[] = "abbr";
            $sql =  "SELECT ". implode(",", $campos) ." FROM ". TABLE_AGRUPADOR ." WHERE 1";

            if( is_array($uids) && count($uids) ){
                $sql .=" AND uid_agrupador in (". implode(",", $uids ) .")";
            } else {
                if( is_numeric($parent) ){
                    $sql .=" AND uid_agrupamiento = $parent";
                }
            }

            if( is_array($forced) && count($forced) ){
                $sql .=" AND uid_agrupador IN (". implode(",", $forced) .")";
            }


            return $sql;
        }


        public function getPatron(){
            $agrupamiento = $this->obtenerAgrupamientoPrimario();
            return $agrupamiento->obtenerDato("patron");
        }

        public function esFilter(){
            $agrupamiento = $this->obtenerAgrupamientoPrimario();
            return $agrupamiento->configValue("filter");
        }

        public function esAnclaje(){
            $agrupamiento = $this->obtenerAgrupamientoPrimario();
            return $agrupamiento->configValue("anclaje");
        }

        public function esJerarquia(){
            $agrupamiento = $this->obtenerAgrupamientoPrimario();
            return $agrupamiento->configValue("jerarquia");
        }

        public function esPago(){
            $agrupamiento = $this->obtenerAgrupamientoPrimario();
            return $agrupamiento->configValue("pago");
        }

        /**
            NOS DA UNA COLECCION DE LOS AGRUPAMIENTOS QUE TIENEN ANCLAJE Y ESTAN A SU VEZ DISPONIBLES
            PARA LA RELACION DE ESTE AGRUPADOR
        **/
        public function agrupamientosAnclados(){
            $orgs   = TABLE_AGRUPAMIENTO;
            $groups = TABLE_AGRUPADOR;

            $sql = "SELECT uid_agrupamiento_inferior
            FROM {$groups}
            INNER JOIN {$orgs} a
            USING (uid_agrupamiento)
            INNER JOIN {$orgs}_agrupamiento
            ON a.uid_agrupamiento = uid_agrupamiento_superior
            WHERE uid_agrupador = {$this->getUID()}
            AND uid_agrupamiento_inferior = (
                SELECT uid_agrupamiento
                FROM {$orgs} sub
                WHERE 1
                AND sub.uid_agrupamiento = uid_agrupamiento_inferior
                AND sub.config_anclaje = 1
            )";

            $uids = $this->db->query($sql, "*", 0);

            $coleccion = array();
            foreach($uids as $uid){
                $coleccion[] = new agrupamiento($uid);
            }

            return $coleccion;
        }


        /*
            NOS INDICA QUE SUB MODULOS TIENE ESTE OBJETO PARA EN CIERTOS LUGARES PODER MOSTRAR LA INFORMACION AGRUPADA
        */
        public static function getSubModules(){
            $modulos = array(util::getModuleId("carpeta"), util::getModuleId("fichero"));
            return $modulos;
        }

        public static function status2img($intStatus, $title=""){

            switch($intStatus){
                case 0:
                    return '<img src="'. RESOURCES_DOMAIN .'/img/common/agrupador_null.png" title="'.$title.'" />';
                break;
                case 2:
                    return '<img src="'. RESOURCES_DOMAIN .'/img/common/agrupador_ok.png" title="'.$title.'" />';
                break;
                case 4:
                    return '<img src="'. RESOURCES_DOMAIN .'/img/common/agrupador_ko.png"  title="'.$title.'" />';
                break;
            }
        }

        public static function importFromFile($file, $agrupamiento, $usuario, $post = null)
        {
            // Objeto database
            $db = db::singleton();

            // Importamos los elementos a la tabla
            $results = self::importBasics($usuario,$file,"agrupador","nombre");

            if( count($results["uids"]) ){
                $empresaUsuario = $usuario->getCompany();

                //Relacionamos los elementos con nuestra empresa
                $sql = "UPDATE ". TABLE_AGRUPADOR ." SET
                    uid_agrupamiento = {$agrupamiento->getUID()},
                    uid_empresa = {$empresaUsuario->getUID()},
                    uid_usuario = {$usuario->getUID()}
                    WHERE uid_agrupador IN (". implode(",", $results["uids"]) .")";


                if( $db->query($sql) ){
                    return $results;
                } else {
                    throw new Exception( "Error al tratar de relacionar" );
                }
            } else {
                throw new Exception( "No hay elementos para relacionar" );
            }
        }

        public static function optionsFilter( $uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null ){
            $class = get_called_class();

            if( $class === __CLASS__ ){
                $condiciones = array();

                if( is_numeric($uidelemento) ){
                    $item = new self($uidelemento);
                    if( $agrupamiento = $item->obtenerAgrupamientoPrimario() ){
                        $categoria = $agrupamiento->obtenerCategoria();

                        if( !$categoria instanceof categoria || (int) $categoria->getUID() !== categoria::TYPE_CLIENTES ){
                            $condiciones[] = " uid_accion != 125"; // sin centros de cotizacion
                        }


                        if( !$categoria instanceof categoria || !in_array($categoria->getUID(), categoria::solicitanEpis()) || !$user->getCompany()->isEnterprise() ){
                            $condiciones[] = " uid_accion != 161"; // sin asignacion de epis
                        }

                        if ($user instanceof usuario){
                            $agrupamientosPropios = $user->getCompany()->obtenerAgrupamientosPropios(array($user));

                            if( !$agrupamientosPropios||!$agrupamientosPropios->contains($agrupamiento) ){
                                $condiciones[] = " uid_accion NOT IN (5,12,13,14,20,31,43,49,161) ";
                            }
                        }
                    }
                }

                if( $parent instanceof agrupamiento ){
                    $modulos = $parent->obtenerModulos(util::getModuleId("certificacion"));

                    if( !count($modulos) ){
                        $condiciones[] = " uid_accion != 42"; // sin informes de certificacion
                    }

                    if( !$parent->configValue("al_vuelo") ){
                        $condiciones[] = " uid_accion != 42"; // sin informes de certificacion
                    }


                }

                if( count($condiciones) ){
                    return "AND " . implode(" AND ", array_unique($condiciones) );
                }
            }

            return false;
        }

        public function esReplicable($module){
            return (bool) $this->obtenerAgrupamientoPrimario()->configValue("replica_".$module);
        }

        public function actualizarTiposEpi() {
            return $this->actualizarTablaRelacional($this->tabla ."_tipo_epi", "tipo_epi");
        }

        public function obtenerTiposEpi(){
            $sql = "SELECT uid_tipo_epi FROM $this->tabla"."_tipo_epi WHERE uid_agrupador = $this->uid";
            $datos = $this->db->query($sql, "*", 0, 'tipo_epi');
            return new ArrayObjectList($datos);
        }

        public static function getFromWhere($field, $value, $tipo, $condicion = NULL){
            if( $condicion instanceof agrupamiento ){
                $condicion = " uid_agrupamiento = {$condicion->getUID()} ";
            }
            return parent::getFromWhere($field, $value, $tipo, $condicion);
        }


        public static function defaultData($data, Iusuario $usuario = NULL){
            if( isset($data["poid"]) ){
                $agrupamiento = new agrupamiento($data["poid"]);

                $data["uid_agrupamiento"] = $agrupamiento->getUID();
                $data["uid_empresa"] = $agrupamiento->getCompany()->getUID();
                $data["uid_usuario"] = $usuario->getUID();

            } else {
                throw new Exception("agrupamiento_no_especificado");
            }

            return $data;
        }

        public static function conditionSQL ($conditions = array()) {
            $filters = array();
            if (is_traversable($conditions) && count($conditions)) {
                foreach($conditions as $key => $condition){
                    if (is_string($condition)) {
                        switch ($condition) {
                            case "!config_al_vuelo":
                                $filters[] = "uid_agrupamiento IN (SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ."  WHERE config_al_vuelo = 0)";
                            break;

                            // medio hack para poder consultar los agrupadores que tengan CarpetasDocumentosDescargables
                            case "has=folder":
                                $filters[] = "uid_agrupador IN (SELECT uid_agrupador FROM ". TABLE_CARPETA ."_agrupador ca WHERE ca.uid_agrupador = agrupador.uid_agrupador)";
                            break;

                            default:
                                $filters[] = $condition;
                            break;
                        }

                    } elseif ($condition instanceof categoria) {
                        $filters[] = 'uid_agrupamiento IN (SELECT uid_agrupamiento FROM '.TABLE_AGRUPAMIENTO.' WHERE uid_categoria='. $condition->getUID().') ';

                    } elseif ($condition instanceof usuario) {

                        if ($condition->isViewFilterByGroups()) {
                            $modulo = util::getModuleId('perfil');
                            $perfil = $condition->obtenerPerfil();

                            $filters[] = "uid_agrupador IN (
                                SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ."_elemento
                                WHERE uid_agrupador = agrupador.uid_agrupador AND uid_elemento = {$perfil->getUID()} AND uid_modulo = {$modulo}
                            )";
                        }


                        if ($condition->isViewFilterByLabel()) {
                            $etiquetas = $condition->obtenerEtiquetas();
                            if (count($etiquetas)) {
                                $filters[] ="uid_agrupador IN (
                                    SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ."_etiqueta
                                    WHERE uid_agrupador = agrupador.uid_agrupador AND uid_etiqueta IN ({$etiquetas->toComaList()})
                                )";
                            }
                        }
                    }
                }
            }

            if (count($filters)) return implode(" AND ", $filters);
        }

        public function __toString(){
            $str = parent::__toString();

            if (isset($this->empresa) || isset($this->referencia)) {
                $str .= "|";

                if (isset($this->empresa)) {
                    $str .= "empresa:{$this->empresa};";
                }

                if (isset($this->referencia)) {
                    $str .= "referencia:{$this->referencia}";
                }
            }

            return $str;
        }

        public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
            $array = new FieldList();
            $usuario = ( $usuario instanceof usuario ) ? $usuario : usuario::getCurrent();
            if( $usuario instanceof usuario ){
                $empresa = $usuario->getCompany();
                $camposExtra = $empresa->obtenerCamposDinamicos(11);
            }

            switch( $modo ){
                case elemento::PUBLIFIELDS_MODE_EDIT:
                    $array["nombre"]            = new FormField(array("tag" => "input",     "type" => "text", "blank" => false ));
                    $array["abbr"]              = new FormField(array("tag" => "input",     "type" => "text" ));

                    if ($objeto instanceof agrupador && $objeto->esFilter()) {
                        $array["direccion"]     = new FormField(array("tag" => "textarea"));
                    }


                    $array["icono"]             = new FormField(array("tag" => "a",     "className" => "line-block img-picker a-extend", "format" => "<img src='%s' />", "href" => "agrupamiento/iconos.php?folder=puestos"));
                    $array["visible"]           = new FormField(array("tag" => "input",     "type" => "checkbox", "className" => "iphone-checkbox"));
                    $array['prioridad']         = new FormField(array("tag" => "slider", "match" => "^[0-9]$", "className" => "slider", "count" => "10"));
                    $array["autoasignacion"]    = new FormField(array("tag" => "input",     "type" => "checkbox", "className" => "iphone-checkbox"));
                    $array["expiracion"]        = new FormField(array("tag" => "slider", "match" => "^[0-9]$", "className" => "slider", "count" => "1000"));
                    $array["description"]       = new FormField(array("tag" => "textarea"));

                    if( $usuario instanceof usuario && $usuario->isViewFilterByGroups() ){
                        unset( $array["abbr"] );
                        unset( $array["autoasignacion"] );
                        unset( $array["visible"] );
                        unset( $array["expiracion"] );
                    }

                    // if( isset($objeto) && $usuario instanceof usuario ){
                    //  if( $objeto->esFilter() ){
                    //      $usuarios = $usuario->obtenerHermanos(); $usuarios[] = $usuario;
                    //      $array["uid_manager"] =     new FormField(array("tag" => "select", "data" => $usuarios, "innerHTML" => "Project Manager", "default" => "seleccionar_manager"));
                    //      $array["uid_engineer"] =    new FormField(array("tag" => "select", "data" => $usuarios, "innerHTML" => "Project Engineer", "default" => "seleccionar_engineer"));
                    //  }
                    // }

                    if( isset($objeto) ){
                        if( $objeto->esPago() ){
                            $array["precio_unitario"] = new FormField(array("tag" => "input", "type" => "text", "innerHTML" => "Precio unitario", ));
                            $array["ambito"] =          new FormField(array("tag" => "input", "type" => "text", "innerHTML" => "Tipo de coste", ));
                        }
                    }

                    $array["ask_for_bounce"] = new FormField(array("tag" => "input",     "type" => "checkbox", "className" => "iphone-checkbox"));
                    $array["is_relevant"] = new FormField(array("tag" => "input",     "type" => "checkbox", "className" => "iphone-checkbox"));

                break;
                case "papelera":
                    $array = new FormField(array(   "papelera" => array("tag" => "input",   "type" => "checkbox") ));
                break;
                case elemento::PUBLIFIELDS_MODE_CRONCALL:
                    $array = new FieldList(array(
                        "alerts" => array()
                    ));
                break;
                case elemento::PUBLIFIELDS_MODE_SEARCH: case elemento::PUBLIFIELDS_MODE_NEW: default:
                        $array["nombre"] = new FormField(array("tag" => "input",    "type" => "text"));
                        $array["abbr"]  = new FormField(array("tag" => "input",     "type" => "text"));


                    if( $modo === elemento::PUBLIFIELDS_MODE_NEW ){
                        $array["uid_empresa"] = new FormField;
                        $array["uid_agrupamiento"] = new FormField;
                        $array["uid_usuario"] = new FormField;
                        $array["description"] = new FormField(array("tag" => "textarea"));
                    }
                break;
            }



            if( $modo != elemento::PUBLIFIELDS_MODE_SEARCH ){
                if( isset($camposExtra) && is_array($camposExtra) && count($camposExtra) ){
                    foreach($camposExtra as $campoExtra){
                        $array[ $campoExtra->getFormName() ] = array(
                            "tag" => $campoExtra->getTag(),
                            "type" => $campoExtra->getFieldType(),
                            "uid_campo" => $campoExtra->getUID(),
                            "data" => $campoExtra->getData()
                        );
                    }
                }
            }


            // if( $modo === "ficha-tabla" && isset($objeto) && $objeto->esFilter() ){
            //  if( $usuario instanceof usuario){
            //      $usuarios = $usuario->obtenerHermanos(); $usuarios[] = $usuario;
            //      $array["uid_manager"] = new FormField(array("tag" => "select", "data" => $usuarios, "innerHTML" => "Project Manager", "default" => "seleccionar_manager"));
            //      $array["uid_engineer"] = new FormField(array("tag" => "select", "data" => $usuarios, "innerHTML" => "Project Engineer", "default" => "seleccionar_engineer"));
            //  }
            // }


            return $array;

        }

        public function getTableFields()
        {
            return [
                ["Field" => "uid_agrupador",           "Type" => "int(10)",        "Null" => "NO",     "Key" => "PRI", "Default" => "",                                                        "Extra" => "auto_increment"],
                ["Field" => "uid_agrupamiento",        "Type" => "int(11)",        "Null" => "YES",    "Key" => "MUL", "Default" => "",                                                        "Extra" => ""],
                ["Field" => "uid_empresa",             "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "uid_usuario",             "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "nombre",                  "Type" => "varchar(250)",   "Null" => "NO",     "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "description",             "Type" => "varchar(255)",   "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "autoasignacion",          "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                                                       "Extra" => ""],
                ["Field" => "expiracion",              "Type" => "int(10)",        "Null" => "NO",     "Key" => "",    "Default" => "0",                                                       "Extra" => ""],
                ["Field" => "visible",                 "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                                                       "Extra" => ""],
                ["Field" => "icono",                   "Type" => "varchar(100)",   "Null" => "YES",    "Key" => "",    "Default" => "http://estatico.afianza.net/img/famfam/bullet_black.png", "Extra" => ""],
                ["Field" => "abbr",                    "Type" => "varchar(20)",    "Null" => "NO",     "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "papelera",                "Type" => "int(1)",         "Null" => "NO",     "Key" => "MUL", "Default" => "0",                                                       "Extra" => ""],
                ["Field" => "uid_manager",             "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "uid_engineer",            "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "0",                                                       "Extra" => ""],
                ["Field" => "precio_unitario",         "Type" => "int(10)",        "Null" => "NO",     "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "ambito",                  "Type" => "varchar(250)",   "Null" => "NO",     "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "prioridad",               "Type" => "int(2)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                                                       "Extra" => ""],
                ["Field" => "alerts",                  "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "0",                                                       "Extra" => ""],
                ["Field" => "direccion",               "Type" => "varchar(1024)",  "Null" => "NO",     "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "updated",                 "Type" => "timestamp",      "Null" => "NO",     "Key" => "",    "Default" => "0000-00-00 00:00:00",                                     "Extra" => "on update CURRENT_TIMESTAMP"],
                ["Field" => "uid_coordinator_company", "Type" => "int(10)",        "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "ask_for_bounce",          "Type" => "int(1)",         "Null" => "NO",     "Key" => "MUL", "Default" => "0",                                                       "Extra" => ""],
                ["Field" => "is_relevant",              "Type" => "int(1)",         "Null" => "NO",     "Key" => "MUL", "Default" => "0",                                                       "Extra" => ""],
                ["Field" => "kind_of_company",         "Type" => "varchar(254)",   "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "regions",                 "Type" => "varchar(254)",   "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "attached_requests",       "Type" => "int(10)",        "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "rejected_requests",       "Type" => "int(10)",        "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "validated_requests",      "Type" => "int(10)",        "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "expired_requests",        "Type" => "int(10)",        "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "pending_requests",        "Type" => "int(10)",        "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "docs_status_updated_at",  "Type" => "datetime",       "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "latlng",                  "Type" => "varchar(40)",    "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "start_date",               "Type" => "datetime",       "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "end_date",                 "Type" => "datetime",       "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "about_expire_email_date",  "Type" => "datetime",       "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
                ["Field" => "expired_email_date",       "Type" => "datetime",       "Null" => "YES",    "Key" => "",    "Default" => "",                                                        "Extra" => ""],
            ];
        }
    }
?>
