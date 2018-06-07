<?php

class contactoempresa extends categorizable implements Ielemento
{
    const SENDY_ACTIVE_COMPANIES_LIST = 3;
    const SENDY_ENTERPRISE_COMPANIES = 4;

    public function __construct($param, $extra = false)
    {
        $this->tipo = "contactoempresa";
        $this->tabla = TABLE_CONTACTOEMPRESA;
        $this->instance( $param, $extra );
    }

    /**
     * A temporary method to convert a legacy class into a entity class
     * @return Company\Contact\Contact
     */
    public function asDomainEntity()
    {
        $entity = new \Dokify\Domain\Company\Contact\Contact(
            \Dokify\Domain\Company\Contact\Contact::makeUid($this->getUID()),
            \Dokify\Domain\Company\Company::makeUid($this->obtenerDato('empresa'))
        );

        return $entity;
    }

    public static function getRouteName()
    {
        return 'contact';
    }

    public static function defaultData($data, Iusuario $usuario = null)
    {
        if ($usuario instanceof Iusuario) {
            $userComapny            = $usuario->getCompany();
            $data['idioma']         = $userComapny->getCountry()->getLanguageId();

            if (!isset($data['uid_empresa'])) {
                $data['uid_empresa']    = $userComapny->getUID();
            }
        }

        if (isset($data["email"])) {
            if (StringParser::isEmail($data['email']) === false) {
                throw new Exception(_("The email address is not valid"));
            }
        }

        return $data;
    }

    public function updateData($data, Iusuario $usuario = null, $mode = null)
    {
        if (isset($data["email"])) {
            if (StringParser::isEmail($data['email']) === false) {
                throw new Exception(_("The email address is not valid"));
            }
        }

        return $data;
    }

    public function getLanguage()
    {
        $lang = system::getLanguageFromId($this->obtenerDato('idioma'));

        if ($lang === false) {
            return Plantilla::DEFAULT_LANGUAGE;
        }

        return $lang;
    }

    public function getUserVisibleName()
    {
        $datos = $this->getInfo();
        $name = trim($datos["nombre"]." ".$datos["apellidos"]);
        if( $name ) return $name;

        return $this->obtenerDato("email");
    }

    public function getContactName()
    {
        $datos = $this->getInfo();
        $name = trim($datos["nombre"]);
        if( $name ) return $name;

        return $this->obtenerDato("email");
    }

    public function getContactSurname()
    {
        $datos = $this->getInfo();
        $surname = trim($datos["apellidos"]);
        if( $surname ) return $surname;

        return "";
    }

    public function getPhone()
    {
        $datos = $this->getInfo();
        if(isset($datos["telefono"])) return $datos["telefono"];
        elseif(isset($datos["movil"])) return $datos["movil"];
        return false;
    }

    public function getMainPhone()
    {
        $phone = $this->obtenerDato("telefono");
        if ($phone) return $phone;
        return false;
    }

    public function getCell()
    {
        $cell = $this->obtenerDato("movil");
        if ($cell) return $cell;
        return false;
    }

    public function obtenerDato($dato, $force = false)
    {
        $datos = $this->getInfo();
        if ( isset($datos[$dato]) ) {
            return $datos[$dato];
        }

        return false;
    }

    public function getCompany()
    {
        $datos = $this->getInfo();
        if ( isset($datos["uid_empresa"]) && is_numeric($datos["uid_empresa"]) ) {
            return new empresa($datos["uid_empresa"], false);
        }

        return false;
    }

    public function obtenerEmail()
    {
        return $this->obtenerDato("email");
    }

