<?php

class solicituddocumento extends elemento implements Ielemento, Iactivable {

    public function __construct($param, $extra = false ){
        $this->tipo = "solicituddocumento";
        $this->tabla = TABLE_DOCUMENTOS_ELEMENTOS;
        $this->instance($param, $extra);
    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Requirement\Request\Request
     */
    public function asDomainEntity()
    {
        return $this->app['requirement_request.repository']->factory($this->getInfo());
    }

    public static function getRouteName () {
        return 'request';
    }


    /***
       *
       *
       *
       */
    public static function getNearExpireSQL ($class)
    {
        $key            = "uid_anexo_{$class}";
        $near           = anexo::getNotificationSQL();
        $attachments    = PREFIJO_ANEXOS . $class;
        $near           = "SELECT a.{$key}
        FROM {$attachments} a
        WHERE 1
        AND a.{$key} = view.{$key}
        AND {$near}";

        return $near;
    }

    public function obtenerElementosActivables(usuario $usuario = NULL){
        return array($this->getElement());
    }

    public function isDeactivable($parent, usuario $usuario){
        return true;
    }

    public function isActivable($parent = false, usuario $usuario = NULL){
        return true;
    }

    public function isEditableBy (Iusuario $user)
    {
        $userCompany    = $user->getCompany();
        $startList      = $userCompany->getStartList();
        $atribute       = $this->obtenerDocumentoAtributo();
        $referenceType  = $atribute->getReferenceType();

        if ($referenceType) {
            $reference  = $this->obtenerEmpresaReferencia();

            switch ($referenceType) {
                case documento_atributo::REF_TYPE_COMPANY:
                    if ($startList->contains($reference) == false) {
                        return false;
                    }
                    break;

                case documento_atributo::REF_TYPE_CHAIN:
                    if (true === is_countable($reference) && count($reference) > 1) {
                        $reference = $reference->getLast();
                    }

                    if ($startList->contains($reference) == false) {
                        return false;
                    }
                    break;
            }
        }

        $item       = $this->getElement();
        $itemModule = $item->getModuleName();

        switch ($itemModule) {
            case 'empresa':
                return $startList->contains($item);
                break;

            case 'empleado':
                return $userCompany->hasEmployee($item);
                break;

            case 'maquina':
                return $userCompany->hasMachine($item);
                break;
        }

        return false;
    }

    public function enviarPapelera($parent, usuario $usuario){
        $sql = "UPDATE {$this->tabla} SET papelera = 1 WHERE uid_documento_elemento = {$this->getUID()}";
        return $this->db->query($sql);
    }


    public function restaurarPapelera($parent, usuario $usuario){
        $sql = "UPDATE {$this->tabla} SET papelera = 0 WHERE uid_documento_elemento = {$this->getUID()}";
        return $this->db->query($sql);
    }

    public function inTrash($parent) {
        return (bool) $this->obtenerDato("papelera");
    }

    public function removeParent(elemento $parent, usuario $usuario = null) {
        return false;
    }

    public function getInlineArray($usuario, $mode=null, $data = null){
        $data = $data ?: array();
        $data[Ilistable::DATA_REFERENCE] = $this;
        $tpl = Plantilla::singleton();
        $inlineArray = array();
        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;
        switch($context){
            case Ilistable::DATA_CONTEXT_DESCARGABLES:
                $atributo = $this->obtenerDocumentoAtributo();
                $anexo = $this->getAnexo(true);
                if ($anexo) {
                    $parent = $data[Ilistable::DATA_PARENT];

                    $inlineArray[] = array(

                        "style" => "text-align: left",
                        "img" =>RESOURCES_DOMAIN . "/img/famfam/chart_organisation.png",
                        array(
                            "tagName" => "span",
                            "nombre" => string_truncate($this->getRequestString(), 60)
                        )
                    );

                    $inlineArray[] = array(
                        "style" => "text-align: right",
                        "img" =>RESOURCES_DOMAIN . "/img/famfam/arrow_down.png",
                        array(
                            "nombre" => $tpl->getString("descargar"),
                            "href" => "../agd/descargar.php?poid={$atributo->getDocumentsId()}&o={$parent->getUID()}&oid={$anexo->getUID()}&m={$parent->getType()}&comefrom={$this->getUID()}&context=descargables&descargable=true&action=dl",
                            "target" => "async-frame"
                        )
                    );

                } else {

                    $inlineArray[] = array(

                        "style" => "text-align: left",
                        "img" =>RESOURCES_DOMAIN . "/img/famfam/chart_organisation.png",
                        array(
                            "tagName" => "span",
                            "nombre" => $this->getRequestString()
                        )
                    );

                    $inlineArray[] = array(
                        "style" => "text-align: right",
                        "img" =>RESOURCES_DOMAIN . "/img/famfam/cross.png",
                        array(
                            "tagName" => "span",
                            "nombre" => $tpl->getString("inactivo")
                        )
                    );
                }
                return $inlineArray;
            break;
            default:
                $elemento = $this->getElement();

                $inlineArray = array(); // inline data

                if ($context != Ilistable::DATA_CONTEXT_TREE) {
                    $inlineArray[] = array(
                        "img" => RESOURCES_DOMAIN . "/img/famfam/arrow_rotate_anticlockwise.png",
                        array(
                            "nombre" => $elemento->getUserVisibleName(),
                            "href" => "ficha.php?m={$elemento->getModuleName()}&oid={$elemento->getUID()}"
                        )
                    );
                }

                $docInline = $this->obtenerDocumento()->getInlineArray($usuario, $mode, $data);

                $inlineArray = array_merge($inlineArray, $docInline);
                return $inlineArray;
            break;
        }
    }


    public function getAnexo($dl=false){
        $attr = $this->obtenerDocumentoAtributo();

        if( $dl ){
            $uid_empresa = $attr->obtenerDato('uid_empresa_propietaria');
            $sql = "SELECT uid_anexo_empresa FROM ". PREFIJO_ANEXOS ."empresa
                WHERE (uid_empresa = 0 OR uid_empresa = $uid_empresa)
                AND uid_documento_atributo = {$attr->getUID()} LIMIT 1";
            $item = "empresa";
        }  else {
            $item = $this->getElement();
            $type = $item->getModuleName();
            $table = PREFIJO_ANEXOS . $type;


            $agrupador = ( $agrupador = $this->obtenerAgrupadorReferencia() ) ? $agrupador->getUID() : 0;
            $empresa = ( $idempresa = $this->obtenerIdEmpresaReferencia() ) ? $idempresa : 0;

            $sql = "SELECT uid_anexo_{$type} FROM $table
                WHERE uid_{$type} = {$item->getUID()}
                AND uid_documento_atributo = {$attr->getUID()}
                AND uid_agrupador = {$agrupador}
                AND uid_empresa_referencia = '{$empresa}' LIMIT 1";
        }

        if( $uid = $this->db->query($sql, 0, 0) ){
            return new anexo($uid, $item);
        }

        return false;
    }


    public function dateUpdated(){
        $item = $this->getElement();
        $type = $item->getModuleName();
        $table = PREFIJO_ANEXOS . $type;
        $attr = $this->obtenerDocumentoAtributo();

        $agrupador = ($agrupador = $this->obtenerAgrupadorReferencia()) ? $agrupador->getUID() : 0;
        $empresa = ($empresa = $this->obtenerEmpresaReferencia()) ? $empresa : 0;

        if ($empresa instanceof empresa) {
            $empresaRef = $empresa->getUID();
        } elseif ($empresa instanceof ArrayObjectList) {
            $empresaRef = $empresa->toComaList();
        } else {
            $empresaRef = '0';
        }

        $sql = "SELECT fecha_emision_real FROM $table
            WHERE uid_{$type} = {$item->getUID()}
            AND uid_documento_atributo = {$attr->getUID()}
            AND uid_agrupador = {$agrupador}
            AND uid_empresa_referencia = '{$empresaRef}' LIMIT 1";


        $firstDate = $this->db->query($sql, 0, 0);

        return (bool) trim($firstDate);
    }


    public function getStatus($string=false, $idioma = null){
        $item = $this->getElement();
        $type = $item->getModuleName();
        $table = PREFIJO_ANEXOS . $type;
        $attr = $this->obtenerDocumentoAtributo();

        $agrupador = ( $agrupador = $this->obtenerAgrupadorReferencia() ) ? $agrupador->getUID() : 0;
        $empresa = ( $idempresa = $this->obtenerIdEmpresaReferencia() ) ? $idempresa : 0;

        $sql = "SELECT estado FROM {$table}
            WHERE uid_{$type} = {$item->getUID()}
            AND uid_documento_atributo = {$attr->getUID()}
            AND uid_agrupador = {$agrupador}
            AND uid_empresa_referencia = '{$empresa}' LIMIT 1";

        $estado = (int) $this->db->query($sql, 0, 0);

        if( $string ){
            return documento::status2String($estado, $idioma);
        } else {
            return $estado;
        }
    }

    public function getStatusData() {
        $templ = Plantilla::singleton();
        $statusData = array();
        $anexo = $this->getAnexo();

        $statusData['status'] = $this->getStatus();
        if ($anexo && $anexo->isRenovation()) {
            $expiredRenovation = $anexo->getExpirationRenovation();
            $statusData['stringStatus'] = $this->getStatus(true).' '.$templ->getString('renovation_initial');
            $statusData['title'] = sprintf($templ('explain_request_renovation'),date('d-m-Y',$expiredRenovation));
        } else {
            $statusData['stringStatus'] = $this->getStatus(true);
            $statusData['title'] = $templ->getString('explain_request.stat_'.$statusData['status']);
        }

        return $statusData;
    }

    public function getHTMLStatus(){
        $templ = Plantilla::singleton();
        $statusData = $this->getStatusData();
        $anexo = $this->getAnexo();
        $status = $this->getStatus();
        $stringStatus = $this->getStatus(true);

        $html = "<span title=\"{$statusData['title']}\" class=\"stat stat_{$status} help\">{$statusData['stringStatus']}</span>";

        if ($anexo && $imageInfo = $anexo->getImageInfo()) {
            $html .= "<img title=\"{$imageInfo['title']}\" style='width: 14px; height: 14px; vertical-align: middle' src=\"{$imageInfo['src']}\" />";
        }

        return $html;
    }

    public function getRequesters($reverse = false) {
        $atributo = $this->obtenerDocumentoAtributo();
        $elemento = $atributo->getElement();

        $pieces = array();
        //$pieces[] = $atributo->getUserVisibleName();

        if( $agrupador = $this->obtenerAgrupadorReferencia() ){
            $pieces[] = $agrupador->getUserVisibleName();
        }

        if( $empresa = $this->obtenerEmpresaReferencia() ){
            if( $empresa instanceof empresa ){
                $pieces[] = $empresa->getUserVisibleName();
            } elseif( $empresa instanceof ArrayObjectList ){
                $pieces[] = implode(", ", $empresa->getNames());
            }
        }

        if( $elemento instanceof agrupador ){
            $pieces[] = $elemento->getNombreTipo();
            $pieces[] = $elemento->getUserVisibleName();
        } else {
            $tpl = Plantilla::singleton();
            $pieces[] = $elemento->getUserVisibleName();
            $pieces[] = $tpl("empresa");
        }

        if ($reverse) $pieces = array_reverse($pieces);

        return $pieces;
    }

    public function getRequestString($corto = false){
        $pieces = $this->getRequesters();

        return implode(" &laquo; ", $pieces);
    }

    public function getUserVisibleName($corto = false){
        if ($corto) return $this->getRequestString();

        $atributo = $this->obtenerDocumentoAtributo();
        return $atributo->getUserVisibleName() . " &laquo; " . $this->getRequestString();
    }

    public function getAvailableOptions(Iusuario $usuario = NULL, $publicMode = false, $config = 0, $groups=true, $ref=false, $extraData = null){
        return $this->obtenerDocumento()->getAvailableOptions($usuario, $publicMode, $config, true, $this);
    }

    public static function optionsFilter( $uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null ){
        if ($uidelemento && $uidmodulo && $user) {
            $modulo = util::getModuleName($uidmodulo);
            $elemento = new $modulo($uidelemento);
            $empresas = $elemento->obtenerAgrupadorContenedor()->getCompany()->getStartIntList();
            if (!$empresas->contains($user->getCompany()->getUID())) {
                return ' AND 0 ';
            }
        }

        return false;
    }

    public function getTreeData(Iusuario $usuario, $data = array()){

        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

        switch($context){
            case Ilistable::DATA_CONTEXT_DESCARGABLES:
                return array(
                "img" => array( "normal" => $this->getIcon()    ),
                "checkbox" => false
                );
            break;
            default:
                return false;
            break;
        }
        return false;
    }


    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
        //$extraData = $extraData ?: array();
        //$extraData[Ilistable::DATA_REFERENCE] = $this;

        $templ = Plantilla::singleton();
        $elemento = $this->getElement();
        $modulo = $elemento->getModuleName();
        $documento = $this->obtenerDocumento();
        $attr = $this->obtenerDocumentoAtributo();
        $alias = $attr->getUserVisibleName();

        $extraData = $extraData ?: array();
        $data[Ilistable::DATA_REFERENCE] = $this;
        $inlineArray = array();
        $context = isset($extraData[Ilistable::DATA_CONTEXT]) ? $extraData[Ilistable::DATA_CONTEXT] : false;


        switch($context){
            case Ilistable::DATA_CONTEXT_DESCARGABLES:

                $info = array($documento->getUID() => array());

                $info[$documento->getUID()]["nombre"] = array(
                    "title" => $alias,
                    "innerHTML" => string_truncate($alias, 80)
                );

                $info["className"] = $this->getLineClass(false, $usuario);
                return $info;
            break;

            default:
                if ($accion = $usuario->accesoAccionConcreta($modulo."_documento", 10)) { // accion ver informacion
                    $href = $accion["href"] . get_concat_char($accion["href"]) . "m=$modulo&o={$elemento->getUID()}&poid={$documento->getUID()}&req={$this->getUID()}";
                } else {
                    $href = null;
                }

                $info = array($documento->getUID() => array());


                $len = isset($extraData[Ilistable::DATA_COMEFROM]) && $extraData[Ilistable::DATA_COMEFROM] == "home" ? 50 : 200;
                $info[$documento->getUID()]["nombre"] = array(
                    "class" => "link box-it",
                    "title" => $alias,
                    "innerHTML" => string_truncate($alias, $len),
                    "href" => $href
                );

                /*$info[$documento->getUID()]["nombre"]["icon"] = array(
                    "src" => RESOURCES_DOMAIN ."/img/famfam/information.png",
                    "title" => $templ('mas_informacion_documento')
                );*/

                $info["className"] = $this->getLineClass(false, $usuario);
                return $info;
            break;
        }



    }

