<?php

abstract class solicitable extends categorizable implements Isolicitable {

    const STATUS_NO_REQUEST = -1;
    const STATUS_INVALID_DOCUMENT = 0;
    const STATUS_VALID_DOCUMENT = 1;

    public static function getModules() {
        return array(
            1 => 'empresa',
            8 => 'empleado',
            14 => 'maquina'
        );
    }

    public static function getModulesCausesNewRange() {
        return array(
            8 => 'empleado',
            14 => 'maquina'
        );
    }

    public function applyHierarchy (usuario $user, $currentClient) {
        if ($this instanceof childItemEmpresa) return true;
        if ($this instanceof empresa) return false;
    }

    public function getViewData (Iusuario $user = NULL) {
        $viewData = parent::getViewData($user);

        $isOk = $this->isOk($user->getCompany(), $user);
        $viewData['ok'] = $isOk;

        $statusData     = [];
        $progressData   = [];
        $requestsData   = [];
        $requests       = $this->obtenerSolicitudDocumentos($user);
        $totalRequests  = count($requests);
        foreach ($requests as $request) {
            $reqData = [];

            $status = $request->getStatus();

            // initialize array
            if (!isset($statusData[$status])) $statusData[$status] = 0;

            $statusData[$status]++;
            $reqData['status'] = $status;

            $requestsData[] = $reqData;
        }

        foreach ($statusData as $i => $count) {
            $progressData[$i] = ($count * 100 / $totalRequests);
        }

        $viewData['progress'] = $progressData;

        return $viewData;
    }

    public static function onSearchByCompleted ($data, $filter, $param, $query) {
        $class = get_called_class();
        $value = reset($filter);

        $compare = '>';
        $sign = substr($value, 0, 1);

        // -- si nos dicen como comparar...
        if ($sign == '<' || $sign == '>' || $sign == '~') {
            if ($sign == '~') $sign = '=';
            $compare = $sign;
            $value = substr($value, 1);
        }

        if (!is_numeric($value)) return false;

        $userCondition = (isset($data['usuario']) && $usuario = $data['usuario']) ? $usuario->obtenerCondicionDocumentosView($class) : '';

        $requests = "
            SELECT count(uid_solicituddocumento) num
            FROM ". TABLE_DOCUMENTO ."_{$class}_estado view
            WHERE view.uid_{$class} = {$class}.uid_{$class}
            AND descargar = 0
            AND obligatorio = 1
            {$userCondition}
        ";

        $SQL = "(({$requests} AND estado = 2) * 100 / ({$requests})) {$compare} {$value}";

        return $SQL;
    }

    public static function onSearchByDocs($data, $filters, $param, $query) {
        $class = get_called_class();
        $uidmodulo = util::getModuleId($class);
        $SQLfilters = array();

        $userCondition = (isset($data['usuario']) && $usuario = $data['usuario']) ? $usuario->obtenerCondicionDocumentosView($class) : '';

        foreach ($filters as $filter) {
            if ($filter == documento::ESTADO_PENDIENTE) {
                $statusSQL = "estado IS NULL";
            } else {
                $statusSQL = "estado = $filter";
            }

            $sql = " uid_{$class} IN (
                SELECT view.uid_{$class} FROM ". TABLE_DOCUMENTO ."_{$class}_estado as view
                WHERE 1
                AND obligatorio = 1
                AND descargar = 0
                AND {$statusSQL}
                AND view.uid_{$class} = {$class}.uid_{$class}
                {$userCondition}
            )";

            $SQLfilters[] = $sql;
        }

        $SQL = '(' . implode(' AND ', $SQLfilters) . ')';

