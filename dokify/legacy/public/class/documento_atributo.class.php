<?php

class documento_atributo extends etiquetable implements Ielemento {
    const PUBLIFIELDS_MODE_CONDITIONALS = 'conditionals';
    const PUBLIFIELDS_MODE_CRITERIA     = 'criteria';

    const REGISTER_ACTION_EDIT = TRUE;

    const TYPE_FILE_UPLOAD = 0;
    const TYPE_ONLINE_SIGN = 1;

    const TEMPLATE_TYPE_GENERAL = "general";
    const TEMPLATE_TYPE_CUSTOM = "custom";
    const TEMPLATE_TYPE_BOTH = "both";


    const REF_TYPE_NONE         = 0;
    const REF_TYPE_COMPANY      = 1;
    const REF_TYPE_CHAIN        = 2;
    const REF_TYPE_CONTRACTS    = 3;

    /**
     * Missing templates variables
     */
    const ECODE_MISSING_LEGAL_REPRESENTATIVE = 1;
    const ECODE_MISSING_ASSIGNMENT_DURATION = 2;

    public function __construct( $param , $extra = true){
        $this->tipo = "documento_atributo";
        $this->tabla = TABLE_DOCUMENTO_ATRIBUTO;
        $this->instance( $param, $extra );

    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Requirement\Requirement
     */
    public function asDomainEntity()
    {
        $info = $this->getInfo();
        $info['alias'] = utf8_decode($info['alias']);
        return $this->app['requirement.repository']->factory($info);
    }

    public static function getRouteName () {
        return 'requirement';
    }

    /**
     * @param $value
     * @param $db
     * @param $sql
     * @param $class
     * @param $attachmentsTable
     * @param $modulo
     *
     * @return int
     */
    private static function doExpandDuration(
        $value,
        &$db,
        $sql,
        $class,
        $attachmentsTable,
        $modulo
    ): int {
        $attachmentsUids = '';
        $comma = '';
        $elements = $db->query($sql, true);
        $countResults = 0;

        foreach ($elements as $registro) {
            $anexo = new $class($registro["uid"]);
            $estado = $registro["estado"];

            if ($value == 0) {
                $duration = "0";

                $modifiedExpirationDate = 0;

                // --- SOLO CAMBIAMOS EL ESTADO DEL DOCUMENTO SI ESTA CADUCADO, AL RESTO CONSERVAMOS SU ESTADO
                switch ($registro["estado"]) {
                    case documento::ESTADO_CADUCADO:
                        $previous = $anexo->getPreviousStatus();

                        $estado = ($previous) ? $previous : documento::ESTADO_ANEXADO;
                        break;
                }
            } else {
                if (is_numeric($value)) {
                    $duration = $value;

                    $modifiedExpirationDate = $registro["fecha_emision"] + ((float) $value) * 24 * 60 * 60;
                } else {
                    list($day, $month) = explode('/', $value);

                    $docDate = new DateTime();
                    $docDate->setTimestamp($registro['fecha_emision']);
                    $docDay = date('d', $registro["fecha_emision"]);
                    $docMonth = date('m', $registro["fecha_emision"]);

                    if ($month == "00") {
                        $expirationDate = $docDate;

                        if ($day <= $docDay) {
                            $expirationDate = $expirationDate->add(date_interval_create_from_date_string('1 month'));
                        }

                        $duration = date('Y-m-' . $day, $expirationDate->getTimestamp());
                    } else {
                        $expirationDate = $docDate->add(date_interval_create_from_date_string('1 year'));
                        $duration = date('Y-' . $month . '-' . $day, $expirationDate->getTimestamp());
                    }

                    $modifiedExpirationDate = strtotime($duration);
                    $duration = date('d/m/Y', $modifiedExpirationDate);
                }

                // --- comprobamos el estado partiendo de la nueva fecha de expiracion
                if (time() > $modifiedExpirationDate && documento::ESTADO_ANULADO !== $estado) {
                    $estado = documento::ESTADO_CADUCADO;
                    $attachmentsUids.= $comma. $anexo->getUID();
                    $comma = ', ';
                } elseif (time() < $modifiedExpirationDate) {
                    switch ($registro["estado"]) {
                        case documento::ESTADO_CADUCADO:
                            $previous = $anexo->getPreviousStatus();

                            $estado = ($previous) ? $previous : documento::ESTADO_ANEXADO;
                            break;
                    }
                }
            }

            if (0 !== $modifiedExpirationDate) {
                $requirementInfo = $anexo->obtenerDocumentoAtributo()->getInfo();
                $gracePeriod = (int) $requirementInfo['grace_period'];
                $modifiedExpirationDate += $gracePeriod * 24 * 60 * 60;
            }

            $sql = "UPDATE {$attachmentsTable}
            SET estado = $estado,
            fecha_expiracion = {$modifiedExpirationDate},
            duration = '{$duration}',
            validation_argument = NULL,
            reverse_status = NULL,
            reverse_date = NULL,
            uid_anexo_renovation = NULL
            WHERE uid_anexo_$modulo = {$anexo->getUID()}
            ";

            if ($db->query($sql)) {
                if ($estado != documento::ESTADO_CADUCADO) {
                    $anexo->writeLogUI(logui::ACTION_STATUS_CHANGE, $estado);
                }

                ++$countResults;
            }
        }

        if (false === empty($attachmentsUids)) {
            $sql = "SELECT GROUP_CONCAT(uid_anexo_{$modulo}) intList, uid_{$modulo} as uid
            FROM {$attachmentsTable}
            WHERE uid_anexo_{$modulo} IN ({$attachmentsUids})
            GROUP BY uid_{$modulo}
            ";

            if ($rows = $db->query($sql, true)) {
                anexo::commentFromAttachGrouped($rows, $modulo);
            }

            unset($rows);
        }

        unset($elements, $anexo, $sql);

        return $countResults;
    }

    public function getReferenceType () {
        return $this->obtenerDato("referenciar_empresa");
    }

    /**
     * Check if the requirement has the attach multiple option active
     * @return boolean
     */
    public function hasAttachMultiple()
    {
        return (bool) $this->obtenerDato("attach_multiple");
    }

    public function hasCompanyReference () {
        return $this->obtenerDato("referenciar_empresa") == self::REF_TYPE_COMPANY;
    }

    public function hasChainReference () {
        return $this->obtenerDato("referenciar_empresa") == self::REF_TYPE_CHAIN;
    }

    public function hasContractsReference () {
        return $this->obtenerDato("referenciar_empresa") == self::REF_TYPE_CONTRACTS;
    }

    public function hasCopyToExample()
    {
        return (bool) $this->obtenerDato("copy_to_example");
    }

    /**
     * Check if the requirement has the only coordinator option active
     * @return boolean
     */
    public function hasOnlyCoordinator()
    {
        return (bool) $this->obtenerDato("only_coordinator");
    }

    public function getCriteria () {
        return trim($this->obtenerDato('criteria'));
    }

    public function isAvailableToRelate () {
        return !(bool) $this->obtenerDato("no_relacionar");
    }

    /** EL NOMBRE DEL ATRIBUT DE DOCUMENTO */
    public function getUserVisibleName($fn=false, $locale = null, $decode = true){
        // Por si tenemos idiomas
        if( ( $locale !== null && $locale != "es" ) || ( $locale === null && ($locale = Plantilla::getCurrentLocale()) != "es" ) ){
            $documentoIdioma = new traductor( $this->getUID(), $this );
            $nombre = $documentoIdioma->getLocaleValue($locale);
        }

        if( !isset($nombre) || !trim($nombre) ){
            $sql = "SELECT alias FROM $this->tabla WHERE uid_$this->tipo = $this->uid";
            if ($decode){
                $nombre = utf8_encode($this->db->query($sql, 0, 0));
            }else{
                $nombre = $this->db->query($sql, 0, 0);
            }

        }

        if( is_callable($fn) ){
            return $fn($nombre);
        }

        return $nombre;
    }

    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
        $info = parent::getInfo(false);

        $data = array();

        $data["nombre"] =  array(
            "innerHTML" => string_truncate($info["alias"], 60),
            "href" => "../agd/ficha.php?m=".get_class($this)."&poid={$this->uid}",
            "className" => "box-it link",
            "title" => $info["alias"]
        );


        return array( $this->getUID() => $data );
    }

    public function getTreeData(Iusuario $usuario, $data = array()){

        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

        switch($context){
            default:
                return false;
            break;
        }
        return false;
    }

    public function getIcon($mode=false){
        switch($mode){
            default:
                return RESOURCES_DOMAIN . "/img/famfam/page_white_acrobat.png";
            break;
        }
    }

    public function getDestinyModuleName(){
        return strtolower(self::getModuleName($this->obtenerDato("uid_modulo_destino")));
    }

    public function getOriginModuleName(){
        return strtolower(self::getModuleName($this->obtenerDato("uid_modulo_origen")));
    }

    /** NOMBRE PARA ASIGNACION **/
    public function getAssignName($usuario, $parent = NULL){
        $name =  parent::getAssignName($usuario, $elemento);
        if( $elemento instanceof buscador ){
            $solicitante = $this->getElement();
            return $name . " - " . $solicitante->getUserVisibleName();
        }
        return $name;
    }

