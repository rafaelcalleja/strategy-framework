<?php

class DelayedStatus
{

    const DEFAULT_CHANGE_DAYS   = 2;
    const RENOVATION_CHANGE_DAYS = 10;

    const MAX_DAYS_TO_CHANGE    = 365;

    // 2147483647 is the largest int value for mysql
    const MAX_TIMESTAMP_DATE    = 2000000000;

    private $reverseStatus = false;
    private $reverseDate = false;

    /***
       *
       *
       *
       *
       */
    public function __construct ($reverseStatus, $reverseDate)
    {
        if (is_int($reverseDate) && $reverseDate < self::MAX_DAYS_TO_CHANGE) {
            $this->reverseDate = strtotime('+'.$reverseDate.' days', time());
        } elseif (is_int($reverseDate)) {
            $this->reverseDate = $reverseDate;
        }

        if ($this->reverseDate == false || $this->reverseDate > self::MAX_TIMESTAMP_DATE) {
            throw new InvalidArgumentException('$reverseDate must be a valid value date or a valid int');
        }

        if (!in_array($reverseStatus, documento::getAllStatus())) {
            throw new InvalidArgumentException('$reverseStatus must be a valid Status Document id');
        } else {
            $this->reverseStatus = $reverseStatus;
        }

    }

    /***
       *    Returns the date to change the Status
       *
       *
       *
       */
    public function getReverseDate ($offset = 0)
    {
        $timestamp = $this->reverseDate;
        $timestamp = $timestamp - (3600 * $offset); // adjuts timezone offset
        return $timestamp;
    }

    /***
       *    Returns the status to change
       *
       *
       *
       */
    public function getReverseStatus ()
    {
        return $this->reverseStatus;
    }

    /***
       *    Return image information as structured data
       *
       *
       *
       */
    public function getImageInfo (anexo $attachment)
    {
        $imgInfo = array();
        switch ($this->getReverseStatus()) {
            case documento::ESTADO_ANEXADO:
                $imgInfo = array();
                $imgInfo['title'] = $this->getMessage();
                $imgInfo['src'] = RESOURCES_DOMAIN . '/img/famfam/arrow_refresh_small.png';
                $imgInfo['class'] = 'help';
                break;

            case documento::ESTADO_ANULADO:
                $item = $attachment->getElement();
                $document = $attachment->obtenerDocumento();
                $module = $item->getModuleName();
                $link = CURRENT_DOMAIN . "/agd/#documentocomentario.php?m={$module}&o={$item->getUID()}&poid={$document->getUID()}";
                $imgInfo = array();
                $imgInfo['title'] = $this->getMessage();
                $imgInfo['src'] = RESOURCES_DOMAIN . '/img/famfam/exclamation.png';
                $imgInfo['class'] = 'link';
                $imgInfo['href'] = $link;
                break;

            default:
                $imgInfo = array();
                $imgInfo['title'] = $this->getMessage();
                $imgInfo['src'] = RESOURCES_DOMAIN . '/img/famfam/exclamation.png';
                $imgInfo['class'] = 'help';
                break;
        }

        return $imgInfo;
    }

    /***
       *    Return image information as structured data
       *
       *
       *
       */
    public function getIcon (anexo $attachment)
    {
        switch ($this->getReverseStatus()) {
            case documento::ESTADO_ANEXADO:
                $img = 'sort';
                break;

            default:
                $img = 'clock';
                break;
        }

        return $img;
    }

    /***
       *    Return message with the revers date and the change status
       *
       *
       *
       */
    public function getMessage ($currentStatus = NULL)
    {
        $tpl = Plantilla::singleton();
        $language = $tpl->getCurrentLocale();
        $reverseStatus = $this->getReverseStatus();
        $reverseDate = $this->getReverseDate();
        $status = documento::status2String($reverseStatus, $language);
        $date = date("d/m/Y", $reverseDate);

        switch ($this->getReverseStatus()) {
            case documento::ESTADO_ANEXADO:
                $message = sprintf($tpl->getString('explain_request_renovation'), $date);
                break;

            default:
                $message = sprintf($tpl->getString('temporary_message'),$status, $date);

                if ($currentStatus == documento::ESTADO_VALIDADO)  {
                    $message = $tpl->getString('temporary_validated') . ". " . $message;
                }

                break;
        }

        return $message;
    }

