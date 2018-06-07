<?php
class exportacion_masiva extends elemento implements Ielemento, Iactivable, Icron {

    const DURACION_DIAS = 30;
    const URL = "/agd/#configurar/listado.php?m=exportacion_masiva&config=1&comefrom=empresa";


    public function __construct( $param, $extra = false){
        //$this->uid = $param;
        $this->tipo = "exportacion_masiva";
        $this->tabla = TABLE_EXPORTACION_MASIVA;

        $this->instance( $param, $extra );
    }

    public function getUserVisibleName(){
        return $this->obtenerDato("descripcion");
    }

    public function getProgressText() {
        $tpl = Plantilla::singleton();
        $progress = round($this->getProgress());

        if ($progress < 1) {
            $str = $tpl('iniciando')."...";
        } elseif($progress == 51) {
            $str = $tpl('generando_zip')."... ".round($this->getProgress(), 2)."%";
        } elseif($progress > 51) {
            $str = $tpl('guardando')."... ".round($this->getProgress(), 2)."%";
        } else {
            $str = $tpl('generando')."... ".round($this->getProgress(), 2)."%";
        }

        return $str;
    }

    public function getLineClass() {
        $class = array("color");

        $progress = $this->getProgress();
        $error = $this->obtenerDato('lasterror');
        if ($this->estaGenerada() && $progress==100 && !$error) {
            $class[] = "green";
        } elseif ($progress && !$error) {
            $class[] = "orange";
        } else {
            $class[] = "red";
        }

        return implode(" ", $class);
    }

    public function getInlineArray($usuario = false, $config = false, $extra = false){
        $tpl = Plantilla::singleton();
        $inline = $incluidos = array();

        if ($lastError = $this->obtenerDato('lasterror')) {
            $this->desbloquear();
            $inline[] = array(
                "img"   => RESOURCES_DOMAIN . "/img/famfam/error.png",
                array( "nombre" => $lastError)
            );
            return $inline;
        }

        if ($this->estaBloqueada()) {
            $working = archivo::PIDExists($this->getPID());

            if ($working) {
                $inline[] = array(
                    "img"   => RESOURCES_DOMAIN . "/img/famfam/time.png",
                    "className" => "update-html",
                    "data-interval" => "5000",
                    "data-href" => "configurar/account/export.php?action=progress&poid=".$this->getUID(),
                    array("nombre" => $this->getProgressText() )
                );
            } else {
                $this->desbloquear();
                $this->error('error_desconocido');
                return $this->getInlineArray($usuario, $config, $extra);
            }

        } else {
            $dateStr = $tpl('desde').' '.util::datetime2human($this->obtenerDato('fecha_inicio'))." ".$tpl('hasta').' '.util::datetime2human($this->obtenerDato('fecha_fin'));
            $inline[] = array(
                "img"   => RESOURCES_DOMAIN . "/img/famfam/date_next.png",
                array( "nombre" => $dateStr)
            );

            if ($this->obtenerDato('documentos_empresas')==1) {
                $incluidos[] = $tpl->getString('empresas');
            }
            if ($this->obtenerDato('documentos_empleados')==1) {
                $incluidos[] = $tpl->getString('empleados');
            }
            if ($this->obtenerDato('documentos_maquinas')==1) {
                $incluidos[] = $tpl->getString('maquinas');
            }

            // $incluidos = implode(', ',$incluidos);

            if (count($incluidos)>0) {
                $cadenaIncluidos = $tpl->getString('documentos_incluidos').' '.implode(', ',$incluidos);
                $inline[] = array(
                    'img' => RESOURCES_DOMAIN . '/img/famfam/folder_page_white.png',
                    array('nombre' => $cadenaIncluidos)
                );
            } else {
                $cadenaIncluidos = $tpl->getString('sin_documentos');
                $inline[] = array(
                    'img' => RESOURCES_DOMAIN . '/img/famfam/folder_add.png',
                    array('nombre' => $cadenaIncluidos, 'class' => 'box-it', 'href'=>'#configurar/modificar.php?m=exportacion_masiva&config=1&oid='.$this->getUID())
                );
            }

            if ($this->obtenerDato('incluir_historico')) {
                $inline[] = array(
                    'img' => RESOURCES_DOMAIN . '/img/famfam/report.png',
                    array('nombre' => $tpl->getString('incluir_historico'))
                );
            }
        }

        if ($size = $this->getSize()) {
            $inline[] = array(
                "img"   => RESOURCES_DOMAIN . "/img/famfam/disk.png",
                array( "nombre" => archivo::formatBytes($size))
            );
        }

        return $inline;
    }

