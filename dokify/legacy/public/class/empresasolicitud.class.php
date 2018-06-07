<?php

class empresasolicitud extends solicitud implements Ielemento
{
    // número de horas hasta que se pueda reenviar un correo
    const RESEND_PERIOD = 24;

    public function __construct($param, $extra = false ){
        $this->tipo = __CLASS__;
        $this->tabla = TABLE_EMPRESA . "_solicitud";
        $this->instance($param, $extra);
    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Notification\Notification
     */
    public function asDomainEntity()
    {
        return $this->app['notification.repository']->factory($this->getInfo());
    }

    public static function getRouteName () {
        return 'notification';
    }

    public function getInPageAlert(){
        $tpl = Plantilla::singleton();

        $alert = array();
        $alert['titulo'] = $this->getTitle();
        $alert['id'] = $this->getUID();
        $alert['texto'] = $this->getText();
        $alert['tipo'] = $this->getTypeOf();
        $alert['className'] = $this->getClassName();

        return $alert;
    }

    public function getTitle(){
        $tpl = Plantilla::singleton();
        return $tpl('accion_requerida');
    }

    public function getURL(){
        $item = $this->getItem();
        $empresaSolicitante = $this->getSolicitante();

        switch( $type = $this->getTypeOf() ){
            case self::TYPE_ASIGNAR:
                return "#asignacion.php?poid={$item->getUID()}&m={$item->getModuleName()}&request={$this->getUID()}";
            break;
            case self::TYPE_TRANSFERENCIA:
                return "empleado/confirmartransferencia.php?poid={$this->getUID()}";
            break;
            case self::TYPE_CONTRATACION:
                return "empresa/confirmarcliente.php?poid={$this->getUID()}";
            break;
            case self::TYPE_SUBCONTRATA:
                return "solicitudsubcontrata.php?poid={$item->getUID()}&request={$this->getUID()}";
            break;
            case self::TYPE_ELIMINARCLIENTE: case self::TYPE_ELIMINARCONTRATA:
                return "confirmareliminarrelacion.php?request={$this->getUID()}";
            break;
        }
    }

    public function getText(){
        $tpl = Plantilla::singleton();
        $item = $this->getItem();
        $empresaSolicitante = $this->getSolicitante();
        $time = $this->getTimestamp();
        $url = $this->getURL();

        if (!$empresaSolicitante instanceof empresa) return false;

        switch( $type = $this->getTypeOf() ){
            case self::TYPE_ASIGNAR:
                //Como hay solicitudes anteriores que no tinen empresa solicitante es necesario ajustar el mensaje que se muestra al usuario
                if ( $empresaSolicitante->exists() ) {
                    $texto = sprintf($tpl->getString('solicitud_ajuste_asignaciones'),$empresaSolicitante->getUserVisibleName(),$item->getUserVisibleName());
                } else $texto = sprintf($tpl->getString('solicitud_ajuste_asignaciones_impersonal'),$item->getUserVisibleName());
                return sprintf($tpl->getString('enlace_href_clase_texto'), $url, '', $texto).' '.sprintf($tpl->getString('solicitud_span_fecha'),date('Y/m/d h:i',$time));;
            break;
            case self::TYPE_TRANSFERENCIA:
                $texto = sprintf($tpl->getString('aviso_solicitud_transferencia_empleado'),$item->getUserVisibleName());
                return sprintf($tpl->getString('enlace_href_clase_texto'),$url,'box-it',$texto);
            break;
            case self::TYPE_CONTRATACION:
                $texto = sprintf($tpl->getString('aviso_solicitud_contratacion'),$item->getUserVisibleName());
                return sprintf($tpl->getString('enlace_href_clase_texto'),$url,'box-it',$texto);
            break;
            case self::TYPE_SUBCONTRATA:
                $texto = sprintf($tpl->getString('aviso_nueva_subcontrata'),$item->getUserVisibleName());
                return sprintf($tpl->getString('enlace_href_clase_texto'),$url,'box-it',$texto).' '.sprintf($tpl->getString('solicitud_span_fecha'),date('Y/m/d h:i',$time));
            break;
            case self::TYPE_ELIMINARCONTRATA:
                $texto = sprintf($tpl->getString('aviso_eliminar_contrata'),$item->getUserVisibleName(),$empresaSolicitante->getUserVisibleName());
                return sprintf($tpl->getString('enlace_href_clase_texto'),$url,'box-it',$texto).' '.sprintf($tpl->getString('solicitud_span_fecha'),date('Y/m/d h:i',$time));
            break;
            case self::TYPE_ELIMINARCLIENTE:
                $texto = sprintf($tpl->getString('aviso_eliminar_cliente'),$item->getUserVisibleName(),$empresaSolicitante->getUserVisibleName());
                return sprintf($tpl->getString('enlace_href_clase_texto'),$url,'box-it',$texto).' '.sprintf($tpl->getString('solicitud_span_fecha'),date('Y/m/d h:i',$time));
            break;
        }
    }

    public function getCompany(){
        $uid = $this->obtenerDato("uid_empresa");
        if( is_numeric($uid) ) return new empresa($uid);
        return false;
    }

    public function getUserVisibleName(){
        $tpl = Plantilla::singleton();
        switch ($this->getTypeOf()) {
            case self::TYPE_TRANSFERENCIA:
                return $tpl->getString('solicitud_transferencia_empleado');
            break;
        }
    }

    public function canResend() {
        $time = time();
        $reqtime = strtotime($this->obtenerDato('fecha'));
        $diff = $time - $reqtime;
        if ($diff > (60*60*self::RESEND_PERIOD)) {
            return true;
        }
        return false;
    }

    /**
      * RETORNA
      * @param $type Set the request Type
      * @param $state Set the request State
      * @param $numApplicant , array con primer indice operador, y segundo valor referente al numero de empresas que tengan este tipo de solicitud
      */
    public static function getRequestByApplicant($type, $excludeState = NULL, $numApplicant = array ()) {
        if (!isset($numApplicant) || !isset($type)) return false;
        $db = db::singleton();
        $filtro = array();
        $operator = $numApplicant[0];
        $value = $numApplicant[1];
        $uniqId = uniqid();
        // creamos tabla para poder contrastar que la solictud pertence al grupo de empresa solicitante y elemento
        $sql = "CREATE TABLE agd_tmp.solicitudes_tmp_".$uniqId." AS SELECT uid_elemento,uid_empresa_origen
                                FROM ". TABLE_EMPRESA ."_solicitud
                                WHERE type =  '{$type}'  ";
        if (isset($excludeState)) {
            $filtro[] .=  " estado != {$excludeState} ";
        }
        if (count($filtro)) {
            $sql .= " AND ".implode('AND',$filtro);
        }
        $sql .=  " GROUP BY uid_elemento,uid_empresa_origen HAVING COUNT(*) {$operator} {$value}";
        $db->query($sql);
        $sql = " SELECT uid_empresa_solicitud
                    FROM ". TABLE_EMPRESA ."_solicitud INNER JOIN agd_tmp.solicitudes_tmp_".$uniqId."  USING(uid_elemento, uid_empresa_origen)
                    WHERE type =  '{$type}' ";
        if (count($filtro)) {
            $sql .= " AND ".implode('AND',$filtro);
        }
        $solicitudes = new ArrayRequestList($db->query($sql, "*", 0, "empresasolicitud"));
        $sql = "DROP TABLE agd_tmp.solicitudes_tmp_".$uniqId;
        $db->query($sql);
        return $solicitudes;
    }

    public static function defaultData($data, Iusuario $usuario = null) {
        if (!isset($data['estado'])) {
            $data['estado'] = self::ESTADO_CREADA;
        }
        // $data["type"] = self::TYPE_ASIGNAR;
        return $data;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $fields = new FieldList;
        $fields["uid_empresa"] = new FormField();
        $fields['uid_empresa_origen'] = new FormField();
        $fields["uid_elemento"] = new FormField();
        $fields["uid_modulo"] = new FormField();
        $fields["uid_usuario"] = new FormField();
        $fields["data"] = new FormField();
        $fields["estado"] = new FormField();
        $fields["type"] = new FormField();
        $fields['message'] = new FormField();
        return $fields;
    }

    public function sendEmailDeniedDeleteRelationship(usuario $usuario) {
        $tpl = "solicitud/empresa/eliminar-relacion/denied";
        $params = array(
                    "request" => $this
                );
        $infolog = "email: Rechazada la solicitud de {$this->getSolicitante()} para que {$this->getCompany()} la elimine. {$this->getTypeOf()}";
        $log = array('empresasolicitud',$infolog,$usuario->getUserVisibleName());
        $plantillaemail = plantillaemail::instanciar("subcontratacion");
        return $this->getSolicitante()->sendEmailWithParams('denied_delete_relationship',$tpl,$params,$log,$plantillaemail);
    }

    public function sendEmailAcceptedDeleteRelationship(usuario $usuario, $message = NULL) {
        $tpl = "solicitud/empresa/eliminar-relacion/accepted";
        $params = array(
                    "request" => $this
                );
        if (!empty($message)) { $params['message'] = $message; }
        $infolog = "email: Aceptada la solicitud de {$this->getSolicitante()} para que {$this->getCompany()} la elimine. {$this->getTypeOf()}";
        $log = array('empresasolicitud',$infolog,$usuario->getUserVisibleName());
        $plantillaemail = plantillaemail::instanciar("subcontratacion");
        return $this->getSolicitante()->sendEmailWithParams('accepted_delete_relationship',$tpl,$params,$log,$plantillaemail);
    }

    public function sendTransferEmployeeRequest() {
        $tpl = "solicitud/empresa/transferencia/request";
        $params = array(
                    "request" => $this
                );
        $infolog = "email: La empresa {$this->getSolicitante()->getUserVisibleName()} solicita la transferencia del empleado {$this->getItem()->getUID()}";
        $log = array('empresasolicitud',$infolog,$this->getUID());
        return $this->getCompany()->sendEmailWithParams('request_transfer_employee',$tpl,$params,$log);
    }

    public function sendAlertExpiredTransferEmployee() {
        $tpl = "solicitud/empresa/transferencia/expired-alert";
        $params = array(
                    "request" => $this
                );
        $infolog = "email: expira dia la solicitud transferencia empleado ( {$this->getItem()->getUID()} ) a empresa {$this->getSolicitante()->getUserVisibleName()}";
        $log = array('empresasolicitud',$infolog,$this->getUID());
        return $this->getCompany()->sendEmailWithParams('expired_alert_transfer_employee',$tpl,$params,$log);
    }

    public static function sendDeniedTransferEmployee(empresa $applicantCompany,empleado $empleado, $messages, usuario $user) {
        $tpl = "solicitud/empresa/transferencia/denied";
        $params = array(
                    "applicantCompany" => $applicantCompany,
                    "employee" => $empleado,
                    "messages" => $messages
                );
        $infolog = "email: Rechazada la solicitud transferencia empleado ( {$empleado->getUID()} ) a empresa {$applicantCompany->getUserVisibleName()}";
        $log = array('empresasolicitud',$infolog,'cronCall');
        $destinatarios = array();
        if ($emailUser = $user->getEmail()) {
            $destinatarios[] = $emailUser;
        }
        $destinatarios[] = $applicantCompany->obtenerContactoPrincipal()->obtenerEmail();
        return $applicantCompany->sendEmailWithParams('denied_transfer_employee', $tpl, $params, $log, null, $destinatarios);
    }

    public static function sendPendingNotifications() {
        $arrayByElement = ArrayRequestGroupedList::getPendingsEmpresaSolicitud(solicitud::TYPE_TRANSFERENCIA);
        $result = true;
        foreach ($arrayByElement as $requestByElement) {
            $result = $requestByElement->sendNotificationsEmpresaSolicitud() && $result;
        }
        return $result;
    }

    static public function cronCall($time, $force = false, $items = NULL){
        $m = date("i", $time);
        $h = date("H", $time);
        $w = date("w", $time);


        $isTime = ($h == 07 && $m == 15);
        if ($isTime || $force) {
            $update = self::sendPendingNotifications();
        }

        return true;
    }

    public function getTableFields(){
        return array(
            array("Field" => "uid_empresa_solicitud",       "Type" => "int(11)",        "Null" => "NO",     "Key" => "PRI",     "Default" => "",                    "Extra" => "auto_increment"),
            array("Field" => "uid_usuario",                 "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_empresa",                 "Type" => "int(11)",        "Null" => "NO",     "Key" => "MUL",     "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_elemento",                "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_modulo",                  "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "estado",                      "Type" => "int(1)",         "Null" => "NO",     "Key" => "MUL",     "Default" => "0",                   "Extra" => ""),
            array("Field" => "type",                        "Type" => "varchar(50)",    "Null" => "NO",     "Key" => "MUL",     "Default" => "",                    "Extra" => ""),
            array("Field" => "data",                        "Type" => "text",           "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => ""),
            array("Field" => "fecha",                       "Type" => "timestamp",      "Null" => "YES",    "Key" => "",        "Default" => "CURRENT_TIMESTAMP",   "Extra" => ""),
            array("Field" => "uid_empresa_origen",          "Type" => "int(11)",        "Null" => "YES",    "Key" => "MUL",     "Default" => "",                    "Extra" => ""),
            array("Field" => "message",                     "Type" => "varchar(400)",   "Null" => "YES",    "Key" => "",        "Default" => "",                    "Extra" => "")

        );
    }
}