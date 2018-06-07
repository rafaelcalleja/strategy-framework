<?php

class ArrayAnexoList extends ArrayObjectList
{
    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Dokify\Domain\Attachment\Collection
     */
    public function asDomainEntity()
    {
        $attachmentArray = $this->map(function($attachment) {
            return $attachment->asDomainEntity();
        });

        $attachmentCollection = new \Dokify\Domain\Attachment\Collection($attachmentArray);

        return $attachmentCollection;
    }

    /***
       *    Get all status from attachment collection
       *
       *
       *
       */
    public function getStatuses ($class) {
        if (count($this) === 0) return new ArrayIntList;

        $db         = db::singleton();
        $view       = PREFIJO_ANEXOS . "{$class}";
        $set        = $this->toComaList();
        $status     = "IF (estado IS NULL, ". documento::ESTADO_PENDIENTE .", estado)";
        $SQL        = "SELECT {$status} FROM {$view} WHERE uid_anexo_{$class} IN ({$set}) GROUP BY estado";
        $statuses   = $db->query($SQL, '*', 0);


        return new ArrayIntList(array_map('intval', $statuses));
    }


    public function saveComment($comment, Iusuario $usuario = null, $action = 0, $logUI = true)
    {
        $db                 = db::singleton();
        $commentRelative    = commentId::createCommentId();

        if (!$comment) {
            $comment = "";
        }
        $comment = trim($comment); //removing new lines and tabs on the left

        if (mb_detect_encoding($comment, "UTF-8")) {
            $comment = utf8_decode($comment);
        }


        $comment = htmlentities($comment, ENT_COMPAT, 'ISO-8859-1');
        $comment = db::scape($comment);

        $reverseDate    = 'NULL';
        $reverseStatus  = 'NULL';

        $delayedStatusAnexos = array_filter($this->foreachCall('getDelayedStatus')->getArrayCopy());
        if (count($delayedStatusAnexos)) {
            $delayedStatus = $delayedStatusAnexos[0];
            if ($delayedStatus instanceof DelayedStatus) {
                $reverseDate    = $delayedStatus->getReverseDate();
                $reverseStatus  = $delayedStatus->getReverseStatus();
            }
        }

        $date = time();
        $usuarioId = ($usuario instanceof Iusuario) ? $usuario->getUID() : 'NULL';
        $usuarioModule = ($usuario instanceof Iusuario) ? $usuario->getModuleId() : 'NULL';

        foreach ($this as $attach) {
            $moduleName = @end(explode("_", $attach->getModuleName()));

            // If there aren't element or atribute document return false
            if (!$element = $attach->getElement()) {
                continue;
            }

            if (!$documentoAtributo = $attach->obtenerDocumentoAtributo()) {
                continue;
            }

            $agrupador = ($agrupador = $attach->obtenerAgrupadorReferencia()) ? $agrupador->getUID() : 0;
            $sql = "INSERT INTO ". PREFIJO_COMENTARIOS ."$moduleName
                ( uid_documento_atributo, uid_$moduleName, uid_agrupador, uid_empresa_referencia,
                 comment, action, date, uid_commenter, uid_module_commenter, commentId, reverse_date, reverse_status)
             VALUES
                ({$documentoAtributo->getUID()}, {$element->getUID()}, '{$agrupador}',
                '{$attach->obtenerIdEmpresaReferencia()}', '$comment', '$action', FROM_UNIXTIME({$date}),
                {$usuarioId}, {$usuarioModule}, '$commentRelative', $reverseDate, $reverseStatus)";

            if (!$db->query($sql)) {
                error_log($attach->lastError());
                return false;
            }
        }

        return new commentId($commentRelative, $moduleName);
    }

    public function getPartners() {
        $partners = new ArrayObjectList();

        foreach ($this as $anexo) {
            if ($anexo instanceof anexo) {
                $partner = $anexo->getPartner();
                if ($partner instanceof empresa) {
                    $partners[] = $partner;
                }
            }
        }

        return $partners->unique();
    }

    public function getValidated () {
        $validated = new ArrayAnexoList();

        foreach ($this as $anexo) {
            if ($anexo->isValidated()) {
                $validated[] = $anexo;
            }
        }

        return $validated;
    }

    public function getAttached () {
        $attached = new ArrayAnexoList();

        foreach ($this as $anexo) {
            if ($anexo->getStatus() == documento::ESTADO_ANEXADO) {
                $attached[] = $anexo;
            }
        }

        return $attached;
    }

    public function getLastToExpire () {
        $lastAttachment = NULL;

        foreach ($this as $attachment) {
            $attachmentExpiration = $attachment->getExpirationTimeStamp();
            if ($attachmentExpiration == 0) {
                return $attachment;
            }

            if (isset($lastAttachment)) {
                $lastAttachmentExpiration = $lastAttachment->getExpirationTimeStamp();
                $lastAttachment = $lastAttachmentExpiration < $attachmentExpiration ? $attachment : $lastAttachment;
            } else {
                $lastAttachment = $attachment;
            }
        }

        return $lastAttachment;
    }

    public function getMostRecent()
    {
        $arrayAttachment = $this->getArrayCopy();
        $mostRecentAttachment = array_reduce($arrayAttachment, function ($mostRecentAttachment, $attachment) {
            if (null === $mostRecentAttachment) {
                return $attachment;
            }

            if ($mostRecentAttachment->getRealTimestamp() < $attachment->getRealTimestamp()) {
                return $attachment;
            }

            return $mostRecentAttachment;
        });

        return $mostRecentAttachment;
    }

    public function getBestReattachable () {
        $validatedReattachables = $this->getValidated();

        $bestGroupAttachables = count($validatedReattachables) ? $validatedReattachables : $this->getAttached();

        $bestReattachable = count($bestGroupAttachables) ? $bestGroupAttachables->getMostRecent() : false;

        return $bestReattachable;
    }


    public function review (usuario $user) {

        foreach ($this as $attachment) {
            $attachment->revisar($user);
        }

        return true;
    }

    public function writeLogUI($texto, $value = "", Iusuario $user = null){
        foreach ($this as $attachment) {
            $attachment->writeLogUI($texto, $value, $user);
        }

        return true;
    }


    }