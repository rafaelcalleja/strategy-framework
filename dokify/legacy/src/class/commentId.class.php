<?php
class commentId implements IcommentId {

    const REPLY_TO_HOST = "comments.dokify.net";
    const REPLY_TO_PREFIX = "reply-";
    const DATE_UNTIL_NEW_COMMENTS_REPLY_ALLERT = "26-09-2013";
    protected $uid;
    protected $module;
    protected $db;


    const EMAIL_PIECE_COMMENTID     = 'c';
    const EMAIL_PIECE_USERID        = 'u';
    const EMAIL_PIECE_USERMODULE    = 'um';
    const EMAIL_PIECE_ENV           = 'e';
    const EMAIL_PIECE_TARGET        = 't';


    public function __construct($commentId, $module = NULL){
        $this->uid = db::scape($commentId);
        $this->db = db::singleton();


        $moduleItems = solicitable::getModules();

        // --- si no nos preestablecen el módulo
        if (!in_array($module, $moduleItems)) {
            $unionPart = array();
            foreach ($moduleItems as $module) {
                $unionPart[] = "
                    SELECT '$module' as module
                    FROM ". PREFIJO_COMENTARIOS ."$module
                    WHERE commentId = '{$commentId}'
                    GROUP BY commentId
                ";
            }

            if (isset($unionPart) && count($unionPart)) $sql = implode(" UNION ", $unionPart);
            $module = $this->db->query($sql, 0, 0);
        }

        if ($module) $this->module = $module;
        else throw new Exception("commentId {$commentId} not exists");
    }

    public static function getRouteName () {
        return 'commentid';
    }

    public function getModule () {
        return $this->module;
    }

    public function getUID(){
        return $this->uid;
    }

    public function isCron () {
        $sql = "
            SELECT uid_commenter FROM ". PREFIJO_COMENTARIOS ."{$this->module}
            WHERE commentId = '{$this->getUID()}'
            LIMIT 1
        ";

        $uid = $this->db->query($sql, 0, 0);
        return is_numeric($uid) === false;
    }

    public function isDeleted () {
        $sql = "
            SELECT deleted FROM ". PREFIJO_COMENTARIOS ."{$this->module}
            WHERE commentId = '{$this->getUID()}'
            LIMIT 1
        ";

        $deleted = $this->db->query($sql, 0, 0);
        return (bool) $deleted == 1;
    }


    public function getValidationArgument(){
        $sql = "SELECT argument FROM ". PREFIJO_COMENTARIOS ."{$this->module}
                WHERE commentId = '{$this->getUID()}'
                GROUP BY commentId";

        if ($uid = $this->db->query($sql, 0, 0)) {
            return new ValidationArgument($uid);
        }

        return false;
    }

    public function getCommenterModule(){
        $sql = "SELECT uid_module_commenter FROM ". PREFIJO_COMENTARIOS ."{$this->module}
                WHERE commentId = '{$this->getUID()}'
                GROUP BY commentId";

        return $this->db->query($sql, 0, 0);
    }

    public function getCommenter(){

        $sql = "SELECT uid_commenter FROM ". PREFIJO_COMENTARIOS ."{$this->module}
                WHERE commentId = '{$this->getUID()}'
                GROUP BY commentId";

        $commenterId = $this->db->query($sql, 0, 0);
        if (is_numeric($commenterId)) {
            if (($moduleId = $this->getCommenterModule()) && is_numeric($moduleId)) {
                $module = util::getModuleName($moduleId);
                return new $module($commenterId);
            }
        }

        return false;
    }


    public function getReply () {

        $sql = "SELECT replyId FROM ". PREFIJO_COMENTARIOS ."{$this->module}
                WHERE commentId = '{$this->getUID()}'
                GROUP BY commentId";

        $replyId = $this->db->query($sql, 0, 0);

        if ($replyId) {
            return new commentId($replyId, $this->module);
        }

        return false;
    }


    public function getReplyUser () {
        if ($replyCommentId = $this->getReply()) {
            return $replyCommentId->getCommenter();
        }

        return false;
    }

    public function getElement() {

        $sql = "SELECT uid_{$this->module} FROM ". PREFIJO_COMENTARIOS ."{$this->module}
                WHERE commentId = '{$this->getUID()}'
                GROUP BY commentId";

        $elementId = $this->db->query($sql, 0, 0);
        if (is_numeric($elementId)) {
            $module = $this->module;
            return new $module($elementId);
        }

        throw new Exception("No se ha encontrado el elemento para el commentId: {$this->getUID()}");
    }