    public function hacerPrincipal()
    {
        $empresaContacto = $this->getCompany();
        if (!$empresaContacto instanceof empresa) { return false; }

        $idEmpresaContacto = $empresaContacto->getUID();
        $sql = "UPDATE $this->tabla SET principal = 0 WHERE uid_empresa = {$idEmpresaContacto} AND uid_empresa_contacto NOT IN ($this->uid)";
        if ( !$this->db->query($sql) ) {
            return $this->db->lastErrorString();
        }

        $sql = "UPDATE $this->tabla SET principal = 1 WHERE uid_empresa_contacto = $this->uid";
        if ( !$this->db->query($sql) ) {
            return $this->db->lastErrorString();
        }

        $this->cache->clear();

        return true;
    }

public function activarRecibirEmail($plantilla)
{
    $sql = "INSERT IGNORE INTO ". TABLE_CONTACTO_PLANTILLAEMAIL ." ( uid_contacto, uid_plantillaemail )
    VALUES (". $this->getUID() .", ". $plantilla->getUID() .")";

    if (!$this->db->query($sql)) {
        return $this->db->lastErrorString();
    }

    return $this->db->getAffectedRows() ? true : null;
}

public function desactivarRecibirEmail($plantilla)
{
    $sql = "DELETE FROM ". TABLE_CONTACTO_PLANTILLAEMAIL ."
    WHERE uid_plantillaemail = ". $plantilla->getUID() ."
    AND uid_contacto = ". $this->getUID();

    if (!$this->db->query($sql)) {
        return $this->db->lastErrorString();
    }

    return $this->db->getAffectedRows() ? true : null;
}

    public function getArrayPlantillas($numeric=false)
    {
        $sql = "SELECT uid_plantillaemail FROM ". TABLE_PLANTILLAEMAIL ." p
                INNER JOIN ". TABLE_CONTACTO_PLANTILLAEMAIL ." cp
                USING ( uid_plantillaemail )
                WHERE uid_contacto = ". $this->getUID() ."
        ";
        $uids = $this->db->query($sql, "*", 0);
        if ($numeric) {
            return $uids;
        } else {
            $plantillas = new ArrayObjectList();
            foreach ($uids as $uid) {
                $plantillas[] = new plantillaemail($uid);
            }

            return $plantillas;
        }
    }

    public function esPrincipal()
    {
        $datos = $this->getInfo(false, null, null);

        return ( $datos["principal"] ) ? true : false;
    }
    /*
    static public function optionsFilter($uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo)
    {
        $m = util::getModuleName($uidmodulo);
        $ob = new $m($uidelemento);
        if ( $ob->esPrincipal() ) {
            return $sql = "AND uid_accion NOT IN ( SELECT uid_accion FROM ". TABLE_ACCIONES ." WHERE alias = 'Hacer Principal' )";
        }
    }
    */

    // candidata a interfaz?
    public function obtenerEmpresasSolicitantes($usuario=false)
    {
        return $this->getCompany()->obtenerEmpresasSolicitantes();
    }

    public function obtenerElementosSuperiores()
    {
        return array($this->getCompany());
    }

    // update agd_core.modulo set asignacion=1 where uid_modulo =22;
    // insert into agd_core.modulo_accion (uid_modulo,uid_accion,href,class) values(22,20, '#asignacion.php?m=contactoempresa','unbox-it');
    // INSERT INTO `plantillaemail` (`nombre`, `descripcion`, `atributos`, `contacto`) VALUES ('documentos', 'Se envia periódicamente a los contactos, con informacion de sus documentos pendientes', 0, 0);

    public static function getAll($filter = false, $order = null)
    {
        $db = db::singleton();
        $activeCompanies = empresa::getActiveCompanies();
        $sql = " SELECT uid_empresa_contacto FROM ".TABLE_CONTACTOEMPRESA. " WHERE uid_empresa IN ({$activeCompanies->toIntList()})";
        if ($filter) $sql .= " AND $filter";
        if ($order) $sql .= " ORDER BY $order";
        $resultado = $db->query($sql, '*', 0, "contactoempresa");

        return new ArrayObjectList($resultado);
    }