        return $SQL;
    }

    public static function onSearchByStatus($data, $filter, $param, $query) {
        $value = reset($filter);
        $SQL = false;
        $class = get_called_class();
        $uidmodulo = util::getModuleId($class);
        $table = PREFIJO_ANEXOS . $class;


        $SQLWrongDocuments = "
            SELECT count(uid_solicituddocumento) FROM ". TABLE_DOCUMENTO ."_{$class}_estado view
            WHERE uid_$class = $class.uid_$class AND obligatorio = 1 AND descargar = 0 AND (estado != ". documento::ESTADO_VALIDADO ." OR estado IS NULL)
        ";

        $SQLNumRequested = "
            SELECT count(uid_solicituddocumento) FROM ". TABLE_DOCUMENTO ."_{$class}_estado view
            WHERE uid_$class = $class.uid_$class AND obligatorio = 1 AND descargar = 0
        ";

        if (isset($data['usuario']) && $usuario = $data['usuario']) {
            $userCondition = $usuario->obtenerCondicionDocumentosView($class);

            $SQLWrongDocuments .= $userCondition;
            $SQLNumRequested .= $userCondition;
        }

        $truthy = array('si', 'ok', '1');
        if (in_array($value, $truthy)) {
            $SQL = "( ($SQLWrongDocuments) = 0 AND ($SQLNumRequested) > 0 )";
        } else {
            $SQL = "( ($SQLWrongDocuments) > 0 OR ($SQLNumRequested) = 0 )";
        }

        return $SQL;
    }

    public function getStatusInCompany($usuario, empresa $company, $toArray = false) {
        $informacionDocumentos = $company->obtenerEstadoDocumentos($usuario, 0, true);
        $isValid = count($informacionDocumentos) == 1 && isset($informacionDocumentos[documento::ESTADO_VALIDADO]);
        $isAttached = count($informacionDocumentos) == 1 && isset($informacionDocumentos[documento::ESTADO_ANEXADO]);

        $errorIMG = RESOURCES_DOMAIN . "/img/famfam/error.png";
        $validIMG = RESOURCES_DOMAIN . "/img/famfam/accept.png";
        $attachedIMG = RESOURCES_DOMAIN . "/img/famfam/exclamation.png";

        $array = array("img" => $errorIMG, "class" => "red");

        $tipo = $this->getModuleName();
        $table = TABLE_DOCUMENTO . "_{$tipo}_estado";

        $last = db::getLastFromSet('uid_empresa_referencia');

        $SQL = "
            SELECT count(uid_anexo_{$tipo}) as num, estado
            FROM $table view
            WHERE uid_{$tipo} = {$this->getUID()}
            AND descargar = 0
            AND obligatorio = 1
            AND (
                    {$last} = {$company->getUID()}
                OR  uid_empresa_referencia = 0
            )
        ";

        if ($usuario instanceof usuario) {
            $viewALL = (bool) $usuario->configValue("viewall");
            if ($viewALL === false) $SQL .= $usuario->obtenerCondicionDocumentosView($tipo);
        } elseif ($usuario instanceof empresa) {
            $SQL .= " AND " . $usuario->getRequestFilter($tipo);
        }


        $SQL .= " GROUP BY estado ";

        $inCompanyInfo = $this->db->query($SQL, true);
        $isValidForCompany = count($inCompanyInfo) == 1 && $inCompanyInfo[0]['estado'] == documento::ESTADO_VALIDADO;
        $isAllAttachedForCompany = count($inCompanyInfo) == 1 && $inCompanyInfo[0]['estado'] == documento::ESTADO_ANEXADO;

        if ($isValidForCompany && $isValid) {
            $array = array("img" => $validIMG, "class" => "green");
        } elseif ( ($isAllAttachedForCompany||$isValidForCompany) && ($isAttached||$isValid)) {
            $array = array("img" => $attachedIMG, "class" => "orange");
        }

        if ($toArray) return $array;
        return ($array['class'] === 'green');
    }


    /* USO DESDE EMPRESA, MAQUINA Y EMPLEADO PARA CUMPLIR CON IfolderContainer.iface.php */
    public function obtenerCarpetas($recursive = false, $level = 0, Iusuario $usuario = NULL){

        $sql = "SELECT uid_carpeta FROM ". TABLE_CARPETA . "_solicitable ci WHERE uid_modulo = {$this->getModuleId()} AND uid_elemento = {$this->getUID()} ";

        if( $usuario instanceof usuario ) {
            $empresa = $usuario->getCompany();
            $isCompany = $this instanceof empresa;
            switch ($isCompany) {
                case true:
                    $sql .= " AND ( ( uid_empresa_referencia = {$empresa->getUID()} ) OR (uid_elemento IN ({$empresa->getStartIntList()->toComaList()}) ) )";
                    break;
                case false:
                    $sql .= " AND ( uid_empresa_referencia = {$empresa->getUID()} OR uid_empresa_referencia = 0 )";
                    break;

                default:
                    return false;
                    break;
            }
        }

        $sql .= " AND uid_carpeta IN ( SELECT uid_carpeta FROM ". TABLE_CARPETA ." WHERE carpeta.uid_carpeta = ci.uid_carpeta) ";

        $carpetas   = $this->db->query($sql, "*", 0, "carpeta");
        $filtered   = carpeta::filtrarNoVisibles($carpetas, $usuario);
        return new ArrayObjectList($filtered);
    }

    public function solicitudesPendientes(Iusuario $user = null, empresa $company = null)
    {
        $companyRequest = TABLE_EMPRESA . "_solicitud";
        $statusCreated = solicitud::ESTADO_CREADA;

        $sql = "SELECT uid_empresa_solicitud FROM {$companyRequest}
        WHERE uid_elemento = {$this->getUID()}
        AND uid_modulo = {$this->getModuleId()}
        AND estado = {$statusCreated}";

        if (null !== $user && false === $user->esStaff()) {
            $sql .= " AND uid_usuario = {$user->getUID()}";
        }

        if (!is_null($company)) {
            $sql .= " AND uid_empresa_origen = {$company->getUID()}";
        }

        $items = $this->db->query($sql, "*", 0, 'empresasolicitud');
        return new ArrayObjectList($items);
    }

    /***
        Método flexible que permite añadir excepciones de visualización de elementos
    **/
    public function canViewBy(Iusuario $usuario, $context, $extraData = NULL){
        switch($context){
            case Ilistable::DATA_CONTEXT_DESCARGABLES:
            /***
                En contexto Descargables, se obtiene el parametro agrupador o empresa de la URL definida previamente para list.php, por lo que si no ha sido modificada dicha URL,
                el primer valor de $extraData tiene que ser necesariamente un objeto agrupador o empresa. En caso contrario no se podran msotrar los elementos
            **/
                if ( $extraData[0] instanceof agrupador ) {
                    $empresa = $usuario->getCompany();
                    if ( $agrupadores = $empresa->obtenerAgrupadores()->merge($empresa->obtenerAgrupadoresPropios()) ) {
                        return $agrupadores->contains($extraData[0]);
                    } else return false;
                } elseif ( $extraData[0] instanceof empresa ) {
                    if ( $empresas = $usuario->getCompany()->obtenerEmpresasCliente() ) {
                        return ( $empresas->contains($extraData[0]) || $usuario->getCompany()->compareTo($extraData[0]) );
                    } else return false;
                }
            return false;
            break;
        }

        return false;
    }

    /**
     * [obtenerEmpresasSolicitantes description]
     * @param  boolean $usuario      [description]
     * @param  boolean $includeSelf  [description]
     * @param  boolean $includeCorps [description]
     * @return [type]                [description]
     */
    public function obtenerEmpresasSolicitantes($usuario = false, $includeSelf = true, $includeCorps = true)
    {
        $empresasEmpleados  = new ArrayObjectList();
        $empresas           = $this->getCompanies(false, $usuario);
        $userCompany        = ($usuario instanceof usuario) ? $usuario->getCompany() : NULL;
        $isSelf             = $userCompany && $empresas->contains($userCompany);

        foreach ($empresas as $empresa) {
            $empresasCliente = $empresa->obtenerEmpresasCliente($userCompany, $usuario);

            if ($includeSelf) {
                $empresasCliente = $empresasCliente->merge($empresa);
            }

            foreach ($empresasCliente as $empresaCliente) {
                $visible = ($isSelf) ? $this->esVisiblePara($empresaCliente, $userCompany) : $this->esVisiblePara($empresaCliente)  || $empresaCliente->compareTo($empresa);

                if ($visible) {
                    if ($includeCorps && $corp = $empresaCliente->perteneceCorporacion()) {
                        if ($usuario instanceof usuario) {
                            $limiterUser = $usuario->getUserLimiter($corp);
                            if ($limiterUser && !$limiterUser->compareTo($usuario)) continue;
                        }

                        $empresasEmpleados[] = $corp;
                    }

                    if ($usuario instanceof usuario) {
                        $limiterUser = $usuario->getUserLimiter($empresaCliente);
                        if ($limiterUser && !$limiterUser->compareTo($usuario)) continue;
                    }

                    $empresasEmpleados[] = $empresaCliente;
                }
            }
        }

        return $empresasEmpleados->unique();
    }

    public function obtenerElementosSuperiores(){
        return $this->getCompanies();
    }

    public function getCreationDate(){
        $data = $this->obtenerDato("created");
        if( $data = trim($data) ){
            return date("d-m-Y h:i:s", $data);
        }
        return "??";
    }

    /* Obtener la empresa segun el contexto actual
     * tanto de cliente como de empresas
     * seleccionadas
     */
    public function obtenerEmpresaContexto(Iusuario $usuario = NULL){
        $empresas = $this->getCompanies();

        if( isset($_SESSION["OBJETO_EMPRESA"]) ){
            $empresaActiva = unserialize($_SESSION["OBJETO_EMPRESA"]);
            foreach($empresas as $empresa){
                if( $empresa->getUID() == $empresaActiva->getUID() ){
                    return $empresa;
                }
            }
        }

        $empresaContexto = reset($empresas);
        if( $usuario instanceof usuario ){
            if( !$usuario->accesoElemento($empresaContexto) && count($empresas) > 1 && $usuario->accesoElemento($empresas[1]) ){
                $empresaContexto = $empresas[1];
            }
        }

        return $empresaContexto;
    }

    public static function filters2Sql(Iusuario $usuario = NULL, $filters = array(), $logica = "AND"){
        $sub = array();
        // Podemos tener varios filtros
        foreach($filters as $key => $filter){
            if ($filter instanceof basic) {
                switch($filter->getModuleName()){
                    case "solicituddocumento":
                        $sub[] = " de.uid_documento_elemento = {$filter->getUID()}";
                    break;
                    case "cliente":
                        $sub[] = " da.uid_empresa_propietaria = {$filter->getUID()}";
                    break;
                    case "empresa":
                        if ($key === "related") {
                            $sub[] = "(da.uid_elemento_origen = {$filter->getUID()} AND da.uid_modulo_origen = 1)";
                        } else {
                            $sub[] = " FIND_IN_SET({$filter->getUID()}, da.uid_empresa_views)";
                        }
                    break;
                    case "documento":
                        $sub[] = " da.uid_documento = {$filter->getUID()}";
                    break;
                    case "agrupador":
                        if ($key === "related") {

                            $list = new ArrayIntList(array($filter->getUID()));
                            $agrupamiento = $filter->obtenerAgrupamientoPrimario();
                            $company = $filter->getCompany();
                            if ($others = $filter->obtenerAgrupadores()) {
                                $list = $list->merge($others->toIntList());
                            }


                            $sub[] = " (
                                (da.uid_elemento_origen IN ({$list}) AND da.uid_modulo_origen = 11)
                                OR
                                (da.uid_elemento_origen = {$agrupamiento->getUID()} AND da.uid_modulo_origen = 12)
                                OR
                                (da.uid_elemento_origen = {$company->getUID()} AND da.uid_modulo_origen = 1)
                            )";
                        }
                    break;
                }
            } elseif(is_numeric($key) && is_array($filter)) {
                $arrayOR = array();
                foreach ($filter as $subkey => $subvalue) {
                    $filtro = "  ( " . self::filters2Sql($usuario, $subvalue) . " ) ";
                    $arrayOR[] =  $filtro ;
                }
                $stringOR = implode (" OR ",$arrayOR);
                $sub[] = "( " . $stringOR . " ) ";
            } elseif($key) {
                // switch evaluate with "==", so we can't add array filters with numeric key
                switch($key){
                    case "rebote": break;
                    case "alias":
                        $subFilterAlias = array();
                        $filtersAlias = explode(' ', $filter);
                        foreach ($filtersAlias as $filterAlias) {
                            $subFilterAlias[] = " $key LIKE '%$filterAlias%' ";
                        }
                        $sub[] = implode(' AND ', $subFilterAlias);
                    break;
                    case "reusable":
                        // filtrar para que no se muestre: que sean replicas, ref subcontratacion, ref agrupador

                        $sub[] = "
                            (da.uid_documento_atributo_replica IS NULL AND referenciar_empresa NOT IN (0, 1) AND uid_agrupador = '0')
                        ";
                    break;
                    case "estado":
                        $class = get_called_class();
                        if( $filter ){
                            $subSQL = " da.uid_documento_atributo IN (
                                SELECT uid_documento_atributo FROM ". PREFIJO_ANEXOS ."$class a
                                WHERE   a.uid_documento_atributo = de.uid_documento_atributo
                                AND     a.uid_agrupador = de.uid_agrupador
                                AND     a.uid_empresa_referencia = de.uid_empresa_referencia
                                AND     a.uid_$class = de.uid_elemento_destino
                            ";

                            if (is_array($filter)) {
                                $subSQL .= " AND a.estado IN (". implode(",", $filter) .")";

                                if (in_array(0, $filter)) {
                                    $subSQL .= " OR ". $class::filters2Sql($usuario, array("estado" => null));
                                }
                            } else {
                                $subSQL .=" AND a.estado = $filter";
                            }

                            $subSQL .= ") ";

                            $sub[] = $subSQL;
                        } else {
                            $sub[] = " da.uid_documento_atributo NOT IN (
                                SELECT uid_documento_atributo FROM ". PREFIJO_ANEXOS ."$class a
                                WHERE   a.uid_documento_atributo = de.uid_documento_atributo
                                AND     a.uid_agrupador = de.uid_agrupador
                                AND     a.uid_empresa_referencia = de.uid_empresa_referencia
                                AND     a.uid_$class = de.uid_elemento_destino
                                AND     a.estado
                            ) ";
                        }
                    break;
                    case "!cliente":
                        if( $filter instanceof empresa ){
                            $list = $filter->getUID();
                        } elseif( $filter instanceof ArrayObjectList && count($filter) ){
                            $list = $filter->toComaList();
                        } else {
                            $list = "0";
                        }

                        $sub[] = " da.uid_empresa_propietaria NOT IN ($list) ";
                    break;
                    case "!estado":
                        $class = get_called_class();
                        if( $filter ){
                            $sub[] = " da.uid_documento_atributo NOT IN (
                                SELECT uid_documento_atributo FROM ". PREFIJO_ANEXOS ."$class a
                                WHERE   a.uid_documento_atributo = de.uid_documento_atributo
                                AND     a.uid_agrupador = de.uid_agrupador
                                AND     a.uid_empresa_referencia = de.uid_empresa_referencia
                                AND     a.uid_$class = de.uid_elemento_destino
                                AND     a.estado = $filter
                            ) ";
                        }
                    break;
                    case "uid_etiqueta":
                        $tabla = TABLE_DOCUMENTO_ATRIBUTO . "_etiqueta";
                        $sub[] = " da.uid_documento_atributo IN (
                            SELECT a.uid_documento_atributo FROM $tabla a
                            WHERE a.uid_documento_atributo = da.uid_documento_atributo
                            AND a.uid_etiqueta = '". db::scape($filter) ."'
                        )";
                    break;
                    case "uid_empresa_referencia":
                        $empresa = new empresa(db::scape($filter));
                        $getLastFromSet = db::getLastFromSet("uid_empresa_referencia");
                        $sub[] = "(CASE
                            WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_COMPANY ." THEN (
                                uid_empresa_referencia IN (". $empresa->getStartIntList() .")
                            )
                            WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_CHAIN ."  THEN (
                                ($getLastFromSet IN (". $empresa->getStartIntList() ."))
                                OR uid_empresa_referencia = 0
                            )
                            WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_CONTRACTS ."  THEN (
                                1
                            ) ELSE (
                                uid_empresa_referencia = 0
                            )
                        END)
                        ";
                    break;
                    case "!replica":
                        $sub[] = "IF (
                            (SELECT COUNT(uid_documento_atributo) FROM agd_docs.documento_atributo pr
                            WHERE uid_documento_atributo = da.uid_documento_atributo_replica
                            AND replica = 1)
                            ,0
                            ,1)
                        ";
                    break;
                    case "!subcontratacion":
                        $sub[] = "referenciar_empresa != ". documento_atributo::REF_TYPE_CHAIN;
                    break;
                    case "requirements":
                        $sub[] = " da.uid_documento_atributo IN (".implode(",", $filter).")";
                    break;
                    default:
                        if (isset($filter)) {
                            $sub[] = " $key = $filter ";
                        }
                    break;
                }
            }
        }

        $sql = implode($logica, $sub);

        return $sql;
    }


    /**
    * Devolver una lista de items solicitudDocumento
    *
    * @param object(solicitable) El objeto del que queremos conocer el dato
    * @param object(usuario) el usuario que pregunta
    * @param [ mixed $filter = NULL ] aplicar filtro a la busqueda
    * @return ArrayObjectList donde cada indice es una solicitud
    */
    public function obtenerSolicitudDocumentos (Iusuario $usuario = NULL, $filters = array(), $returnSQL = false, $logica = " AND ", $optionsFilter = null) {
        $SQL = TABLE_DOCUMENTOS_ELEMENTOS ." de
            INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." da
            USING (uid_documento_atributo, uid_modulo_destino)
            WHERE 1
            AND uid_elemento_destino = {$this->getUID()}
            AND uid_modulo_destino = {$this->getModuleId()}
            AND activo = 1 AND replica = 0
        ";

        if( !is_traversable($filters) ) $filters = array($filters);
        if( !array_key_exists("papelera", $filters) ){ $filters["papelera"] = 0; }
        if( !array_key_exists("descargar", $filters) ){ $filters["descargar"] = 0; }

        //if( !isset($filters["certificacion"]) ){ $filters["certificacion"] = 0; }
        $class = $this->getModuleName();
        if( is_array($filters) && count($filters) && $filterSQL = $class::filters2Sql($usuario, $filters, $logica) ){
            $SQL .= " AND " . $filterSQL;
        }

        if ($usuario instanceof Iusuario && $condicion = $usuario->obtenerCondicionDocumentos()) {
            $SQL .= $condicion;
        }

        if ($returnSQL) return $SQL;


        if (isset($optionsFilter['count']) && $optionsFilter['count']) {
            $SQL = "SELECT count(uid_documento_elemento) FROM $SQL";
            return $this->db->query($SQL, 0, 0);
        }

        $SQL = "SELECT uid_documento_elemento FROM $SQL";
        $list = new ArrayRequestList;
        if ($solicitudes = $this->db->query($SQL, "*", 0, "solicituddocumento")) {
            $list = new ArrayRequestList($solicitudes);
        }

        return $list;
    }

    public function reattachAll($companies = null, usuario $user, $callback = null)
    {
        $requestsAttached = new ArrayObjectList;
        $documents = $this->getDocuments();
        $totalDocuments = count($documents);
        foreach ($documents as $i => $document) {
            // progress
            if (true === is_callable($callback)) {
                call_user_func($callback, $i+1, $totalDocuments);
            }

            $filters = array();
            $filters[] = $document;
            $requests = $this->obtenerSolicitudDocumentos(null, $filters);

            if (count($requests) && ($bestReattachable = $this->getBestReattachable($document, $user))) {
                $requestReattach = new ArrayObjectList;
                foreach ($requests as $request) {
                    if (isset($companies)) {
                        if ($companies instanceof empresa) {
                            $company = $companies;
                            $companies = new ArrayObjectList;
                            $companies[] = $company;
                        }

                        if ($companies instanceof ArrayObjectList) {
                            $requestCompany = $request->getClientCompany();
                            if (!$companies->contains($requestCompany)) {
                                continue;
                            }
                        }

                    }

                    $status = $request->getStatus();
                    $reattachableStatus = in_array($status, documento::getInvalidStatus());
                    $canReattach = $request->canReattach();

                    if (true === $reattachableStatus
                        && true === $canReattach) {
                        $requestReattach[] = $request;
                        $requestsAttached[] = $request;
                    }
                }

                if (count($requestReattach)) {
                    $fileinfo = $bestReattachable->getInfo();
                    $file = DIR_FILES . $fileinfo["archivo"];
                    $hash = $fileinfo["hash"];
                    $filename = archivo::getRandomName($fileinfo["nombre_original"]);
                    $fecha = date("d/m/Y", $fileinfo["fecha_emision"]);
                    if (!archivo::tmp($filename, archivo::leer($file))) {
                        throw new Exception("error_leer_archivo", 1);
                    }
                    try {
                        $document->upload($filename, $hash, $fileinfo["nombre_original"], $fecha, $this, $requestReattach, $user, null, $fileinfo["fecha_expiracion"], null, $bestReattachable);
                    } catch (Exception $e) {
                        //continue to the next document
                    }
                }
            }
        }

        return $requestsAttached;
    }

    public function getBestReattachable(documento $document, Iusuario $user)
    {
        $reattachables = $this->getReattachableDocuments($document, $user);

        return $reattachables->getBestReattachable();
    }

    public function getReattachableDocuments (documento $document, $user = null) {
        $docsAtribute = $this->getDocumentAtributeFromDocument($document, $user);
        $attach = new ArrayAnexoList;
        foreach ($docsAtribute as $key => $attr) {
            $anexos = $attr->getAnexo($this, false, true);
            // --- si no hay anexos
            if ($anexos === false || !count($anexos)) continue;


            foreach ($anexos as $anexo) {
                $empresaReferencia = $anexo->obtenerDato("uid_empresa_referencia");

                // --- si la empresa referencia esta definida y no somos nosotros
                if ($empresaReferencia != 0 && isset($user) && !$user->getCompany()->getStartIntList()->contains($empresaReferencia)) continue;

                $solicitud = $anexo->getSolicitud();
                $referencia = $solicitud && $solicitud->obtenerAgrupadorReferencia();
                $estadoAnexo = $anexo->getStatus();

                // si no hay referencia y el documento esta en un estado reutilizable
                if (!$referencia && in_array($estadoAnexo, documento::getReusableStatus())) {
                    $attach[] = $anexo;
                }
            }
        }

        return $attach;
    }

    public function getReattachableFiltered (documento $document, usuario $user) {
        $allReattachables       = $this->getReattachableDocuments($document, $user);
        $filteredReatachables   = new ArrayObjectList;
        $requests               = $document->obtenerSolicitudDocumentos($this, $user, [], null);

        foreach ($allReattachables as $attachment) {
            $request = $attachment->getSolicitud();
            // If there are only one request we have to unset his attach (if exists)
            if (count($requests) == 1 && $requests->contains($request)) {
                continue;
            }

            $filteredReatachables[] = $attachment;
        }

        return $filteredReatachables;

    }

    public function getDocumentAtributeFromDocument(documento $document, $user = null, $filters = array("referenciar_empresa" => array(0,1), "descargar" => 0)) {
        $modulo = $this->getModuleName();

        $sql = "
            SELECT da.uid_documento_atributo FROM ". TABLE_DOCUMENTO . "_atributo da
            INNER JOIN ". PREFIJO_ANEXOS . "{$modulo} ae USING (uid_documento_atributo)
            WHERE uid_documento = ". $document->uid ."
            AND uid_{$modulo} = ". $this->getUID() ."
        ";

        if (!isset($user)) {
            $sql .= " AND uid_empresa_referencia = 0";
        } elseif ($this instanceof childItemEmpresa) {
            $sql .= " AND ( uid_empresa_referencia IN (". $user->getCompany()->getStartIntList() .")
                OR uid_empresa_referencia = 0
            )";
        }

        foreach ($filters as $key => $filter) {
            switch ($key) {
                default:
                    if (is_array($filter)) {
                        $sql .= " AND $key IN (". implode(",", $filter) .")";
                    } else {
                        $sql .= " AND $key = '$filter' ";
                    }
                break;
            }
        }

        $sql .= "GROUP BY da.uid_documento_atributo";


        $docs = $this->db->query($sql, "*", 0, 'documento_atributo');
        if ($docs && count($docs)) return new ArrayObjectList($docs);
        return new ArrayObjectList;
    }


    /** DEVUELVE UN ARRAY FORMATEADO PARA SER UN INDICE "inline" CON LA INFORMACION DE LA DOCUMENTACION **/
    public function getDocsInline(Iusuario $usuario){
        $cacheKey = __CLASS__.'-'.__FUNCTION__.'-'.$this.'-'.$usuario->obtenerPerfil();
        if (($value = $this->cache->getData($cacheKey)) !== NULL) return json_decode($value, true);

        $docs = array();

        $name = $this->getModuleName() . "_documento";
        if( $usuario->accesoModulo($name) ){
            $lang = Plantilla::singleton();
            $docs["img"] = array( "src" => RESOURCES_DOMAIN . "/img/famfam/folder.png");

            $class = get_called_class();
            $informacionDocumentos = $this->obtenerEstadoDocumentos($usuario, 0, true);
            if( count($informacionDocumentos) ){
                foreach( $informacionDocumentos as $idestado => $estado ){
                    $docs[] = array("nombre" => $estado, "title" => $lang('explain.stat_'.$idestado), "className" => "stat stat_".$idestado, "href" => "#documentos.php?m={$class}&poid=".$this->getUID()."&estado=$idestado");
                }
            } else {
                $docs[] = array("nombre" => solicitable::status2string("-1"), "title" => $lang('explain.stat_-1'), "className" => "stat stat_-1", "href" => "#documentos.php?m=". $this->getModuleName() ."&poid={$this->getUID()}");
            }
        }

        $this->cache->addData($cacheKey, json_encode($docs), 60*60*15);
        return $docs;
    }





    /** DEVUELVE ARRAY DE CLIENTES QUE PIDEN ALGUN DOCUMENTO A ESTE ELEMENTO **/

    public function obtenerEmpresasClienteNoHabilitadas(Iusuario $usuario = NULL){
        $sql = $this->obtenerSolicitudDocumentos($usuario, array("certificacion" => 1, "!estado" => documento::ESTADO_VALIDADO), true);
        $sql = "SELECT uid_empresa_propietaria FROM $sql GROUP BY uid_empresa_propietaria";
        $empresas = $this->db->query($sql, "*", 0, "empresa");
        if ($err = $this->db->lastError()) throw new Exception($err . ". SQL: {$sql}");

        return new ArrayObjectList($empresas);
    }


    public function updateNeeded ($set = null) {

        // use this function to set the updated needed instead of read the value
        if (is_bool($set)) {

            // reverse the data, updated = 0 means is out of date
            $data = ['updated' => (int) !$set];

            return $this->update($data, elemento::PUBLIFIELDS_MODE_SYSTEM);
        }


        $companies = $this->obtenerEmpresasSolicitantes();
        if ($this instanceof empresa) $companies = $companies->merge($this);

        if (!count($companies)) {
            return false;
        }

        $updated = (int) $this->obtenerDato('updated');
        if ($updated === 0) {
            return true;
        }

        $moduleId       = $this->getModuleId();
        $tableAtribute  = TABLE_DOCUMENTO_ATRIBUTO;
        $tableAgr       = TABLE_AGRUPADOR;
        $tableOrg       = TABLE_AGRUPAMIENTO;


        // return true if any of the companies groups has beed updated
        $agrUpdateSql = "SELECT count(uid_agrupador) FROM {$tableAgr} WHERE 1
        AND uid_empresa IN ({$companies->toIntList()})
        AND updated > DATE_ADD(NOW(), INTERVAL -1 DAY)
        ";

        if ($this->db->query($agrUpdateSql, 0, 0)) {
            return true;
        }

        // return true if any of the companies orgs has been updated
        $orgUpdatedSql = "SELECT count(uid_agrupamiento) FROM {$tableOrg} WHERE 1
        AND uid_empresa IN ({$companies->toIntList()})
        AND updated > DATE_ADD(NOW(), INTERVAL -1 DAY)
        ";

        if ($this->db->query($orgUpdatedSql, 0, 0)) {
            return true;
        }

        $attrRelated = "SELECT count(uid_documento_atributo) as attrs
        FROM $tableAtribute attr
        INNER JOIN (
            SELECT ae.rebote as uid_agrupador_rebote
            FROM $tableAtribute attr
            INNER JOIN {$tableAgr}_elemento ae
            ON attr.uid_elemento_origen = ae.uid_agrupador
            WHERE uid_modulo_origen = 11
            AND uid_documento_atributo IN (
                SELECT uid_documento_atributo
                FROM $tableAtribute
                WHERE uid_modulo_destino = {$moduleId}
                AND uid_empresa_propietaria IN ({$companies->toIntList()})
            )
            AND ae.fecha > DATE_ADD(NOW(), INTERVAL -1 DAY)
            AND rebote != 0
            AND uid_elemento = {$this->getUID()}
            AND uid_modulo = {$moduleId}
        ) as rebotes
        ON attr.uid_elemento_origen = rebotes.uid_agrupador_rebote
        AND uid_modulo_origen =  11
        AND uid_modulo_destino = {$moduleId}";

        $SQL = "SELECT SUM(attrs) FROM (
        SELECT count(uid_documento_atributo) as attrs
        FROM $tableAtribute
        WHERE uid_modulo_destino = {$moduleId}
        AND uid_empresa_propietaria IN ({$companies->toIntList()})
        AND fecha > DATE_ADD(NOW(), INTERVAL -1 DAY)
        UNION $attrRelated
        ) as attrNeeded";

        return (bool) $this->db->query($SQL, 0, 0);
    }


    public static function cronCall ($time, $force = false, $tipo) {
        $table  = constant( "TABLE_". strtoupper($tipo));
        $db     = db::singleton();
        $cache  = cache::singleton();

        $filter = ($tipo == 'empresa' || $force) ? " AND 1 " : " AND uid_{$tipo} IN (SELECT uid_{$tipo} FROM {$table}_empresa WHERE papelera = 0)";

        // Get count
        $sql    = "SELECT count(*) FROM $table WHERE 1 $filter";
        $t      = $db->query($sql, 0, 0);

        // Get items
        $sql    = "SELECT uid_$tipo FROM $table WHERE 1 $filter ORDER BY uid_$tipo DESC";//." WHERE updated = 0";
        $array  = $db->query($sql, "*", 0);
        if (!count($array)) return "Nada que hacer";

        $now = date("Y-m-d H:i:s");
        echo "[$now] Comenzamos a actualizar $t solicitudes\n";

        foreach($array as $i => $uid ){
            $elemento = new $tipo($uid, false);
            echo "Actualizando solicitudes $tipo $uid.. [$i/$t] ". round(100*$i/$t, 1) . "%";
            if ($force || $elemento->updateNeeded()) {
                if ($error = $elemento->actualizarSolicitudDocumentos()) {
                    echo " Ok ";
                    unset($elemento);
                }

                // clean the cache every 100 items
                if ($i && $i % 100 === 0) {
                    $cache->clear();
                }

                if ($error === NULL) {
                    echo " Na ";
                } elseif (!$error) {
                    echo " Error ";
                }

                if ($error !== NULL && !$error) {
                    echo "\n";
                } else {
                    echo "\r";
                }
            } else {
                echo " No need! \r";
            }
        }
        $now = date("Y-m-d H:i:s");
        echo "\n[$now] Finalizamos actualizacion de solicitudes\n";
        return true;
    }

    public function getDownloadAtributos($filtro, $papelera=null, $limit=null, $usuario=false){
        $idDocs = $this->getDocumentsId(1, null, $papelera, $filtro );
        if( !count($idDocs) ){ return array(); }

        $sql = "SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ." da
                INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." USING(uid_documento_atributo, uid_modulo_destino)
                WHERE uid_documento IN (". implode(",", $idDocs) .")
                AND uid_elemento_destino = ". $this->getUID() ."
                AND uid_modulo_destino = ". $this->getModuleId() ."
                AND descargar = 1
        ";

        if( is_array($filtro) && count($filtro) ){
            foreach( $filtro as $campo => $valor ){
                switch( $campo ){
                    case "alias":
                        $sql .= " AND da.$campo LIKE '%". db::scape($valor) ."%'";
                    break;
                    default:
                        $sql .= " AND da.$campo = '". db::scape($valor) ."'";
                    break;
                }
            }
        }

        if( $usuario instanceof usuario && $usuario->isViewFilterByGroups() ){
            if( count($list) ){
                $agrupadores = $this->obtenerAgrupadores();
                $list = count($agrupadores) ? $agrupadores->toComaList() : "0";

                $filter = array();
                $filter[] = "( da.uid_modulo_origen = 11 AND da.uid_elemento_origen IN (". $list .")";
                $filter[] = "( da.uid_modulo_origen != 11 )";
                $sql .= " AND " . implode(" OR ", $filter);
            } else {
                $sql .= " AND 0 ";
            }
        }

        $sql .= " GROUP BY da.uid_documento_atributo, da.uid_modulo_origen, da.uid_elemento_origen";

        // Delimitar los resultados
        if( is_array($limit) ){
            $sql .= " LIMIT ". $limit[0] .", ". $limit[1];
        }

        $coleccion = $this->db->query($sql, "*", 0, "documento_atributo");

        return $coleccion;
    }

    /***
       *
       * returns ArrayRequestList. Collection of the requirement requests
       *
       * param @opts
       *    download [bool = false]  - get the downloadable request
       *    sort [string = false]   - order the result. "wrong" and "name" are reserverd words to simplify operations
       *    status [array]          - filter by status. It has reserved words (documento::CONSTANTS)
       *        "expiring"      - includes request about to expire
       *        "renovation"    - includes request in renovation process
       *        "rejecting"     - includes request rejected but temporary validated
       *        "wrong"         - includes the 3 wrong document statuses
       *        "validable"     - includes attached the user can validate
       *    q [string]              - filter by query
       *    count [bool]            - alternative method to count, same as use @count parameter
       *    list [array|int]        - filter by reqtype uid(s)
       *    mandatory [bool = null] - filter by mandatory (null means not to filter)
       *    client [array|object company] - filtery by requester companies
       *    viewer [empresa|usuario] - apply the viewer filters
       *    reference [array] - Used to filter the field uid_empresa_referencia
       * param @count - returns a number instead of a ArrayRequestList
       *
       *
       */
    public function getReqTypes ($opts = [], $returnType = 'objectList')
    {
        $class      = $this->getModuleName();
        $sort       = isset($opts['sort']) ? $opts['sort'] : false;
        $limit      = isset($opts['limit']) ? $opts['limit'] : false;
        $view       = TABLE_DOCUMENTO . "_{$class}_estado";

        $reqtypes   = new ArrayRequestList;
        $docName    = "(SELECT nombre FROM ". TABLE_DOCUMENTO ." d WHERE d.uid_documento = view.uid_documento)";

        $filters    = " uid_{$class} = {$this->getUID()} ";

        $filters    .= " AND " . self::getReqTypeFilters($opts);

        switch ($returnType) {
            case 'count':
                $SQL = "SELECT count(distinct uid_documento) FROM {$view} view WHERE 1 AND {$filters}";
                return (int) $this->db->query($SQL, 0, 0);
            case 'intList':
                $SQL = "SELECT distinct uid_documento FROM {$view} view WHERE 1 AND {$filters}";
                return $this->db->query($SQL, '*', 0);
            case 'objectList':
                // get all the documents
                $SQL = "SELECT uid_documento, GROUP_CONCAT(uid_solicituddocumento) as requests
                FROM {$view} view WHERE 1 AND {$filters} GROUP BY uid_documento";

                if ($sort) {
                    $sort = str_replace('wrong', 'estado IS NULL DESC, estado = 3 DESC, estado = 4 DESC, estado = 1 DESC', $sort);
                    $sort = str_replace('name', "{$docName} ASC", $sort);


                    $SQL .= " ORDER BY {$sort}";
                }


                if ($limit) {
                    $SQL .= " LIMIT {$limit[0]}, {$limit[1]}";
                }

                $rows = $this->db->query($SQL, true);
                foreach ($rows as $row) {
                    $intList            = ArrayIntList::factory($row['requests']);
                    $reqtype            = new documento($row['uid_documento'], $this, false);
                    $reqtype->requests  = $intList->toObjectList('solicituddocumento', 'ArrayRequestList');

                    $reqtypes[] = $reqtype;
                }

                return $reqtypes;
        }

        return false;
    }


    /**
     * get SQL filters for reqTypes
     * @param  array $opts
     *    download [bool = false]  - get the downloadable request
     *    status [array]          - filter by status. It has reserved words (documento::CONSTANTS)
     *        "expiring"      - includes request about to expire
     *        "renovation"    - includes request in renovation process
     *        "rejecting"     - includes request rejected but temporary validated
     *        "wrong"         - includes the 3 wrong document statuses
     *        "validable"     - includes attached the user can validate
     *    q [string]              - filter by query
     *    list [array|int]        - filter by reqtype uid(s)
     *    label [elemento|int]    - filter by label
     *    mandatory [bool = null] - filter by mandatory (null means not to filter)
     *    client [array|object company] - filtery by requester companies
     *    viewer [empresa|usuario] - apply the viewer filters
     *    reference [array] - Used to filter the field uid_empresa_referencia
     * @return string  Returns the SQL filters
     */
    public static function getReqTypeFilters($opts = [])
    {
        $class = get_called_class();

        if (in_array($class, self::getModules()) === false) {
            return false;
        }

        $where = [];
        // $trash       = isset($opts['papelera']) ? $opts['papelera'] : false;
        $download = isset($opts['download']) ? $opts['download'] : false;
        $status = isset($opts['status']) ? $opts['status'] : false;
        $query = isset($opts['q']) ? db::scape($opts['q']) : false;
        $list = isset($opts['list']) ? $opts['list'] : false;
        $mandatory = isset($opts['mandatory']) ? (int) $opts['mandatory'] : null;
        $label = isset($opts['label']) ? $opts['label'] : false;
        $reference = isset($opts['reference']) ? $opts['reference'] : false;
        $client = isset($opts['client']) ? $opts['client'] : false;
        $viewer = isset($opts['viewer']) ? $opts['viewer'] : false;
        $origin = isset($opts['origin']) ? $opts['origin'] : false;

        $docName    = "(SELECT nombre FROM ". TABLE_DOCUMENTO ." d WHERE d.uid_documento = view.uid_documento)";

        if ($client !== false) {
            if ($client instanceof ArrayObjectList) {
                if (count($client) === 0) {
                    return new ArrayRequestList;
                }

                $client = $client->toComaList();
            } else if (is_array($client)) {
                $client  = implode(',', $client);
            } else {
                $client  = $client instanceof empresa ? $client->getUID() : $client;
            }

            $where[] = " uid_empresa_propietaria IN ({$client})";
        }

        if (is_bool($download)) {
            $where[] = "descargar = ". (int) $download;
        }

        if ($status) {
            $condition = [];

            if (in_array(documento::STATUS_WRONG, $status, true) === true) {
                $status = array_merge($status, documento::getInvalidStatus());
            }

            if (true === in_array(documento::STATUS_NOT_VALIDATED, $status, true)) {
                $status = array_merge($status, documento::getNotValidatedStatus());
            }

            foreach ($status as $int) {
                if (is_numeric($int)) {
                    $condition[] = "estado " . ($int == documento::ESTADO_PENDIENTE ? "IS NULL" : "= {$int}");
                }
            }

            // more complex statuses
            $valid          = documento::ESTADO_VALIDADO;
            $attached       = documento::ESTADO_ANEXADO;
            $rejected       = documento::ESTADO_ANULADO;

            // about to expire
            if (in_array(documento::STATUS_EXPIRING, $status, true)) {
                $near = solicituddocumento::getNearExpireSQL($class);
                $condition[] = "(estado = {$valid} OR estado = {$attached}) AND uid_anexo_{$class} IN ($near)";
            }

            // in renovation
            if (in_array(documento::STATUS_RENOVATION, $status, true)) {
                $condition[] = "(reverse_status = {$attached})";
            }

            // temporary validated documents
            if (in_array(documento::STATUS_REJECTING, $status, true)) {
                $condition[] = "(reverse_status = {$rejected})";
            }

            // show documents validables by the $user
            if ($viewer instanceof Iusuario && in_array(documento::STATUS_VALIDABLE, $status, true)) {
                $userCompany = $viewer->getCompany();

                $sub = [];
                $sub[] = "(estado = {$attached} OR reverse_status = {$attached})";
                $sub[] = " FIND_IN_SET({$userCompany->getUID()}, uid_empresa_views)";

                $condition[] = "(". implode(" AND ", $sub) .")";
            }

            $where[] = "(". implode(" OR ", $condition) .")";
        }

        if ($query) {
            $app = \Dokify\Application::getInstance();
            $query = $app['string_normalizer']->clean($query);

            $names = [];

            $names[] = "{$docName} LIKE '%{$query}%'";

            $groups  = TABLE_AGRUPADOR;
            $names[] = "uid_modulo_origen = 11 AND uid_elemento_origen IN (SELECT uid_agrupador FROM {$groups} WHERE nombre LIKE '%{$query}%')";

            $where[] = "(" . implode(" OR ", $names) . ")";
        }

        if ($list) {
            if (is_numeric($list) === false) {
                $list = is_array($list) ? implode(',', $list) : $list->toComaList();
            }

            $where[] = "uid_documento IN ({$list})";
        }

        if (is_numeric($mandatory)) {
            $where[] = "obligatorio = {$mandatory}";
        }

        if ($label) {
            $label  = $label instanceof elemento ? $label->getUID() : $label;

            $table      = TABLE_DOCUMENTO_ATRIBUTO . "_etiqueta";
            $labelsAttr = "SELECT uid_documento_atributo FROM {$table} e WHERE 1
            AND view.uid_documento_atributo = e.uid_documento_atributo
            AND uid_etiqueta IN ({$label})";

            $where[]    = "uid_documento_atributo IN ({$labelsAttr})";
        }


        if ($reference) {
            $references = is_array($reference) ? $reference : array($reference);

            $referenceFilter = array();
            foreach ($references as $reference) {
                if ($reference instanceof empresa) {
                    $last = db::getLastFromSet('uid_empresa_referencia');
                    $referenceFilter[] = "{$last} = {$reference->getUID()}";
                }

                if (null === $reference) {
                    $referenceFilter[] = "uid_empresa_referencia = 0";
                }
            }

            if (count($referenceFilter)) {
                $where[] = '(' . implode(' OR ', $referenceFilter) . ')';
            }
        }

        if (false !== $origin && $origin instanceof agrupador) {
            $organization = $origin->getOrganization();
            $where[] = "(
                (uid_elemento_origen = {$origin->getUID()} AND uid_modulo_origen = 11)
                OR (uid_elemento_origen = {$organization->getUID()} AND uid_modulo_origen = 12)
            )";
        }

        if ($viewer instanceof usuario) {
            // add 1 because the condition starts with AND
            $where[] = " 1 " . $viewer->obtenerCondicionDocumentosView($class);
        } elseif ($viewer instanceof empresa) {
            $where[] = $viewer->getRequestFilter($class);
        }

        return implode(" AND ", $where);
    }


    /**
     * Get all the item requests for the reqtypes
     * @param  $reqTypesAvailable The reqtypes to locate the requests
     * @param  Array $filters Equal self::getReqTypes description
     * @return [ArrayObjectList] Returns the reqtypes with the item requests
     */
    public function getLocateReqTypes ($reqTypesAvailable, $filters = [])
    {
        $reqTypes = new ArrayObjectList;

        foreach ($reqTypesAvailable as $reqTypeAvailable) {
            $filters['list'] = $reqTypeAvailable->getUID();

            $reqType = $this->getReqTypes($filters);

            // Check if we have requests to attach
            if (count($reqType) !== 1) {
                continue;
            }

            $reqTypes[] = $reqType[0];
        }

        return $reqTypes;
    }



    public function getDocumentsId($descargar=false, $obligatorio=null, $papelera = false, $filtro = false, $return=false, $certificacion=null) {
        //$cacheString = "getDocumentsId-{$this}-{$this->user}-".($this->user?$this->user->configValue("viewall"):"null")."-$descargar-". (is_array($obligatorio)?implode("-",$obligatorio):$obligatorio) ."-$papelera-".(is_array($filtro)?implode("-",$filtro):$filtro)."-$return-$certificacion";
        //if( ($estado = $this->cache->getData($cacheString)) !== null ){ return $estado; }


        $sql = "SELECT uid_documento,da.uid_documento_atributo
        FROM ".TABLE_DOCUMENTOS_ELEMENTOS ." de
        INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." da
        USING( uid_documento_atributo, uid_modulo_destino )
        WHERE uid_elemento_destino = $this->uid
        AND da.activo = 1 AND da.replica = 0
        AND uid_modulo_destino = ". $this->getModuleId()."
        ";

        if( is_numeric($descargar) ){
            $sql .=" AND descargar = $descargar";
            if( $descargar ){
                //$sql .= " AND da.replica = 0 ";
            }
        }

        if( is_bool($obligatorio) ){
            $opcional = (int) $obligatorio;
            $sql .= " AND obligatorio = $opcional";
        } else {
            if( is_array($obligatorio) ){ $limitador = $obligatorio; } // nunca pedimos las 2 cosas
        }

        if( is_bool($papelera) ){
            $papelera = (int) $papelera;
            $sql .= " AND papelera = $papelera";
        }

        if( is_bool($certificacion) ){
            $certificacion = (int) $certificacion;
            $sql .= " AND certificacion = $certificacion";
        }

        if( is_array($filtro) && count($filtro) ){
            foreach( $filtro as $campo => $valor ){
                switch( $campo ){
                    case "!cliente":
                        if( $valor instanceof ArrayObjectList ){
                            $sql .= " AND da.uid_empresa_propietaria NOT IN ({$valor->toComaList()}) ";
                        }
                    break;
                    case "estado":
                        $modulo = strtolower($this->getType());
                        $tabla = PREFIJO_ANEXOS . $modulo;
                        if( is_numeric($valor) ){
                            if( $valor != 0 ){
                                $sql .= " AND da.uid_documento_atributo IN (
                                    SELECT a.uid_documento_atributo FROM $tabla a
                                    WHERE a.uid_documento_atributo = da.uid_documento_atributo
                                    AND a.uid_$modulo = $this->uid
                                    AND a.uid_agrupador = de.uid_agrupador
                                    AND a.uid_empresa_referencia = de.uid_empresa_referencia
                                    AND a.estado = ". db::scape($valor) ."
                                )";
                            } else {
                                $sql .= " AND da.uid_documento_atributo NOT IN (
                                    SELECT a.uid_documento_atributo FROM $tabla a
                                    WHERE a.uid_documento_atributo = da.uid_documento_atributo
                                    AND a.uid_agrupador = de.uid_agrupador
                                    AND a.uid_empresa_referencia = de.uid_empresa_referencia
                                    AND a.uid_$modulo = $this->uid
                                )";
                            }
                        } elseif( is_array($valor) && count($valor) ){
                            $sql .= " AND (";

                            $cond = "";
                            if( ( count($valor)==1 && !implode("",$valor)=="0" ) || count($valor) > 1 ){
                                $sql .= " da.uid_documento_atributo IN (
                                    SELECT a.uid_documento_atributo FROM $tabla a
                                    WHERE a.uid_documento_atributo = da.uid_documento_atributo
                                    AND a.uid_$modulo = $this->uid
                                    AND a.uid_agrupador = de.uid_agrupador
                                    AND a.uid_empresa_referencia = de.uid_empresa_referencia
                                    AND a.estado IN ( ".implode(",",$valor)." )
                                ) ";

                                if( in_array("0", $valor) ){
                                    $cond = " OR ";
                                }
                            }


                            if( in_array("0", $valor) ){
                                $sql .= " $cond da.uid_documento_atributo NOT IN (
                                    SELECT a.uid_documento_atributo FROM $tabla a
                                    WHERE a.uid_documento_atributo = da.uid_documento_atributo
                                    AND a.uid_agrupador = de.uid_agrupador
                                    AND a.uid_empresa_referencia = de.uid_empresa_referencia
                                    AND a.uid_$modulo = $this->uid
                                )";
                            }

                            $sql .= " )";
                        }
                    break;
                    case "uid_etiqueta":
                        $tabla = TABLE_DOCUMENTO_ATRIBUTO . "_etiqueta";
                        $sql .=" AND da.uid_documento_atributo IN (
                            SELECT a.uid_documento_atributo FROM $tabla a
                            WHERE a.uid_documento_atributo = da.uid_documento_atributo
                            AND a.uid_etiqueta = '". db::scape($valor) ."'
                        )";
                    break;
                    case "alias":
                        $sql .= " AND da.$campo LIKE '%". db::scape($valor) ."%'";
                    break;
                    case "rebote":
                        $val = db::scape($valor);
                        $sql .= " AND (
                            de.uid_agrupador = '". $val ."'
                            OR da.uid_elemento_origen = '". $val ."'
                            OR (
                                -- Mostrar los documentos solicitados por rebote a las asignaciones por relacion
                                da.uid_elemento_origen IN (
                                    SELECT uid_agrupador FROM ".TABLE_AGRUPADOR ."_elemento WHERE uid_elemento IN (
                                        SELECT aea.uid_agrupador
                                        FROM ".TABLE_AGRUPADOR ."_elemento ae
                                        INNER JOIN ".TABLE_AGRUPADOR ."_elemento_agrupador aea
                                        USING(uid_agrupador_elemento)
                                        WHERE ae.uid_agrupador = '". $val ."'
                                    ) AND uid_modulo = 11

                                -- Mostrar todos los documentos solicitados por cualquier agrupador si es de tipoempresa
                                ) OR da.uid_elemento_origen IN (
                                    SELECT uid_agrupador
                                    FROM ".TABLE_AGRUPADOR." aa INNER JOIN ".TABLE_AGRUPAMIENTO." ag USING(uid_agrupamiento)
                                    WHERE uid_categoria = ".categoria::TYPE_TIPOEMPRESA."

                                -- Mostrar los documentos solicitados por agrupadores asignados por relación
                                ) OR da.uid_elemento_origen IN (
                                    SELECT rel.uid_agrupador FROM ".TABLE_AGRUPADOR ."_elemento ae
                                    INNER JOIN ".TABLE_AGRUPADOR ."_elemento_agrupador rel
                                    USING(uid_agrupador_elemento)
                                    WHERE ae.uid_agrupador = '". $val ."'
                                )
                            )
                            OR da.uid_modulo_origen = 1
                        )";
                    break;
                    case "uid_documento":
                        $sql .= " AND da.uid_documento = ". db::scape($valor);
                    break;
                    default:
                        $sql .= " AND da.$campo = '". db::scape($valor) ."'";
                    break;
                }
            }
        }

        if( $this->user instanceof usuario ){
            $sql .= $this->user->obtenerCondicionDocumentos();
        }

        $sql .= " GROUP BY uid_documento ORDER BY da.alias";
        if( $return ){
            //$this->cache->addData( $cacheString, $sql );
            return $sql;
        }

        if( isset($limitador) ){
            $sql .= " LIMIT ". $limitador[0] .", ". $limitador[1];
        }

        $resultado = $this->db->query($sql, "*", 0);

        $intList = new ArrayIntList($resultado);
        //$this->cache->addData($cacheString, "$intList");

        return $intList;
    }


    public function getStatusImage($usuario, $html = false){
        $cachestring = "getstatusimage-{$this}-{$usuario}";
        if( ($value = $this->cache->getData($cachestring)) !== null ){
            return $value ? json_decode($value, true) : false;
        }

        $lang = Plantilla::singleton();

        $conteos    = $this->informacionDocumentos($usuario, 0, true, null);
        $estados    = array_keys($conteos);
        $imagen     = array("src" => RESOURCES_DOMAIN . "/img/famfam/", "color" => "");
        $company    = $usuario instanceof empresa ? $usuario : $usuario->getCompany();

        if ($this instanceof empresa || $this instanceof empleado || $this instanceof maquina) {
            if (!$company->isSuitableItem($this)) {
                $imagen["title"] = $lang->getString("elemento_no_apto");
                $imagen["color"] .= "black";
                $imagen["src"] .= "exclamation.png";
                $this->cache->set($cachestring, json_encode($imagen));
                return $imagen;
            }
        }

        // Alguno en mal estado | 0 es sin anexar
        if (in_array(0, $estados) || in_array(documento::ESTADO_CADUCADO, $estados) || in_array(documento::ESTADO_ANULADO, $estados)) {
            $imagen["title"] = $lang->getString("algunos_documentos_no_validos");
            $imagen["color"] .= "red";
            $imagen["src"] .= "exclamation.png";
            $this->cache->set($cachestring, json_encode($imagen));
            return $imagen;
        }

        // Si hay algun anexado
        if (in_array(documento::ESTADO_ANEXADO, $estados)) {
            $imagen["title"] = $lang->getString("documentos_pendientes_validar");
            $imagen["color"] .= "orange";
            $imagen["src"] .= "error.png";
            $this->cache->set($cachestring, json_encode($imagen));
            return $imagen;
        }

        // Si
        if (in_array(documento::ESTADO_VALIDADO, $estados)) {
            $imagen["title"] = $lang->getString("todos_los_documento_estan_ok");
            $imagen["color"] .= "green";
            $imagen["src"] .= "accept.png";
            $this->cache->set($cachestring, json_encode($imagen));
            return $imagen;
        }

        $this->cache->set($cachestring, false);
        return false;
    }

    /***
        RETORNA UN OBJETO STANDAR
            @param = usuario
            @param = agrupador...
            @param = solo url de la imagen [ true | false ]...
    */
    public function obtenerEstadoEnAgrupador(Iusuario $usuario, agrupador $agrupador){
        $cacheKey = __CLASS__.'-'.__FUNCTION__.'-'.$this.'-'.$agrupador;
        if (($value = $this->cache->get($cacheKey)) !== NULL) {
            return $value ? json_decode($value) : false;
        }

        $agrupadores = $this->obtenerAgrupadores(null, $usuario, false, false, true);

        if ($agrupadores && $agrupadores->contains($agrupador)) {
            $objetoResultado = new StdClass();

            $modulo = $this->getModuleName();

            $filter = "";
            if (false === $usuario instanceof empleado) {
                $filter = $usuario->obtenerCondicionDocumentosView($modulo);
            }

            $tableName = TABLE_DOCUMENTO . "_{$modulo}_estado";
            $sql = "SELECT estado, count(uid_solicituddocumento) num
            FROM {$tableName} as view
            WHERE uid_{$modulo} = {$this->getUID()}
            AND uid_modulo_origen = 11
            AND uid_elemento_origen = {$agrupador->getUID()}
            AND descargar = 0
            AND obligatorio = 1
            {$filter}
            GROUP BY estado
            ";

            $estados = array();
            $counts = $this->db->query($sql, true);
            foreach($counts as $statusCount) {
                $estados[$statusCount['estado']] = $statusCount['num'];
            }


            if (count($estados) == 1 && isset($estados[2])) {
                $objetoResultado->estado = documento::ESTADO_VALIDADO;
            } elseif (!count($estados)) {
                $objetoResultado->estado = self::STATUS_NO_REQUEST;
            } else {
                $objetoResultado->estado = documento::ESTADO_ANULADO;
            }


            $objetoResultado->img = agrupador::status2img($objetoResultado->estado, $agrupador->getUserVisibleName());

            $this->cache->set($cacheKey, json_encode($objetoResultado), 600);
            return $objetoResultado;
        }

        $this->cache->set($cacheKey, false, 600);
        return false;
    }


    /** DEVUELVE ARRAY DE OBJETOS QUE PIDEN ALGUN DOCUMENTO A ESTE ELEMENTO **/
    public function getDocumentsSolicitantes($descargar=false, $obligatorio=null, $papelera = false, $filtro = false){
        $sql = $this->getDocumentsId($descargar, $obligatorio, $papelera, false, true);
        $search = array( "SELECT uid_documento,", "GROUP BY uid_documento");
        $replace = array( "SELECT uid_elemento_origen, uid_modulo_origen,", "GROUP BY uid_elemento_origen, uid_modulo_origen" );
        $sql = str_replace($search, $replace, $sql);

        $lineas = $this->db->query($sql, true);
        $solicitantes = array();
        foreach($lineas as $fila){
            $modulo = util::getModuleName($fila["uid_modulo_origen"]);
            $solicitante = new $modulo($fila["uid_elemento_origen"], false);
            $solicitantes[] = $solicitante;
        }
        return $solicitantes;
    }


    /** DEVUELVE ARRAY DE OBJETOS QUE PIDEN ALGUN DOCUMENTO A ESTE ELEMENTO **/
    public function getDocumentsEtiquetas($descargar=false, $obligatorio=null, $papelera = false, $filtro = false){
        $sql = $this->getDocumentsId($descargar, $obligatorio, $papelera, false, true);

        $search = array( "SELECT uid_documento,da.uid_documento_atributo", "GROUP BY uid_documento", "FROM agd_docs.documento_elemento de");
        $replace = array( "SELECT uid_documento_atributo", "GROUP BY uid_documento_atributo", "FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." de USING(uid_documento_atributo)" );
        $sql = str_replace($search, $replace, $sql);

        $attrs = $this->db->query($sql, "*", 0);
        if( count($attrs) ){
            $SQL = "SELECT uid_etiqueta FROM ". TABLE_DOCUMENTO_ATRIBUTO . "_etiqueta WHERE uid_documento_atributo IN (". implode(',', $attrs) .") GROUP BY uid_etiqueta";

            $etiqueta = $this->db->query($SQL, "*", 0, "etiqueta");
            return new ArrayObjectList($etiqueta);
        }

        return new ArrayObjectList;
    }/**/

    //OBTENER DOCUMENTO-ATRIBUTO DESCARGABLES Y CARPETAS PUBLICAS CON FICHEROS DESCARGABLES
    public function obtenerCarpetasDocumentosDescargables($elemento, $usuario, $filtroDocumentos = array()) {
        $carpetas = array();
        $solicitudes = array();
        $userCompany = $usuario->getCompany();

        if ($filtroDocumentos && is_array($filtroDocumentos) === false) {
            $filtroDocumentos = [$filtroDocumentos];
        }

        // montamos la sql que nos da la informacion carpetas publicas del elemento deseado
        if ($elemento instanceof agrupador) {
            $sql = "
            SELECT ca.uid_carpeta
                FROM ". TABLE_CARPETA ." c
                INNER JOIN ". TABLE_CARPETA_AGRUPADOR ." ca
                USING ( uid_carpeta )
                WHERE ca.uid_agrupador = ". $elemento->getUID() ."
                AND c.es_publica = 1
            ";

            $carpetas = $this->db->query($sql, "*", 0, 'carpeta');
            $agrupamiento = $elemento->obtenerAgrupamientoPrimario();

            $filtroDocumentos[] = array(
                array(
                    "uid_elemento_origen"   => $elemento->getUID(),
                    "uid_modulo_origen"     => $elemento->getModuleId()
                ),

                array(
                    "uid_elemento_origen"   => $agrupamiento->getUID(),
                    "uid_modulo_origen"     => $agrupamiento->getModuleId()
                )
            );


        } else {
            $filtroDocumentos["uid_elemento_origen"] = $elemento->getUID();
            $filtroDocumentos["uid_modulo_origen"] = $elemento->getModuleId();
        }


        $filtroDocumentos["descargar"] = 1;
        $filtroDocumentos["uid_empresa_referencia"] = $userCompany->getUID();

        $solicitudes = $this->obtenerSolicitudDocumentos($usuario, $filtroDocumentos);

        $result = array_merge((array)$carpetas, (array)$solicitudes);

        return new ArrayObjectList ($result);

    }

    public function obtenerDocumentosCarpetas($usuario,$busqueda,$carpetas){
        //Solo para carpetas publicas. Si se quieren indexar todas las carpetas quitar condicion es_publica = 1
        $ficherosCarpetas = array();

        foreach ($carpetas as $carpeta) {
            if (!$carpeta->indexada()) {
                $indexar = $carpeta->indexar();
            }
            $stringsubcarpetas=$carpeta->obtenerDato('subcarpetas');

            if ($stringsubcarpetas != '' ) {
                $stringsubcarpetas .= ",";
            }
                $stringsubcarpetas .= $carpeta->getUID();

            $sql = "
                SELECT fc.uid_fichero
                    FROM ". TABLE_FICHERO_CARPETA ." fc
                    INNER JOIN ". TABLE_FICHERO ." f
                    USING ( uid_fichero )
                    WHERE fc.uid_carpeta IN (".$stringsubcarpetas.")
                    AND f.nombre LIKE '%".$busqueda."%'
                ";
                $listaFicherosCarpeta =  $this->db->query($sql, "*", 0, 'fichero');

                $ficherosCarpetas = array_merge((array)$ficherosCarpetas, (array)$listaFicherosCarpeta);
        }
        return  $ficherosCarpetas;
    }

    //OBTENER FICHEROS DESCARGABLES Y CARPETAS PUBLICAS CON FICHEROS DESCARGABLES
    public function obtenerBusquedaDocumentosDescargables($busqueda , $usuario){

        $docs = array();
        $userCompany = $usuario->getCompany();


        $carpetas = $elementoCarpetas = array();

        $coleccionEmpresas = $this->getEmpresasAsignadosConDescargables($usuario);
        $coleccionAgrupadores = $this->getAgrupadoresAsignadosConDescargables($usuario);

        $coleccion =new ArrayObjectList (array_merge((array)$coleccionEmpresas, (array)$coleccionAgrupadores));

        foreach ($coleccion as $elemento) {
            $filtroDocumentos = array();
            // montamos la sql que nos da la informacion carpetas publicas del elemento deseado
            if ($elemento instanceof agrupador) {
                $sql = "
                SELECT ca.uid_carpeta
                    FROM ". TABLE_CARPETA ." c
                    INNER JOIN ". TABLE_CARPETA_AGRUPADOR ." ca
                    USING ( uid_carpeta )
                    WHERE ca.uid_agrupador = ". $elemento->getUID() ."
                    AND c.es_publica = 1
                ";

                $elementoCarpetas = $this->db->query($sql, "*", 0, 'carpeta');
                $agrupamiento = $elemento->obtenerAgrupamientoPrimario();

                $filtroDocumentos[] = array(
                    array(
                        "uid_elemento_origen"   => $elemento->getUID(),
                        "uid_modulo_origen"     => $elemento->getModuleId()
                    ),

                    array(
                        "uid_elemento_origen"   => $agrupamiento->getUID(),
                        "uid_modulo_origen"     => $agrupamiento->getModuleId()
                    )
                );


            } else {
                $filtroDocumentos["uid_elemento_origen"] = $elemento->getUID();
                $filtroDocumentos["uid_modulo_origen"] = $elemento->getModuleId();
            }


            $filtroDocumentos["descargar"] = 1;
            $filtroDocumentos["uid_empresa_referencia"] = $userCompany->getUID();

            if ($busqueda instanceof elemento) {
                $filtroDocumentos["related"] = $busqueda;
            } elseif ($busqueda) {
                $filtroDocumentos["alias"] = $busqueda;
            }

            $solicitudes = $this->obtenerSolicitudDocumentos($usuario, $filtroDocumentos);

            $carpetas = array_merge((array)$carpetas, (array)$elementoCarpetas);

            $docs = array_merge((array)$solicitudes, (array)$docs);

        }

        $documentosCarpetas = $this->obtenerDocumentosCarpetas($usuario, $busqueda, $carpetas);

        $docs = array_merge((array)$documentosCarpetas, (array)$docs);

        return new ArrayObjectList (array_unique($docs));

    }



            /** Coleccion de objetos documento asociados que el solicitante pide al este elemento **/
    public function obtenerAgrupamientosConDocumentos($usuario,$filters = array()){
        $sql = "    SELECT uid_elemento_origen FROM ". TABLE_DOCUMENTO. "_". $this->getModuleName(). "_estado view
                                        WHERE uid_". $this->getModuleName(). " = ". $this->getUID() ."
                                        AND descargar = 1
                                        AND uid_modulo_origen = 12
                                        ". $usuario->obtenerCondicionDocumentosView($this->getModuleName());

        // Podemos tener varios filtros
        foreach($filters as $key => $filter){
                switch($key){
                    default: $sql .= " AND $key = '$filter' "; break;
                }
        }

        $sql .= " GROUP BY uid_elemento_origen ";


        return new ArrayObjectList ( $this->db->query($sql, "*", 0, 'agrupamiento') );
    }


    public function obtenerAgrupadoresElemento($usuario, $sqlOptions = []){
        $sql = "    SELECT uid_agrupador FROM ". TABLE_AGRUPADOR. "_elemento
                                        WHERE uid_elemento = ". $this->getUID() ."
                                        AND uid_modulo = ". $this->getModuleId().
                                        " GROUP BY uid_agrupador ";

        if (count($sqlOptions)) {

            if (isset($sqlOptions['limit'])) {
                if (is_numeric($sqlOptions['limit'])) {
                    $sql .= " LIMIT ".$sqlOptions['limit'];
                } elseif (count($sqlOptions['limit']) == 2) {
                    $sql .= " LIMIT ".$sqlOptions['limit'][0].", ".$sqlOptions['limit'][1];
                }
            }

            if (isset($sqlOptions['count']) && $sqlOptions['count']) {
                $sql = "SELECT count(uid_agrupador) FROM ". TABLE_AGRUPADOR. "_elemento
                        WHERE uid_elemento = ". $this->getUID() ."
                        AND uid_modulo = ". $this->getModuleId();
                return $this->db->query($sql, 0, 0);
            }

        }

        return new ArrayObjectList ( $this->db->query($sql, "*", 0, 'agrupador') );
    }


    //OBTENER AGRUPADORES Y EMPRESAS CON FICHEROS DESCARGABLES
    public function getAgrupadoresAsignadosConDescargables($usuario){
        // montamos la sql que nos da la informacion de agrupadores con carpetas publicas y documentos descargables
        $listaAgrupamientos = $this->obtenerAgrupamientosConDocumentos($usuario)->toComaList();
        $listaAgrupadores = $this->obteneragrupadores()->toComaList();

        $sql = "
        SELECT view.uid_elemento_origen FROM ". TABLE_DOCUMENTO. "_". $this->getModuleName(). "_estado view
            WHERE uid_". $this->getModuleName(). " = ". $this->getUID() ."
            AND view.descargar = 1
            AND view.uid_modulo_origen = 11
            ". $usuario->obtenerCondicionDocumentosView($this->getModuleName()) ."
            GROUP BY view.uid_elemento_origen
        UNION
        SELECT ae.uid_agrupador
            FROM ". TABLE_AGRUPADOR ."_elemento ae
            INNER JOIN ". TABLE_CARPETA_AGRUPADOR ." ca
            USING ( uid_agrupador )
            INNER JOIN  ". TABLE_CARPETA ." c ON c.uid_carpeta = ca.uid_carpeta
            WHERE ae.uid_elemento = ". $this->getUID() ."
            AND ae.uid_modulo = ". $this->getModuleId() ."
            AND c.es_publica = 1
            GROUP BY ae.uid_agrupador
        ";

        if ($listaAgrupamientos) {
            $sql .= " UNION
                    SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ."
                            WHERE uid_agrupamiento IN ( $listaAgrupamientos )
                            AND uid_agrupador IN (  $listaAgrupadores )
                            GROUP BY uid_agrupador  ";
        }

        $solicitantes = $this->db->query($sql, "*", 0, 'agrupador');

        if ($solicitantes && count($solicitantes)) return new ArrayObjectList ($solicitantes);
        return new ArrayObjectList;

    }

            //OBTENER AGRUPADORES Y EMPRESAS CON FICHEROS DESCARGABLES
    public function getEmpresasAsignadosConDescargables($usuario){

        // montamos la sql que nos da la informacion de empresas con documentos descargables
        $sql = "
        SELECT view.uid_elemento_origen FROM ". TABLE_DOCUMENTO. "_". $this->getModuleName(). "_estado view
            WHERE uid_". $this->getModuleName(). " = ". $this->getUID() ."
            AND view.descargar = 1
            AND view.uid_modulo_origen = 1
            ". $usuario->obtenerCondicionDocumentosView($this->getModuleName()) ."
            GROUP BY view.uid_elemento_origen
        ";

        $empresas = $this->db->query($sql, "*", 0, 'empresa');

        return new ArrayObjectList ($empresas);

    }

    /**
     * check if a child item is valid for a company, not combined
     * @param  [empresa]  $company [the company to check]
     * @param  [usuario]  $user    [the active user]
     * @return boolean          [if the child item is valid or not FOR the @company (just the child item)]
     */
    public function isOkForCompany ($company, Iusuario $user = null)
    {
        $opts = [
            'reference' => [$company, null],
            'mandatory' => 1,
            'viewer' => $user
        ];

        $summary = $this->getReqTypeSummary($opts);

        return $summary->allAreValids();
    }

    /***
       *
       *
       *
       */
    public function getReqTypeSummary ($opts = [])
    {
        $class      = $this->getModuleName();
        $filter     = [];

        $filter[]   = "uid_{$class} = {$this->getUID()}";

        $summary    = new \Dokify\RequirementTypeRequestSummary;

        $filter     = implode(" AND ", $filter);

        // add reqtype filters
        $filter .= " AND " . self::getReqTypeFilters($opts);

        $table      = TABLE_DOCUMENTO . "_{$class}_estado";
        $pending    = documento::ESTADO_PENDIENTE;

        $SQL    = "SELECT IFNULL(estado, {$pending}) status, count(uid_solicituddocumento) num
        FROM {$table} view
        WHERE 1
        AND {$filter}
        GROUP BY estado";

        if ($rows = $this->db->query($SQL, true)) {
            foreach ($rows as $row) {
                $summary[$row['status']] = $row['num'];
            }
        }

        $statuses = documento::getAllStatus();
        foreach ($statuses as $status) {
            if (empty($summary[$status])) {
                $summary[$status] = 0;
            }
        }

        return $summary;
    }


    /***
        DEPRECATED, use self::getReqTypeSummary instead
    */
    public function getNumberOfDocumentsByStatus(Iusuario $usuario = null, $estado = false, $obligatorio = null, $papelera = false, $descargar = 0, $columns = MYSQLI_BOTH, $all = false)
    {
        $cacheKey = implode('-', [$this, __FUNCTION__, $usuario, $estado, $obligatorio, $papelera, $descargar, $columns, $all]);
        if (null !== ($value = $this->cache->getData($cacheKey))) {
            return json_decode($value, true);
        }

        $modulo = $this->getModuleName();
        $condicion = $usuario ? $usuario->obtenerCondicionDocumentosView($modulo) : '';
        $table = TABLE_DOCUMENTO . "_{$modulo}_estado";

        $SQL = "SELECT uid_documento_atributo, if(estado IS NULL, '0', estado) estado FROM {$table} view WHERE uid_$modulo = {$this->getUID()}";

        if (is_numeric($estado)) {
            $estado = db::scape($estado);
            $SQL .= " AND if(estado IS NULL, ".documento::ESTADO_PENDIENTE.", estado) = $estado";

        // this is a hack, we need to refactor this method!
        } elseif ($estado instanceof empresa) {
            $origin = $estado->getOriginCompanies();
            $SQL .= " AND uid_empresa_propietaria IN ({$origin->toComaList()})";
        }

        // filtramos por descargar
        if (is_numeric($descargar)) {
            $descargar = db::scape($descargar);
            $SQL .= " AND descargar = $descargar";
        }

        // filtramos por obligatorio
        if (is_bool($obligatorio)) {
            $opcional = (int) $obligatorio;
            $SQL .= " AND obligatorio = $opcional";
        }

        $SQL .= $condicion; // añadir condicion de usuario
        $SQL .= " GROUP BY uid_documento_atributo"; // agrupar
        $datos = $this->db->query($SQL, true);

        $cuentas = array();
        $estados = documento::getAllStatus();

        // Queremos todos los estados, incluso si estan a 0
        if ($all === true) {
            foreach ($estados as $estado) {
                if ($columns === MYSQLI_NUM || $columns === MYSQLI_BOTH) {
                    if (!isset($cuentas[$estado])) {
                        $cuentas[$estado] = 0;
                    }
                }

                if ($columns === MYSQLI_ASSOC || $columns === MYSQLI_BOTH) {
                    $nameStatus = documento::status2String($estado);
                    if (!isset($cuentas[$nameStatus])) {
                        $cuentas[$nameStatus] = 0;
                    }
                }
            }
        }

        foreach ($datos as $doc) {
            $numberStatus = $doc["estado"];
            $nameStatus = documento::status2String($doc["estado"]);

            if (!$numberStatus) {
                $numberStatus = 0;
            }

            if ($columns === MYSQLI_NUM || $columns === MYSQLI_BOTH) {
                if (!isset($cuentas[$numberStatus])) {
                    $cuentas[$numberStatus] = 0;
                }
                $cuentas[$numberStatus]++;
            }

            if ($columns === MYSQLI_ASSOC || $columns === MYSQLI_BOTH) {
                if (!isset($cuentas[ $nameStatus ])) {
                    $cuentas[ $nameStatus ] = 0;
                }
                $cuentas[ $nameStatus ]++;
            }
        }

        $this->cache->set($cacheKey, json_encode($cuentas));
        return $cuentas;
    }


    public function obtenerSolicitantesPorModulo(usuario $usuario = NULL){
        $datosSolicitantes = array();
        $arrayModulosSolicitantes = config::modulosSolicitantes( $this->getModuleId() );

        if( !$usuario instanceof usuario ){
            if( isset($_SESSION) ){
                $usuario = usuario::getCurrent();
            }
        }


        if( ( $usuario instanceof usuario ) ){
            $recursividad = $usuario->getCompany()->maxRecursionLevel();
        } else {
            $recursividad = empresa::DEFAULT_DISTANCIA;
        }


        foreach( $arrayModulosSolicitantes as $moduloSolicitante ){
            $metodo = array( $this, $moduloSolicitante["metodo"] );
            //$limit = ( $this instanceof solicitable ) ? false : $usuario; // si le pasamos el usuario a la busqueda de empresas limita cosas que aqui no queremos (busqueda recursiva)
            $elementosSolicitantes = call_user_func($metodo, $recursividad, false);

            if( $moduloSolicitante["uid_modulo"] == 1 && ( $this instanceof empresa ) ){
                $datosSolicitantes[ $moduloSolicitante["uid_modulo"] ][] = $this;
            }

            foreach( $elementosSolicitantes as $elementoSolicitante ){
                //QUIZAS TENGAMOS QUE MATIZAR ESTO MAS TARDE
                $datosSolicitantes[ $moduloSolicitante["uid_modulo"] ][] = $elementoSolicitante;
            }
        }

        unset($arrayModulosSolicitantes);unset($recursividad);unset($moduloSolicitante);unset($elementosSolicitantes);

        return $datosSolicitantes;
    }

    public function tieneDocumentacion(){
        $sql = "SELECT count(uid_modulo) FROM ". TABLE_MODULOS ." WHERE documentos = 1 AND nombre = '". $this->tipo ."'";
        return (bool) $this->db->query($sql, 0, 0);
    }



    /** Coleccion de objetos documento_atributo asociados que el solicitante pide al este elemento
        solo disponibles para objetos a los que se le piden documentos
     **/
    public function obtenerAtributosDeSolicitante(usuario $usuario = NULL, Ielemento $solicitante, $descargar=0, $papelera=null){

        $coleccion = array();
        $uid = $solicitante->getUID();
        $module = $solicitante->getModuleId();
        $descargar = (int)  $descargar;

        $sql = "SELECT uid_documento_atributo
                FROM ". TABLE_DOCUMENTO_ATRIBUTO ." attr
                WHERE uid_elemento_origen = $uid
                AND uid_modulo_origen = $module
                AND descargar = {$descargar}
        ";

        if( $usuario instanceof usuario ){
            $empresa = $usuario->getCompany();
            $sql .= " AND uid_empresa_propietaria = {$empresa->getUID()} ";
        }

        if( is_bool($papelera) ) {
            $val = ( $papelera ) ? 1 : 0;
            $sql .= " AND attr.uid_documento_atributo IN (
                SELECT rel.uid_documento_atributo FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." rel
                WHERE rel.uid_elemento_destino = ". $this->getUID() ."
                AND rel.uid_modulo_destino = ". $this->getModuleId() ."
                AND rel.uid_documento_atributo = attr.uid_documento_atributo
                AND papelera = $val
            )";
        }

        if( is_bool($papelera) ) $sql .= " AND activo = " . ( $papelera === false ? 1 : 0 );

        $sql .= " GROUP BY uid_documento_atributo";

        $arrayIds = $this->db->query($sql, "*", 0);

        foreach( $arrayIds as $uid ){
            $atributo = new documento_atributo($uid);
            $coleccion[] = $atributo;
        }
        return $coleccion;
    }

    /** Coleccion de objetos documento asociados que el solicitante pide al este elemento **/
    public function obtenerDocumentosDeSolicitante(usuario $usuario, $solicitante, $descargar=0){
        $coleccionDocumentos = array();
        $arrayDocumentos = $this->getDocuments($descargar);
        foreach( $arrayDocumentos as $documento ){
            $solicitantes = $documento->obtenerSolicitantes( $usuario );

            foreach( $solicitantes as $solicitanteDocumento ){
                if( $solicitanteDocumento->getUID() == $solicitante->getUID() && $solicitanteDocumento->getType() == $solicitante->getType() ){
                    $coleccionDocumentos[] = $documento;
                    break;
                }
            }
        }
        return $coleccionDocumentos;
    }

    /**
     * Get a status for this "solicitable" in a company (as client)
     * @param  [usuario]    $usuario       The context user
     * @param  integer      $descarga      Downloadable documents or not (not really usefull)
     * @param  [bool|null]  $obligatorio   count mandatory documents (yes/no/both)
     * @param  [empresa]    $companyFilter The client company instance
     * @return [int]        return a solicitable constant representing the status
     */
    public function getStatusWithCompany($usuario = NULL, $descarga=0, $obligatorio = null, $companyFilter = NULL){
        $companyFilter = $companyFilter instanceof empresa ? new ArrayObjectList([$companyFilter, $this]) : $companyFilter;

        $docs = $this->obtenerEstadoDocumentos($usuario, $descarga, $obligatorio, $companyFilter);

        if (count($docs) == 1 && isset($docs[2])) {
            return self::STATUS_VALID_DOCUMENT;
        } elseif(count($docs)) {
            return self::STATUS_INVALID_DOCUMENT;
        } else {
            return self::STATUS_NO_REQUEST;
        }
        return false;
    }


    public function getMessageWithCompany($usuario = NULL, $descarga=0, $obligatorio = null, $companyFilter = NULL){
        $tpl = Plantilla::singleton();
        $statusWithCompany = $this->getStatusWithCompany($usuario, $descarga, $obligatorio, $companyFilter);
        switch ($statusWithCompany) {
            case self::STATUS_NO_REQUEST:
                $message = $tpl->getString("cadena_no_valida_not_request");
                break;
            case self::STATUS_INVALID_DOCUMENT:
                $message = $tpl->getString("cadena_cliente_no_valida");
                break;
            case self::STATUS_VALID_DOCUMENT:
                $message = $tpl->getString("cadena_valida");
                break;

            default:
                $message = false;
                break;
        }
        return $message;
    }

    /***
       * Returns bool if the item is valid for the @user param viewer
       *
       * @param user the user to filter with
       * @param opts concrete cases filters
       *
       */
    public function isValid (usuario $user, $opts = [])
    {
        if (empty($opts['mandatory'])) {
            $opts['mandatory'] = true;
        }

        $opts['viewer'] = $user;

        if (isset($opts['client']) && $opts['client'] instanceof empresa) {
            $opts['client'] = $opts['client']->getOriginCompanies();
        }

        $summary = $this->getReqTypeSummary($opts);

        return $summary->allAreValids();
    }


    /***
       * Returns the reqTypes wich the user needs to worry about
       *
       *
       *
       */
    public function getAlertReqTypes(Iusuario $user, $opts = [], $returnType = 'objectList')
    {
        $status = [
            documento::STATUS_REJECTING,
            documento::STATUS_EXPIRING,
            documento::ESTADO_ANULADO,
            documento::ESTADO_CADUCADO,
            documento::ESTADO_PENDIENTE
        ];

        $opts['status'] = $status;
        $opts['viewer'] = $user;

        return $this->getReqTypes($opts, $returnType);
    }

    /***
       * Returns the reqTypes wich are in invalid states
       *
       *
       *
       */
    public function getInvalidReqTypes(usuario $user, $opts = [], $returnType = 'objectList'){
        $status = [documento::ESTADO_ANULADO, documento::ESTADO_CADUCADO, documento::ESTADO_PENDIENTE];
        $opts['status'] = $status;
        $opts['viewer'] = $user;

        return $this->getReqTypes($opts, $returnType);
    }

    /**
     * DEPRECTATED, use self::getReqTypeSummary instead
     */
    public function obtenerEstadoDocumentos ($usuario = null, $descarga=0, $obligatorio = null, $companyFilter = null, empresa $client = null)
    {
        $cacheString = "obtenerEstadoDocumentos-{$usuario}-{$this}-$descarga-$obligatorio-{$client}";

        if (null !== ($dato = $this->cache->getData($cacheString))) {
            return $dato;
        }

        $tipo       = $this->getModuleName();
        $table      = TABLE_DOCUMENTO . "_{$tipo}_estado";
        //$viewALL = $usuario instanceof usuario && (bool) $usuario->configValue("viewall");

        $sql = "
            SELECT count(uid_solicituddocumento) as num, estado FROM $table view
            WHERE uid_{$tipo} = {$this->getUID()}
            AND descargar = ". ((int) $descarga);

        if (isset($companyFilter)) {
            $isContratacionList = $companyFilter instanceof empresaContratacion;
            $firstCompany = $isContratacionList ? $companyFilter->getCompanyHead() : $companyFilter[0];
            $sql .= " AND FIND_IN_SET('{$firstCompany->getUID()}', uid_empresa_views) ";
            $referenciaList = count($companyFilter) > 1 ? $companyFilter->toComaList() : $firstCompany->getUID();
            $sql .= " AND ( uid_empresa_referencia LIKE '{$referenciaList}' OR uid_empresa_referencia = 0 )";
        }

        if ($obligatorio !== null) {
            $sql .= " AND obligatorio = ".((int) $obligatorio);
        }


        if ($client) {
            if ($client instanceof ArrayObjectList) {
                if (count($client) === 0) {
                    return new ArrayRequestList;
                }

                $client = $client->toComaList();
            } else {
                $client  = $client instanceof empresa ? $client->getUID() : $client;
            }

            $sql .= " AND uid_empresa_propietaria IN ({$client})";
        }

        if (isset($usuario) && $usuario instanceof usuario) {
            $sql .= $usuario->obtenerCondicionDocumentosView($tipo);
        }

        if (isset($usuario) && $usuario instanceof empresa) {
            $sql .= " AND " . $usuario->getRequestFilter($tipo);
        }

        $sql .= " GROUP BY estado ";

        $result = $this->db->query($sql, true);

        $estadosDocumentacion = array();
        if (count($result) && isset($result[0])) {
            foreach ($result as $infoestado) {
                $estado = is_numeric($infoestado["estado"]) ? $infoestado["estado"] : documento::ESTADO_PENDIENTE;
                $estadosDocumentacion[$estado] = self::status2String($estado); //$infoestado["num"];
            }
        }

        asort($estadosDocumentacion);

        return $estadosDocumentacion;

    }

    /**
        retorna si tiene o no, documentos en un estado determinado
    */
    public function getNumberOfDocumentsWithState($estado, usuario $usuario){
        $docsInfo = $this->obtenerEstadoDocumentos($usuario);
        if( isset($docsInfo[$estado]) ){
            return 1; //count($docsInfo[$estado]);
        } else {
            return 0;
        }
    }

    /**
        CONTEO POR ESTADO DE LOS DOCUMENTOS - VERSION PROCEDIMIENTO
    */
    public function informacionDocumentos($usuario, $descarga=0, $obligatorio=null, $certificacion=false){
        $cacheString = "informacionDocumentos-{$usuario}-{$this}-$descarga-$obligatorio-$certificacion";
        if (null !== ($dato = $this->cache->getData($cacheString))) {
            return json_decode($dato);
        }


        $estados = array();


        $tipo = $this->getModuleName();
        $uidmodulo = $this->getModuleId();
        $table = TABLE_DOCUMENTO . "_{$tipo}_estado";

        $sql = "SELECT estado FROM $table view
        WHERE uid_{$tipo} = {$this->getUID()}
        AND descargar = ". ((int) $descarga);

        if (is_bool($certificacion)) {
            $sql .= " AND uid_documento_atributo IN (
                SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO . " da
                WHERE da.uid_documento_atributo = view.uid_documento_atributo
                AND certificacion = " . (int) $certificacion . "
            )";
        }

        if ($obligatorio !== null) $sql .= " AND obligatorio = ".((int) $obligatorio);
        if ($usuario instanceof usuario) $sql .= $usuario->obtenerCondicionDocumentosView($tipo);
        if ($usuario instanceof empresa) $sql .= " AND " . $usuario->getRequestFilter($tipo);

        $informacion = $this->db->query($sql, true);

        foreach ($informacion as $i => $infoatributo) {
            $estado = $infoatributo["estado"];

            if (!is_numeric($estado)) {
                $estado = 0;
            }

            if (!isset($estados[$estado])) {
                $estados[$estado] = 0;
            }

            $estados[$estado] = $estados[$estado] + 1;
        }

        $this->cache->addData($cacheString, json_encode($estados));
        return $estados;
    }

    public function obtenerSolicitantesIndirectos(empresa $requesterFilter = NULL){
        // Vamos a extraer los solicitantes indirectos
        $sqlIndirectos = "
            SELECT aea.uid_agrupador, ae.uid_agrupador as referencia, a.uid_agrupamiento
            FROM ". TABLE_AGRUPADOR ."_elemento_agrupador  aea
            INNER JOIN ". TABLE_AGRUPADOR ."_elemento  ae
            USING( uid_agrupador_elemento )
            INNER JOIN ". TABLE_AGRUPADOR ." a
            ON a.uid_agrupador = ae.uid_agrupador
            WHERE ae.uid_elemento = ". $this->getUID() ."
            AND ae.uid_modulo = ". $this->getModuleId() ."
            AND a.papelera = 0
        ";

        if ($requesterFilter) {
            $sqlIndirectos .= " AND a.uid_empresa = {$requesterFilter->getUID()}";
        }

        $tabla = $this->db->query($sqlIndirectos, true);

        $solicitantes = $agrupamientos = array();
        foreach( $tabla as $linea ){
            $referencia = new agrupador($linea["referencia"]); // para conocer el motivo de este elemento
            $agrupamiento = new agrupamiento($linea["uid_agrupamiento"]);
            $agrupador = new agrupador($linea["uid_agrupador"]);

            $agrupador->referencia = $agrupamiento->referencia = $referencia;
            $solicitantes[] = $agrupador;
            $agrupamientos[] = $agrupamiento;
        }

        $agrupamientos = array_unique($agrupamientos);
        $solicitantes = array_merge($solicitantes, $agrupamientos);


        return $solicitantes;
    }


    /**
     * [actualizarSolicitudDocumentos update the table documento_elemento for this item]
     * @param  [Iusuario] $usuario [only usefull to clear some cache values]
     * @return [void]
     *
     */
    public function actualizarSolicitudDocumentos (Iusuario $usuario = NULL)
    {
        $atributos      = $this->calcularAtributosSolicitados();
        $typeOfCompany  = new categoria(categoria::TYPE_TIPOEMPRESA);

        if (is_traversable($atributos) && count($atributos)) {
            //dump($atributos->toIntList());
            // IDs de item y modulo
            $uidModuloDestino = $this->getModuleId();
            $uidElementoDestino = $this->getUID();

            $tmpTableName = "tmp_solicitud_" . uniqid();
            $tempSQL = "CREATE TEMPORARY TABLE `$tmpTableName` (
                `uid_documento_elemento` int(15) NOT NULL AUTO_INCREMENT,
                `uid_documento_atributo` int(10) NOT NULL,
                `uid_elemento_destino` int(10) NOT NULL,
                `uid_agrupador` int(11) NOT NULL DEFAULT '0' COMMENT 'Solo si el agrupador es indirecto',
                `uid_empresa_referencia` varchar(255) NOT NULL DEFAULT '0',
                `uid_modulo_destino` int(10) NOT NULL,
                `generated_by` varchar(255) DEFAULT NULL,
                `fecha_carga` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `papelera` int(1) NOT NULL DEFAULT '0',
                PRIMARY KEY (`uid_documento_elemento`),
                UNIQUE KEY `UNIQUE` (
                `uid_documento_atributo`,
                `uid_elemento_destino`,
                `uid_modulo_destino`,
                `uid_agrupador`,
                `uid_empresa_referencia`
                ),
                KEY `DESTINO` (`uid_elemento_destino`,`uid_modulo_destino`),
                KEY `uid_elemento_destino` (`uid_elemento_destino`)
            ) ENGINE=MyISAM ";

            if (!$this->db->query($tempSQL)) {
                return false;
            }

            $empresas = $this->getCompanies();

            if ($this instanceof empresa) {
                $listaEmpresas = $this->getUID();
                $companiesElement = new ArrayObjectList(array($this));
            } else {
                $listaEmpresas = count($empresas) ? $empresas->toComaList() : '0';
                $companiesElement = $empresas;
            }

            $empresasNoHabilitadas = $this->obtenerEmpresasClienteNoHabilitadas();

            foreach ($atributos as $attr) {
                $empresa        = $attr->getCompany();
                $origin         = $attr->getElement();
                $referenciar    = (int) $attr->obtenerDato("referenciar_empresa");

                // origin types
                $isTypeOfCompany    = $origin instanceof agrupador && $typeOfCompany->compareTo($origin->getCategory());
                $isFromCompany      = $origin instanceof empresa || $isTypeOfCompany;
                $originIsGroup      = $origin instanceof agrupador && $isTypeOfCompany == false;
                $generatedBy        = 'NULL';
                $generators         = [];

                if (true === $originIsGroup) {
                    $onlyCoordinator    = $attr->hasOnlyCoordinator();
                    if ($onlyCoordinator && $this->compareTo($origin->getCoordinator()) === false) {
                        continue;
                    }
                }

                if (true === $originIsGroup && false !== $assignment = $this->getAssignment($origin)) {
                    $entity     = $assignment->asDomainEntity();
                    $versions   = $this->app['assignment_version.repository']->fromAssignment($entity);

                    if (0 !== count($versions)) {
                        foreach ($versions as $version) {
                            $generators[] = $version->company->uid();
                        }

                        $generatedBy = "'". implode(',', $generators) ."'";
                    }
                }

                $conditionalIntList = new ArrayIntList();
                if ($conditionalSet = trim($attr->obtenerDato("uid_agrupador_condicion"))) {
                    $conditionalIntList = new ArrayIntList(explode(',', $conditionalSet));
                }

                $condicionAsignado  = (bool) !$attr->obtenerDato("condicion_negativa");
                $condicionOR        = (bool) $attr->obtenerDato("condition_type");

                // Tiene alguna condicion que debemos verificar ?
                if (0 !== count($conditionalIntList) && $referenciar !== documento_atributo::REF_TYPE_COMPANY) {
                    if ($condicionOR && $condicionAsignado) {
                        $assignments = TABLE_AGRUPADOR ."_elemento";
                        $versions    = DB_DATA . '.assignment_version';
                        $SQL = "SELECT count(uid_elemento)
                        FROM {$assignments}
                        LEFT JOIN {$versions}
                        ON {$assignments}.uid_agrupador_elemento = {$versions}.uid_assignment
                        WHERE 1
                        AND uid_modulo = {$uidModuloDestino}
                        AND uid_elemento = {$this->getUID()}
                        AND uid_agrupador IN ({$conditionalSet})";

                        if ($this instanceof childItemEmpresa) {
                            $SQL .= " AND (
                                {$versions}.uid_company IN ({$listaEmpresas})
                                OR
                                {$versions}.uid_company IS NULL
                            )";
                        }

                        $SQL .= " LIMIT 1";

                        $asignado = (bool) $this->db->query($SQL, 0, 0);

                        if (!$asignado) {
                            continue;
                        }

                    } else {
                        $skip = false;

                        foreach ($conditionalIntList as $uid) {
                            if (false === is_numeric($uid)) {
                                error_log(
                                    "Not numeric values in uid_empresa_referencia ({$conditionalSet}) column
                                    for attr {$attr->getUID()} of element {$uidElementoDestino}
                                    module: {$uidModuloDestino})"
                                );
                                continue;
                            }

                            $assignments = TABLE_AGRUPADOR ."_elemento";
                            $versions    = DB_DATA . '.assignment_version';
                            $SQL = "SELECT count(uid_elemento)
                            FROM {$assignments}
                            LEFT JOIN {$versions}
                            ON {$assignments}.uid_agrupador_elemento = {$versions}.uid_assignment
                            WHERE 1
                            AND uid_modulo = $uidModuloDestino
                            AND uid_elemento = {$this->getUID()}
                            AND uid_agrupador = {$uid}";

                            if ($this instanceof childItemEmpresa) {
                                $SQL .= " AND (
                                    {$versions}.uid_company IN ({$listaEmpresas})
                                    OR
                                    {$versions}.uid_company IS NULL
                                )";
                            }

                            $SQL .= " LIMIT 1";

                            $agrupadorAsignado = (bool) $this->db->query($SQL, 0, 0);

                            // Nuestra condicion es que si tiene que tener cada uno de los agrupadores asignados
                            if ($condicionAsignado) {
                                // Indica que no esta asignado
                                if ($agrupadorAsignado === false) {
                                    $skip = true;
                                    break;
                                }
                            } else {
                                // Nuestra condicion es que no puede tener ningun agrupador asignado
                                if ($agrupadorAsignado === true) {
                                    $skip = true;
                                    break;
                                }
                            }
                        }

                        // Si no se cumple correctamente alguna de las condiciones
                        if ($skip) {
                            continue;
                        }
                    }
                }


                // Tiene alguna condicion que debemos verificar ?
                if ($conjunto = trim($attr->obtenerDato("uid_agrupamiento_condicion"))) {
                    $intList        = new ArrayIntList(explode(',', $conjunto));
                    $assignments    = TABLE_AGRUPADOR ."_elemento";
                    $groups         = TABLE_AGRUPADOR;
                    $SQL = "SELECT count(uid_elemento) FROM {$assignments}
                    INNER JOIN {$groups} USING (uid_agrupador)
                    WHERE uid_modulo = $uidModuloDestino
                    AND uid_elemento = {$this->getUID()}
                    AND uid_agrupamiento IN ({$intList->toComaList()})
                    LIMIT 1";

                    $asignado = (bool) $this->db->query($SQL, 0, 0);

                    // do not request this doc if no group of this kind is assigned
                    if (!$asignado) {
                        continue;
                    }
                }



                // Si no es una empresa, vamos a ver los condicionales de empresa
                if ($uidElementoDestino !== 1) {
                    // Tiene alguna condicion que debemos verificar ?
                    if ($conjunto = trim($attr->obtenerDato("uid_agrupador_company_condicion"))) {
                        $companyNegativeCondition = (bool)$attr->obtenerDato("company_negative_condition");

                        $intList = new ArrayIntList(explode(',', $conjunto));

                        $source = TABLE_AGRUPADOR . "_elemento INNER JOIN " . TABLE_AGRUPADOR . " USING (uid_agrupador)";
                        $SQL = "SELECT count(uid_elemento) FROM {$source} WHERE 1
                        AND uid_modulo = 1
                        AND uid_elemento IN ({$listaEmpresas})
                        AND uid_agrupador IN ({$intList->toComaList()}) LIMIT 1";

                        $assignedByCompanyCondition = (bool)$this->db->query($SQL, 0, 0);

                        // do not request this doc if group do not pass the condition
                        if (($companyNegativeCondition === false && $assignedByCompanyCondition === false)
                            || ($companyNegativeCondition === true && $assignedByCompanyCondition === true)
                        ) {
                            continue;
                        }
                    }
                }



                $forCertification = (bool) $attr->obtenerDato("certificacion");
                // Si el documento no es de certificación debemos ver
                // si ya está habilitada para esa empresa antes de mostrar
                if ($forCertification == false && !$attr->obtenerDato("descargar")) {
                    if ($empresasNoHabilitadas->contains($empresa)) {
                        continue;
                    }
                }

                $agrupadorField = "0";
                if (isset($attr->reference) && $attr->reference instanceof agrupador && $attr->isAvailableToRelate()) {
                    $agrupadorField = "{$attr->reference->getUID()}";
                }

                switch ($referenciar) {
                    case documento_atributo::REF_TYPE_NONE:
                        $continue = true;
                        foreach ($companiesElement as $companyElement) {
                            if ($attr->companyPassTargetCondition($companyElement) === true) {
                                $continue = false;
                                break;
                            }
                        }

                        if ($continue === true) {
                            continue;
                        }

                        // When origin is company check if is requested by client and check chainlist
                        if (
                            $origin instanceof empresa
                            && false === $empresa->compareTo($origin)
                            && false === $companiesElement->contains($empresa)
                            && false === $empresa->esCorporacion()
                        ) {
                            $originStartIntList = $origin->getStartIntList();
                            $ownerStartIntList = $empresa->getStartIntList();
                            $hierarchy = TABLE_EMPRESA ."_jerarquia";

                            $SQL = "SELECT n1, n2, n3, n4 FROM {$hierarchy}
                            WHERE n1 IN ({$originStartIntList->toComaList()})
                            AND (
                                    ( n2 IN ({$ownerStartIntList->toComaList()}) AND n3 IN ({$listaEmpresas}) AND (n4 = 0 OR n4 IS NULL) )
                                OR  ( n3 IN ({$ownerStartIntList->toComaList()}) AND n4 IN ({$listaEmpresas}))
                            )";
                            $chainList = $this->db->query($SQL, true);

                            // if there is no contract chain...
                            if (count($chainList) == 0) {
                                continue;
                            }
                        }

                        $SQL = "INSERT IGNORE INTO {$tmpTableName} (
                            uid_documento_atributo, uid_elemento_destino, uid_modulo_destino, uid_agrupador, generated_by
                        ) VALUES (
                            {$attr->getUID()}, {$this->getUID()}, {$this->getModuleId()}, $agrupadorField, {$generatedBy}
                        )";

                        if (!$this->db->query($SQL)) {
                            if (CURRENT_ENV == 'dev') {
                                dump($this->db);
                            }
                        }

                        break;

                    case documento_atributo::REF_TYPE_COMPANY:

                        // Solo si es un empleado o una maquina
                        if ($this instanceof childItemEmpresa) {
                            foreach ($empresas as $empresa) {
                                $referenceUid = (int) $empresa->getUID();

                                if ($attr->companyPassTargetCondition($empresa) === false) {
                                    continue;
                                }

                                // if we know which companies are the authors of the assignments
                                if (0 !== count($generators)) {
                                    // then we must check if the loop company is in the "generators" set
                                    if (false === in_array($empresa->getUID(), $generators)) {
                                        // check if has a corp
                                        if ($corp = $empresa->perteneceCorporacion()) {
                                            // then we must check if the loop company corporation is in the "generators" set
                                            if (false === in_array($corp->getUID(), $generators)) {
                                                continue;
                                            }
                                        } else {
                                            continue;
                                        }
                                    }
                                }

                                if (0 !== count($conditionalIntList)) {
                                    $assignedStatuses = [];
                                    foreach ($conditionalIntList as $conditionalUid) {
                                        $conditionalGroup = new agrupador($conditionalUid);

                                        $assignment = $this->getAssignment($conditionalGroup);

                                        if (false === $assignment) {
                                            $assignedStatuses[] = false;
                                            continue;
                                        }

                                        $conditionalVersions = $this->app['assignment_version.repository']->fromAssignment(
                                            $assignment->asDomainEntity()
                                        );

                                        $assignedBy = [];
                                        foreach ($conditionalVersions as $version) {
                                            $assignedBy[] = $version->company->uid();
                                        }

                                        // if no version, all are valids
                                        if (0 === count($assignedBy)) {
                                            $assignedBy = array_map('intval', $companiesElement->toIntList()->getArrayCopy());
                                        }

                                        if (false === in_array($referenceUid, $assignedBy, true)) {
                                            $assignedStatuses[] = false;
                                            continue;
                                        }

                                        $assignedStatuses[] = true;
                                    }

                                    if ($condicionOR) {
                                        $conditionIsSatisfied = in_array(true, $assignedStatuses, true);
                                    } else {
                                        $conditionIsSatisfied = !in_array(false, $assignedStatuses, true);
                                    }

                                    if ($conditionIsSatisfied) {
                                        $proceed = $condicionAsignado;
                                    } else {
                                        $proceed = !$condicionAsignado;
                                    }

                                    if (false === $proceed) {
                                        continue;
                                    }
                                }

                                // Si hay referencia por empresa, es posible que la empresa no tenga asignado
                                // el agrupador en este caso no hay que hacer referencia a dicha empresa
                                if ($attr->getOriginModuleName() == "agrupador") {
                                    $groups   = $empresa->obtenerAgrupadores();
                                    $assigned = $groups->contains($origin);

                                    if ($assigned == false) {
                                        $related = $groups->obtenerAgrupadoresRelacionados($empresa);

                                        if (false === $related->contains($origin)) {
                                            continue;
                                        }
                                    }
                                }

                                if ($attr->getOriginModuleName() == "agrupamiento") {
                                    $assignments = TABLE_AGRUPADOR ."_elemento";
                                    $groups = TABLE_AGRUPADOR;
                                    $versions    = DB_DATA . '.assignment_version';

                                    $SQL = "SELECT count(uid_elemento)
                                    FROM {$assignments}
                                    INNER JOIN {$groups}
                                    USING(uid_agrupador)
                                    LEFT JOIN {$versions}
                                    ON {$assignments}.uid_agrupador_elemento = {$versions}.uid_assignment
                                    WHERE 1
                                    AND uid_modulo = $uidModuloDestino
                                    AND uid_elemento = {$this->getUID()}
                                    AND uid_agrupamiento = {$origin->getUID()}
                                    AND (
                                        {$versions}.uid_company = {$empresa->getUID()}
                                        OR
                                        {$versions}.uid_company IS NULL
                                    )
                                    LIMIT 1";

                                    $agrupadorAsignado = (bool) $this->db->query($SQL, 0, 0);

                                    if (false === $agrupadorAsignado) {
                                        continue;
                                    }
                                }

                                $uid = $attr->obtenerDato("uid_empresa_propietaria");
                                if ($uid) {
                                    $empresaPropietaria = new empresa($uid);
                                    if (!$this->esVisiblePara($empresaPropietaria, $empresa)) {
                                        continue;
                                    }
                                }

                                if ($agrupadorField) {
                                    $group = new agrupador($agrupadorField);
                                    if ($empresa->getAssignment($group) === false) {
                                        continue;
                                    }
                                }

                                // Tiene alguna condicion que debemos verificar ?
                                $companyNegativeCondition = (bool)$attr->obtenerDato("company_negative_condition");
                                if (($conjunto = trim($attr->obtenerDato("uid_agrupador_company_condicion")))
                                && false === $companyNegativeCondition) {
                                    $intList = new ArrayIntList(explode(',', $conjunto));

                                    $source = TABLE_AGRUPADOR ."_elemento INNER JOIN ". TABLE_AGRUPADOR ." USING (uid_agrupador)";
                                    $SQL = "SELECT count(uid_elemento) FROM {$source} WHERE 1
                                    AND uid_modulo = 1
                                    AND uid_elemento IN ({$empresa->getUID()})
                                    AND uid_agrupador IN ({$intList->toComaList()}) LIMIT 1";

                                    $asignado = (bool) $this->db->query($SQL, 0, 0);
                                    // do not request this doc if no group of this kind is assigned
                                    if (!$asignado) {
                                        continue;
                                    }
                                }

                                if ($agrupamientoComaList = trim($attr->obtenerDato("uid_agrupamiento_condicion"))) {
                                    $assignments = TABLE_AGRUPADOR ."_elemento";
                                    $groups = TABLE_AGRUPADOR;
                                    $versions    = DB_DATA . '.assignment_version';

                                    $SQL = "SELECT count(uid_elemento)
                                    FROM {$assignments}
                                    INNER JOIN {$groups}
                                    USING(uid_agrupador)
                                    LEFT JOIN {$versions}
                                    ON {$assignments}.uid_agrupador_elemento = {$versions}.uid_assignment
                                    WHERE 1
                                    AND uid_modulo = $uidModuloDestino
                                    AND uid_elemento = {$this->getUID()}
                                    AND uid_agrupamiento IN ({$agrupamientoComaList})
                                    AND (
                                        {$versions}.uid_company = {$empresa->getUID()}
                                        OR
                                        {$versions}.uid_company IS NULL
                                    )
                                    LIMIT 1";

                                    $agrupadorAsignado = (bool) $this->db->query($SQL, 0, 0);

                                    if (false === $agrupadorAsignado) {
                                        continue;
                                    }
                                }

                                $SQL = "INSERT IGNORE INTO {$tmpTableName} (
                                    uid_documento_atributo, uid_elemento_destino, uid_modulo_destino, uid_empresa_referencia, uid_agrupador, generated_by
                                ) VALUES (
                                    {$attr->getUID()}, {$this->getUID()}, {$this->getModuleId()}, {$empresa->getUID()}, {$agrupadorField}, {$generatedBy}
                                )";

                                if (!$this->db->query($SQL)) {
                                    if (CURRENT_ENV == "dev") {
                                        dump($this->db);
                                    }
                                }
                            }
                        }

                        break;

                    case documento_atributo::REF_TYPE_CONTRACTS:
                        if ($this instanceof empresa && $attr->companyPassTargetCondition($this)) {
                            $companies = $this->obtenerEmpresasInferiores();
                            $startList = $empresa->getStartList();
                            foreach ($companies as $company) {
                                $requestByContract          = false;
                                $isSubContract              = false;
                                $isSecondLevelSubContract   = false;

                                // Si el origen no es de empresa
                                if (!$isFromCompany) {
                                    // Si es un agrupador y este no está asignado..
                                    if ($originIsGroup && !$company->estadoAgrupador($origin)) {
                                        continue;
                                    }
                                }

                                // Check all the owner companies, companies on requester corporation are owner companies too
                                if ($startList->contains($this)) {
                                    foreach ($startList as $ownerCompany) {
                                        if ($company->esContrata($ownerCompany)) {
                                            $requestByContract = true;
                                            break;
                                        }
                                    }
                                }

                                // Only check when it is necessary
                                if ($requestByContract === false) {
                                    foreach ($startList as $ownerCompany) {
                                        if ($company->esSubcontrataDe($this, $ownerCompany)) {
                                            $isSubContract = true;
                                            break;
                                        }
                                    }

                                    // Only check when it is necessary
                                    if ($isSubContract === false) {
                                        foreach ($startList as $ownerCompany) {
                                            if ($company->esSubcontrataDe($this, null, $ownerCompany)) {
                                                $isSecondLevelSubContract = true;
                                                break;
                                            }
                                        }
                                    }
                                }

                                if ($agrupadorField) {
                                    $group = new agrupador($agrupadorField);
                                    if ($company->getAssignment($group) === false) {
                                        continue;
                                    }
                                }

                                if ($requestByContract || $isSubContract || $isSecondLevelSubContract) {
                                    $SQL = "INSERT IGNORE INTO {$tmpTableName} (
                                        uid_documento_atributo,
                                        uid_elemento_destino,
                                        uid_modulo_destino,
                                        uid_empresa_referencia,
                                        uid_agrupador,
                                        generated_by
                                    ) VALUES (
                                        {$attr->getUID()},
                                        {$this->getUID()},
                                        {$this->getModuleId()},
                                        {$company->getUID()},
                                        {$agrupadorField},
                                        {$generatedBy}
                                    )";

                                    if (!$this->db->query($SQL)) {
                                        if (CURRENT_ENV == "dev") {
                                            dump($this->db);
                                        }
                                    }
                                }
                            }
                        }

                        break;

                    case documento_atributo::REF_TYPE_CHAIN:
                        $requesterCompany   = $attr->getCompany();
                        $targets            = $attr->getTargets();

                        // just a security check, it always have to be a company
                        if (false === $requesterCompany instanceof empresa) {
                            continue;
                        }

                        $requesterStartList = $requesterCompany->getStartList();

                        if (array_search(1, $targets) !== false) {
                            foreach ($companiesElement as $companyElement) {
                                if ($requesterStartList->contains($companyElement) && $attr->companyPassTargetCondition($companyElement)) {
                                    $companyConstraint = false;
                                    if ($isFromCompany == true || $this instanceof company) {
                                        $companyConstraint = true;
                                    } else {
                                        if ($companyElement->getAssignment($origin) != false) {
                                            $companyConstraint = true;
                                        } elseif ($companyElement->getRelatedAssignment($origin) != false) {
                                            $companyConstraint = true;
                                        }

                                        if ($agrupadorField && $companyConstraint == true) {
                                            $companyConstraint = false;
                                            $refGroup = new agrupador($agrupadorField);

                                            // check the same relation in the company
                                            if ($companyElement->getRelatedAssignment($origin, $refGroup) != false) {
                                                $companyConstraint = true;
                                            }
                                        }
                                    }

                                    if ($companyConstraint) {
                                        $SQL = "INSERT IGNORE INTO {$tmpTableName} (
                                            uid_documento_atributo,
                                            uid_elemento_destino,
                                            uid_modulo_destino,
                                            uid_empresa_referencia,
                                            uid_agrupador,
                                            generated_by
                                        ) VALUES (
                                            {$attr->getUID()},
                                            {$this->getUID()},
                                            {$this->getModuleId()},
                                            {$companyElement->getUID()},
                                            {$agrupadorField},
                                            {$generatedBy}
                                        )";

                                        if (!$this->db->query($SQL)) {

                                            if (CURRENT_ENV == "dev") {
                                                dump($this->db);
                                            }
                                        }
                                    }
                                }
                            }
                        }


                        $startIntList = $requesterCompany->getStartIntList();

                        if ($isFromCompany) {
                            $hierarchy = TABLE_EMPRESA ."_jerarquia";

                            $SQL = "SELECT n1, n2, n3, n4 FROM {$hierarchy}
                            WHERE n1 IN ({$startIntList->toComaList()})
                            AND (
                                    ( n2 IN ({$listaEmpresas}) AND (n3 = 0 OR n3 IS NULL) AND (n4 = 0 OR n4 IS NULL) )
                                OR  ( n3 IN ({$listaEmpresas}) AND (n4 = 0 OR n4 IS NULL) )
                                OR  ( n4 IN ({$listaEmpresas}) )
                            )";

                        } else {
                            // if the requirement is from a grup all the companies in chain must have it assigned

                            $assigns = TABLE_AGRUPADOR ."_elemento";

                            // check the assigned companies
                            $assigments = "SELECT uid_elemento FROM {$assigns}
                            WHERE uid_modulo = 1
                            AND uid_agrupador = {$origin->getUID()}
                            AND uid_elemento = n%s";

                            // check the assigned by relation companies
                            $relations = "SELECT uid_elemento FROM {$assigns}
                            INNER JOIN {$assigns}_agrupador aea USING (uid_agrupador_elemento)
                            WHERE uid_modulo = 1
                            AND aea.uid_agrupador = {$origin->getUID()}
                            AND uid_elemento = n%s";

                            // test both things at same time
                            $assigned = "{$assigments} UNION {$relations}";

                            // mount the filter queries
                            $assignedN2 = sprintf($assigned, "2", "2");
                            $assignedN3 = sprintf($assigned, "3", "3");
                            $assignedN4 = sprintf($assigned, "4", "4");

                            $hierarchy = TABLE_EMPRESA ."_jerarquia";
                            $SQL = "SELECT n1, n2, n3, n4 FROM {$hierarchy} WHERE 1
                            AND n1 IN ({$startIntList->toComaList()})
                            AND
                            (
                                (
                                    n2 IN ({$listaEmpresas})
                                    AND (n3 = 0 OR n3 IS NULL)
                                    AND (n4 = 0 OR n4 IS NULL)
                                    AND n2 IN ({$assignedN2})
                                )
                                OR (
                                    n3 IN ({$listaEmpresas})
                                    AND (n4 = 0 OR n4 IS NULL)
                                    AND n2 IN ({$assignedN2})
                                    AND n3 IN ({$assignedN3})
                                )
                                OR (
                                    n4 IN ({$listaEmpresas})
                                    AND n2 IN ({$assignedN2})
                                    AND n3 IN ({$assignedN3})
                                    AND n4 IN ({$assignedN4})
                                )
                            )";
                        }

                        $chainList = $this->db->query($SQL, true);

                        // if there is no contract chain...
                        if (count($chainList) == 0) {
                            break;
                        }

                        // loop over all contracts chains
                        foreach ($chainList as $chain) {
                            $chain          = array_filter($chain, 'intval');
                            $numNodeChain   = count($chain);

                            if ($numNodeChain == 0) {
                                break;
                            }

                            $firstCompany   = new empresa($chain['n1']);

                            if ($firstCompany->esCorporacion()) {
                                continue;
                            }

                            $insert = true;
                            $tail   = new empresa(end($chain));

                            if (array_search($numNodeChain, $targets) === false) {
                                continue;
                            }

                            foreach ($chain as $node) {
                                $isRequester    = $startIntList->contains($node);
                                $chainCompany   = new empresa($node);

                                if ($this instanceof childItemEmpresa) {
                                    $isVisible        = $this->esVisiblePara($chainCompany, $tail);
                                    $isOwnCorporation = false;
                                    if ($isRequester && $chainCompany->esCorporacion()) {
                                        $isOwnCorporation = true;
                                    }

                                    if ($isOwnCorporation == false && $isVisible == false) {
                                        $insert = false;
                                    }
                                }

                                // if we have a referenced request, we need to check if the referenced
                                // group is assigned to all companies in the chain except the requester
                                if ($insert == true && $agrupadorField && $isRequester === false) {
                                    $group = new agrupador($agrupadorField);

                                    if ($chainCompany->getAssignment($group) === false) {
                                        $insert = false;
                                    }
                                }
                            }

                            // continue to next chain
                            if ($insert === false) {
                                continue;
                            }

                            $reference = implode(",", $chain);
                            $SQL = "INSERT IGNORE INTO {$tmpTableName} (
                                uid_documento_atributo,
                                uid_elemento_destino,
                                uid_modulo_destino,
                                uid_empresa_referencia,
                                uid_agrupador,
                                generated_by
                            ) VALUES (
                                {$attr->getUID()},
                                {$this->getUID()},
                                {$this->getModuleId()},
                                '{$reference}',
                                {$agrupadorField},
                                {$generatedBy}
                            )";

                            if (!$this->db->query($SQL)) {
                                if (CURRENT_ENV == "dev") {
                                    dump($this->db);
                                }
                            }
                        }

                        break;
                }
            }

            // Vamos a eliminar las solicitudes que ya no deban existir, salvo bloqueadas, para recordarlas
            $fields = [
                "uid_documento_atributo",
                "uid_elemento_destino",
                "uid_modulo_destino",
                "uid_agrupador",
                "uid_empresa_referencia",
            ];

            $fieldList = implode(", ", $fields);

            // Vamos a ver si tenemos que eliminar alguna solicitud
            $SQL = "
                SELECT de.uid_documento_elemento
                FROM (
                    SELECT uid_documento_elemento, $fieldList
                    FROM ". TABLE_DOCUMENTOS_ELEMENTOS ."
                    WHERE uid_modulo_destino = $uidModuloDestino
                    AND uid_elemento_destino = $uidElementoDestino
                ) as de
                LEFT JOIN $tmpTableName tmp USING($fieldList)
                WHERE tmp.uid_documento_elemento IS NULL
            ";
            $deletes = $this->db->query($SQL, "*", 0);
            if ($this->db->lastError()) {
                if (CURRENT_ENV == "dev") {
                    dump($this->db);
                }
            }

            if (is_array($deletes) && count($deletes)) {
                $deleted = implode(",", $deletes);
                $SQL = "DELETE FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." WHERE uid_documento_elemento IN ({$deleted})";
                if (!$this->db->query($SQL)) {
                    if (CURRENT_ENV == "dev") {
                        dump($this->db);
                    }
                }
            }

            // Guardar las nuevas solicitudes
            $sql = "INSERT IGNORE INTO ". TABLE_DOCUMENTOS_ELEMENTOS ." ($fieldList, generated_by)
            SELECT $fieldList, generated_by
            FROM $tmpTableName
            ON DUPLICATE KEY UPDATE documento_elemento.generated_by = {$tmpTableName}.generated_by";
            if (!$this->db->query($sql)) {
                if (CURRENT_ENV == "dev") {
                    dump($this->db);
                }
            }

            // Eliminar tablas temporales
            $this->db->query("DROP TABLE IF EXISTS $tmpTableName");


            $noHabilitadasAfter = $this->obtenerEmpresasClienteNoHabilitadas();
            if (isset($empresasNoHabilitadas) && (count($empresasNoHabilitadas) < count($noHabilitadasAfter))) {
                $diff = $noHabilitadasAfter->discriminar($empresasNoHabilitadas);

                $SQL = "DELETE ". TABLE_DOCUMENTOS_ELEMENTOS ."
                FROM ". TABLE_DOCUMENTOS_ELEMENTOS ."
                INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ."
                USING(uid_documento_atributo, uid_modulo_destino) WHERE 1
                AND uid_elemento_destino = {$this->getUID()}
                AND uid_modulo_destino = {$this->getModuleId()}
                AND certificacion = 0
                AND uid_empresa_propietaria IN ({$diff->toComaList()})";

                if (!$this->db->query($SQL)) {
                    if (CURRENT_ENV == "dev") {
                        dump($this->db);
                    }
                }
            }


            if ($usuario instanceof Iusuario) {
                $cacheStrings = array();
                $cacheStrings[] = "getDocumentsId-{$this}-{$usuario}-{$usuario->configValue("viewall")}-0-----";
                $cacheStrings[] = "getDocumentsId-{$this}-{$usuario}-{$usuario->configValue("viewall")}-0----1-";
                $cacheStrings[] = "getDocumentsId-{$this}-{$usuario}-{$usuario->configValue("viewall")}-0-0-15----";
                foreach ($cacheStrings as $value) {
                    $this->cache->deleteData($value);
                }

                // Delete requesters cache
                $cacheString = 'empresa-getRequestFilter-' . $usuario->getCompany()->getUID() . $usuario->activeProfile();
                $this->app['cache']->deleteItems([$cacheString]);
            }

            $updated = (int) $this->obtenerDato('updated');
            if ($updated === 0) {
                $this->updateNeeded(false);
            }

        } else {
            $SQL = "DELETE FROM ". TABLE_DOCUMENTOS_ELEMENTOS ."
            WHERE uid_elemento_destino = {$this->getUID()}
            AND uid_modulo_destino = {$this->getModuleId()}
            ";
            if (!$this->db->query($SQL)) {
                if (CURRENT_ENV == 'dev') {
                    dump($this->db);
                }
            }
        }

        // dispatch the internal event
        $class = get_class($this);
        $name = $class::getRouteName();
        $eventName = "post.{$name}.update_requests";
        $event = new \Dokify\Application\Event\Requestable\RequestsUpdated($this, $usuario);
        $this->dispatcher->dispatch($eventName, $event);

        return true;
    }

    public function calcularAtributosSolicitados () {
        $module             = $this->getModuleName();
        $companies          = $this->obtenerEmpresasSolicitantes();

        $validCompanies = new ArrayObjectList;
        $requesters     = new ArrayObjectList;

        if ($this instanceof empresa) {
            $referenceCompanies     = new ArrayObjectList;
            $referenceCompanies[]   = $this;
        } else {
            $referenceCompanies     = $this->getCompanies();
        }

        $mandatoriesOrg = $this->getMandatoryOrganizations();

        foreach ($referenceCompanies as $referenceCompany) {
            foreach ($companies as $company) {
                if (count($validCompanies) && $validCompanies->contains($company)) {
                    continue;
                }

                $filters = [];
                $filters[] = 'mandatory';
                $filters['modulo'] = $module;

                if ($this instanceof empresa) {
                    $filters[] = $referenceCompany;
                } else {
                    $contracts = $company->obtenerEmpresasInferiores();

                    if (count($contracts) && $contracts->contains($referenceCompany)) {
                        $filters[] = $referenceCompany;
                    }
                }

                $organizations = $company->obtenerAgrupamientosPropios($filters);
                if ($corp = $company->belongsToCorporation()) {
                    $corpOrganizations = $corp->obtenerAgrupamientosPropios($filters);
                    $organizations = $organizations->merge($corpOrganizations);
                }

                $organizations = $organizations->match($mandatoriesOrg);


                if ($organizations && count($organizations)) {
                    $numgroups  = count($organizations);
                    $nummatches = 0;

                    foreach ($organizations as $organization) {
                        $groups = $this->obtenerAgrupadores(null, false, $organization, false, false ,NULL, ['count' => true]);
                        if ($groups) {
                            $nummatches++;
                        }
                    }

                    if ($numgroups === $nummatches) {
                        $validCompanies[] = $company;
                    }
                } else {
                    $validCompanies[] = $company;
                }
            }
        }

        $allGroupsCoordinated = new ArrayObjectList;
        foreach ($validCompanies as $company) {
            $requesters[]   = $company;

            $organizations  = $company->obtenerAgrupamientosPropios(['modulo' => $module]);
            if (count($organizations) === 0) continue;

            $groups = $this->obtenerAgrupadores(null, false, $organizations);

            if ($this instanceof empresa) {
                $coordinatedGroups = $this->getCoordinatedGroups($company);
                $allGroupsCoordinated = $allGroupsCoordinated->merge($coordinatedGroups);
            }

            if (count($groups) === 0) continue;

            $requesters     = $requesters->merge($groups);
            // add indirect assigns
            $indirectGroups = $this->obtenerSolicitantesIndirectos($company);
            if (count($indirectGroups)) {
                $indirectGroups = new ArrayAgrupadorList($indirectGroups);
                $requesters     = $requesters->merge($indirectGroups);

                $indirectOrganizations  = $indirectGroups->toOrganizationList();

                if (count($indirectOrganizations)) {
                    $requesters             = $requesters->merge($indirectOrganizations);
                }
            }

            $organizations  = $groups->toOrganizationList();


            if (count($organizations) === 0) continue;
            // add to requesters array
            $requesters     = $requesters->merge($organizations);
        }

        $directAttributes   = new ArrayObjectList;
        $indirectAttributes = new ArrayObjectList;

        // IDs de item y modulo
        $uidModuloDestino   = $this->getModuleId();
        $uidElementoDestino = $this->getUID();

        foreach ($allGroupsCoordinated as $coordinatedGroup) {
            $filters = [];
            $filters[] = " uid_modulo_origen = 11 ";
            $filters[] = " uid_elemento_origen = {$coordinatedGroup->getUID()} ";
            $filters[] = " uid_modulo_destino = {$uidModuloDestino} ";
            $filters[] = " activo = 1 ";
            $filters[] = " replica = 0 ";
            $filters[] = " only_coordinator = 1 ";

            $SQL = "SELECT uid_documento_atributo
            FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
            WHERE 1
            AND ". implode(" AND ", $filters);

            $coordinatedAtributes = $this->db->query($SQL, "*", 0, "documento_atributo");
            $directAttributes = $directAttributes->merge($coordinatedAtributes);
        }

        foreach ($requesters as $solicitante) {
            $parts = array();

            // Filtros básicos
            $moduloID = $solicitante->getModuleId();

            $parts[] = " uid_modulo_origen = $moduloID ";
            $parts[] = " uid_elemento_origen = {$solicitante->getUID()} ";
            $parts[] = " uid_modulo_destino = {$uidModuloDestino} ";
            $parts[] = " activo = 1 ";
            $parts[] = " replica = 0 ";

            // Extraer la referencia
            $empresaReferencia = $solicitante;
            if ($solicitante instanceof agrupador || $solicitante instanceof agrupamiento) {
                $empresaReferencia = $solicitante->getCompany();
            }


            if ($empresaReferencia) {
                // Calcular la distancia
                $distanciaSolicitante = $this->obtenerDistancia($empresaReferencia, false);
                if( !is_numeric($distanciaSolicitante) ){ $distanciaSolicitante = 0; }
                $parts[] = " $distanciaSolicitante <= recursividad";

                $reference = (isset($solicitante->referencia)) ? " {$solicitante->referencia->getUID()} as referencia " : " 0 as referencia ";

                $SQL = "SELECT uid_documento_atributo, $reference FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE 1 AND ". implode(" AND ", $parts);

                $atributesRaw = $this->db->query($SQL, true);

                foreach ($atributesRaw as $atributeRaw) {

                    $attr = new documento_atributo($atributeRaw["uid_documento_atributo"]);
                    if (isset($atributeRaw["referencia"]) && is_numeric($atributeRaw["referencia"]) && $atributeRaw["referencia"] > 0) {
                        $attr->reference = new agrupador($atributeRaw["referencia"]);
                        $indirectAttributes[]   = $attr;
                    } else {
                        $directAttributes[]     = $attr;
                    }
                }
            }
        }

        if (count($directAttributes)) {
            $directAttributes = $directAttributes->unique();

            if (count($indirectAttributes)) return $directAttributes = $directAttributes->merge($indirectAttributes);
            else return $directAttributes;

        } elseif (count($indirectAttributes)) return $indirectAttributes;

        return new ArrayObjectList;
    }


    /**
        @param = descargar - si es de descarga o no
        @param = obligatorio - opcionales u obligatorios | si es un array sera el limitador
        @param = $papelera - si alguno de sus atributos esta en la paplera
    **/
    public function getDocuments($descargar=false, $obligatorio=null, $papelera = false, $filtro=false, $certificacion=null){
        $filterKey = ($filtro) ? json_encode($filtro) : '';
        $cacheString = "documentos-{$this}-$descargar-$obligatorio-$papelera-$filterKey";
        if( ($estado = $this->cache->getData($cacheString)) !== null ){
            return ArrayObjectList::factory($estado, $this);
        }

        $arrayIDS = $this->getDocumentsId($descargar, $obligatorio, $papelera, $filtro, false, $certificacion);
        $arrayDocumentos = new ArrayObjectList;
        foreach( $arrayIDS as $uidDocumento ){
            $arrayDocumentos[] = new documento( $uidDocumento, $this );
        }
        $this->cache->addData($cacheString, "$arrayDocumentos");
        return $arrayDocumentos;
    }


    /**
     * Return ArrayObjectList of companies wich are requesting any document to this item. Never return a corporation
     * @param  usuario $user         the user to filter with
     * @param  boolean $self         not in use, just for compatibility
     * @param  boolean $corps        not in use, just for compatibility
     * @param  boolean $requirements not in use, just for compatibility
     *
     * @return ArrayObjectList       the collection of requester companies
     * @SuppressWarnings("unused")
     */
    public function getRequesterCompanies (usuario $user = null, $self = true, $corps = true, $requirements = false)
    {
        $class      = $this->getModuleName();
        $table      = TABLE_DOCUMENTO . "_{$class}_estado";
        $requesters = new ArrayObjectList;

        $userCompany    = $user->getCompany();
        $clients        = $this->getClientCompanies($user)->merge($userCompany);

        // add corps
        foreach ($clients as $client) {
            if ($corp = $client->perteneceCorporacion()) {
                $clients[] = $corp;
            }
        }

        $sql = "SELECT uid_solicituddocumento, uid_empresa_propietaria, uid_elemento_origen, uid_modulo_origen
        FROM {$table} view
        WHERE 1
        AND descargar = 0
        AND uid_{$class} = {$this->getUID()}
        AND uid_empresa_propietaria IN ({$clients->toComaList()})
        ";

        if (isset($user) && $user instanceof usuario) {
            $sql .= $user->obtenerCondicionDocumentosView($class);
        }

        if (empty($rows = $this->db->query($sql, true))) {
            return $requesters;
        }

        foreach ($rows as $row) {
            $company = new empresa($row['uid_empresa_propietaria']);

            if ($company->esCorporacion()) {
                $class  = util::getModuleName($row['uid_modulo_origen']);
                $origin = new $class($row['uid_elemento_origen']);

                if ($origin instanceof empresa) {
                    // dont need to check if it is corporation because we never
                    // use a document from company-to-company in corps
                    $startList  = $company->getStartList();
                    $requesters = $requesters->merge($startList);
                } else {
                    if ($origin instanceof agrupador) {
                        $organization = $origin->obtenerAgrupamientoPrimario();
                    } else {
                        $organization = $origin;
                    }

                    $corpCompanies  = $organization->getCorporationCompanies();

                    foreach ($corpCompanies as $child) {
                        if ($clients->contains($child)) {
                            $requesters[] = $child;
                        }
                    }
                }
            } else {
                $requesters[] = $company;
            }
        }

        $requesters = $requesters->unique();
        return $requesters;
    }

    /** DEVUELVE ARRAY DE CLIENTES QUE PIDEN ALGUN DOCUMENTO A ESTE ELEMENTO **/
    public function getDocumentsEmpresas($descargar=false, $obligatorio=null, $papelera = false, $filtro = false){
        $sql = $this->getDocumentsId($descargar, $obligatorio, $papelera, false, true);
        $search = array( "SELECT uid_documento,", "GROUP BY uid_documento ");
        $replace = array( "SELECT uid_empresa_propietaria,", "GROUP BY uid_empresa_propietaria " );
        $sql = str_replace($search, $replace, $sql);

        $ids = $this->db->query($sql, "*", 0);
        $empresas = array();
        foreach($ids as $uidEmpresa){
            $empresa = new empresa($uidEmpresa, false);
            if( $empresa->exists() ){
                $empresas[] = $empresa;
            } else {
                //die("El empresa $uidEmpresa no existe");
            }
        }

        return $empresas;
    }


    /** ESTADO A MODO DE TEXTO DE UN OBJETO REFERENTE A SU DOCUMENTACION A PARTIR DE UN ID */
    public static function status2String( $uidestado ){
        $lang = Plantilla::singleton();
        switch( $uidestado ){
            case "-1":      return $lang->getString("sin_solicitar_documentos");        break;
            case 0:         return $lang->getString("sin_ningun_documento");            break;
            case 1:         return $lang->getString("con_documentos_pendientes");       break;
            case 2:         return $lang->getString("con_todos_documentos_validos");    break;
            case 3:         return $lang->getString("con_documentos_caducados");        break;
            case 4:         return $lang->getString("con_documentos_anulados");         break;

        }
    }
}