    public function obtenerElementosActivables(usuario $usuario = null) {
        return new ArrayObjectList(array($usuario->getCompany()));
    }

    public function enviarPapelera($parent, usuario $usuario) {
        return $this->update(array('papelera'=>1), elemento::PUBLIFIELDS_MODE_TRASH, $usuario);
    }

    public function restaurarPapelera($parent, usuario $usuario) {
        return $this->update(array('papelera'=>0), elemento::PUBLIFIELDS_MODE_TRASH, $usuario);
    }

    public function inTrash($parent = false) {
        return (bool) $this->db->query('select papelera from '.$this->tabla.' where uid_'.$this->tipo.'='.$this->getUID(),0,0);
    }

    public function isActivable($parent = false, usuario $usuario = NULL){
        return $this->inTrash($parent);
    }

    public function isDeactivable($parent, usuario $usuario){
        return !$this->inTrash($parent);
    }

    public function removeParent(elemento $elemento, usuario $usuario) {
        return $this->eliminar($usuario);
    }

    public function setGenerada($generada) {
        return $this->update(array('generada'=>($generada?$generada:'0')), 'system');
        // return $this->db->query('UPDATE '.$this->tabla.' SET generada='.($generada?time():'0').' WHERE uid_'.$this->tipo.'='.$this->getUID());
    }

    public function estaGenerada($return = false) {
        if ($return && $fechaGeneracion = $this->obtenerDato('generada')) return $fechaGeneracion;
        return (bool) $this->obtenerDato('generada');
        // return $this->db->query('SELECT generada FROM '.$this->tabla.' WHERE uid_'.$this->tipo.'='.$this->getUID(),0,0);
    }

    public function estaBloqueada() {
        return (bool) $this->obtenerDato('block',true);
    }

    public function bloquear() {
        return $this->update(array('block'=>'1'), elemento::PUBLIFIELDS_MODE_SYSTEM);
    }

    private function error($string) {
        $tpl = Plantilla::singleton();
        $errorString = db::scape($tpl->getString($string));
        return $this->update(array('lasterror'=>$errorString),elemento::PUBLIFIELDS_MODE_SYSTEM);
    }

    public function desbloquear() {
        return $this->db->query('UPDATE '.$this->tabla.' SET block=0 WHERE uid_'.$this->tipo.'='.$this->getUID());
        // no podemos bloquear y desbloquear la misma instancia, por que basic->update compara los cambios a realizar con los valores al instanciar,
        // no en tiempo real. de momento prefiero dejar esto así a hacer modificaciones ahi dentro.
        // return $this->update(array('block'=>'0'), 'system');
    }

    // public function triggerBeforeUpdate($usuario, $item) {
    //  $lastError = $item->obtenerDato('lasterror');
    //  if (!empty($lastError)) {
    //      return $this->db->query("UPDATE {$this->tabla} SET lasterror='' WHERE uid_{$this->tipo}={$this->getUID()}");
    //  }
    // }

    public function triggerAfterCreate($usuario, $item) {
        if ($item instanceof exportacion_masiva) {
            if (!$item->estaBloqueada() && !$item->inTrash()) {
                $item->runScript();
            }
        }
    }

    public function runScript() {
        $this->error('');
        $cmdPath = realpath(dirname(__FILE__).'/../func/cmd/massexport.php');
        $pid = archivo::php5exec( $cmdPath, array($this->getUID(),CURRENT_DOMAIN));
        return $this->setPID($pid);
    }