    public static function enviarResumenEpis(contactoempresa $contacto, $force = false)
    {
        // instanciamos lo que hay que instanciar
        $languages = system::getLanguages();
        $template = Plantilla::singleton();
        $db = db::singleton();
        $log = log::singleton();
        $log->info($contacto->getModuleName(), "leer/enviar resumen EPIs {$contacto->getCompany()}", $contacto->getUserVisibleName() );
        $empresa = $contacto->getCompany();
        $html = $defaultHTML = false; // Por defecto no enviamos nada

        if ( !$empresa instanceof empresa || !$empresa->exists() ) {
            echo "No se encuentra la empresa para el contacto {$contacto->getUID()} \n";
            $log->resultado( "No se encuentra la empresa para el contacto {$contacto->getUID()}", true);

            return false;
        }

        if ($empresa instanceof empresa) {
            $plantillaemail = plantillaemail::instanciar("resumen_epis");
            if ($plantillaemail) {
                $defaultHTML = trim($plantillaemail->getFileContent($empresa));
            }
            if (!$defaultHTML) {
                if (!$force) {
                    error_log(print_r('No hay html en la plantilla de resumen Epis. Se cancela el proceso.',true));
                    exit;
                }
            }
        }

        if (!$defaultHTML) {
            $defaultHTML = utf8_decode(archivo::leer( DIR_ROOT . "res/template/resumenepis.html" ));
        }

        $resumenEpis = $empresa->resumenEpis();
        if (!$resumenEpis) return false;
        $html = plantillaemail::reemplazar($defaultHTML,array('{%epis%}' => $resumenEpis));

        if ($html === false) {
            echo "No se tratará de enviar ningún email. No hay nada que enviar\n"; ob_flush();flush();
            echo "<hr /></blockquote>";

            return false;
        }

        $destinatario = trim($contacto->obtenerDato('email'));
        $forceWhiteList = array("fgomez@afianza.net", "ldonoso@afianza.net","jandres@afianza.net",  );
        if ( $force && !in_array($destinatario, $forceWhiteList) ) {
            echo "\t\t\tSaltando $destinatario por forzar\n";

            return false;
        }

        $destinatarios = explode(",", $destinatario);
        $email = new email($destinatarios);

        $email->establecerContenido($html);
        $email->establecerAsunto( utf8_decode("Resumen de EPIS para ".  $contacto->getUserVisibleName())  );

        echo "Enviando a ". implode(", ", $email->obtenerDestinatarios()) . "...<br />"; ob_flush();flush();

        if ( ($status = $email->enviar()) === true ) {
            echo "Email enviado a ". implode(", ", $email->obtenerDestinatarios() ) . "\n<br />"; ob_flush();flush();
            if (CURRENT_ENV != "dev") { $log->resultado("Email enviado a ". implode(", ", $email->obtenerDestinatarios()) , true); }

            return true;
        } else {
            echo "Ocurrió un error al enviar el email a ". $contacto->getUserVisibleName() . "\n<br />"; ob_flush();flush();
            if (CURRENT_ENV != "dev") { $log->resultado("Ocurrió un error al enviar el email a ". $contacto->getUserVisibleName(), true); }
        }

        return false;
    }

    public function sendEmailDeletedSubcontractor(empresaContratacion $subcontractorChain, usuario $usuario)
    {
        //$template = Plantilla::singleton();
        set_time_limit(0);
        $template = new Plantilla();
        $infoLog = "email cadena contratacion eliminada ( {$subcontractorChain[0]->getUserVisibleName()} ) a empresa {$this->getUserVisibleName()}";
        $log = log::singleton(); $log->info($this->getModuleName(), $infoLog , $this->getUserVisibleName() );

        $template->assign("empresaFinal", $subcontractorChain->getCompanyTail() );
        $template->assign("empresaUsuario", $usuario->getCompany() );
        $template->assign("empresaContacto", $this->getCompany() );

        $direccion = new ArrayObjectList (array($this->obtenerEmail()));

        if (CURRENT_ENV == 'dev') {
            $direccion = email::$developers;
        }

        if ($direccion) {
            $email = new email($direccion);

            $html = $template->getHTML('email/subcontratacioneliminada.tpl');
            $email->establecerContenido($html);
            $email->establecerAsunto( $template("cadena_subcontrata_eliminada") );

            $estado = $email->enviar();
            if ($estado !== true) {
                $estado = $estado && trim($estado) ? trim($estado) : $template('error_desconocido');
                $log->resultado("error $estado", true);
                throw new Exception($estado);
            }

            $log->resultado("ok ", true);

            return true;
        }

        return false;

    }

    public function addContactToList($list)
    {
        $URI = "http://newsletter.dokify.net/subscribe";

        $company = $this->getCompany();
        $name = $this->getUserVisibleName();
        $email = $this->obtenerEmail();

        $postData = util::doPost($URI, array(
            "name"      => $name,
            "email"     => $email,
            "list"      => $list,
            "boolean"   => "true",
            "company"   => $company->getUserVisibleName()
        ));

        return $postData->content;
    }

