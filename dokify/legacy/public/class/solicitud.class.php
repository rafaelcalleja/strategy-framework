<?php

use Dokify\Application\Event\Assignment\Suggest as AssignmentSuggestEvent;
use Dokify\Events\Assignment\SuggestEvents as SuggestEvents;

class solicitud extends elemento
{
    const ESTADO_CREADA = 0;
    const ESTADO_ACEPTADA = 1;
    const ESTADO_RECHAZADA = 2;
    const ESTADO_CANCELADA = 3;
    const ESTADO_PROCESSED = 4;
    const ESTADO_SHARED = 5;
    const ESTADO_EXPIRED = 6;

    const TYPE_ASIGNAR = "asignar";
    const TYPE_UPLOAD = "upload";
    const TYPE_TRANSFERENCIA = "transferencia";
    const TYPE_CONTRATACION = "contratacion";
    const TYPE_SUBCONTRATA = "subcontrata";
    const TYPE_ELIMINARCONTRATA = "eliminarcontrata";
    const TYPE_ELIMINARCLIENTE = "eliminarcliente";

    public static function getAllTypes()
    {
        return [
            self::TYPE_ASIGNAR,
            self::TYPE_UPLOAD,
            self::TYPE_TRANSFERENCIA,
            self::TYPE_CONTRATACION,
            self::TYPE_SUBCONTRATA,
            self::TYPE_ELIMINARCONTRATA,
            self::TYPE_ELIMINARCLIENTE
        ];
    }

    public function getJsonData()
    {
        $data = array();

        $data["uid"] = $this->getUID();
        $data["innerHTML"] = $this->getText();
        $data["title"] = $this->getTitle();
        $data["estado"] = $this->getState();
        $data["className"] = $this->getClassName();
        $data['tipo'] = $this->getTypeOf();
        return $data;
    }

    public function getClassName()
    {
        switch ($type = $this->getTypeOf()) {
            case self::TYPE_UPLOAD:
                return "confirm ".self::TYPE_UPLOAD;
            break;
            default:
                return $type;
            break;
        }
    }

    public static function getTableFromModule($module)
    {
        switch ($module) {
            case 'empresa':
                return TABLE_EMPRESA ."_solicitud";
            break;
            case 'empleado':
                return TABLE_EMPLEADO ."_solicitud";
            break;
            default:
                return false;
            break;
        }
    }

    public static function getFromItem($module, elemento $elemento, elemento $parent = null, $filter = null, $count = false)
    {
        $db = db::singleton();
        $m = $elemento->getModuleId();
        $uid = $elemento->getUID();
        $table = solicitud::getTableFromModule($module);
        $field = $count ? "count(uid_{$module}_solicitud)" : "uid_{$module}_solicitud";

        $sql = "SELECT {$field} FROM {$table} WHERE uid_elemento = {$uid} AND uid_modulo = {$m}";

        if ($parent) {
            $sql .= " AND uid_{$module} = {$parent->getUID()} ";
        }

        if (is_traversable($filter)) {
            foreach ($filter as $field => $value) {
                if (!empty($field)) {
                    $sql .= " AND {$field} = '{$value}'";
                }
            }
        }

        if ($count) {
            return $db->query($sql, 0, 0);
        }

        $requests = $db->query($sql, '*', 0, $module."solicitud");

        if ($requests) {
            return new ArrayObjectList($requests);
        }

        return new ArrayObjectList;
    }

    public function getText()
    {
        $tpl = Plantilla::singleton();

        switch ($this->getTypeOf()) {
            case solicitud::TYPE_UPLOAD:
                return "El usuario <a href='{$this->getUser()->obtenerUrlPreferida()}'>{$this->getUser()->getUserName()}</a> quiere subir ". archivo::formatBytes($this->getValue());
            break;
        }
    }

    public static function getRequests($module, $filter = null)
    {
        $db = db::singleton();
        $table = solicitud::getTableFromModule($module);
        $field = "uid_".$module."_solicitud";
        $sql = " SELECT {$field}
            FROM {$table}
            WHERE 1 ";
        if (is_traversable($filter)) {
            foreach ($filter as $field => $value) {
                if (!empty($field)) {
                    $sql .= " AND {$field} = '{$value}'";
                }
            }
        }
        return new ArrayObjectList($db->query($sql, '*', 0, $module."solicitud"));
    }

