<?php

class anexo extends elemento implements Ielemento
{
    private $module;

    /** $item = false por compatibilidad con la interface **/
    public function __construct($uid, $item = false){

        if ($item instanceof solicitable) {
            $this->module = strtolower($item->getType());
            $this->tipo = "anexo_{$this->module}";
            $this->tabla = constant("PREFIJO_ANEXOS") . $this->module;
        } elseif (is_string($item)) {
            $this->module = $item;
            $this->tipo = "anexo_". $item;
            $this->tabla = constant("PREFIJO_ANEXOS") . $item;
        }

        $this->instance($uid);
    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Company\Attachment\Attachment | Employee\Attachment\Attachment | Machine\Attachment\Attachment
     */
    public function asDomainEntity()
    {
        switch ($this->module) {
            case 'empresa':
                $attachment = $this->app['company_attachment.repository']->factory($this->getInfo());

                break;
            case 'empleado':
                $attachment = $this->app['employee_attachment.repository']->factory($this->getInfo());

                break;
            case 'maquina':
                $attachment = $this->app['machine_attachment.repository']->factory($this->getInfo());

                break;
        }

        return $attachment;
    }

    public static function getRouteName () {
        return 'attachment';
    }

    /***
       * return String SQL Condition to calculate if a document is near expire
       *
       *
       *
       */
    public static function getNotificationSQL ($factor = 0.1, $max = 15, $min = 5) {
        $daySeconds     = 60 * 60 * 24;
        $duration       = "(fecha_expiracion - fecha_emision)";
        $prevDays       = "({$duration} * {$factor}) / {$daySeconds}";
        $max            = "IF ({$prevDays} > {$max}, {$max}, {$prevDays})";
        $prevDays       = "IF ({$prevDays} < {$min}, {$min}, {$max})";

        $diff           = "DATEDIFF(FROM_UNIXTIME(fecha_expiracion), NOW())";

        $conditions     = [];
        $conditions[]   = "fecha_expiracion != 0";
        $conditions[]   = "{$diff} <= {$prevDays}";

        // join all the conditions
        $SQL = implode(" AND ", $conditions);

        return $SQL;
    }

    /***
       * return bool whether this attachment is about to expire or not
       *
       *
       *
       */
    public function isNearExpire () {
        $where = self::getNotificationSQL();

        $key = "uid_{$this->tipo}";
        $SQL = "SELECT IF ({$key}, 1, 0) about_expire FROM {$this->tabla} WHERE {$key} = {$this->getUID()} AND {$where}";

        return (bool) $this->db->query($SQL, 0 , 0);
    }

    public function getDate ($format = 'd/m/Y') {
        if ($timestamp = $this->obtenerDato('fecha_emision')) {
            return date($format, $timestamp);
        }
    }

    public function getDuration () {
        return $this->obtenerDato("duration");
    }

    public function isFullValid () {
        return $this->getStatus() === documento::ESTADO_VALIDADO && $this->obtenerDato("full_valid");
    }

    public function getUpdateDate ($offset=0) {
        $key = "uid_{$this->tipo}";
        $SQL = "SELECT UNIX_TIMESTAMP(fecha_actualizacion) FROM {$this->tabla} WHERE {$key} = {$this->getUID()}";

        if ($timestamp = $this->db->query($SQL, 0, 0)) {
            // adjuts timezone offset
            $timestamp = $timestamp - (3600 * $offset);

            return (int) $timestamp;
        }

        return 0;
    }

    public function getTimestamp($offset=0){
        $timestamp = $this->obtenerDato("fecha_anexion");
        $timestamp = $timestamp - (3600 * $offset); // adjuts timezone offset
        return $timestamp;
    }

    public function getExpirationTimestamp($userTimeZone = null)
    {
        if (false === $userTimeZone instanceof DateTimeZone) {
            $userTimeZone = new DateTimeZone('Europe/Madrid');
        }
        $timestamp = (int) $this->obtenerDato("fecha_expiracion");
        if (0 === $timestamp) {
            return $timestamp;
        }

        $expirationDateTime = new DateTime();
        $expirationDateTime->setTimestamp($timestamp);
        $offset = $userTimeZone->getOffset($expirationDateTime);
        $timestamp += $offset; // adjuts timezone offset

        return $timestamp;
    }

    public function getExpirationRenovation(){
        if ($this->isRenovation() && $delayedStatus = $this->getDelayedStatus()) {
            return $delayedStatus->getReverseDate();
        }
        return false;
    }

    public function getRealTimestamp($offset=0){
        $timestamp = $this->obtenerDato("fecha_emision");
        $timestamp = $timestamp - (3600 * $offset); // adjuts timezone offset
        return $timestamp;
    }

    public function getFileHash (){
        return $this->obtenerDato("hash");
    }

    public function getFullPath(){
        return DIR_FILES . $this->obtenerDato("archivo");
    }

    public function getModuleId($tipo = false) {
        $aux = explode(".", $this->tabla);
        return self::obtenerIdModulo(end($aux));
    }


    public function getHexColor(){
        $hash = $this->obtenerDato("hash");
        if( !trim($hash) ) return '';
        return color_assoc($hash);
    }

    public function revisar(Iusuario $usuario){
        $info = $this->getInfo();
        $elemento = $this->getElement();
        $tipo = $elemento->getType();
        $tabla = PREFIJO_ANEXOS_ATRIBUTOS . $tipo;

        $sql = "INSERT INTO {$tabla} (uid_documento_atributo, uid_agrupador, uid_empresa_referencia, fecha_anexion, uid_{$tipo}, uid_usuario)
        VALUES ({$info["uid_documento_atributo"]}, {$info["uid_agrupador"]}, '{$info["uid_empresa_referencia"]}', {$info["fecha_anexion"]}, {$elemento->getUID()}, {$usuario->getUID()})";

        if( $this->db->query($sql) ){
            return true;
        }

        return false;
    }

    public function obtenerRevisiones(Iusuario $usuario = NULL){
        $info = $this->getInfo();

        if( !$elemento = $this->getElement() ) return false;
        $tipo = $elemento->getType();
        $tabla = PREFIJO_ANEXOS_ATRIBUTOS . $tipo;

        $sql = "SELECT uid_anexo_atributo_{$tipo} FROM $tabla WHERE 1
        AND uid_documento_atributo = {$info["uid_documento_atributo"]}
        AND uid_agrupador = {$info["uid_agrupador"]}
        AND uid_empresa_referencia = '{$info["uid_empresa_referencia"]}'
        AND fecha_anexion = {$info["fecha_anexion"]}
        AND uid_{$tipo} = {$elemento->getUID()}
        ";

        if( $usuario instanceof usuario ){
            $sql .= " AND uid_usuario = {$usuario->getUID()}";
        }

        $revisiones = $this->db->query($sql, "*", 0);

        array_walk($revisiones, function(&$uid, $i, $item){
            $uid = new revision($uid, $item);
        }, $elemento);

        return new ArrayObjectList($revisiones);
    }


    public function yaRevisado(Iusuario $usuario = NULL){
        $info = $this->getInfo();

        if ($elemento = $this->getElement()) {
            $tipo = $elemento->getType();
            $tabla = PREFIJO_ANEXOS_ATRIBUTOS . $tipo;

            $sql = "SELECT uid_usuario FROM $tabla WHERE 1
            AND uid_documento_atributo = {$info["uid_documento_atributo"]}
            AND uid_agrupador = {$info["uid_agrupador"]}
            AND uid_empresa_referencia = '{$info["uid_empresa_referencia"]}'
            AND fecha_anexion = {$info["fecha_anexion"]}
            AND uid_{$tipo} = {$elemento->getUID()}
            ";

            if( $usuario instanceof usuario ){
                $sql .= " AND uid_usuario = {$usuario->getUID()}";
            }

            $sql .= " LIMIT 1";

            $uid = $this->db->query($sql, 0, 0);
            return (bool) $uid;
        }

        return false;
    }

    public function getPreviousStatus () {
        $current = $this->getStatus();

        $module = $this->getModuleId();
        $SQL = "SELECT valor FROM ". TABLE_LOGUI ."
            WHERE uid_modulo = {$module}
            AND uid_elemento = {$this->getUID()}
            AND texto = '". logui::ACTION_STATUS_CHANGE ."'
            AND valor != {$current}
            ORDER BY uid_logui DESC
            LIMIT 1
        ";

        $previous = $this->db->query($SQL, 0, 0);

        return $previous;
    }

    public function isValidated () {
        return $this->getStatus() == documento::ESTADO_VALIDADO;
    }

    public function getStatus($string=false){
        $estado = $this->obtenerDato("estado");
        if( $string ) return documento::status2String($estado);
        return (int) $estado;
    }

    public function getExtension() {
        $data = $this->getInfo();
        return archivo::getExtension($data['archivo']);
    }

    /***
       *    Return image information as structured data
       *
       *
       *
       */
    public function getImageInfo () {
        if ($argument = $this->getValidationArgument()) {
            $imageInfo = $argument->getImageInfo($this);
            if ($imageInfo != false) return $imageInfo;
        }

        if ($delayedStatus = $this->getDelayedStatus()) {
            return $delayedStatus->getImageInfo($this);
        }

        return false;
    }

    /***
       *    Return image information as structured data
       *
       *
       *
       */
    public function getIcon ($size = false) {
        if ($argument = $this->getValidationArgument()) {
            $icon = $argument->getIcon($this);
            if ($icon != false) return $icon;
        }

        if ($delayedStatus = $this->getDelayedStatus()) {
            return $delayedStatus->getIcon($this);
        }

        return false;
    }


    /***
       * Returns the file as binary
       *
       *
       */
    public function getAsBinary ()
    {
        $path = $this->getFullPath();
        return archivo::leer($path);
    }


    public function getAsPDF(){
        $ext = $this->getExtension();

        if ($ext == "pdf") {
            return $this->getAsBinary();
        }

        $path = $this->getFullPath();

        $list = ["doc", "docx", "png", "jpg", "jpeg", "tiff"];
        if (in_array($ext, $list)) {
            $pdf = archivo::file2pdf($path, $ext);
            return archivo::leer($pdf);
        }

        return false;
    }


    public function getAsImage(){
        $ext    = $this->getExtension();
        $list   = ["jpg", "jpeg", "gif", "png"];

        if (in_array($ext, $list)) {
            return $this->getAsBinary();
        }

        return false;
    }


    public function canPreview () {
        $path   = $this->getFullPath();
        $ext    = $this->getExtension();
        if (archivo::is_readable($path) === false) {
            return false;
        }


        $whitelist = array("pdf", "doc", "docx");
        if (in_array($ext, $whitelist)) {
            $app = \Dokify\Application::getInstance();
            return $app['browser_detect']->compatibleDocumentPreview() ? 'pdf' : false;
        }

        $whitelist = array("tiff", "gif", "png", "jpg", "jpeg");
        if (in_array($ext, $whitelist)) {
            return $ext;
        }

        return false;
    }


    public function getRequestString($largo = false){
        $tpl = Plantilla::singleton();
        $atributo = $this->obtenerDocumentoAtributo();
        if (!$atributo) {
            return '';
        }

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
            if ($largo) $pieces[] = $atributo->getCompany()->getUserVisibleName();
            $pieces[] = $elemento->getNombreTipo();
            $pieces[] = $elemento->getUserVisibleName();
        } else {
            $pieces[] = $elemento->getUserVisibleName();
            $pieces[] = $tpl("empresa");
        }



        return implode(" &laquo; ", $pieces);
    }

