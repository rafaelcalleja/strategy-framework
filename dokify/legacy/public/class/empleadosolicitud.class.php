<?php

class empleadosolicitud extends solicitud implements Ielemento {

    public function __construct($param, $extra = false ){
        $this->tipo = __CLASS__;
        $this->tabla = TABLE_EMPLEADO . "_solicitud";
        $this->instance($param, $extra);
    }

    public function getEmployee(){
        $uid = $this->obtenerDato("uid_empleado");
        if( is_numeric($uid) ) return new empleado($uid);
        return false;
    }

    public function sendTransferEmployeeRequest($expired = false) {
        $tpl = "solicitud/empleado/transferencia/request";
        $params = array(
                    "request" => $this
                );
        if ($expired) {
            $params['days'] = $this->daysToExpired();
            $infolog = "email: Recordatorio de solicitud de empresa {$this->getSolicitante()->getUserVisibleName()} solicita la transferencia del empleado {$this->getItem()->getUID()}";
            $log = array('empleadosolicitud',$infolog, $this->getUID());
            $asunto = 'expired_alert_add_employee';
        } else {
            $infolog = "email: La empresa {$this->getSolicitante()->getUserVisibleName()} solicita la transferencia del empleado {$this->getItem()->getUID()}";
            $log = array('empleadosolicitud',$infolog, $this->getUID());
            $asunto = 'request_add_employee';
        }
        $empleado = $this->getEmployee();
        return $this->getUser()->getCompany()->sendEmailWithParams($asunto, $tpl, $params, $log, null, array($empleado->getEmail()));
    }

    public function sendDeniedTransferEmployee() {
        $tpl = "solicitud/empleado/transferencia/denied";
        $params = array(
                    "request" => $this
                );
        $infolog = "email: El empleado {$this->getEmployee()->getUserVisibleName()} rechaza la transferencia del empleado {$this->getItem()->getUID()}";
        $log = array('empleadosolicitud',$infolog, $this->getUID());
        $destinatarios = array();
        if ($emailUser = $this->getUser()->getEmail()) {
            $destinatarios[] = $emailUser;
        }
        $solicitante = $this->getSolicitante();
        $destinatarios[] = $solicitante->obtenerContactoPrincipal()->obtenerEmail();
        return $solicitante->sendEmailWithParams('denied_transfer_employee', $tpl, $params, $log, null, $destinatarios);
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $fields = new FieldList;
        $fields["uid_empleado"] = new FormField();
        $fields['uid_empresa_origen'] = new FormField();
        $fields["uid_elemento"] = new FormField();
        $fields["uid_modulo"] = new FormField();
        $fields["uid_usuario"] = new FormField();
        $fields["data"] = new FormField();
        $fields["estado"] = new FormField();
        $fields["type"] = new FormField();
        $fields['message'] = new FormField();
        $fields['token'] = new FormField();
        return $fields;
    }

    public function getUserVisibleName(){
        $tpl = Plantilla::singleton();
        switch ($this->getTypeOf()) {
            case self::TYPE_TRANSFERENCIA:
                return $tpl->getString('solicitud_transferencia_empleado');
            break;
        }
    }

    public function getToken(){
        return $this->obtenerDato('token');
    }

    public function deleteToken(){
        $db = db::singleton();
        $sql = "UPDATE {$this->tabla} SET token = NULL WHERE uid_empleado_solicitud = {$this->getUID()}";
        return $db->query($sql, 0, 0);
    }

    public static function getByToken($token) {
        $db = db::singleton();
        $sql = "SELECT uid_empleado_solicitud FROM ".TABLE_EMPLEADO . "_solicitud WHERE token = '".$token."'";
        if( $uid = $db->query($sql, 0, 0) ){
            return new empleadosolicitud ($uid);
        } else return false;
    }

    public function rechazar($comentario = null, Iusuario $usuario = null)
    {
    //  $this->deleteToken();
        $this->sendDeniedTransferEmployee();
        return parent::rechazar($comentario, $usuario);
    }

    // public function share($comentario = null, $usuario = null) {
    //  $this->deleteToken();
    //  return parent::share($comentario, $usuario);
    // }

    public function sendNotification() {
        switch ($this->getTypeOf()) {
            case solicitud::TYPE_TRANSFERENCIA:
                $days = $this->daysSinceCreated();
                switch ($days) {
                    case 4:
                        solicitud::sendExpiredTransferEmployee($this->getSolicitante(),$this->getItem(),$this->getUser());
                        $this->setState(solicitud::ESTADO_EXPIRED);
                        break;
                    case 3:
                        $this->sendTransferEmployeeRequest(true);
                        break;
                    case 1:
                        $this->sendTransferEmployeeRequest(true);
                        break;
                    default:
                        if ($days > 4) {
                        //  solicitud::sendExpiredTransferEmployee($this->getSolicitante(),$this->getItem(),$this->getUser());
                            $this->setState(solicitud::ESTADO_EXPIRED);
                        }
                        break;
                }
                break;

            default:
                return false;
                break;
        }
        return true;
    }

    public static function sendPendingNotifications() {
        $filter = array();
        $filter['type'] = solicitud::TYPE_TRANSFERENCIA;
        $filter['estado'] = solicitud::ESTADO_CREADA;
        $transferRequests = solicitud::getRequests('empleado', $filter);
        $result = true;
        foreach ($transferRequests as $transferRequest) {
            $result = $transferRequest->sendNotification() && $result;
        }
        return $result;
    }

    static public function cronCall($time, $force = false, $items = NULL){
        $m = date("i", $time);
        $h = date("H", $time);
        $w = date("w", $time);

        if( ($h == 07 && $m == 30) || $force ){
            $update = self::sendPendingNotifications();
        }

        return true;
    }
}
