<?php

class documento extends elemento implements Ielemento
{
    const COMENTARIOS_POR_PAGINA = 3;
    const DESCARGABLES_POR_PAGINA = 15;

    const ESTADO_SIN_SOLICITAR = -1;
    const ESTADO_PENDIENTE = 0;
    const ESTADO_ANEXADO = 1;
    const ESTADO_VALIDADO = 2;
    const ESTADO_CADUCADO = 3;
    const ESTADO_ANULADO = 4;

    const STATUS_EXPIRING = 'expiring';
    const STATUS_RENOVATION = 'renovation';
    const STATUS_REJECTING = 'rejecting';
    const STATUS_WRONG = 'wrong';
    const STATUS_VALIDABLE = 'validable';
    const STATUS_NOT_VALIDATED = 'not_valid';

    const DAYS_BEFORE_SUMMARY = 15;
    const DAYS_MAX_RENOVATION = 10;

    const ERROR_JAVASCRIPT = '<script>var ref=location.href,w=parent.window,$=parent.$,d=location.protocol+"//"+document.domain+"/agd/",ref=ref.replace(d,"");$("a[href$=\'"+ref+"\']").html("Archivo no disponible").removeAttr("href").addClass("fail");</script>';

    const MAX_REQUEST_INLINE = 2;


    const DETERMINE_MAX_ERROR_MARGIN = 5;

    const DETERMINE_MINIMUM_SIMILARITY = 75;

    const INFORME_APTITUD_NAME = 'reconocimiento';

    const INFORME_APTITUD = 23;
    const ITA = 5141;
    const ALTASS = 506;
    const TC2 = 18;
    const TC1 = 194;
    const SELF_EMPLOYED_RECEIPT = 35;

    // Minimum duration for Informe de aptitud in days
    const MIN_DURATION_INFORME_APTITUD = 180;

    protected $datos;
    public $elementoFiltro;
    public $pdf;