    public function getDate($offset = 0) {

        $sql = "SELECT UNIX_TIMESTAMP(date) FROM ". PREFIJO_COMENTARIOS ."{$this->module}
                WHERE commentId = '{$this->getUID()}'
                GROUP BY commentId";

        if ($timestamp = $this->db->query($sql, 0, 0)) {
            return $timestamp - (3600 * $offset); // adjuts timezone offset
        }

        return 0;

    }

    public function getDocument() {

        $sql = "
            SELECT uid_documento FROM ". PREFIJO_COMENTARIOS ."{$this->module}
            INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." using(uid_documento_atributo)
            WHERE commentId = '{$this->getUID()}'
            LIMIT 1
        ";

        $uidDocumento = $this->db->query($sql, 0, 0);

        $element = $this->getElement();
        if ($uidDocumento && $element) {
            return new documento($uidDocumento, $element);
        }

        throw new Exception("No se ha encontrado el documento para el commentId: {$this->getUID()}");

    }

    public function getComment() {

        $sql = "SELECT comment FROM ". PREFIJO_COMENTARIOS ."{$this->module}
                WHERE commentId = '{$this->getUID()}'
                GROUP BY commentId";

        return $this->db->query($sql, 0, 0);
    }

    public function getAction() {

        $sql = "SELECT action FROM ". PREFIJO_COMENTARIOS ."{$this->module}
                WHERE commentId = '{$this->getUID()}'
                GROUP BY commentId";

        return $this->db->query($sql, 0, 0);
    }


    /**
     * Get commentId related field, for example for explain the actrion has been done from a connector
     * @return [null,string] Returns the related field
     */
    public function getRelated()
    {
        $table = PREFIJO_COMENTARIOS;

        $sql = "SELECT related FROM {$table}{$this->module}
        WHERE commentId = '{$this->getUID()}'
        GROUP BY commentId";

        return $this->db->query($sql, 0, 0);
    }

    public function getActionInfo()
    {
        $action                 = $this->getAction();
        //notes: John has commented: bla bla bla
        $commentVerb            = _("has commented");
        //notes: John has attached a document
        $documentActionTemplate = _("%action-verb% a document");

        switch ($action) {
            case comment::NO_ACTION:
                $name           = "comment";
                $verb           = $commentVerb;
                $template       = "%action-verb%";
                $class          = "grey";
                $icon           = "chat-bubble-outline blue";
                break;
            case comment::ACTION_ATTACH:
                $name           = "attach";

                if ($this->isCron()) {
                    //notes: John has changed status to attached
                    $verb       = _("has changed status");
                    //notes: John has changed status to attached
                    $template   = _("%action-verb% to attached");
                } else {
                    //notes: John has attached a document
                    $verb       = _("has attached");
                    $template   = $documentActionTemplate;
                }

                $class          = "blue";
                $icon           = "attach-file blue";
                break;
            case comment::ACTION_VALIDATE:
                $name           = "validate";
                //notes: John has validated a document
                $verb           = _("has validated");
                $template       = $documentActionTemplate;
                $class          = "green";
                $icon           = "check green";
                break;
            case comment::ACTION_DELETE:
                $name           = "delete";
                //notes: John has deleted a document
                $verb           = _("has deleted");
                $template         = $documentActionTemplate;
                $class          = "black";
                $icon           = "trash black";
                break;
            case comment::ACTION_REJECT:
                $name           = "reject";
                //notes: John has rejected a document
                $verb           = _("has rejected");
                $template       = $documentActionTemplate;
                $class          = "red";
                $icon           = "cancel red";
                break;
            case comment::ACTION_EMAIL:
                $name           = "email";
                $verb           = $commentVerb;
                $template       = "%action-verb%";
                $class          = "grey";
                $icon           = "email";
                break;
            case comment::ACTION_CHANGE_DATE:
                $name           = "change-date";
                //notes: John has updated the date
                $verb           = _("has updated");
                //notes: John has updated the date
                $template       = _("%action-verb% the date");
                $class          = "grey";
                $icon           = "calendar blue";
                break;
            case comment::ACTION_EXPIRE:
                $name           = "expire";
                //notes: Dokify has expired a document
                $verb           = _("has expired");
                $template       = $documentActionTemplate;
                $class          = "orange";
                $icon           = "access-time orange";
                break;
            case comment::ACTION_SIGN:
                $name           = "sign";
                //notes: John has signed and validated a document
                $verb           = _("has signed and validated");
                $template       = $documentActionTemplate;
                $class          = "green";
                $icon           = "sign green";
                break;
        }

        return [
            "class"     => $class,
            "html"      => str_replace("%action-verb%", "<strong>$verb</strong>", $template),
            "icon"      => $icon,
            "name"      => $name,
            "plaintext" => str_replace("%action-verb%", $verb, $template),
            "uid"       => $action
        ];
    }