    public function removeContactFromList($list)
    {
        $URI = "http://newsletter.dokify.net/unsubscribe";

        $email = $this->obtenerEmail();

        $postData = util::doPost($URI, array(
            "email"     => $email,
            "list"      => $list,
            "boolean"   => "true",
        ));

        return $postData->content;
    }

    public function isActive()
    {
        $SQL = "
            SELECT count(uid_empresa_contacto) FROM ". TABLE_CONTACTOEMPRESA ."
            WHERE uid_empresa_contacto = {$this->getUID()}
            AND uid_empresa IN (
                SELECT uid_empresa FROM ". TABLE_USUARIO ."
                INNER JOIN ". TABLE_PERFIL ." USING(uid_usuario)
                WHERE FROM_UNIXTIME(fecha_ultimo_acceso) > DATE_ADD(NOW(), interval -5 month)
                GROUP BY uid_empresa
            )
        ";

        return (bool) $this->db->query($SQL, 0, 0);
    }

    public static function cronCall($time, $force=false)
    {
        $db = db::singleton();
        $periodo = 10*24*60*60; // 10dias * 24h * 60m * 60s

        // Actualizar lista de emails
        if(date("H:i", $time)== "02:30") return self::updateMalingList();

        return true;
    }

    public static function updateMalingList()
    {
        $pwd = @$_SERVER["PWD"];

        $order = " (
            SELECT DATE_ADD(NOW(), interval -5 month) > FROM_UNIXTIME(fecha_ultimo_acceso)
            FROM ". TABLE_USUARIO ." INNER JOIN ". TABLE_PERFIL ." USING(uid_usuario)
            WHERE perfil.uid_empresa = empresa_contacto.uid_empresa GROUP BY uid_empresa
            ORDER BY fecha_ultimo_acceso DESC
        ) ASC ";

        /*
        // Actualización de la lista de enterprise
        $filter = "uid_empresa IN (SELECT uid_empresa FROM ". TABLE_EMPRESA ." WHERE is_enterprise = 1)";
        $enterpriseContacts = self::getAll($filter, $order);
        $total = count($enterpriseContacts);

        foreach ($enterpriseContacts as $i => $contact) {
            if ($active = $contact->isActive()) {
                $res = $contact->addContactToList(self::SENDY_ENTERPRISE_COMPANIES);
            } else {
                $res = $contact->removeContactFromList(self::SENDY_ENTERPRISE_COMPANIES);
            }

            if($pwd) echo ($active?"Add to":"Remove from") ." enterprise list contact ".($i+1)."/{$total}: $res\n";
        }
        */

        // Actualización de la lista de empresas activas
        $contacts = self::getAll(false, $order);
        $total = count($contacts);

        foreach ($contacts as $i => $contact) {
            if ($active = $contact->isActive()) {
                $res = $contact->addContactToList(self::SENDY_ACTIVE_COMPANIES_LIST);
            } else {
                $res = $contact->removeContactFromList(self::SENDY_ACTIVE_COMPANIES_LIST);
            }

            if($pwd) echo ($active ? "Add to" : "Remove from") ." active list contact ".($i+1)."/{$total}: $res\n";
        }