    public function sendByEmail($user, $reqtype, $emails, $attach = false, $comment = false)
    {
        $email = new email($emails);
        $email->establecerAsunto("Envio de documento");

        $mailTemplate = new Plantilla();
        $mailTemplate->assign("link", $reqtype->obtenerUrlPublica($user));
        $mailTemplate->assign("usuario", $user);
        $mailTemplate->assign("documento", $reqtype);
        $mailTemplate->assign("comentario", $comment);
        $mailTemplate->assign("adjunto", $attach);

        if ($attach) {
            $path = $this->getFullPath();

            $data = array(
                        "path" => $path,
                        "downloadName" => $this->getDownloadName(),
                        "timeUrl" => '7 days'
                );
            try {
                $publicFile = new publicfile($data, $user);
            } catch (Exception $e) {
                return false;
            }
            $mailTemplate->assign("urlPublicFile", CURRENT_DOMAIN."/publicfile.php?token=".$publicFile->getToken());
        }

        $html = $mailTemplate->getHTML("email/enviodocumento.tpl");
        $email->establecerContenido($html);
        set_time_limit(120);
        if ($email->enviar()) {
            return true;
        }

        return false;
    }

    public function getUserVisibleName() {
        $data = $this->getInfo();
        return $data['nombre_original'];
    }