    public function getWatchers()
    {
        $module            = $this->module;
        $moduleId          = util::getModuleId($module);
        $from              = $this->getCommenter();
        $element           = $this->getElement();
        $requirements      = $this->affectTo();
        $requirementsList  = $requirements && count($requirements) ? $requirements->toComaList() : '0';
        $watchCommentTable = TABLE_WATCH_COMMENT . "_{$module}";
        $requestTable      = TABLE_DOCUMENTOS_ELEMENTOS;

        $sql = "
            SELECT wc.uid_watcher, wc.uid_module_watcher FROM {$watchCommentTable} wc
            INNER JOIN {$requestTable} de
            ON  de.uid_elemento_destino = wc.uid_{$module}
                AND de.uid_documento_atributo = wc.uid_documento_atributo
                AND de.uid_agrupador = wc.uid_agrupador
                AND de.uid_empresa_referencia = wc.uid_empresa_referencia
            WHERE uid_modulo_destino = {$moduleId}
            AND wc.uid_{$module} = {$element->getUID()}
            AND uid_documento_elemento IN ($requirementsList)
        ";

        if ($from instanceof Iusuario) {
            $sql .= " AND wc.uid_watcher != {$from->getUID()} ";
        }

        $sql .= " GROUP BY wc.uid_watcher, wc.uid_module_watcher ";

        $rows = $this->db->query($sql, true);

        $watchers = new ArrayObjectList();
        foreach ($rows as $userData) {
            $moduleWatcher = util::getModuleName($userData["uid_module_watcher"]);
            if ($moduleWatcher && is_numeric($userData["uid_watcher"])) {
                $usuario = new $moduleWatcher($userData["uid_watcher"]);
                if ($usuario->isActiveWatcher()) {
                    $watchers[] = $usuario;
                }
            }

        }

        return $watchers;

    }

    public function isFromUrgent() {

        $requirements = $this->affectTo();
        $module = $this->module;
        $moduleId = util::getModuleId($module);
        $element = $this->getElement();
        $requirements = $this->affectTo();
        $requirementsList = $requirements && count($requirements) ? $requirements->toComaList() : '0';

        $SQL = "
            SELECT is_urgent FROM ". PREFIJO_COMENTARIOS ."{$module} c
            INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." de
            ON  de.uid_elemento_destino = c.uid_{$module}
                AND de.uid_documento_atributo = c.uid_documento_atributo
                AND de.uid_agrupador = c.uid_agrupador
                AND de.uid_empresa_referencia = c.uid_empresa_referencia
            INNER JOIN ". PREFIJO_ANEXOS ."{$module} a
                ON  de.uid_elemento_destino = a.uid_{$module}
                AND de.uid_documento_atributo = a.uid_documento_atributo
                AND de.uid_agrupador = a.uid_agrupador
                AND de.uid_empresa_referencia = a.uid_empresa_referencia
            WHERE uid_modulo_destino = {$moduleId}
            AND uid_documento_elemento IN ($requirementsList)
            GROUP BY fileId limit 1
        ";

        return (bool) $this->db->query($SQL, 0, 0);
    }

    public function reply($comment, Iusuario $usuario = NULL, $action = comment::NO_ACTION) {

        if (!$comment = trim($comment)) return false;
        $requirements = $this->affectTo($usuario);

        $reqType = new requirementTypeRequest($requirements, $this->getElement());
        return $reqType->saveComment($comment, $usuario, $action, watchComment::AUTOMATICALLY_COMMENT, $this->getUID());
    }


    public static function mountEmailAddress ($commentId, Iusuario $user) {
        $pieces = array(
            self::EMAIL_PIECE_COMMENTID     => $commentId,
            self::EMAIL_PIECE_USERID        => $user->getUID(),
            self::EMAIL_PIECE_USERMODULE    => $user->getModuleId(),
            self::EMAIL_PIECE_ENV           => CURRENT_ENV
        );

        if (CURRENT_ENV == 'dev')  $pieces[self::EMAIL_PIECE_TARGET] = CURRENT_DOMAIN;

        $encoder = self::getEncoder();
        $text = http_build_query($pieces);
        $hash = $encoder->encode($text);

        return self::REPLY_TO_PREFIX . $hash . "@" . self::REPLY_TO_HOST;
    }