    public function setState($estado)
    {
        $primary = db::getPrimaryKey($this->tabla);
        $sql = "UPDATE $this->tabla SET estado = $estado WHERE $primary = $this->uid";
        if ($this->db->query($sql)) {
            $this->clearItemCache();
            return true;
        }
        return false;
    }

    public function getTitle()
    {
        $primary = db::getPrimaryKey($this->tabla);
        $sql = "SELECT descripcion FROM ". DB_CORE . ".solicitud
                    INNER JOIN $this->tabla USING( uid_solicitud )
                    WHERE $primary = $this->uid";
        $string = $this->db->query($sql, 0, 0);
        return $string;
    }

    public function getState()
    {
        return (int) $this->obtenerDato("estado");
    }

    public function getUser()
    {
        $uid = $this->obtenerDato("uid_usuario");
        if (is_numeric($uid)) {
            return new usuario($uid);
        }

        return false;
    }

    public function getSolicitante()
    {
        $uid = $this->obtenerDato('uid_empresa_origen');
        if (is_numeric($uid)) {
            return new empresa($uid);
        }

        return false;
    }

    public function getItem()
    {
        $info = $this->getInfo();
        $modulo = util::getModuleName($info["uid_modulo"]);
        $uid = $info["uid_elemento"];
        return new $modulo($uid);
    }

    public function isAccepted()
    {
        return $this->getState() == solicitud::ESTADO_ACEPTADA;
    }

    public function isShared()
    {
        return $this->getState() == solicitud::ESTADO_SHARED;
    }

    public function isCanceled()
    {
        return $this->getState() == solicitud::ESTADO_CANCELADA;
    }

    public function isExpired()
    {
        return $this->getState() == solicitud::ESTADO_EXPIRED;
    }

    public function isCreatedStatus()
    {
        return $this->getState() == solicitud::ESTADO_CREADA;
    }

    public function isRefused()
    {
        return $this->getState() == solicitud::ESTADO_RECHAZADA;
    }

    public function isProcessed()
    {
        return $this->getState() == solicitud::ESTADO_PROCESSED;
    }

    public function getTypeOf()
    {
        return $this->obtenerDato('type');
    }

    public function getValue()
    {
        $data = $this->obtenerDato("data");
        return json_decode($data);
    }

    public function getTimestamp()
    {
        return strtotime($this->obtenerDato("fecha"));
    }

    public function daysSinceCreated()
    {
        $date = $this->getTimestamp();
        $diffDate = time() - $date;
        return (int)($diffDate / (60 * 60 * 24));
    }

    public function setMessage($message)
    {
        $primary = db::getPrimaryKey($this->tabla);
        $message = db::scape(utf8_decode($message));
        $sql = "UPDATE {$this->tabla} SET message = '{$message}' WHERE {$primary} = {$this->getUID()}";
        if ($this->db->query($sql)) {
            $this->clearItemCache();
            return true;
        }
        return false;
    }

    public function getMessage()
    {
        return $this->obtenerDato("message", true);
    }

    /***
       * @param $uid = false (just form compatibility)
       *
       *
       */
    public function getModuleName($uid = false)
    {
        $aux    = explode(".", $this->tabla);
        $parts  = explode("_", $aux[1]);
        return reset($parts);
    }

    public function daysToExpired()
    {
        switch ($this->getTypeOf()) {
            case solicitud::TYPE_TRANSFERENCIA:
                //Contamos con que las notificaciones caducan a los 5 dias
                return (int) (4 - $this->daysSinceCreated());
                break;

            default:
                return false;
                break;
        }
        return false;
    }

    public function aceptar($comentario = null, Iusuario $usuario = null)
    {
        if ($this->getState() != self::ESTADO_CREADA) {
            return 'solicitud_no_valida';
        }

        $this->setState(self::ESTADO_ACEPTADA);
        $item = $this->getItem();

        if (!empty($comentario)) {
            $this->setMessage($comentario);
        }

        if ($status = $item->onRequestResponse($this, $usuario)) {
            switch ($this->getTypeOf()) {
                case solicitud::TYPE_TRANSFERENCIA:
                    $this->sendAcceptedTransferEmployee();
                    break;
                case solicitud::TYPE_ASIGNAR:
                    $event = new AssignmentSuggestEvent\Accepted($this->asDomainEntity());

                    $this->dispatcher->dispatch(
                        SuggestEvents::ASSIGNMENT_SUGGEST_ACCEPTED,
                        $event
                    );
                    break;
                default:
                    break;
            }
        }
        return $status;
    }

