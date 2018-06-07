<?php

class ValidationArgument
{
    const WRONG_DATE_MAX_DAYS = 2;

    const DEFAULT_MAX_DAYS = 5;
    const DEFAULT_STATUS_CHANGE = documento::ESTADO_ANULADO;

    const WRONG_DATE = 1;
    const FIXED_DATE = 2;

    private $uid = false;

    /***
       *    Instance must recive a valid argument id
       *
       *
       *
       */
    public function __construct($uid)
    {
        if (!in_array($uid, self::getAll())) {
            throw new InvalidArgumentException('$uid must be a valid ValidationArgument id');
        }

        $this->uid = $uid;
    }

    /***
       *    Returns the argument ID
       *
       *
       *
       */
    public function getUID()
    {
        return $this->uid;
    }

    /***
       *    The document state for this argument
       *
       *
       *
       */
    public function getDocumentStatus()
    {
        switch ($this->uid) {
            case self::WRONG_DATE:
                return documento::ESTADO_VALIDADO;
                break;
        }
    }

    /***
       *    Get the HTML with a link to fix the problem
       *
       *
       *
       */
    public function getCommentIdFixLink(commentId $commentId, $html = true, $locale = Plantilla::DEFAULT_LANGUAGE)
    {
        $item = $commentId->getElement();
        $document = $commentId->getDocument();

        return $this->getFixLink($item, $document, $commentId, $html, $locale);
    }

    /***
       *    Get the HTML with a link to fix/inform the problem
       *
       *
       *
       */
    public function getAttachmentFixLink(anexo $attachment, $html = true)
    {
        $item = $attachment->getElement();
        $document = $attachment->obtenerDocumento();

        return $this->getFixLink($item, $document, $attachment, $html);
    }

    /***
       *    Get the HTML with a link to fix the problem
       *
       *
       *
       */
    protected function getFixLink(solicitable $item, documento $document, $fix, $html = true, $locale = Plantilla::DEFAULT_LANGUAGE)
    {
        $lang = new Plantilla();
        $lang->assign('lang', $locale);

        $module = $item->getModuleName();
        $link = CURRENT_DOMAIN . "/agd/#documentocomentario.php?m={$module}&o={$item->getUID()}&poid={$document->getUID()}&fix={$fix}";

        if ($html === false) {
            return $link;
        }

        switch ($this->uid) {
            case self::WRONG_DATE:
                $string = $lang->getString('click_cambiar_fecha', $locale);

                return sprintf($string, $link);
                break;
        }

        return false;
    }

    /***
       *    Get the literal for the subject of the notification email
       *
       *
       *
       */
    public function getEmailSubject($literal = false, $locale = Plantilla::DEFAULT_LANGUAGE)
    {
        $lang = new Plantilla();
        $lang->assign('lang', $locale);

        switch ($this->uid) {
            case self::WRONG_DATE:
                $key = 'new_comment_validate_temporary';
                if (true == $literal) {
                    return $key;
                }

                return $lang->getString($key, $locale);

                break;
            case self::FIXED_DATE:
                $key = 'fixed_date';
                if (true == $literal) {
                    return $key;
                }

                return $lang->getString($key, $locale);

                break;
        }

        return false;
    }

    /***
       *    Get the the alert message to display in comments and links
       *
       *
       *
       */
    public function getAlertMessage($literal = false, anexo $anexo, $locale = Plantilla::DEFAULT_LANGUAGE)
    {
        $lang = new Plantilla();
        $lang->assign('lang', $locale);

        switch ($this->uid) {
            case self::WRONG_DATE:
                $expireDate = date("d-m-Y", $anexo->getReverseDate());
                $key = 'documento_validado_date';
                if ($literal) {
                    return printf($key, $expireDate);
                }

                return sprintf($lang->getString($key, $locale), $expireDate);
                break;
        }

        return false;
    }

    /***
       *    Get the the alert message to display in comments and links
       *
       *
       *
       */
    public function getStaticAlertMessage($literal = false, $locale = Plantilla::DEFAULT_LANGUAGE)
    {
        $lang = new Plantilla();
        $lang->assign('lang', $locale);

        switch ($this->uid) {
            case self::WRONG_DATE:
                $key = 'documento_validado_hrs';

                if (true == $literal) {
                    return $key;
                }

                $days = $this->getReverseDate();
                $hrs  = round($days * 24);
                return sprintf($lang->getString($key, $locale), $hrs);
                break;
            case self::FIXED_DATE:
                $key = 'fixed_date';

                if (true == $literal) {
                    return $key;
                }

                return $lang->getString($key, $locale);
                break;
        }

        return false;
    }