    /***
        $uid - uid del documento
        $objeto -
            si es === null || === false =>  tomará el valor de desacarga si existe y si no exite generico
            si es === true => fuerza la busqueda generica de todos los atributos
            si es === objeto documento_atributo => Filtrar por los datos de este...
            si es === objeto destino, filtra datos por el mismo
            si es === objeto solicitante, tipicamente para documentos de descarga (contextos diferentes a la 1ª opcion)
    */
    public function __construct( $uid, $objeto = false, $info = true){
        $this->tipo = "documento";
        $this->tabla = TABLE_DOCUMENTO;
        $this->instance( $uid, false);

        if( $objeto !== false ){
            if( $objeto instanceof documento_atributo ){
                $this->elementoFiltro = $objeto->getElement();
                $this->moduloFiltro = $this->elementoFiltro->getModuleName();
                if ($info) {
                    $this->datos = $this->documentInfo($objeto->getUID());
                }
            } else {
                $this->elementoFiltro = $objeto;
                if( $objeto !== true ){
                    $this->moduloFiltro = $this->elementoFiltro->getModuleName();
                }
                if ($info) {
                    $this->datos = $this->documentInfo();
                }
            }
        }

    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Reqtype\Reqtype
     */
    public function asDomainEntity()
    {
        // Instance the entity
        $entity = new \Dokify\Domain\Requirement\Type\Type(
            new \Dokify\Domain\Requirement\Type\TypeUid((int) $this->obtenerDato("uid_documento")),
            $this->obtenerDato("nombre")
        );

        return $entity;
    }

    public static function getRouteName () {
        return 'reqtype';
    }

    /**
     *
     * @return boolean Returns if the the document has a connector available
     */
    public function hasAvailableConnector()
    {
        $reqtypes = Dokify\Fremap\Connector::getAvailableDocuments();


        return $reqtypes->contains($this);
    }

    /***
       *
       *
       *
       */
    public function isStandard () {
        return (bool) $this->obtenerDato('is_standard');
    }

    /**
     * Check if the document is public or private
     *
     * @return bool
     */
    public function isPublic () {
        return (bool) $this->obtenerDato('is_public');
    }

    /***
       * Busca si alguno de los atributos solicitados por este cliente tiene definido un criterio de validación para mostrar al anexar
       *
       *
       */
    public function getCriteria (empresa $company) {
        $SQL = "SELECT criteria FROM ". TABLE_DOCUMENTO_ATRIBUTO . " WHERE 1
        AND uid_documento = {$this->getUID()}
        AND criteria != ''
        AND uid_empresa_propietaria = {$company->getUID()}
        LIMIT 1";

        $criteria = $this->db->query($SQL, 0, 0);
        if ($criteria = trim($criteria)) {
            return $criteria;
        }

        return false;
    }


    /***
       *
       *
       *
       */
    public static function fromCustom($custom, $item = false) {
        if (!is_numeric($custom)) return false;

        $db = db::singleton();
        $SQL = "SELECT uid_documento FROM ". TABLE_DOCUMENTO . " WHERE custom_id = {$custom}";

        if ($uid = $db->query($SQL, 0, 0)) {
            return new self($uid, $item);
        }

        return false;
    }

    /***
       *
       *
       *
       */
    public static function cronCall($time, $force = false) {
        if (date('H:i') != '05:30' && $force === false) return true;

        $db = db::singleton();

        $docsWithData = "(SELECT uid_documento FROM ". TABLE_DOCUMENTO . "_words)";
        $SQL = "SELECT uid_documento FROM ". TABLE_DOCUMENTO . " WHERE uid_documento IN ({$docsWithData})";
        $docs = $db->query($SQL, "*", 0, 'documento');

        if (!$docs) return true;

        $num = count($docs);

        foreach ($docs as $i => $doc) {
            print "Creating index from document {$doc->getUID()} \t[". ($i+1) ." of {$num}]\n";
            $doc->createKeywordIndex();
        }

    }

    /***
       *
       *
       *
       */
    public function createKeywordIndex () {
        $SQL = "
            SELECT doc_words
            FROM ". TABLE_DOCUMENTO ."_words
            WHERE uid_documento = {$this->getUID()}
            ORDER BY date DESC
            LIMIT 500
        ";

        $garbage = array('?', '(', ')', '!', ';', ',', 'º', 'ª', '%', '&', '€', '$', '=', '-', '~', "'", '"', '[', ']');
        $dict = Dictionary::getCommonWords();

        // --- save each word ocurrence
        $ocurrences = array();

        $soup = $this->db->query($SQL, "*", 0);

        // minimo 100 uploads de un tipo para tener una muestra valida
        if (CURRENT_ENV !== 'dev') if (count($soup) < 100) return false;

        if ($soup) foreach ($soup as $text) {
            $text = utf8_encode($text);
            $text = str_replace($garbage, ' ', $text);
            $words = preg_split('/[\s]/', $text);

            foreach ($words as $word) {
                $word = trim($word);
                $len = strlen($word);

                if ($len < 4 || $len > 16) continue;
                if (is_numeric($word)) continue;
                if (strstr($word, '@')) continue;
                if (in_array($word, $dict)) continue;

                if (!isset($ocurrences[$word])) $ocurrences[$word] = 0;
                $ocurrences[$word]++;
            }
        }

        $numOcurrences = array_unique(array_values($ocurrences));
        sort($numOcurrences);

        // -- get the most 3 repeats values
        $repeats = array_splice($numOcurrences, -5);


        $relevants = array();
        foreach ($ocurrences as $word => $ocurr) {
            if (in_array($ocurr, $repeats)) {
                $relevants[] = utf8_decode($word);
            }

            // max 20 words
            if (count($relevants) == 20) break;
        }


        $kwString = (count($relevants) > 4) ? implode(' ', $relevants) : '';


        $SQL = "UPDATE ". TABLE_DOCUMENTO ." SET `keywords` = '{$kwString}' WHERE uid_documento = {$this->getUID()}";
        return $this->db->query($SQL);
    }


    /***
       * Localiza las solicitudes de documento de todos los elementos de la empresa @company
       *
       *
       */
    public function locateRequestedItems (empresa $company)
    {
        $startIntList = $company->getStartIntList();


        $attrs = "SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO . " WHERE uid_documento = {$this->getUID()} AND descargar = 0 AND activo = 1 AND replica = 0";
        $getLastFromSet = db::getLastFromSet("uid_empresa_referencia");

        $sql = "SELECT uid_elemento_destino, uid_modulo_destino
        FROM ". TABLE_DOCUMENTOS_ELEMENTOS . " de
        WHERE uid_documento_atributo IN ({$attrs})
        AND IF (uid_empresa_referencia, $getLastFromSet IN ({$startIntList}), TRUE)
        ";

        $employees  = "SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa WHERE uid_empresa IN ({$startIntList}) AND papelera = 0";
        $machines   = "SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa WHERE uid_empresa IN ({$startIntList}) AND papelera = 0";

        $where = array();
        $where[] = "(uid_modulo_destino = 1 AND uid_elemento_destino IN ({$startIntList}))";
        $where[] = "(uid_modulo_destino = 8 AND uid_elemento_destino IN ({$employees}))";
        $where[] = "(uid_modulo_destino = 14 AND uid_elemento_destino IN ({$machines}))";

        $sql .= " AND (" . implode(" OR ", $where) . ") GROUP BY uid_elemento_destino, uid_modulo_destino";

        $items = new ArrayObjectList();

        if ($rows = $this->db->query($sql, true)) {
            foreach ($rows as $row) {
                $module = util::getModuleName($row['uid_modulo_destino']);
                $items[] = new $module($row['uid_elemento_destino']);
            }
        }

        return $items;
    }



    /***
       * Localiza el documento segun un PDF
       *
       *
       * @file => the path of the file we want to check
       * @ocr bool => whether we use ocr techniques or not
       * @ai bool => apply artificial intelligence?!!
       *
       *
       *
       * @jose ---- flippy of the day!
       *
       */
    public static function determine ($file, $filename = false, $ocr = false, $ai = false, $forceOCR = false) {
        $db         = db::singleton();
        $similarity = 0;

        $SQL = "SELECT uid_documento uid, nombre FROM ". TABLE_DOCUMENTO . " WHERE 1";

        if ($ai) {
            $SQL .= " AND keywords != ''";
        }

        $SQL .= " AND determinable = 1";


        $documentsData = $db->query($SQL, true);
        if ($filename) {
            // try to order documents..

            foreach ($documentsData as $key => $data) {
                $documentsData[$key]['similarity'] = similar_text($data['nombre'], $filename);
            }

            uasort($documentsData, function($a, $b) {
                return $a['similarity'] > $b['similarity'] ? -1 : 1;
            });
        }


        $words = pdfHandler::getPlainWords($file, NULL, $ocr, $forceOCR);


        if (!is_array($words) || !count($words)) {
            // itentalo de nuevo forzando OCR
            if ($forceOCR === false) {
                return self::determine ($file, $filename, true, $ai, true);
            }

            return NULL;
        }



        $matches = [];

        foreach ($documentsData as $data) {
            $similar    = [];
            $uid        = $data['uid'];
            $doc        = new documento($uid);
            $haystack   = $doc->getReservedWords($ai);

            if (!$haystack) continue;

            $num = count($haystack);
            $min = floor((self::DETERMINE_MAX_ERROR_MARGIN * $num) / 100);


            foreach ($haystack as $str) {
                if (in_array($str, $words)) {
                    $keys = array_keys($haystack, $str, true);
                    foreach ($keys as $i) {
                        // print "{$haystack[$i]} exists\n";
                        $similarity = 100;
                        $similar[]  = $similarity;
                        unset($haystack[$i]);
                    }


                } else {

                    $found = false;

                    foreach ($words as $word) {
                        similar_text($str, $word, $sim);
                        similar_text(utf8_encode($str), utf8_encode($word), $utf8sim);
                        $similarity = max($sim, $utf8sim);


                        if ($similarity > self::DETERMINE_MINIMUM_SIMILARITY) {
                            $found = true;
                            $similar[] = $similarity;
                            // print $str ." - ". $word . " - {$similarity}\n";
                            $keys = array_keys($haystack, $str, true);
                            foreach ($keys as $i) unset($haystack[$i]);
                        }
                    }

                    if ($found === false) {
                        $similar[] = 0;
                    }
                }

                // $remaining = count($haystack);
                // if ($remaining == 0 || $remaining <= $min) {
                // $matches[$uid][] = $similarity;
                //}
            }

            $count  = ($count = count($similar)) ? $count : 1;
            $avg    = array_sum($similar) / $count;

            if ($avg == 100) {
                $doc->match = $avg;
                return $doc;
            }

            $matches[] = ['avg' => $avg, 'uid' => $uid];
            // print $doc->getUserVisibleName() . "\n";
            // print_r($haystack);
            // print "\n";
            // break;
        }


        if (count($matches)) {
            uasort ($matches, function ($a, $b) {
                return $a['avg'] < $b['avg'];
            });



            $first  = reset($matches);
            $avg    = $first['avg'];

            // no ha matcheado nada de nada!
            if ($avg < self::DETERMINE_MINIMUM_SIMILARITY) {

                // itentalo de nuevo forzando OCR
                if ($forceOCR === false) {
                    return self::determine ($file, $filename, true, $ai, true);
                }

                return null;
            }

            $doc    = new documento($first['uid']);

            // just for DEV
            $doc->match = $avg;

            return $doc;
        }

        return null;
    }


    /*public static function estadosNoValidos() {
        return array(self::ESTADO_PENDIENTE, self::ESTADO_ANEXADO, self::ESTADO_CADUCADO, self::ESTADO_ANULADO);
    }*/

    public function zipAll($anexos){
        //nombre temporal del fichero
        $tempName = "/tmp/".time().".zip";

        //creamos el archivo
        if( !($zip = archivo::getZipInstance($tempName)) ){
            return false;
        }

        foreach($anexos as $anexo){
            $info = $anexo->getInfo();
            $attr = $anexo->obtenerDocumentoAtributo();
            $solicitante = $attr->getElement();
            $nombreSolicitante = $solicitante->getUserVisibleName();

            $data = $anexo->getInfo();
            $realFilePath = DIR_FILES . $info["archivo"];

            if( isset($realFilePath) && $fileData = archivo::leer($realFilePath) ){
                $zipFileName = archivo::cleanFilenameString($nombreSolicitante) . " - " . archivo::cleanFilenameString($attr->obtenerDato("alias")) . "." . archivo::getExtension($info["archivo"]);

                $zip->addFromString($zipFileName, $fileData);
            }
        }

        $zip->close();
        unset( $zip );

        if( !is_readable($tempName) ) return false;

        return archivo::descargar($tempName, "documentos");
    }

    /**
    * Devolver una lista de items anexo
    *
    * @param object(solicitable) El objeto del que queremos conocer el dato
    * @param object(usuario) el usuario que pregunta
    * @param [ mixed $filter = NULL ] aplicar filtro a la busqueda
    * @return ArrayObjectList donde cada indice es una anexo
    */
    public function obtenerAnexos(solicitable $item, Iusuario $usuario = NULL, $filters = NULL){
        $type = $item->getType();


        $sql = "SELECT uid_anexo_{$type} FROM ". TABLE_DOCUMENTO ."_{$type}_estado view WHERE 1
            AND uid_documento = {$this->getUID()} AND descargar = 0 AND uid_{$type} = {$item->getUID()}
            AND uid_anexo_{$type} IS NOT NULL ";

        if ($filters && !is_traversable($filters)) $filters = array($filters);
        if ($filters && count($filters)) foreach ($filters as $filter) {
            if ($filter instanceof solicituddocumento) {
                $sql .= " AND uid_solicituddocumento = {$filter->getUID()}";
            }
        }

        if ($condition = $usuario->obtenerCondicionDocumentosView($type)) $sql .= $condition;

        $sql .= " GROUP BY uid_{$type}, uid_agrupador, uid_empresa_referencia, uid_documento_atributo";


        $anexos = array();
        $uids   = $this->db->query($sql, "*", 0);
        if ($uids) foreach ($uids as $uid) {
            $anexos[] = new anexo($uid, $item);
        }


        return new ArrayObjectList($anexos);
    }


    /**
    * Devolver una lista de items solicitudDocumento
    *
    * @param object(solicitable) El objeto del que queremos conocer el dato
    * @param object(usuario) el usuario que pregunta
    * @param [ mixed $filter = NULL ] aplicar filtro a la busqueda
    * @param [int] determina los tipos a obtener
    * @param $req [solicituddocumento] - para comparar si esta solicitud existe y solo devolveresa
    * @return ArrayObjectList donde cada indice es una solicitud
    */
    public function obtenerSolicitudDocumentos(solicitable $item, Iusuario $usuario = NULL, $filters = array(), $reqType = null, solicituddocumento $req = null, $optionsFilter = null){

        if (true === is_countable($filters) && count($filters) === 0 && $reqType === null) {
            $options = isset($optionsFilter) ? implode($optionsFilter) : null;
            $cacheKey = implode('-', [__FUNCTION__, $this, $item, $usuario, $req, $options]);
            if (($value = $this->cache->getData($cacheKey)) !== NULL) {
                return ArrayRequestList::factory($value);
            }
        }


        if (!is_traversable($filters)) $filters = array($filters);
        $filters[] = $this;
        if ($reqType !== null) $filters['req_type'] = $reqType;
        if ($req instanceof solicituddocumento) $filters['req'] = $req;

        $solicitudes = $item->obtenerSolicitudDocumentos($usuario, $filters, false, " AND ", $optionsFilter);

        if (isset($cacheKey)) $this->cache->set($cacheKey, "{$solicitudes}");
        return $solicitudes;
    }


    public function getTypesFor(solicitable $item, Iusuario $usuario = NULL) {
        $solicitudes = $this->obtenerSolicitudDocumentos($item, $usuario);
        if( !count($solicitudes) ) return array();

        $module = $item->getType();
        $table = TABLE_DOCUMENTO."_{$module}_estado";

        $SQL = "SELECT req_type FROM {$table} WHERE uid_solicituddocumento IN ({$solicitudes->toComaList()}) GROUP BY req_type";
        $types = $this->db->query($SQL, "*", 0);

        return $types;
    }

    public function isOk(solicitable $item, Iusuario $usuario = NULL, $filters = array()){
        $solicitudes = $this->obtenerSolicitudDocumentos($item, $usuario, $filters);
        if (!count($solicitudes)) return false;

        $class      = $item->getModuleName();
        $anexos     = $solicitudes->getAttachments($class);
        $statuses   = $anexos->getStatuses($class);

        if (count($solicitudes) == count($anexos) && $statuses && count($statuses) == 1 && (int) $statuses[0] === documento::ESTADO_VALIDADO) {
            return true;
        }

        return false;
    }

    /**
    * Devolver una colección de clientes que piden algún documento a este item
    *
    * @param object(solicitable) El objeto del que queremos conocer el dato
    * @param object(usuario) el usuario que pregunta
    * @param reqType (int) el tipo de solicitudes que mirarmemos
    * @param req - si queremos mirar una sola request, para comparar si existe
    * @return object(ArrayObjectList) cada incide representará un cliente
    */
    public function obtenerEmpresasSolicitantes(solicitable $item, Iusuario $usuario = NULL, $reqType = documento_atributo::TYPE_FILE_UPLOAD, solicituddocumento $req = NULL){
        $type = $item->getModuleName();

        $sql = "SELECT uid_empresa_propietaria FROM ". TABLE_DOCUMENTO ."_{$type}_estado view WHERE 1
        AND uid_{$type} = {$item->getUID()} AND uid_documento = {$this->getUID()} AND descargar = 0 {$usuario->obtenerCondicionDocumentosView($type)}
        ";

        if (is_numeric($reqType)) {
            $reqType = $reqType ? $reqType : '0';
            $sql .= " AND req_type = {$reqType}";
        }

        if ($req instanceof solicituddocumento) {
            $sql .= " AND uid_solicituddocumento = {$req->getUID()}";
        }

        $sql .= " GROUP BY uid_empresa_propietaria";
        $empresas = $this->db->query($sql, "*", 0, "empresa");

        return new ArrayObjectList($empresas);
    }





    /**
    * Actualizar el estado de cada anexo pasado por parametro
    *
    * @param object(ArrayObjectList) Conjunto de anexos
    * @param object(usuario) el usuario que pregunta
    * @return bool
    */
    public function updateStatus($anexos, $estado, Iusuario $usuario = NULL, $argument = NULL) {
        if ($argument) {
            $argument = is_numeric($argument) ? new ValidationArgument($argument) : $argument;
        }

        foreach($anexos as $anexo){
            $elemento = $anexo->getElement();
            if (!$elemento) continue;
            $modulo = $elemento->getModuleName();
            $current = $anexo->getStatus();
            $table = $anexo->getFullTableName();

            if( $current == $estado ){
            //  return false;
            }

            if( $current == documento::ESTADO_CADUCADO ){
                throw new Exception("documento_caducado_no_validable");
            }

            // estado = $estado,
            $set = array();
            $set[] = "estado = $estado";
            $set[] = "fecha_actualizacion = UTC_TIMESTAMP()";
            if ($argument instanceof ValidationArgument) {
                if (!$anexo->addValidationArgument($argument)) {
                    throw new Exception("error_actualizar");
                }

                switch ($argument->getUID()) {
                    case ValidationArgument::WRONG_DATE:
                        if ($anexo->resetUpdatedDate($usuario) === false) {
                            throw new Exception("error_actualizar");
                        }
                        break;

                    default:
                        # code...
                        break;
                }

            } else {
                $set[] = "validation_argument = NULL";
            }

            $delayedStatusArgument = $argument && $argument->getDelayedStatus($anexo);

            // Dejamos en renovación con delayed Status si anulamos documento en renovación
            if ($anexo->isRenovation() && $estado == documento::ESTADO_ANULADO && (!$delayedStatusArgument)) {
                $set[] = "estado = ".documento::ESTADO_VALIDADO;

                $anexoReverseDate = $anexo->getReverseDate();
                $strMaxDate = '+ '. DelayedStatus::defaultChangeDays() . ' days';
                $reverseMaxDate = strtotime($strMaxDate);
                $reverseDate = $reverseMaxDate < $anexoReverseDate ? $reverseMaxDate : $anexoReverseDate;

                $reverseStatus = documento::ESTADO_ANULADO;

                $delayedStatus = new DelayedStatus($reverseStatus, $reverseDate);
                if (!$anexo->addDelayedStatus($delayedStatus)) throw new Exception("error_add_delayed_status");
            } elseif (($estado == documento::ESTADO_VALIDADO || $estado == documento::ESTADO_ANULADO || $estado == documento::ESTADO_ANEXADO)  && (!$delayedStatusArgument)) {
                $set[] = "uid_anexo_renovation = NULL";
                $set[] = "reverse_status = NULL";
                $set[] = "reverse_date = NULL";
            }


            $sql = "UPDATE $table SET ". implode(", ", $set) ." WHERE uid_anexo_{$modulo} = {$anexo->getUID()}";
            if (!$this->db->query($sql)) {
                throw new Exception("error_actualizar");
            }

            $writeLog = ($usuario instanceof usuario && $usuario->getAppVersion() != 2) || !$usuario instanceof usuario;
            if ($writeLog) {
                if (!$anexo->writeLogUI(logui::ACTION_STATUS_CHANGE, $estado, $usuario)) {
                    error_log('Unable to write logUI for documento::updateStatus in anexo #'. $anexo->getUID());
                    throw new Exception("error_log");
                }
            }

            $anexo->clearItemCache();
        }

        if ($elemento instanceof solicitable) {
            cache::exec('clear', "{$elemento}-getInlineArray-*");
            cache::exec('clear', "solicitable-obtenerEstadoEnAgrupador-{$elemento}*");
            cache::exec('clear', "solicitable-getDocsInline-{$elemento}*");
        }

        return true;
    }


    public function saveWords ($words, fileId $fileId = NULL, $file = NULL) {

        // then we do it async
        if ($file) {
            $cmd = DIR_ROOT . "func/cmd/readwords.php ";

            return archivo::php5exec($cmd, [$this->getUID(), $file, $fileId->getUID()]);
        }

        $words = array_filter($words, function($str){
            $vat = str_pad(ltrim($str, "0"), 9, "0", STR_PAD_LEFT);
            if (vat::isValidSpainVAT($vat)) return false;
            if (vat::isValidSpainId($vat)) return false;

            $noNumbers = preg_replace('/[0-9]+/', '', $str);
            if (strlen($noNumbers) < 3) return false;
            if (is_numeric($str)) return false;

            if (strpos($str, "*") !== false) return false;
            if (strpos($str, ":") !== false) return false;

            return true;
        });

        $words = array_map('utf8_decode', $words);
        $words = db::scape(implode(' ', $words));
        $fileId = $fileId instanceof fileId ? $fileId->getUID() : "";

        $SQL = "INSERT INTO ". TABLE_DOCUMENTO . "_words (uid_documento, doc_words, fileId) VALUES ({$this->getUID()}, '{$words}', '{$fileId}')";
        return $this->db->query($SQL);
    }


    /**
    * Cargar un fichero en una serie de solicitudes
    *
    * @param object(solicitable) El objeto del que queremos conocer el dato
    * @param object(usuario) el usuario que pregunta
    * @return object(ArrayObjectList) cada incide representará un cliente
    */
    public function upload(
        $file,
        $fileHash,
        $name,
        $date,
        Isolicitable $item,
        $solicitudes,
        $usuario,
        $comentario = null,
        $expiracion = null,
        $mime = null,
        $autoValidate = false,
        $lastUpload = false,
        $related = null,
        $minDuration = null
    ) {

        if (!count($solicitudes)) {
            $exception = new \Dokify\Exception\FormException('no_solicitante_documento');
            $exception->setType('request');
            throw $exception;
        }

        // allow to use DateTime objects
        if ($date instanceof DateTime) {
            $fechaEmision = $date->getTimestamp();
        } elseif (!$date || !trim($date)) {
            $exception = new \Dokify\Exception\FormException('error_falta_fecha');
            $exception->setType('date');
            throw $exception;
        } else {
            $fechaEmision = documento::parseDate($date);
        }

        // sera un error

        if (!is_numeric($fechaEmision)) {
            $exception = new \Dokify\Exception\FormException('error_fecha');
            $exception->setType('date');
            throw $exception;
        }


        //trabajamos con el archivo
        $splittedName = explode(".", $file);
        $relativePath = self::obtenerNombreModulo($item->getModuleId()) . "/uid_" . $item->getUID() . "/";
        $rutaCarpeta = DIR_FILES . $relativePath;
        $fileDBName = uniqid(). '.' . time() . "." . end($splittedName);
        $rutaArchivo = $rutaCarpeta . $fileDBName;
        $sqlFileName = $relativePath . $fileDBName;
        $s3 = archivo::getS3();

        if (!$s3 && !is_dir($rutaCarpeta)) {
            mkdir( $rutaCarpeta, 0777, true);
        }

        // Para optimizar el tiempo de espera, si tienemos S3 activado copiamos directamente los ficheros
        // Si comentamos este IF el entorno local y el de producción deberían seguir funcionando, de forma mas lenta
        if ($s3) {
            $error = !archivo::copy($file, $sqlFileName);

            // We cant copy from the S3 to the final bucket, try locally
            if ($error) {
                $localfile = '/tmp/'. $file;
                if (is_readable($localfile)) {
                    $error = !archivo::uploadToS3($localfile, $sqlFileName);
                }
            }

            // Throw exception if cant copy the file
            if ($error) {
                throw new Exception("error_copiar_archivo");
            }
            // Copy tmp file a remove it


        } else {
            // Recover temporary file
            if (!$filedata = archivo::tmp($file)) {
                throw new Exception("error_copiar_archivo");
            }
            // Write to final destination
            if (!archivo::escribir($rutaArchivo, $filedata)) {
                throw new Exception("error_copiar_archivo");
            }
        }

        // Make sure we have the file
        if (!archivo::is_readable($rutaArchivo)) {
            throw new Exception("error_leer_archivo");
        }


        $comentario = $comentario ? trim($comentario) : null;
        $modulo = $item->getModuleName();

        $fileId = fileId::generateFileId($sqlFileName);

        $event = new Dokify\Application\Event\Upload\FileIdStore($fileId);
        $this->dispatcher->dispatch(Dokify\Events::POST_FILEID_STORE, $event);

        $uploadTime = time();

        foreach ($solicitudes as $solicitud) {
            $attr = $solicitud->obtenerDocumentoAtributo();
            $solicitante = $attr->getElement();
            // -------- Validar formatos...
            $formato = $this->comprobarFormato($attr->getUID(), archivo::getMimeType($rutaArchivo, $mime/*, $arrayDatosArchivo["type"]*/));
            if ($formato === false) {
                throw new Exception("formato_documento_no_permitido");
            }
        }

        $validatedAttachments = new \Dokify\Domain\Attachment\Collection();

        foreach ($solicitudes as $solicitud) {
            $attr = $solicitud->obtenerDocumentoAtributo();
            $solicitante = $attr->getElement();
            //$tipoSolicitante = $solicitante->getModuleName();

            if ($attr->caducidadManual()) {
                $duracion = null;
                $manualExpirationDate = null;

                if ($expiracion && isset($expiracion[$solicitud->getUID()])) {
                    $manualExpirationDate = $expiracion[$solicitud->getUID()];
                } elseif ($this->canSelectItems()) {
                    $manualExpirationDate = reset($expiracion);
                }

                if (null !== $manualExpirationDate) {
                    $fechaExpiracion = documento::parseDate($manualExpirationDate);

                    if ($fechaExpiracion === "error_fecha_incorrecta") {
                        $fechaExpiracion = 0;
                    } else {
                        if ($fechaEmision > $fechaExpiracion) {
                            $exception = new \Dokify\Exception\FormException('error_fecha_caducidad');
                            $exception->setType('expiredate');
                            throw $exception;
                        }
                    }

                // Forzar fecha determinada
                } elseif (is_numeric($expiracion)) {
                    $fechaExpiracion = $expiracion;
                } else {
                    $exception = new \Dokify\Exception\FormException('sin_fecha_caducidad');
                    $exception->setType('expiredate');
                    throw $exception;
                }

            } else {
                $duraciones = $attr->obtenerDuraciones(false, $fechaEmision);

                if ($expiracion && is_numeric($expiracion) && self::TC2 === (int) $this->getUID()) {
                    $duracion = $expiracion;
                } elseif ($expiracion && isset($expiracion[$solicitud->getUID()]) && ($duracion = $expiracion[$solicitud->getUID()]) && is_array($duraciones)) {
                    if (is_numeric($timestamp = documento::parseDate($duracion))) {
                        // --- check if its valid
                        if (!in_array($duracion, $duraciones)) {
                            $message = _("The selected document date is not valid");
                            throw new Exception($message);
                        }

                        $fechaExpiracion = $timestamp;

                    // Vemos que es una duración de las posibles
                    } elseif (in_array($duracion, $duraciones)) {
                        $duracion = $expiracion[$solicitud->getUID()];
                    } else {
                        throw new Exception("Seleccina una de las opciones de la lista");
                    }
                } elseif (is_array($duraciones) && count($duraciones) == 1) {
                    $duracion = reset($duraciones);
                    if (is_numeric($timestamp = documento::parseDate($duracion))) {
                        $fechaExpiracion = $timestamp;
                    } else {
                        throw new Exception("Seleccina una de las opciones de la lista");
                    }

                } elseif (is_numeric($minDuration)) {
                    if (is_numeric($duraciones)) {
                        $duracion = $duraciones;
                    } else {
                        $duracionesFiltradas = array_filter($duraciones, function ($duration) use ($minDuration) {
                            return $duration >= $minDuration;
                        });

                        $duracion = count($duracionesFiltradas) ? min($duracionesFiltradas) : max($duraciones);
                    }
                } else {
                    $duracion = is_numeric($duraciones) ? $duraciones : min($duraciones);
                }

                if (is_numeric($duracion)) {
                    $segundosParaCaducar = ((int)$duracion)*24*60*60;
                    $fechaExpiracion = ($duracion) ? ($fechaEmision+$segundosParaCaducar) : 0;
                }
            }

            // caducar los documentos segun la zona horaria
            if ($fechaExpiracion) {
                $userTimeZone = $usuario->getTimeZone();
                $expirationDateTime = new DateTime();
                $expirationDateTime->setTimestamp($fechaExpiracion);
                $fechaExpiracion -= $userTimeZone->getOffset($expirationDateTime);
            }

            if (0 !== $fechaExpiracion) {
                $requirementInfo = $attr->getInfo();
                $gracePeriod = (int) $requirementInfo['grace_period'];
                $fechaExpiracion += $gracePeriod * 24* 60 * 60;
            }

            if ($fechaExpiracion && time() > $fechaExpiracion) {
                throw new Exception("actualmente_caducado", 498);
            }

            $camposTabla = "uid_documento_atributo, archivo, estado, uid_$modulo, uid_agrupador, uid_empresa_referencia, hash, nombre_original, fecha_actualizacion, fecha_emision, fecha_anexion, fecha_emision_real, fecha_expiracion, language, is_urgent, uid_usuario, uid_empresa_anexo, uid_validation, fileId, duration, full_valid";
            $camposTablaHistorico = $selectHistorico = $camposTabla ." ,uid_empresa_payment, time_to_validate, validation_errors, validation_argument";


            $tablaHistorico = PREFIJO_ANEXOS . "historico_$modulo";
            $tablaAnexo = PREFIJO_ANEXOS . $modulo;
            $className = "anexo_{$modulo}";


            // --- ajustamos algunos datos al pasar al historico
            $status = 'IF (reverse_status IS NOT NULL, reverse_status, estado)';
            $selectHistorico = str_replace('estado', $status, $selectHistorico);


            $agrupador = ( $agrupador = $solicitud->obtenerAgrupadorReferencia() ) ? $agrupador->getUID() : 0;
            $empresa = ( $empresa = $solicitud->obtenerIdEmpresaReferencia() ) ? $empresa : 0;
            $uidAnexoRenovation = 'NULL';
            $anexoStatus = documento::ESTADO_ANEXADO;
            $reverseStatus = 'NULL';
            $reverseDate = 'NULL';

            $sql = "SELECT uid_anexo_{$modulo} FROM {$tablaAnexo} WHERE 1
            AND uid_documento_atributo = {$attr->getUID()}
            AND uid_$modulo = {$item->getUID()}
            AND uid_agrupador = $agrupador
            AND uid_empresa_referencia = '$empresa'";
            $uid = $this->db->query($sql, 0, 0);

            // Si ya hay una anexo anterior
            if (is_numeric($uid)) {
                $anexo = new $className($uid);


                $sql = "INSERT IGNORE INTO {$tablaHistorico} ($camposTablaHistorico) SELECT $selectHistorico FROM {$tablaAnexo} WHERE uid_anexo_{$modulo} = $uid";
                if (!$this->db->query($sql)) {
                    throw new Exception("error_guardar_historico");
                }
                // --- Nueva primary key de nuestro archivo en el historico...
                $primaryKeyHistorico = $this->db->getLastId();
                $anexoIsValidated = $anexo->getStatus() == documento::ESTADO_VALIDADO;

                $anexoStatus = (true === $anexoIsValidated) ? documento::ESTADO_VALIDADO : $anexoStatus;

                if (($anexoRenovation = $anexo->getAnexoRenovation()) && ($anexoReverseDate = $anexo->getReverseDate())) {
                    $uidAnexoRenovation = (int)$anexoRenovation->getUID();
                } else {
                    $uidAnexoRenovation = (true === $anexoIsValidated) ? $primaryKeyHistorico : $uidAnexoRenovation;
                }

                // add delayed status
                if (is_int($uidAnexoRenovation)) {
                    if (isset($anexoReverseDate)) {
                        $reverseDate = $anexoReverseDate;
                    } else {
                        $expirationDates = array();
                        $strMaxDate = '+ '. DelayedStatus::RENOVATION_CHANGE_DAYS . ' days';
                        $expirationDates['maxDate'] = strtotime($strMaxDate);
                        if ($anexoExpiredTime = $anexo->getExpirationTimestamp()) {
                            $expirationDates['anexoExpiredTime'] = $anexoExpiredTime;
                        }
                        $reverseDate = min($expirationDates);
                    }

                    $reverseStatus = documento::ESTADO_ANEXADO;
                }

                if ($primaryKeyHistorico) {
                    $classNameHistorico = "anexo_historico_{$modulo}";
                    $anexoHistorico = new $classNameHistorico($primaryKeyHistorico);
                    if (!logui::move($anexo, $anexoHistorico)) {
                        error_log("Logui from anexo {$modulo} $uid cant be moved to $primaryKeyHistorico");
                    }
                    $anexoHistorico->update(array("uid_anexo"=>$uid));
                    $validationsStatus = $anexoHistorico->getValidationStatus();
                    if (count($validationsStatus)) {
                        $validationsStatus->foreachCall("update", array(array("uid_modulo"=>$anexoHistorico->getModuleId())));
                    }
                } else {
                    // dump("pkey: $primaryKeyHistorico");
                    $reqList = $solicitudes->toComaList();
                    error_log("anexo_{$modulo} $uid cant be copied to historic for req {$solicitud->getUID()}. Requests {$reqList}");
                }

                //----------- borramos de la tabla actual el registro
                $sql = "DELETE FROM {$tablaAnexo} WHERE uid_anexo_{$modulo} = $uid";
                if (!$this->db->query($sql)) {
                    throw new Exception("error_limpiar_actual");
                }
            }

            $userCompany = $usuario->getCompany();
            if ($userCompany->esCorporacion()) {
                $uidEmpresaAnexo = ($item instanceof empresa) ? $item->getUID() : $item->getCompany($usuario)->getUID();
            } else {
                $uidEmpresaAnexo = $userCompany->getUID();
            }

            $language = system::getIdLanguage($usuario->getCompany()->getCountry()->getLanguage());


            $fullValid  = '0';
            $set        = false;

            if ($autoValidate) {
                $set = ($autoValidate instanceof anexo && $autoValidate->isFullValid()) || $autoValidate === true;

                if ($set) {
                    $anexoStatus        = documento::ESTADO_VALIDADO;
                    $reverseStatus      = 'NULL';
                    $reverseDate        = 'NULL';
                    $uidAnexoRenovation = 'NULL';

                    if ($autoValidate) {
                        $fullValid = '1';
                    }
                }
            }

            $sql = "INSERT INTO {$tablaAnexo} ($camposTabla, uid_anexo_renovation, reverse_status, reverse_date)
            VALUES ({$attr->getUID()}, '$sqlFileName', $anexoStatus, {$item->getUID()}, $agrupador, '$empresa', '$fileHash', '". db::scape($name)."', FROM_UNIXTIME({$uploadTime}), $fechaEmision, {$uploadTime}, '0', $fechaExpiracion, '" .$language. "', '0', {$usuario->getUID()}, $uidEmpresaAnexo, '0', '$fileId', ". db::valueNull($duracion) .", {$fullValid}, $uidAnexoRenovation, $reverseStatus, $reverseDate)";

            if (!$this->db->query($sql)) {
                throw new Exception("error_guardar_nuevo_archivo");
            } else {
                $primaryKey = $this->db->getLastId();
                $anexo = new anexo($primaryKey, $item);
                $anexo->writeLogUI(logui::ACTION_CREATE, null, $usuario);

                $attachmentEntity = $anexo->asDomainEntity();
                $event = new Dokify\Application\Event\Attachment\Store($attachmentEntity);
                $this->dispatcher->dispatch(Dokify\Events::POST_ATTACHMENT_STORE, $event);

                $userComment = $autoValidate && $set ? null : $usuario;

                if ($anexoStatus === documento::ESTADO_VALIDADO && $uidAnexoRenovation == 'NULL') {
                    $anexo->writeLogUI(logui::ACTION_STATUS_CHANGE, $anexoStatus, $userComment);
                }

                if ($set) {
                    $validatedAttachments->append($anexo->asDomainEntity());
                }
            }
        }

        if ($validatedAttachments->count() > 0) {
            $validationAction = \Dokify\Application\Event\Validation::ACTION_VALIDATE;

            $validationEvent = new \Dokify\Application\Event\Validation(
                $validationAction,
                $validatedAttachments,
                $userCompany->asDomainEntity(),
                $usuario->asDomainEntity()
            );

            $app = \Dokify\Application::getInstance();

            switch ($item->getRouteName()) {
                case 'company':
                    $app->dispatch(\Dokify\Events::POST_COMPANY_ATTACHMENT_VALIDATION, $validationEvent);
                    break;
                case 'employee':
                    $app->dispatch(\Dokify\Events::POST_EMPLOYEE_ATTACHMENT_VALIDATION, $validationEvent);
                    break;
                case 'machine':
                    $app->dispatch(\Dokify\Events::POST_MACHINE_ATTACHMENT_VALIDATION, $validationEvent);
                    break;
            }
        }

        cache::exec('clear', "{$item}-getInlineArray-*");

        //guardamos los comentarios
        $reqType = new requirementTypeRequest($solicitudes, $item);
        $commentId = $reqType->saveComment(
            $comentario,
            $usuario,
            comment::ACTION_ATTACH,
            watchComment::AUTOMATICALLY_ATTACHMENT,
            false,
            null,
            null,
            $related
        );

        if ($autoValidate && $set) {
            $commentId = $reqType->saveComment('', null, comment::ACTION_VALIDATE);
        }

        $event = new Dokify\Application\Event\CommentId\Store($commentId);
        $this->dispatcher->dispatch(Dokify\Events\CommentIdEvents::POST_COMMENTID_STORE, $event);

        // Si todo ha ido bien podemos eliminar el fichero temporal. Solo es importante si estamos en S3
        if ($lastUpload) {
            if ($s3) {
                archivo::tmp($file, null, true);
            }
        }

        return true;
    }


    /********************* PREVIO A ESTO, EMPIEZO A RE-ESTRUCTURAR ESTE CLASE ******************************/



    public static function getAllStatus(){
        return array( self::ESTADO_PENDIENTE, self::ESTADO_ANEXADO, self::ESTADO_VALIDADO, self::ESTADO_CADUCADO, self::ESTADO_ANULADO );
    }

    public static function getInvalidStatus(){
        return array( self::ESTADO_PENDIENTE, self::ESTADO_CADUCADO, self::ESTADO_ANULADO );
    }

    /**
     * Get all the status but validated
     * @return array Not valid status constants
     */
    public static function getNotValidatedStatus()
    {
        return [
            self::ESTADO_PENDIENTE,
            self::ESTADO_ANEXADO,
            self::ESTADO_CADUCADO,
            self::ESTADO_ANULADO,
            self::STATUS_RENOVATION,
        ];
    }


    public static function getReusableStatus($index = false){
        $status = array(self::ESTADO_ANEXADO, self::ESTADO_VALIDADO);

        // -- helper para poder guardar los estados en un índice determinado
        if ($index) return array($index => $status);

        return $status;
    }

    public function obtenerDuraciones($solicitante=false){
        $uids = $this->obtenerIdAtributos();
        foreach($uids as $uid){
            $attr = new documento_atributo($uid);
            if( $solicitante ){
                $elemento = $attr->getElement();
                if( !util::comparar($solicitante, $elemento) || $attr->getUID() != $solicitante->atributoDocumento["uid_documento_atributo"] ){ continue; }
            }

            return $attr->obtenerDuraciones();
        }
    }


    public function getMaxDuration () {
        $SQL = "SELECT max(duracion) FROM ". TABLE_DOCUMENTO_ATRIBUTO . " WHERE uid_documento = {$this->getUID()}";
        return $this->db->query($SQL, 0, 0);
    }


    public function obtenerHistorico(Iusuario $usuario, $filter = null) {
        $modulo = $type = strtolower($this->elementoFiltro->getType());
        $tabla = PREFIJO_ANEXOS_HISTORICO. $modulo;

        $sql = "SELECT uid_anexo_historico_$modulo
                FROM (
                    SELECT *, NULL as generated_by
                    FROM $tabla INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO . " as da USING(uid_documento_atributo)
                    WHERE uid_$modulo = ". $this->elementoFiltro->getUID() ."
                    AND uid_documento_atributo IN (
                        SELECT uid_documento_atributo
                        FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
                        WHERE uid_documento = ". $this->getUID() ."
                    )
                ) as view WHERE 1
        ";

        if ($filter instanceof solicituddocumento) {
            $data = $filter->getInfo();
            $sql .= "
                AND uid_agrupador = {$data['uid_agrupador']}
                AND uid_empresa_referencia = '{$data['uid_empresa_referencia']}'
                AND uid_documento_atributo = '{$data['uid_documento_atributo']}'
            ";
        }

        if ($filter instanceof anexo) {
            $sql .= "
                AND uid_anexo_historico_$modulo = {$filter->getUID()}
            ";
        }

        if( $condicion = $usuario->obtenerCondicionDocumentosView($type) ){
            $sql .= $condicion;
        }

        $datos = $this->db->query($sql, "*", 0);

        if( !is_array($datos) || !count($datos) ){ return false; }

        $colleccionHistorico = array();

        foreach($datos as $idHistorico){
            $colleccionHistorico[] = new documento_historico($idHistorico, $modulo);
        }

        return $colleccionHistorico;

    }

    public function getUserVisibleName($fn=false, $locale = null){
        $locale = isset($locale) ? $locale : Plantilla::getCurrentLocale();

        if( $locale != "es" ){
            $documentoIdioma = new traductor( $this->getUID(), $this );
            $nombre = $documentoIdioma->getLocaleValue( $locale );
        }


        if( !isset($nombre) || !trim($nombre) ){
            if( !is_array($this->datos)||!count($this->datos) ){
                $nombre = utf8_encode($this->db->query("SELECT nombre FROM $this->tabla WHERE uid_documento = $this->uid", 0, 0));
            } else {
                $datos = reset($this->datos);
                $nombre = utf8_encode($datos["nombre"]);
            }
        }

        if( is_callable($fn) ){
            return $fn($nombre);
        }

        return $nombre;
    }

    public function verDatos($filter=false, $limit=false){
        $db = db::singleton();
        $sql = "SELECT uid_documento, nombre, flags, uid_documento_atributo, alias, uid_modulo_origen, uid_modulo_destino, uid_elemento_origen, duracion, caducidad_manual,
                obligatorio, descargar, codigo, uid_empresa_propietaria, relevante, recursividad, certificacion, no_relacionar, uid_documento_atributo_ejemplo, fecha, activo
                FROM ". TABLE_DOCUMENTO . " INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO . " USING(uid_documento)
                WHERE uid_documento = ". $this->getUID() ."
                ";

        if( is_traversable($filter) ){
            foreach($filter as $col => $val){
                $sql .= " AND $col = '". db::scape($val) ."'";
            }
        }

        if( $limit ){
            $sql .= "LIMIT ". reset($limit) . ", ". end($limit);
        }

        $data = $db->query($sql, true);
        if (count($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = array_map("utf8_encode", $data[$key]);
            }
        }

        return $data;
    }

    public function obtenerObjeto(){
        return $this->elementoFiltro;
    }

    public function numeroSolicitantes(){
        return count($this->datos);
    }

    /** RETORNA LOS ELEMENTOS QUE "TIPICAMENTE" MOSTRAMOS DE ESTE ELEMENTO PARA VER EN MODO INLINE

        COMO LOS DOCUMENTOS PUEDEN ESTAR REFERENCIADOS DESDE VARIOS PUNTOS, LA VARIABLE $solicitud NOS INDICA
        SI DEBEMOS MOSTRAR LOS DATOS INLINE PARA EL ELEMENTO FILTRO (subida) O PARA EL SOLICITANTE (descarga)

        $data NOS AYUDA A PASAR DATOS A ESTA FUNCION

    */
    public function getInlineArray(Iusuario $usuario = NULL, $subida=false, $data=false){
        $comefrom = isset($data[Ilistable::DATA_COMEFROM]) ? $data[Ilistable::DATA_COMEFROM] : false;
        $reference = isset($data[Ilistable::DATA_REFERENCE]) ? $data[Ilistable::DATA_REFERENCE] : false;
        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

        $inlineArray = array();
        $tpl = Plantilla::singleton();

        if ($subida) {
            $elementosSolicitantes = array(); //Mostrar resumen de todos los solicitantes de este documento para este elemento

            $solicitudes = ($reference instanceof solicituddocumento) ? array($reference) : $this->obtenerSolicitudDocumentos($this->elementoFiltro, $usuario, $data["filtro"]);
            $reqType = new requirementTypeRequest($solicitudes, $this->elementoFiltro);

            $countSolicitudes = count($solicitudes);

            // Ajustamos la longitud de los textos, segun el número de solicitantes
            $max = $countSolicitudes > self::MAX_REQUEST_INLINE ? self::MAX_REQUEST_INLINE : $countSolicitudes;
            $namelength = 50 - (10 * $max);
            $dropdown = false;

            foreach ($solicitudes as $i => $solicitud) {

                // Indicamos al usuario que hay mas solicitudes..
                if ($i) {
                    $elementosSolicitantes[] = array(
                        "nombre"        => "+". ($countSolicitudes-1),
                        "className"     => "treerow",
                        "title"         => $tpl("ver_todos"),
                        "href"          => "../agd/requests.php?m={$this->elementoFiltro->getModuleName()}&o={$this->elementoFiltro->getUID()}&poid={$this->getUID()}"
                    );

                    // indicamos que hay que desplegar
                    $dropdow = true;

                    break;
                }



                $attr = $solicitud->obtenerDocumentoAtributo();
                $solicitante = $attr->getElement();
                $estadoID = $solicitud->getStatus();
                $anexo = $solicitud->getAnexo();
                $isRenovation = $anexo && $anexo->isRenovation() ? true : false;
                $ownerCompany = $attr->getCompany();
                $companyName = $ownerCompany->getUserVisibleName();
                $image = "";

                /*$inlineData = array(
                    //"extra" => $imagenesRevision,
                    "nombre" => string_truncate($nombreSolicitante, $namelength),
                    "title" => $nombreSolicitante,
                    //"oid" => $solicitante->getUID(),
                    "tipo" => $solicitante->getType(),
                    "estadoid" => $estadoID,
                    "estado" => documento::status2String( $estadoID )
                );

                if ($usuario->getCompany()->getStartIntList()->contains($attr->getCompany()->getUID())) {
                    $inlineData["oid"] = $solicitante->getUID();
                }/**/


                if ($context == Ilistable::DATA_CONTEXT_TREE) {
                    $logo = $ownerCompany->obtenerLogo(false);

                    if ($logo) $image = "<img src='{$logo}' alt='' height='14px' style='vertical-align:text-bottom' /> &nbsp;&nbsp; ";

                    $nombreSolicitante = trim($solicitud->getRequestString());
                    $title = $companyName . " - " . $tpl("explicacion_solicitantes");

                    $namelength = 300;
                } else {
                    $nombreSolicitante = trim($solicitante->getUserVisibleNameAbbr());
                    $title =  $nombreSolicitante . " - " . $tpl("explicacion_solicitantes");

                    if ($attr->getOriginModuleName() != 'empresa') {
                        $title = $companyName . ' ' . $title;
                    }
                }

                $elementosSolicitantes[] = array(
                    "tagName" => "span",
                    "className" => "help black-light",
                    "nombre" => $image . string_truncate($nombreSolicitante, $namelength),
                    "title" => $title
                );

                $statusData = $solicitud->getStatusData();

                $elementosSolicitantes[] = array(
                    "tagName" => "span",
                    "nombre" => $statusData['stringStatus'],
                    "title" => $statusData['title'],
                    "className" => "help stat stat_".$estadoID
                );

                if($anexo) {

                    if (isset($usuario) && $usuario->canAccess($solicitud, 'anexar', $solicitud->getElement())) {
                        if ($imageInfo = $anexo->getImageInfo()) {
                            $elementosSolicitantes[] = array(
                                "img" => $imageInfo
                            );
                        }
                    }

                    $isUrgent = $anexo->isUrgent();
                    $rejectReverseStatus = $anexo->getReverseStatus() == documento::ESTADO_ANULADO;
                    $isLikeAttached = $estadoID == documento::ESTADO_ANEXADO || $isRenovation;

                    if ($isLikeAttached && $isUrgent && !$rejectReverseStatus) {
                        $elementosSolicitantes[] = array(
                            "img" => array(
                                "class" => "help",
                                "src" => RESOURCES_DOMAIN . '/img/famfam/lightning_rojo.png',
                                "title" => $tpl("validacion_urgente"),
                                "width" => "10px",
                                "height" => "10px"
                            )
                        );
                    }

                    $revisiones = $anexo->obtenerRevisiones();
                    if( $revisiones && count($revisiones) ){
                        $imagenesRevision = array();
                        foreach($revisiones as $revision){
                            $revisor = $revision->getUser();

                            $revisionImage = array(
                                "class" => "help",
                                "src" => $revisor->getIcon(),
                                "width" => "15px",
                                "height" => "15px",
                                "title" => $tpl("revisado_por"). $revisor->getUserVisibleName()
                            );

                            if ($usuario->compareTo($revisor)) {
                                $class = $this->elementoFiltro->getModuleName();
                                $revisionImage['title']     = $tpl("document_revision_delete");
                                $revisionImage['href']      = "removerevision.php?poid={$revision->getUID()}&m={$class}";
                                $revisionImage['class'] = "box-it clickable";
                            }

                            $elementosSolicitantes[] = array("img" => $revisionImage);
                        }

                    }
                }
            }


            // Montar el dropdow si procede
            /*if ($dropdow && $this->elementoFiltro instanceof elemento) { // accion ver informacion

                // get last inline
                $last =& $elementosSolicitantes[count($elementosSolicitantes)-1];

                // apply attrs to bind events
                $elementosSolicitantes["className"] = "treerow";
                $last["title"] = $tpl("ver_todos");
                $last["className"] = "clickable";
                //$last["src"] = "../agd/requests.php?m={$this->elementoFiltro->getModuleName()}&o={$this->elementoFiltro->getUID()}&poid={$this->getUID()}";
                $elementosSolicitantes["href"] = "../agd/requests.php?m={$this->elementoFiltro->getModuleName()}&o={$this->elementoFiltro->getUID()}&poid={$this->getUID()}&inline=1";
            }*/


            // ----- COMENTARIOS
            $comentariosDocumento = array();
            $accion = $usuario->accesoAccionConcreta($this->elementoFiltro->getModuleName()."_documento", 17);

            if ($this->elementoFiltro instanceof elemento && $accion) { // accion ver comentarios
                $comment = $reqType->getComments($usuario, false, 1)->get(0);

                if ($comment && !$comment->isDeleted() && ($commentText = $comment->getComment())) {
                    $commentText = string_truncate($commentText, 80);
                    $commentText = html_entity_decode($commentText);

                    $comentariosDocumento = array(
                        "className" => "goto clickable center",
                        "href" => "#documentocomentario.php?m=". $this->elementoFiltro->getModuleName() ."&o=".$this->elementoFiltro->getUID()."&poid=". $this->getUID(),
                        array()
                    );

                    if ($reference instanceof solicituddocumento) {
                        $comentariosDocumento["href"] .= "&req=" . $reference->getUID();
                    }

                    $commentUser = $comment->getCommenter();
                    if ($commentUser && $usuario->compareTo($commentUser)) {
                        $comentariosDocumento["img"] = array(
                            "src" => RESOURCES_DOMAIN . "/img/famfam/comments-byn.png"
                        );
                        $comentariosDocumento["title"] = $tpl("you") .": ". $commentText;
                    } elseif ($commentUser) {
                        $comentariosDocumento["img"] = array(
                            "src"           => RESOURCES_DOMAIN . "/img/famfam/comments.png",
                            "className"     => "comment-message-link"
                        );
                        $comentariosDocumento["title"] = trim($commentUser->getName()) .": ". $commentText;
                    }

                    $comentariosDocumento["img"]["height"] = "16";
                    $comentariosDocumento["img"]["width"] = "16";

                }


            }

            // Guardamos los datos inline en el array de retorno
            $inlineArray[] = $elementosSolicitantes;
            $inlineArray[] = $comentariosDocumento;

        } else {
            //$comeFrom = ( isset($data["comefrom"]) ) ? $data["comefrom"] : false;
            //$estados = $this->obtenerEstadoDocumentos(  );
            $estatus = $this->getStatusFor( $this->elementoFiltro );
            if( !$estatus ){
                $estatus = $this->getStatusFor( $this->elementoFiltro, false, true);
            }
            $estado = self::status2string( $estatus );

            $inlineArray["estado"] = array(
                array( "nombre" => $estado )
            );


            $inlineArray["elemento"] = array(
                array( "nombre" => $this->elementoFiltro->getUserVisibleName() )
            );
        }
        //dump( $this->informacionArchivo() );




        return $inlineArray;
    }


    public function obtenerFormatosPermitidos($uidDocumentoAtributo){
        $documentoAtributo = new documento_atributo($uidDocumentoAtributo);
        $formatosPermitidos = $documentoAtributo->obtenerFormatosAsignados($uidDocumentoAtributo);
        return $formatosPermitidos;
    }

    public function obtenerDocumentoatributos( $usuario = false, $descargar = false, $papelera = false, $filtro = false ){
        if( $this->elementoFiltro ){
            $cacheString = "solic-".$this->getUID()."-".$this->elementoFiltro->getType()."-".$this->elementoFiltro->getUID()."-".$descargar."-".$papelera;
        } else {
            $cacheString = "solic-".$this->getUID()."-".$descargar."-".$papelera;
        }
        if( $filtro && $filtro instanceof elemento ){
            $cacheString .= "-".$filtro->getUID()."-".$filtro->getType();
        }

        $dato = $this->cache->getData($cacheString);
        if( $dato !== null ){
            return ArrayObjectList::factory($dato);
        }

        $order = " uid_empresa_propietaria ";

        $coleccionAtributos = new ArrayObjectList;
        $solicitantes = $this->obtenerSolicitantes( $usuario, $descargar, $papelera, $filtro, false, $order );
        foreach($solicitantes as $solicitante){
            //if( ( is_object($filtro) && $filtro->getType() == $solicitante->getType() && $solicitante->getUID() && $filtro->getUID() ) || !$filtro ){
            if( ( is_object($filtro) && util::comparar( $filtro,$solicitante )  && $filtro->getUID() ) || !$filtro || (is_array($filtro) && count($filtro)) ){
                $documentoAtributo = new documento_atributo( $solicitante->atributoDocumento["uid_documento_atributo"] );
                    if( isset($solicitante->referencia) ) $documentoAtributo->referencia = $solicitante->referencia;
                    if( isset($solicitante->empresa) ) $documentoAtributo->empresa = $solicitante->empresa;
                $coleccionAtributos[] = $documentoAtributo;
            }
        }

        $this->cache->addData($cacheString, "$coleccionAtributos");
        return $coleccionAtributos;
    }


    public function obtenerSolicitantes( $usuario = false, $descargar=false, $papelera = false, $filtro = null, $anexarDescarga=false , $order=false){
        $usercache = ( $usuario instanceof usuario ) ? $usuario->getUID() : "-";
        $cacheString = $this->getUID() . "-usuario-$usercache-$descargar-$papelera-";

        if (isset($filtro) && $filtro instanceof elemento) {
            $cacheString .= "-".$filtro->getUID()."-".$filtro->getType();
        }

        if( null !== ($dato = $this->cache->getData($cacheString)) ){
            return $dato;
        }

        $descargar = (int) $descargar;
        $papelera = (int) $papelera;


        $campos = "uid_documento_atributo, uid_documento, alias, uid_modulo_origen, uid_modulo_destino, uid_elemento_origen, duracion, obligatorio, descargar, codigo, da.uid_empresa_propietaria, relevante, recursividad, fecha,
                        ( SELECT nombre FROM ". TABLE_DOCUMENTO ." dc WHERE dc.uid_documento = da.uid_documento ) as nombre";
        //uid_documento_atributo    uid_documento   alias   uid_modulo_origen   uid_modulo_destino  uid_elemento_origen
        //duracion  obligatorio     descargar   codigo  uid_empresa_propietaria relevante   recursividad    fecha
        $sql = "SELECT $campos";

        if( !$descargar && $this->elementoFiltro ){ $sql .= ", uid_agrupador, uid_empresa_referencia "; }

        $sql .= "
            FROM ". TABLE_DOCUMENTO_ATRIBUTO ." da
        ";
        if( !$anexarDescarga && $this->elementoFiltro ){
            $sql .=" INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS . "
                USING ( uid_documento_atributo, uid_modulo_destino )
            ";
        }

        if( $descargar ){
            $sql .= " WHERE (   uid_documento = ". $this->getUID() ." AND descargar = 1 ) ";
            if( $this->elementoFiltro && !$anexarDescarga ){
                $sql .= " AND ( uid_elemento_destino = ". $this->elementoFiltro->getUID() .")";
            }
            if( is_array($filtro) && $filtro["alias"] ){
                $sql .= " AND alias LIKE '%". db::scape($filtro["alias"]) ."%'";
            }
        } else {

            if( $this->elementoFiltro ){
                $sql .= "
                        WHERE (
                            uid_elemento_destino = ". $this->elementoFiltro->getUID() ."
                            AND uid_documento = ". $this->getUID() ."
                            AND uid_modulo_destino = ". $this->elementoFiltro->getModuleId() ."
                        )
                        AND ( descargar = $descargar AND papelera = $papelera )
                    ";
            } else {
                // ES POSIBLE QUE ESTO TENGA QUE SER REVISADO
                $sql .= "
                        WHERE (
                            uid_documento = ". $this->getUID() ."
                            AND uid_modulo_destino
                        )
                        AND ( descargar = $descargar )
                ";
            }

        }


        if( $filtro instanceof documento_atributo ){
            $sql .= " AND uid_documento_atributo = ". $filtro->getUID();
        } elseif ( $filtro instanceof agrupador ){
            $sql .= " AND (
                ( uid_elemento_origen = ". $filtro->getUID() ." AND uid_modulo_origen = ". $filtro->getModuleId() ." ) OR ( uid_agrupador = ". $filtro->getUID() ." )
            ) ";
        } elseif ( is_array($filtro) ){
            foreach( $filtro as $campo => $valor ){
                switch( $campo ){
                    case "estado":
                        // INCIDENCIA 1227.0 -- Envia mas documentos de los que debe, aunque no es el problema ni lo resuelve este otro relacionado
                    break;
                }
            }

        }

        $sql .= " AND da.activo = 1 ";

        if( $usuario instanceof usuario ){
            if( $descargar ){
                $intList = $usuario->getCompany()->getStartIntList();
                $sql .= " AND da.uid_empresa_propietaria IN ({$intList->toComaList()})";
            } elseif ($this->elementoFiltro instanceof Isolicitable) {
                $sql .= $usuario->obtenerCondicionDocumentos();
            }
        }


        if( $descargar ){
            $sql .= " GROUP BY uid_documento_atributo";
        } else {
            $sql .= " AND da.replica = 0";
        }

        if( $order ){
            $sql .= " ORDER BY ".$order;
        }

        $data = $this->db->query($sql, true);


        $solicitantes = $agrupadores = array();

        foreach($data as $i => $info ){
            $modulo = util::getModuleName($info["uid_modulo_origen"]);
            $solicitante = new $modulo($info["uid_elemento_origen"]);
            if( isset($info["uid_agrupador"]) && $info["uid_agrupador"] ){
                $agrupadorReferencia = new agrupador($info["uid_agrupador"]);
                $solicitante->referencia = $agrupadorReferencia;
            }

            /*
            if( $info["uid_empresa_referencia"] ){
                $empresaReferencia = new empresa($info["uid_empresa_referencia"]);
                $solicitante->empresa = $empresaReferencia;
            }*/

            $solicitante->atributoDocumento = $info;
            $solicitantes[] = $solicitante;

            // Guardar todos los agrupadores asignados
            if( $solicitante instanceof agrupador ){
                $agrupadores[] = $solicitante;
            }
        }


        if( !$descargar && $usuario instanceof usuario && is_object($this->elementoFiltro) ){
            // Esto es necesario si tenemos agrupadores asignados de forma automatica
            $list = ( count($agrupadores) ) ? implode(",", elemento::getCollectionIds($agrupadores)) : "0";
            $sql = "
                SELECT a.uid_agrupador, $campos
                FROM ".TABLE_AGRUPADOR." a
                INNER JOIN ".TABLE_AGRUPAMIENTO."_modulo am
                USING (uid_agrupamiento)
                INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." da
                ON da.uid_elemento_origen = a.uid_agrupador AND da.uid_modulo_origen = 11
                WHERE am.uid_modulo = {$this->elementoFiltro->getModuleId()}
                AND da.uid_documento = {$this->getUID()}
                AND a.autoasignacion = 1
                AND a.uid_agrupador NOT IN ($list)
                AND a.uid_empresa = {$usuario->getCompany()->getUID()}
                GROUP BY a.uid_agrupador
            ";

            $lineasDatos = $this->db->query($sql, true );
            foreach( $lineasDatos as $i => $info ){
                $agrupador = new agrupador( $info["uid_agrupador"] );
                $agrupador->atributoDocumento = $info;
                $solicitantes[] = $agrupador;
            }
        }


        //$this->cache->addData($cacheString, $solicitantes);
        return $solicitantes;
    }