    public function triggerAfterUpdate($usuario, $data) {
        if ( isset($data['block']) && $data['block'] == 1)  return null;
        // si cambia cualquier campo de los que determinan los anexos a incluir, regeneramos.
        if( isset($data["fecha_inicio"]) ){
            $aux = explode("/", $data["fecha_inicio"]);
            if( count($aux) === 3 ){
                $data["fecha_inicio"] = $aux[2]."-".$aux[1]."-".$aux[0] . " 00:00:00";
            } else {
                unset($data["fecha_inicio"]);
            }
        }
        if( isset($data["fecha_fin"]) ){
            $aux = explode("/", $data["fecha_fin"]);
            if( count($aux) === 3 ){
                $data["fecha_fin"] = $aux[2]."-".$aux[1]."-".$aux[0] . " 00:00:00";
            } else {
                unset($data["fecha_fin"]);
            }
        }
        $watchedFields = array('fecha_inicio','fecha_fin','documentos_empresas','documentos_empleados','documentos_maquinas','incluir_historico');
        $regenerar = false;
        foreach ($data as $fieldName => $value) {
            if (in_array($fieldName,$watchedFields) && $value != $this->obtenerDato($fieldName)) {
                $regenerar = true;
            }
        }

        if ($regenerar && $this instanceof exportacion_masiva) {
            if (!$this->estaBloqueada() && !$this->inTrash()) {
                $this->runScript();
            }
        }
    }

    public function getCompany() {
        return new empresa($this->obtenerDato('uid_empresa'));
    }

    /**
     * @return usuario
     */
    public function getUser() {
        return new usuario($this->obtenerDato('uid_usuario'));
    }

    public function getCompanies() {
        $empresa = $export->getCompany();
        $listaEmpresas = $empresa->getAllCompaniesIntList();
        $empresas = $listaEmpresas->toObjectList('empresa');
    }

    public function getFilename($completo = false) {
        if ($fechaGenerada = $this->estaGenerada(true)) {
            return ($completo?DIR_EXPORT:'').'export.'.$fechaGenerada.'.zip';
        }
        return null;
    }

    public function getDownloadName() {
        if ($fechaGenerada = $this->estaGenerada(true)) {
            return archivo::cleanFilenameString($this->getUserVisibleName()) .'.'.date('d.m.Y.H.i.s',$fechaGenerada).'.zip';
        }
        return null;
    }

    /**
     * A target company to get the docs from
     * @return empresa
     */
    private function getTargetCompany()
    {
        $target = $this->obtenerDato('uid_target');

        if (false === is_numeric($target)) {
            return null;
        }

        if (!$target) {
            return null;
        }

        return new empresa($target);
    }

    private function getExportResultset() {
        $company = $this->getCompany();
        $owners = $company->getStartIntList()->toComaList();
        $target = $this->getTargetCompany();

        $modules = array();
        if ($this->obtenerDato('documentos_empresas')) $modules[1] = array('name' => 'empresa');
        if ($this->obtenerDato('documentos_empleados')) $modules[8] = array('name' => 'empleado');
        if ($this->obtenerDato('documentos_maquinas')) $modules[14] = array('name' => 'maquina');
        $addHistoric = (bool) $this->obtenerDato('incluir_historico');


        $fields = array();
        $fields[] = "CONCAT('".DIR_FILES."', archivo) as archivo";
        $fields[] = "hash as md5";
        $fields[] = "alias as nombre";
        $fields[] = "nombre_original as original";
        $fields[] = "DATE_FORMAT(FROM_UNIXTIME(fecha_anexion), '%d-%m-%Y') as fecha";

        $fieldList = implode(', ', $fields);

        //$all = new ArrayObjectList;
        $unions = array();
        foreach($modules as $uid => &$data) {
            $module = $data['name'];
            $itemTable = constant('TABLE_'. strtoupper($module));

            $SQL = "
                SELECT uid_anexo_{$module} as uid, 'anexo_{$module}' as class, $fieldList
                FROM ". PREFIJO_ANEXOS."{$module}
                INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." USING(uid_documento_atributo)
                WHERE uid_empresa_propietaria IN ($owners) {$this->getDateCondition()}
                AND uid_{$module} IN (SELECT uid_{$module} FROM $itemTable)
            ";

            if ($target) {
                $SQL .= " AND uid_empresa_anexo = {$target->getUID()}";
            }

            $unions[] = $SQL;

            if ($addHistoric) {
                $SQL = "
                    SELECT uid_anexo_historico_{$module} as uid, 'anexo_historico_{$module}' as class, $fieldList
                    FROM ". PREFIJO_ANEXOS_HISTORICO."{$module}
                    INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." USING(uid_documento_atributo)
                    WHERE uid_empresa_propietaria IN ($owners) {$this->getDateCondition()}
                    AND uid_{$module} IN (SELECT uid_{$module} FROM $itemTable)
                ";

                if ($target) {
                    $SQL .= " AND uid_empresa_anexo = {$target->getUID()}";
                }

                $unions[] = $SQL;
            }

        }

        $SQL = implode(" UNION ", $unions);
        if ($resultset = $this->db->query($SQL)) {
            return $resultset;
        }

        return false;
    }