    public function getLineClass($parent, $usuario, $data = NULL){
        $class = array('color');
        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

        switch($context){
            case Ilistable::DATA_CONTEXT_DESCARGABLES:
                return $class;
            break;
            default:
                if( $anexo = $this->getAnexo() ){
                    if( $anexo->getStatus() === documento::ESTADO_VALIDADO ){
                        $class[] = "green";
                    }
                }

                if( !in_array('green', $class) ) $class[] = "red";

                return implode(" ", $class);
            break;
        }
    }

    public function getElement(){
        $info = parent::getInfo(false);
        if (!$info) throw new Exception("No data found for solicituddocumento {$this->getUID()}");
        $destino = util::getModuleName($info["uid_modulo_destino"]);
        return new $destino($info["uid_elemento_destino"]);
    }

    public function obtenerDocumento(){
        $tipodocumento = $this->obtenerTipoDocumento();
        return new documento( $tipodocumento->getUID(), $this->getElement() );
    }

    public function obtenerTipoDocumento(){
        if( $uid = $this->obtenerDocumentoAtributo()->getDocumentsId() ){
            return new tipodocumento($uid);
        }
    }

    public function obtenerAgrupadorReferencia(){
        if( $uid = $this->obtenerDato("uid_agrupador") ){
            return new agrupador($uid);
        }
        return false;
    }