    public static function unmountEmailAddress ($address, $encrypted = false, $base64 = false) {
        $encoder = self::getEncoder();

        // Geting the whole-hash
        list($account, $host) = explode("@",  $address);

        // --- if we use a name in the reply to ...
        if (($pos = strpos($account, self::REPLY_TO_PREFIX)) !== 0) {
            $account = substr($account, $pos);
        }

        // Remove reply- from our hash
        $encoded = substr($account, strlen(self::REPLY_TO_PREFIX));

        if ($base64) {
            $decoded = util::base64_email_decode($encoded);
        } else {
            $decoded = $encoder->decode($encoded);
        }

        // -- try to decrypt
        if ($encrypted) $decoded = util::decrypt($decoded);

        parse_str($decoded, $data);

        // -- return false if no data
        if (!$data || !count($data)) {
            // -- try in base64
            if (!$base64) return self::unmountEmailAddress($address, false, true);

            // -- try to decrypt
            if (!$encrypted) return self::unmountEmailAddress($address, true, true);
            throw new Exception('cant read account data');
        }

        // result array
        $result = array();

        // --- save comment id to result
        if (isset($data[self::EMAIL_PIECE_COMMENTID])) {
            $result[] = $data[self::EMAIL_PIECE_COMMENTID];
        } else {
            // -- try in base64
            if (!$base64) return self::unmountEmailAddress($address, false, true);

            // -- try to decrypt
            if (!$encrypted) return self::unmountEmailAddress($address, true, true);
            throw new Exception('invalid comment Id');
        }


        // --- check element [user|employee] id
        if (isset($data[self::EMAIL_PIECE_USERID]) && isset($data[self::EMAIL_PIECE_USERMODULE])&& is_numeric($data[self::EMAIL_PIECE_USERID])) {
            $module = util::getModuleName($data[self::EMAIL_PIECE_USERMODULE]);
            $result[] = new $module($data[self::EMAIL_PIECE_USERID]);
        } else throw new Exception('invalid commenter id');

        // --- check env
        if (isset($data[self::EMAIL_PIECE_ENV])) {
            $result[] = $data[self::EMAIL_PIECE_ENV];
        } else throw new Exception('invalid enviroment');

        // --- check target
        if (isset($data[self::EMAIL_PIECE_TARGET])) {
            $result[] = $data[self::EMAIL_PIECE_TARGET];
        } else {
            $result[] = CURRENT_ENV;
        }


        // --- ready for "list($commentID, $usuario) = "
        return $result;
    }

    private function affectedReqtypes()
    {
        $sql = "
            SELECT uid_documento FROM ". PREFIJO_COMENTARIOS ."{$this->module}
            INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." using(uid_documento_atributo)
            WHERE commentId = '{$this->getUID()}'
        ";

        if ($reqtypes = $this->db->query($sql, "*", 0, "documento")) {
            return new ArrayObjectList($reqtypes);
        }

        return new ArrayObjectList;
    }

    public function affectTo(Iusuario $user = null, $requestFilter = null)
    {
        $element = $this->getElement();

        if (!$element) {
            return new ArrayObjectList;
        }

        $sql = "SELECT uid_documento_elemento FROM ". PREFIJO_COMENTARIOS ."{$this->module} c
                INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." de
                ON  uid_elemento_destino = uid_{$this->module}
                    AND c.uid_documento_atributo = de.uid_documento_atributo
                    AND c.uid_agrupador = de.uid_agrupador
                    AND c.uid_empresa_referencia = de.uid_empresa_referencia
                INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." da
                ON c.uid_documento_atributo = da.uid_documento_atributo
                WHERE commentId = '{$this->getUID()}'
                AND uid_{$this->module} = {$element->getUID()}";

        if ($user instanceof Iusuario) {
            $reqtypes        = $this->affectedReqtypes();
            $reqtypeRequests = new ArrayObjectList;

            foreach ($reqtypes as $reqtype) {
                $reqtypeRequests = $reqtypeRequests->merge($reqtype->obtenerSolicitudDocumentos($element, $user));
            }

            $reqtypeRequestsList = count($reqtypeRequests) ? $reqtypeRequests->toComaList() : '0';
            $sql                 .= " AND uid_documento_elemento IN ($reqtypeRequestsList) ";
        }

        if ($requestFilter instanceof solicituddocumento) {
            $sql .= " AND uid_documento_elemento = {$requestFilter->getUID()} ";
        }

        $sql .= " GROUP BY uid_documento_elemento ";

        if ($requests = $this->db->query($sql, "*", 0, "solicituddocumento")) {
            return new ArrayObjectList($requests);
        }

        return new ArrayObjectList;

    }