    public function rechazar($comentario = null, Iusuario $usuario = null)
    {
        if ($this->getState() != self::ESTADO_CREADA) {
            return 'solicitud_no_valida';
        }
        $this->setState(self::ESTADO_RECHAZADA);
        $item = $this->getItem();
        if (!empty($comentario)) {
            $this->setMessage($comentario);
        }

        if ($status = $item->onRequestResponse($this, $usuario)) {
            switch ($this->getTypeOf()) {
                case solicitud::TYPE_TRANSFERENCIA:
                    // see empresasolicitud::cronCall
                    break;
                case solicitud::TYPE_ASIGNAR:
                    $event = new AssignmentSuggestEvent\Rejected($this->asDomainEntity());

                    $this->dispatcher->dispatch(
                        SuggestEvents::ASSIGNMENT_SUGGEST_REJECTED,
                        $event
                    );
                    break;
                default:
                    break;
            }
        }
        return $status;
    }

    public function cancelar($comentario = null, Iusuario $usuario = null)
    {
        if ($this->getState() != self::ESTADO_CREADA) {
            return 'solicitud_no_valida';
        }
        $item = $this->getItem();
        if (!empty($comentario)) {
            $this->setMessage($comentario);
        }
        switch ($this->getTypeOf()) {
            case solicitud::TYPE_TRANSFERENCIA:
                $status = $item->onRequestResponse($this, $usuario);
                $this->sendAcceptedTransferEmployee();
                $this->setState(self::ESTADO_ACEPTADA);
                break;
            case solicitud::TYPE_ASIGNAR:
                $status = $this->setState(self::ESTADO_CANCELADA);
                $event = new AssignmentSuggestEvent\Canceled($this->asDomainEntity());

                $this->dispatcher->dispatch(
                    SuggestEvents::ASSIGNMENT_SUGGEST_CANCELED,
                    $event
                );
                break;
            default:
                # code...
                break;
        }
        return $status;
    }

    public function share($comentario = null, $usuario = null)
    {
        if ($this->getState() != self::ESTADO_CREADA) {
            return 'solicitud_no_valida';
        }
        $this->setState(self::ESTADO_SHARED);
        $item = $this->getItem();
        if (!empty($comentario)) {
            $this->setMessage($comentario);
        }
        if ($status = $item->onRequestResponse($this, $usuario)) {
            switch ($this->getTypeOf()) {
                case solicitud::TYPE_TRANSFERENCIA:
                    $this->sendAcceptedTransferEmployee();
                    break;
                case solicitud::TYPE_ASIGNAR:
                    break;

                default:
                    # code...
                    break;
            }
        }
        return $status;
    }

    public function sendAcceptedTransferEmployee()
    {
        $tpl = "solicitud/transferencia/accepted";
        $params = array(
                    "request" => $this
                );
        $destinatarios = array();
        if ($emailUser = $this->getUser()->getEmail()) {
            $destinatarios[] = $emailUser;
        }

        $solicitante = $this->getSolicitante();
        if ($main = $solicitante->obtenerContactoPrincipal()) {
            $destinatarios[] = $main->obtenerEmail();
        }

        $infolog = "email: La solicitud de transferencia de empleado ( {$this->getItem()->getUID()} ) a empresa {$this->getSolicitante()->getUserVisibleName()} ha sido aceptada";
        $log = array('empresasolicitud',$infolog,$this->getUID());
        return $this->getSolicitante()->sendEmailWithParams('accepted_transfer_employee', $tpl, $params, $log, null, $destinatarios);
    }

    public static function sendExpiredTransferEmployee(empresa $applicantCompany, empleado $empleado, usuario $user)
    {
        $tpl = "solicitud/transferencia/expired";
        $params = array(
                    "applicantCompany" => $applicantCompany,
                    "employee" => $empleado
                );
        $infolog = "email: Expirada la solicitud transferencia empleado ( {$empleado->getUID()} ) a empresa {$applicantCompany->getUserVisibleName()}";
        $log = array('empresasolicitud',$infolog,'cronCall');
        $destinatarios = array();
        if ($emailUser = $user->getEmail()) {
            $destinatarios[] = $emailUser;
        }
        $destinatarios[] = $applicantCompany->obtenerContactoPrincipal()->obtenerEmail();
        return $applicantCompany->sendEmailWithParams('expired_transfer_employee', $tpl, $params, $log, null, $destinatarios);
    }
}