    /***
       *    Get the the class alert message to display in comments
       *
       *
       *
       */
    public function getClassMessage ()
    {
        $class = false;
        switch ($this->getReverseStatus()) {
            case documento::ESTADO_ANULADO:
                $class = 'comment-alert-green';
                break;

            default:
                $class = 'comment-alert-red';
                break;
        }

        return $class;
    }

    /***
       *    Get the HTML with a link to fix/inform the problem
       *
       *
       *
       */
    public function getCommentIdFixLink (commentId $commentId, $html = true)
    {
        return false;
    }

    /**
     * Method to return de default delay in days
     * @return int
     */
    public function defaultChangeDays()
    {
      // add one more day in fridays
      if(date('w') === "5"){
        return self::DEFAULT_CHANGE_DAYS + 1;
      }

      return self::DEFAULT_CHANGE_DAYS;
    }

    /***
       *    Temporary document cronCall
       *
       *
       *
       */
    public static function cronCall ($time, $force = false, $tipo = NULL)
    {
        // a la una de la madrugada
        if (date("H:i", $time) == "01:00" || $force) {
            DelayedStatus::changeStatusAttachments();
        }

        return true;
    }

    /***
       *    Change the attachment status when the reverse date comes
       *
       *
       *
       */
    public static function changeStatusAttachments ()
    {
        $pwd = @$_SERVER["PWD"];
        $modules = solicitable::getModules();
        $db = db::singleton();

        foreach ($modules as $uid => $module) {
            $class = "anexo_{$module}";
            $table = PREFIJO_ANEXOS . $module;

            $SQL = "SELECT GROUP_CONCAT(uid_anexo_{$module}) intList, uid_{$module} as uid, uid_documento_atributo, reverse_status FROM {$table}
            WHERE reverse_date < UNIX_TIMESTAMP()
            GROUP BY uid_{$module}, fileId, reverse_status";

            if ($rows = $db->query($SQL, true)) {
                if ($count = count($rows)) {
                    $updated = 0;
                    if ($pwd) print "Encontrados {$count} solicitudes de documento de {$module}\n";

                    foreach ($rows as $data) {
                        $list   = $data['intList'];
                        $status = $data['reverse_status'];

                        if (!is_numeric($status)) {
                            error_log("{$module} attachments with uids ({$list}) doesnt have a valid reverse_status");
                            continue;
                        }

                        // --- instance object
                        $item = new $module($data['uid']);
                        $attr = new documento_atributo($data['uid_documento_atributo']);
                        $document = $attr->getDocumentByAttribute();

                        // --- instance attachments
                        $items = ArrayIntList::factory($list);
                        $attachments = new ArrayAnexoList ($items->toObjectList($class));

                        try {
                            if ($updated = $document->updateStatus($attachments, $status, NULL)) {
                                $updated++;

                                if (count($attachments)) {
                                    $commentId = $attachments->saveComment('', NULL, $status);
                                }
                            }
                        } catch(Exception $e){
                            echo "Error cron DelayedStatus ".$e->getMessage()."\n";
                            continue;
                        }

                        if ($pwd) print "Solicitud de documento ".documento::status2String($status, 'es')."\n";
                    }

                    if ($pwd) print "{$count} solicitudes de documento modificadas\n";
                }
            }
        }
    }


    /***
       *    toString methods (easy object comparision)
       *
       *
       *
       */
    public function __toString ()
    {
        return implode('-', [$this->reverseDate, $this->reverseStatus, __CLASS__]);
    }

}