    public function getAttachments (Iusuario $user = NULL) {
        $requests = $this->affectTo($user);

        $attachments = new ArrayAnexoList;
        foreach ($requests as $req) {
            if ($attachment = $req->getAnexo()) {
                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    public function getDelayedStatus() {
        $sql = "SELECT reverse_status FROM ". PREFIJO_COMENTARIOS ."{$this->module}
                WHERE commentId = '{$this->getUID()}'
                GROUP BY commentId";

        $reverseStatus = $this->db->query($sql, 0, 0);

        $sql = "SELECT reverse_date FROM ". PREFIJO_COMENTARIOS ."{$this->module}
                WHERE commentId = '{$this->getUID()}'
                GROUP BY commentId";

        $reverseDate = $this->db->query($sql, 0, 0);

        if (is_numeric($reverseStatus) && is_numeric($reverseDate)){
            return new DelayedStatus((int) $reverseStatus, (int)$reverseDate);
        }

        return false;
    }

    public function getStaticAlertMessage ($locale = Plantilla::DEFAULT_LANGUAGE) {
        $message = false;
        if ($argument = $this->getValidationArgument()) {
            $message = $argument->getStaticAlertMessage(false, $locale);
        }

        if (!$message && $delayedStatus = $this->getDelayedStatus()) {
            $currentStatus  = NULL;

            if ($attachments = $this->getAttachments()) {
                $currentStatus = $attachments[0]->getStatus();
            }

            $message = $delayedStatus->getMessage($currentStatus);
        }

        return $message;
    }

    public function getCommentIdFixLink ($locale = Plantilla::DEFAULT_LANGUAGE) {
        $link = false;
        if ($argument = $this->getValidationArgument()) {
            $link = $argument->getCommentIdFixLink($this, true, $locale);
        }

        if (!$link && $delayedStatus = $this->getDelayedStatus()) {
            $link = $delayedStatus->getCommentIdFixLink($this);
        }

        return $link;
    }

    public function getClassMessage () {
        $class = false;
        if ($argument = $this->getValidationArgument()) {
            $class = $argument->getClassMessage();
        }

        if (!$class && $delayedStatus = $this->getDelayedStatus()) {
            $class = $delayedStatus->getClassMessage();
        }

        return $class;
    }

    public function showNewNotice(){
        $now = time();
        $deadline = strtotime(self::DATE_UNTIL_NEW_COMMENTS_REPLY_ALLERT);
        return $now < $deadline;
    }

    public function deleteComment(){

        $sql = "UPDATE ". PREFIJO_COMENTARIOS ."{$this->module} SET deleted = '1'
                WHERE commentId = '{$this->getUID()}'";

        return $this->db->query($sql);
    }

    public function editComment($text){
        $text = trim(db::scape($text));
        if( !trim($text) ){ return false; }

        $sql = "UPDATE ". PREFIJO_COMENTARIOS ."{$this->module} SET comment = '". utf8_decode($text) ."'
                WHERE commentId = '{$this->getUID()}'";
        return $this->db->query($sql);
    }

    public static function createCommentId(){

        $commentRelative = buscador::getRandomKey();
        $moduleItems = solicitable::getModules();

        $unionPart = array();
        foreach ($moduleItems as $module) {
            $unionPart[] = " SELECT commentId FROM ". PREFIJO_COMENTARIOS ."$module WHERE commentId = '{$commentRelative}' ";
        }

        if (isset($unionPart) && count($unionPart)) $sql = implode(" UNION ", $unionPart);
        $existingRelativeComment = db::get($sql, 0, 0);

        if ($existingRelativeComment) { return commentId::createCommentId(); }

        return $commentRelative;
    }


    public function __toString () {
        return $this->getUID() . '-' . __CLASS__;
    }

    public static function getEncoder () {
        return new Base2n(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', FALSE, TRUE, TRUE);

    }
}