    public function obtenerAgrupadorReferencia(){
        if( $uid = $this->obtenerDato("uid_agrupador") ){
            return new agrupador($uid);
        }
        return false;
    }

    public function obtenerEmpresaReferencia(){
        if( $uid = $this->obtenerDato("uid_empresa_referencia") ){
            if( is_numeric($uid) ){
                return new empresa($uid);
            } else {
                $int = new ArrayIntList( explode(",", $uid) );
                return $int->toObjectList("empresa");
            }
        }
        return false;
    }

    public function obtenerIdEmpresaReferencia(){
        return $this->obtenerDato("uid_empresa_referencia");
    }

    public function obtenerLanguage(){
        return $this->obtenerDato("language");
    }

    public function getUploaderCompany(){

        if( $uid = $this->obtenerDato("uid_empresa_anexo") ){
            if( is_numeric($uid) ){
                return new empresa($uid);
            }
        }
        return false;

    }

    public function getAnexoRenovation(){
        if( $uid = $this->obtenerDato("uid_anexo_renovation") ){
            if( is_numeric($uid) ){
                $explode = explode("_", $this->tabla);
                $modulo = end($explode);
                $tipoAnexo = "anexo_historico_{$modulo}";
                return new $tipoAnexo($uid);
            }
        }
        return false;

    }

    public function isRenovation(){
        $reverseStatusAttach = $this->getReverseStatus() == documento::ESTADO_ANEXADO;
        $isValidated = $this->getStatus() == documento::ESTADO_VALIDADO;
        return $reverseStatusAttach && $isValidated;
    }


    public function obtenerDocumento(){
        $data = $this->getInfo();
        $sql = "SELECT uid_documento FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_documento_atributo = {$data["uid_documento_atributo"]} GROUP BY uid_documento";
        $uid = $this->db->query($sql, 0, 0);
        return new documento($uid);
    }

