<?php

class logui extends elemento
{
    const ACTION_ADD_ORGANIZATION = "add_organization";
    const ACTION_ASSIGN_AGR = "assign_agr";
    const ACTION_ASSIGN_MACHINES = "assign_machines";
    const ACTION_AVISOEMAIL = "aviso_email";
    const ACTION_AVISOEMAIL_LICENCIA = "aviso_email_licencia";
    const ACTION_AVISOEMAIL_VALIDACION = "aviso_email_validacion";
    const ACTION_CREATE = "crear_elemento";
    const ACTION_DESTROY = "destroy";
    const ACTION_DISABLE = "disable";
    const ACTION_DISCONNECT = "disconnect";
    const ACTION_DOWNLOAD = "descargar_fichero";
    const ACTION_EDIT = "editar_elemento";
    const ACTION_ENABLE = "enable";
    const ACTION_FICHA = "acceso_ficha_qr";
    const ACTION_FORBIDDEN = "forbidden";
    const ACTION_NEW_ROL = "set_new_rol";
    const ACTION_PLACE_ACCESS = "place_access";
    const ACTION_PLACE_LEAVE = "place_leave";
    const ACTION_REGENERATE_KEY = "regenerar_clave";
    const ACTION_REVISAR = "revisar_anexo";
    const ACTION_SET_ACCESS_ACTIONS = "set_access_actions";
    const ACTION_SET_VISIBILITY = "set_visibility";
    const ACTION_SHARE = "share";
    const ACTION_STATUS_CHANGE = "cambiar_estado";
    const ACTION_TRANSFER = "transfer";
    const ACTION_UNASSIGN_AGR = "unassign_agr";
    const ACTION_UNASSIGN_MACHINES = "unassign_machines";
    const ACTION_UPDATE_IMAGE = "update_image";
    const ACTION_UPLOAD = "cargar_fichero";
    const ACTION_URGENT = "urgent";
    const ACTION_ZIP_DOCS = "zip_docs";
    const STRING_ACTION_ADD = "add";
    const STRING_ACTION_NEW = "new";
    const STRING_ACTION_OLD = "old";
    const STRING_ACTION_REMOVE = "remove";
    const STRING_ORIGIN_COMPANY = "uid_empresa_origen";
    const STRING_TARGET_COMPANY = "uid_empresa_destino";
    const ACTION_SET_SUITABLE = "set_suitable";
    const ACTION_UNSET_SUITABLE = "unset_suitable";

    public function __construct( $param, $extra = NULL ){
        $this->tipo = "logui";
        $this->tabla = TABLE_LOGUI;

        $this->instance( $param, $extra );
    }

