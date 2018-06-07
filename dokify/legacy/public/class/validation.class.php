<?php

class validation extends solicitable implements Ivalidation, Ielemento{

    const MIN_TIME_VALIDATE = 60;
    const MAX_TIME_VALIDATE_NORMAL = 172800;
    const MAX_TIME_VALIDATE_URGENT = 86400;
    const TYPE_VALIDATION_URGENT = "urgente";
    const TYPE_VALIDATION_NORMAL = "normal";
    const TYPE_VALIDATION_OTHERS = "others";
    const TYPE_VALIDATION_STATS = "stats";
    const TYPE_VALIDATION_REVIEW = "review";
    const TYPE_VALIDATION_AUDIT = "audit";
    const STATUS_AUDIT_OK = "ok";
    const STATUS_AUDIT_WRONG = "wrong";

    public function __construct($param, $extra = false){
        $this->tipo = "validation";
        $this->tabla = TABLE_VALIDATION;

        $this->instance( $param, $extra );
    }

    /**
     * check if this validation is urgent or not
     * @return boolean if it is urgent or no
     */
    public function isUrgent ()
    {
        return (bool) $this->obtenerDato('is_urgent');
    }

    /**
     * create a validation "concept"
     * @param  empresa $company     [description]
     * @param  usuario $usuario     [description]
     * @param  [type]  $status      [description]
     * @param  [type]  $attachments the assoc validations
     * @return [validation|false] return the newly create validation or false
     */
    public static function create (
        empresa $company,
        usuario $usuario,
        $status,
        $attachments
    ) {

        $dbc            = new db();
        $anexo          = reset($attachments);
        $language       = $anexo->obtenerLanguage();
        $partner        = $company->getPartner($language);
        $num            = count($attachments);
        $userCompany    = $usuario->getCompany();
        $isUrgent       = (int) $anexo->isUrgent();
        $table          = TABLE_VALIDATION;
        $userId         = $usuario->getUID();
        $companyId      = $userCompany->getUID();

        if ($partner === false && $empresaPartner = empresaPartner::getEmpresasPartners($company, null, null, true, true)) {
            $partner = $empresaPartner->getPartner();
        }

        $validationByPartner = $partner instanceof empresa && $userCompany->compareTo($partner);

        if ($validationByPartner) {
            $partnerId              = $partner->getUID();
            $totalCostValidation    = $partner->getValidationPrice($isUrgent);
            $unitCostValidation     = $totalCostValidation/count($attachments);
            $partnerCost            = $partner->getCost();

            $sql = "INSERT INTO {$table} (
                num_anexos, uid_partner, uid_empresa_validadora, uid_usuario, date, amount, amount_partner, is_urgent
            ) VALUES (
                $num, $partnerId, {$companyId}, {$userId}, now(), $totalCostValidation, $partnerCost, $isUrgent
            )";
        } else {
            $unitCostValidation = 0;
            $sql = "INSERT INTO {$table} (
                num_anexos, uid_empresa_validadora, uid_usuario, date, is_urgent
            ) VALUES (
                $num, {$companyId}, {$usuario->getUID()}, now(), $isUrgent
            )";
        }

        if (!$dbc->query($sql)) {
            error_log("erro creando la validacion");
            return false;
        }

        $idValidation = $dbc->getLastId();

        foreach ($attachments as $attachment) {
            if (!$docAttr = $attachment->obtenerDocumentoAtributo()) {
                return false;
            }

            $companyPaymentId   = 0;
            $uploaderCompany    = $attachment->getUploaderCompany();
            $owner              = $docAttr->getCompany();
            $language           = $attachment->obtenerLanguage();
            $filters            = array('language' => $language);
            $empresaPartner     = null;

            if ($validationByPartner) {
                $empresaPartner = empresaPartner::getEmpresasPartners($owner, $partner, $filters, true, true);
            }

            if ($isUrgent && $uploaderCompany) {
                $companyPaymentId = $uploaderCompany->getUID();
            } elseif ($empresaPartner instanceof empresaPartner) {
                $payMethod = $empresaPartner->getPaymentMethod();
                $isPartner = $userCompany->compareTo($partner);

                if ($payMethod === empresaPartner::PAYMENT_SELF && !$isUrgent && $isPartner) {
                    $companyPaymentId = $owner->getUID();
                } elseif ($payMethod === empresaPartner::PAYMENT_ALL && $isPartner && $uploaderCompany) {
                    $companyPaymentId = $uploaderCompany->getUID();
                }
            }

            // Check if the uploader company has a client paying for it
            if ($uploaderCompany && $companyPaymentId === $uploaderCompany->getUID()) {
                $app = Dokify\Application::getInstance();

                $clientPayResponse = $app['company.client_pay']->execute(
                    $app['company.client_pay']->createRequest((int) $uploaderCompany->getUID())
                );

                if (true === $clientPayResponse->hasClientPay()) {
                    $companyPaymentId = (int) $clientPayResponse->client()->getAsNumber();
                }
            }

            $attachment->update(array("uid_empresa_payment" => $companyPaymentId));

            $uidModule  = util::getModuleId($anexo->getModuleName());
            $table      = TABLE_VALIDATION_STATUS;

            $sql        = "INSERT INTO {$table} (
                uid_validation, uid_anexo, status, amount,uid_modulo,uid_empresa_payment,
                language, uid_documento_atributo, uid_empresa_propietaria
            ) VALUES (
                $idValidation, {$attachment->getUID()}, $status, $unitCostValidation, $uidModule, $companyPaymentId,
                '{$language}', {$docAttr->getUID()}, {$owner->getUID()}
            )";
            if (!$dbc->query($sql)) {
                error_log("erro creando la validacion status");
                return false;
            }
        }

        return new validation($idValidation);
    }

    public function getUserVisibleName(){
        $info = $this->getInfo();
        return "validation-".$this->getUID();
    }

    public function getNumAttachments(){
        $info = $this->getInfo();
        return $info["num_anexos"];
    }

    public function getPartner(){
        $info = $this->getInfo();
        return new empresa($info["uid_partner"]);
    }

    public function getCompany(){
        $info = $this->getInfo();
        return new empresa($info["uid_empresa_validadora"]);
    }

    public function getUser(){
        $info = $this->getInfo();
        return new usuario($info["uid_usuario"]);
    }

    public function getDate(){
        $info = $this->getInfo();
        return $info["date"];
    }

    public function getTimestamp ($offset = 0) {
        $timestamp = strtotime($this->getDate());
        if (!$timestamp) return false;

        $timestamp = $timestamp - (3600 * $offset); // adjuts timezone offset
        return $timestamp;
    }

    public function getAmoutValidation(){
        $totalAmount = db::get("SELECT sum(amount) FROM " .TABLE_VALIDATION_STATUS. " WHERE uid_validation = {$this->getUID()}", 0, 0);
        return $totalAmount;
    }

    public function getValidationAttachmentsStatus(){
        $validationStatus = $this->db->query("SELECT uid_validation_status FROM " .TABLE_VALIDATION_STATUS. " WHERE uid_validation = {$this->getUID()}", "*", 0, "validationStatus");
        return new ArrayObjectList($validationStatus);
    }

    public function isRejected()
    {
        $validationStatuses = $this->getValidationAttachmentsStatus();
        $validationStatus = reset($validationStatuses);

        return validationStatus::STATUS_REJECTED === (int) $validationStatus->getStatus();
    }

    public function auditOk(Iusuario $user)
    {
        $this->audit(self::STATUS_AUDIT_OK, $user);
    }

    public function auditWrong(Iusuario $user)
    {
        $this->audit(self::STATUS_AUDIT_WRONG, $user);
    }

    public function audit($auditStatus, Iusuario $user)
    {
        $sql = "UPDATE {$this->tabla}
        SET audit_status = '{$auditStatus}'
        ,   audit_date = NOW()
        ,   audit_user = {$user->getUID()}
        WHERE uid_validation = {$this->getUID()}";

        $this->db->query($sql);
    }

    public function assignToAuditUser(usuario $usuario)
    {
        $timeSeen = date("Y-m-d H:i:s", time() + fileId::ASSIGN_TIME);

        $sql = "UPDATE {$this->tabla}
        SET screen_audit_uid_usuario = {$usuario->getUID()},
            screen_audit_time_seen = '{$timeSeen}'
        WHERE uid_validation = {$this->getUID()}
        ";

        return $this->db->query($sql);
    }

    public function isAssignedToAuditOther(usuario $usuario)
    {
        $seenBy = $this->obtenerDato("screen_audit_uid_usuario");

        if ('' === $seenBy) {
            return false;
        }

        if ($seenBy === $usuario->getUID()) {
            return false;
        }

        return true;
    }

    public function getFileId()
    {
        $validationStatuses = $this->getValidationAttachmentsStatus();

        if (0 === count($validationStatuses)) {
            return false;
        }

        $validationStatus = reset($validationStatuses);
        $attachment = $validationStatus->getAttachment();
        return $attachment->getFileId();
    }

    public static function releasingValidationsFromAuditor()
    {
        $db = db::singleton();
        $validationTable = TABLE_VALIDATION;

        $sql = "UPDATE {$validationTable}
        SET screen_audit_uid_usuario = NULL
        ,   screen_audit_time_seen = 0
        WHERE screen_audit_uid_usuario IS NOT NULL
        AND screen_audit_time_seen < NOW()";

        $db->query($sql);
    }

    public static function getPendingValidationInvoiced(empresa $empresa = null){
        $validation = db::get("SELECT uid_validation FROM " .TABLE_INVOICE. " val  LEFT OUTER JOIN " .TABLE_TRANSACTION. " using(custom) INNER JOIN " .TABLE_INVOICE_ITEM. " ii using(uid_invoice) INNER JOIN " . TABLE_VALIDATION_STATUS. " vs ON ii.uid_reference = vs.uid_validation_status WHERE uid_paypal is null", "*", 0, "validation");
        return new ArrayObjectList($validation);
    }

    public static function cronCall($time, $force = false, $tipo = NULL)
    {
        // run every day 1
        $isTime = date("d H:i", $time) === "01 03:00";

        if (!$isTime && !$force) {
            return true;
        }

        $plantilla = new Plantilla();
        $log = log::singleton();

        if (CURRENT_ENV == 'dev') {
            $address = email::$developers;
        } else{
            $address = email::$facturacion;
        }

        $log->info("email facturacion", "resumen pagos partner", implode(', ', $address));
        $totalValidationPerPartner = array();
        $partners = empresa::getAllPartners();
        $validations = array();

        $firstDay = (new DateTime('first day of last month'))->setTime(0, 0);
        $lastDay = (new DateTime('last day of last month'))->setTime(23, 59, 59);

        echo "\nIncluyendo validaciones entre {$firstDay->format('d-m-y H:i:s')} y {$lastDay->format('d-m-y H:i:s')}";
        echo "\nTenemos #". count($partners) ." Partners\n\n";

        $firstDay = $firstDay->getTimestamp();
        $lastDay = $lastDay->getTimestamp();

        foreach ($partners as $partner) {
            echo "Partner: {$partner->getUserVisibleName()} [{$partner->getUID()}] \n";
            $validationsCompany = array();
            $validationCompanies = $partner->getValidationCompanies();
            echo "Encontradas ". count($validationCompanies) ." empresas para las que valida \n\n";

            foreach ($validationCompanies as $company) {
                echo "Calculando validaciones de la empresa {$company->getUserVisibleName()} [{$company->getUID()}] \n\n";
                $validationsCompany[] = $company->getValidations($partner, $firstDay, $lastDay)->getResume();
            }

            if (count($validationsCompany)) {
                $totalValidationPerPartner[] = [
                    "partner" => $partner,
                    "items" => $validationsCompany
                ];
            }
        }

        $infolog = "Email facutación partner";

        $htmlPath ='email/validation/invoiceFacturation.tpl';
        $plantilla->assign("totalValidationPerPartner", $totalValidationPerPartner);
        $html = $plantilla->getHTML($htmlPath);
        $email = new email($address);
        $email->establecerContenido($html);
        $email->establecerAsunto("Resumen pago validaciones partner");

        $estado = $email->enviar();
        if ($estado !== true) {
            $estado = $estado && trim($estado) ? trim($estado) : 'error_desconocido';
            $log->resultado("error $estado", true);
            echo "error enviando email de facturación partners\n";
            return false;
        }

        echo "Email enviado correctamente.\n";
        $log->resultado("ok ", true);

        return true;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $arrayCampos = new FieldList();
        $arrayCampos["num_anexos"] = new FormField();
        $arrayCampos["uid_partner"] = new FormField();
        $arrayCampos["uid_empresa_validadora"] = new FormField();
        $arrayCampos["uid_usuario"] = new FormField();
        $arrayCampos["date"] = new FormField();

        return $arrayCampos;

    }
}