    public function getElement()
    {
        $tableExploded = explode("_", $this->tabla);
        $modulo = end($tableExploded);

        if ($uid = $this->obtenerDato("uid_{$modulo}")) {
            $item = new $modulo($this->obtenerDato("uid_{$modulo}"));
            if ($item->exists()) {
                return $item;
            }
        }

        return false;
    }

    public function obtenerDocumentoAtributo(){
        $data = $this->getInfo();
        if( $uid = $data["uid_documento_atributo"] ){
            return new documento_atributo($data["uid_documento_atributo"]);
        }
        return false;
    }

    public function getDownloadName(){
        $archivo = $this->obtenerDato("archivo");
        $aux = explode(".", $archivo);

        if ($atributo = $this->obtenerDocumentoAtributo()) {
            return $atributo->getUserVisibleName();
        }

        return false;
    }

    public function getExportData() {
        //$log = log::singleton();
        //$log->info($this->getModuleName(), "exportacion anexo", $this->getUserVisibleName() );
        $dataAnexo = $this->getInfo();


        $docat = $this->obtenerDocumentoAtributo();
        $dataDocAt = $docat->getInfo();

        /*if (!archivo::is_readable(DIR_FILES.$dataAnexo['archivo'])) {
            $log->nivel(5);
            $log->resultado("el fichero del anexo no es legible", true);
            return null;
        }*/


        $elemento = $this->getElement();
        if ($elemento instanceof solicitable) {
            $dataElemento = $elemento->getInfo();

            $data = array();
            $data['archivo'] = DIR_FILES.$dataAnexo['archivo'];
            $data['md5'] = $dataAnexo['hash'];
            $data['nombre'] = $dataDocAt['alias'];
            $data['original'] = $dataAnexo['nombre_original'];
            $data['fecha'] = date('d-m-Y',$dataAnexo['fecha_emision']);
            $data['extension'] = archivo::getExtension($dataAnexo['archivo']);
            $data['carpeta'] = get_class($elemento).'s/';
            $data['carpeta'] .= archivo::cleanFilenameString($elemento->getUserVisibleName(),function($n){return str_ireplace(' ','_',$n);});
            $data['fullname'] = archivo::cleanFilenameString(trim($data['nombre']).'.'.$data['extension'],function($n){return str_ireplace(' ','_',$n);});
            if ($this->esHistorico()) {
                $data['carpeta'] .= '/historico';
                $data['fullname'] = archivo::cleanFilenameString(trim($data['nombre']).'.'.$data['fecha'].'.'.$data['extension'],function($n){return str_ireplace(' ','_',$n);});
            }
            $data['fullpath'] = $data['carpeta'].'/'.$data['fullname'];
            $data['historico'] = $this->esHistorico();
            //$log->nivel(1);
            //$log->resultado('ok',true);
            return $data;
        } else {
            $class = get_class($this);
            error_log("Ya no existe el elemento asociado al {$class} {$this->getUID()}, saltamos");
            return false;
        }
    }

    public function esHistorico() {
        return (bool) stripos($this->tabla,'historico');
    }

    public function download ($return = false) {
        $data = $this->getInfo();

        $archivo = $data["archivo"];
        //$fileOriginalName = $data["nombre_original"];
        //$hash = $data["hash"];
        //$aux = explode(".", $archivo);
        $archivo =  DIR_FILES . $archivo;

        if ($return) return $archivo;
        if (!archivo::descargar($archivo, $this->getDownloadName() . "." . $this->getExtension() )) {
            //die( documento::ERROR_JAVASCRIPT );
        }

        /*
        // Ordenamos descendente para obetener el ultimo en caso de que algun fichero no se haya movido a la tabla de historico
        $sql = "
            SELECT archivo, nombre_original, hash FROM {$this->tabla}
            WHERE uid_documento_atributo = ". $this->getUID() ." AND ( uid_$modulo = 0 OR uid_$modulo = ". $elemento->getUID() ." )
            ORDER BY  uid_". $tableName ."$modulo DESC
            LIMIT 1
        ";
        $uploaded = $this->db->query( $sql, 0, "*" );
        $archivo = $uploaded["archivo"];
        $fileOriginalName = $uploaded["nombre_original"];
        $hash =  $uploaded["hash"];
        */
    }