        return true;
    }

    public function getGlobalStatusForClient(empresa $company, Iusuario $user)
    {
        return null;
    }

    public function getToken()
    {
        $token = $this->obtenerDato('token');

        if (!$token) {
            $token = $this->createToken();
        }

        if ($token) return $token;
        return false;
    }

    public function createToken()
    {
        $token  = md5($this->getUID().time());

        $SQL    = "SELECT count(uid_empresa_contacto)
        FROM $this->tabla
        WHERE token = '{$token}'";

        if ((bool) $this->db->query($SQL, 0, 0)) return $this->createToken();

        $SQL = "UPDATE $this->tabla
        SET token = '{$token}'
        WHERE uid_empresa_contacto = {$this->getUID()}";

        if (!$this->db->query($SQL)) {
            return false;
        }

        return $token;
    }

    public function hasTemplateAssigned($template)
    {
        $table  = TABLE_CONTACTO_PLANTILLAEMAIL;
        $sql    = "SELECT uid_plantillaemail FROM $table
        WHERE uid_contacto = {$this->getUID()}
        AND  uid_plantillaemail = {$template->getUID()}
        ";

        if ($this->esPrincipal() && !in_array($template->getUID(), plantillaemail::$templatesToAvoid)) {
            //If the contact is principal, and the template is not in the no mandatory set, return true.
            return true;
        }

        return (bool) $this->db->query($sql, 0, 0);
    }

    public function assignTemplates(ArrayObjectList $templates)
    {
        $table  = TABLE_CONTACTO_PLANTILLAEMAIL;
        $values = [];

        foreach ($templates as $template) {
            $values[] = "({$this->getUID()}, {$template->getUID()})";
        }

        $values = implode(",", $values);
        $sql    = "INSERT IGNORE INTO $table  (uid_contacto, uid_plantillaemail)
        VALUES $values";

        return (bool) $this->db->query($sql);
    }

    public function removingTemplates()
    {
        $table  = TABLE_CONTACTO_PLANTILLAEMAIL;

        $sql    = "DELETE FROM $table
        WHERE uid_contacto = {$this->getUID()}";

        return (bool) $this->db->query($sql);
    }

    public static function getContactbyToken($token)
    {
        $db     = db::singleton();
        $table  = TABLE_CONTACTOEMPRESA;

        $sql    = "SELECT uid_empresa_contacto FROM $table
        where token = '{$token}'";

        $uid = $db->query($sql, 0, 0);
        if (is_numeric($uid)) {
            return new contactoempresa($uid);
        }

        return false;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $arrayCampos = new FieldList();
        $arrayCampos["nombre"] =        new FormField(array("tag" => "input", "type" => "text", "blank" => false ));
        $arrayCampos["apellidos"] =     new FormField(array("tag" => "input", "type" => "text" ));
        $arrayCampos["email"] =         new FormField(array("tag" => "input",   "type" => "text", "blank" => false, "match" => elemento::getEmailRegExp()));
        $arrayCampos["movil"] =         new FormField(array("tag" => "input", "type" => "text" ));
        $arrayCampos["telefono"] =      new FormField(array("tag" => "input", "type" => "text" ));
        $arrayCampos["referencia"] =    new FormField(array("tag" => "input", "type" => "text" ));
        $arrayCampos["idioma"] =        new FormField(array('tag' => 'select', 'default'=>'Seleccionar...','data'=> system::getLanguages()));

        if (isset($modo)&&is_string($modo)) {
            switch ($modo) {
                case self::PUBLIFIELDS_MODE_NEW: case self::PUBLIFIELDS_MODE_IMPORT:
                    $arrayCampos["principal"] = new FormField(array());
                    $arrayCampos["uid_empresa"] = new FormField(array());
                break;
            }
        }

        return $arrayCampos;
    }

    public function getTableFields()
    {
        return array(
            array("Field" => "uid_empresa_contacto",    "Type" => "int(10)",        "Null" => "NO",     "Key" => "PRI",     "Default" => "",        "Extra" => "auto_increment"),
            array("Field" => "uid_empresa",         "Type" => "int(10)",        "Null" => "NO",     "Key" => "MUL",     "Default" => "",        "Extra" => ""),
            array("Field" => "nombre",                  "Type" => "varchar(50)",    "Null" => "NO",     "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "apellidos",               "Type" => "varchar(150)",   "Null" => "NO",     "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "email",                   "Type" => "varchar(512)",   "Null" => "NO",     "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "referencia",              "Type" => "varchar(512)",   "Null" => "NO",     "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "principal",               "Type" => "int(10)",        "Null" => "NO",     "Key" => "",        "Default" => "0",       "Extra" => ""),
            array("Field" => "telefono",                "Type" => "varchar(50)",    "Null" => "NO",     "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "movil",                   "Type" => "varchar(50)",    "Null" => "NO",     "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "fax",                 "Type" => "varchar(50)",    "Null" => "NO",     "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "enviado",             "Type" => "int(11)",        "Null" => "NO",     "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "idioma",                  "Type" => "varchar(2)",     "Null" => "NO",     "Key" => "",        "Default" => "es",      "Extra" => ""),
            array("Field" => "token",                   "Type" => "varchar(500)",   "Null" => "YES",    "Key" => "",        "Default" => "",        "Extra" => "")
        );
    }

}