    private function getDateCondition() {
        $data = $this->getInfo();
        $sqlCondition = '';
        switch (true) {
            case isset($data['fecha_inicio']):
                $sqlCondition .= ' AND fecha_anexion>='.strtotime($data['fecha_inicio']).' ';
            case isset($data['fecha_fin']):
                $sqlCondition .= ' AND fecha_anexion<='.strtotime($data['fecha_fin']).' ';
        }
        return $sqlCondition;
    }


    public static function defaultData($data, Iusuario $usuario = NULL) {
        if ( ($m = obtener_comefrom_seleccionado()) == 'empresa') {
            $data["uid_empresa"] = obtener_uid_seleccionado();
        } else {
            die('Inaccesible');
        }

        if (isset($data['fecha_inicio']) && isset($data['fecha_fin'])) {
            $fini = DateTime::createFromFormat('d/m/Y',$data['fecha_inicio']);
            $ffin = DateTime::createFromFormat('d/m/Y',$data['fecha_fin']);
            $range = $ffin->diff($fini,true);
            if ($range->m >= 3 && $range->d > 0) {
                throw new Exception('Introduce dos fechas separadas como máximo por 3 meses.');
            }
        } else {
            throw new Exception('Introduce dos fechas separadas como máximo por 3 meses.');
        }

        if (empty($data['documentos_maquinas']) && empty($data['documentos_empleados']) && empty($data['documentos_empresas'])) {
            throw new Exception('Introduce al menos un tipo de documentos a exportar');
        }


        if( isset($data["fecha_inicio"]) ){
            $aux = explode("/", $data["fecha_inicio"]);
            if( count($aux) === 3 ){
                $data["fecha_inicio"] = $aux[2]."-".$aux[1]."-".$aux[0] . " 00:00:00";
            } else {
                unset($data["fecha_inicio"]);
            }
        }
        if( isset($data["fecha_fin"]) ){
            $aux = explode("/", $data["fecha_fin"]);
            if( count($aux) === 3 ){
                $data["fecha_fin"] = $aux[2]."-".$aux[1]."-".$aux[0] . " 23:59:59";
            } else {
                unset($data["fecha_fin"]);
            }
        }




        return $data;
    }

    public function updateData($data, Iusuario $usuario = NULL, $mode = NULL) {

        // estamos bloqueando o desbloqueando, o marcando un error
        if ($mode == elemento::PUBLIFIELDS_MODE_SYSTEM) {
            return $data;
        }
        // estamos enviando o restaurando papelera
        if ($mode == elemento::PUBLIFIELDS_MODE_TRASH && count($data) == 1 && isset($data['papelera'])) {
            return $data;
        }

        if (isset($data['fecha_inicio']) && isset($data['fecha_fin'])) {
            $fini = DateTime::createFromFormat('d/m/Y',$data['fecha_inicio']);
            $ffin = DateTime::createFromFormat('d/m/Y',$data['fecha_fin']);
            $range = $ffin->diff($fini,true);
            if ($range->m >= 3 && $range->d > 0) {
                throw new Exception('Introduce dos fechas separadas como máximo por 3 meses.');
            }
        } else {
            throw new Exception('Introduce dos fechas separadas como máximo por 3 meses.');
        }

        if (empty($data['documentos_maquinas']) && empty($data['documentos_empleados']) && empty($data['documentos_empresas'])) {
            throw new Exception('Introduce al menos un tipo de documentos a exportar');
        }

        $data['lasterror'] = '';
        return $data;
    }