    public function getModifiedString($timezoneOffset = null, $user = null)
    {
        $isStaff = $user ? $user->esStaff() : false;
        $valor = $this->obtenerDato("valor");
        $lang = Plantilla::singleton();
        $item = $this->getElement();
        $module = $item->getModuleName();
        $fields = $module::publicFields(elemento::PUBLIFIELDS_MODE_EDIT, $item);
        $text = $this->obtenerDato("texto");
        $strings = array();
        $updates = explode(",", $valor);

        foreach ($updates as $update) {
            @list($field, $data) = explode(" = ", $update);
            $data = str_replace("'", "", $data);
            $module = str_replace('uid_', '', $field); //removing uid drom uid_module
            // The updated fields that don't represents classes
            $notClassList = ['email'];

            if (in_array($module, $notClassList) === false && class_exists($module)) {
                if (empty($data) === false) {
                    $instance = new $module($data);
                    if ($instance->getUserVisibleName() === "invoice") {
                        //Case only for inovices, we display amount and date.
                        $tstamp = $instance->getInvoiceTimestamp();
                        $tstamp    -= (3600 * $timezoneOffset);
                        $strings[] = $lang("importe").": ".$instance->getTotalAmount().", ".$lang("fecha").": ".date('d/m/Y H:i:s', $tstamp);
                    } else {
                        //Case we have a list of elements like: <uid_module = uid>
                        $strings[] = $lang($module) ." = " . $instance->getUserVisibleName();
                    }
                } else {
                    $strings[] = $lang($module) ." = ''";
                }
            } else if (isset($fields[$field]) && $public = $fields[$field]) {
                //Case edit element
                if (isset($public["data"]) && $public["data"]) {
                    $data = isset($public["data"][$data]) ? $public["data"][$data] : "N/A";
                }

                $default = $data;

                // Prevenir campos fecha
                $data = str_replace(" 00:00:00", "", $data);
                if ($default != $data) {
                    $data = date("d/m/Y", strtotime($data));
                }

                // Si es un timestamp
                if (is_numeric($data) && strlen($data) == 10) {
                    $data = date("d/m/Y", $data);
                }

                // Prevenir saltos de linea
                $data = str_replace(array('\n', '\r'), array("", ""), $data);


                if ($className = elemento::getColMap($field)) {
                    if (is_numeric($data) && $data) {
                        $instance = new $className($data);
                        $data = $instance->getUserVisibleName();
                    } else {
                        $data = "sin_$className";
                    }
                }

                // Guardar partes
                $strings[] = $lang($field) ." = " . $lang($data);
            } else if (trim($field) == logui::STRING_ACTION_ADD && is_numeric($data) && $data != 0) {
                //Case we are adding elements to a item
                $strings[] = sprintf($lang('add_elements'), $data);
            } else if (trim($field) == logui::STRING_ACTION_REMOVE && is_numeric($data) && $data != 0) {
                //Case we are removing elements to a item
                $strings[] = sprintf($lang('remove_elements'), $data);
            } else if (($field == logui::STRING_ACTION_NEW || $field == logui::STRING_ACTION_OLD) && $this->obtenerDato("texto") == self::ACTION_NEW_ROL) {
                //case rol changed
                $rol = new rol($data);
                if ($rol instanceof rol) {
                    if ($field == logui::STRING_ACTION_NEW) {
                        $strings[] = $lang('new_rol') ." <strong>". $rol->getUserVisibleName()."</strong>";
                    } else {
                        $strings[] = $lang('old_rol') ." <strong>". $rol->getUserVisibleName()."</strong>";
                    }
                }
            } else if ($text == logui::ACTION_PLACE_ACCESS) {
                // show a company name
                if (is_numeric($update) && $update = (int) $update) {
                    $company = new empresa($update);
                    $strings[] = $company->getUserVisibleName();
                }
            } else if ($isStaff && in_array($text, [logui::ACTION_SHARE, logui::ACTION_TRANSFER])) {
                if ($field == logui::STRING_ORIGIN_COMPANY) {
                    $company = new empresa($data);
                    $strings[] = $lang('origin_company') ." <strong>". $company->getUserVisibleName()."</strong>";
                } elseif ($field == logui::STRING_TARGET_COMPANY) {
                    $company = new empresa($data);
                    $strings[] = $lang('target_company') ." <strong>". $company->getUserVisibleName()."</strong>";
                }
            } else {
                $strings[] = $update;
            }
        }

        return implode(", ", $strings);
    }

    public function getText(){
        $tpl = Plantilla::singleton();
        if (($texto = $this->obtenerDato("texto")) == 'cambiar_estado') {
            $texto = $tpl->getString($this->obtenerDato("texto")).' '.documento::status2string($this->obtenerDato('valor'));
        } else {
            $texto = $tpl->getString($texto);
        }
        return $texto;
    }

    public function getUser(){
        return new usuario( $this->obtenerDato("uid_usuario"));
    }

    public function getEmployee() {
        try {
            return new empleado($this->obtenerDato("uid_usuario"));
        } catch (exception $e){
            return false;
        }
    }

