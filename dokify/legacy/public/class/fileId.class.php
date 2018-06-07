<?php

class fileId implements IfileId
{
    const ASSIGN_TIME = 300;
    protected $uid;

    public function __construct($param, $module = null)
    {
        $this->uid = db::scape($param);

        if (!$module) {
            $module = self::getModuleOfFileId($param);
        }

        $this->module = db::scape($module);
        $this->db = db::singleton();
    }

    public static function getRouteName()
    {
        return 'fileid';
    }

    public static function createFileId()
    {
        return buscador::getRandomKey();
    }

    public function getUID()
    {
        return $this->uid;
    }

    public function assignToUser(usuario $usuario)
    {
        $table = PREFIJO_ANEXOS . "{$this->module}";
        $attachments = $this->getAttachments($usuario->getCompany());

        // --- if we have no attachments
        if (!count($attachments)) {
            return false;
        }

        $timeSeen = date("Y-m-d H:i:s", time() + fileId::ASSIGN_TIME);

        $SQL = "
            UPDATE {$table}
            SET screen_uid_usuario = {$usuario->getUID()},
                screen_time_seen = '{$timeSeen}'
            WHERE uid_anexo_{$this->module} IN ({$attachments->toIntList()})
        ";

        return $this->db->query($SQL);
    }

    public function isAssignedToOther(usuario $usuario)
    {
        $attachments = $this->getAttachments($usuario->getCompany());

        // --- if we have no attachments
        if (!count($attachments)) {
            return false;
        }

        $attachment = $attachments[0];
        $seenBy = $attachment->obtenerDato("screen_uid_usuario");

        // --- if no one is assigned
        if (!$seenBy) {
            return false;
        }

        // --- if param $user is assigned
        if ($seenBy === $usuario->getUID()) {
            return false;
        }

        return true;
    }

    public static function generateFileId($filePath = null)
    {
        $uuid = preg_replace('/\./', '', uniqid('', true));

        // this is just a check to prevent errors in legacy codebase
        if ($filePath) {
            $dbc = db::singleton();
            $uploadsTable = DB_DOCS . '.upload';
            $sql = "INSERT INTO {$uploadsTable} (`fileId`, `path`)
            VALUES ('{$uuid}', '{$filePath}')";

            if (!$dbc->query($sql)) {
                throw new Exception("Unable to create fileId");
            }
        }

        return $uuid;
    }

    public function getDocument()
    {
        $attachments = PREFIJO_ANEXOS . $this->getModule();
        $requirements = TABLE_DOCUMENTO_ATRIBUTO;

        $sql = "SELECT uid_documento
        FROM {$attachments}
        JOIN {$requirements} using(uid_documento_atributo)
        WHERE fileId = '{$this->getUID()}'";

        $uidDocumento = db::get($sql, 0, 0);

        return new documento($uidDocumento);
    }

    public function obtenerSolicitudDocumentos()
    {
        $tableElem = TABLE_DOCUMENTOS_ELEMENTOS;
        $attachments = PREFIJO_ANEXOS . $this->getModule();
        $moduleElement = str_replace("historico_", "", $this->getModule());

        $SQL = "SELECT uid_documento_elemento FROM {$attachments} attach
        INNER JOIN $tableElem de
        ON  attach.uid_documento_atributo = de.uid_documento_atributo
        AND attach.uid_{$moduleElement} = de.uid_elemento_destino
        AND attach.uid_empresa_referencia = de.uid_empresa_referencia
        AND attach.uid_agrupador = de.uid_agrupador
        WHERE fileId = '{$this->getUID()}'";

        $requests = $this->db->query($SQL, "*", 0, "solicituddocumento");
        if ($requests) {
            return new ArrayRequestList($requests);
        }

        return false;
    }

    public function getFile()
    {
        $anexo = $this->getAnexo();
        if (!$anexo) {
            return false;
        }

        return $anexo->getFullPath();
    }

    public function getModule()
    {
        return $this->module;
    }

    public function fromHistory()
    {
        if (strpos($this->module, "historico") !== false) {
            return true;
        }

        return false;
    }