    public static function cronCall($time, $force = null) {
        if ( ($lastcall = croncall::lastcall(get_called_class())) && $time - $lastcall < self::cronPeriod() && !$force) {
            return false;
        }

        echo "\n"; // CLI
        foreach (glob(DIR_EXPORT."*.zip") as $filename) {
            if (archivo::filectime($filename) + self::DURACION_DIAS*24*60*60 > $time) {
                echo 'Borrando '.$filename;
                echo (unlink($filename)?'OK':'Error...');
            }
        }
        return true;
    }


    public static function cronPeriod() {
        return self::DURACION_DIAS*24*60*60;
    }

    public static function optionsFilter($uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $extraData = null){
        if (empty($uidelemento) || empty($uidmodulo)) {
            return null;
        }
        $m = util::getModuleName($uidmodulo);
        $export = new $m($uidelemento);

        if ($export->estaBloqueada()) {
            return $sql = " AND 0";
        }

        //$lastError = $export->obtenerDato('lasterror');
        //if (!empty($lastError)) {
        //  return $sql = " AND uid_accion NOT IN (171,8) ";
        //}

        if( $export->estaGenerada() ){
            // return $sql = " AND uid_accion <> 171 ";
        } else {
            return $sql = " AND uid_accion <> 8 "; // descargar
        }
    }

    public function getProgress() {
        return (float) $this->obtenerDato("progress");
    }

    public function setProgress($progress, $add = false) {
        if ($add) {
            $progress = $this->getProgress() + $progress;
        }

        if ($progress > 100) $progress = 100;
        return $this->update(array("progress" => $progress), elemento::PUBLIFIELDS_MODE_SYSTEM);
    }

    public function getSize() {
        return (int) $this->obtenerDato("size");
    }

    public function setSize($size) {
        return $this->update(array("size" => $size), elemento::PUBLIFIELDS_MODE_SYSTEM);
    }


    public function getPID() {
        return (int) $this->obtenerDato("pid");
    }

    public function setPID($pid) {
        return $this->update(array("pid" => $pid), elemento::PUBLIFIELDS_MODE_SYSTEM);
    }

    public function generarExportacion()
    {
        $log = log::singleton();
        $log->info('exportacion_masiva', 'generar exportacion', $this->getUserVisibleName());
        if ($this->estaBloqueada()) {
            $log->nivel(3);
            $log->resultado('la exportacion está bloqueada', true);
            return false;
        }

        $this->bloquear();
        $this->setProgress(0);
        $this->setSize(0);

        if (!$tempName = $this->createZip()) {
            return false;
        }

        $this->setProgress(52);

        $fechaGeneracion = time();
        $ruta = DIR_EXPORT.'export.'.$fechaGeneracion.'.zip';
        $size = filesize($tempName);
        if ($size) {
            $this->setSize($size);

            $export = $this;
            $ok = archivo::uploadPiecesToS3($tempName, $ruta, function($response, $i, $part, $parts) use ($export) {
                $unit = 47/count($parts);
                $export->setProgress($unit, true);
            });

            if ($ok) {
                $this->setGenerada($fechaGeneracion);
                $log->nivel(1);
                $log->resultado('ok', true);
                $this->enviarEmailExportacion();
                $this->desbloquear();
                $this->error('');
                $this->setProgress(100);

                @unlink($tempName);
                return true;
            } else {
                $log->info('exportacion_masiva', 'generar zip exportacion', 'error');
                $log->nivel(3);
                $log->resultado("no se pudo escribir el fichero {$ruta}", true);
                $this->error("exportacion_error_escribir_fichero");
                $this->desbloquear();
                $this->setSize(0);
                return false;
            }
        } else {
            $log->info('exportacion_masiva', 'generar zip exportacion', 'no-data');
            $log->nivel(3);
            $log->resultado("el archivo zip con tiene contenido", true);
            $this->error("exportacion_error_fichero_vacio");
            $this->desbloquear();
            $this->setSize(0);
            return false;
        }
    }