    public function updateDate($emision, $expiracion = null, $usuario, DelayedStatus $delayedStatus = null, $duration = null)
    {
        $emision = documento::parseDate($emision);

        if (false === is_numeric($emision)) {
            return 'error_fecha_incorrecta';
        }

        $info = $this->getInfo();
        $atributo = $this->obtenerDocumentoAtributo();

        $hasManualExpiration = (bool) $atributo->caducidadManual();

        if (null === $duration) {
            $duration = $this->getDuration();
        }

        if ($hasManualExpiration && null !== $expiracion) {
            $nuevaExpiracion = documento::parseDate($expiracion);
        } elseif ($duration && is_numeric($duration)) {
            $expiracion = true;
            $nuevaExpiracion = $emision + ($duration*60*60*24);
        } else {
            $duraciones = $atributo->obtenerDuraciones(false, $emision);
            $duracion = is_traversable($duraciones) ? reset($duraciones) : $duraciones;

            if ($duracion && is_numeric($duracion)) {
                //Duration is a number of days
                $now = time();
                $limitecaducidad = $now - ($duracion*60*60*24);
                if ($limitecaducidad >= $emision) {
                    return 'error_anexo_caducado';
                }

                $durationDate = $emision + ($duracion*60*60*24);
            } else if ($duracion) {
                list($day, $month, $year) = @explode('/', $duracion);
                if (checkdate($month, $day, $year)) {
                    //If duration is a valid date
                    $durationDate = DateTime::createFromFormat('d/m/Y', $duracion);
                    $durationDate = $durationDate->getTimestamp();
                    if ($durationDate && time() >= $durationDate) {
                        return 'error_anexo_caducado';
                    }
                } else {
                    //Shoud not be here never
                    return 'error_desconocido';
                }
            }

            $expiracion = $expiracion ? documento::parseDate($expiracion) : null;
            $nuevaExpiracion = $expiracion;

            if ($duracion) {
                $nuevaExpiracion = $expiracion ? $expiracion : $durationDate;
            }

            if ($expiracion > 0 && $emision > $expiracion) {
                return 'error_anexo_caducado';
            }
        }

        $data = [
            'fecha_emision' => $emision,
            'fecha_emision_real' => $info['fecha_emision'],
        ];

        if ($delayedStatus instanceof DelayedStatus) {
            if (!$this->addDelayedStatus($delayedStatus)) {
                return 'error_add_delayed_status';
            }
        } elseif (!$this->isRenovation()) {
            $data['estado'] = documento::ESTADO_ANEXADO;
        }

        $fileId = $this->getFileId();
        if (!$fileId instanceof fileId) {
            $fileId = fileId::generateFileId();
            $data['fileId'] = $fileId;
        }

        $userCompany = $usuario->getCompany();
        if ($userCompany->esCorporacion()) {
            $element = $this->getElement();
            $uidEmpresaAnexo = ($element instanceof empresa) ? $element->getUID() : $element->getCompany($usuario)->getUID();
        } else {
            $uidEmpresaAnexo = $userCompany->getUID();
        }

        $data['uid_usuario'] = $usuario->getUID();
        $data['urgente'] = 0;
        $data['uid_empresa_anexo'] = $uidEmpresaAnexo;
        $data['fecha_actualizacion'] = date('Y-m-d H:i:s');

        // Si tiene duración
        if (isset($nuevaExpiracion)) {
            // si la fecha es timestamp
            if (is_numeric($nuevaExpiracion)) {
                $userTimeZone = $usuario->getTimeZone();
                $expirationDateTime = new DateTime();
                $expirationDateTime->setTimestamp($nuevaExpiracion);
                $nuevaExpiracion -= $userTimeZone->getOffset($expirationDateTime);

                $requirementInfo = $atributo->getInfo();
                $gracePeriod = (int) $requirementInfo['grace_period'];
                $nuevaExpiracion += $gracePeriod * 24 * 60 * 60;

                if ($nuevaExpiracion < time()) {
                    return 'error_anexo_caducado';
                }

                if ($nuevaExpiracion < $emision) {
                    return 'error_anexo_caducado';
                }
            }

            $data['fecha_expiracion'] = ($nuevaExpiracion === 'error_fecha_incorrecta') ? 'no_caduca' : $nuevaExpiracion;
        }

        return $this->update($data, false, $usuario);
    }

    public function resetUpdatedDate(usuario $user){
        $data["fecha_emision_real"] = 0;

        return  $this->update($data, false, $user);
    }

    public function isUrgent(){
        return (bool)$this->obtenerDato("is_urgent");
    }

    /**
     * Get the attachment partner company
     * @return false|empresa Returns the partner company if the attachment has to be validated by a partner, false otherwise
     */
    public function getPartner ()
    {
        $atributo = $this->obtenerDocumentoAtributo();

        if (!$atributo) {
            return false;
        }

        $company = $atributo->getCompany();
        $companyStartList = $company->getStartList();

        if (!$company) {
            return false;
        }

        $isCustom = ($atributo->getIsCutom()) ? documento_atributo::TEMPLATE_TYPE_CUSTOM : documento_atributo::TEMPLATE_TYPE_GENERAL;
        $language = $this->obtenerLanguage();

        $uploaderCompany = $this->getUploaderCompany();

        if ($companyStartList->contains($uploaderCompany)) {
            $target = empresaPartner::VALIDATION_TARGET_SELF;
        } else {
            $target = empresaPartner::VALIDATION_TARGET_CONTRACTS;
        }

        return $company->getPartner($language, $isCustom, $target);
    }