    public function getAnexo()
    {
        $sql = "SELECT uid_anexo_{$this->getModule()}
            FROM ". PREFIJO_ANEXOS ."{$this->getModule()}
            WHERE fileId = '{$this->getUID()}' LIMIT 1";

        $uid = db::get($sql, 0, 0);

        if ($uid) {
            $anexo = new anexo($uid, $this->getModule());

            return $anexo;
        }

        return false;
    }

	public function getElement() {
		$module = $this->getModule();
		$class  = str_replace('historico_', '', $module);
		$tableName = PREFIJO_ANEXOS ."{$module}";

		$sql = "SELECT uid_{$class} FROM {$tableName} WHERE fileId = '{$this->getUID()}' LIMIT 1";
		$uid = db::get($sql, 0, 0);
		if ($uid) {
			return new $class($uid);
		}

		return false;
	}

    public function updateDate($emision, $expiracion = null, $usuario, DelayedStatus $delayedStatus = null, $duration = null, $attachments = null)
    {
        if (null === $attachments) {
            $attachments = $this->getAttachments(null, false, null, false, true);
        }

        $estado =  true;

        foreach ($attachments as $attachment) {
            if ("" === $emision) {
                $emision = $attachment->getDate();
            }

            $updated = $attachment->updateDate($emision, $expiracion, $usuario, $delayedStatus, $duration);
            if ($updated !== true) {
                $estado = $updated;
                break;
            }
        }

        return $estado;
    }

    public function getAttachments(empresa $partner = null, $others = false, ArrayIntList $owners = null, $random = false, $force = false, $onlyWithRequests = true)
    {
        $where  = [];
        $modulo = $this->getModule();
        if ($partner instanceof empresa) {
            $empresasPartner = $partner->getEmpresasPartnersAsPartner();
            $filters = $subFiltersAND = array();

            foreach ($empresasPartner as $empresaPartner) {
                $subFiltersAND  = array();
                $subFiltersAND[] = " uid_empresa_propietaria = {$empresaPartner->getCompany()->getUID()}";
                $custom = $empresaPartner->isCustom();
                if (isset($custom)) {
                    $subFiltersAND[] .= " is_custom = $custom";
                }

                if (!$others) {
                    $subFiltersAND[] = " language = {$empresaPartner->getLanguage()}";
                }

                if (isset($subFiltersAND) && count($subFiltersAND)) {
                    $filters[] =  " ( ".implode(" AND ", $subFiltersAND). " ) ";
                }
            }
        } else {
            $filters[] = " 1 ";
        }

        if (isset($filters) && count($filters)) {
            $where[] = " (". implode(" OR ", $filters). " )";
        }

        if (!$force) {
            $where[] = ($random) ? " ((estado = 2 OR estado = 4) AND (estado != 2 AND reverse_status != 1)) " : " (estado = 1 OR (estado = 2 AND reverse_status = 1)) ";
        }

        $attachments = PREFIJO_ANEXOS . $modulo;
        $requirements = TABLE_DOCUMENTO_ATRIBUTO;

        $sql = "SELECT uid_anexo_$modulo as uid_anexo
        FROM {$attachments}
        INNER JOIN {$requirements} using(uid_documento_atributo)
        WHERE fileId = '{$this->getUID()}'";

        if (isset($where) && count($where)) {
            $sql .= " AND " . implode(" AND ", $where);
        }

        if (true === is_countable($owners) && count($owners)) {
            $ownersList = count($owners) ? $owners->toComaList() : '0';
            $sql .= " AND uid_empresa_propietaria IN ($ownersList) ";
        }

        $anexosIds = $this->db->query($sql, "*", 0);
        $anexos = array();
        if ($anexosIds) {
            foreach ($anexosIds as $id) {
                $attachment = new anexo($id, $modulo);

                if (true === $onlyWithRequests && false === $attachment->getSolicitud()) {
                    continue;
                }

                $anexos[] = $attachment;
            }

            if ($anexos) {
                return new ArrayAnexoList($anexos);
            }
        }

        return new ArrayAnexoList();
    }