    private function createZip()
    {
        $log = log::singleton();
        $log->info('exportacion_masiva', 'generar zip exportacion', $this->getUserVisibleName());

        $resultset = $this->getExportResultset();
        $numFiles = db::getNumRows($resultset);

        if (0 === $numFiles) {
            $log->nivel(3);
            $log->resultado('Error: No hay datos para exportar', true);
            $this->error("exportacion_sin_datos");
            $this->desbloquear();
            return false;
        }

        $exportId = time().uniqid();
        $tmpFolder = "/tmp/{$exportId}";

        if (!mkdir($tmpFolder)) {
            error_log("Unable to create tmp folder {$tmpFolder}");
            $log->nivel(3);
            $log->resultado('error al crear archivo zip', true);
            $this->error('exportacion_error_crear_zip');
            $this->desbloquear();
            return false;
        }

        // Calculamos que porcentaje supone cada archivo leido y añadido
        $unit = 50/$numFiles;

        $transformString = function($n) {
            return str_ireplace(' ', '_', $n);
        };

        while ($row = db::fetch_array($resultset, MYSQLI_ASSOC)) {
            $filePath = $row['archivo'];
            $className = $row['class'];
            $anexo = new $className($row['uid']);

            $item = $anexo->getElement();

            if ($item instanceof solicitable) {
                $extension = archivo::getExtension($filePath);
                $zipTypeFolderName = $tmpFolder.'/'.ucfirst(get_class($item)).'s';
                $zipFoldername = $zipTypeFolderName.'/'.archivo::cleanFilenameString($item->getUserVisibleName(), $transformString);
                $zipFileName = $zipFoldername.'/'.archivo::cleanFilenameString(trim($row['nombre']).'.'.$extension, $transformString);

                if (!is_dir($zipTypeFolderName) && !mkdir($zipTypeFolderName)) {
                    error_log("Unable to create tmp folder {$zipTypeFolderName}");
                    $log->nivel(3);
                    $log->resultado('error al crear archivo zip', true);
                    $this->error('exportacion_error_crear_zip');
                    $this->desbloquear();
                    return false;
                }

                if (!is_dir($zipFoldername) && !mkdir($zipFoldername)) {
                    error_log("Unable to create tmp folder {$zipTypeFolderName}");
                    $log->nivel(3);
                    $log->resultado('error al crear archivo zip', true);
                    $this->error('exportacion_error_crear_zip');
                    $this->desbloquear();
                    return false;
                }

                if (strpos($className, "historico") !== false) {
                    $zipFoldername .= '/historico';
                    if (!is_dir($zipFoldername) && !mkdir($zipFoldername)) {
                        error_log("Unable to create tmp folder {$zipTypeFolderName}");
                        $log->nivel(3);
                        $log->resultado('error al crear archivo zip', true);
                        $this->error('exportacion_error_crear_zip');
                        $this->desbloquear();
                        return false;
                    }

                    $zipFileName = $zipFoldername.'/'.archivo::cleanFilenameString(trim($row['nombre']).'.'.$row['md5'].'.'.$extension, $transformString);
                }

                if (archivo::downloadFromS3($filePath, $zipFileName)) {
                    $this->error('');
                    $this->setProgress($unit, true);
                } else {
                    $log->nivel(3);
                    $log->resultado("Error al añadir el archivo {$filePath}", true);
                    $this->error('exportacion_error_añadir_anexo');
                    error_log("El fichero {$filePath} no se puede descargar");
                }
            } else {
                error_log("El {$anexo} no tiene elemento asociado");
            }
        }

        // crear un zip a partir de la carpeta $tmpFolder, con el mismo nombre (false), con -m para borrar los ficheros añadidos y con prioridad baja (ionice)
        if ($zipFile = archivo::zipFolder($tmpFolder, false, '-m', 'ionice -c 3 nice -n 19')) {
            @unlink($tmpFolder);
            $log->nivel(1);
            $log->resultado('ok', true);

            $this->setProgress(51);
            return $zipFile;
        }

        $log->nivel(3);
        $log->resultado('Error: al crear el archivo zip', true);
        $this->error("exportacion_error_crear_zip");

        return false;
    }