    public function anular( $solicitantes = false, $usuario = false ){
        return $this->cambiarEstado(4, $solicitantes, $usuario);
    }

    public function validar( $solicitantes = false, $usuario = false ){
        return $this->cambiarEstado(2, $solicitantes, $usuario);
    }

    public function caducar( $solicitantes = false ){
        return $this->cambiarEstado(3, $solicitantes);
    }

    /** SI TENEMOS UN DOCUMENTO VALIDADO / ANULADO Y QUEREMOS PONERLO COMO ANEXADO POR ALGUN MOTIVO**/
    public function desvalidar( $solicitantes = false ){
        return $this->cambiarEstado(1, $solicitantes);
    }

    /** LA FUNCION EXISTE, DE ESTA MANERA ES MAS RAPIDA, NO NOS INTERSA CONOCER DETALLES DE CLIENTES, ES CON FINES INTERNOS **/
    public function getDocumentStatus(){
        $uidmodulo = $this->elementoFiltro->getModuleId();
        $modulo = $this->elementoFiltro->getModuleName();
        $uid = $this->elementoFiltro->getUID();

        $sql = "SELECT estado FROM (
                SELECT uid_documento_atributo, uid_elemento_destino as uid_$modulo, uid_agrupador FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
                INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." USING(uid_documento_atributo, uid_modulo_destino)
                WHERE uid_modulo_destino = $uidmodulo
                AND uid_elemento_destino = $uid
                AND uid_documento = ". $this->getUID() ."
                AND descargar = 0
            ) as attr
            INNER JOIN ". PREFIJO_ANEXOS ."$modulo
            USING(uid_documento_atributo, uid_$modulo, uid_agrupador)
            GROUP BY estado
        ";