    public function getFileId()
    {
        $info = $this->getInfo();
        $fileId = $info["fileId"];

        $prefix = "";
        if (false !== strpos($this->getModuleName(), "historico")) {
            $prefix = "historico_";
        }

        $moduleName = $prefix . @end(explode("_", $this->getModuleName()));

        if ('' !== $fileId) {
            return new fileId($fileId, $moduleName);
        }

        return false;
    }

    public function getCompanyPayment(){
        $info = $this->getInfo();
        $companyId = $info["uid_empresa_payment"];
        if (is_numeric($companyId)){
            return new empresa($companyId);
        }

        return false;
    }

    public function getValidationArgument() {
        $uid = $this->obtenerDato('validation_argument');

        if (is_numeric($uid)){
            return new ValidationArgument($uid);
        }

        return false;
    }

    public function getDelayedStatus() {
        $reverseStatus = $this->obtenerDato('reverse_status');
        $reverseDate = $this->obtenerDato('reverse_date');

        if (is_numeric($reverseStatus) && is_numeric($reverseDate)){
            return new DelayedStatus((int) $reverseStatus, (int)$reverseDate);
        }

        return false;
    }

    public function getReverseDate() {
        if ($delayedStatus = $this->getDelayedStatus()) {
            return $delayedStatus->getReverseDate();
        }
        return false;
    }

    public function getReverseStatus() {
        if ($delayedStatus = $this->getDelayedStatus()) {
            return $delayedStatus->getReverseStatus();
        }
        return false;
    }

    public function addDelayedStatus(DelayedStatus $delayedStatus)
    {
        $table          = $this->tabla;
        $reverseStatus  = $delayedStatus->getReverseStatus();
        $reverseDate    = $delayedStatus->getReverseDate();
        $primaryKey     = "uid_anexo_" . $this->module;
        $uid            = $this->getUID();

        $SQL = "UPDATE {$table}
        SET reverse_status = {$reverseStatus},
        reverse_date = {$reverseDate}
        WHERE {$primaryKey} = {$uid}";

        if (!$this->db->query($SQL)) {
            return $this->db->lastErrorString();
        }

        return true;
    }

    public function addValidationArgument(ValidationArgument $argument) {
        if ($delayedStatus = $argument->getDelayedStatus()) {
            if (!$this->addDelayedStatus($delayedStatus)) {
                return false;
            }
        }
        $sql = "UPDATE {$this->tabla} SET validation_argument = ". $argument->getUID() .", uid_anexo_renovation = NULL
        WHERE uid_anexo_{$this->module} = {$this->getUID()}";
        if (!$this->db->query($sql)) {
            return $this->db->lastErrorString();
        }

        return true;
    }

    public function getValidation()
    {
        $info = $this->getInfo();
        $validationId = $info["uid_validation"];

        if (true === is_numeric($validationId) && 0 !== (int) $validationId) {
            return new validation($validationId);
        }

        return false;
    }

    public function getValidationStatus(){

        $validationStatus = db::get("SELECT uid_validation_status FROM " .TABLE_VALIDATION_STATUS. " WHERE uid_anexo = {$this->getUID()}", "*", 0, "validationStatus");
        if ($validationStatus){
            return reset($validationStatus);
        }

        return false;
    }

    public function makeUrgent(){
        if ($this->canApplyUrgent()) {
            return $this->update(array("is_urgent" => 1));
        }
        return false;
    }


    public function getUploaderUser(){
        $info = $this->getInfo();
        $usuarioId = $info["uid_usuario"];
        if (is_numeric($usuarioId) && $usuarioId != 0) {
            return new usuario($usuarioId);
        }

        return false;
    }


    /**
     * Check if the attachment has a partner and it could apply for urgent validation
     * @return bool Returns true if the attachment could request urgent validation, false other wise
     */
    public function canApplyUrgent()
    {
        $partner = $this->getPartner();

        if ($partner instanceof empresa) {
            return true;
        }

        return false;
    }


    public function getSolicitud(){
        $atributo = $this->obtenerDocumentoAtributo();
        if (!$atributo) {
            return false;
        }

        $sql = " SELECT uid_documento_elemento FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." WHERE uid_documento_atributo = {$atributo->getUID()}
            AND uid_empresa_referencia = '{$this->obtenerDato("uid_empresa_referencia")}'
            AND uid_agrupador = {$this->obtenerDato("uid_agrupador")}
            ";

        if ($element = $this->getElement()) {
            $sql .= " AND uid_elemento_destino = {$element->getUID()} ";
        }

        $agrupador = $this->obtenerAgrupadorReferencia();
        if ($agrupador instanceof agrupador) {
            $sql .= " AND uid_agrupador = {$agrupador->getUID()} ";
        }

        $sql .= "limit 1";
        $result = $this->db->query($sql, "*", 0, "solicituddocumento");
        if (!count($result)) return false;
        return $result[0];
    }