    /***
       *    Get the the class alert message to display in comments
       *
       *
       *
       */
    public function getClassMessage()
    {
        $class = false;
        switch ($this->uid) {
            default:
                $class = 'comment-alert-red';
                break;
        }

        return $class;
    }

    /***
       *    The text to include in this validation comment
       *
       *
       *
       */
    public function getCommentText($locale = Plantilla::DEFAULT_LANGUAGE)
    {
        $lang = new Plantilla();
        $lang->assign('lang', $locale);

        switch ($this->uid) {
            case self::WRONG_DATE:
                return $lang->getString('wrong_date_argument', $locale);
                break;
            case self::FIXED_DATE:
                return $lang->getString('fixed_date_argument', $locale);
                break;
        }

        return false;
    }

    /***
       *    Return image information as structured data
       *
       *
       *
       */
    public function getDefaultImageInfo(anexo $anexo)
    {
        $imgInfo = array();
        $imgInfo['title'] = $this->getAlertMessage(false, $anexo). " - " . strip_tags($this->getAttachmentFixLink($anexo));
        $imgInfo['src'] = RESOURCES_DOMAIN . '/img/famfam/exclamation.png';
        $imgInfo['class'] = 'link';
        $imgInfo['href'] = $this->getAttachmentFixLink($anexo, false);

        return $imgInfo;
    }

    /***
       *    Return image information as structured data
       *
       *
       *
       */
    public function getImageInfo(anexo $anexo)
    {
        switch ($this->getUID()) {
            case self::WRONG_DATE:
                if ($anexo->dateUpdated()) {
                    return false;
                }

                $imgInfo = $this->getDefaultImageInfo($anexo);
                break;

            default:
                $imgInfo = $this->getDefaultImageInfo($anexo);
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
    public function getIcon(anexo $anexo)
    {
        switch ($this->getUID()) {
            case self::WRONG_DATE:
                if ($anexo->dateUpdated()) {
                    return false;
                }

                $img = 'calendar';
                break;

            default:
                $img = 'clock';
                break;
        }

        return $img;
    }

    /***
       *    Return wheter means a temoporary validation or not
       *
       *
       *
       */
    public function hasDelayedStatus()
    {
        return in_array($this->uid, self::getAllWithDelayedStatus());
    }

    /***
       *    Return DelayedStatus Object if ValdiationArgument has it
       *
       *
       *
       */
    public function getDelayedStatus(anexo $anexo = null)
    {
        if (!$this->hasDelayedStatus()) {
            return false;
        }

        // Si, por ejemplo, queremos que el delayedStatus de renovacion tenga prioridad sobre el delayedStatus de un argument tendriamos que incluir aqui la condicion
        switch ($this->getUID()) {
            default:
                $reverseStatus = $this->getReverseStatus();
                $reverseDate = $this->getReverseDate();
                $delayedStatus = new DelayedStatus($reverseStatus, $reverseDate);

                return $delayedStatus;
                break;
        }

        return false;
    }

    /***
       *    Returns the status to change
       *
       *
       *
       */
    private function getReverseStatus()
    {
        if (!$this->hasDelayedStatus()) {
            return false;
        }

        switch ($this->getUID()) {
            default:
                $reverseStatus = self::DEFAULT_STATUS_CHANGE;
                break;
        }

        return $reverseStatus;
    }

    /***
       *    Returns the date to change the Status
       *
       *
       *
       */
    private function getReverseDate()
    {
        if (!$this->hasDelayedStatus()) {
            return false;
        }

        switch ($this->getUID()) {
            case self::WRONG_DATE:
                $reverseDate = self::WRONG_DATE_MAX_DAYS;

                // add one more day in fridays
                if (date('w') === "5") {
                    $reverseDate++;
                }

                break;
            default:
                $reverseDate = self::DEFAULT_MAX_DAYS;
                break;
        }

        return $reverseDate;
    }

    /***
       *    Get all the arguments with means temporary validation
       *
       *
       *
       */
    public static function getAllWithDelayedStatus()
    {
        $arguments = array();

        $arguments[] = self::WRONG_DATE;

        return $arguments;
    }

    /***
       *    Get all the available arguments
       *
       *
       *
       */
    public static function getAll()
    {
        $arguments = array();

        $arguments[] = self::WRONG_DATE;
        $arguments[] = self::FIXED_DATE;

        return $arguments;
    }
}
