<?php
class requirementTypeRequest extends extendedArray implements IrequirementTypeRequest
{

    private $requirements;
    private $requested;
    private $db;

    public function __construct($requirements, $requested){

        $this->requirements = (is_array($requirements)) ? new ArrayObjectList($requirements) : $requirements;
        $this->requested = $requested;
        $this->db = db::singleton();
    }

    /**
     * get last comment not deleted
     * @param  usuario $user
     * @return null|ArrayObjectList Returns the last comment not deleted in a ArrayObjectList or null if there isn't any comment
     */
    public function getLastComment($user){
        $comments = $this->getComments($user, false, 1, false, ['deleted' => false]);

        if (count($comments)) {
            return $comments[0];
        }

        return null;
    }

    public function getComments(Iusuario $usuario = NULL, $count = false, $limit = false, $offset = 0, $opts = []) {

        $company = $usuario->getCompany();
        $moduleName = $this->requested->getModuleName();
        $requirementsList = ($this->requirements && count($this->requirements)) ? $this->requirements->toComaList() : '0';

        $sqlFilers = '';
        $filters = array();

        $commonFROM = PREFIJO_COMENTARIOS ."$moduleName c
            INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." de
            ON  uid_elemento_destino = uid_{$this->requested->getModuleName()}
                AND c.uid_documento_atributo = de.uid_documento_atributo
                AND c.uid_agrupador = de.uid_agrupador
                AND c.uid_empresa_referencia = de.uid_empresa_referencia
            WHERE 1
            AND uid_{$moduleName} = {$this->requested->getUID()}
            AND uid_documento_elemento IN ($requirementsList)

        ";

        if (is_numeric($offset) && !$count && $limit != 1) {
            // -- fractionating all comments in sets by attachments
            $offsetLimit = ($offset > 0) ? $offset - 1 : 0;
            $sqlLimit = "
                SELECT MIN(uid_comentario_$moduleName)
                FROM $commonFROM
                AND (action = ". comment::ACTION_ATTACH ." OR action = ". comment::ACTION_CHANGE_DATE .")
                GROUP BY commentId
                ORDER BY date DESC
                LIMIT $offsetLimit, 1
            ";

            $offsetTop = $this->db->query($sqlLimit, 0, 0);

            $sqlLimit = "
                SELECT uid_comentario_$moduleName
                FROM $commonFROM
                AND (action = ". comment::ACTION_ATTACH ." OR action = ". comment::ACTION_CHANGE_DATE .")
                GROUP BY commentId
                ORDER BY date DESC
                LIMIT $offset, 1
            ";

            $offsetBottom = $this->db->query($sqlLimit, 0, 0);

            if ($offset == 0 && $offsetTop) {
                $filters[] = " uid_comentario_$moduleName >= $offsetTop";

            } elseif ($offset != 0){

                // --- there is not more pagination
                if (!$offsetTop && !$offsetBottom) return new ArrayObjectList;


                if ($offsetTop) $filters[] = "uid_comentario_$moduleName < $offsetTop";
                if ($offsetBottom) $filters[] = "uid_comentario_$moduleName >= $offsetBottom";
            }

        }

        if (isset($opts['deleted'])) {
            $delete = $opts['deleted'] ? '1' : '0';
            $filters[] = " deleted = {$delete} ";
        }

        if (count($filters)) $sqlFilers = " AND ". implode(" AND ", $filters);

        $sql = " $commonFROM $sqlFilers GROUP BY commentId";

        if ($count) {
            $sql = "SELECT count(total) FROM (SELECT uid_comentario_$moduleName as total FROM $sql) as record";
            return $this->db->query($sql, 0,0);
        }

        $sql = "SELECT uid_comentario_$moduleName FROM $sql ORDER BY date DESC, uid_comentario_$moduleName DESC ";
        if ($limit) $sql .= " limit $limit";

        $comments = $this->db->query($sql, true);
        $commentColection = new ArrayObjectList;
        if ($comments && count($comments)) {
            foreach ($comments as $comment) {
                $commentColection[] = new comment($comment["uid_comentario_$moduleName"], $this->requested);
            }
        }
        return $commentColection;
    }