        $estados = $this->db->query($sql, "*", 0);
        return $estados;
    }

    public function cambiarEstado( $estado, $solicitantes, $usuario = false ){

        if( $solicitantes ){
            $seleccionados = $solicitantes;
        } else {
            $seleccionados = $this->verSolicitantesSeleccionados();
        }

        $totalFilasAfectadas = 0;
        foreach( $seleccionados as $solicitante ){
            $modulo = $this->elementoFiltro->getType();
            foreach( $this->datos as $atributos ){
                if( $atributos["uid_modulo_origen"] == $solicitante->getModuleId() && $atributos["uid_elemento_origen"] == $solicitante->getUID() && !$atributos["descargar"] ){
                    $attr = new documento_atributo($atributos["uid_documento_atributo"]);

                    $uidRef = ( $solicitante->referencia ) ? $solicitante->referencia->getUID() : 0;

                    $sql = "    SELECT estado FROM  ". PREFIJO_ANEXOS . $modulo ."
                                WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
                                AND uid_$modulo = ".$this->elementoFiltro->getUID() ." AND uid_agrupador = $uidRef";

                    $estadoActual = $this->db->query($sql, 0, 0);

                    if( $estadoActual == documento::ESTADO_CADUCADO ){
                        throw new Exception("documento_caducado_no_validable");
                    }

                    $sql = "    UPDATE ". PREFIJO_ANEXOS . $modulo ." SET estado = $estado, fecha_actualizacion = NOW()
                                WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
                                AND uid_$modulo = ".$this->elementoFiltro->getUID() ." AND uid_agrupador = $uidRef";

                    $resultset = $this->db->query( $sql );

                    if( $resultset ){
                        if( $anexo = $attr->getAnexo($this->elementoFiltro, $solicitante->referencia) ){
                            $anexo->clearItemCache();
                            $anexo->writeLogUI( logui::ACTION_STATUS_CHANGE, $estado, $usuario);
                        }
                        $totalFilasAfectadas += $this->db->getAffectedRows();
                    } else {
                        return $this->db->lastErrorString();
                    }

                }
            }
        }

        return $totalFilasAfectadas;
    }

    public static function obtenerSolicitanteDesdeAtributo( $atributo ){
        $modulo = self::obtenerNombreModulo( $atributo["uid_modulo_origen"] );
        $object = new $modulo( $atributo["uid_elemento_origen"], false);
        $object->atributoDocumento = $atributo;
        return $object;
    }

    public function obtenerSolicitanteDesdeIdAtributo( $idatributo ){
        foreach( $this->datos as $dato ){
            if( $dato["uid_documento_atributo"] == $idatributo ){
                return self::obtenerSolicitanteDesdeAtributo( $dato );
            }
        }
        return false;
    }

    public function obtenerIdAtributos(){
        $idAtributos = array();
        foreach( $this->datos as $dato ){
            $idAtributos[] = $dato["uid_documento_atributo"];
        }
        return $idAtributos;
    }

    public function hasAttribute( $campo, $valor = false, $usuario ){
        $solicitantes = $this->obtenerSolicitantes( $usuario );
        foreach( $solicitantes as $solicitante ){
            $atributo = $solicitante->atributoDocumento;
            //definimos el estado como una propiedad mas del atributo del documento
            $atributo["estado"] = $this->getStatusFor( $solicitante );
            //definimos las etiquetas
            $atributo["etiqueta"] = $this->obtenerEtiquetas($solicitante)->toIntList()->getArrayCopy();

            //COMPARAMOS
            if( isset($atributo[$campo]) ){
                if( $valor !== false ){
                    if( is_array( $atributo[$campo] ) ){
                        if( in_array( $valor, $atributo[$campo]) ){
                            return $atributo[$campo];
                        }
                    } else {
                        if( $atributo[$campo] == $valor ){
                            return $atributo[$campo];
                        }
                    }
                } elseif( $valor == false ){
                    if( isset($atributo[$campo]) ){
                        return $atributo[$campo];
                    }
                }
            }
        }
        return false;
    }

    /**
      * Crea una relacion de anexo - elemento, basada en uid_elemento,
      */
    public function revisadoPor(usuario $usuario){
        $tipo = strtolower($this->elementoFiltro->getType());
        $tabla = PREFIJO_ANEXOS_ATRIBUTOS . $tipo;

        $solicitantes = $this->verSolicitantesSeleccionados();
        foreach( $solicitantes as $solicitante ){
            $info = (object ) $this->informacionArchivo($solicitante);
            $atributo = (object) $solicitante->atributoDocumento;
            $attr = new documento_atributo($atributo->uid_documento_atributo);

            $uidReferencia = ( $solicitante->referencia ) ? $solicitante->referencia->getUID() : 0;
            $sql = "INSERT INTO $tabla ( uid_$tipo, uid_documento_atributo, uid_agrupador, fecha_anexion, uid_usuario ) VALUES (
                ". $this->elementoFiltro->getUID()  .", ". $atributo->uid_documento_atributo .", $uidReferencia, ". $info->fecha_anexion .", ". $usuario->getUID() ."
            )";

            if( !$this->db->query($sql) ){
                return $this->db->lastErrorString();
            } else {
                if( $anexo = $attr->getAnexo($this->elementoFiltro, $solicitante->referencia) ){
                    $anexo->writeLogUI( logui::ACTION_REVISAR, NULL, $usuario);
                }
            }
        }

        return true;
    }

    public function yaRevisadoPor(usuario $usuario, $solicitante){
        $usuarios = $this->obtenerUsuariosRevision($solicitante);
        $uidUsuarios = elemento::getCollectionIds($usuarios);
        if( in_array($usuario->getUID(), $uidUsuarios) ){
            return true;
        } else {
            return false;
        }
    }


    public function obtenerUsuariosRevision($solicitante, $usuario=false){
        // ---- Todos los usuarios con revision
        $coleccionUsuarios = array();

        $tipo = strtolower($this->elementoFiltro->getType());
        $tabla = PREFIJO_ANEXOS_ATRIBUTOS . $tipo;

        //$solicitantes = $this->obtenerSolicitantes($usuario);
        //foreach( $solicitantes as $solicitante ){

            //if( util::comparar($solicitanteRevision,$solicitante) && ( ( !$solicitanteRevision->referencia && $solicitante->referencia )  || util::comparar(@$solicitante->referencia,@$solicitanteRevision->referencia) ) ){
                $atributo = (object) $solicitante->atributoDocumento;

                /*
                        UNION
                        SELECT uid_empleado, uid_documento_atributo, fecha_anexion
                        FROM ". PREFIJO_ANEXOS_HISTORICO ."$tipo a
                        WHERE uid_documento_atributo = ". $atributo->uid_documento_atributo ."
                        AND uid_empleado = ". $this->elementoFiltro->getUID() ."
                */
                $referencia = ( isset($solicitante->referencia) ) ? $solicitante->referencia->getUID() : 0;
                $sql = "SELECT uid_usuario
                    FROM $tabla as relacion
                    INNER JOIN (
                        SELECT uid_$tipo, uid_documento_atributo, fecha_anexion
                        FROM ". PREFIJO_ANEXOS ."$tipo a
                        WHERE uid_documento_atributo = ". $atributo->uid_documento_atributo ."
                        AND uid_$tipo = ". $this->elementoFiltro->getUID() ."
                        AND uid_agrupador = $referencia
                    ) as anexo
                    USING( uid_$tipo, uid_documento_atributo, fecha_anexion )
                    WHERE 1
                    AND uid_agrupador = $referencia
                    ";

                $arrayIDUsuarios = $this->db->query($sql, "*", 0);
                foreach( $arrayIDUsuarios as $uid ){
                    $usuario = new usuario($uid, false);
                    $coleccionUsuarios[] = $usuario;
                }
            //}
        //}

        return $coleccionUsuarios;
    }


    public function comprobarFormato($uidDocumentoAtributo, $documentoTipo) {

        $cacheString = __CLASS__."-".__FUNCTION__."-".$this->getUID()."-".$uidDocumentoAtributo."-".$documentoTipo;
        if (($dato = $this->cache->getData($cacheString)) !== null) return $dato;

        $coleccionFormatos = $this->obtenerFormatosPermitidos($uidDocumentoAtributo);
        $result = false;
        if( true === is_countable($coleccionFormatos) && count($coleccionFormatos) != 0 ) {
            foreach($coleccionFormatos as $formato) {
                if( $formato->getUserVisibleName() == $documentoTipo ) {
                    $result = true;
                }
            }
        } else { $result = true; }

        $this->cache->set($cacheString, $result, 60);
        return $result;
    }

    /**
    * Hace uso del superglobal $_REQUEST ['fecha','comentario','caducidad','duracion']
    * Hace uso del superglobal $_GET ['src']
    * @param mixed $arrayDatosArchivo ['name','size','type','error','tmp_name']
    * @param bool $descargar true si estamos anexando un archivo de descarga
    * @param bool $atributo se pasa como $filtro a obtenerSolicitantes
    * @param bool|usuario $usuario se utiliza solo para el LogUI
    * @return bool|string true si todo va bien o cadena de error en caso contrario
    */
    public function anexar($arrayDatosArchivo, $descargar = false, $atributo = false, $usuario = false)
    {
        if ($arrayDatosArchivo["error"]) {
            return "error_sin_archivo";
        }

        if (!isset($_REQUEST["fecha"]) || !trim($_REQUEST["fecha"])) {
            if (!$descargar) {
                return "error_falta_fecha";
            }
        } else {
            $fechaEmision = documento::parseDate($_REQUEST["fecha"]);

            if (!is_numeric($fechaEmision)) { //sera un error
                return $fechaEmision;
            }
        }

        //trabajamos con el archivo
        $splittedName = explode(".", $arrayDatosArchivo['name']);
        $relativePath = self::obtenerNombreModulo($this->elementoFiltro->getModuleId()) . "/uid_" . $this->elementoFiltro->getUID() . "/";
        $rutaCarpeta = DIR_FILES . $relativePath;
        $fileDBName = time() . "." . end($splittedName);
        $rutaArchivo = $rutaCarpeta . $fileDBName;
        $sqlFileName = $relativePath . $fileDBName;
        $file = $arrayDatosArchivo['tmp_name'];
        $s3 = archivo::getS3();


        // Para optimizar el tiempo de espera, si tienemos S3 activado copiamos directamente los ficheros
        // Si comentamos este IF el entorno local y el de producción deberían seguir funcionando, de forma mas lenta
        if ($s3) {
            // Copy tmp file and remove it
            if (!archivo::tmp($file, null, $sqlFileName)) {
                throw new Exception("error_copiar_archivo");
            }
        } else {
            // Recover temporary file
            if (!$filedata = archivo::tmp($file)) {
                throw new Exception("error_copiar_archivo");
            }

            // Write to final destination
            if (!archivo::escribir($rutaArchivo, $filedata, true)) {
                throw new Exception("error_copiar_archivo");
            }
        }

        $fileHash = isset($arrayDatosArchivo['md5_file']) ? $arrayDatosArchivo['md5_file'] : md5($fileDBName);


        // Make sure we have the file
        if (!archivo::is_readable($rutaArchivo)) {
            throw new Exception("error_leer_archivo");
        }

        $modulosConHistorico = util::getAllModules();

        if ($descargar) {
            $modulo = $this->elementoFiltro->getModuleName();
            $seleccionados = $this->obtenerSolicitantes(false, true, false, $atributo, true);
        } else {
            $modulo = $this->elementoFiltro->getModuleName();
            // Upload por ajax (directo)..
            if (isset($_GET["src"]) && $_GET["src"] == "ajax") {
                $seleccionados = $this->obtenerSolicitantes();
            } else {
                $seleccionados = $this->verSolicitantesSeleccionados();
            }
        }

        if (!count($seleccionados)) {
            return "no_solicitante_documento";
        }

        foreach ($seleccionados as $solicitante) {
            $tipoSolicitante = $solicitante->getModuleName();
            $atributos = $solicitante->atributoDocumento;

            $attr = new documento_atributo($atributos["uid_documento_atributo"]);

            $requirement = $attr->asDomainEntity();

            if (false === $requirement->canHaveAttachment()) {
                continue;
            }

            $formatIsAccepted = $this->comprobarFormato(
                $atributos["uid_documento_atributo"],
                archivo::getMimeType($rutaArchivo)
            );

            if (false === $formatIsAccepted) {
                if (count($seleccionados) === 1) {
                    return "formato_documento_no_permitido";
                }

                continue;
            }

            if ($attr->caducidadManual()) {
                // si esta establecido
                if (isset($_REQUEST["caducidad"])
                    && isset($_REQUEST["caducidad"][$tipoSolicitante])
                    && isset($_REQUEST["caducidad"][$tipoSolicitante][$solicitante->getUID()])
                ) {
                    $caducidad = $_REQUEST["caducidad"][$tipoSolicitante][$solicitante->getUID()];
                    $fechaCaducidad = documento::parseDate($caducidad);
                    if ($fechaEmision > $fechaCaducidad) {
                        return "error_fecha_caducidad";
                    } else {
                        $fechaExpiracion = $fechaCaducidad;
                    }
                } else {
                    return "sin_fecha_caducidad";
                }
            } else {
                $duraciones = $duracion = null;

                if (isset($_REQUEST["duracion"])) {
                    if (isset($_REQUEST["duracion"][$tipoSolicitante])
                        && isset($_REQUEST["duracion"][$tipoSolicitante][$solicitante->getUID()])
                    ) {
                        $duracion = $_REQUEST["duracion"][$tipoSolicitante][$solicitante->getUID()];
                        $duraciones = $this->obtenerDuraciones($solicitante);
                    }
                }
                if (!is_array($duraciones) || !count($duraciones) || !in_array($duracion, $duraciones)) {
                    $duracion = $atributos["duracion"];
                }

                $segundosParaCaducar = ((int)$duracion)*24*60*60;
                if (!$descargar) {
                    $fechaExpiracion = ( $duracion ) ? ($fechaEmision+$segundosParaCaducar) : 0;
                } else {
                    $fechaExpiracion = 0;
                }
            }

            if ($fechaExpiracion && time() > $fechaExpiracion) {
                return "actualmente_caducado";
            }


            if ($descargar) {
                //$moduloDestino = ( $modulo != "empresa" ) ? util::getModuleName($atributos["uid_modulo_destino"]) : $modulo;
                $moduloDestino = util::getModuleName($atributos["uid_modulo_destino"]);
                //if( $atributos["uid_modulo_origen"] ==

                $camposTabla = "uid_documento_atributo, archivo, estado, uid_empresa, uid_agrupador, uid_empresa_referencia, hash, nombre_original, fecha_anexion, fecha_emision_real";
            } else {
                $camposTabla = "uid_documento_atributo, archivo, estado, uid_$modulo, uid_agrupador, uid_empresa_referencia, hash, nombre_original, fecha_actualizacion, fecha_emision, fecha_anexion, fecha_emision_real, fecha_expiracion";
            }

            $ref = (int) @$atributos["uid_agrupador"];
            $empresaRef = (int) @$atributos["uid_empresa_referencia"];

            if (in_array($modulo, $modulosConHistorico)) {
                //---------- copiamos el documento actual a la tabla del historico solo si es de uno de los modulos con docs
                $table = $descargar ? PREFIJO_ANEXOS . "historico_empresa" : PREFIJO_ANEXOS . "historico_$modulo";

                if ($descargar) {
                    $sql = "INSERT IGNORE INTO {$table} ( $camposTabla )
                    SELECT $camposTabla
                    FROM ". PREFIJO_ANEXOS ."empresa  WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
                    AND uid_agrupador = $ref
                    AND uid_empresa = 0";
                } else {
                    $sql = "INSERT IGNORE INTO {$table} ( $camposTabla )
                    SELECT $camposTabla
                    FROM ". PREFIJO_ANEXOS . $modulo ." WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
                    AND uid_agrupador = $ref
                    AND uid_$modulo = ".$this->elementoFiltro->getUID();
                }

                if (!$this->db->query($sql)) {
                    return "error_guardar_historico";
                }

                // --- Nueva primary key de nuestro archivo en el historico...
                $primaryKeyHistorico = $this->db->getLastId();
            }

            //---------- insertamos el nuevo registro
            if ($descargar) {
                //----------- borramos de la tabla actual el registro solo si es de uno de los modulos con docs
                $sql = "DELETE FROM ". PREFIJO_ANEXOS . "empresa" ." WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
                AND uid_empresa = 0 AND uid_agrupador = $ref";
                if (!$this->db->query($sql)) {
                    return "error_limpiar_actual_descarga";
                }

                $sql = "INSERT INTO ". PREFIJO_ANEXOS . "empresa" ." ( $camposTabla )   VALUES (
                ".$atributos["uid_documento_atributo"].", '$sqlFileName', 1, 0, $ref, 0, '$fileHash', '". utf8_decode(db::scape($arrayDatosArchivo['name'])) ."', ". time().", '0' )";
            } else {
                if (in_array($modulo, $modulosConHistorico)) {
                    //----------- borramos de la tabla actual el registro solo si es de uno de los modulos con docs
                    $sql = "DELETE FROM ". PREFIJO_ANEXOS . $modulo ." WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
                    AND uid_$modulo = ".$this->elementoFiltro->getUID() . " AND uid_agrupador = $ref";
                    if (!$this->db->query($sql)) {
                        return "error_limpiar_actual";
                    }
                }

                $sql = "INSERT INTO ". PREFIJO_ANEXOS . $modulo ." ( $camposTabla ) VALUES (
                ".$atributos["uid_documento_atributo"].", '$sqlFileName', 1, ".$this->elementoFiltro->getUID().", $ref, 0, '$fileHash', '". utf8_decode(db::scape($arrayDatosArchivo['name']))."', NOW(), $fechaEmision, ".time().", '0', $fechaExpiracion)";
            }

            if (!$this->db->query($sql)) {
                return "error_guardar_nuevo_archivo";
            } else {
                if ($descargar && ($list = $attr->getChilds($usuario)) && count($list)) {
                    //----------- borramos de la tabla actual el registro solo si es de uno de los modulos con docs
                    $sql = "DELETE FROM ". PREFIJO_ANEXOS . $moduloDestino ." WHERE uid_documento_atributo IN ( ". $list->toComaList() ." )
                    AND uid_$moduloDestino = 0 AND uid_agrupador = $ref";
                    if (!$this->db->query($sql)) {
                        return "error_limpiar_actual";
                    }

                    $sql = "INSERT INTO ". PREFIJO_ANEXOS . $moduloDestino ." ( $camposTabla )
                        SELECT
                        uid_documento_atributo, '$sqlFileName', 1, 0, $ref, '$fileHash', '{$arrayDatosArchivo['name']}', ". time().", '0'
                        FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
                        WHERE uid_documento_atributo IN ( ". $list->toComaList() ." )
                    ";

                    if (!$this->db->query($sql)) {
                        // Lanzariamos una excepcion
                    }
                } elseif (!$descargar) {
                    $primaryKey = $this->db->getLastId();
                    $anexo = new anexo($primaryKey, $this->elementoFiltro);
                    $anexo->writeLogUI(logui::ACTION_CREATE, null, $usuario);
                }

                if ($descargar) {
                    $attr->writeLogUI(logui::ACTION_UPLOAD, null, $usuario);
                }
            }
        }

        return true;
    }

    public function obtenerUrlPublica($usuario){
        if (!$this->elementoFiltro) return parent::obtenerUrlPublica($usuario);

        $tipo = strtolower($this->getType());
        $modulo = $this->elementoFiltro->getModuleName();
        $uid = $this->elementoFiltro->getUID();
        return CURRENT_DOMAIN . "/agd/#buscar.php?q=tipo:anexo-$modulo%20$modulo:$uid%20documento:{$this->getUID()}";
    }

    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array())
    {
        $tableInfo = parent::getTableInfo($usuario, $parent, $extraData);
        $id = $this->getUID();

        if (isset($tableInfo[$id]['description'])) {
            unset($tableInfo[$id]['description']);
        }

        return $tableInfo;
    }

    public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = NULL, $extra = array(), $force = false){
        $reference = isset($data[Ilistable::DATA_REFERENCE]) ? $data[Ilistable::DATA_REFERENCE] : false;

        // Usuario siempre debería existir, lo mantenemos para detectar errors facilmente
        /*if( !isset($usuario) && !$usuario instanceof usuario ){
            die('No se encuentra al usuario para poder filtrar correctamente los documentos');
        }*/

        if ($usuario) $this->setUser( $usuario ); // es accesible desde el objeto
        $reference = isset($data[Ilistable::DATA_REFERENCE]) ? $data[Ilistable::DATA_REFERENCE] : false;


        //implementamos una version diferente de get info para mostrar el nombre del documento mas adecuado
        $info = parent::getInfo( $publicMode, $comeFrom, $usuario );

        $locale = Plantilla::getCurrentLocale();
        $templ = Plantilla::singleton();


        $docName = $this->getUserVisibleName();
        $atributos = $this->obtenerDocumentoatributos( $this->user );

        $m = ($this->elementoFiltro) ? $this->elementoFiltro->getModuleName() : "documento";
        $uid = ($this->elementoFiltro) ? $this->elementoFiltro->getUID() : $this->getUID();
        $docuid = $this->getUID();


        // accion ver informacion
        if ($this->elementoFiltro instanceof Ielemento && $usuario && $accion = $usuario->accesoAccionConcreta($this->elementoFiltro->getModuleName()."_documento", 10)) {
            $href = $accion["href"] . get_concat_char($accion["href"]) . "m=$m&o=$uid&poid=$docuid";
        } else {
            $href = null;
        }


        if (count($atributos) == 1) {
            $informacionAnexado = $this->informacionArchivo( $atributos[0]->getElement() );
            $docName = $informacionAnexado["alias"];

            if ($locale != 'es') {
                $documentoIdioma = new traductor( $atributos[0]->getUID(), $atributos[0] );
                $aliasLocale = $documentoIdioma->getLocaleValue($locale);
                if (trim($aliasLocale)) {
                    $docName = $aliasLocale;
                }
            }
        }


        $charsSpace = 90;
        $docResumed = string_truncate($docName, $charsSpace);
        $innerHTML = "<a href='". $href ."' class='box-it link'>{$docResumed}</a>";

        $descripcion = isset($info[$this->uid]['description']) ? $info[$this->uid]['description'] : $info['description'];

        if ($descripcion) {
            $chars = $charsSpace - strlen($docResumed);

            if ($chars > 5 && $desc = string_truncate($descripcion, $chars))
            $innerHTML .= " <span class='doc-desc' title='{$descripcion}'>{$desc}</span>";
        }

        $info[$this->uid]["nombre"] = array(
            "innerHTML" => $innerHTML
        );

        if (isset($info[ $this->uid ]["determinable"])) unset($info[ $this->uid ]["determinable"]);
        if (isset($info[ $this->uid ]["custom_id"])) unset($info[ $this->uid ]["custom_id"]);
        if (isset($info[ $this->uid ]["keywords"])) unset($info[ $this->uid ]["keywords"]);

        switch( $comeFrom ){
            case "table":
                unset($info[ $this->uid ]["flags"]);

            break;
            case "folder":
                unset( $info[ $this->uid ]["flags"]);
            break;
        }

        if( isset($this->elementoFiltro) ){
            $filtro = isset($extra[Ielemento::EXTRADATA_FILTER]) ? $extra[Ielemento::EXTRADATA_FILTER] : array();
            $info["className"] = $this->getLineClass(false, $usuario, $filtro);
        }

        return $info;
    }

    public function getLineClass($parent, $usuario, $filtro=array()){
        $class = array();
        if( $this->elementoFiltro ){
            $class[] = "drop-area";
        }

        if ($this->isOk($this->elementoFiltro, $usuario, $filtro)) {
            $class[] = "color green";
        } else {
            $class[] = "color red";
        }

        return implode(" ", $class);
    }


    public function getGlobalStatus($usuario, $filtro){
        $badStatus = array(documento::ESTADO_PENDIENTE, documento::ESTADO_ANEXADO, documento::ESTADO_CADUCADO, documento::ESTADO_ANULADO);


        /*
        $solicitantes = $this->obtenerSolicitantes( $usuario, false, false, $filtro );
        foreach( $solicitantes as $i => $solicitante ){
            //dump( "-----------------------> " . $solicitante->getType() . " - " . $solicitante->getUID() . " - " . $solicitante->getUserVisibleName() );
            // ---- Alguien ha revisado el documento?
            //$usuariosRevisores = $this->obtenerUsuariosRevision( $solicitante, $usuario );
            $estadoID = $this->getStatusFor( $solicitante );
            if( in_array($estadoID, $badStatus) ){
                return false;
            }
        }
        return true;
        */
    }

    public function verSolicitantesSeleccionados(){
        $solicitantesSeleccionados = array();
        $solicitantes = $this->obtenerSolicitantes();

        foreach( $solicitantes as $solicitante ){
            $modulo = $solicitante->getType();
            $modulo = urlencode($modulo);
            if( isset($_REQUEST[ $modulo ]) && is_array($_REQUEST[ $modulo ]) ){
                if( $solicitante->referencia ){
                    if( isset($_REQUEST["ref"]) && $_REQUEST["ref"] != $solicitante->referencia->getUID() ){ continue; }
                    $referencia = "referencia-".$solicitante->referencia->getUID();

                    if( array_key_exists($referencia, $_REQUEST[ $modulo ]) ){
                        /*
                        dump("Encontrado a ". $solicitante->getUserVisibleName() ." con referencia " . $solicitante->referencia->getUserVisibleName());
                        dump("El key $referencia esta en esta lista:");
                        dump( array_keys($_REQUEST[ $modulo ]) );
                        */
                        $solicitantesSeleccionados[] = $solicitante;
                    }
                } else {
                    if( isset($_REQUEST["ref"]) && $_REQUEST["ref"] ){ continue; }
                    foreach( $_REQUEST[ $modulo ] as $key => $uid ){
                        //dump("La clave $key del modulo $modulo vale $uid");
                        if( is_numeric($key) && $solicitante->getUID() == $uid ){
                            $solicitantesSeleccionados[] = $solicitante;
                            break;
                        }
                    }
                }

            }
        }


        //echo elemento::getCollectionIds($solicitantesSeleccionados);
        return $solicitantesSeleccionados;
    }

    public function downloadZip( ){

        //nombre temporal del fichero
        $tempName = "/tmp/".time().".zip";

        //creamos el archivo
        if( !($zip = archivo::getZipInstance($tempName)) ){
            return false;
        }

        $seleccionados = $this->verSolicitantesSeleccionados();

        foreach( $seleccionados as $solicitante ){
            $nombreSolicitante = $solicitante->getUserVisibleName();
            $info = $this->downloadFile( $solicitante, false, true );
            $realFilePath = DIR_FILES . $info["path"];

            $fileData = is_readable($realFilePath) ? file_get_contents($realFilePath) : archivo::leer($realFilePath);

            if( $fileData ){
                $zipFileName = archivo::cleanFilenameString($nombreSolicitante) . " - " . archivo::cleanFilenameString($info["alias"]) . "." . $info["ext"];
                $zip->addFromString($zipFileName, $fileData);
            }
        }

        $zip->close();
        unset( $zip );

        archivo::descargar( $tempName, "documentos" );
    }

    public function getAllFiles($usuario=false){
            // Array con todo
            $coleccionArchivos = array();

            if( $usuario instanceof usuario ){
                $solicitantes = $this->obtenerSolicitantes($usuario);
            } else {
                $solicitantes = $this->obtenerSolicitantes();
            }


            foreach( $solicitantes as $solicitante ){
                $info = $this->downloadFile( $solicitante, false, true );

                $realFilePath = DIR_FILES . $info["path"];
                if( isset($realFilePath) && is_readable($realFilePath) && !is_dir($realFilePath) ){
                    $file = new archivo($realFilePath);

                    $name = utf8_decode($solicitante->getUserVisibleName());
                    if( isset($solicitante->referencia) ){
                        $name = "_" . $solicitante->referencia->getUserVisibleName();
                    }
                    $name .= "_" . $info["alias"] . "." . $info["ext"];

                    $file->setRealfilename($name);
                    $coleccionArchivos[] = $file;
                }
            }

            return $coleccionArchivos;
    }

    public function downloadFile( $objeto, $descargar = false, $return = false ){
        if( $descargar ){
            $solicitantes = $this->obtenerSolicitantes(false, true, false, null, true);
        } else {
            $solicitantes = $this->obtenerSolicitantes();
        }

        foreach( $solicitantes as $solicitante ){
            $atributos = $solicitante->atributoDocumento;
            if( util::comparar($objeto, $solicitante) ){

                // Nos aseguramos que es el mismo solicitante y el mismo documento
                if( isset($this->elementoFiltro->atributoDocumento) && $this->elementoFiltro->atributoDocumento["uid_documento_atributo"] != $atributos["uid_documento_atributo"] ){
                    continue;
                }

                // Prevenir un mismo solicitante por varias o ninguna referencia
                if( isset($solicitante->referencia) && ( !isset($objeto->referencia) || $solicitante->referencia->getUID() != $objeto->referencia->getUID() ) ){
                    continue;
                }


                if( $descargar ){
                    $modulo = util::getModuleName($atributos["uid_modulo_destino"]);
                    $modulo = "empresa"; // siempre que es descarga, guardamos en la tabla de empresas
                } else {
                    $modulo = $this->elementoFiltro->getModuleName();
                }

                $tableNameExploded = new ArrayObject(explode('.', PREFIJO_ANEXOS));
                $tableName = end($tableNameExploded);
                if( $atributos["descargar"] ){
                    $sql = "SELECT uid_". $tableName ."$modulo as uid, uid_documento_atributo, archivo, nombre_original, hash, estado FROM ". PREFIJO_ANEXOS . $modulo . "
                    WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"] ." AND ( uid_$modulo = 0 OR uid_$modulo = ". $solicitante->getUID() ." ) ";
                } else {
                    $sql = "SELECT uid_". $tableName ."$modulo as uid, uid_documento_atributo, archivo, nombre_original, hash, estado FROM ". PREFIJO_ANEXOS . $modulo . "
                    WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
                    AND uid_$modulo = ".$this->elementoFiltro->getUID();
                }

                if( isset($objeto->referencia) && $objeto->referencia ){
                    $sql .= " AND uid_agrupador = " . $objeto->referencia->getUID();
                } else {
                    $sql .= " AND uid_agrupador = 0";
                }

                if( $atributos["descargar"] ){
                    $sql .= " LIMIT 1";
                }

                $datos = $this->db->query( $sql, true );

                if( isset($datos[0]["archivo"]) ){

                    $archivo = $datos[0]["archivo"];
                    $fileOriginalName = $datos[0]["nombre_original"];
                    $hash = $datos[0]["hash"];
                    $estado = $datos[0]["estado"];
                    $uidAtributo = $datos[0]["uid_documento_atributo"];

                    $sql = "UPDATE ". PREFIJO_ANEXOS . $modulo . " SET descargas = descargas+1 WHERE uid_". $tableName ."$modulo = ".$datos[0]["uid"];
                    $this->db->query($sql);


                    if( $return ){

                        return array(   "path" => $archivo,
                                        "alias" => str_replace("/","_",$atributos["alias"]),
                                        "ext" => archivo::getExtension($archivo),
                                        "hash" => $hash,
                                        "nombrefichero" => $fileOriginalName,
                                        "estado" => $estado,
                                        "uid_documento_atributo" => $uidAtributo
                        );
                    } else {
                        $archivo =  DIR_FILES . $archivo;
                        if( !archivo::descargar( $archivo, $atributos["alias"] . "." . archivo::getExtension($archivo) ) ){
                            die( documento::ERROR_JAVASCRIPT );
                        }
                        break;
                    }
                }
            }
        }

        if( $return ){ return false; }

        die( documento::ERROR_JAVASCRIPT );
    }

    /** SI EL DOCUMENTO CON ID "X" TIENE UN ATRIBUTO DESCARGABLE NOS DA EL OBJETO SOLICITANTE*/
    public function obtenerSolicitanteDescargable(){
        $moduloBuscado = $this->elementoFiltro->getType();

        $sql = "
        SELECT uid_documento_atributo, uid_$moduloBuscado, uid_modulo_origen, uid_elemento_origen, alias
        FROM ". DB_DOCS .".documento_atributo  da
        LEFT JOIN ". DB_DOCS .".anexo_$moduloBuscado am
        USING( uid_documento_atributo )
        WHERE da.descargar
        AND da.uid_documento = $this->uid
        AND da.uid_modulo_destino = ".$this->elementoFiltro->getModuleId()." LIMIT 1";
        $datos = $this->db->query( $sql, true );
        if( !count($datos) ){ return false; }
        $datos = reset($datos);

        $modulo = $this->getModuleName($datos["uid_modulo_origen"]);

        $solicitante = new $modulo($datos["uid_elemento_origen"]);

        return $solicitante;
    }

    public function informacionArchivo( $solicitante=null, $formateada = false ){

        $estado = false;
        if( $this->elementoFiltro && $this->elementoFiltro->exists() ){

            $moduloBuscado = $this->elementoFiltro->getType();

            //documento descargables, no se indica el solicitante
            if( !$solicitante ){
                $descarga = true;
                $solicitante = $this->obtenerSolicitanteDescargable();

                $uidModuloBuscado = $solicitante->getModuleId();
                $uidElemento = $solicitante->getUID();

                //miramos si tiene anexo
                $sql = "
                SELECT uid_documento_atributo, uid_elemento_origen, uid_modulo_origen, archivo, estado, uid_$moduloBuscado, hash, nombre_original, fecha_emision, fecha_expiracion, fecha_anexion, alias, duracion, codigo
                FROM ". DB_DOCS .".documento_atributo  da
                LEFT JOIN ". DB_DOCS .".anexo_$moduloBuscado am
                USING( uid_documento_atributo, uid_agrupador )
                WHERE da.descargar
                AND da.uid_documento = $this->uid
                AND da.uid_modulo_origen = ". $solicitante->getModuleId()."
                AND ( am.uid_$moduloBuscado = 0 OR am.uid_$moduloBuscado = da.uid_elemento_origen )
                AND da.uid_modulo_destino = ".$this->elementoFiltro->getModuleId();

                if( isset($this->elementoFiltro->atributoDocumento) ){
                    $sql .= " AND uid_documento_atributo = ". $this->elementoFiltro->atributoDocumento["uid_documento_atributo"];
                }

                $sql .= " LIMIT 1";
            } else {
                $descarga = false;
                $uidModuloBuscado = $solicitante->getModuleId();
                $uidElemento = $solicitante->getUID();

                //miramos si tiene anexo
                $sql = "
                SELECT uid_documento_atributo, archivo, estado, uid_$moduloBuscado, hash, nombre_original, fecha_emision, fecha_expiracion, fecha_anexion, alias, duracion, codigo, obligatorio
                FROM ". DB_DOCS .".documento_atributo  da
                LEFT JOIN ". DB_DOCS .".anexo_$moduloBuscado am
                USING( uid_documento_atributo )
                WHERE uid_modulo_origen = $uidModuloBuscado
                AND da.uid_documento = $this->uid
                AND da.uid_modulo_destino = ".$this->elementoFiltro->getModuleId()."
                AND da.uid_elemento_origen = $uidElemento
                AND da.descargar = 0
                AND am.uid_$moduloBuscado = ".$this->elementoFiltro->getUID();

                if( isset($solicitante->referencia) ){
                    $sql .= " AND am.uid_agrupador = ". $solicitante->referencia->getUID();
                }

            }

            $estado = $this->db->query( $sql, true );
        }

        if( true === is_countable($estado) && count($estado) && is_array($estado) ){
            $datos = reset($estado);
            $datos = utf8_multiple_encode($datos);
            $datos["archivo"] = DIR_FILES . $datos["archivo"];
            if( $descarga ){
                $moduloSolicitante = util::getModuleName( $datos["uid_modulo_origen"] );
                $datos["solicitante"] = new $moduloSolicitante( $datos["uid_elemento_origen"], false );
            } else {
                $datos["solicitante"] = $solicitante;
            }


            if( $formateada ){
                if( $datos["fecha_expiracion"] ){
                    $datos["fecha_expiracion"] = date("d-m-Y", $datos["fecha_expiracion"]);}
                if( $datos["fecha_emision"] ){
                    $datos["fecha_emision"] = date("d-m-Y", $datos["fecha_emision"]);}
                if( $datos["fecha_anexion"] ){
                    $datos["fecha_anexion"] = date("d-m-Y m:h", $datos["fecha_anexion"]);}
            }
            return $datos;
        } else {
            if( $this->elementoFiltro ){
                $sql = "
                SELECT uid_documento_atributo, alias, duracion, obligatorio, uid_modulo_origen, uid_elemento_origen
                FROM ". DB_DOCS .".documento_atributo  da
                WHERE uid_modulo_origen = $uidModuloBuscado
                AND da.uid_documento = $this->uid
                AND da.uid_modulo_destino = ".$this->elementoFiltro->getModuleId()."
                AND da.uid_elemento_origen = $uidElemento";

                if( $solicitante ){ $sql .= " AND da.descargar = 0 "; } else { $sql .= " AND da.descargar = 1 "; }

            } else {
                $sql = "
                    SELECT uid_documento_atributo, alias, duracion, obligatorio, uid_modulo_origen, uid_elemento_origen
                    FROM ". DB_DOCS .".documento_atributo  da
                    WHERE uid_documento = $this->uid
                    LIMIT 0,1
                ";
            }
            $estado = $this->db->query( $sql, true );

            if( count($estado) && is_array($estado) ){
                $infoarchivo = reset( $estado );

                $infoarchivo["estado"] = documento::ESTADO_PENDIENTE;
                $infoarchivo = utf8_multiple_encode($infoarchivo);

                $moduloSolicitante = util::getModuleName( $infoarchivo["uid_modulo_origen"] );
                $infoarchivo["solicitante"] = new $moduloSolicitante( $infoarchivo["uid_elemento_origen"], false );
                return $infoarchivo;
            }
        }
        return false;
    }

    public function getStatusFor( $objeto=false, $descargar = false, $string = false ){

        $descargar = ( $descargar ) ? 1 : 0;
        if( !$this->elementoFiltro || !$this->elementoFiltro instanceof solicitable ){
            if( !$objeto ){ return false; }

            $this->elementoFiltro = $objeto;
            //$descargar = ( $this->elementoFiltro ) ? 0 : 1;

            $moduloBuscado = util::getModuleName($objeto->atributoDocumento["uid_modulo_destino"]);
            $uidModuloBuscado = $objeto->getModuleId();
            $uidElemento = $objeto->getUID();

            $sql = "
            SELECT estado FROM ". DB_DOCS .".documento_atributo  da
            LEFT JOIN ". DB_DOCS .".anexo_$moduloBuscado am
            USING( uid_documento_atributo )
            WHERE descargar = $descargar
            AND uid_modulo_origen = ". $this->elementoFiltro->atributoDocumento["uid_modulo_origen"] ."
            AND da.uid_documento = $this->uid
            AND da.uid_modulo_destino =". $this->elementoFiltro->atributoDocumento["uid_modulo_destino"] ."
            AND da.uid_elemento_origen = ". $this->elementoFiltro->atributoDocumento["uid_elemento_origen"];

            if( $objeto->referencia ){
                $sql .= " AND am.uid_agrupador = ". $objeto->referencia->getUID();
            } else {
                $sql .= " AND am.uid_agrupador = 0";
            }

        } else {
            if( !$objeto ){ $objeto = $this->elementoFiltro; }

            $moduloBuscado = $this->elementoFiltro->getType();

            $uidModuloDestino = ( $descargar) ? $objeto->atributoDocumento["uid_modulo_destino"] : $this->elementoFiltro->getModuleId();
            $uidModuloBuscado = ( $descargar ) ? "empresa" : $objeto->getModuleId();
            $uidElemento = $objeto->getUID();

            //$descargar = ( $this->elementoFiltro ) ? 0 : 1;

            $sql = "
            SELECT estado FROM ". DB_DOCS .".documento_atributo  da
            LEFT JOIN ". DB_DOCS .".anexo_$moduloBuscado am
            USING( uid_documento_atributo )
            WHERE descargar = $descargar
            AND uid_modulo_origen = $uidModuloBuscado
            AND da.uid_documento = $this->uid
            AND da.uid_modulo_destino = $uidModuloDestino
            AND da.uid_elemento_origen = $uidElemento";


            //si es de descarga, no miramos hacia quien apunta, solo el origen y el modulo de destino
            if( !$descargar ){
                $sql .= " AND am.uid_$moduloBuscado = ".$this->elementoFiltro->getUID();
            }

            if( isset($this->elementoFiltro->atributoDocumento) ){
                $sql .= " AND am.uid_documento_atributo = ".$this->elementoFiltro->atributoDocumento["uid_documento_atributo"];
            }

            if( $objeto->referencia ){
                $sql .= " AND am.uid_agrupador = ". $objeto->referencia->getUID();
            } else {
                $sql .= " AND am.uid_agrupador = 0";
            }

        }

        $estado = $this->db->query( $sql, true );
        if( count($estado) ){
            if( $string ){
                return self::status2String($estado[0]["estado"]);
            } else {
                return $estado[0]["estado"];
            }
        } else {
            if( $string ){
                return self::status2String( null );;
            } else {
                return documento::ESTADO_PENDIENTE;
            }
        }
    }

    public function obtenerEtiquetas( $solicitante = false, $callback=false){

        if( !$solicitante ){ $solicitante = $this->elementoFiltro; }

        $moduloBuscado = $solicitante->getType();
        $uidModuloBuscado = $solicitante->getModuleId();
        $uidElemento = $solicitante->getUID();
        $uidModuloDestino = $this->elementoFiltro->getModuleId();

        //APAÑO PARA BUSCAR EL AGRUPADOR NO EL AGRUPAMIENTO
        if( $uidModuloDestino == 11 ){ $uidModuloDestino = 14; }


        $datos = reset($this->datos);

        $sql = "SELECT e.uid_etiqueta
                FROM ". TABLE_ETIQUETA ." e
                INNER JOIN " . TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta de
                USING( uid_etiqueta )
                WHERE uid_documento_atributo = ". $solicitante->atributoDocumento["uid_documento_atributo"];

        $datosEtiquetas = $this->db->query( $sql, true );
        if( is_array($datosEtiquetas) && count($datosEtiquetas) ){
            $coleccionEtiquetas = array();
            foreach( $datosEtiquetas as $datosEtiqueta ){
                $coleccionEtiquetas[] = new etiqueta( $datosEtiqueta["uid_etiqueta"] );
            }
            if( $callback ){
                return array_map( array($this,$callback) , $coleccionEtiquetas);
            } else {
                return $coleccionEtiquetas;
            }
        }
        return false;
    }


    /***
       *
       *
       * @param $ref = int ref | Ielemento instance  -> referencia como modulo o como item
       *
       *
       */
    public function getAvailableOptions(Iusuario $user = NULL, $publicMode = false, $config = 0, $groups = true, $ref = false, $extraData = null) {
        $strSearch = $this->elementoFiltro->getType() . "_Documento";
        $idModulo = elemento::obtenerIdModulo($strSearch);
        if ($ref) $extraData['req'] = $ref;
        $options = config::obtenerOpciones($this->uid, $idModulo, $user, $publicMode, $config, 1, true, false, $this, $extraData);

        if ($this->elementoFiltro) {
            /*if ($this->elementoFiltro instanceof pagable && !$this->elementoFiltro->pagado($user) && !$user->isEnterprise()) {
                return array();
            }*/

            foreach ($options as &$option) {
                $option["href"] .= "&o=" . $this->elementoFiltro->getUID();

                if ($ref instanceof solicituddocumento) {
                    $option["href"] .= "&req=" . $ref->getUID();
                }
            }
        }


        return $options;
    }


    protected function documentInfo($uidAtributo=false){
        $camposDocumento = $this->getFields();
        $camposAtributos = documento_atributo::getTableFields();
        $campos = array_merge_recursive( $camposDocumento, $camposAtributos );
        foreach( $campos as $campo ){
            $camposSQL[] = $campo["Field"];
        }



        $sqlGenerica = "    SELECT ". implode(",",$camposSQL) ."
        FROM $this->tabla d
        INNER JOIN ". DB_DOCS .".documento_atributo
        USING( uid_documento )
        WHERE uid_documento = ". $this->getUID();

        if( is_numeric($uidAtributo) ){
            $sql = "    SELECT ". implode(",",$camposSQL) ."
            FROM $this->tabla d
            INNER JOIN ". DB_DOCS .".documento_atributo
            USING( uid_documento )
            WHERE uid_documento_atributo = $uidAtributo";
        }
        //---- FILTRANDO POR UN ELEMENTO
        elseif( is_object($this->elementoFiltro) ){
            //dump("Busqueda por objeto");
            $sql = "    SELECT ". implode(",",$camposSQL) ."
            FROM $this->tabla d
            INNER JOIN ". DB_DOCS .".documento_atributo
            USING( uid_documento )
            INNER JOIN ". DB_DOCS .".documento_elemento de
            USING( uid_documento_atributo, uid_modulo_destino )
            WHERE uid_documento = ". $this->getUID() ."
            AND uid_elemento_destino = ".$this->elementoFiltro->getUID()."
            AND uid_modulo_destino = ". $this->elementoFiltro->getModuleId();

        } elseif( $this->elementoFiltro !== true){
            //dump("Busqueda de descarga");
            $sql = "SELECT ". implode(",",$camposSQL) ." FROM $this->tabla INNER JOIN ". DB_DOCS .".documento_atributo
                    USING( uid_documento ) WHERE uid_documento = ". $this->getUID() ." AND descargar = 1 LIMIT 1";
        } else {
            //dump("Busqueda forzada");
            $sql = $sqlGenerica;
        }

        //--- EJECUTAR Y COMPROBAR
        $datos = $this->db->query( $sql, true );

        if( !is_array($datos) || !count($datos) ){

            $datos = $this->db->query($sqlGenerica, true);
            //if( !is_array($datos) || !count($datos)){ return false; }
            return utf8_multiple_encode($datos);
            //return false;
        }

        return $datos;
    }

    public static function instanceFromAtribute( $uidatributo, $autoFiltro = true ){
        $db = db::singleton();
        $uidatributo = db::scape( $uidatributo );
        $sql = "    SELECT uid_documento_atributo, uid_documento, uid_modulo_origen, uid_elemento_origen, uid_modulo_destino, descargar
                    FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
                    WHERE uid_documento_atributo = $uidatributo;";
        $datos = $db->query( $sql, true );

        if( isset($datos[0]) ){
            $atributo = $datos[0];
            $solicitante = self::obtenerSolicitanteDesdeAtributo($atributo);
            if( $autoFiltro ){
                return new self( $atributo["uid_documento"], $solicitante );
            } else {
                return new self( $atributo["uid_documento"], new documento_atributo($uidatributo) );
            }
        }
        return false;
    }

    public static function status2String( $uidestado, $idioma = null ){
        $lang = Plantilla::singleton();
        switch( $uidestado ){
            case self::ESTADO_ANEXADO: return $lang->getString("anexado", $idioma); break;
            case self::ESTADO_VALIDADO: return $lang->getString("validado", $idioma); break;
            case self::ESTADO_CADUCADO: return $lang->getString("caducado", $idioma); break;
            case self::ESTADO_ANULADO: return $lang->getString("anulado", $idioma); break;
            case self::ESTADO_PENDIENTE: return $lang->getString("sin_anexar", $idioma); break;
            case self::ESTADO_SIN_SOLICITAR: return $lang->getString("sin_solicitar", $idioma); break;
            default: return $lang->getString("sin_anexar", $idioma); break;
        }
    }

    public static function string2status( $string, $encode = false ){
        //if( $encode ){ dump( var_dump( $string ) ); }
        $lang = Plantilla::singleton();
        switch( strtolower($string) ){
            case strtolower($lang->getString("anexado")):
            case "anexado": case "pendientes": case "anexados":
                return self::ESTADO_ANEXADO;
            break;
            case strtolower($lang->getString("validado")):
            case "válidos": case "validos": case "validados":
                return self::ESTADO_VALIDADO;
            break;
            case strtolower($lang->getString("caducado")):
            case "caducados": case "caducado": case "vencido":
                return self::ESTADO_CADUCADO;
            break;
            case strtolower($lang->getString("anulado")):
            case "anulados": case "anulado":
            case "no-valido": case "no-válido": case "no-válidos": case "no-validos":
                return self::ESTADO_ANULADO;
            break;
            case strtolower($lang->getString("sin_anexar")):
            case "sin_anexar":
            case "sin-anexar":
                return self::ESTADO_PENDIENTE;  break;
            default:
                if( !$encode ){ return self::string2status( utf8_decode($string), true ); }
                return null;
            break;
        }
    }

    public function fileInfo( $objeto ) {
        $solicitantes = $this->obtenerSolicitantes();
        foreach( $solicitantes as $solicitante ){
            $atributos = $solicitante->atributoDocumento;
            if( $objeto->compareTo($solicitante) ){
                if ( isset($this->elementoFiltro->atributoDocumento) &&
                        $this->elementoFiltro->atributoDocumento["uid_documento_atributo"] !=
                        $atributos["uid_documento_atributo"] )
                {
                    continue;
                }
                if ( $atributos["descargar"] )
                {
                    $modulo = util::getModuleName($atributos["uid_modulo_destino"]);
                }
                else
                {
                    $modulo = $this->elementoFiltro->getModuleName();
                }
                $tableName = end(new ArrayObject( explode(".",PREFIJO_ANEXOS)));
                if ( $atributos["descargar"] )
                {
                    /*
                    $sql = "SELECT uid_". $tableName ."$modulo as uid, archivo, nombre_original, hash,
                    uid_documento_atributo
                    FROM ". PREFIJO_ANEXOS . $modulo . "
                    WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"];
                    */
                    $sql = "SELECT *
                    FROM ". PREFIJO_ANEXOS . $modulo . "
                    WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"];
                }
                else
                {
                    /*
                    $sql = "SELECT uid_". $tableName ."$modulo as uid, archivo, nombre_original, hash,
                    uid_documento_atributo
                    FROM ". PREFIJO_ANEXOS . $modulo . "
                    WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
                    AND uid_$modulo = ".$this->elementoFiltro->getUID();
                    */
                    $sql = "SELECT *
                    FROM ". PREFIJO_ANEXOS . $modulo . "
                    WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
                    AND uid_$modulo = ".$this->elementoFiltro->getUID();
                }
                $datos =  $this->db->query( $sql, true );
                //dump(reset($datos));
                return reset($datos);
            }
        }
    }

    public function actualizarFecha($stringFecha, $duracion, $solicitante, $caducidad = null){
        /*duracion = duracion definida del documento atributo*/
        $fecha = documento::parseDate($stringFecha);
        if( !is_numeric($fecha) ){ return "error_fecha_incorrecta"; }

        $atributos = $this->atributoDesdeSolicitante($solicitante);
        foreach($atributos as $atributo){
            $m = $this->elementoFiltro->getModuleName();
            $uid = $this->elementoFiltro->getUID();
            $tabla = PREFIJO_ANEXOS . $m;

            $sql = "UPDATE $tabla SET estado = ". documento::ESTADO_ANEXADO .",fecha_emision_real = fecha_emision, fecha_emision = '$fecha'";

            if( $caducidad ){
                $caducidad = documento::parseDate($caducidad);
                $sql .= ", fecha_expiracion = '$caducidad'" ;
            } else {
                $nueva_expiracion = ( $duracion ) ? $fecha + (((int)$duracion)*24*60*60) : "0";
                $sql .= ", fecha_expiracion = '$nueva_expiracion'";
            }

            $sql .= "
                    WHERE uid_documento_atributo = ". $atributo->getUID() ."
                    AND uid_$m = $uid
                    AND !fecha_emision_real
            ";

            if( !$this->db->query($sql) ){
                return $this->db->lastErrorString();
            }
        }
        return true;
    }

    public function atributoDesdeSolicitante($solicitante, $descargar=0){
        $sql = "
            SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
            WHERE uid_documento = ". $this->uid ."
            AND uid_elemento_origen = ". $solicitante->getUID() ."
            AND uid_modulo_origen = ". $solicitante->getModuleId() ."
        ";

        if( is_numeric($descargar) ){
            $sql .= " AND descargar = ". $descargar;
        }

        $docs = array();
        $uids = $this->db->query($sql, "*", 0);
        foreach($uids as $uid){
            $attr = new documento_atributo($uid);
            $docs[] = $attr;
        }
        return $docs;
    }

    /** DEPRECATEDDDDD!! NO USAR NUNCA (DE MOMENTO NO COMENTAR) **/
    public function reanexar( $arrayDatosArchivo, $solicitantesManual=false, $usuario=false ) {
            //definimos variables importantes
        $modulosConHistorico = util::getAllModules();
        $seleccionados = $this->verSolicitantesSeleccionados();
        $modulo = $this->elementoFiltro->getModuleName();

        if( is_array($solicitantesManual) && count($solicitantesManual) ){
            $seleccionados = $solicitantesManual;
        }

        if ( !count($seleccionados)  ){
            return "no_solicitante_documento";
        }

        foreach( $seleccionados as $solicitante ){
            $atributos = $solicitante->atributoDocumento;

            $attr = new documento_atributo( $atributos["uid_documento_atributo"] );
            //comprobamos que el formato que quiere subir esta permitido
            $formato = $this->comprobarFormato($atributos["uid_documento_atributo"], archivo::getMimeType($arrayDatosArchivo["archivo"]));
            if( $formato === false ) {
                return "formato_documento_no_permitido";
            }

            $a_camposTabla = array( 'uid_documento_atributo',
                    'archivo',
                    'estado',
                    'uid_'.$modulo,
                    'uid_agrupador',
                    'hash',
                    'nombre_original',
                    'fecha_emision',
                    'fecha_anexion',
                    'fecha_emision_real',
                    'fecha_expiracion',
                    'language',
                    'is_urgent',
                    'uid_validation',
                    'fileId',
                    'uid_usuario',
                    'uid_empresa_anexo',
                    'uid_empresa_payment',
                    'validation_errors'
                    );


            $camposTabla = implode(',',$a_camposTabla);


            $referencia = (isset($solicitante->referencia)) ? $solicitante->referencia->getUID() : 0;

            // copiamos el documento actual a la tabla del historico solo si es de uno de los modulos con docs
            $sql = "INSERT IGNORE INTO ". PREFIJO_ANEXOS . "historico_$modulo ( uid_anexo, $camposTabla )
            SELECT uid_anexo_$modulo, $camposTabla
            FROM ". PREFIJO_ANEXOS . $modulo ."
            WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
            AND uid_$modulo = ".$this->elementoFiltro->getUID()."
            AND uid_agrupador = $referencia";
            if ( !$this->db->query( $sql ) ) {
                if (CURRENT_ENV=='dev') dump($this->db);
                return "error_guardar_historico";
            }


            // Nueva primary key de nuestro archivo en el historico...
            // imagino que esto es para trasladar los comentarios o algo
            $primaryKeyHistorico = $this->db->getLastId();


            //----------- borramos de la tabla actual el registro solo si es de uno de los modulos con docs
            $sql = "DELETE FROM ". PREFIJO_ANEXOS . $modulo ."
            WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
            AND uid_$modulo = ".$this->elementoFiltro->getUID()."
            AND uid_agrupador = $referencia";


            if ( !$this->db->query( $sql ) ){ return "error_limpiar_actual"; }

            $duraciones = $attr->obtenerDuraciones($solicitante);
            $duracion = ( is_array($duraciones) ) ? reset($duraciones) : $duraciones;
            if( $duracion == 0 ){
                $arrayDatosArchivo['fecha_expiracion'] = 0;
            } else {
                $segundosduracion = $duracion * 24 * 60 * 60;
                $arrayDatosArchivo['fecha_expiracion'] = $arrayDatosArchivo['fecha_emision'] + $segundosduracion;
            }

            if (!$arrayDatosArchivo["fileId"]) {
                $fileId = fileId::generateFileId();
                $arrayDatosArchivo["fileId"] = $fileId;
            }

            if (!$arrayDatosArchivo["uid_usuario"] && $usuario instanceof usuario) {
                $arrayDatosArchivo["uid_usuario"] = $usuario->getUID();
            }

            if (!$arrayDatosArchivo["uid_empresa_anexo"] && $usuario instanceof usuario) {
                $userCompany = $usuario->getCompany();
                if ($userCompany->esCorporacion()) {
                    $element = $this->elementoFiltro;
                    $arrayDatosArchivo["uid_empresa_anexo"] = ($element instanceof empresa) ? $element->getUID() : $element->getCompany($usuario)->getUID();
                } else {
                    $arrayDatosArchivo["uid_empresa_anexo"] = $userCompany->getUID();
                }
            }

            // Insertamos el nuevo registro
            $sql = "INSERT INTO ". PREFIJO_ANEXOS . $modulo ." ( $camposTabla ) VALUES (
                ".$atributos["uid_documento_atributo"].", '". $arrayDatosArchivo['archivo']."', ". documento::ESTADO_ANEXADO .",
                ".$this->elementoFiltro->getUID().", $referencia, '".$arrayDatosArchivo['hash']."', '". db::scape($arrayDatosArchivo['nombre_original']) ."',
                ".$arrayDatosArchivo['fecha_emision'].", ".time().", ".$arrayDatosArchivo['fecha_emision'].", ".$arrayDatosArchivo['fecha_expiracion'].",
                '".$arrayDatosArchivo['language']."', '0','".$arrayDatosArchivo['uid_validation']."',
                '".$arrayDatosArchivo['fileId']."',".db::valueNull($arrayDatosArchivo['uid_usuario']).","
                .db::valueNull($arrayDatosArchivo['uid_empresa_anexo']).",".db::valueNull($arrayDatosArchivo['uid_empresa_payment']).",
                '".$arrayDatosArchivo['validation_errors']."'
            )";

            if ( !$this->db->query( $sql ) ){ return "error_guardar_nuevo_archivo"; }
        }
        return true;
    }


    public function borrar(Iusuario $usuario) {
      if( $solicitantes ) {
            $seleccionados = $solicitantes;
        } else {
            $seleccionados = $this->verSolicitantesSeleccionados();
        }
        $totalFilasAfectadas = 0;
        foreach ( $seleccionados as $solicitante ) {
            $modulo = $this->elementoFiltro->getType();
            foreach ( $this->datos as $atributos ) {
                if( $atributos["uid_modulo_origen"] == $solicitante->getModuleId() && $atributos["uid_elemento_origen"] == $solicitante->getUID() ) {
                    //$attr = new documento_atributo($atributos["uid_documento_atributo"]);

                    $sql = "DELETE FROM ". PREFIJO_ANEXOS . $modulo ."
                          WHERE uid_documento_atributo = ".$atributos["uid_documento_atributo"]."
                          AND uid_$modulo = ".$this->elementoFiltro->getUID();


                    $resultset = $this->db->query( $sql );
                    if( $resultset ){
                        $totalFilasAfectadas += $this->db->getAffectedRows();
                    } else {
                        return $this->db->lastErrorString();
                    }

                }
            }
        }

        return $totalFilasAfectadas;
    }


    public static function optionsFilter($uid, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){

        $condiciones = array();

        if ($user instanceof empleado && $uidmodulo && $parent) {
            if( ( $parent instanceof documento && $parent->elementoFiltro->compareTo($user) ) || ( $parent instanceof empleado && $parent->compareTo($user) ) ){
                // No se podrá: anular, validar, borrar, reanexar, filtrar RESPECTIVAMENTE cuando el empelado se vea a sí mismo
                $condiciones[] = " ( uid_accion NOT IN (6,9,57,56,41,131) ) ";

                if ( $parent instanceof documento ) {
                    $types = $parent->getTypesFor($parent->elementoFiltro, $user);
                    if (!in_array(documento_atributo::TYPE_FILE_UPLOAD, $types)) $condiciones[] = " (uid_accion NOT IN (7)) ";
                    if (!in_array(documento_atributo::TYPE_ONLINE_SIGN, $types)) $condiciones[] = " (uid_accion NOT IN (173)) ";
                }
            }
        }

        if ($uidmodulo && $user instanceof usuario && $parent) {
            $userCompany            = $user->getCompany();
            $startList              = $userCompany->getStartList();
            $userDocumentCompanies  = $startList;

            if ($corporation = $userCompany->perteneceCorporacion()) {
                $userDocumentCompanies[] = $corporation;
            }

            if ($user->esValidador()) {
                $companiesToValidate    = $userCompany->getValidationCompanies();
                $userDocumentCompanies  = $userDocumentCompanies->merge($companiesToValidate);
            }

            if ($solicitable = $parent->elementoFiltro) {
                $class = $solicitable->getModuleName();


                if (isset($extraData['req']) && $extraData['req'] instanceof solicituddocumento) {
                    $solicitudes = new ArrayRequestList([$extraData['req']]);
                } elseif (isset($parent->requests)) { // simple cache
                    $solicitudes = $parent->requests;
                } else {
                    $solicitudes = $parent->obtenerSolicitudDocumentos($solicitable, $user);
                }



                $hiddenActions  = [[6, 7, 8, 9, 13, 37, 41, 57, 131, 173]];
                foreach ($solicitudes as $solicitud) {
                    $forbidden      = [];
                    $attr           = $solicitud->obtenerDocumentoAtributo();
                    $attachment     = $solicitud->getAnexo();
                    $owner          = $attr->getCompany();
                    $type           = $attr->getRequirementType();
                    $isOwner        = $userDocumentCompanies->contains($owner);

                    if ($attr->hasCompanyReference()) {
                        $reference = $solicitud->obtenerEmpresaReferencia();

                        if ($reference && !$startList->contains($reference)) {
                            $forbidden[] = 7;
                        }
                    } elseif ($attr->hasChainReference()) {
                        $reference = $solicitud->obtenerEmpresaReferencia();

                        if ($reference) {
                            $contractChain = new empresaContratacion($reference);
                            $tailCompany = $contractChain->getCompanyTail();

                            if ($startList->contains($tailCompany) == false) {
                                $forbidden[] = 7;
                                $forbidden[] = 57;
                            }
                        }
                    }

                    // Validate the company owner
                    if (!$isOwner) {
                        $forbidden = array_merge($forbidden, [6, 9, 41, 13, 131]); // no se puede anular, validar, descargar, historico
                    }


                    if ($attachment instanceof anexo) {
                        $status = $attachment->getStatus();

                        if ($isOwner && $attachment->yaRevisado($user)) {
                            $forbidden[] = 131; // no revisar
                        }

                        // if delayed status, act like the future status
                        if ($delayed = $attachment->getReverseStatus()) {
                            $status = $delayed;
                        }

                        switch ($status) {
                            case documento::ESTADO_ANEXADO:
                                break;

                            case documento::ESTADO_VALIDADO:
                                $forbidden[] = 9;   // no validar
                                break;

                            case documento::ESTADO_CADUCADO:
                                $forbidden[] = 9;   // no validar
                                $forbidden[] = 6;   // no anular
                                $forbidden[] = 131; // no revisar
                                $forbidden[] = 57;  // no borrar
                                break;

                            case documento::ESTADO_ANULADO:
                                if (!$delayed) $forbidden[] = 6; // Si tenemos delayed no anulamos.
                                break;
                        }

                    } else { // if no attachment...
                        $forbidden = array_merge($forbidden, [57, 6, 9, 8, 37, 131]);
                    }


                    // case old app, sign and attach are compatible options
                    if ($user->getAppVersion() == 1) {
                        // Validate the cumplementation type
                        if ($type == documento_atributo::TYPE_FILE_UPLOAD) {
                            $forbidden[] = 173; // no firmar
                        } else {
                            $forbidden[] = 7; // no anexar
                        }

                    }


                    $hiddenActions[] = $forbidden;
                }


                // Insersect all the requests options
                $hiddenActions = count($hiddenActions) === 1 ? $hiddenActions[0] : call_user_func_array('array_intersect', $hiddenActions);
                if (count($hiddenActions)) {
                    $condiciones[] = " (uid_accion NOT IN (". implode(',', $hiddenActions) .")) "; // ocultar las opciones
                }


                if ($solicitable instanceof empresa) {
                    $allow      = $startList->contains($solicitable);
                } else {
                    $empresas   = $solicitable->getCompanies();
                    $matches    = $startList->match($empresas);
                    $allow      = isset($matches[0]);
                }

                // Indica si el elemento es de nuestra "propiedad"
                if (!$allow){
                    $condiciones[] = " ( uid_accion NOT IN (7, 57, 173) ) "; // no se puede anexar, borrar, firmar
                }

            }
        }

        if ($condiciones) {
            return "AND " . implode(" AND ", $condiciones);
        }

        return false;
    }

    static public function getAll($limit=false, $filtro=false)
    {
        $coleccion  = array();
        $onlyPublic = false;

        $data = config::obtenerArrayDocumentos($limit, $filtro, $onlyPublic);

        foreach($data as $line){
            $coleccion[] = new documento($line["uid_documento"]);
        }
        return $coleccion;
    }



    public function getExtension($solicitante){
        $arrInfo = $this->informacionArchivo($solicitante);
        $ext = archivo::getExtension($arrInfo["archivo"]);
        if( $ext ){
            return $ext;
        }
    }


    public function getCustomId () {
        return $this->obtenerDato("custom_id");
    }

    public function isIta(Iusuario $usuario = NULL){
        $cID = $this->obtenerDato("custom_id");
        return tipodocumento::TIPO_DOCUMENTO_ITA == $cID;
    }

    public function isTc2(Iusuario $usuario = NULL){
        $cID = $this->obtenerDato("custom_id");
        return tipodocumento::TIPO_DOCUMENTO_TC2 == $cID;
    }

    public function isAltaSS (Iusuario $usuario = NULL){
        $cID = $this->obtenerDato("custom_id");
        return tipodocumento::TIPO_DOCUMENTO_ALTASS == $cID;
    }

    public function applyForAllRequest(Iusuario $usuario){
        if ($this->isIta($usuario) && $this->getValidModulesForIta()->contains($this->moduloFiltro))  return true;
        return false;
    }

    public function getValidModulesForIta(){

        $modules = array(
            51 => 'empleado',
        );

        return new ArrayObjectList($modules);

    }

    public function canSelectItems(Iusuario $usuario = null, $module = false)
    {
        $filter = isset($this->moduloFiltro) ? $this->moduloFiltro : $module;
        $uid    = $this->getUID();

        // Para el módulo de empleado
        if ($filter == 'empleado') {
            /**
             * Formacion en el puesto de trabajo - uid:5
             */
            $multiples = [5];

            if (true === in_array($uid, $multiples)) {
                return true;
            }

            // If the document is versionable then it is enabled to multiupload
            return $this->isVersionable();
        }

        if ($filter == 'maquina') {
            /**
             * - Seguro obligatorios de los camiones - uid:171
             * - Seguro de transporte - uid: 13928
             */
            $multiples = [171, 13928];

            if (true === in_array($uid, $multiples)) {
                return true;
            }
        }

        return false;
    }

    public function supportsChecks()
    {
        $customId = $this->obtenerDato("custom_id");
        $autovalidables = [
            tipodocumento::TIPO_DOCUMENTO_ITA,
            tipodocumento::TIPO_DOCUMENTO_TC2,
        ];

        return in_array($customId, $autovalidables);
    }

    public function supportsAutovalidation()
    {
        $customId = $this->obtenerDato("custom_id");
        $autovalidables = [
            tipodocumento::TIPO_DOCUMENTO_ITA,
            tipodocumento::TIPO_DOCUMENTO_TC2
        ];

        return in_array($customId, $autovalidables);
    }

    public function isVersionable()
    {
        $cid = $this->obtenerDato("custom_id");
        $selectables = array(tipodocumento::TIPO_DOCUMENTO_ITA, tipodocumento::TIPO_DOCUMENTO_TC2);

        return in_array($cid, $selectables);
    }


    public function comparePdf ($handler) {
        $words = $this->getReservedWords();

        if (false === $words) {
            return false;
        }

        return $handler->hasWords($words, false);
    }

    public function isProcessable($filePath){
        $words = $this->getReservedWords();
        if (false === $words) {
            return false;
        }

        if ($filePath instanceof pdfHandler) {
            $handler = $filePath;
        } else {
            if (!archivo::is_readable($filePath)) return false;
            if (archivo::getExtension($filePath) !== 'pdf') return false;

            $size = filesize($filePath);
            $maxSize = 1024 * 1024 * 15; // 5MB of PDF


            if ($size > $maxSize) return false;

            try {
                $handler = new pdfHandler($filePath);

                if (!$handler->getNumPages()) return false;
            } catch (Exception $e) {
                return false;
            }
        }

        $needProcess = $this->isVersionable();
        $hasWords = $handler->hasWords($words, $needProcess);


        return $hasWords ? $handler : false;
    }



    public function passChecks(pdfHandler $handler, Iusuario $usuario, $date)
    {
        $cacheString = __CLASS__."-".__FUNCTION__."-".$this->getUID()."-".$handler->getFile()."-".$usuario->getUID()."-".$date;

        if (($dato = $this->cache->getData($cacheString)) !== null) {
            return $dato;
        }

        $result = false;
        if ($this->supportsChecks($usuario)) {
            $versionable = $this->isVersionable();

            if ($versionable) {
                // --- CHECK CIF
                $cif = $handler->getFirstCIF();
                if (!$cif || strtolower($cif) != strtolower($cif)) {
                    $exception = new \Dokify\Exception\FormException('wrong_cif_document');
                    $exception->setType('cif');
                    throw $exception;
                }
            }

            $inputTime = self::parseDate($date);

            $maxDiff = $this->getDocumentDateMaxDiff();
            $periods = $this->pdfDatePeriod();

            // --- CHECK DATE
            $docDate = $handler->getFirstDate(true, $this->getPDFDatePriorityMethod(), $maxDiff, $periods);
            if ($docDate) {
                $timeMatch = strtotime($docDate) === $inputTime;

                if ($timeMatch === false) {
                    $exception = new \Dokify\Exception\FormException("wrong_date_document");
                    $exception->setType('date');
                    throw $exception;
                }

                $result = true;
            }
        }

        $this->cache->set($cacheString, $result, 60*60);
        return $result;
    }

    public function getHTMLMultiuploadItemList(Iusuario $usuario, $module, $filename) {
        if ($module == 'empleado') {
            return $this->getHTMLEmployeeList($usuario, $filename, false);
        } elseif ($module == 'maquina') {
            return $this->getHTMLMachineList($usuario, $filename);
        }
    }

    public function getMultiuploadVisibleEmployees(Iusuario $user, ArrayEmployeeList $employeesFilter = null)
    {
        $userCompany = $user->getCompany();
        $companyList = $userCompany->getStartIntList();
        $companyEmployeesTable = TABLE_EMPLEADO . "_empresa";
        $documentStatusTable = TABLE_DOCUMENTO . "_empleado_estado";

        $sql = "SELECT uid_empleado FROM {$companyEmployeesTable}
        WHERE uid_empresa IN ({$companyList})
        AND papelera = 0";

        // --- si tenemos una lista siempre la usamos para filtrar
        if (count($employeesFilter)) {
            $employeeList = $employeesFilter->toIntList();
            $sql .= " AND uid_empleado IN ({$employeeList})";
        }

        // --- user filters
        if ($user->isViewFilterByGroups()) {
            $userCondition = $user->obtenerCondicion($this, "uid_empleado");
            if ($userCondition) {
                $condicion = " uid_empleado IN ($userCondition)";
            } else {
                $condicion = " 0";
            }

            $sql .= " AND {$condicion}";
        }


        $requesteds = "SELECT uid_empleado
        FROM {$documentStatusTable}
        WHERE uid_documento = {$this->getUID()}";

        if ($this->elementoFiltro) {
            $requests = $this->obtenerSolicitudDocumentos($this->elementoFiltro, $user);
            $owners = $requests->getOwnerCompanies();
            $comaList = $owners->toComaList();

            $requesteds .= " AND uid_empresa_propietaria IN ({$comaList})";
        }

        $sql .= " AND uid_empleado IN ({$requesteds})";

        return $this->db->query($sql, "*", 0, 'empleado');
    }

    public function getMultiuploadVisibleMachines(Iusuario $user)
    {
        $userCompany = $user->getCompany();
        $companyList = $userCompany->getStartIntList();
        $companyMachinesTable = TABLE_MAQUINA . "_empresa";
        $documentStatusTable = TABLE_DOCUMENTO . "_maquina_estado";

        $sql = "SELECT uid_maquina FROM {$companyMachinesTable}
        WHERE uid_empresa IN ({$companyList})
        AND papelera = 0";

        // --- user filters
        if ($user->isViewFilterByGroups()) {
            $userCondition = $user->obtenerCondicion($this, "uid_maquina");
            if ($userCondition) {
                $condicion = " uid_maquina IN ($userCondition)";
            } else {
                $condicion = " 0";
            }

            $sql .= " AND {$condicion}";
        }


        $requesteds = "SELECT uid_maquina
        FROM {$documentStatusTable}
        WHERE uid_documento = {$this->getUID()}";

        if ($this->elementoFiltro) {
            $requests = $this->obtenerSolicitudDocumentos($this->elementoFiltro, $user);
            $owners = $requests->getOwnerCompanies();
            $comaList = $owners->toComaList();

            $requesteds .= " AND uid_empresa_propietaria IN ({$comaList})";
        }

        $sql .= " AND uid_maquina IN ({$requesteds})";

        return $this->db->query($sql, "*", 0, 'maquina');
    }

    public function getHTMLEmployeeList(Iusuario $usuario, $filename, $html = true)
    {
        $employeesWithRequest = new ArrayEmployeeList;
        $employees = new ArrayEmployeeList;
        $handler = false;
        $localPath = "/tmp/{$filename}";
        $versionable = $this->isVersionable();
        $multiple    = $this->canSelectItems();

        $data = [
            "documento" => $this,
            "selectedItem" => $this->elementoFiltro,
            "module" => $this->moduloFiltro,
            "selected" => false,
            "multiple" => $multiple,
            "versionable" => $versionable,
            "info" => [],
            "processable" => false,
            "vat" => false,
        ];

        // --- make sure we have the file in disk
        if (!archivo::is_readable($localPath)) {
            $fileData = archivo::tmp($filename);
            archivo::escribir($localPath, $fileData);
        }

        // --- make sure is a pdf and is processable
        if (archivo::getExtension($filename) === 'pdf') {
            try {
                $handler = $this->isProcessable($localPath);
                $data["processable"] = (bool) $handler;
                $data["info"] = (array) pdfHandler::getInfoFromFile($localPath);
            } catch (Exception $e) {
                // return false;
            }
        }

        if ($handler) {
            // --- should we present the checkbox checked?
            if (count($employees = $handler->getEmployees($usuario, $versionable))) {
                if ($employees->contains($this->elementoFiltro)) {
                    $data["selected"] = true;
                }
            }

            // check the document CIF
            if ($this->isVersionable() && $cif = $handler->getFirstCIF()) {
                $data["vat"] = mb_strtoupper($cif);
            }
        }

        $visibleEmployees = $this->getMultiuploadVisibleEmployees($usuario, $employees);

        $data["others"] = false;
        // --- si no lo vamos a dar seleccionado, mostramos la lista de los visibles
        if ($data["selected"] == false && $handler) {
            if ($visibleEmployees && count($employees)) {
                $data["others"] = new ArrayEmployeeList($visibleEmployees);
            } else {
                $data["others"] = true;
            }
        }

        // --- convertir si aplica el array en ArrayEmployeeList
        if (count($visibleEmployees)) {
            $employeesWithRequest = new ArrayEmployeeList($visibleEmployees);
        }

        if ($html == false) {
            $data["items"] = $employeesWithRequest;
            return $data;
        }

        $html = $employeesWithRequest->render("multiupload.tpl", $data);

        return $html;

    }

    public function getHTMLMachineList(Iusuario $usuario, $filename)
    {
        $machinesWithRequest = new ArrayChildItemList;
        $localPath = "/tmp/{$filename}";
        $versionable = $this->isVersionable();
        $multiple    = $this->canSelectItems();

        $data = [
            "documento" => $this,
            "selectedItem" => $this->elementoFiltro,
            "module" => $this->moduloFiltro,
            "selected" => false,
            "multiple" => $multiple,
            "versionable" => $versionable,
            "info" => [],
            "processable" => false,
            "vat" => false,
        ];

        // --- make sure we have the file in disk
        if (!archivo::is_readable($localPath)) {
            $fileData = archivo::tmp($filename);
            archivo::escribir($localPath, $fileData);
        }

        // --- make sure is a pdf and is processable
        if (archivo::getExtension($filename) === 'pdf') {
            try {
                $data["info"] = (array) pdfHandler::getInfoFromFile($localPath);
            } catch (Exception $e) {
                // return false;
            }
        }

        $visibleMachines = $this->getMultiuploadVisibleMachines($usuario);

        $data["others"] = false;

        // --- convertir si aplica el array en ArrayChildItemList
        if (count($visibleMachines)) {
            $machinesWithRequest = new ArrayChildItemList($visibleMachines);
        }

        $data["items"] = $machinesWithRequest;
        return $data;
    }

    public function getDocumentItemVersion (pdfHandler $handler, $usuario) {
        $vat = strtolower($this->elementoFiltro->getId());

        return $handler->getVersionFromVAT($vat);
    }

    /***
       * Cuando anexamos sin seleccionar el documento ni la fecha, en que estado vamos a dejar el documento?
       *
       *
       */
    public function getPDFDatePriorityMethod()
    {
        $cID = $this->obtenerDato("custom_id");

        switch ($cID) {
            case tipodocumento::TIPO_DOCUMENTO_ALTASS:
                return ['fecha', 'efectos', 'reconoce', 'alta', 'indica', 'continuación'];
                break;
            case tipodocumento::TIPO_DOCUMENTO_ITA:
                return ['informe', 'trabajadores', 'alta', 'fecha'];
                break;
            case tipodocumento::TIPO_DOCUMENTO_TC2:
                return ['Periodo', 'de', 'liquidación'];
                break;
            case tipodocumento::TIPO_DOCUMENTO_AUTONOMOS:
                return pdfHandler::SEARCH_METHOD_MOST_REPEATED;
                break;
        }

        return pdfHandler::SEARCH_METHOD_FIRST_DOCUMENT;
    }

    /**
     * Wether the date of this document is represented in periods or not (in the PDFs)
     * @return bool
     */
    public function pdfDatePeriod()
    {
        $cID = $this->obtenerDato("custom_id");

        switch ($cID) {
            case tipodocumento::TIPO_DOCUMENTO_TC2:
            case tipodocumento::TIPO_DOCUMENTO_AUTONOMOS:
                return true;
                break;
        }

        return false;
    }

    /**
     * The max diff to apply to the pdf getFirstDate for the given document
     * @return int|null
     */
    public function getDocumentDateMaxDiff()
    {
        $cID = $this->obtenerDato("custom_id");

        switch ($cID) {
            case tipodocumento::TIPO_DOCUMENTO_ITA:
                return 365*24*60*60;
                break;
        }

        return null;
    }

    /***
       * Cuando anexamos sin seleccionar el documento ni la fecha, en que estado vamos a dejar el documento?
       *
       *
       */
    public function isUploadAutovalidated () {
        $cID = $this->obtenerDato("custom_id");

        $strings = array();

        switch ($cID) {
            case tipodocumento::TIPO_DOCUMENTO_ITA:
                return true;
                break;
        }

        return false;
    }



    public function getReservedWords($ai = false)
    {
        if ($ai) {
            if ($keywords = $this->obtenerDato('keywords')) {
                $keywords = mb_strtolower($keywords, 'ISO-8859-1');
                return explode(' ', $keywords);
            }

            return false;
        }



        $cID = $this->obtenerDato("custom_id");

        $strings = array();

        switch ($cID) {
            case tipodocumento::TIPO_DOCUMENTO_ITA:
                $strings = array("ita", "informe", "trabajadores", "alta", "código", "cuenta", "cotización");
                break;

            case tipodocumento::TIPO_DOCUMENTO_TC2:
                $strings = array("relación", "nominal", "trabajadores", "autorización", "liquidación", "bases");
                break;

            case tipodocumento::TIPO_DOCUMENTO_TC1:
                $strings = array(
                    "cotizaciones", "liquido", "recibo",
                    "liquidación", "calificador", "autorización",
                    "modalidad", "pago", "huella", "cuotas"
                );
                break;

            case tipodocumento::TIPO_DOCUMENTO_ALTASS:
                $strings = array(
                    "tesorería", "general", "seguridad",
                    "social", "resolución", "reconocimiento",
                    "alta", "procedido", "reconocer",
                    "afiliación", "código", "cuenta",
                    "cotización", "fecha", "nacimiento",
                    "reconoce", "recurso", "alzada", "administración",
                    "plazo", "notificación", "conformidad",
                    "dispuesto", "régimen", "jurídico",
                    "impresos", "artículos", "solicitud"
                );
                break;

            case tipodocumento::TIPO_DOCUMENTO_AUTONOMOS:
                $strings = array("autonomos", "cuotas", "seguridad", "social", "trabajadores", "fecha");
                break;

            default:
                return false;
                break;
        }

        return $strings;
    }

    public function getDateCriteria($locale = null)
    {
        $locale = isset($locale) ? $locale : Plantilla::getCurrentLocale();
        $translator = new traductor($this->getUID(), $this, 'date_criteria');

        $dateCriteria = '';
        if (true === in_array($locale, $translator->getLocales())) {
            $dateCriteria = $translator->getLocaleValue($locale);
        }

        if ('' === $dateCriteria) {
            $dateCriteria = $this->obtenerDato('date_criteria');
        }

        if ('' === $dateCriteria) {
            $tpl = new Plantilla();
            $tpl->assign('lang', $locale);
            $dateCriteria = $tpl->getString('default_date_criteria');
        }

        return $dateCriteria;
    }

    public function getDescription($locale = null)
    {
        if (null === $locale) {
            $locale = Plantilla::getCurrentLocale();
        }

        $description = '';
        $translator = new traductor($this->getUID(), $this, 'description');

        if (true === in_array($locale, $translator->getLocales())) {
            $description = $translator->getLocaleValue($locale);
        }

        if ('' === $description) {
            $description = $this->obtenerDato('description');
        }

        return $description;
    }

    public static function parseDate($date){
        $fecha = explode("/",$date);

        if (count($fecha) !== 3) return "error_fecha_incorrecta";

        if( strlen($fecha[0]) == 1 ) $fecha[0] = "0".$fecha[0];
        if( isset($fecha[1]) && strlen($fecha[1]) == 1 ) $fecha[1] = "0".$fecha[1];

        if(     (strlen($fecha[0]) != 2 && $fecha[0] < 32) ||
                (strlen($fecha[1]) != 2 && $fecha[1] < 13) ||
                (strlen($fecha[2]) != 4 && $fecha[2])
            ){
            return "error_fecha_incorrecta";
        }
        $fechaF[0] = $fecha[2];
        $fechaF[1] = $fecha[1];
        $fechaF[2] = $fecha[0];
        $fechaF = implode("-",$fechaF);
        $fechaF = strtotime($fechaF);
        return $fechaF;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false) {
        $fieldList = new FieldList();
        $fieldList["nombre"] = new FormField();
        $fieldList["description"] = new FormField();
        return $fieldList;
    }

    public function getTableFields()
    {
        return array(
            array("Field" => "uid_documento",       "Type" => "int(10)",        "Null" => "NO",     "Key" => "PRI", "Default" => "",    "Extra" => "auto_increment"),
            array("Field" => "nombre",              "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "description",         "Type" => "varchar(500)",   "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "flags",               "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "keywords",            "Type" => "varchar(1024)",  "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "custom_id",           "Type" => "int(2)",         "Null" => "NO",     "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "determinable",        "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "is_standard",         "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "is_public",           "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "1",   "Extra" => ""),
            array("Field" => "image_url",           "Type" => "text",           "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => ""),
            array("Field" => "image_is_landscape",  "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",   "Extra" => ""),
            array("Field" => "date_criteria",       "Type" => "text",           "Null" => "NO",     "Key" => "",    "Default" => "",    "Extra" => ""),
        );
    }

    /**
     * Converts a reqType to array
     * @return array
     */
    public function toArray($app = null)
    {
        $model = array(
            'name'  => $this->getUserVisibleName(),
            'uid'   => $this->getUID()
        );

        return $model;
    }
}