    public function obtenerIdEmpresaReferencia(){
        return $this->obtenerDato("uid_empresa_referencia");
    }

    public function obtenerEmpresaReferencia(){
        if( $uid = $this->obtenerIdEmpresaReferencia() ){
            if( is_numeric($uid) ){
                return new empresa($uid);
            } else {
                $list = new ArrayIntList(explode(",", $uid));
                return $list->toObjectList("empresa");
                // return new empresa($uid);
            }
        }
        return false;
    }

    public function getClientCompany () {
        return $this->obtenerDocumentoAtributo()->getCompany();
    }

    public function obtenerCliente(){
        return $this->getClientCompany();
    }

    public function obtenerDocumentoAtributo(){
        $cacheString = __CLASS__."-".__FUNCTION__."-{$this->getUID()}";
        if (($data = $this->cache->getData($cacheString)) !== null) return documento_atributo::factory($data);

        $result = false;
        $uid = (int) $this->obtenerDato("uid_documento_atributo");
        if ($uid) {
            $result = new documento_atributo($uid);
        }

        $this->cache->set($cacheString, "{$result}", 60);
        return $result;
    }

    /**
     * Get the requirements for this request
     * @return documento_atributo The requirements
     */
    public function getRequirement()
    {
        return $this->obtenerDocumentoAtributo();
    }