    private function enviarEmailExportacion() {
        $tpl = Plantilla::singleton();
        $log = log::singleton();
        $log->info('exportacion_masiva','enviar correo exportacion generada', $this->getUserVisibleName());
        $user = $this->getUser();
        $direcciones = [$user->getEmail()];

        if (CURRENT_ENV == 'dev') {
            $direcciones = email::$developers;
        }

        $email = new email($direcciones);
        // no podemos usar CURRENT_DOMAIN por que este script se ejecuta via archivo::php5exec y no pasa por apache.
        $enlace = $tpl->getString('configuracion_sistema').' - '.$tpl->getString('exportacion_masiva');
        $current_domain = (defined('CURRENT_DOMAIN')?CURRENT_DOMAIN:$_SERVER["argv"][2]);
        if ($current_domain) {
            $enlace = sprintf($tpl->getString('enlace_href_texto'),$current_domain.exportacion_masiva::URL,$enlace);
        }
        $email->establecerContenido(sprintf($tpl->getHTML('email/exportacion_masiva.tpl'),$this->getUserVisibleName(),$enlace));
        $email->establecerAsunto( utf8_decode($tpl->getString('asunto_email_exportacion_generada')));
        if ( $email->enviar() ) {
            $log->nivel(1);
            $log->resultado("Enviando a ". implode(", ", $email->obtenerDestinatarios()), true);
            return true;
        } else {
            $log->nivel(3);
            $log->resultado("Ocurrió un error al enviar el email a ". $contacto->getUserVisibleName(), true);
            return false;
        }
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $fieldList = new FieldList();
        if ($modo == elemento::PUBLIFIELDS_MODE_TABLEDATA) {
            $fieldList['descripcion'] = new FormField();
            return $fieldList;
        }

        if ($modo == elemento::PUBLIFIELDS_MODE_TRASH) {
            $fieldList['papelera'] = new FormField( array("tag" => "input", "type" => "radio" ));
            return $fieldList;
        }

        if ($modo == elemento::PUBLIFIELDS_MODE_SYSTEM) {
            $fieldList['generada'] = new FormField(array('tag' => 'input', 'type' => 'radio'));
            $fieldList['block'] = new FormField(array('tag' => 'input', 'type' => 'radio'));
            $fieldList['lasterror'] =  new FormField(array('tag' => 'input', 'type' => 'text'));
            $fieldList['progress'] =  new FormField(array('tag' => 'input', 'type' => 'text'));
            $fieldList['size'] =  new FormField(array('tag' => 'input', 'type' => 'text'));
            $fieldList['pid'] =  new FormField(array('tag' => 'input', 'type' => 'text'));
            return $fieldList;
        }

        if ($modo == elemento::PUBLIFIELDS_MODE_NEW) {
            $fieldList['uid_empresa'] = new FormField(array('tag' => 'input', 'type' => 'text'));
        }


        $fieldList['descripcion'] = new FormField (array('tag' => 'input', 'type' => 'text', 'blank' => false));
        $fieldList['fecha_inicio'] = new FormField( array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "size" => "12", "date_format" => "%d/%m/%Y",  "placeholder" => "DD/MM/YYYY" ) );
        $fieldList['fecha_fin'] = new FormField( array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "size" => "12", "date_format" => "%d/%m/%Y",  "placeholder" => "DD/MM/YYYY" ) );
        $fieldList['documentos_empresas'] = new FormField(array('tag' => 'input', 'type'=>'checkbox', "className" => "iphone-checkbox"));
        $fieldList['documentos_empleados'] = new FormField(array('tag' => 'input', 'type'=>'checkbox', "className" => "iphone-checkbox"));
        $fieldList['documentos_maquinas'] = new FormField(array('tag' => 'input', 'type'=>'checkbox', "className" => "iphone-checkbox"));
        $fieldList['incluir_historico'] = new FormField(array('tag' => 'input', 'type'=>'checkbox', "className" => "iphone-checkbox"));
        if( $objeto instanceof exportacion_masiva && $objeto->obtenerDato('lasterror') ){
            $fieldList['lasterror'] =  new FormField(array('tag' => 'input','disabled'=>true));
        }

        if ($usuario instanceof Iusuario && $usuario->esStaff()) {
            $fieldList["uid_target"] = new FormField(array("tag" => "input", "type" => "text", "innerHTML" => "Contrata"));
        }

        return $fieldList;
    }
}