    public function getCommentsId(Iusuario $usuario = NULL, $count = false, $limit = false, $offset = 0) {

        $company = $usuario->getCompany();
        $moduleName = $this->requested->getModuleName();
        $requirementsList = ($this->requirements && count($this->requirements)) ? $this->requirements->toComaList() : '0';

        $sqlFilers = '';
        $filters = array();

        $commonFROM = PREFIJO_COMENTARIOS ."$moduleName c
            INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." de
            ON  uid_elemento_destino = uid_{$this->requested->getModuleName()}
                AND c.uid_documento_atributo = de.uid_documento_atributo
                AND c.uid_agrupador = de.uid_agrupador
                AND c.uid_empresa_referencia = de.uid_empresa_referencia
            WHERE 1
            AND uid_{$moduleName} = {$this->requested->getUID()}
            AND uid_documento_elemento IN ($requirementsList)

        ";

        if (is_numeric($offset) && !$count && $limit != 1) {
            // -- fractionating all comments in sets by attachments
            $offsetLimit = ($offset > 0) ? $offset - 1 : 0;
            $sqlLimit = "
                SELECT MIN(uid_comentario_$moduleName)
                FROM $commonFROM
                AND (action = ". comment::ACTION_ATTACH ." OR action = ". comment::ACTION_CHANGE_DATE .")
                GROUP BY commentId
                ORDER BY date DESC
                LIMIT $offsetLimit, 1
            ";

            $offsetTop = $this->db->query($sqlLimit, 0, 0);

            $sqlLimit = "
                SELECT uid_comentario_$moduleName
                FROM $commonFROM
                AND (action = ". comment::ACTION_ATTACH ." OR action = ". comment::ACTION_CHANGE_DATE .")
                GROUP BY commentId
                ORDER BY date DESC
                LIMIT $offset, 1
            ";

            $offsetBottom = $this->db->query($sqlLimit, 0, 0);

            if ($offset == 0 && $offsetTop) {
                $filters[] = " uid_comentario_$moduleName >= $offsetTop";

            } elseif ($offset != 0){

                // --- there is not more pagination
                if (!$offsetTop && !$offsetBottom) return new ArrayObjectList;


                if ($offsetTop) $filters[] = "uid_comentario_$moduleName < $offsetTop";
                if ($offsetBottom) $filters[] = "uid_comentario_$moduleName >= $offsetBottom";
            }

        }

        if (count($filters)) $sqlFilers = " AND ". implode(" AND ", $filters);

        $sql = " $commonFROM $sqlFilers GROUP BY commentId";

        if ($count) {
            $sql = "SELECT count(total) FROM (SELECT uid_comentario_$moduleName as total FROM $sql) as record";
            return $this->db->query($sql, 0,0);
        }

        $sql = "SELECT commentId FROM $sql ORDER BY date DESC, uid_comentario_$moduleName DESC ";
        if ($limit) $sql .= " limit $limit";

        $comments = $this->db->query($sql, true);
        $commentColection = new ArrayObjectList;
        if ($comments && count($comments)) {
            foreach ($comments as $comment) {
                $commentColection[] = new commentId($comment["commentId"], $moduleName);
            }
        }
        return $commentColection;
    }