    public function getTimestamp ($offset = 0) {
        $SQL = "SELECT UNIX_TIMESTAMP(fecha) FROM {$this->tabla} WHERE uid_logui = {$this->getUID()}";

        if ($timestamp = $this->db->query($SQL, 0, 0)) {
            // adjuts timezone offset
            $timestamp = $timestamp - (3600 * $offset);

            return (int) $timestamp;
        }

        return 0;
    }


    public function getDate($offset = 0){
        if ($timestamp = $this->getTimestamp($offset)) {
            return date("d/m/Y H:i:s", $timestamp);
        }
    }

    public function getElement(){
        $modulo = util::getModuleName($this->obtenerDato("uid_modulo"));
        return new $modulo($this->obtenerDato("uid_elemento"));
    }

    public function getClass(){
        $text = $this->obtenerDato("texto");
        $aux = explode(" ", $text);
        if( count($aux) == 1 ){
            return reset($aux);
        }
        return "";
    }

    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $data = array()){


        $data = array();
        $tpl = Plantilla::singleton();

        $user = $this->getUser();
        $timezone = $usuario->getTimezoneOffset();

        if ($user->exists()) {

            $data["usuario"] = array(
                    "class" => "box-it",
                    "href" => $user->obtenerUrlFicha(),
                    "title" => $user->getUserName(),
                    "innerHTML" =>  $tpl("usuario") ." ". $user->getUserName()
                );

        } else {
            $employee = $this->getEmployee();
            if ($employee->exists()) {
                $data["usuario"] = array(
                    "class" => "box-it",
                    "href" => $employee->obtenerUrlFicha(),
                    "title" => $employee->getUserName(),
                    "innerHTML" => $tpl("empleado") ." ". $employee->getUserName()
                );

            } else {

                $data["usuario"] = array(
                    "title" => $tpl("usuario")." dokify",
                    "innerHTML" => $tpl("usuario")." dokify"
                );

            }
        }

        $data["action"] = array(
            "innerHTML" => $tpl("action_performed")." <strong>{$this->getText()}</strong>. {$tpl('fecha')} <strong>{$this->getDate($timezone)}</strong>"
        );


        if ($modified = $this->getModifiedString($timezone, $usuario)) {
            $data["action"]["innerHTML"] .= " | ".string_truncate($modified, 100);
            $data["action"]["title"] = $modified;
        }

        $tableInfo = array( $this->uid => $data );
        return $tableInfo;
    }

    public static function move(elemento $from, elemento $to) {
        $SQL = "UPDATE ". TABLE_LOGUI ."
            SET uid_elemento = {$to->getUID()}, uid_modulo = {$to->getModuleId()}
            WHERE uid_elemento = {$from->getUID()} AND uid_modulo = {$from->getModuleId()}
        ";

        return db::get($SQL);
    }

    public static function getUserFromElementAndValue(elemento $element, $value) {
        $sql = "SELECT uid_usuario FROM ". TABLE_LOGUI ."
            WHERE uid_elemento = {$element->getUID()}
            AND uid_modulo = {$element->getModuleId()}
            AND valor LIKE '{$value}'
            ORDER BY uid_logui
            LIMIT 1
        ";

        $users = db::query($sql, "*", 0, 'usuario');

        if (count($users) === 1) {
            return $users[0];
        }

        return null;
    }


    static public function publicFields($modo, $objeto, $usuario)
    {
        $arrayCampos = new FieldList();

        switch ($modo) {
            default:
                $arrayCampos["uid_elemento"] = new FormField();
                $arrayCampos["uid_modulo"]  = new FormField();
                $arrayCampos["uid_usuario"] = new FormField();
                $arrayCampos["uid_empresa"] = new FormField();
                $arrayCampos["user_type"]   = new FormField();
                $arrayCampos["texto"]       = new FormField();
                $arrayCampos["valor"]       = new FormField();
                $arrayCampos["uid_perfil"]  = new FormField();
                break;
        }

        return $arrayCampos;
    }

}