    public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = null, $parent = false, $force = false)
    {
        $cacheKey = implode('-', [$this, __FUNCTION__, $publicMode, $comeFrom, $usuario, $parent, $force]);
        if (($json = $this->cache->getData($cacheKey)) !== null) {
            return json_decode($json, true);
        }

        $info = parent::getInfo($publicMode, $comeFrom, $usuario);
        if (($locale = Plantilla::getCurrentLocale()) != "es") {
            $documentoIdioma = new traductor($this->getUID(), $this);
            $aliasLocale = $documentoIdioma->getLocaleValue($locale);
            if (trim($aliasLocale)) {
                $info["alias"] = $aliasLocale;
            }
        }

        $assignedFormats = $this->obtenerFormatosAsignados();
        $info['formatos'] = '';
        if (true === is_countable($assignedFormats) && 0 < count($assignedFormats)) {
            $formats = [];
            foreach ($assignedFormats as $assignedFormat) {
                $formats[] = $assignedFormat->getName();
            }
            $info['formatos'] = implode(', ', $formats);
        }

        $this->cache->set($cacheKey, json_encode($info));
        return $info;
    }

    public function clearItemCache()
    {
        $cacheKey = implode('-', [$this, 'getInfo', '*']);
        $this->cache->clear($cacheKey);
        parent::clearItemCache();
    }

    /**
     * @param  usuario $user
     */
    public function writeLogUIActionEdit(usuario $user)
    {
        $oldRequirementData = $this->getInfo();
        $this->clearItemCache();
        $newRequirementData = $this->getInfo();
        $diffRequirementData = array_diff_assoc($newRequirementData, $oldRequirementData);

        $logValue = implode(', ', array_map(
            function ($val, $key) {
                return "{$key} = '{$val}'";
            },
            $diffRequirementData,
            array_keys($diffRequirementData)
        ));
        $this->writeLogUI(logui::ACTION_EDIT, $logValue, $user);
    }

    public function getElementName(){
        return $this->getElement()->getUserVisibleName();
    }

    public static function getSearchData(Iusuario $usuario, $papelera = false, $all = false){
        $limitparts = $searchData = array();
        if (!$usuario->accesoModulo(__CLASS__, true)) return false;

        if( $all != true ){
            $limitparts[] = "uid_empresa_propietaria = {$usuario->getCompany()->getUID()} ";
        }

        if( is_bool($papelera) ){
            $limitparts[] = " activo = ". ($papelera ? 0 : 1);
        }


        if( $usuario instanceof usuario && $usuario->isViewFilterByLabel() ){
            $etiquetas = $usuario->obtenerEtiquetas();
            $uids = count($etiquetas) ? $etiquetas->toComaList() : 0;

            if( count($etiquetas) && is_traversable($etiquetas) ){
                $limitparts[] = " uid_documento_atributo IN (
                    SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta WHERE uid_etiqueta IN ($uids)
                )";
            } else {
                $limitparts[] = " uid_documento_atributo NOT IN (
                    SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta WHERE uid_documento_atributo = documento_atributo.uid_documento_atributo
                )";
            }
        }


        $limit = implode(" AND ", $limitparts);

        $data = array(
            "type" => "documento_atributo",
            "fields" => array("alias"),
            "limit" => $limit,
            "accept" => array(
                "tipo" => "documento_atributo",
                "uid" => true,
                "attr" => true,
                "destino" => true,
                "documento" => true,
                "origen" => true,
                "list" => true
            )
        );

        $data['accept']['etiqueta'] = function($data, $filter, $param, $query){
            $value = db::scape(utf8_decode(reset($filter)));

            $SQL =" uid_documento_atributo IN (
                        SELECT uid_documento_atributo FROM agd_docs.documento_atributo_etiqueta WHERE uid_etiqueta IN (
                            SELECT uid_etiqueta FROM agd_data.etiqueta WHERE nombre like '%$value%'
                        )
                     ) ";

            return $SQL;
        };

        $data['accept']['destino'] = function($data, $filter, $param, $query){
            $SQL    = "";
            $value  = db::scape(utf8_decode(reset($filter)));

            if (!is_numeric($value)) {
                $value = util::getModuleId($value);
            }

            if ($value) {
                $SQL = " uid_documento_atributo IN (
                    SELECT uid_documento_atributo FROM agd_docs.documento_atributo WHERE uid_modulo_destino = $value
                ) ";
            }


            return $SQL;
        };

        $searchData[ TABLE_DOCUMENTO_ATRIBUTO ] = $data;

        return $searchData;
    }

    // getAvailableOptions(Iusuario $user = NULL, $publicMode = false, $config = 0, $groups=true, $ref=false ){
    public function getAvailableOptions(Iusuario $user = NULL, $publicMode = false, $config = 0, $groups = true, $ref = false, $extraData = null ){
        return config::obtenerOpciones($this->getUID(), $this->tipo, $user, $publicMode, $config, 1, $groups, $ref);
    }

    /**
     * Get the node positions from a companies contract where the atribute has to request the documents
     * @return array Returns the posistions where the atribute has to request the document
     */
    public function getTargets()
    {
        $positions = [];

        if ($this->obtenerDato('target_n1')) {
            $positions[] = 1;
        }

        if ($this->obtenerDato('target_n2')) {
            $positions[] = 2;
        }

        if ($this->obtenerDato('target_n3')) {
            $positions[] = 3;
        }

        if ($this->obtenerDato('target_n4')) {
            $positions[] = 4;
        }

        return $positions;
    }


    /**
     * Check if the company is ok about atribute target conditions
     * @param  empresa $company The company we want to check
     * @throws Exception If the $item is not a valid object
     * @return boolean Returns true if the item pass the target atribute conditions, false if it doesn't
     */
    public function companyPassTargetCondition($company)
    {
        if ($company instanceof empresa === false) {
            throw new Exception("Error $company is not an empresa class");
        }

        // filter by company region
        if ($regionTarget = $this->getCompanyRegionTarget()) {
            $country = $company->getCountry();

            if (false === $country->matchRegion($regionTarget)) {
                return false;
            }
        }

        // we should filter by company kind
        if (count($kinds = $this->getTargetCompanyKinds())) {
            $companyKind = (int) $company->obtenerDato('kind');

            if (false === in_array($companyKind, $kinds, true)) {
                return false;
            }
        }

        $element = $this->getElement();
        $targets = $this->getTargets();
        $owner = $this->getCompany();
        $ownerList = $owner->getNoCorporationStartList();

        if (count($targets) === empresaContratacion::MAX_COMPANIES) {
            return true;
        }

        $keyTarget1 = array_search(1, $targets);
        if ($keyTarget1 !== false) {
            if ($ownerList->contains($company) === true) {
                return true;
            }

            unset($targets[$keyTarget1]);
        }

        $keyTarget2 = array_search(2, $targets);
        if ($keyTarget2 !== false) {
            foreach ($ownerList as $ownerCompany) {
                if (true === ($element instanceof agrupador)) {
                    if ($company->isContractVerifiedWithAssignmentVersion($ownerCompany, $element)) {
                        return true;
                    }
                } else {
                    if (true === $company->esContrata($ownerCompany)) {
                        return true;
                    }
                }
            }

            unset($targets[$keyTarget2]);
        }

        if (count($targets) === 0) {
            return false;
        }

        foreach ($ownerList as $ownerCompany) {
            if (true === ($element instanceof agrupador)) {
                $companyContract = $ownerCompany->getChainsVerifiedWithAssignmentVersion($company, $targets, [1], $element, false);
            } else {
                $companyContract = $ownerCompany->obtenerCadenasContratacion($company, $targets, [1], false);
            }

            if (count($companyContract) !== 0) {
                return true;
            }
        }

        return false;
    }

    public function isCertification(){
        return (bool)$this->obtenerDato("certificacion");
    }

    public function dateUpdated($element, $referencia=0){
        $m = $element->getModuleName();
        $uid = $element->getUID();
        $tabla = PREFIJO_ANEXOS . $m;

        if( $referencia instanceof agrupador ){
            $referencia = $referencia->getUID();
        }
        if( !is_numeric($referencia) ){
            $referencia = 0;
        }

        $sql = "SELECT fecha_emision_real FROM $tabla
            WHERE uid_documento_atributo = $this->uid
            AND uid_$m = $uid
            AND uid_agrupador = $referencia
        ";

        $firstDate = $this->db->query($sql, 0, 0);
        if( trim($firstDate) ){
            return true;
        }
        return false;
    }

    /** RETORNA TRUE SI EL OBJETO ESTA EN LA PAPELERA Y FALSE SI NO **/
    public function enPapelera( $objeto ){
        $sql = "SELECT papelera FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." WHERE uid_documento_atributo = $this->uid
        AND uid_elemento_destino = ". $objeto->getUID() ." AND uid_modulo_destino = ". $objeto->getModuleId();
        $papelera = (bool) $this->db->query($sql, 0, 0);
        return $papelera;
    }

    protected function modificarSolicitud( $objeto, $papelera ){
        $sql = "UPDATE ". TABLE_DOCUMENTOS_ELEMENTOS ." SET papelera = $papelera WHERE uid_documento_atributo = $this->uid
        AND uid_elemento_destino = ". $objeto->getUID() ." AND uid_modulo_destino = ". $objeto->getModuleId();
        if( $this->referencia ){
            $sql .=" AND uid_agrupador = ". $this->referencia->getUID() ;
        } else {
            $sql .=" AND uid_agrupador = 0";
        }
        return $this->db->query($sql);
    }

    /**
      * NOS RETORNA UN ID DE DOCUMENTO, REFERENTE A ESTE ATRIBUTO
      * Usamos descargar por compatibilidad, pero no es necesario usarlo en este metodo
      */
    public function getDocumentsId($descargar=false){
        $sql = "SELECT uid_documento FROM $this->tabla WHERE uid_documento_atributo = $this->uid";
        return $this->db->query($sql, 0, 0);
    }

    /**
      * Deprecated, see self::getReqType
      */
    public function getDocumentByAttribute(){
        return $this->getReqType();
    }

    /***
       *
       *
       *
       */
    public function getReqType () {
        $uid = $this->obtenerDato('uid_documento');

        return new documento($uid);
    }

    /**
      * NOS RETORNA UN ARRAY CON LOS ID DE AGRUPAMIENTO_AUTO DE DOCUMENTO, REFERENTE A ESTE ATRIBUTO
      */
    public function getAgrupamientoAuto(){
        $uidAgrupamientoAuto = $this->obtenerDato("agrupamiento_auto");
        if( $uidAgrupamientoAuto )
            return new agrupamiento( $uidAgrupamientoAuto );
        else
            return false;
    }

    public function getStatus($objeto, $descargar=false, $toString=false){
        $info = $this->getInfo();

        $descargar = ( $descargar ) ? 1 : 0;
        $moduloDestino = ( $descargar ) ? $info["uid_modulo_destino"] : $objeto->getModuleId();
        // Si es de descarga y además el origen no es empresa, entonces el anexo irá en el anexo_%DESTINO%
        // Si es de descarga y el origen si es una empresa no es necesario preguntar el destino, se anexa siempre en origen
        //
        // Esta comprobación es util para los documentos de descarga con origen empresa y destino de otro tipo (empleado o maquina por ahora)
        // En los casos de origen agrupamiento o agrupador se debe anexar el documento en destino con uid_%DESTINO% = 0
        //if( $descargar && !$this->getElement() instanceof empresa ){
        //  $tipo = elemento::obtenerNombreModulo($info["uid_modulo_destino"]);
        //}

        $tipo =  elemento::obtenerNombreModulo($info["uid_modulo_destino"]);

        if( $descargar ){
            $tipo = "empresa";
            $table = PREFIJO_ANEXOS . $tipo;
        } else {
            $table = PREFIJO_ANEXOS . strtolower( $tipo );
            $tipo = strtolower($objeto->getType());
        }



        if( $descargar ){
            $sql = "SELECT estado FROM $this->tabla LEFT JOIN $table USING(uid_documento_atributo) WHERE
                    uid_$this->tipo = $this->uid AND uid_modulo_destino = '". $moduloDestino ."' AND
                     ( uid_$tipo = 0 OR uid_$tipo = uid_elemento_origen ) ";
        } else {
            $sql = "SELECT if(estado IS NULL, ". documento::ESTADO_PENDIENTE .", estado ) estado FROM ".
                        TABLE_DOCUMENTO ."_{$tipo}_estado WHERE
                             uid_documento_atributo = {$this->getUID()} AND uid_$tipo  = '". $objeto->getUID() ."'";
        }

        $solicitante = $this->getElement();
        if( isset($solicitante->referencia) && $solicitante->referencia ){
            $sql .= " AND uid_agrupador = ". $solicitante->referencia->getUID();
        } else {
            $sql .= " AND uid_agrupador = 0";
        }


        $sql .= " AND descargar = $descargar";
        $sql .= " ORDER BY uid_anexo_$tipo DESC LIMIT 1";
        $estatus = $this->db->query($sql, 0, 0);

        if ($estatus === false){ $estatus = documento::ESTADO_SIN_SOLICITAR; }

        if( $toString ){
            return documento::status2String($estatus);
        } else {
            return $estatus;
        }
    }




    /** DEVUELVE LA FECHA EN LA QUE EL USUARIO EJECUTO LA REVISION */
    public function getRevisionDate(Iusuario $revisor, Ielemento $item){
        //$moduloOrigen = $this->getOriginModuleName();
        $destino = $this->getDestinyModuleName();

        $tabla = PREFIJO_ANEXOS_ATRIBUTOS . $destino;

        $referencia = ( isset($solicitante->referencia) ) ? $solicitante->referencia->getUID() : 0;
        $sql = "
            SELECT DATE_FORMAT(fecha, '%d/%m/%Y') as fecha FROM {$tabla} WHERE 1
            AND uid_documento_atributo = {$this->getUID()}
            AND uid_usuario = {$revisor->getUID()}
            AND uid_agrupador = $referencia
            AND uid_{$destino} = {$item->getUID()}
        ";

        return $this->db->query($sql, 0, 0);
        /*SELECT uid_usuario
                    FROM agd_docs.anexo_atributo_empleado as relacion
                    INNER JOIN (
                        SELECT uid_empleado, uid_documento_atributo, fecha_anexion
                        FROM agd_docs.anexo_empleado a
                        WHERE uid_documento_atributo = 26641
                        AND uid_empleado = 1048
                        AND uid_agrupador = 0
                    ) as anexo
                    USING( uid_empleado, uid_documento_atributo, fecha_anexion )
                    WHERE 1
                    AND uid_agrupador = 0*/


    }



    public function getFileInfo ($objeto, $descargar=false) {
        $info = $this->getInfo();

        $descargar = (bool) $descargar;
        $moduloDestino = ($descargar) ? $info["uid_modulo_destino"] : $objeto->getModuleId();
        $modoModulo = ($descargar) ? "destino" : "destino";
        $tipo = ($descargar) ? "empresa" : strtolower($objeto->getType());


        $table = PREFIJO_ANEXOS . strtolower( $tipo );
        $sql = "SELECT
            uid_anexo_{$tipo} as uid_anexo,
            estado,
            DATE_FORMAT(FROM_UNIXTIME(a.fecha_emision),'%d-%m-%Y') as fecha_emision,
            if( a.fecha_expiracion, DATE_FORMAT(FROM_UNIXTIME(a.fecha_expiracion),'%d-%m-%Y'), 0) as fecha_expiracion,
            DATE_FORMAT(FROM_UNIXTIME(a.fecha_anexion),'%d-%m-%Y') as fecha_anexion,
            if( a.fecha_expiracion, DATEDIFF( FROM_UNIXTIME(a.fecha_expiracion), FROM_UNIXTIME(a.fecha_emision)), null) as restante,
            if( a.fecha_actualizacion > '2012-02-15', DATE_FORMAT(fecha_actualizacion,'%d-%m-%Y'), NULL) as fecha_actualizacion
            FROM $this->tabla LEFT JOIN $table a USING(uid_documento_atributo) WHERE uid_$this->tipo = $this->uid
        ";
        $sql .= " AND uid_modulo_$modoModulo = '". $moduloDestino ."'";
        if( $descargar ){
            $sql .= " AND ( uid_$tipo = 0 OR uid_$tipo = uid_elemento_origen ) ";
        } else {
            $sql .= " AND uid_$tipo  = '". $objeto->getUID() ."'";
        }
        $sql .= " AND descargar = $descargar";

        $solicitante = $this->getElement();
        if( $solicitante->referencia ){
            $sql .= " AND a.uid_agrupador = ". $solicitante->referencia->getUID();
        } else {
            $sql .= " AND a.uid_agrupador = 0";
        }

        $sql .= " ORDER BY uid_anexo_$tipo DESC LIMIT 1";

        $data = $this->db->query($sql, 0, "*");
        return $data;
    }

    /**
      * Comprobar si el documento esta cargado..
      * Solo tiene lógica en documentos de descarga
      */
    public function isLoaded(){
        $filtro = $this->getElement();
        $filtro->atributoDocumento = $this->getInfo();
        $documento = new documento( $this->getDocumentsId(), $filtro );
        if( $documento->downloadFile( $filtro, true, true ) !== false ){
            return true;
        } else {
            return false;
        }
    }

    /** NOS RETORNA EL OBJETO DOCUMENTO ATRIBUTO QUE HACE DE EJEMPLO **/
    public function obtenerDocumentoEjemplo(){
        $uid = $this->obtenerDato("uid_documento_atributo_ejemplo");
        if( $uid ) return new documento_atributo($uid);
        return false;
    }

    /** NOS RETORNA EL OBJETO DOCUMENTO ATRIBUTO QUE HACE DE EJEMPLO **/
    public function obtenerDocumentoViaEjemplo(solicitable $item, $n=1){
        $SQL = "SELECT uid_documento FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_documento_atributo_ejemplo = {$this->getUID()} LIMIT 0, $n";
        $array = $this->db->query($SQL, "*", 0);

        $coleccion = new ArrayObjectList;
        foreach($array as $uid){
            $coleccion[] = new documento($uid, $item);
        }

        return $coleccion;
    }


    /**
      * Descargará el documento descargable si lo hay
      * asociando a este atributo
      */
    public function downloadFile($return = false, Ielemento $elementoActual = null, Iusuario $usuario = null, solicituddocumento $solicitud = null, $extraData = null)
    {
        $elemento = $this->getElement();

        $dataInfo = $this->getInfo();
        $tpl = Plantilla::singleton();


        $modulo = "empresa";
        $tableNameFilter = new ArrayObject(explode(".", PREFIJO_ANEXOS));
        $tableName = end($tableNameFilter);

        // Ordenamos descendente para obetener el ultimo en caso de que algun fichero no se haya movido a la tabla de historico
        $sql = "
            SELECT uid_". $tableName ."$modulo as uid, archivo, nombre_original, hash FROM ". PREFIJO_ANEXOS . $modulo . "
            WHERE uid_documento_atributo = ". $this->getUID() ." AND ( uid_$modulo = 0 OR uid_$modulo = ". $elemento->getUID() ." )
            ORDER BY  uid_". $tableName ."$modulo DESC
            LIMIT 1
        ";
        $uploaded = $this->db->query($sql, 0, "*");
        $archivo = $uploaded["archivo"];
        $fileOriginalName = $uploaded["nombre_original"];
        $hash =  $uploaded["hash"];
        $aux = explode(".", $archivo);



        $needParse = end($aux) == 'html' && ($elementoActual instanceof solicitable ||  $elementoActual instanceof categorizable);
        if ($return && !$needParse) {
            $archivo =  DIR_FILES . $archivo;

            return array(   "path" => $archivo,
                            "alias" => $this->getUserVisibleName(),
                            "ext" => end($aux),
                            "hash" => $hash,
                            "nombrefichero" => $fileOriginalName
            );
        } else {
            $archivo =  DIR_FILES . $archivo;


            if ($needParse) {
                $modulo = $elementoActual->getModuleName();
                $html = archivo::leer($archivo);

                if (!$html) {
                    throw new Exception(documento::ERROR_JAVASCRIPT);
                }

                $sustituciones = [];
                $avoidReplacements = isset($extraData['context']) && 'config' === $extraData['context'];

                if (false === $avoidReplacements) {
                    if ($elementoActual instanceof empresa) {
                        $empresa = $elementoActual;
                        if ($municipio = $empresa->obtenerMunicipio()) {
                            $municipioName = $municipio->getUserVisibleName();
                            $sustituciones['{%municipio%}'] = $municipioName;
                            $sustituciones['{municipio}'] = $municipioName;
                        }

                    } else {
                        if (true === isset($extraData["calledFromValidation"])) {
                            $empresa = $elementoActual->getCompany();
                        } else {
                            $empresa = $elementoActual->getCompany($usuario);
                        }

                        if ($elementoActual instanceof empleado) {
                            $empleadoDNI = $elementoActual->obtenerDato('dni');
                            $sustituciones['{%empleado-nif%}'] = $empleadoDNI;
                            $sustituciones['{empleado-nif}'] = $empleadoDNI;

                            $epis = $elementoActual->obtenerEpis();
                            if (count($epis)) {
                                $HTMLEpis = '<ul>';
                                foreach ($epis as $epi) {
                                    $HTMLEpis .= $epi->getHTMLName();
                                }
                                $HTMLEpis .= '</ul>';
                            } else {
                                $HTMLEpis = $tpl->getString("sin_epis");
                            }

                            $sustituciones['{%epis%}'] = $HTMLEpis;
                            $sustituciones['{epis}'] = $HTMLEpis;

                            $episSolicitadas = $elementoActual->obtenerTiposEpiSolicitados();
                            $HTMLEpisSolicitadas = ( count($episSolicitadas) ) ? '<ul><li>'. implode($episSolicitadas->getNames(), '</li><li>').'</li></ul>' : $tpl->getString("sin_epis_solicitadas");
                            $sustituciones['{%epis-solicitadas%}'] = $HTMLEpisSolicitadas;
                            $sustituciones['{epis-solicitadas}'] = $HTMLEpisSolicitadas;
                            $jobDescription = $elementoActual->obtenerDato('descripcion_puesto');
                            $sustituciones['{%descripcion-puesto%}'] = $jobDescription;
                            $sustituciones['{descripcion-puesto}'] = $jobDescription;
                        }


                    }

                    /* Vamos a soportar las variables dinámicas con y sin % así que el código puede parecer algo redundante, pero va a ser solo temporal. */

                    $sustituciones['{%representante-legal%}'] = $empresa->obtenerDato("representante_legal");
                    $sustituciones['{%documento-obligatoriedad%}'] = ( $this->obtenerDato("obligatorio") ) ? $tpl->getString(citamedica::CARACTER_OBLIGATORIO) : $tpl->getString(citamedica::CARACTER_OPCIONAL);
                    $sustituciones['{%documento-nombre%}'] = $this->getUserVisibleName();
                    $sustituciones['{%elemento-tipo%}'] = $elementoActual->getType();
                    $sustituciones['{%empresa-nombre%}'] = $empresa->getUserVisibleName(); /*perfilActivo?*/
                    $sustituciones['{%empresa-cif%}'] = $empresa->obtenerDato('cif');
                    $empresasSuperiores = $empresa->obtenerEmpresaContexto()->obtenerEmpresasSuperiores();
                    $empresaSuperior = reset($empresasSuperiores);
                    $sustituciones['{%empresa-superior-cif%}'] = $empresaSuperior?$empresaSuperior->obtenerDato('cif'):'';
                    $sustituciones['{%empresa-superior-nombre%}'] = $empresaSuperior?$empresaSuperior->getUserVisibleName():'';
                    $sustituciones['{%elemento-nombre%}'] = $elementoActual->getUserVisibleName();


                    $sustituciones['{representante-legal}'] = $empresa->obtenerDato("representante_legal");
                    $sustituciones['{documento-obligatoriedad}'] = ( $this->obtenerDato("obligatorio") ) ? $tpl->getString(citamedica::CARACTER_OBLIGATORIO) : $tpl->getString(citamedica::CARACTER_OPCIONAL);
                    $sustituciones['{documento-nombre}'] = $this->getUserVisibleName();
                    $sustituciones['{elemento-tipo}'] = $elementoActual->getType();
                    $sustituciones['{empresa-nombre}'] = $empresa->getUserVisibleName(); /*perfilActivo?*/
                    $sustituciones['{empresa-cif}'] = $empresa->obtenerDato('cif');
                    $sustituciones['{empresa-superior-cif}'] = $empresaSuperior?$empresaSuperior->obtenerDato('cif'):'';
                    $sustituciones['{empresa-superior-nombre}'] = $empresaSuperior?$empresaSuperior->getUserVisibleName():'';
                    $sustituciones['{elemento-nombre}'] = $elementoActual->getUserVisibleName();



                    if ($elemento instanceof agrupador) {
                        $startDate      = $elementoActual->getStartDate($elemento);
                        $expirationDate = $elementoActual->getAssignExpirationDate($elemento);

                        $sustituciones['{fecha-inicio}'] = $startDate;
                        $sustituciones['{fecha-fin}']    = $expirationDate;

                        $sustituciones['{%fecha-inicio%}'] = $startDate;
                        $sustituciones['{%fecha-fin%}']    = $expirationDate;
                    }


                    // Verificar ciertas variables cuando la solicitud exista
                    if ($solicitud) {
                        $exceptionCode  = 0;
                        $atributo       = $solicitud->obtenerDocumentoAtributo();

                        $reqElementName                         = $atributo->getElementName();
                        $sustituciones['{elemento-origen}']     = $reqElementName;
                        $sustituciones['{%elemento-origen%}']   = $reqElementName;

                        if ($referenceGorup = $solicitud->obtenerAgrupadorReferencia()) {
                            $groupName                      = $referenceGorup->getUserVisibleName();
                            $sustituciones['{agrupador-relacion}']    = $groupName;
                            $sustituciones['{%agrupador-relacion%}']  = $groupName;
                        }

                        if (isset($extraData[Ilistable::DATA_CONTEXT])) {
                            switch ($extraData[Ilistable::DATA_CONTEXT]) {
                                case Ilistable::DATA_CONTEXT_FIRM:
                                    $returnUrl = "/agd/firmar.php?m={$modulo}&poid={$atributo->getDocumentsId()}&o={$elementoActual->getUID()}";
                                    break;

                                case Ilistable::DATA_CONTEXT_DESCARGABLES:
                                    $returnUrl = "/agd/done.php";
                                    break;

                                case Ilistable::DATA_CONTEXT_ATTACH:
                                    $returnUrl = "/agd/anexar.php?m={$modulo}&poid={$atributo->getDocumentsId()}&o={$elementoActual->getUID()}";
                                    break;

                                case Ilistable::DATA_CONTEXT_INFO:
                                    $returnUrl = "/agd/informaciondocumento.php?m={$modulo}&poid={$atributo->getDocumentsId()}&o={$elementoActual->getUID()}";
                                    break;
                            }

                            if (isset($returnUrl)) {
                                // add the selected request
                                if (isset($extraData["req"])) {
                                    $returnUrl .= get_concat_char($returnUrl) . "req={$extraData["req"]}";
                                }

                                // auto-open the frame again if not ajax
                                if (!isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
                                    $returnUrl .= get_concat_char($returnUrl) . "frameopen=" . urlencode($_SERVER["REQUEST_URI"]);
                                }
                            }
                        }


                        $representNeeded = !trim($sustituciones['{representante-legal}']) && strpos($html, '{representante-legal}') !== false;

                        $representNeeded = $representNeeded || !trim($sustituciones['{%representante-legal%}']) && strpos($html, '{%representante-legal%}') !== false;

                        // los documentos con esta variable la necsitan de forma obligatoria
                        if ($representNeeded) {
                            $URL = "empresa/modificar.php?poid=". $empresa->getUID() . "&edit=representante_legal&comefrom={$solicitud->getUID()}";

                            $exceptionCode = self::ECODE_MISSING_LEGAL_REPRESENTATIVE;
                        }


                        // Si el solicitante es un agrupador y tenemos estos datos, deben estar cumplimentados si o si
                        if ($elemento instanceof agrupador) {
                            $agrupamiento = $elemento->obtenerAgrupamientoPrimario();

                            $startDateNeeded = !trim($sustituciones['{fecha-inicio}']) && strpos($html, '{fecha-inicio}') !== false;
                            $startDateNeeded = $startDateNeeded || !trim($sustituciones['{%fecha-inicio%}']) && strpos($html, '{%fecha-inicio%}') !== false;

                            $endDateNeeded   = !trim($sustituciones['{fecha-fin}']) && strpos($html, '{fecha-fin}') !== false;
                            $endDateNeeded   = $endDateNeeded || !trim($sustituciones['{%fecha-fin%}']) && strpos($html, '{%fecha-fin%}') !== false;


                            if ($startDateNeeded || $endDateNeeded) {
                                $URL = "/agd/asignacion.php?m={$modulo}&poid={$elementoActual->getUID()}&oid={$elemento->getUID()}&o={$agrupamiento->getUID()}&tab=duracion";

                                $exceptionCode = self::ECODE_MISSING_ASSIGNMENT_DURATION;
                            }
                        }

                        // Si tenemos que redirigir a alguna URl..
                        if (isset($URL)) {

                            // Si tenemos una URL de retorno, añadimos la request seleccionada y la ponemos en la URL
                            if (isset($returnUrl)) {
                                $URL .= "&return=". urlencode($returnUrl);
                            }

                            if ($return) {
                                throw new Exception($URL, $exceptionCode);
                            }

                            throw new Exception('<script>top.agd.func.open("'. $URL .'");</script>', $exceptionCode);
                        }
                    }

                    // Fernando, te dejo una pequeña funcion que te va a ayudar plantillaemail::getMatches($html, $var)
                    // $matches = plantillaemail::getMatches($html, "asignados")
                    // $matches = array(
                    //      array("var" => "{%asignados|826,824,823%}", "params" => "826,824,823" )
                    // )
                    // Ahora el modificador es | en vez de "," que nos lo hace un poco mas fácil y asi distinguimos nombres de variable con caracter "modificador"

                    // TODO fgomez: sacaré esta funcionalidad de 'sustituciones con etiquetas dinámicas' a una funcion
                    // manipulando cadenas {%asignados-826,824,823%}


                    // Varios alias ...
                    $matchesAsignados = plantillaemail::getMatches($html, 'agrupador');
                    if ($matchesAsignados && count($matchesAsignados)) {
                        foreach ($matchesAsignados as $matchAsignado) {
                            $numeric = array_filter($matchAsignado['params'], 'is_numeric');
                            $arrayNumericDiff = array_diff($matchAsignado['params'], $numeric);
                            // Paramétro de modo de "dibujar" la lista
                            $string = reset($arrayNumericDiff);

                            $uids = new ArrayIntList($numeric);
                            $agrupadoresSeleccionados = $uids->toObjectList("agrupador");
                            $agrupadoresAsignados = $elementoActual->obtenerAgrupadores();
                            $agrupadores = $agrupadoresSeleccionados->match($agrupadoresAsignados);

                            if ($agrupadores) {
                                switch($string){
                                    default:
                                        $replace = $agrupadores->toUL(true);
                                        break;
                                    case 'comalist':
                                        $replace = $agrupadores->getUserVisibleName();
                                        break;
                                }
                            } else {
                                $replace = '';
                            }

                            $sustituciones[$matchAsignado['var']] = $replace;
                        }
                    }


                    // Variable fecha de documentos
                    if ($elementoActual instanceof solicitable) {
                        $matchDocumentos = plantillaemail::getMatches($html, 'fecha-documento');
                        if ($matchDocumentos && count($matchDocumentos)) {
                            foreach ($matchDocumentos as $match) {
                                $uid = reset($match['params']);
                                $documento = new documento($uid);
                                $anexos = $documento->obtenerAnexos($elementoActual, $usuario);
                                $fechas = $anexos->foreachCall('getRealTimestamp', array($usuario->getTimezoneOffset()))->getArrayCopy();
                                sort($fechas, SORT_NUMERIC);
                                $sustituciones[$match['var']] = count($fechas) ? date('d-m-Y', end($fechas)) : 'N/A';
                            }
                        }
                    }


                    $matchesValidez = plantillaemail::getMatches($html, 'elemento-validez');
                    if ($matchesValidez && count($matchesValidez)) {
                        foreach ($matchesValidez as $matchValidez) {
                            $uids = new ArrayIntList($matchValidez['params']);
                            $agrupadores = $uids->toObjectList("agrupador");

                            $validStatus = array();
                            $validez = true;
                            foreach ($agrupadores as $agrupador) {
                                $statusData = $elementoActual->obtenerEstadoEnAgrupador($usuario, $agrupador);
                                if (!$statusData || $statusData->estado != documento::ESTADO_VALIDADO) {
                                    $validez = false;
                                    break;
                                }
                            }

                            $sustituciones[$matchValidez['var']] = 'Validez en ' . $agrupadores->getUserVisibleName() .': ' .($validez?'SI':'NO');
                        }
                    }


                    $matchesValidez = plantillaemail::getMatches($html, 'elemento-validez-cruz');
                    if ($matchesValidez && count($matchesValidez)) {
                        foreach ($matchesValidez as $matchValidez) {
                            $uids = new ArrayIntList($matchValidez['params']);
                            $agrupadores = $uids->toObjectList("agrupador");

                            $validStatus = array();
                            $validez = true;
                            foreach ($agrupadores as $agrupador) {
                                $statusData = $elementoActual->obtenerEstadoEnAgrupador($usuario, $agrupador);
                                if (!$statusData || $statusData->estado != documento::ESTADO_VALIDADO) {
                                    $validez = false;
                                    break;
                                }
                            }

                            $sustituciones[$matchValidez['var']] = $validez ? 'X' : '';
                        }
                    }

                    $matchesEvaluacionRiesgos = plantillaemail::getMatches($html, 'evaluacion-riesgos');
                    if ($matchesEvaluacionRiesgos && count($matchesEvaluacionRiesgos)) {
                        foreach ($matchesEvaluacionRiesgos as $match) {
                            //buscamos los agrupadores
                            $riesgos = array();
                            foreach ($elementoActual->obtenerAgrupamientos($usuario) as $i => $agrupamiento) {
                                $agrupadores = $agrupamiento->obtenerAgrupadoresAsignados($elementoActual);
                                foreach ($agrupadores as $agrupador) {
                                    $filepath = DIR_RIESGOS . "empresa_". $this->getCompany()->getUID() . "/agrupador_". $agrupador->getUID();
                                    if (archivo::is_readable($filepath) && in_array($agrupador->getUID(), $match["params"])) {
                                        $riesgos[] = archivo::leer($filepath);
                                    }

                                }
                            }

                            if (count($riesgos)) {
                                $sustituciones[$match['var']] = implode("<br />", $riesgos);
                            }
                        }
                    }

                    $downloadDateDocument =  util::getDateFormat(time());
                    $sustituciones['{%fecha-descarga%}'] = $downloadDateDocument;
                    $sustituciones['{fecha-descarga}'] = $downloadDateDocument;


                    if ($return) {
                        // Preguntamos si se ha definido la posicion de la firma
                        $hasSignature = plantillaemail::getMatches($html, 'firma-usuario');

                        // Generamos la firma para esta solicitud
                        $userSignature = $usuario->getDocumentSignature($solicitud);

                        // Si esta definida, la incrustamos ahí
                        if ($hasSignature) {
                            // Esta sustitucion solo la necesitamos al firmar documentos
                            // y es en el único sitio donde se usa el $return en este contexto,
                            // por lo que para evitar poner mas parámetros pongo aqui la sustitución
                            $sustituciones['{firma-usuario}'] = $usuario->getDocumentSignature($solicitud);
                        } else {
                            // Si no está definida (y no pedimos explícitamente que se quite) al final del documento
                            if (!isset($extraData['hide_signature'])) {
                                $html .= '<br />'. $userSignature;
                            }
                        }
                    }


                    $html = plantillaemail::reemplazar($html, $sustituciones);


                    $tpl = new Plantilla();
                    $referencia = $this->obtenerDato("referenciar_empresa");
                    if (!$referencia || !($solicitud instanceof solicituddocumento)) {
                        $tpl->assign("clientes", array());
                    } else {
                        $empresaReferencia = $solicitud->obtenerIdEmpresaReferencia();

                        // si los documentos
                        if ($empresaReferencia) {
                            if (is_numeric($empresaReferencia)) {
                                $empresas = array( new empresa($empresaReferencia) );
                            } else {
                                // La lista ha de ir de 'abajo a arriba' por eso se hace el reverse
                                $list = explode(",", $empresaReferencia);
                                $intList = new ArrayIntList(array_reverse($list));
                                $empresas = $intList->toObjectList("empresa");
                            }

                            $empresasCliente = array();
                            foreach ($empresas as $empresaCliente) {
                                if (!$empresa->compareTo($empresaCliente) && !$empresaCliente->esCorporacion()) {
                                    $empresasCliente[] = $empresaCliente->getInfo();
                                }
                            }
                            $tpl->assign("clientes", $empresasCliente);
                        } else {
                            $tpl->assign("clientes", array());
                        }

                    }
                }

                $html = $tpl->parseHTML($html);

                $orientation = $this->obtenerDato("orientation");
                $orientation = $orientation ? $orientation : 'P';

                $html2pdf = new HTML2PDF($orientation, 'A4', 'es', array(10, 10, 10, 10));
                $html2pdf->WriteHTML($html);

                if ($return) {
                    $data = $html2pdf->Output(archivo::cleanFilenameString($this->getUserVisibleName()).'.pdf', 'S');

                    $tmpFile = 'parsed-'.uniqid().'-'.time().'.pdf';

                    if (archivo::tmp($tmpFile, $data)) {
                        return array(
                            "path" => "/tmp/{$tmpFile}",
                            "alias" => $this->getUserVisibleName(),
                            "ext" => 'pdf',
                            "hash" => $hash,
                            "nombrefichero" => $fileOriginalName
                        );
                    }

                } else {
                    $html2pdf->Output(archivo::cleanFilenameString($this->getUserVisibleName()).'.pdf', 'D');
                }
            } else {
                if (!archivo::descargar($archivo, $this->getUserVisibleName() . "." . end($aux))) {
                    throw new Exception(documento::ERROR_JAVASCRIPT);
                }
            }

        }
    }


    public function getSelectName($fn=false){
        $name = $this->getUserVisibleName();
        $item = $this->getElement();
        $descargar = (bool) $this->obtenerDato("descargar");

        $str = $descargar ? "(D)" : "(U)";
        return $name . " - ". $item->getUserVisibleName() . " " . $str;
    }


    /***
       * get the element which own this attr
       *
       *
       */
    public function getElement() {
        $module = $this->getOriginModuleName();

        $cacheString = __CLASS__."-".__FUNCTION__."-{$this->getUID()}";
        if (($data = $this->cache->getData($cacheString)) !== null) return $module::factory($data);

        $uid = $this->obtenerDato('uid_elemento_origen');

        $elemento = new $module($uid);

        if (isset($this->referencia)) {
            $elemento->referencia = $this->referencia;
        }

        if (isset($this->empresa)) {
            $elemento->empresa = $this->empresa;
        }

        if (is_object($elemento)) {
            $result = $elemento;
        } else {
            $result = false;
        }


        $this->cache->set($cacheString, "{$result}", 60);
        return $result;
    }

    public function getCompany(){
        $info = $this->getInfo();

        $empresa = new empresa($info["uid_empresa_propietaria"]);
        if( $empresa->getUID() && $empresa->exists() ){
            return $empresa;
        } else {
            return false;
        }
    }

    public function getType(){
        return "documento";
    }

    public function getAllFileFormats()
    {
        $formats = $this->obtenerFormatosDisponibles(null);

        if (is_null($formats)) {
            return null;
        }

        $formats = new ArrayObjectList($formats);

        return $formats->toArray();
    }

    public function obtenerFormatosDisponibles ($uidDoccumentoAtributo) {
        $sql = "SELECT uid_documento_atributo_formato_permitido FROM ".TABLE_FORMATO." ";

        $info = $this->db->query( $sql,"*",0 );

        if( !is_array($info) ) { return false; }

        if( count($info) ) {

            $formatos = array();
            foreach($info as $formato) {
                $formatos[] = new formato($formato);
            }
            return $formatos;
        }
    }


    public function obtenerFormatosAsignados() {
        $sql = "
            SELECT uid_documento_atributo_formato_permitido
            FROM ".TABLE_FORMATO."
            INNER JOIN ".TABLE_DOCUMENTO_ATRIBUTO."_formato
            USING(uid_documento_atributo_formato_permitido)
            WHERE uid_documento_atributo={$this->getUID()}";

        $formatos = $this->db->query($sql, "*", 0, "formato");
        if( !is_array($formatos) || !count($formatos) ){ return null; }

        return $formatos;
    }

    /**
     * Get the file allowed formats for this document
     * @return Array|null The allowed formats information.
     *                    If there isn't information about allowed formats return null (this means that all formats are allowed)
     */
    public function getAllowedFormats()
    {
        $allowedFormats = $this->obtenerFormatosAsignados();

        if (is_null($allowedFormats)) {
            return null;
        }

        $allowedFormatsList = new ArrayObjectList($allowedFormats);

        return $allowedFormatsList->toArray();
    }

    /**
     * Validate a given format with the formats accepted
     * @param  string  $format The mime type
     * @return boolean         Return true if the format is acepted
     */
    public function isValidMimeType($format)
    {
        $allowedFormats = $this->getAllowedFormats();

        //If there isn't any information about allowed formats, the system assume that all formats are allowed
        if (is_null($allowedFormats)) {
            return true;
        }

        foreach ($allowedFormats as $allowedFormat) {
            if ($format == $allowedFormat['mime_type']) {
                return true;
            }
        }

        return false;
    }

    public function actualizarFormato($uids = [])
    {
        $tabla = TABLE_DOCUMENTO_ATRIBUTO ."_formato";
        $currentUIDElemento = $this->getUID();

        $sql = "DELETE FROM {$tabla}
        WHERE uid_documento_atributo = {$currentUIDElemento}";

        $deleteStatus = $this->db->query($sql);
        if (false == $deleteStatus) {
            return $this->db->lastErrorString();
        }

        if (0 === count($uids)) {
            return true;
        }

        $idFormatos = array_map("db::scape", $uids);
        $inserts = [];

        foreach ($idFormatos as $idFormato) {
            $inserts[] = "(".$currentUIDElemento.",".$idFormato.")";
        }

        $insertLines = implode(",", $inserts);
        $sql = "INSERT INTO {$tabla} (uid_documento_atributo, uid_documento_atributo_formato_permitido)
        VALUES {$insertLines}";

        $insertStatus = $this->db->query($sql);
        if (false == $insertStatus) {
            return $this->db->lastErrorString();
        }

        return true;
    }

    public function caducidadManual(){
        return $this->obtenerDato("caducidad_manual");
    }

    public function obtenerDuraciones($list = false, $refDate = false){
        $duracion = trim($this->obtenerDato("duracion"));

        if (is_numeric($duracion) || $list) return $duracion;

        $durations = explode(",", $duracion);
        foreach ($durations as $i => $duration){
            if (!is_numeric($duration)){

                // definicion de fechas
                if (strlen($duration) == 5 && strpos($duration, '/')) {
                    $today = new DateTime("now");
                    list ($day, $month) = explode('/', $duration);


                    $expirationDate = false;

                    if ($month == "00") {
                        if ($refDate) {
                            $today = new DateTime(date('Y-m-d', $refDate));
                            $expirationDate = new DateTime(date('Y-m-', $refDate) . $day);
                        } else {
                            $expirationDate = new DateTime(date('Y-m-') . $day);
                        }

                        $diffDays = $today->diff($expirationDate)->format('%R%a');

                        if ($diffDays < 1) {
                            $expirationDate = $expirationDate->add(date_interval_create_from_date_string('1 month'));
                        }
                    } else {

                        if ($refDate) {
                            $today = new DateTime(date('Y-m-d', $refDate));
                            $expirationDate = new DateTime(date('Y-', $refDate) . $month . '-' . $day);
                        } else {
                            $expirationDate = new DateTime(date('Y-') . $month . '-' . $day);
                        }

                        $diffDays = $today->diff($expirationDate)->format('%R%a');

                        if ($diffDays < 1) {
                            $expirationDate = $expirationDate->add(date_interval_create_from_date_string('1 year'));
                        }
                    }

                    if ($expirationDate) {
                        $durations[$i] = $expirationDate->format("d/m/Y");
                    } else {
                        unset($durations[$i]);
                    }
                }

            // duración en días
            } else {
                $durations[$i] = trim($duration);
            }
        }

        return $durations;
    }


    /** RETORNA LOS ELEMENTOS QUE "TIPICAMENTE" MOSTRAMOS DE ESTE ELEMENTO PARA VER EN MODO INLINE */
    public function getInlineArray(Iusuario $usuario = NULL, $config = false, $data = NULL){
        $tpl = Plantilla::singleton();
        $inlineArray = array();
        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;
        $modulo = isset($data[Ilistable::DATA_MODULO]) ? $data[Ilistable::DATA_MODULO] : false;
        switch($context){
            default:

                $info = $this->getInfo();
                $download = (bool) $info["descargar"];
                $modo = ($download) ? $tpl("descargar") : $tpl("subir") . " ·";
                $obligatorio = ($info["obligatorio"]) ? $tpl("obligatorio") : $tpl("opcional");
                $duracion = ($info["duracion"]) ? $info["duracion"] . " " . $tpl("dias") : $tpl("no_caduca");

                if ($info["uid_modulo_origen"] == 0) {
                    error_log( "Modulo origen 0 para documento_atributo {$this->getUID()}");
                    return false;
                }

                $moduloOrigen = $this->getOriginModuleName();
                $moduloDestino = $this->getDestinyModuleName();
                $elementoOrigen = $this->getElement();

                $attrs = array(
                    "img" => RESOURCES_DOMAIN . "/img/famfam/application_view_tile.png",
                    array("nombre" => $modo, "tagName" => "span")
                );

                if (!$download) {
                    $attrs[] = array("nombre" => $obligatorio. " ·", "tagName" => "span");
                    $attrs[] = array("nombre" => $duracion, "tagName" => "span");
                }

                $inlineArray[] = $attrs;


                $inlineArray[] = array(
                    "img" => RESOURCES_DOMAIN . "/img/famfam/arrow_left.png",
                    array("nombre" => $elementoOrigen->getUserVisibleName()." (". $tpl($elementoOrigen->getType()) .")", "href" => $elementoOrigen->obtenerUrlFicha() )
                );

                $inlineArray[] = array(
                    "img" => RESOURCES_DOMAIN . "/img/famfam/arrow_right.png",
                    array(
                        "tagName" => "span",
                        "nombre" => $tpl($moduloDestino)
                    )
                );

                if ($download) {
                    $status = $this->getStatus($elementoOrigen, true);

                    $inlineArray[] = array(
                        array(
                            "tagName" => "span",
                            "nombre" => documento::status2String($status),
                            "className" => "help stat stat_".$status
                        )
                    );
                }
                /*
                if( $info["descargar"] ){
                    //$moduloSolicitante = util::getModuleName( $info["uid_modulo_origen"] );
                    //$elementoSolicitante = new $moduloSolicitante( $info["uid_elemento_origen"], false);

                    $status = $this->getStatus($elementoOrigen, true);
                    $stringStatus = documento::status2string($status);

                    $inlineArray[2][0] = array_merge( $inlineArray[2][0], array( "estadoid" => $status, "estado" => $stringStatus ) );
                    /*
                    $inlineArray["estado"] = array(
                        array( "nombre" => $stringStatus, "estadoid" => $status, "estado" => $stringStatus )
                    );
                }
                */

                if( $info["uid_modulo_origen"] == util::getModuleId("agrupamiento") && $info["replica"] != 0 && $usuario instanceof usuario ){
                    $optionsAvailable = $usuario->getAvailableOptionsForModule($this->getModuleId(), 4/*UID DE LA ACCION DE MODIFICAR*/, 1);
                    if ($accion = reset($optionsAvailable)) {
                        $inlineArray[] = array(
                            "img" => array( "src" => RESOURCES_DOMAIN . "/img/famfam/folder_database.png", "className" => "box-it", "href"=> $accion["href"] . "?poid={$this->getUID()}", "title" => $tpl->getString("atributo_replica")),
                            array()
                        );
                    }

                } elseif ($parent = $this->getReplicaParent()) {
                    $inlineArray[3] = array(
                        "img" => array( "src" => RESOURCES_DOMAIN . "/img/famfam/folder_link.png",
                        "className" => "box-it",
                        "href"=> $parent->obtenerUrlFicha(),
                        "title" => sprintf($tpl->getString("atributo_replicado"), $parent->getUserVisibleName())),

                        array()
                    );
                }

                return $inlineArray;
                break;
            }
    }

    public function getReplicaParent () {
        $parent = (int) $this->obtenerDato("uid_documento_atributo_replica");

        if ($parent) {
            $SQL = "SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO . " WHERE uid_documento_atributo = {$parent} AND replica = 1";
            if ($uid = $this->db->query($SQL, 0, 0)) {
                return new documento_atributo($uid);
            }
        }

        return false;
    }

    public function getAnexo($item = NULL, $ref = false, $all = false){
        if( $item instanceof solicitable ){
            $tipo = $item->getType();
            $sql = "SELECT uid_anexo_{$tipo} FROM ". PREFIJO_ANEXOS ."{$tipo} WHERE uid_{$tipo} = {$item->getUID()} AND uid_documento_atributo = {$this->getUID()}";
            if( $ref instanceof agrupador ){
                $sql .= " AND uid_agrupador = {$ref->getUID()}";
            } else {
                $sql .= " AND uid_agrupador = 0";
            }
        } elseif( is_string($item) ){
            $tipo = $item;
            $sql = "SELECT uid_anexo_empresa FROM ". PREFIJO_ANEXOS ."empresa WHERE uid_empresa = 0 AND uid_documento_atributo = {$this->getUID()}";
            if( $ref instanceof agrupador ){
                $sql .= " AND uid_agrupador = {$ref->getUID()}";
            } else {
                $sql .= " AND uid_agrupador = 0";
            }
        }

        if (!$all) {
            $uid = $this->db->query($sql, 0, 0);
            if( is_numeric($uid) ){
                return new anexo($uid, $item);
            }
        }else {
            $list = $this->db->query($sql, "*", 0, "anexo_{$tipo}");
            if ($list && count($list)) return new ArrayObjectList($list);
            return false;
        }
        return false;
    }

    /**
     * Copy an attachment to the example requirement
     * @param  anexo  $attachment The attachment to copy
     * @throws Exception If there isn't example atribute or on error
     */
    public function copyToExample(anexo $attachment)
    {
        $example = $this->obtenerDocumentoEjemplo();

        if ($example instanceof documento_atributo === false) {
            throw new Exception("req_type_necesita_documento_ejemplo", 1);
        }

        $moduleName         = $example->getDestinyModuleName();
        $exampleAttachment  = $example->getAnexo($moduleName);
        $table              = PREFIJO_ANEXOS . $moduleName;
        $primaryKey         = "uid_anexo_" . $moduleName;
        $attachedState      = documento::ESTADO_ANEXADO;
        $commonFields       = "archivo, uid_agrupador, uid_empresa_referencia, hash, nombre_original, fecha_anexion, language, uid_usuario";

        if ($exampleAttachment instanceof anexo) {
            $tableHistoric = PREFIJO_ANEXOS . "historico_$moduleName";

            //move the attachment to the historic
            $sql = "INSERT IGNORE INTO {$tableHistoric}
            (uid_documento_atributo,
            uid_$moduleName,
            estado,
            {$commonFields})
            SELECT
            uid_documento_atributo,
            uid_$moduleName,
            estado,
            {$commonFields}
            FROM {$table}
            WHERE {$primaryKey} = {$exampleAttachment->getUID()}";

            if (!$this->db->query($sql)) {
                throw new Exception("error_guardar_historico");
            }

            // delete the current attachment
            $sql = "DELETE FROM {$table}
            WHERE {$primaryKey} = {$exampleAttachment->getUID()}";

            if (!$this->db->query($sql)) {
                throw new Exception("error_limpiar_actual");
            }
        }

        $sql = "INSERT INTO {$table}
        (uid_documento_atributo,
        uid_$moduleName,
        estado,
        {$commonFields})
        SELECT
        {$example->getUID()},
        0,
        {$attachedState},
        {$commonFields}
        FROM {$table}
        WHERE {$primaryKey} = {$attachment->getUID()}";

        if (!$this->db->query($sql)) {
            throw new Exception("Error copiando anexo a ejemplo");
        }
    }

    public function getChilds($usuario = false){
        $info = $this->getInfo(false, null, $usuario);
        if( $info["replica"] == 1 && $info["uid_modulo_origen"] == util::getModuleId("agrupamiento")){
            $sql = "SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
                WHERE uid_agrupamiento = {$info["uid_elemento_origen"]}
                AND uid_documento = {$info["uid_documento"]}
            ";
            $list = $this->db->query($sql, "*", 0, "documento_atributo");
            return new ArrayObjectList($list);
        } else {
            return new ArrayObjectList();
        }
    }

    public function getReplicas($usuario){
        $info = $this->getInfo(false, null, $usuario);

        if ($info["replica"] == 0) {
            return new ArrayObjectList();
        }

        if ($info["uid_modulo_origen"] == util::getModuleId("agrupamiento") && isset($info['uid_documento_atributo_replica'])) {
            $sql = " SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_documento_atributo_replica = {$this->getUID()} ";
            $list = $this->db->query($sql, "*", 0, "documento_atributo");
            return new ArrayObjectList($list);
        }

        return new ArrayObjectList();
    }

    public function triggerAfterUpdate($usuario, $data, $newData, $fieldsMode){
        // este nunca lo copiamos
        if (isset($data["replica"])) {
            unset($data["replica"]);
        }

        // este nunca lo copiamos
        if (isset($data["uid_documento"])) {
            unset($data["uid_documento"]);
        }

        if ($usuario instanceof usuario && $data) {
            $list = $this->getReplicas($usuario);

            foreach ($list as $item) {
                $result = $item->update($data, $fieldsMode, $usuario);
                if ($result && isset($data['referenciar_empresa']) && isset($newData['referenciar_empresa']) && $data['referenciar_empresa'] != $newData['referenciar_empresa']) {
                    if ($data['referenciar_empresa'] == documento_atributo::REF_TYPE_NONE || $data['referenciar_empresa'] == documento_atributo::REF_TYPE_COMPANY) {
                        $item->asyncUpdateReferenciaEmpresa((bool) $data['referenciar_empresa'], @$data['debug']);
                    }
                }
            }
        }
    }


    public function triggerBeforeDelete($usuario){
        $list = $this->getReplicas($usuario);
        foreach($list as $item){
            $result = $item->eliminar($usuario);
            if( !$result ){
                //dump("No se puede eliminar {$item->getUID()}");
                // Se hace necesario arrojar aqui un error.. pero de momento
                // lo dejo asi para agilizar
            }
        }
    }

    /** CREAR A TRAVES DE UN ARRAY DE DE DATOS UN NUEVO ATRIBUTO DE DOCUMENTO */
    public static function crearNuevo($datos, $usuario = false)
    {
        if (!$usuario instanceof Iusuario) {
            error_log("we need a user for create a new user");
            return false;
        }

        $app = \Dokify\Application::getInstance();
        $companyRepository = $app['company.repository'];

        $empresaUsuario = $usuario->getCompany();
        $companyUser = $empresaUsuario->asDomainEntity();

        $database = db::singleton();

        //---- FORMATEAMOS LA SALIDA
        $alias = db::scape(utf8_decode($datos["nombre_documento"]));
        $obligatorio = (isset($datos["documento_obligatorio"])) ? 1 : 0;
        $descarga = (isset($datos["documento_descarga"]) && $datos["documento_descarga"]) ? 1 : 0;
        $referenciaEmpresa = (isset($datos["referenciar_empresa"])) ? 1 : 0;
        $duracion = db::scape($datos["documento_duracion"]);
        $gracePeriod = db::scape($datos["documento_grace_period"] ?? 0);
        $tipoRequisito = db::scape($datos["req_type"]);
        $uidDocumentoEjemplo = $datos['doc_ejemplo'] ? db::scape($datos['doc_ejemplo']) : 'NULL';
        $codigo = db::scape(utf8_decode($datos["documento_codigo"]));
        $orientation = ($descarga && isset($datos['orientation'])) ? $datos['orientation'] : 'NULL';

        //---- DEBE HABER SOLICITANTES
        if (!isset($datos["id_solicitante"]) && !isset($datos["agrupamiento"])) {
            return "no_solicitante_seleccionado";
        }

        //---- ELEMENTOS DE ORIGEN, LIMIPIAR LOS DATOS
        $elementosOrigen = array();
        if (isset($datos["id_solicitante"]) && is_array($datos["id_solicitante"])) {
            foreach ($datos["id_solicitante"] as $idSoliciante) {
                $elementosOrigen[] = db::scape($idSoliciante);
            }
        }

        //---- MODELO DE DOCUMENTO A CREAR
        $idDocumento = db::scape($datos["tipo_documento"]);

        //comprobar que exista y que hay permiso!!
        if (isset($datos["agrupamiento"])) {
            $moduloSolicitante = "agrupamiento";
            $solicitantesSeleccionados = array($datos["agrupamiento"]);
        } else {
            $moduloSolicitante = ( strtolower($datos["tipo_solicitante"]) == "empresa" ) ? "empresa" : "agrupador";
            $solicitantesSeleccionados = $datos["id_solicitante"];
        }

        $solicitantes = array();
        foreach ($solicitantesSeleccionados as $idSolicitante) {
            $elementoSolicitante = new $moduloSolicitante( $idSolicitante, false );

            $worksFor = false;
            if ($elementoSolicitante instanceof \empresa && false === $elementoSolicitante->compareTo($empresaUsuario)) {
                $companyRequest = $elementoSolicitante->asDomainEntity();
                $worksFor = $companyRepository->queryForClients($companyUser)->worksFor($companyRequest);
            }

            if (!$usuario->accesoElemento($elementoSolicitante) && false === $worksFor) {
                return $elementoSolicitante . "sin_acceso_a_elemento";
            }

            $moduloOrigen = $elementoSolicitante->getModuleId();
            $solicitantes[] = $elementoSolicitante;
        }

        //---- DEBE HABER SOLICITANTES
        if (!count($solicitantes)) {
            return false;
        }

        //---- SE DEBEN HABER SELECCIONADO LOS RECEPTORES
        if (!count($datos["tipo_receptores"])) {
            return "no_modulo_destino";
        }

        $replica = ( isset($datos["replica"]) ) ? (int) $datos["replica"] : 0;

        $idAtributos = array();
        //---- PARA CADA TIPO DE RECEPTOR, CREAREMOS UN ATRIBUTO
        foreach ($datos["tipo_receptores"] as $receptor) {
            $moduloDestino = is_numeric($receptor) ? $receptor : elemento::obtenerIdModulo($receptor);
            if (!is_numeric($moduloDestino)) {
                return "no_modulo_destino";
            }

            //---- PARA CADA TIPO DE SOLICITANTE CREAMOS UN ATRIBUTO
            foreach ($solicitantes as $solicitante) {
                $idOrigen = $solicitante->getUID();
                $uidEmpresaViews = ($empresaUsuario->esCorporacion()) ? $empresaUsuario->getStartIntList() : $empresaUsuario->getUID();
                $sql = "
                INSERT INTO ". TABLE_DOCUMENTO_ATRIBUTO ."
                ( uid_documento, alias, duracion, grace_period, obligatorio, descargar, codigo, uid_elemento_origen, uid_modulo_origen, uid_modulo_destino, uid_empresa_propietaria, uid_empresa_views, replica, referenciar_empresa, req_type, uid_documento_atributo_ejemplo, orientation )
                VALUES
                ( $idDocumento, '$alias', '$duracion', '$gracePeriod', '$obligatorio', '$descarga', '$codigo', '$idOrigen', '$moduloOrigen','$moduloDestino', '". $empresaUsuario->getUID() ."', '$uidEmpresaViews', $replica, '$referenciaEmpresa', $tipoRequisito, $uidDocumentoEjemplo,";
                if ($orientation != 'NULL') {
                    $sql .= " '$orientation' )
                    ";
                } else {
                    $sql .= " $orientation )
                    ";
                }
                if (!$database->query($sql)) {
                    dump($database);
                    return $database->lastErrorString();
                } else {
                    $uid = $database->getLastId();
                    $idAtributos[] = $uid;

                    $documentoAtributo = new documento_atributo($uid);
                    $documentoAtributo->writeLogUI(logui::ACTION_CREATE, "", $usuario);

                    $app = \Dokify\Application::getInstance();
                    $entity = $documentoAtributo->asDomainEntity();
                    $event  = new \Dokify\Application\Event\Requirement\Store($entity);
                    $app->dispatch(\Dokify\Events::POST_REQUIREMENT_STORE, $event);
                }
            }
        }
        return $idAtributos;
    }

    static public function optionsFilter($uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $extraData = null){
        // Instanciamos la bbdd
        $db = db::singleton();
        $condicion = array();

        if( $uidelemento && $uidmodulo && $user ){

            // Aplica?
            $download = $db->query("SELECT descargar FROM ". TABLE_DOCUMENTO_ATRIBUTO . " WHERE uid_documento_atributo = " . $uidelemento, 0, 0);

            if( !$download ) {
                // SQL
                $filtroDescargar = array("'Anexar'","'Descargar'","'Borrar Archivo'","'Plantilla'");
                $condicion[] =  "uid_accion NOT IN ( SELECT uid_accion FROM ". TABLE_ACCIONES ." WHERE alias IN (". implode(",", $filtroDescargar) .") )";

            }

            $modulo = util::getModuleName($uidmodulo);
            $elemento = new $modulo($uidelemento);

            $infoAttr = $elemento->getInfo();
            $uidModuloOrigen = $infoAttr["uid_modulo_origen"];

            if ($uidModuloOrigen == 11 || $uidModuloOrigen == 12){

                $uidEmpresaPropietaria = $infoAttr["uid_empresa_propietaria"];
                $empresaPropietaria = new empresa ($uidEmpresaPropietaria);
                if (!$user->getCompany()->compareTo($empresaPropietaria)){
                    $condicion[] = "uid_accion NOT IN (4,12,14,30,49,59,7,174,177,178)";
                }

            }

            if ($download && $elemento->isUsedAsExample(documento_atributo::TYPE_ONLINE_SIGN)
                || true === $elemento instanceof self && false === $elemento->asDomainEntity()->canHaveAttachment()
            ) {
                $condicion[] = " ( uid_accion NOT IN (7) ) "; // no se puede anexar un documento que esta como ejemplo de firma
            }

            if ($download) {
                $condicion[] = " ( uid_accion NOT IN (178) ) "; // un doc de descarga no tiene criterios
            }


            //obtener array empresas (corporacion) del documento_atributo
            if ($company = $elemento->getCompany()) {
                $empresas = $company->getStartIntList();
                if (!$empresas->contains($user->getCompany()->getUID()))  {
                    $condicion[] = ' 0 ';
                }
            }


            if( count($condicion) ){
                return " AND ". implode(" AND ", $condicion);
            }
        }

        return false;
    }

    static public function getPosiblesEjemplos(array $data){

        if (!isset($data["alias"]) || !isset($data["uid_modulo_destino"]) || !isset($data["uid_empresa_propietaria"])) return false;

        $db = db::singleton();

        $docNameParts = explode(" ", $data["alias"]);

        // Montamos el where para extraer atributos similares
        $where = $coleccion = array();
        foreach( $docNameParts as $part ){
            if( util::cadenaValida($part) ){
                $where[] = "( ". implode(" OR ", prepareLike( array("alias"), $part )) .") ";
            }
        }


        if( count($where) == 0 && count($docNameParts) == 1 ){
            $where[] = "alias LIKE '%". reset($docNameParts) ."%'";
        }


        $sql = "SELECT uid_documento_atributo
                FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
                WHERE ( ". implode(" OR ", $where) ." )
                AND uid_modulo_destino = ". $data["uid_modulo_destino"] ."
                AND uid_empresa_propietaria = ". $data["uid_empresa_propietaria"] ."
                AND descargar = 1
        ";
        if (isset($data["uid"])) $sql .= "AND uid_documento_atributo != ". $data["uid"];

        return new ArrayObjectList($db->query($sql, "*", 0, "documento_atributo"));
    }

    public function obtenerPosiblesEjemplos(){

        $datos = $this->getInfo();
        $data = array();
        $data["alias"] = utf8_encode($this->getUserVisibleName(false, false, false));
        $data["uid_modulo_destino"] = $datos["uid_modulo_destino"];
        $data["uid_empresa_propietaria"] = $datos["uid_empresa_propietaria"];
        $data["uid"] = $this->getUID();

        return self::getPosiblesEjemplos($data);
    }

    public function isUsedAsExample ($reqType = null) {
        $SQL = "
            SELECT count(uid_documento_atributo)
            FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
            WHERE uid_documento_atributo_ejemplo = {$this->getUID()}
        ";

        if (is_numeric($reqType)) $SQL .= " AND req_type = {$reqType}";

        return (bool) db::get($SQL, 0, 0);
    }

    /**
     * Update status and expiration date of attachments affected by duration change
     * @param  string $duration
     * @return array
     */
    public function expandDurationToAllAttachments($duration): array
    {
        return self::expandirDuracion($this, null, $duration);
    }

    public static function expandirDuracion($object, $field, $value, $data = false)
    {
        ini_set('memory_limit', '256M');
        set_time_limit(0);

        if (!is_numeric(str_replace('/', '', $value))) {
            return array();
        }

        $db = db::singleton();
        $data = $object->getInfo();
        $modulo = util::getModuleName($data["uid_modulo_destino"]);
        $class = "anexo_{$modulo}";
        $attachmentsTable = PREFIJO_ANEXOS . $modulo;

        $sql = "SELECT uid_anexo_$modulo as uid, uid_$modulo, fecha_anexion, fecha_emision, fecha_expiracion, estado
        FROM {$attachmentsTable}
        WHERE uid_documento_atributo = {$object->getUID()}
        ";

        $queries = $db->paginateQuery(1000, $sql);
        $countResults = 0;

        foreach ($queries as $sql) {
            $countResults += self::doExpandDuration($value, $db, $sql, $class, $attachmentsTable, $modulo);
            cache::singleton()->clear();
            $db = db::singleton(false, true);
        }

        return (new \SplFixedArray($countResults))->toArray();
    }

    public function obtenerSolicitudDocumentos($modulos = false){
        $sql = "SELECT uid_documento_elemento FROM ". TABLE_DOCUMENTOS_ELEMENTOS ."
            WHERE uid_documento_atributo = {$this->getUID()}";

        if ($modulos) {
            $sql .= " AND uid_modulo_destino IN (".implode(",", $modulos).")";
        }

        $coleccion = $this->db->query($sql, "*", 0, "solicituddocumento");
        try {
            return new ArrayObjectList($coleccion);
        } catch(InvalidArgumentException $e) {
            error_log("InvalidArgumentExcepcion in SQL {$sql}");
            return new ArrayObjectList;
        }
    }

    public function obtenerAnexos($tipoAnexo){
        $sql = "SELECT uid_anexo_$tipoAnexo FROM " .PREFIJO_ANEXOS.$tipoAnexo. "
                WHERE  uid_documento_atributo = {$this->getUID()}";

        $coleccion = $this->db->query($sql, "*", 0, "anexo_$tipoAnexo");
        try {
            return new ArrayObjectList($coleccion);
        } catch(InvalidArgumentException $e) {
            error_log("InvalidArgumentExcepcion in SQL {$sql}");
            return new ArrayObjectList;
        }
    }

    public function isTemplate () {
        $info = $this->downloadFile(true);
        $ext = $info['ext'];

        $editable = array('html','txt');
        if (in_array($ext,$editable)) return true;
        return false;
    }

    public function updateReferenciaEmpresaElemento($onToOff = true, $debug = false){

        set_time_limit(0);
        $tiposItem = array('empleado','maquina');
        $debug = true;
        if ($onToOff) { /**  De SI a NO  **/
            error_log("Entramos en update Referencia para On to Off y el documento atributo #{$this->getUID()}");
            $coleccionSolicitudDocumentos = $this->obtenerSolicitudDocumentos(array('8','14'));
            /**  Obtenemos todas las solicitudes del tipo empleado o maquina (8 ó 14) para un documento atributo **/
            if ($debug) error_log("On To Off. hay " .count($coleccionSolicitudDocumentos)." solicitud documentos ");

            foreach ($coleccionSolicitudDocumentos as $i => $solicitudDocumento) {

                if ($solicitudDocumento->obtenerEmpresaReferencia() == false) {
                    /* Si la solicitud no esta referenciada por empresa no hacemos nada.
                    */
                    if ($debug) error_log("La solicitud no está referenciada, no hacemos nada.");
                    continue;
                }

                if ($debug) error_log("On to off: Solicitud documento: Estamos en el elemento #". ($i+1) ." de un total de ".count($coleccionSolicitudDocumentos));

                $obtenerMismosSolicitudDocumento = $solicitudDocumento->obtenerEntradasRepetidas();

                if (count($obtenerMismosSolicitudDocumento) == 1) {
                    /* Solo hay una solicitud para un mismo documento_atributo, uid_modulo_destino y uid_elemento_destino */
                    if (!$solicitudDocumento->exitsForCompany()) {
                        $done = $solicitudDocumento->update(array("uid_empresa_referencia" => 0));

                        if ($debug) {
                            if ($done===false) error_log("Error al poner referencia a cero de la solicitud #{$solicitudDocumento->getUID()}");
                            if ($done===null) error_log("La solicitud #{$solicitudDocumento->getUID()} ya estaba a cero");
                        }
                    }

                } else if (count($obtenerMismosSolicitudDocumento) > 1) {
                    /* Hay varias solicitudes para un mismo documento_atributo, uid_modulo_destino y uid_elemento_destino */

                    $continue = false;
                    if (!$solicitudDocumento->exitsForCompany()) {
                        if (!$solicitudDocumento->copyTo('0')){
                            error_log("Error clonando la solicitud con {$solicitudDocumento->getUID()} para establecer la referencia a 0");
                            $continue = true;
                        }
                    }

                    if ($continue) continue;

                    /* Eliminamos la solicitud */
                    if (!$solicitudDocumento->eliminar()) {
                        error_log("Error eliminando la Solicitud con UID #{$solicitudDocumento->getUID()}");
                    }
                }
            }


            foreach ($tiposItem as $tipoItemAnexo) {
                /**  Obtenemos todos los anexos para un documento atributo **/
                $coleccionAnexos = $this->obtenerAnexos($tipoItemAnexo);

                if ($debug) error_log("On To Off. Hay " .count($coleccionAnexos)." anexos tipo $tipoItemAnexo ");

                foreach ($coleccionAnexos as $i => $anexo) {
                    if ($debug) error_log("On To Off. Anexo $tipoItemAnexo: Estamos en el elemento #". ($i+1) ." de un total de ".count($coleccionAnexos));

                    $item = $anexo->getElement();
                    if (!$item instanceof $tipoItemAnexo) { continue; }
                    $empresasItem = $item->getCompanies(null);

                    if (count($empresasItem) == 1) {
                        /**  Si solo hay una empresa actualizamos la referencia a 0  **/
                        $done = $anexo->update(array('uid_empresa_referencia' => "0"));

                        if ($debug) {
                            if ($done===false) error_log("Error al poner a cero la referencia a la solicitud de {$tipoItemAnexo} {$anexo->getUID()}");
                            if ($done===null) error_log("La solicitud documento de {$tipoItemAnexo} con {$anexo->getUID()} ya estaba a cero");
                        }
                    }
                }
            }
        } else { /**  De NO a SI  **/
            error_log("Entramos en update Referencia para Off to On y el docuemento atributo #{$this->getUID()}");

            $coleccionSolicitudDocumentos = $this->obtenerSolicitudDocumentos(array('8','14'));
            /**  Obtenemos todas las solicitudes del tipo empleado o maquina (8 ó 14) para un documento atributo **/

            if ($debug) error_log("Off To On. hay " .count($coleccionSolicitudDocumentos)." solicitud documentos");

            foreach ($coleccionSolicitudDocumentos as $i => $solicitudDocumento) {

                if ($solicitudDocumento->obtenerEmpresaReferencia() != false) {
                    /* Si la solicitud tiene como empresa referencia una empresa o
                        un conjunto de empresas no hacemos nada.
                    */
                    if ($debug) error_log("La solicitud está referenciada por empresa, no hacemos nada.");
                    continue;
                }
                if ($debug) error_log("Off to On: Solicitud documento: Estamos en el elemento #". ($i+1) ." de un total de ".count($coleccionSolicitudDocumentos));

                $item = $solicitudDocumento->getElement();
                $empresasItem = $item->getCompanies();
                if (count($empresasItem) > 1) {
                    $continue = false;
                    foreach ($empresasItem as $elementoEmpresa) {
                        /** Clonamos la solicitud tantas solicitudes como empresas está trabajando el item (empleado o máquina) **/
                        if (!$solicitudDocumento->exitsForCompany($elementoEmpresa)) {
                            if (!$solicitudDocumento->copyTo($elementoEmpresa)){
                                error_log("Error clonando la solicitud con {$solicitudDocumento->getUID()} y para la empresa con UID {$elementoEmpresa->getUID()}");
                                $continue = true;
                            }
                        }
                    }

                    if ($continue) continue;
                    /** Eliminamos la solicitud actual ( ya esta clonada por empresa )**/
                    if(!$solicitudDocumento->eliminar()) {
                        error_log("Error eliminando la solicitud documento de {$item->getModuleName()} con UID {$solicitudDocumento->getUID()}");
                    }

                } elseif (count($empresasItem) == 1) {
                    /* Actualizamos la solicitud del documento */
                    $empresaItem = $empresasItem->get(0);
                    if (!$solicitudDocumento->exitsForCompany($empresaItem)) {
                        $done = $solicitudDocumento->update(array("uid_empresa_referencia" => $empresaItem->getUID()));

                        if ($debug) {
                            if ($done===false) error_log("Error al poner referencia a la solicitud {$solicitudDocumento->getUID()}");
                            if ($done===null) error_log("La solicitud documento {$solicitudDocumento->getUID()} ya estaba referenciada");
                        }

                    }
                }
            }

            foreach ($tiposItem as $tipoItemAnexo) {
                $coleccionAnexos = $this->obtenerAnexos($tipoItemAnexo);

                if ($debug) error_log("Off To On. Hay " .count($coleccionAnexos)." anexos tipo $tipoItemAnexo");

                /**  Obtenemos todos los anexos para un documento atributo **/
                foreach ($coleccionAnexos as $i => $anexo) {

                    if ($anexo->obtenerEmpresaReferencia() != false) {
                        /* Si el tiene como empresa referencia una empresa o
                            un conjunto de empresas no hacemos nada.
                        */
                        if ($debug) error_log("El anexo está referenciado por empresa, no hacemos nada.");
                        continue;
                    }

                    if ($debug) error_log("Off To On. Anexo $tipoItemAnexo: Estamos en el elemento #". ($i+1) ." de un total de ".count($coleccionAnexos));

                    $item = $anexo->getElement();
                    if (!$item instanceof $tipoItemAnexo) { continue; }
                    $empresasItem = $item->getCompanies(null);
                    if (count($empresasItem)>1) {
                        $continue = false;
                        foreach ($empresasItem as $elementoEmpresa) {
                            /* Clonamos el anexo por cada empresa en la que esta trabajando el item (empleada o máquina)*/
                            if (!$anexo->exitsForCompany($elementoEmpresa)) {
                                if (!$anexo->copyTo($elementoEmpresa)) {
                                    error_log("Error clonando el anexo de {$tipoAnexo} con uid #{$anexo->getUID()} con referencia a la empresa con UID #{$elementoEmpresa->getUID()}");
                                    $continue = true;
                                }
                            }
                        }

                        if ($continue) continue;

                        if (!$anexo->eliminar()) {
                            error_log("Error eliminando el anexo con UID #{$anexo->getUID()}");
                        }

                    }elseif (count($empresasItem) == 1) {
                        /** Actualizamos la referencia_empresa del anexo del empleado o la máquina **/
                        $empresaItem = $empresasItem->get(0);
                        $done = $anexo->update(array('uid_empresa_referencia' => $empresaItem->getUID()));

                        if ($debug) {
                            if ($done===false) error_log("Error al referenciar el anexo {$tipoItemAnexo} con uid #{$anexo->getUID()}");
                            if ($done===null) error_log("El anexo {$tipoItemAnexo} #{$anexo->getUID()} ya estaba referenciado a esta empresa");
                        }
                    }
                }
            }
        }

        error_log("Salimos de referenciar empresa para el documento atributo #{$this->getUID()}");
        return true;
    }


    public static function getExportSQL($usuario=false, $uids, $forced, $parent=false){
        $campos = array();
        if( $usuario && $usuario->esStaff() ){
            $campos[] = "uid_documento_atributo";
        }

        $campos[] = "alias";
        $campos[] = reporte::dato( TABLE_DOCUMENTO_ATRIBUTO . ".uid_documento", "documento", "nombre") . " as 'Tipo de documento'";
        $campos[] = reporte::tipoSoliciante(TABLE_DOCUMENTO_ATRIBUTO) . " as 'tipo solicitante'";
        $campos[] = reporte::soliciante(TABLE_DOCUMENTO_ATRIBUTO) . " as solicitante";
        $campos[] = "if(obligatorio, 'Si', 'No') as 'Obligatorio'";
        $campos[] = "if(descargar, 'Si', 'No') as 'Solo descarga'";
        $campos[] = "if(duracion, duracion, 'No Caduca') as duracion";
        $campos[] = "if(caducidad_manual, 'Si','No') as 'Caducidad manual'";
        $campos[] = "if(caducidad_automatica, 'Si','No') as 'Caducar cuando no se solicite'";
        $campos[] = "recursividad";
        $campos[] = "codigo as 'Cod (Opcional)'";


        $sql =  "SELECT ". implode(",", $campos) ." FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE 1";

        if( is_traversable($uids) && count($uids) ){
            $sql .=" AND uid_documento_atributo in (". implode(",", $uids ) .")";
        }

        if( $parent instanceof elemento ){
            $sql .=" AND uid_modulo_origen = {$parent->getModuleId()} AND uid_elemento_origen = {$parent->getUID()} ";
        }

        if ($parent instanceof agrupador) {
            $sql .= " AND activo = 1";
        }

        if ($usuario instanceof usuario) {

            if ($usuario->isViewFilterByLabel()) {
                $labels = $usuario->obtenerEtiquetas();
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

        if( is_traversable($forced) && count($forced) ){
            $sql .=" AND uid_documento_atributo IN (". implode(",", $forced) .")";
        }

        //dump($sql);exit;
        return $sql;
    }


    public function asyncUpdateReferenciaEmpresa($onToOff, $debug = false){
        return archivo::php5exec( DIR_ROOT . "func/cmd/updateSolicitudesAnexos.php", array($this->getUID(), (int)$onToOff, $debug));
    }

    public function hasExample() {
        $ejemplo = $this->obtenerDocumentoEjemplo();
        return $ejemplo instanceof documento_atributo && $ejemplo->obtenerDato("descargar") && $ejemplo->isLoaded();
    }

    public function getRequirementType() {
        return $this->obtenerDato("req_type");
    }


    /** SE INVOCARÁ CUANDO SE MODIFIQUE UN ELEMENTO EXISTENTE. SOLO TIENE SENTIDO SI ES SOBREESCRITA **/
    public function updateData($data, Iusuario $usuario = null, $mode = null)
    {
        if (isset($data["uid_agrupador_condicion"])) {
            if (is_array($data["uid_agrupador_condicion"]) && $list = $data["uid_agrupador_condicion"]) {
                asort($list);
                $data["uid_agrupador_condicion"] = implode(",", $list);
            }
        }

        if (isset($data["uid_agrupamiento_condicion"])) {
            if (is_array($data["uid_agrupamiento_condicion"]) && $list = $data["uid_agrupamiento_condicion"]) {
                asort($list);
                $data["uid_agrupamiento_condicion"] = implode(",", $list);
            }
        }

        if (isset($data["labels_condition"])) {
            if (is_array($data["labels_condition"]) && $list = $data["labels_condition"]) {
                asort($list);
                $data["labels_condition"] = implode(",", $list);
            }
        }

        if (isset($data["uid_agrupador_company_condicion"])) {
            if (is_array($data["uid_agrupador_company_condicion"]) && $list = $data["uid_agrupador_company_condicion"]) {
                asort($list);
                $data["uid_agrupador_company_condicion"] = implode(",", $list);
            }
        }

        if (isset($data["target_company_kinds"])) {
            if (is_array($data["target_company_kinds"]) && $list = $data["target_company_kinds"]) {
                asort($list);
                $data["target_company_kinds"] = implode(",", $list);
            }
        }

        if (isset($data["replica"]) && $this->obtenerDato("replica") && $data["replica"] == 0) {
            $data["activo"] = 0;
        }

        // Cuando queramos establecer el tipo de requisito como firma, tememos que comprobar que hay un documento de ejemplo
        if (isset($data["req_type"]) && $data["req_type"] == self::TYPE_ONLINE_SIGN) {
            $hasExample = isset($data['uid_documento_atributo_ejemplo']) && $data['uid_documento_atributo_ejemplo'];

            if (!$hasExample) {
                throw new Exception("req_type_necesita_documento_ejemplo", 1);
            }

            // Vamos a verificar que el documento es una plantilla
            $attr = new documento_atributo($data['uid_documento_atributo_ejemplo']);
            $info = $attr->downloadFile(true);

            if ($info['ext'] !== 'html') {
                throw new Exception("req_type_ejemplo_html", 1);
            }
        }

        if (isset($data["orientation"]) && ($data["orientation"]=="L" || $data["orientation"]=="P")) {
            $isDownload = $data['descargar'];
            if (!$isDownload) {
                throw new Exception("orientation_necesita_documento_descarga", 1);
            }
        }

        if (isset($data["copy_to_example"]) && $data['copy_to_example'] == 1) {
            $hasExample = isset($data['uid_documento_atributo_ejemplo']) && $data['uid_documento_atributo_ejemplo'];
            $isDownload = $data['descargar'];

            if ($isDownload) {
                throw new Exception("copy_to_example_not_template", 1);
            }

            if ($hasExample === false) {
                throw new Exception("copy_to_example_needs_example", 1);
            }
        }

        return $data;
    }

    public static function getRequirementTypes() {
        return array(
            self::TYPE_FILE_UPLOAD  => 'requirement_type_upload',
            self::TYPE_ONLINE_SIGN  => 'requirement_type_sign',
        );
    }

    public function getIsCutom(){
        $info = $this->getInfo();
        return (bool)$info["is_custom"];
    }

    /**
     * Returns the array of the current kinds of company targets
     * @return array
     */
    public function getTargetCompanyKinds()
    {
        $kindList = $this->obtenerDato('target_company_kinds');
        $numbers  = array_filter(explode(',', $kindList), 'is_numeric');
        return array_map('intval', $numbers);
    }

    /**
     * Get the region code to match to the company's region
     * @return string
     */
    public function getCompanyRegionTarget()
    {
        return $this->obtenerDato('target_company_region');
    }

    /**
     * Get all the available regions
     * @return array
     */
    public static function getTargetRegions()
    {
        $regions = [
            'UE'  => _('European Union'),
            '!UE' => _('Outside European Union'),
            '!ES' => _('Outside Spain'),
            'ALL' => _('Worldwide'),
        ];

        $countries = TABLE_PAIS;
        $sql = "SELECT char_code, nombre
        FROM {$countries}
        WHERE enable_target_region = 1
        ";

        $dbc = db::singleton();

        if ($rows = $dbc->query($sql, true)) {
            foreach ($rows as $row) {
                $regions[$row['char_code']] = _(utf8_encode($row['nombre']));
            }
        }

        return $regions;
    }

    public static function cronCall($time, $force = false) {
        $minute = date("i", $time);
        $run = $minute == "05" || $force;

        if (!$run) return false;

        $db = db::singleton();

        $SQL = "SELECT uid_empresa FROM ". TABLE_EMPRESA . " WHERE activo_corporacion = 1";

        if ($list = $db->query($SQL, '*', 0, 'empresa')) {
            foreach ($list as $company) {
                $intList = $company->getStartIntList();

                echo "Update views {$company->getUserVisibleName()} .. ";
                $SQL = "UPDATE ". TABLE_DOCUMENTO_ATRIBUTO . " SET uid_empresa_views = '{$intList}' WHERE uid_empresa_propietaria = {$company->getUID()} AND activo = 1";
                if ($db->query($SQL)) {
                    echo " Ok";
                } else {
                    echo " Error [$db->lastError()}]";
                }

                echo "\n";
            }
        }

        return true;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $arrayCampos = new FieldList();

        switch ($modo) {
            default:
                $arrayCampos["alias"] = new FormField([
                    "tag" => "input",
                    "type" => "text",
                    "blank" => false,
                ]);

                break;
            case self::PUBLIFIELDS_MODE_CONDITIONALS:
                if ($objeto instanceof documento_atributo && $usuario instanceof usuario) {
                    $empresa = $objeto->getCompany();
                    $groups = $empresa->obtenerAgrupadoresVisibles();
                    $target = $objeto->getDestinyModuleName();

                    if ($target != "empresa") {
                        $arrayCampos["uid_agrupador_company_condicion[]"] = new FormField([
                            "tag" => "select",
                            "innerHTML" => "Condicional empresa",
                            "data" => $groups,
                            "default" => "Seleccionar",
                            "list" => true,
                            "search" => true,
                            "info" => true,
                        ]);

                        $arrayCampos["company_negative_condition"] = new FormField([
                            "tag" => "input",
                            "type" => "checkbox",
                            "className" => "iphone-checkbox",
                            "info" => true,
                            "hr" => true,
                        ]);
                    }

                    $arrayCampos["labels_condition[]"] = new FormField([
                        "tag" => "select",
                        "innerHTML" => "Condicional",
                        "data" => $empresa->getLabels(),
                        "default" => "Seleccionar",
                        "list" => true,
                        "search" => true,
                        "info" => true,
                    ]);

                    $arrayCampos["uid_agrupamiento_condicion[]"] = new FormField([
                        "tag" => "select",
                        "innerHTML" => "Condicional",
                        "data" => $empresa->obtenerAgrupamientosVisibles(),
                        "default" => "Seleccionar",
                        "list" => true,
                        "info" => true,
                        "hr" => true,
                    ]);

                    $arrayCampos["uid_agrupador_condicion[]"] = new FormField([
                        "tag" => "select",
                        "innerHTML" => "Condicional",
                        "data" => $groups,
                        "default" =>
                        "Seleccionar",
                        "list" => true,
                        "search" => true,
                        "info" => true,
                    ]);

                    $arrayCampos["condicion_negativa"] = new FormField([
                        "tag" => "input",
                        "type" => "checkbox",
                        "className" => "iphone-checkbox",
                        "info" => true,
                    ]);

                    $arrayCampos["condition_type"] = new FormField([
                        "tag" => "input",
                        "type" => "checkbox",
                        "className" => "iphone-checkbox",
                        "info" => true,
                    ]);
                }

                break;
            case self::PUBLIFIELDS_MODE_CRITERIA:
                $arrayCampos["criteria"] = new FormField([
                    "tag" => "input",
                    "type" => "textarea",
                ]);

                break;
            case self::PUBLIFIELDS_MODE_REFERENCIAR:
                $arrayCampos["referenciar_empresa"] = new FormField;
                break;
            case self::PUBLIFIELDS_MODE_EDIT:
                $tpl = new Plantilla;
                $download = $objeto instanceof documento_atributo && $objeto->obtenerDato("descargar") == "1";
                $empresa = $objeto instanceof documento_atributo ? $objeto->getCompany() : null;

                if ($usuario instanceof usuario && $usuario->esStaff() && $objeto instanceof documento_atributo) {
                    $arrayCampos["uid_documento"] = new FormField([
                        "innerHTML" => "Documento",
                        "tag" => "select",
                        "blank" => false,
                        "size" => "10",
                        "data" => documento::getAll(),
                        "search" => true,
                    ]);
                }

                $arrayCampos["alias"] = new FormField([
                    "tag" => "input",
                    "type" => "text",
                    "blank" => false,
                ]);

                if (!$download && $empresa instanceof empresa) {
                    $arrayCampos["uid_documento_atributo_ejemplo"] = new FormField([
                        "tag" => "select",
                        "innerHTML" => "Documento ejemplo",
                        "data" => $objeto->obtenerPosiblesEjemplos(),
                        "default" => "seleccionar_ejemplo",
                    ]);
                }

                if ($download && $objeto instanceof self && $objeto->isTemplate()) {
                    $arrayCampos["orientation"] = new FormField([
                        "tag" => "select",
                        "data" => ["P" => "vertical", "L" => "apaisado"],
                        "default" => "Seleccionar",
                    ]);
                }

                if (!$download) {
                    $arrayCampos["duracion"] = new FormField([
                        "tag" => "input",
                        "type" => "text",
                        "blank" => false,
                        "size" => "18",
                    ]);

                    if ($usuario instanceof usuario && $usuario->esStaff()) {
                        $arrayCampos["grace_period"] = new FormField([
                            "tag" => "input",
                            "type" => "text",
                            "blank" => false,
                            "size" => "18",
                        ]);
                    }
                }

                $arrayCampos["uid_modulo_destino"] = new FormField([
                    "tag" => "select",
                    "data" => solicitable::getModules(),
                ]);

                $arrayCampos["codigo"] = new FormField([
                    "tag" => "input",
                    "type" => "text",
                ]);

                $arrayCampos["req_type"] = new FormField([
                    "tag" => "select",
                    "data" => self::getRequirementTypes(),
                ]);

                $arrayCampos["recursividad"] = new FormField([
                    "tag" => "slider",
                    "type" => "text",
                    "match" => "^([0-3])$",
                    "count" => "4",
                ]);

                $arrayCampos["criticity"] = new FormField([
                    "tag" => "slider",
                    "type" => "text",
                    "match" => "^([0-3])$",
                    "count" => "10",
                    "hr" => true,
                ]);

                $arrayCampos["caducidad_manual"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                $arrayCampos["caducidad_automatica"]= new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                $arrayCampos["obligatorio"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                $arrayCampos["descargar"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                $arrayCampos["relevante"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                $arrayCampos["certificacion"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                $arrayCampos["no_relacionar"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                $arrayCampos["modulo_salud"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                $arrayCampos["is_custom"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                    "info" => true,
                ]);

                if ($usuario instanceof usuario && $usuario->esStaff()) {
                    $arrayCampos["attach_multiple"] = new FormField([
                        "tag" => "input",
                        "type" => "checkbox",
                        "className" => "iphone-checkbox",
                        "info" => true,
                    ]);
                }

                $arrayCampos["activo"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                    "hr" => true,
                ]);

                if ($objeto instanceof documento_atributo) {
                    if (isset($arrayCampos["duracion"])) {
                        $arrayCampos["duracion"]["extra"] = new FormField([
                            [
                                "tag" => "input",
                                "type" => "checkbox",
                                "name" => "duracionExtra",
                                "innerHTML" => "duracion_extra",
                                "callback" => "expandirDuracion",
                            ],
                        ]);
                    }

                    $arrayCampos["agrupamiento_auto"] = new FormField([
                        "tag" => "select",
                        "innerHTML" => "Agrupamiento",
                        "data" => $empresa->obtenerAgrupamientosPropios(),
                        "default" => "Seleccionar",
                        "info" => true,
                        "hr" => true,
                    ]);

                    if ($objeto->getDestinyModuleName() === 'empresa') {
                        $arrayCampos["target_company_kinds[]"]  = new FormField([
                            "tag" => "select",
                            "innerHTML" => "Tipos de empresa",
                            "data" => empresa::getKindsSelect(),
                            "list" => true,
                        ]);

                        $arrayCampos["target_company_region"]  = new FormField([
                            "tag" => "select",
                            "innerHTML" => _("Regiones"),
                            "data" => self::getTargetRegions(),
                        ]);
                    }
                }

                if ($usuario instanceof usuario && $usuario->esStaff()) {
                    $arrayCampos["replica"] = new FormField([
                        "tag" => "select",
                        "data" => [0 => 'No replicar', 1 => 'Replicar atributo', 2 => 'Replicar atributo + tipo'],
                        "hr" => true,
                    ]);
                }

                $arrayCampos["target_n1"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                $arrayCampos["target_n2"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                $arrayCampos["target_n3"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" =>
                    "iphone-checkbox",
                ]);

                $arrayCampos["target_n4"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                    "hr" => true,
                ]);

                $arrayCampos["copy_to_example"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                $arrayCampos["only_coordinator"] = new FormField([
                    "tag" => "input",
                    "type" => "checkbox",
                    "className" => "iphone-checkbox",
                ]);

                break;
        }
        return $arrayCampos;
    }

    public static function getTableFields()
    {
        return array(
            array("Field" => "uid_documento_atributo",              "Type" => "int(10)",        "Null" => "NO",     "Key" => "PRI",     "Default" => "",                    "Extra" => "auto_increment"),
            array("Field" => "uid_documento",                       "Type" => "int(10)",        "Null" => "NO",     "Key" => "MUL",     "Default" => "",                    "Extra" => ""),
            array("Field" => "alias",                               "Type" => "varchar(100)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_modulo_origen",                   "Type" => "int(10)",        "Null" => "NO",     "Key" => "MUL",     "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_modulo_destino",                  "Type" => "int(10)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_elemento_origen",                 "Type" => "int(10)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "duracion",                            "Type" => "varchar(50)",    "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "grace_period",                        "Type" => "int(3)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "caducidad_manual",                    "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "caducidad_automatica",                "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "obligatorio",                         "Type" => "int(1)",         "Null" => "NO",     "Key" => "MUL",     "Default" => "1",                   "Extra" => ""),
            array("Field" => "descargar",                           "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "codigo",                              "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_empresa_propietaria",             "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_empresa_views",                   "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "relevante",                           "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "recursividad",                        "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "3",                   "Extra" => ""),
            array("Field" => "criticity",                           "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "certificacion",                       "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "no_relacionar",                       "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "uid_documento_atributo_ejemplo",      "Type" => "int(11)",        "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "replica",                             "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "uid_agrupamiento",                    "Type" => "int(11)",        "Null" => "YES",    "Key" => "MUL",     "Default" => "0",                   "Extra" => ""),
            array("Field" => "agrupamiento_auto",                   "Type" => "int(11)",        "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "modulo_salud",                        "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "referenciar_empresa",                 "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "attach_multiple",                     "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "1",                   "Extra" => ""),
            array("Field" => "fecha",                               "Type" => "timestamp",      "Null" => "NO",     "Key" => "",        "Default" => "CURRENT_TIMESTAMP",   "Extra" => "on update CURRENT_TIMESTAMP"),
            array("Field" => "activo",                              "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "1",                   "Extra" => ""),
            array("Field" => "uid_agrupador_condicion",             "Type" => "varchar(1024)",  "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_agrupamiento_condicion",          "Type" => "varchar(1024)",  "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_agrupador_company_condicion",     "Type" => "varchar(1024)",  "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "condicion_negativa",                  "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "condition_type",                      "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "uid_documento_atributo_replica",      "Type" => "int(11)",        "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "req_type",                            "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "is_custom",                           "Type" => "int(1)",         "Null" => "NO",     "Key" => "MUL",     "Default" => "0",                   "Extra" => ""),
            array("Field" => "orientation",                         "Type" => "char(1)",        "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "criteria",                            "Type" => "text",           "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "target_company_kinds",                "Type" => "varchar(254)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "target_n1",                           "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "1",                   "Extra" => ""),
            array("Field" => "target_n2",                           "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "1",                   "Extra" => ""),
            array("Field" => "target_n3",                           "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "1",                   "Extra" => ""),
            array("Field" => "target_n4",                           "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "1",                   "Extra" => ""),
            array("Field" => "target_company_region",               "Type" => "varchar(3)",     "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "copy_to_example",                     "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
            array("Field" => "only_coordinator",                    "Type" => "int(1)",         "Null" => "NO",     "Key" => "MUL",     "Default" => "0",                   "Extra" => ""),
            array("Field" => "labels_condition",                    "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "company_negative_condition",          "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",                   "Extra" => ""),
        );
    }
}