    public function getAttachmentsGroupingByCompany(empresa $partner = null, $others = false, ArrayObjectList $owners = null, $force = false, $onlyWithRequests = true)
    {
        $anexoByCompany = new ArrayObjectList();
        $anexos = $this->getAttachments($partner, $others, $owners, false, $force, $onlyWithRequests);
        foreach ($anexos as $anexo) {
            $atributo = $anexo->obtenerDocumentoAtributo();
            $owner = $atributo->getCompany();
            $anexoByCompany[$owner->getUID()][] = $anexo;
        }

        return new ArrayObjectList($anexoByCompany);
    }

    public function getAttributes()
    {
        $modulo = $this->getModule();
        $sql = "SELECT uid_documento_atributo FROM " . PREFIJO_ANEXOS ."$modulo WHERE fileId = '{$this->getUID()}'";
        $documentosAttr = $this->db->query($sql, "*", 0, "documento_atributo");

        return new ArrayObjectList($documentosAttr);
    }

    public function getCompanyApplicant()
    {
        $modulo = $this->getModule();
        $sql = "SELECT uid_empresa_propietaria FROM ( SELECT uid_documento_atributo FROM " . PREFIJO_ANEXOS ."$modulo WHERE fileId = '{$this->getUID()}'
                    ) as anexos
                 INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." using(uid_documento_atributo) GROUP BY uid_empresa_propietaria";
        $uidEmpresa = $this->db->query($sql, 0, 0);

        if (is_numeric($uidEmpresa)) {
            return new empresa($uidEmpresa);
        }

        return false;
    }

    public function isOtherAssigned($usuario)
    {
        $modulo = $this->getModule();
        if (strpos($modulo, "historico") !== false) {
            return false;
        }

        $attachmentTable = PREFIJO_ANEXOS . $modulo;
        $userTable = TABLE_USUARIO;
        $profileTable = TABLE_PERFIL;
        $attachedStatus = documento::ESTADO_ANEXADO;
        $validatedStatus = documento::ESTADO_VALIDADO;

        $sql = "SELECT screen_uid_usuario
            FROM {$attachmentTable} anexo
            JOIN {$userTable} u ON u.uid_usuario = anexo.screen_uid_usuario
            JOIN {$profileTable} p ON p.uid_perfil = u.perfil
            WHERE screen_uid_usuario != {$usuario->getUID()}
            AND p.uid_empresa = {$usuario->getCompany()->getUID()}
            AND screen_uid_usuario is NOT NULL
            AND fileId = '{$this->getUID()}'
            AND (estado = {$attachedStatus} OR (estado = {$validatedStatus} AND reverse_status = 1))
            GROUP BY fileId";

        return (bool) $this->db->query($sql, 0, 0);
    }

    public static function getModuleOfFileId($fileId)
    {
        $moduloItems = anexo::getModules();
        $unionPart = array();

        foreach ($moduloItems as $modulo) {
            $unionPart[] = "SELECT '$modulo' as module FROM ". PREFIJO_ANEXOS ."$modulo WHERE fileId = '{$fileId}' GROUP BY fileId";
        }

        if (isset($unionPart) && count($unionPart)) {
            $sql = implode(" UNION ", $unionPart);
        }

        return db::get($sql, 0, 0);
    }

    public function getValidatior()
    {
        $modulo = $this->getModule();

        $sql = "SELECT val.uid_usuario FROM ". TABLE_VALIDATION ." val INNER JOIN ". TABLE_VALIDATION_STATUS ." using(uid_validation)
                    INNER JOIN ". PREFIJO_ANEXOS ."$modulo ON uid_anexo_$modulo = uid_anexo
                         WHERE fileId = '{$this->getUID()}'

                UNION

                SELECT val.uid_usuario FROM ". TABLE_VALIDATION ." val INNER JOIN ". TABLE_VALIDATION_STATUS ." using(uid_validation)
                    INNER JOIN ". PREFIJO_ANEXOS_HISTORICO ."$modulo using(uid_anexo)
                         WHERE fileId = '{$this->getUID()}'";

        $uidUsuario = $this->db->query($sql, 0, 0);

        if (is_numeric($uidUsuario)) {
            return new usuario($uidUsuario);
        }

        return false;
    }
}
