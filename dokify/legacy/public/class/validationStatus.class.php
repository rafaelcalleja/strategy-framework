<?php

class validationStatus extends solicitable implements IvalidationStatus, Ielemento
{
    const STATUS_VALIDATED = 2;
    const STATUS_REJECTED = 4;

    public function __construct($param, $extra = false){
        $this->tipo = "validationStatus";
        $this->tabla = TABLE_VALIDATION_STATUS;
        $this->instance( $param, $extra );
    }

    public function getUserVisibleName(){
        $info = $this->getInfo();
        return "validationStatus";
    }

    public function getAttachment(){
        $info = $this->getInfo();
        $type = str_replace ("anexo_", "", $this->getRequestableModuleName());


        if (strstr($type, 'historico')){
            $anexo = db::get("SELECT uid_anexo_$type FROM " .PREFIJO_ANEXOS. "$type WHERE uid_anexo = ".$info["uid_anexo"], 0, 0);
            return new anexo_historico($anexo,  $type);
        }
        return new anexo($info["uid_anexo"],  $type);
    }

    public function getStatus(){
        $info = $this->getInfo();
        return $info["status"];
    }

    public function getAmount(){
        $info = $this->getInfo();
        return $info["amount"];
    }

    public function getRequestableModule(){
        $info = $this->getInfo();
        return $info["uid_modulo"];
    }

    public function getRequestableModuleName(){
        return util::getModuleName($this->getRequestableModule());
    }

    public function getValidation(){
        $info = $this->getInfo();
        $validationId = $info["uid_validation"];
        if (is_numeric($validationId)) {
            $val = new validation($validationId);
            return $val;
        }
        return false;

    }

    public function getDate () {
        $validation = $this->getValidation();
        if ($validation) return $validation->getDate();
        return false;
    }

    public function validar(usuario $usuario){
        $this->update(array("status"=>documento::ESTADO_VALIDADO));
    }

    public function anular(usuario $usuario){
        $this->update(array("status"=>documento::ESTADO_ANULADO));
    }

    public function changeStatus($status, usuario $usuario){

        switch ($status) {
            case documento::ESTADO_VALIDADO:
                $return = $this->validar($usuario);
                break;
            case documento::ESTADO_ANULADO:
                $return = $this->anular($usuario);
                break;
            default:
                return false;
        }

        if ($return) {
            $validation = $this->getValidation();
            return $validation->update(array("usuario"=>$usuario->getUID(), "uid_empresa_validadora"=>$usuario->getCompany()->getUID()));
        }

        return false;
    }

    /**
     * Create an invoice items based on this validationStatus
     * @param  invoice $invoice     [the invoice to add to]
     * @return [invoiceItem|false] the newly created invoice or false if it fails
     */
    public function createInvoiceItem(invoice $invoice)
    {
        $anexo      = $this->getAttachment();
        $atributo   = $anexo->obtenerDocumentoAtributo();

        if (!$atributo instanceof documento_atributo) {
            error_log("Creando invoice status");
            error_log("Se ha encontrado un anexo con uid: {$anexo->getUID()} que no tiene documento atributo");
            return false;
        }

        $unitAmount = $this->getAmount();
        $validation = $this->getValidation();
        $partner    = $validation->getPartner();
        $owner      = $atributo->getCompany();
        $language   = $anexo->obtenerLanguage();
        $date       = $this->getDate();
        $filters    = array('language' => $language);

        if (!$empresaPartner = empresaPartner::getEmpresasPartners($owner, $partner, $filters, true, true)) {
            if (!$empresaPartner = empresaPartner::getEmpresasPartners($owner, $partner, null, true, true)) {
                // Get partner when realtionship between ppartner and company has been deleted
                if (!$empresaPartner = empresaPartner::getEmpresasPartners($owner, null, $filters, true, true)) {
                    $empresaPartner = empresaPartner::getEmpresasPartners($owner, null, null, true, true);
                }
            }
        }

        $configuredParner   = $partner instanceof empresa && $empresaPartner instanceof empresaPartner;
        $isUrgent           = $validation->isUrgent();
        if ($configuredParner && false === $isUrgent && ($variation = $empresaPartner->getVariation())) {
            $percentage = $variation / 100;
            $variation  = $unitAmount * $percentage;
            $unitAmount = $unitAmount + $variation;
        }

        $dataItem = array(
            "uid_invoice"   => $invoice->getUID(),
            "uid_reference" => $this->getUID(),
            "description"   => invoiceItem::DESCRIPTION_VALIDATION,
            "amount"        => $unitAmount,
            "num_items"     => 1,
            "date"          => $date,
            "uid_modulo"    => util::getModuleId('validationStatus')
        );

        $invoiceItem = new invoiceItem($dataItem, null);

        // if item is not in the bbdd
        if (false === $invoiceItem->exists()) {
            error_log("Error: can't create invoice item from validation status {$this->getUID()}");
            return false;
        }

        return $invoiceItem;
    }

    public static function getValidationStatus($anexo){
        $validationStatus = db::get("SELECT uid_validation_status FROM " .TABLE_VALIDATION_STATUS. " WHERE uid_anexo = {$anexo->getUID()}", "*", 0, "validationStatus");
        return reset($validationStatus);
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $arrayCampos = new FieldList();
        $arrayCampos["status"] = new FormField();
        $arrayCampos["uid_modulo"] = new FormField();

        return $arrayCampos;
    }
}