    public static function getTotalCount(){
        $modulos = array("empresa", "empleado", "maquina");
        $counts = array();
        foreach($modulos as $modulo){
            $counts[] = "SELECT count(*) FROM ". PREFIJO_ANEXOS . $modulo;
            $counts[] = "SELECT count(*) FROM ". PREFIJO_ANEXOS_HISTORICO . $modulo;
        }

        $SQL = implode(" UNION ", $counts);
        $data = @db::get($SQL, "*", 0);

        $num = array_reduce($data, function($a, $b){
            return $a + $b;
        });

        return $num;
    }


    public static function releasingAttachmentsFromValidator(){

        $db         = db::singleton();
        $pwd        = isset($_SERVER["PWD"]);
        $modules    = solicitable::getModules();
        $total      = 0;

        foreach ($modules as $module) {
            $table      = PREFIJO_ANEXOS . "{$module}";
            $primaryKey = "uid_anexo_{$module}";

            $SQL = "SELECT {$primaryKey} FROM {$table} WHERE 1
            AND screen_uid_usuario IS NOT NULL
            AND screen_time_seen < NOW()
            ";

            $uids = $db->query($SQL, '*', 0);

            if (count($uids)) {
                $list   = implode(',', $uids);
                $SQL    = "UPDATE {$table} SET screen_uid_usuario = NULL, screen_time_seen = 0 WHERE {$primaryKey} IN ({$list})";

                if ($db->query($SQL)) {
                    $affected = $db->getAffectedRows();
                    $total += $affected;
                    if ($pwd) echo "Releasing {$affected} attachments from {$module}!\n";
                } else {
                    error_log("Error cron anexo: Updating attachments for {$module} {$db->lastError()}");
                }
            } else {
                if ($pwd) echo "No attachments fixed for module {$module}!\n";
            }
        }

        /*if ($total) {
            $app = \Dokify\Application::getInstance();
            $app['slack']->addNotice("{$total} queued attachments released");
        }*/

        return true;

    }

    public static function cronCall($time, $force = false, $tipo = NULL){
        anexo::releasingAttachmentsFromValidator();
        validation::releasingValidationsFromAuditor();

        return true;
    }

    public static function getModules() {
        return array(
            41 => 'empresa',
            42 => 'historico_empresa',
            51 => 'empleado',
            52 => 'historico_empleado',
            60 => 'historico_maquina',
            61 => 'maquina'
        );
    }

    public function dateUpdated(){
        $updated = $this->obtenerDato('fecha_emision_real');

        return (bool) trim($updated);
    }


    public function getValidationErrors() {
        $info = $this->getInfo();
        return $info["validation_errors"];
    }


    public function __toString(){
        $str = parent::__toString();
        if (stripos('_', $str) !== false && $this->module) $str .= "_{$this->module}";
        return $str;
    }

    public static function getExpired($modulo) {
        if (!isset($modulo)) return false;
        $db = new db;
        $sql = "SELECT uid_anexo_{$modulo}
                FROM ".PREFIJO_ANEXOS."{$modulo}
                WHERE (estado = 1 OR estado = 2 )
                AND fecha_expiracion != 0
                AND fecha_expiracion < UNIX_TIMESTAMP()";
        $anexos = $db->query($sql, '*', 0, "anexo_{$modulo}");
        return $anexos;
    }

    public static function expiredLogUI($modulo) {
        $anexosExpired = anexo::getExpired($modulo);
        foreach ($anexosExpired as $anexoExpired) {
            $anexoExpired->writeLogUI(logui::ACTION_STATUS_CHANGE, documento::ESTADO_CADUCADO, NULL);
        }
    }

    public static function commentExpired() {
        $modules = solicitable::getModules();
        $db = db::singleton();

        foreach ($modules as $uid => $module) {
            $table            = PREFIJO_ANEXOS . $module;
            $requirementTable = TABLE_DOCUMENTO_ATRIBUTO;
            $conditions       = [];
            $conditions[]     = "fecha_expiracion != 0";
            $conditions[]     = "fecha_expiracion < UNIX_TIMESTAMP()";
            $conditions[]     = "(estado = ". documento::ESTADO_VALIDADO . " OR estado = ". documento::ESTADO_ANEXADO . ")";


            $sql = "SELECT GROUP_CONCAT(uid_anexo_{$module}) intList, uid_{$module} as uid
                    FROM {$table} att
                    INNER JOIN {$requirementTable} req USING (uid_documento_atributo)
                    WHERE ". implode(' AND ', $conditions) ." GROUP BY uid_{$module}, uid_documento";

            if ($rows = $db->query($sql, true)) anexo::commentFromAttachGrouped($rows, $module);
        }
    }

    public static function commentFromAttachGrouped($rows, $module) {
        $class = "anexo_{$module}";
        if (count($rows)) foreach ($rows as $data) {
            // --- instance attachments
            $items = ArrayIntList::factory($data['intList']);
            $attachments = new ArrayAnexoList ($items->toObjectList($class));

            if (count($attachments)) {
                $commented = $attachments->saveComment('', NULL, comment::ACTION_EXPIRE);
            }

        }
    }