    /**
     * Validate a given format with the formats accepted in the requirements of the request
     * @param  string  $format The mime type
     * @return boolean         Return true if the format is acepted for this request
     */
    public function isValidMimeType($format)
    {
        return $this->getRequirement()->isValidMimeType($format);
    }

    public function isCertification(){
        $atribute = $this->obtenerDocumentoAtributo();

        return (bool)$atribute->obtenerDato("certificacion");
    }

    public function getIcon($mode=false){
        switch($mode){
            default:
                return RESOURCES_DOMAIN . "/img/famfam/page_white_acrobat.png";
            break;
        }
    }

    public static function getSearchData(Iusuario $usuario, $papelera = false, $all = false){
        $searchData = array();

        $condicion = $usuario->obtenerCondicionDocumentos();


        $limit = array();
        $limit[] = "replica = 0";
        $limit[] = "descargar = 0";
        $limit[] = "papelera = 0";
        if( is_bool($papelera) ) $limit[] = "activo = ". ($papelera ? 0 : 1);



        if( $usuario instanceof usuario && $usuario->isViewFilterByLabel() ){
            $etiquetas = $usuario->obtenerEtiquetas();
            $uids = count($etiquetas) ? $etiquetas->toComaList() : 0;

            if( count($etiquetas) && is_traversable($etiquetas) ){
                $limit[] = " uid_documento_atributo IN (
                    SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta WHERE uid_etiqueta IN ($uids)
                )";
            } else {
                $limit[] = " uid_documento_atributo NOT IN (
                    SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta WHERE uid_documento_atributo = da.uid_documento_atributo
                )";
            }

        }

        $limit = implode(" AND ", $limit);

        $modulos = array(1 => "empresa", 8 => "empleado", 14 => "maquina");
        foreach($modulos as $uid => $modulo){
            if (!$usuario->accesoModulo("{$modulo}_documento")) continue;

            if ($usuario instanceof usuario && !$usuario->esValidador()) {
                $searchDataModule = $modulo::getSearchData($usuario, $papelera, $all);

                if (is_array($searchDataModule)) {
                    $searchDataModule = reset($searchDataModule);
                    $limitItems = str_replace("uid_$modulo IN", "uid_elemento_destino IN", $searchDataModule["limit"]);
                    $limitItems = str_replace(" = uid_$modulo", " = uid_elemento_destino", $limitItems);
                    $limitModule = $limit." AND ".TABLE_DOCUMENTOS_ELEMENTOS.".uid_modulo_destino = $uid AND ".$limitItems;
                }

            } else {
                $limitModule = $limit;
            }

            if ($all !== true) $limitModule .= $condicion;


            $data = array(
                "table" => TABLE_DOCUMENTOS_ELEMENTOS ." INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." da USING(uid_documento_atributo, uid_modulo_destino)",
                "name" => $modulo,
                "type" => "solicituddocumento",
                "fields" => array("alias"),
                "limit" => $limitModule,
                "accept" => array(
                    "list" => true,
                    "tipo" => "anexo-$modulo",
                    "docs" => true,
                    "attr" => true,
                    "rand" => "true"
                ),
                "required" => array(
                    "tipo" => "anexo-$modulo"
                )
            );

            $data['accept']['has'] = function($data, $filter, $param, $query){
                $value = db::scape(reset($filter));
                $type = $data['name'];

                $SQL = "uid_documento_atributo IN (
                    SELECT uid_documento_atributo
                    FROM ". PREFIJO_ANEXOS ."{$type} anexo
                    INNER JOIN ". TABLE_DOCUMENTO ."_words
                    USING (fileId)
                    WHERE anexo.uid_{$type} = uid_elemento_destino
                    AND anexo.uid_documento_atributo = documento_elemento.uid_documento_atributo
                    AND anexo.uid_agrupador = documento_elemento.uid_agrupador
                    AND anexo.uid_empresa_referencia = documento_elemento.uid_empresa_referencia
                    AND doc_words LIKE '%$value%'
                )";


                return $SQL;
            };

            $data['accept']['req'] = function($data, $filter, $param, $query){
                $type = $data['name'];
                $m = util::getModuleId($type);
                $value = reset($filter);

                if (is_numeric($value)) {
                    $SQL = " (uid_documento_elemento = {$value}) ";
                }

                return $SQL;
            };

            $data['accept']['obligatorio'] = function($data, $filter, $param, $query){
                $value = reset($filter) ? '1' : '0';
                $SQL = ' ( obligatorio = '. $value .' ) ';

                return $SQL;
            };

            $data['accept']['documento'] = function($data, $filter, $param, $query){
                $type = $data['name'];
                $value = reset($filter);
                $SQL = false;

                if( is_numeric($value) ){
                    $SQL = " ( uid_documento = $value ) ";
                }

                return $SQL;
            };

            $data['accept']['attr'] = function($data, $filter, $param, $query){
                $type = $data['name'];
                $value = reset($filter);
                $SQL = false;

                if( is_numeric($value) ){
                    $SQL = " ( uid_documento_atributo = $value ) ";
                }

                return $SQL;
            };

            // Callback to filter
            $data['accept']['empresa'] = function($data, $filter, $param, $query){
                $type = $data['name'];
                $modulo = util::getModuleId($type);
                $value = reset($filter);
                $table = constant("TABLE_" . strtoupper($type));

                if( !is_numeric($value) ){
                    $value = db::scape($value);
                    $value = "( SELECT uid_empresa FROM ". TABLE_EMPRESA ." WHERE ( nombre LIKE '%{$value}%' OR CIF LIKE '{$value}' ) )";
                }

                switch($type){
                    case 'empresa':
                        $SQL = "( ". TABLE_DOCUMENTO ."_elemento.uid_elemento_destino IN ({$value}) AND uid_modulo_destino = {$modulo} )";
                    break;
                    default:
                        $SQL = "( ( ". TABLE_DOCUMENTO ."_elemento.uid_elemento_destino IN (
                                SELECT uid_$type
                                FROM {$table}_empresa item
                                WHERE item.uid_empresa IN ({$value})
                                AND papelera = 0
                            ) AND uid_modulo_destino = {$modulo} )
                        )";
                    break;
                }

                return $SQL;
            };

            $data['accept']['empleado'] = function($data, $filter, $param, $query){
                $value = reset($filter);
                $SQL = false;

                if (is_numeric($value)) {
                    $SQL = " ( uid_modulo_destino = 8 AND uid_elemento_destino IN (". db::scape($value) .") ) ";
                } elseif (is_string($value)) {
                    $SQL = " ( uid_modulo_destino = 8 AND uid_elemento_destino IN (
                        SELECT uid_empleado FROM ". TABLE_EMPLEADO ." WHERE concat(nombre,' ',apellidos) LIKE '%". db::scape($value) ."%'
                    ) ) ";
                }

                return $SQL;
            };

            $data['accept']['maquina'] = function($data, $filter, $param, $query){
                $value = reset($filter);
                $SQL = false;

                if( is_numeric($value)) {
                    $SQL = " ( uid_modulo_destino = 14 AND uid_elemento_destino IN (
                        SELECT uid_maquina FROM ". TABLE_MAQUINA ." WHERE uid_maquina =". db::scape($value) ."
                    ) ) ";
                } elseif (is_string($value)) {
                    $SQL = " ( uid_modulo_destino = 14 AND uid_elemento_destino IN (
                        SELECT uid_maquina FROM ". TABLE_MAQUINA ." WHERE nombre LIKE '%". db::scape($value) ."%'
                    ) ) ";
                }
                return $SQL;
            };

            $data['accept']['origen'] = function($data, $filter, $param, $query){
                $value = reset($filter);
                $SQL = false;

                if( is_numeric($value) ){
                    $SQL = " ( uid_documento_atributo IN (SELECT uid_documento_atributo FROM
                      ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_elemento_origen IN ({$value}))) ";
                }else{
                    $SQL = " ( uid_documento_atributo IN (SELECT uid_documento_atributo FROM
                      ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_modulo_origen = ". util::getModuleId($value) .")) ";
                }

                return $SQL;
            };

            $data['accept']['until'] = function($data, $filter, $param, $query){
                $value = reset($filter);
                $SQL = false;

                $tipo = $query['tipo'];
                $tipo = explode("-", $tipo[0]);
                $tipo = $tipo[1];

                if( is_string($value) ){
                    $SQL = "uid_documento_atributo  IN (
                        SELECT uid_documento_atributo
                        FROM ". PREFIJO_ANEXOS ."$tipo anexo
                        WHERE anexo.uid_$tipo = uid_elemento_destino
                        AND anexo.uid_documento_atributo = documento_elemento.uid_documento_atributo
                        AND anexo.uid_agrupador = documento_elemento.uid_agrupador
                        AND anexo.uid_empresa_referencia = documento_elemento.uid_empresa_referencia
                        AND (
                            DATEDIFF( FROM_UNIXTIME(fecha_expiracion), NOW() ) <= $value
                            AND DATEDIFF( FROM_UNIXTIME(fecha_expiracion), NOW() ) >= 0

                        )
                    )";
                }

                return $SQL;
            };

            $data['accept']['from'] = function($data, $filter, $param, $query){
                $value = reset($filter);
                $SQL = false;

                $usuario = $data['usuario'];
                $empresa = $usuario->getCompany();
                $tipo = $query['tipo'];
                $tipo = explode("-", $tipo[0]);
                $tipo = $tipo[1];

                if (is_string($value) && strpos($value, 'contacto') === 0){
                    $uidContacto = (int) substr($value, 9);
                    $contacto = new contactoempresa ($uidContacto);
                    $agrupadoresAsignados = $contacto->obtenerAgrupadores();
                    $intListAgrupadores = count($agrupadoresAsignados) ? $agrupadoresAsignados->toComaList() : false;

                    if ($intListAgrupadores) {
                        switch ($tipo) {
                            case 'empresa':
                                $SQL = " uid_elemento_destino IN (
                                    SELECT uid_elemento FROM " .TABLE_AGRUPADOR. "_elemento WHERE
                                    uid_modulo = ". util::getModuleId("empresa") ." AND uid_elemento = ". $empresa->getUID() . " AND uid_agrupador IN ($intListAgrupadores)
                                ) ";
                                break;

                            case 'empleado':
                                $SQL = " uid_elemento_destino IN (
                                    SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa INNER JOIN " .TABLE_AGRUPADOR. "_elemento ON uid_empleado = uid_elemento WHERE
                                    uid_modulo = ". util::getModuleId("empleado") ." AND uid_empresa = {$empresa->getUID()} AND uid_agrupador IN ($intListAgrupadores)
                                ) ";
                                break;

                            case 'maquina':
                                $SQL = " AND uid_elemento_destino IN (
                                    SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa INNER JOIN " .TABLE_AGRUPADOR. "_elemento ON uid_maquina = uid_elemento WHERE
                                    uid_modulo = ". util::getModuleId("maquina") ." AND uid_empresa = {$empresa->getUID()} AND uid_agrupador IN ($intListAgrupadores)
                                ) ";
                                break;

                            default:
                                # code...
                                break;
                        }
                    }
                }

                return $SQL;
            };

            $data['accept']['asignado'] = function($data, $filter, $param, $query){

                $SQL = false;
                $modulo = util::getModuleId($data['name']);
                $value = reset($filter);

                if (is_numeric($value)){
                    $SQL = " ( uid_documento_elemento IN (SELECT uid_documento_elemento FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." WHERE
                     uid_elemento_destino IN (
                        SELECT uid_elemento FROM " .TABLE_AGRUPADOR. "_elemento
                         WHERE uid_agrupador = $value AND uid_modulo = $modulo
                        )
                    ))";

                }

                return $SQL;
            };

            $data['accept']['anexo'] = function($data, $filter, $param, $query){

                $tipo = $query['tipo'];
                $tipo = explode("-", $tipo[0]);
                $tipo = $tipo[1];

                $SQL = false;
                $value = reset($filter);
                if (is_numeric($value)){
                    $SQL = " ( uid_documento_elemento IN (

                                SELECT uid_documento_elemento FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." WHERE

                                     uid_elemento_destino IN (
                                        SELECT uid_$tipo
                                            FROM ". PREFIJO_ANEXOS ."$tipo anexo
                                            WHERE anexo.uid_anexo_$tipo = $value
                                    )

                                    AND

                                    uid_agrupador IN (
                                        SELECT uid_agrupador
                                            FROM ". PREFIJO_ANEXOS ."$tipo anexo
                                            WHERE anexo.uid_anexo_$tipo = $value
                                    )

                                    AND

                                    uid_empresa_referencia IN (
                                        SELECT uid_empresa_referencia
                                            FROM ". PREFIJO_ANEXOS ."$tipo anexo
                                            WHERE anexo.uid_anexo_$tipo = $value
                                    )

                            )

                            AND

                            uid_documento_atributo  IN (
                                SELECT uid_documento_atributo
                                    FROM ". PREFIJO_ANEXOS ."$tipo anexo
                                    WHERE anexo.uid_anexo_$tipo = $value
                            ))";

                }

                return $SQL;
            };




            $data['accept']['docs'] = function($data, $filters, $param, $query){
                $tipo = explode("-", $query['tipo'][0]);
                $class = $tipo[1]; // anexo-empresa for example, we want only "empresa"

                $userCondition = (isset($data['usuario']) && $usuario = $data['usuario']) ? $usuario->obtenerCondicionDocumentosView($class) : '';
                $SQLfilters = array();

                foreach ($filters as $filter) {
                    $statusSQL = "estado = $filter AND estado IS NOT NULL";

                    switch ($filter) {
                        case documento::ESTADO_PENDIENTE:
                            $statusSQL = "estado IS NULL";
                            break;

                        case documento::ESTADO_ANEXADO:
                            $statusSQL =  "(estado = $filter OR (estado = 2 AND reverse_status = 1))";
                            break;
                    }

                    $sql = " documento_elemento.uid_documento_elemento IN (
                        SELECT uid_solicituddocumento FROM ". TABLE_DOCUMENTO ."_{$class}_estado as view
                        WHERE 1
                        AND descargar = 0
                        AND {$statusSQL}

                    )";

                    $SQLfilters[] = $sql;
                }

                $SQL = '(' . implode(' AND ', $SQLfilters) . ')';
                //dump($SQL);exit;
                return $SQL;
            };

            $searchData[] = $data;
        }

        return $searchData;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $fieldList = new FieldList();
        $fieldList["papelera"] = new FormField(array());
        $fieldList["uid_empresa_referencia"] = new FormField(array());
        return $fieldList;
    }

    public function copyTo($uidEmpresa){

        $uidEmpresa = $uidEmpresa instanceof empresa ? $uidEmpresa->getUID() : $uidEmpresa;
        $info = parent::getInfo(false);
        $sql = "INSERT IGNORE INTO {$this->tabla} (uid_documento_atributo,uid_elemento_destino,
            uid_agrupador,uid_empresa_referencia,uid_modulo_destino,papelera) VALUES (
            ".$info['uid_documento_atributo'].",".$info['uid_elemento_destino'].",
            ".$info['uid_agrupador'].",'".$uidEmpresa."',
            ".$info['uid_modulo_destino'].",".$info['papelera'].")";

        if (!$this->db->query($sql)){
            error_log("Error poniendo la referencia por empresa con la SQL :".$sql. " y el error : ".$this->db->lasterror());
            return false;
        }
        return true;
    }

    public function obtenerEntradasRepetidas(){

        $info = parent::getInfo(false);

        $sql = "SELECT uid_documento_elemento FROM {$this->tabla} WHERE uid_documento_atributo = ".$info['uid_documento_atributo']."
         AND uid_elemento_destino = ".$info['uid_elemento_destino']." AND uid_modulo_destino = ".$info['uid_modulo_destino'];

        $coleccion = $this->db->query($sql, "*", 0, "solicituddocumento");
        return new ArrayObjectList($coleccion);
    }

    public function exitsForCompany(empresa $empresaRef = NULL){

        $UIDempresaRef = ($empresaRef instanceof empresa) ? $empresaRef->getUID() : 0;
        $info = parent::getInfo(false);

        $sql = "SELECT count(uid_documento_elemento) FROM {$this->tabla} WHERE uid_documento_atributo = ".$info['uid_documento_atributo']."
         AND uid_elemento_destino = ".$info['uid_elemento_destino']." AND uid_modulo_destino = ".$info['uid_modulo_destino'] ."
         AND uid_agrupador = ".$info['uid_agrupador']." AND uid_empresa_referencia = {$UIDempresaRef}
         AND uid_documento_elemento != {$this->getUID()}";

        return (bool)$this->db->query($sql, 0, 0);

    }

    public function canReattach () {
        $attr = $this->obtenerDocumentoAtributo();
        return $attr->hasAttachMultiple();
    }

    public function getExampleRequest()
    {
        $attribute = $this->obtenerDocumentoAtributo();
        $atributeExample = $attribute->obtenerDocumentoEjemplo();

        if (false === $atributeExample) {
            return false;
        }

        $sql = "SELECT de1.uid_documento_elemento FROM {$this->tabla} de1
            INNER JOIN {$this->tabla} de2 ON
            de1.uid_elemento_destino = de2.uid_elemento_destino
            AND de1.uid_modulo_destino = de2.uid_modulo_destino
            AND de1.uid_agrupador = de2.uid_agrupador
            AND de1.uid_empresa_referencia = de2.uid_empresa_referencia
            WHERE de1.uid_documento_atributo = {$atributeExample->getUID()}
            AND de2.uid_documento_elemento = {$this->getUID()}";

        $requests = $this->db->query($sql, "*", 0, 'solicituddocumento');

        if ($requests) {
            return reset($requests);
        }

        return false;
    }

    public function obtenerDuraciones($list = false, $refDate = false)
    {
        $attr = $this->obtenerDocumentoAtributo();

        return $attr->obtenerDuraciones($list, $refDate);
    }
}