    public function saveComment(
        $comment,
        Iusuario $usuario = null,
        $action = 0,
        $assigned = false,
        $reply = false,
        ValidationArgument $argument = null,
        DelayedStatus $delayedStatus = null,
        $related = null
    ) {
        if (!count($this->requirements)) {
            return false;
        }

        $commentRelative = commentId::createCommentId();


        if (!$comment) {
            if ($argument instanceof ValidationArgument) {
                $replyComment = new commentId($reply, $this->requested->getModuleName());
                $replyUser = $replyComment->getCommenter();
                $lang = ($replyUser instanceof usuario) ? $replyUser->obtenerDato("locale") : null;
                $comment = $argument->getCommentText($lang);
            } else {
                $comment = "";
            }
        }

        $comment = trim($comment); //removing new lines and tabs on the left

        if (mb_detect_encoding($comment, "UTF-8")) {
            $comment = utf8_decode($comment);
        }


        $comment = htmlentities($comment, ENT_COMPAT, 'ISO-8859-1');
        $comment = db::scape($comment);

        $moduleName = $this->requested->getModuleName();
        $date = time();
        $usuarioId = ($usuario instanceof Iusuario) ? $usuario->getUID() : 'NULL';
        $usuarioModule = ($usuario instanceof Iusuario) ? $usuario->getModuleId() : 'NULL';
        $related = isset($related) ? "'" . $related . "'" : 'NULL';

        if ($assigned == watchComment::AUTOMATICALLY_ATTACHMENT || $assigned == watchComment::AUTOMATICALLY_CHANGE_DATE) {
            $this->unWatchThread();
        }

        $argument = $argument instanceof ValidationArgument ? $argument->getUID() : 'NULL';

        $reverseDate = 'NULL';
        $reverseStatus = 'NULL';
        if ($delayedStatus instanceof DelayedStatus) {
            $reverseDate = $delayedStatus->getReverseDate();
            $reverseStatus = $delayedStatus->getReverseStatus();
        }

        foreach ($this->requirements as $requirement) {
            $documentoAtributo = $requirement->obtenerDocumentoAtributo();
            $agrupador = ($agrupador = $requirement->obtenerAgrupadorReferencia()) ? $agrupador->getUID() : 0;
            $sql = "INSERT INTO ". PREFIJO_COMENTARIOS ."$moduleName
                ( uid_documento_atributo, uid_$moduleName, uid_agrupador, uid_empresa_referencia,
                 comment, action, argument, date, uid_commenter, uid_module_commenter, commentId, replyId, reverse_date, reverse_status, related)
             VALUES
                ({$documentoAtributo->getUID()}, {$this->requested->getUID()}, '{$agrupador}',
                '{$requirement->obtenerIdEmpresaReferencia()}', '$comment', '$action', $argument, FROM_UNIXTIME({$date}),
                {$usuarioId}, {$usuarioModule}, '$commentRelative',
                ". db::valueNull($reply) .", $reverseDate, $reverseStatus, $related)";

            if (!$this->db->query($sql)) {
                error_log($this->db->lastError());
            }

            if ($assigned) {
                $sql = "INSERT IGNORE INTO ". TABLE_WATCH_COMMENT ."_$moduleName
                    ( uid_documento_atributo, uid_$moduleName, uid_agrupador, uid_empresa_referencia,
                     uid_watcher, uid_module_watcher, assigned)
                 VALUES
                    ({$documentoAtributo->getUID()}, {$this->requested->getUID()}, '{$agrupador}',
                    '{$requirement->obtenerIdEmpresaReferencia()}', {$usuarioId}, {$usuarioModule},
                    '$assigned')";

                if (!$this->db->query($sql)) {
                    error_log($this->db->lastError());
                }
            }
        }


        return new commentId($commentRelative, $moduleName);
    }


    public function getRequesterCompanies () {
        $comaList = $this->requirements->toComaList();
        $SUBSQL = "SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." WHERE uid_documento_elemento IN ({$comaList})";
        $SQL = "SELECT IF (uid_modulo_origen = 1, uid_elemento_origen, uid_empresa_propietaria) uid FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_documento_atributo IN ($SUBSQL) GROUP BY uid_empresa_propietaria";

        // our array with companies
        $array = $this->db->query($SQL, "*", 0, 'empresa');


        if ($array && count($array)) {
            return new ArrayObjectList($array);
        }

        new ArrayObjectList;
    }


    public function getOwnerCompanies () {
        $comaList = $this->requirements->toComaList();
        $SUBSQL = "SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." WHERE uid_documento_elemento IN ({$comaList})";
        $SQL = "SELECT uid_empresa_propietaria FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_documento_atributo IN ($SUBSQL) GROUP BY uid_empresa_propietaria";

        // our array with companies
        $array = $this->db->query($SQL, "*", 0, 'empresa');

        if ($array && count($array)) {
            return new ArrayObjectList($array);
        }

        new ArrayObjectList;
    }


    public function unWatchThread (Iusuario $user = NULL) {

        $moduleName = $this->requested->getModuleName();
        $moduleId = util::getModuleId($moduleName);
        $requirementsList = ($this->requirements && count($this->requirements)) ? $this->requirements->toComaList() : '0';

        $sql = "
            DELETE wc FROM ". TABLE_WATCH_COMMENT ."_{$moduleName} wc
                INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." de
                ON  uid_elemento_destino = wc.uid_{$moduleName}
                    AND wc.uid_documento_atributo = de.uid_documento_atributo
                    AND wc.uid_agrupador = de.uid_agrupador
                    AND wc.uid_empresa_referencia = de.uid_empresa_referencia
                    AND de.uid_modulo_destino = {$moduleId}
                WHERE 1
                AND uid_documento_elemento IN ($requirementsList)
            ";
        if ($user instanceof Iusuario) {
            $sql .= " AND uid_watcher = {$user->getUID()} AND uid_module_watcher = {$user->getModuleId()} ";
        }

        return $this->db->query($sql);

    }

}