    /* public static function publicFields($modo, elemento $objeto = null, usuario $usuario = null);
     * pasamos objeto para cuando se da el caso de que queremos los daos de una instancia en concreto.
     * pasamos usuario cuando estamos editandolo como staff, para tener acceso completo a TODOS los datos (uid y tal)
    */
    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $arrayCampos = new FieldList();
        $arrayCampos['estado'] = new FormField(array('tag' => 'select', 'data'=> documento::getAllStatus(), 'default'=> '1' ) /*documento::ESTADO_ANEXADO /*1*/);
        $arrayCampos['fecha_actualizacion'] = new FormField();
        $arrayCampos['fecha_emision'] = new FormField();
        $arrayCampos['fecha_emision_real'] = new FormField();
        $arrayCampos['fecha_expiracion'] = new FormField();
        $arrayCampos['uid_empresa_referencia'] = new FormField();
        $arrayCampos['language'] = new FormField();
        $arrayCampos['is_urgent'] = new FormField();
        $arrayCampos['uid_usuario'] = new FormField();
        $arrayCampos['fileId'] = new FormField();
        $arrayCampos['uid_empresa_anexo'] = new FormField();
        $arrayCampos['uid_empresa_payment'] = new FormField();
        $arrayCampos['uid_validation'] = new FormField();
        $arrayCampos['full_valid'] = new FormField();
        $arrayCampos['uid_anexo_renovation'] = new FormField();
        $arrayCampos['reverse_date'] = new FormField();
        $arrayCampos['reverse_status'] = new FormField();
        $arrayCampos['validation_argument'] = new FormField();
        $arrayCampos['time_to_validate'] = new FormField();
        $arrayCampos['screen_uid_usuario'] = new FormField();
        $arrayCampos['screen_time_seen'] = new FormField();
        $arrayCampos['validation_errors'] = new FormField();

        switch($modo){
            case elemento::PUBLIFIELDS_MODE_ATTR:
                $arrayCampos["uid_anexo_renovation"] = new FormField();
                break;

            default:
                break;
        }
        return $arrayCampos;
    }


    public function copyTo($uidEmpresa){
        $uidEmpresa = $uidEmpresa instanceof empresa ? $uidEmpresa->getUID() : $uidEmpresa;

        $info = $this->getInfo(false);
        $item = $this->getElement();
        $tipo = $item->getType();

        $arrayCamposTabla = array("uid_documento_atributo", "archivo", "estado", "uid_$tipo", "uid_agrupador",
                    "uid_empresa_referencia", "hash", "nombre_original", "fecha_actualizacion",
                    "fecha_emision", "fecha_anexion", "fecha_emision_real", "descargas", "fecha_expiracion",
                    "language", "is_urgent", "uid_validation", "fileId",
                    "uid_usuario", "uid_empresa_anexo", "uid_empresa_payment, validation_errors", "full_valid");


        $sql = "INSERT IGNORE INTO {$this->tabla} (" .implode(",", $arrayCamposTabla). ") VALUES (
            '".$info['uid_documento_atributo']."','".$info['archivo']."','".$info['estado']."','".$info["uid_$tipo"]."',
            '".$info["uid_agrupador"]."','".$uidEmpresa."','".$info['hash']."','".db::scape($info['nombre_original'])."',
            '".$info['fecha_actualizacion']."','".$info['fecha_emision']."','".$info['fecha_anexion']."',
            '".$info['fecha_emision_real']."','".$info['descargas']."','".$info['fecha_expiracion']."',
            '".$info['language']."','".$info['is_urgent']."','".$info['uid_validation']."','".$info['fileId']."',
            ".db::valueNull($info['uid_usuario']).",".db::valueNull($info['uid_empresa_anexo']).",
            ".db::valueNull($info['uid_empresa_payment']).", '".$info['validation_errors']."', '".$info['full_valid']."')";


        if (!$this->db->query($sql)){
            error_log("Error poniendo la referencia por empresa con la SQL :".$sql. " y el error : ".$this->db->lasterror());
            return false;
        }

        return true;
    }

    public function exitsForCompany(empresa $empresaRef = NULL){

        $UIDempresaRef = ($empresaRef instanceof empresa) ? $empresaRef->getUID() : 0;
        $info = parent::getInfo(false);
        $item = $this->getElement();
        $tipo = $item->getType();

        $sql = "SELECT count(uid_anexo_$tipo) FROM {$this->tabla} WHERE uid_documento_atributo = ".$info['uid_documento_atributo']."
         AND uid_$tipo = ".$info["uid_$tipo"]." AND uid_agrupador = ".$info['uid_agrupador']."
         AND uid_empresa_referencia = {$UIDempresaRef} AND uid_anexo_$tipo != {$this->getUID()}";

        return (bool)$this->db->query($sql, 0, 0);

    }

}
