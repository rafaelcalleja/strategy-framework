<?php

use Dokify\Domain\Company\Invitation\Invitation;

class signinRequest extends categorizable implements Ielemento {

    const STATE_NOT_SENT                    = -1;
    const STATE_PENDING                     = 0;
    const STATE_ACCEPTED                    = 1;
    const STATE_REJECTED                    = 2;
    const STATE_DISCARD                     = 3;
    const STATE_CONFIGURATION               = 4;

    const FIRST_STEP                        = 1;
    const SECOND_STEP                       = 2;
    const THIRD_STEP                        = 3;

    const SEND_CONFIRM_EMAIL_COMPANY        = "confirm_user";
    const SEND_ACCEPT_INVITATION_COMPANY    = "accept_company";

    const FIRST_NOTIFICATION_COMPANY        = 2;
    const SECOND_NOTIFICATION_COMPANY       = 5;
    const THIRD_NOTIFICATION_COMPANY        = 10;
    const DAYS_TO_EXPIRE                    = 20;

    const TYPE_INTERNAL                     = "internal";
    const TYPE_EXTERNAL                     = "external";

    public function __construct( $param, $extra = false ){
        $this->tipo = "signinRequest";
        $this->tabla = TABLE_SIGNINREQUEST;
        $this->instance( $param, $extra );
    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Company\Invitation\Invitation
     */
    public function asDomainEntity()
    {
        $info = $this->getInfo();
        $creationDate = DateTime::createFromFormat('U', $info['date']);

        // Optional properties
        $acceptedDate      = ($info['end_date'] && $info['end_date'] != 0) ? DateTime::createFromFormat('U', $info['end_date'])                   : null;
        $deadlineDate      = $this->getDeadline()                          ? DateTime::createFromFormat('U', $this->getDeadline())                : null;
        $companyInvitedUid = $this->getCompanyInvited()                    ? new \Dokify\Domain\Company\CompanyUid($info['uid_empresa_invitada']) : null;

        $expiredEmailDate = null;
        if ('' !== $info['expired_email_date']) {
            $expiredEmailDate = new \DateTime($info['expired_email_date']);
        }

        $type = $info['tipo'] === '' ?  Invitation::TYPE_EXTERNAL : $info['tipo'];

        // Instance the entity
        $entity = new \Dokify\Domain\Company\Invitation\Invitation(
            new \Dokify\Domain\Company\Invitation\InvitationUid($this->getUID()),
            new \Dokify\Domain\Company\CompanyUid($info['uid_empresa']),
            new \Dokify\Domain\User\UserUid($info['uid_usuario']),
            $info['nombre'],
            $info['email'],
            $info['cif'],
            $type,
            $this->getCountry(),
            $info['token'],
            $this->getState(),
            $creationDate,
            $this->obtenerDato('client_configured'),
            $this->getAppVersion(),
            $companyInvitedUid,
            $acceptedDate,
            $deadlineDate,
            $this->getMessage(),
            $info['phone'],
            $expiredEmailDate
        );

        return $entity;
    }

    public function getAppVersion ()
    {
        return (int) $this->obtenerDato('app_version');
    }

    public static function getRouteName () {
        return 'invitation';
    }

    public function updateData($data, Iusuario $usuario = NULL, $mode = NULL) {
        if (isset($data['email'])) {
            if (StringParser::isEmail($data['email']) === false) {
                throw new Exception(_("The email address is not valid"));
            }
        }
        return $data;
    }
    public static function defaultData($data, Iusuario $usuario = null) {

        $data['token'] = self::createToken();
        $data['date'] = time();
        if ($usuario instanceof Iusuario) {
            $data['uid_usuario'] = $usuario->getUID();
        }

        if (!isset($data['tipo'])) {
            $data['tipo'] = self::TYPE_EXTERNAL;
        }

        if ($data['tipo'] == self::TYPE_EXTERNAL) {
            if (!isset($data['email']) || !StringParser::isEmail($data['email'])) {
                throw new Exception("The email address is not valid");
                exit;
            }
        }

        $country    = new pais($data['uid_pais']);

        if ($country->exists() == false) {
            throw new Exception(_('Specify a valid country'));
        }

        if (empty($data['uid_empresa_invitada']) && vat::checkValidVAT($country, $data['cif']) == false) {
            // notes: VAT for companies
            throw new Exception(_('The VAT number is invalid'));
        }

        return $data;
    }

    public function getUserVisibleName(){
        $info = $this->getInfo();
        return $info["nombre"];
    }

    public function getCompany(){
        $info = $this->getInfo();
        return new empresa($info["uid_empresa"]);
    }

    public function getCompanyInvited(){
        $companyID = $this->obtenerDato("uid_empresa_invitada");
        if (is_numeric($companyID) && $companyID > 0) {
            return new empresa($companyID);
        }
        return false;
    }

    public function getInviterUser(){
        $info = $this->getInfo();
        return new usuario($info["uid_usuario"]);
    }

    public function getInvitationEmail(){
        $info = $this->getInfo();
        return $info["email"];
    }

    public function getToken(){
        $info = $this->getInfo();
        return $info["token"];
    }

    public function getCountry(){
        $info = $this->getInfo();
        return new pais($info["uid_pais"]);
    }

    public function getVat(){
        $info = $this->getInfo();
        return $info["cif"];
    }

    public function getState(){
        $info = $this->getInfo();
        return $info["state"];
    }

    public function getDate($offset = 0){
        $info = $this->getInfo();
        return $info["date"] - (3600 * $offset);
    }

    public function getEndDate($offset = 0)
    {
        $info = $this->getInfo();
        if (false === empty($info["end_date"])) {
            return $info["end_date"] - (3600 * $offset);
        }
        return false;
    }

    public function getDeadline(){
        $info = $this->getInfo();
        if ($info["deadline_ok"] && $info["deadline_ok"]!=0) return strtotime($info["deadline_ok"]);
        return false;
    }

    public function getName(){
        $info = $this->getInfo();
        return $info["nombre"];
    }

    public function getMessage(){
        return $this->obtenerDato("message");
    }

    public function getTypeInvitation()
    {
        $info = $this->getInfo();
        return in_array($info["tipo"], [self::TYPE_EXTERNAL, self::TYPE_INTERNAL]) ? $info["tipo"] : null;
    }

    public function isInternal()
    {
        return $this->getTypeInvitation() === self::TYPE_INTERNAL;
    }

    /**
     * Define la información que se mostrará en una fila de un grid de la aplicación antigua
     * @param  Iusuario     $usuario : Usuario
     * @param  Ielemento    $parent  : No se usa
     * @param  array        $data    : Datos de entrada
     * @return array    : Elementos que se mostrarán en el grid
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $data = array())
    {
        $info = parent::getInfo(true);
        $data = $info[ $this->uid ];

        $linedata = array();
        $linedata["nombre"] =  $data["nombre"];
        $linedata["email"] = $data["email"];

        if ($data["date"]) {
            $timezone   = $usuario->getTimezoneOffset();
            $date       = $data["date"] - 3600*$timezone;
            $linedata["fecha_envio"] = date("d/m/Y h:i", $date);
        }

        return array($this->getUID() => $linedata);

    }

    public function getInlineArray (Iusuario $usuario = NULL, $config = false, $data = NULL) {
        $inline             = [];
        $lang               = Plantilla::singleton();
        $inviter            = $this->getInviterUser();
        $inviterName        = $inviter->getUserVisibleName();
        $canSendInvitation  = $this->existsCompany();
        $isExpired          = $this->invitationExpired();
        $state              = $this->getState();
        $date               = $this->obtenerDato('date');
        $module             = $this->getModuleName();

        // prepare the inviter data
        $inviterData    = ["img" => RESOURCES_DOMAIN . "/img/famfam/user.png"];
        $inviterData[]  = [
            "title" => sprintf($lang('company_invited_by'), $inviterName),
            "nombre" => $inviterName,
            "href"  => $inviter->obtenerUrlFicha()
        ];


        $statusData = [];

        // if not sent yet..
        if ($state == self::STATE_NOT_SENT) {
            $statusData[]   = [
                'tagName'   => 'span',
                "nombre"    => $lang('enviando'). "..."
            ];
        } elseif ($state == self::STATE_CONFIGURATION) {
            if ($this->needConfigureClients($usuario)) {
                $statusData     = [
                    "img" => RESOURCES_DOMAIN . '/img/famfam/exclamation.png',
                    "title"     => $lang('configure_clientes')
                ];

                $statusData[]   = [
                    "nombre"    => $lang('configure_clientes'),
                    "href"      => "/agd/asignarcliente.php?m={$module}&poid={$this->getUID()}&comefrom=nuevo&return=0"
                ];
            } else if ($this->needConfigureAssignment($usuario)) {
                $statusData     = [
                    "img" => RESOURCES_DOMAIN . '/img/famfam/exclamation.png',
                    "title"     => $lang('configure_clientes')
                ];
                $statusData[]   = [
                    "nombre"    => $lang('configure_assignments'),
                    "href"      => "#asignacion.php?m={$module}&poid={$this->getUID()}&comefrom=nuevo&return=0"
                ];
            }
        // If it is expired
        } elseif ($isExpired) {
            $statusData[]   = [
                'tagName'   => 'span',
                'nombre'    =>  $lang('caducada')
            ];
        } else {
            $statusData[]   = [
                'tagName'   => 'span',
                'nombre'    =>  $lang('sent')
            ];
        }

        // Compose the inline
        $inline[] = $inviterData;
        $inline[] = $statusData;


        return $inline;
    }

    public function changeStateInvitation($state=0) {

        return $this->update(array("state" => (int)$state));
    }


    public function changeEmailInvitation($email) {

        return $this->update(array("email" => $email));
    }

    public function updateNewCompany($company) {

        return $this->update(array("uid_empresa_invitada" => $company->getUID()));
    }


    public function invitationExpired()
    {
        return $this->asDomainEntity()->isExpired();
    }

    public function acceptInvitation() {

        if ($this->update(array("state" => self::STATE_ACCEPTED)) && $this->update(array("end_date" => time()))){
            return true;
        }

        return false;

    }

    public static function getFromToken($token) {

        $uid = db::get("SELECT uid_signin_request FROM " .TABLE_SIGNINREQUEST. " WHERE token = '". db::scape($token)."'", 0, 0);
        if ($uid) return new self($uid);
        return false;
    }

    public static function allPendingInvitations()
    {
        $invitationsTable = TABLE_SIGNINREQUEST;
        $pendingStatus = self::STATE_PENDING;

        $sql = "SELECT uid_signin_request
            FROM {$invitationsTable}
            WHERE state = {$pendingStatus}
            ORDER BY date DESC
        ";

        $pendingInvitations = db::get($sql, "*", 0, 'signinRequest');
        return new ArrayObjectList($pendingInvitations);
    }

    public static function getNotSentInvitations() {

        $sql = "SELECT uid_signin_request FROM " .TABLE_SIGNINREQUEST. " WHERE state = ".self::STATE_NOT_SENT. " ORDER BY date DESC";
        $notSentInvitations = db::get($sql, "*", 0, 'signinRequest');
        return new ArrayObjectList($notSentInvitations);
    }

    public static function createToken() {
        return uniqid();
    }

    public function validate($key, $value, $extraData) {

        $key = str_replace("_", "", $key);
        $func = array( "signinRequest", "validate". $key);

        if (is_callable($func)) {
            $val = call_user_func($func,$value, $extraData);
        } else {
            $val = trim($value);
        }

        return $val;

    }

    public function validateKind($value, $extraData) {
        if (false === is_numeric($value)) {
            return false;
        }

        $kinds = array_keys(empresa::getKindsSelect());
        return in_array((int) $value, $kinds);
    }

    public function validateUsuario($value, $extraData) {
        return (!usuario::fromUserName($value) && preg_match("/". usuario::getUserRegExp() ."/", $value));
    }

    public function validateEmail($value, $extraData) {
        return (!usuario::isEmailInUse($value, NULL) && preg_match("/". elemento::getEmailRegExp() ."/", $value));
    }

    public function validatePass($value, $extraData) {
        $checkPass = usuario::comprobarPassword($value,$extraData['pass2']);
        if (is_bool($checkPass) && $checkPass && trim($extraData['pass2'])) {
            return true;
        }
        return false;
    }

    public function validateCif($value, $extraData) {

        if (empresa::fromCif($value) || !isset($extraData['uid_pais']) || !is_numeric($extraData['uid_pais'])){ return false;}

        $country = new pais($extraData['uid_pais']);
        if (!$country->exists()) {return false;}

        $countries = pais::obtenerTodos();

        /* NOTA IMPORTANTE: La linea de abajo es necesaria ya que construimos las funciones de validar los VAT
            DINAMICAMENTE DEPENDIENDO DEL PAIS, Lo BUENO SERÍA TENERLOS EN INGLES, PERO SOLO
            LOS TENEMOS EN CASTELLANO, Y OBVIAMENTE NO PODEMOS LLAMARA A UNA FUNCION CON LA "Ñ"
            DE ESPAÑA. ASI QUE LE LLAMAMOS SPAIN

            ESTO ES UNA SOLUCIÓN TEMPORAL HASTA QUE TENGAMOS LOS NOMBRES DE LOS PAISES EN INGLÉS.
        */

        $pais = ($country->getUID() == pais::SPAIN_CODE) ? "Spain" : $country->getUserVisibleName();

        if ($countries->toIntList()->contains($country->getUID())) {
            $funcValidVat = "vat::isValid" .$pais. "VAT";
            if (is_callable($funcValidVat)) {
                return call_user_func($funcValidVat, $value);
            } else {
                return trim($value);
            }
        }
    }

    public function validateId($value, $extraData) {

        if (!isset($extraData['uid_pais']) || !is_numeric($extraData['uid_pais'])) { return (trim($value) && !usuario::isIdInUse($value)); }
        $country = new pais($extraData['uid_pais']);
        if ($country->getUID() == pais::SPAIN_CODE) {
            return (!usuario::isIdInUse($value) && vat::isValidSpainId($value));
        } else {
            return (trim($value) && !usuario::isIdInUse($value));
        }

    }



    public function validateUidPais($value, $extraData) {

        if (!is_numeric($value)) { return false; }

        $country = new pais($value);
        if ($country->exists()) {
            return true;
        }
        return false;

    }

    public function validateUidProvincia($value, $extraData) {

        if (is_numeric($extraData['uid_pais'])) {
            $country = new pais($extraData['uid_pais']);
            if (!$country->exists()) return false;
            $validCountries = pais::getValidCountries();
            if (!$validCountries->contains($country)) {
                return true;
            }
        }
        if (!is_numeric($value)) { return false; }

        $provincia = new provincia($value);
        if ($provincia->exists()) {
            return true;
        }
        return false;

    }

    public function validateUidMunicipio($value, $extraData) {

        if (is_numeric($extraData['uid_pais'])) {
            $country = new pais($extraData['uid_pais']);
            if (!$country->exists()) return false;
            $validCountries = pais::getValidCountries();
            if (!$validCountries->contains($country)) {
                return true;
            }
        }

        if (!is_numeric($value)) { return false; }

        $municipio = new municipio($value);
        if ($municipio->exists()) {
            return true;
        }
        return false;

    }

    public function validateTipoEmpresa($value, $extraData) {

        if (!is_numeric($value)) { return false; }
        $agrupador = new agrupador($value);
        $agrupadoresTipoEmpresa = $this->getCompany()->obtenerAgrupadoresVisibles(new categoria(categoria::TYPE_TIPOEMPRESA));

        if ($agrupadoresTipoEmpresa->contains($agrupador)) {
            return true;
        }

        return false;

    }

    public function validatePreventionService($value, $extraData) {
        if ($value) {
            return true;
        }

        return false;
    }

    public function validateTelefono($value, $extraData) {

        return strlen($value) > 5;

    }

    public function singUpElementCompanyAndUser($data)
    {

        $newUser = usuario::crearNuevo($data, $this->getAppVersion());

        if (!$newUser instanceof usuario) {
            return $newUser;
        }

        if ($newUser->necesitaCambiarPassword()) {
            $newUser->changePasswordNotRequired();
        }


        $inviterCompany = $this->getCompany();
        $user = $this->getInviterUser();

        if (!$user->exists()) {
            $user = null;
        }

        $dataCompany = array(
            "cif" => $data['cif'],
            "nombre" => $data['nombre_empresa'],
            "nombre_comercial" => $data['nombre_comercial'],
            "representante_legal" => $data['representante_legal'],
            "uid_pais" => $data['uid_pais'],
            "direccion" => $data['direccion'],
            "cp" => $data['cp'],
            "kind" => $data['kind'],
            "prevention_service" => $data['prevention_service'],
        );

        if (is_numeric($data['uid_pais'])) {
            $country = new pais($data['uid_pais']);

            if (!$country) {
                return false;
            }

            if (pais::getValidCountries()->contains($country)) {
                $dataCompany["uid_provincia"] = $data['uid_provincia'];
                $dataCompany["uid_municipio"] = $data['uid_municipio'];
            }
        }

        $newCompany = new empresa($dataCompany, $user);

        if (!$newCompany->exists() || !$inviterCompany->exists()) {
            $newUser->eliminar();
            return false;
        }

        $this->updateNewCompany($newCompany);
        $newCompany->hacerInferiorDe($inviterCompany, $user, null, true);

        $assigneds = new ArrayObjectList;
        if (isset($data['tipo_empresa'])) {
            $arrayIds       = array($data['tipo_empresa']);
            $agrupadores    = $newCompany->asignarAgrupadores($arrayIds, $user);
            $assigneds      = $assigneds->merge($agrupadores);
        }

        $companyEntity  = $inviterCompany->asDomainEntity();
        $userEntity     = $user ? $user->asDomainEntity() : null;
        $assigneds      = $assigneds->unique();
        foreach ($assigneds as $group) {
            $assignment = $newCompany->getAssignment($group);
            $entity     = $assignment->asDomainEntity();
            $event      = new \Dokify\Application\Event\Assignment\Store($entity, $companyEntity, $userEntity);

            $this->app->dispatch(\Dokify\Events::POST_ASSIGNMENT_STORE, $event);
        }

        $uidPerfil = $newUser->crearPerfil($newUser, $newCompany, true);
        $userPefil = new perfil($uidPerfil);
        $rol = rol::obtenerRolesGenericos(rol::ROL_DEFAULT);
        $perfilActualizado = $rol->actualizarPerfil($userPefil, true);

        $dataContact = array("nombre" => $data['nombre'],
                            "apellidos" => $data['apellidos'],
                            "email" => $data['email'],
                            "telefono" => $data['telefono'],
                            "idioma" => system::getIdLanguage($country->getLanguage()),
                            "principal" => 1,
                            "uid_empresa" => $newCompany
                        );

        $newContact = new contactoempresa($dataContact, $newUser);

        $invitationAccepted = $this->acceptInvitation();

        $visibility     = $this->applyVisibility();
        $assignments    = $this->applyAssignments();

        if ($perfilActualizado && $newContact instanceof contactoempresa && $invitationAccepted && $visibility && $assignments) {
            $this->addStartingAssignments($newCompany);

            // update requests, may be documents with contract reference
            $inviterCompany->actualizarSolicitudDocumentos();

            $newCompany->actualizarSolicitudDocumentos($user);
            return true;
        }

        if ($newCompany->exists()) {
            $newCompany->eliminar();
        }

        if ($newUser->exists()) {
            $newUser->eliminar();
        }

        return false;
    }


    public function addStartingAssignments(empresa $empresa) {
        $agrupadores        = new ArrayObjectList;
        $categories         = array(categoria::TYPE_PUESTO, categoria::TYPE_GRUPODERIESGO, categoria::TYPE_TIPOMAQUINARIA);
        $clientCompanies    = $this->obtenerEmpresasSolicitantes(null, false);
        $iniviterCompany    = $this->getCompany();

        foreach ($clientCompanies as $client) {
            foreach ($categories as $category) {
                $condition          = array();
                $condition[] = new categoria($category);
                $condition[]        = $empresa;
                $condition['asignado'] = $iniviterCompany;

                $incategory = $client->obtenerAgrupadoresVisibles($condition);
                if ($incategory) {
                    $agrupadores = $agrupadores->merge($incategory);
                }
            }
        }

        if ($intList = $agrupadores->toIntList()) {
            $agrupadores = $empresa->asignarAgrupadores($intList->getArrayCopy());

            if (true === is_countable($agrupadores) && count($agrupadores)) {
                return true;
            }
        }

        return false;
    }


    public function existsCompany() {
        $sql = "SELECT uid_empresa FROM " .TABLE_EMPRESA. " where cif = '{$this->getVat()}'";
        return !(bool) db::get($sql, 0, 0);
    }


    static public function optionsFilter($uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){
        $condicion = array();

        if ($uidelemento) {
            $invitation = new self($uidelemento);
            if ($invitation instanceof signinRequest) {
                if (!$invitation->existsCompany()) {
                    $condicion[] = " uid_accion NOT IN (75, 172) ";
                }
                if ($invitation->getState() != signinRequest::STATE_PENDING) {
                    $condicion[] = " uid_accion NOT IN (75) ";
                }
            }
        }

        if (count($condicion)) {
            return " AND ". implode(" AND ", $condicion);
        }

        return false;
    }

    public function sendEmailWithParams($asunto, $tpl, array $params, array $logParams, $recipients = false, $lang = 'es') {
        set_time_limit(0);

        $plantilla = new Plantilla();
        $log = log::singleton();

        $method = array($log,'info');
        call_user_func_array($method, $logParams);

        foreach ($params as $key => $value) {
            $plantilla->assign($key, $value );
        }

        if (CURRENT_ENV == 'dev') {
            $recipients = email::$developers;
        }

        if ($recipients) {
            $email = new email($recipients);

            $htmlPath ='email/'.$tpl.'.tpl';
            $html = $plantilla->getHTML($htmlPath);
            $email->establecerContenido($html);
            $email->establecerAsunto($plantilla->getString($asunto, $lang));

            $estado = $email->enviar();
            if ($estado !== true ) {
                $estado = $estado && trim($estado) ? trim($estado) : $plantilla('error_desconocido');
                $log->resultado("error $estado", true);
                throw new Exception($estado);
            }

            $log->resultado("ok ", true);
            return true;
        }

        return false;
    }

    public function sendEmail($action = "", $data = false) {

        $inviterCompany = $this->getCompany();
        $mailTemplate = new Plantilla();

        try {
            switch ($action) {
                case self::SEND_ACCEPT_INVITATION_COMPANY:

                    $invitedCompany = $this->getCompanyInvited();
                    $inviter = $this->getInviterUser();
                    $lang = $this->getCountry()->getLanguage();
                    $tpl = "acceptInvitation";
                    $params = array("lang" => $lang,
                                    "company" => $invitedCompany,
                                    "token" => $this->getToken(),
                                    "elemento_logo" => $inviterCompany->obtenerLogo()
                            );

                    $infolog = "invitation accepted new company: {$invitedCompany->getUID()}";
                    $log = array('signinRequest', $infolog, $this->getInvitationEmail());

                    $mainContact = $inviterCompany->obtenerContactoPrincipal();
                    if ($mainContact) {
                        $recipients = array($inviter->getEmail(),$mainContact->obtenerEmail());
                    } else {
                        $recipients = $inviter->getEmail();
                    }

                    try {
                        return $this->sendEmailWithParams('invitacion_aceptada', $tpl ,$params, $log, $recipients, $lang);
                    } catch(Exception $e) {
                        echo "Error sending accepted invitation notice: {$e->getMessage()}\n";
                    }
                    break;


                case self::SEND_CONFIRM_EMAIL_COMPANY:

                    $invitedCompany = $this->getCompanyInvited();
                    $emailRecipient = $data['email'];
                    $lang = plantilla::getCurrentLocale();

                    $tpl = "confirmNewUser";
                    $params = array("usuario" => $data['usuario'],
                                    "password" => $data['pass'],
                                    "lang" => $lang,
                                    "elemento_logo" => $invitedCompany->obtenerLogo(),
                                    "elementoNombre" => $data['nombre'],
                                    "empresaNombre" => $data['nombre_empresa']
                            );

                    $infolog = "send welcome new company";
                    $log = array('signinRequest', $infolog, $emailRecipient);

                    try {
                        return $this->sendEmailWithParams('email_subject_signup_confirmation', $tpl ,$params, $log, $emailRecipient, $lang);
                    } catch(Exception $e) {
                        echo "Error sending confirmation new company invitation: {$e->getMessage()}\n";
                    }
                    break;

            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return false;

    }

    static public function cronCall($time, $force = false, $items = NULL){

        $db = db::singleton();
        $log = new log();
        $app = \Dokify\Application\Console::getInstance();

        //Invitations that were not send because were uploaded from file
        $notSentInvitations = self::getNotSentInvitations();
        foreach ($notSentInvitations as $invitation) {

            echo "Updating and sending invitation to {$invitation->getInvitationEmail()} from company {$invitation->getCompany()->getUserVisibleName()}... ";
            $log->info("signinRequest", "cron user sending invitation from company: ".$invitation->getCompany()->getUID(). " to  {$invitation->getInvitationEmail()}", $invitation->getInvitationEmail());

            if (empresa::fromCif($invitation->getVat())) {
                echo "La empresa ya existe, no mandamos emails\n";
            } else {

                $invitationEvent = new \Dokify\Application\Event\Company\Invitation\Store($invitation->asDomainEntity());
                $app->dispatch(\Dokify\Events\Company\InvitationEvents::POST_COMPANY_INVITATION_UPDATE, $invitationEvent);
                $invitation->update(array("state" => self::STATE_PENDING, "date" => time()));
                $log->resultado("ok", true);
                echo "Email mandado\n";
            }
        }

        //Resend invitations pending. important: just one per day
        $isTime = (date("H:i", $time) == "07:00");
        if (!$isTime && !$force) return true;

        $pendingInvitations = self::allPendingInvitations();
        echo "\n\nChecking ". count($pendingInvitations) ." invitations pending\n\n";
        foreach ($pendingInvitations as $invitation) {
            if (empresa::fromCif($invitation->getVat())) continue;

            $firstAction = new DateTime();
            $now = new DateTime();
            $firstAction->setTimestamp($invitation->getDate());
            $datediff = $firstAction->diff($now);
            $deadlineTime = $invitation->getDeadline();

            $befordeadline = true;
            if ($deadlineTime) {
                $deadline = new DateTime("@$deadlineTime");
                $befordeadline = ($deadline > $now);
            }

            $notifyPending = $datediff->days == self::FIRST_NOTIFICATION_COMPANY || $datediff->days == self::SECOND_NOTIFICATION_COMPANY || $datediff->days == self::THIRD_NOTIFICATION_COMPANY;

            if ($befordeadline && $notifyPending)  {
                echo "Notifiying email [{$invitation->getInvitationEmail()}]. It has been {$datediff->days} days since the invitation was created\n";
                $inviterCompany = $invitation->getCompany();
                $infolog = "send reminder email new company days {$datediff->days}: uid inviter => {$inviterCompany->getUID()}: cif => {$invitation->getVat()} ";
                $log = array('signinRequest', $infolog, $invitation->getInvitationEmail());
                try {
                    $emailSendService = $app['company_invitation.email_send'];
                    $emailSendRequest = $emailSendService->createRequest($invitation->asDomainEntity());
                    $emailSendRequest->setReminder($datediff->days);
                    $emailSendService->execute($emailSendRequest);
                } catch(Exception $e) {
                    echo "Error sending the invitation: {$e->getMessage()}\n";
                }
            }
        }

        return true;
    }

    public function accept () {

        $invitedCompany = $this->getCompanyInvited();
        $changeState    = $this->changeStateInvitation(signinRequest::STATE_ACCEPTED);
        $visibility     = $this->applyVisibility();
        $assignments    = $this->applyAssignments();
        $this->addStartingAssignments($invitedCompany);

        return $changeState && $visibility && $assignments;
    }

    public function cancel () {
        return $this->changeStateInvitation(signinRequest::STATE_REJECTED);
    }

    public static function importFromFile($file, $company, $usuario, $post = null)
    {
        $log = new log();
        $tmptabla = "tmp_table_signinRequest_importFromFile_".$usuario->getUID().uniqid();
        $temporal = DB_TMP .".$tmptabla";
        $reader = new dataReader($tmptabla , $file["tmp_name"], archivo::getExtension($file["name"]) );

        if ($reader->cargar(true)) {

            /* Comprobamos que los emails que quieren importar no esten repetidos en el fichero */
            $sql = "SELECT email FROM $temporal GROUP BY email HAVING count(email) > 1 ";
            $repitedEmailFile = db::get($sql, "*", 0);

            if ($repitedEmailFile) {
                $reader->borrar();
                return array("email" => $repitedEmailFile);
            }

            /* Comprobamos que el email no este registrado ya como una petición válida  */
            $sql = "SELECT email FROM " .TABLE_SIGNINREQUEST. " sr INNER JOIN $temporal USING(email) WHERE (sr.state = " .self::STATE_ACCEPTED. " OR sr.state = " .self::STATE_PENDING. " OR sr.state = " .self::STATE_NOT_SENT. ") and uid_empresa = {$company->getUID()} GROUP BY sr.email";
            $repitedEmailBD = db::get($sql, "*", 0);

            if ($repitedEmailBD) {
                $reader->borrar();
                return array("email" => $repitedEmailBD);
            }

            /* Comprobamos que los cif que quieren importar no esten repetidos en el fichero */
            $sql = "SELECT cif FROM $temporal GROUP BY cif HAVING count(cif) > 1 ";
            $repitedCifFile = db::get($sql, "*", 0);

            if ($repitedCifFile) {
                $reader->borrar();
                return array("cif" => $repitedCifFile);
            }

            /* Comprobamos que el cif no este registrado ya como una petición válida  */
            $sql = "SELECT cif FROM " .TABLE_SIGNINREQUEST. " sr INNER JOIN $temporal USING(cif) WHERE (sr.state = " .self::STATE_ACCEPTED. " OR sr.state = " .self::STATE_PENDING. " OR sr.state = " .self::STATE_NOT_SENT. ") and uid_empresa = {$company->getUID()} GROUP BY sr.cif";
            $repitedCifBD = db::get($sql, "*", 0);

            if ($repitedCifBD) {
                $reader->borrar();
                return array("cif" => $repitedCifBD);
            }

            /* Comprobamos que los cif que quieren importar ya están en la BBDD y enviamos las solicitudes */
            $sql = "SELECT uid_empresa FROM " .TABLE_EMPRESA. " sr INNER JOIN $temporal USING(cif) GROUP BY uid_empresa";
            $repeatedCompanies = db::get($sql, "*", 0, "empresa");

            /* Enviamos todas las solicitudes para empresas que ya existen */
            foreach ($repeatedCompanies as $invitedCompany) {
                if (!solicitud::getFromItem('empresa',$company, $invitedCompany,array('estado'=>empresasolicitud::ESTADO_CREADA),true)) {
                    $resultado      = $invitedCompany->hacerInferiorDe($company, $usuario);
                    $signinRequest  = $company->createInvitation($invitedCompany, $usuario);
                    $changeState    = $signinRequest->changeStateInvitation(self::STATE_PENDING);
                } else {
                    $reader->borrar();
                    return false;
                }

            }

            $reader->borrar();

        }

        $results = self::importBasics($usuario,$file,"signinRequest");

        if (count($results["uid_nuevos"])) {

            foreach ($results["uid_nuevos"] as $id) {

                $signIn = new self($id);
                //Eliminamos las entradas de las empresas que ya existen:
                $companyInvited = empresa::fromCif($signIn->getVat());
                if ($companyInvited instanceof empresa) $signIn->eliminar();

                $log->info("signinRequest", "massive invitation from mail userID: ".$usuario->getUID(), $signIn->getInvitationEmail());

                $statusUpdate = $signIn->update(array("state" => self::STATE_NOT_SENT, "token" => self::createToken(), "uid_usuario" => $usuario, "uid_empresa" => $company,  "uid_pais"=> $usuario->getCountry(), "date" => time()));

                if (is_bool($statusUpdate) && !$statusUpdate) {
                    $log->resultado("Error while updating row. Request elimianted ID: ".$signIn->getUID(), true);
                    $signIn->eliminar();
                    return false;
                }
            }

            $results["ok"] = true;
            return $results;
        }
    }

    public static function checkInvitationCompany($company, $conditions = array()){
        $uidEmpresa = ($company instanceof empresa) ? $company->getUID() : $company;
        $sql        = "SELECT count(uid_signin_request)
            FROM " .TABLE_SIGNINREQUEST. "
            WHERE
            uid_empresa = $uidEmpresa
            AND (state = ".self::STATE_PENDING. "
                OR state = " .self::STATE_NOT_SENT. ")
            ";

        foreach ($conditions as $key => $condition) {
            $filtersOR[] = " {$key} = '{$condition}' ";
        }

        if (isset($filtersOR)) $sql .= " AND ( " .implode(" OR ", $filtersOR). " ) ";

        return (bool) db::get($sql, 0, 0);

    }

    //Implements Icategorizable
    public function obtenerElementosSuperiores(){
        return array($this->getCompany());
    }

    /**
      * It will return all the comany clients of the invitation
      *
      *
      * @return ArrayObjectList
      *
      **/
    public function obtenerEmpresasCliente($addInviter = true) {

        $SQL = "SELECT n1, n2 FROM ". TABLE_SIGNINREQUEST . "_contratacion
                INNER JOIN ". TABLE_SIGNINREQUEST ." USING(uid_signin_request)
                WHERE uid_signin_request = {$this->getUID()}";

        $companies  = new ArrayObjectList();
        $inviter    = $this->getCompany();
        $data       = $this->db->query($SQL, true);

        foreach($data as $array){
            if (isset($array["n1"]) && is_numeric($array["n1"]) && $array["n1"] != $inviter->getUID()) $companies[] = new empresa($array["n1"]);
            if (isset($array["n2"]) && is_numeric($array["n2"]) && $array["n2"] != $inviter->getUID()) $companies[] = new empresa($array["n2"]);
        }

        if ($addInviter) $companies = $companies->merge($inviter)->unique();
        return $companies->unique();
    }

    /**
      * Delete contract chains of an invitation
      *
      *
      *
      * @return bool
      *
      **/
    public function eliminarCadenasContratacion(empresa $empresa, $subs = false){

        $SQL = "DELETE FROM ". TABLE_SIGNINREQUEST ."_contratacion WHERE uid_signin_request = {$this->getUID()}";
        if (!$this->db->query($SQL)) throw new Exception($this->db->lastError());

        return true;
    }

    /**
      * It creates contract chains from the companies passed as arguments
      *
      *
      * @param [] contract chain, up to 3 arguments of type: empresa
      *
      * @return bool
      *
      **/
    public function crearCadenaContratacion(){
        $args = func_get_args();
        if( count($args) < 2 ) throw new Exception("Should pass at least two companies");
        //Removing the company that invited you
        array_shift($args);
        $maxdepth = count($args) + 1;

        // then insert all the companies passed as argument, up to two.
        foreach($args as $i => $company){
            $level = $maxdepth - 1 - $i;
            $fields[] = "n" . $level;
            $values[] = $company->getUID();
        }

        if (count(array_unique($values)) !== count($values)) return false;

        //We need to insert the invitation id first
        array_unshift($fields, "uid_signin_request");
        array_unshift($values, $this->getUID());

        $SQL = "INSERT IGNORE INTO ". TABLE_SIGNINREQUEST ."_contratacion (". implode(",", $fields) .") VALUES (". implode(",", $values).")";

        if( !$this->db->query($SQL) ) throw new Exception($this->db->lastError());
        return true;
    }

    /**
      * It will show if a signin_request has a contract chain for those companies passed as arguments
      *
      *
      * @param [] contract chain, up to 3 arguments of type: empresa
      *
      * @return bool
      *
      **/
    public function esSubcontrataDe(){
        $where      = array();
        $args       = func_get_args();
        if( count($args) < 2 ) throw new Exception("Should pass at least two companies");

        $inviter    = array_shift($args);
        $maxdepth   = count($args) + 1;

        $SQL = "SELECT count(*) FROM ". TABLE_SIGNINREQUEST . "_contratacion
                INNER JOIN ". TABLE_SIGNINREQUEST . " using(uid_signin_request)
                WHERE  uid_empresa = {$inviter->getUID()} AND uid_signin_request = {$this->getUID()}";

        foreach($args as $i => $sup){
            $level   = $maxdepth - 1 - $i;
            $where[] = "n{$level} = {$sup->getUID()}";
        }

        if( count($args) < 2 ){
            $where[] = "(n2 = 0 OR n2 IS NULL)";
        }


        $SQL    .= " AND ". implode(" AND ", $where);
        $res    = $this->db->query($SQL, 0, 0);

        return (bool) $res;
    }

    /**
      * Get a set of client companies
      *
      * @return arrayObjectList<empresa>
      *
      **/
    public function obtenerEmpresasSolicitantes($user = NULL, $getCorp = true){

        $setCompanies       = new ArrayObjectList();
        $clientCompanies    = $this->obtenerEmpresasCliente();
        foreach ($clientCompanies as $client) {
            if ($user instanceof usuario) {
                $limiterUser = $user->getUserLimiter($client);
                if ($limiterUser && !$limiterUser->compareTo($user)) continue;
            }

            if ($getCorp && $client instanceof empresa && ($corp = $client->perteneceCorporacion())) {
                $setCompanies[] = $client;
                if ($user instanceof usuario) {
                    $limiterUser = $user->getUserLimiter($corp);
                    if ($limiterUser && !$limiterUser->compareTo($user)) continue;
                }
                $setCompanies[] = $corp;
            } else {
                $setCompanies[] = $client;
            }
        }

        return $setCompanies->unique();
    }

    /**
      * It shows if an invitation needs to conigure assignments.
      *
      * @param Iusuario. It is necessary to check groups assigned to the invitation.
      *
      * @return bool
      *
      **/
    public function needConfigureAssignment (Iusuario $usuario = NULL) {

        $assignments = $this->getAssignData($usuario);
        if (!count($assignments)) return false;

        $sql = "SELECT count(uid_agrupador_elemento) FROM ". TABLE_AGRUPADOR ."_elemento
        WHERE 1
        AND uid_modulo = {$this->getModuleId()}
        AND uid_elemento = {$this->getUID()}
        ";

        $hasSomeAssigned    = (bool) $this->db->query($sql, 0, 0);

        if (!$hasSomeAssigned) return true;
        $groupsSet          = $this->obtenerAgrupadores();
        $orgsAssigned       = $groupsSet->toOrganizationList();

        // Retrieving all mandatory groups of clients of this element
        $validCompanies = new ArrayObjectList;
        $organizations  = new ArrayAgrupamientoList;
        $companies      = $this->obtenerEmpresasSolicitantes();

        foreach ($companies as $company) {
            $organizationsCompany = $company->obtenerAgrupamientosPropios(['mandatory', 'modulo' => $this->getModuleName(), $usuario->getCompany()]);
            if ($organizationsCompany) $organizations = $organizations->merge($organizationsCompany);
        }

        $needMandatoryGroups = count($organizations->diff($orgsAssigned));
        return !count($organizations) || $needMandatoryGroups;

    }

    /**
      * It shows if an invitation need to configure clients.
      *
      * @param Iusuario. It is necessary to check groups assigned to the invitation.
      *
      * @return bool
      *
      **/
    public function needConfigureClients (Iusuario $user = null) {
        $inviter = $this->getCompany();
        $clients = $inviter->obtenerEmpresasSuperioresSubcontratando(null, null, true, $user);

        $invitedCompany = $this->getCompanyInvited();

        // do not show as client the invited
        if ($invitedCompany instanceof empresa) {
            $clients = $clients->discriminar($invitedCompany);
        }

        return !(bool) $this->obtenerDato('client_configured') && count($clients);
    }

    /**
      * Apply the visibility configured to the invitation to the invited company.
      *
      *
      * @return array<insert query result>
      *
      **/
    public function applyVisibility()
    {
        $newCompany = $this->getCompanyInvited();
        $inviter = $this->getCompany();
        $inviterUser = $this->getInviterUser();

        $subselect =   "SELECT n1,
        @n2 := IF(n2 IS NULL, uid_empresa, n2) as n2,
        @n3 := IF(n2 IS NULL, uid_empresa_invitada, uid_empresa) as n3,
        @n4 := IF(n2 IS NULL, NULL, uid_empresa_invitada) as n4,
        COALESCE(@n4, @n3, @n2) as ntail

        FROM ". TABLE_SIGNINREQUEST ."_contratacion
        INNER JOIN ". TABLE_SIGNINREQUEST ." using(uid_signin_request)
        WHERE uid_signin_request = {$this->getUID()}
        ";

        $insert = "INSERT INTO ". TABLE_EMPRESA . "_contratacion" ."
        (`n1`, `n2`, `n3`, `n4`, `ntail`)
        $subselect
        ";
        $newCompany->cache->clear("idEmpresasSuperiores*");

        $sql = "SELECT n1
        FROM ". TABLE_SIGNINREQUEST ."_contratacion
        INNER JOIN ". TABLE_SIGNINREQUEST ." using(uid_signin_request)
        WHERE uid_signin_request = {$this->getUID()}
        ";

        $newClients = $this->db->query($sql, "*", 0, "empresa");

        if (false == $this->db->query($insert)) {
            return false;
        };

        if (count($newClients)) {
            $app = \Dokify\Application::getInstance();
            $chainNewEmailService = $app['company_chain.send_email_new'];
            foreach ($newClients as $newClient) {
                $entityNewClient = $newClient->asDomainEntity();
                $entityCompany = $newCompany->asDomainEntity();
                $emailRequest = $chainNewEmailService->createRequest(
                    $entityNewClient,
                    $entityCompany,
                    $inviterUser->asDomainEntity()
                );
                $chainNewEmailService->execute($emailRequest);
            }
        }
        return true;
    }

    /**
      * Apply the assigned groups of an invitation to the company invited to the invitation.
      *
      *
      * @return array<insert query result>
      *
      **/
    public function applyAssignments()
    {
        $newCompany = $this->getCompanyInvited();
        if (!$newCompany instanceof empresa) {
            error_log("no company invited yet");
            return false;
        }

        $sql  = "SELECT uid_agrupador, 1 as uid_modulo, uid_empresa_invitada as uid_elemento, fecha, fecha_inicio, bloqueado, rebote, duracion, description
        FROM ". TABLE_AGRUPADOR ."_elemento
        INNER JOIN ". TABLE_SIGNINREQUEST ."
        ON uid_elemento = uid_signin_request
        WHERE 1
        AND uid_modulo = {$this->getModuleId()}
        AND uid_elemento = {$this->getUID()}";

        $rows = $this->db->query($sql, true);

        if ($rows) {
            $companyEntity  = $this->getCompany()->asDomainEntity();
            $userEntity     = $this->getInviterUser()->asDomainEntity();

            foreach ($rows as $row) {
                $insert =  "INSERT IGNORE INTO ". TABLE_AGRUPADOR . "_elemento (
                    uid_agrupador, uid_modulo, uid_elemento, fecha, fecha_inicio, bloqueado, rebote, duracion, description
                ) VALUES (
                    {$row['uid_agrupador']}, {$row['uid_modulo']}, {$row['uid_elemento']},
                    '{$row['fecha']}', '{$row['fecha_inicio']}', {$row['bloqueado']},
                    {$row['rebote']}, {$row['duracion']}, '{$row['description']}'
                )";

                if (!$this->db->query($insert)) {
                    error_log("error applying assignments for the invitation: {$this->getUID()}. SQL: {$insert}");
                    continue;
                }

                if (!$id = $this->db->getLastId()) {
                    $sql = "SELECT uid_agrupador_elemento FROM " . TABLE_AGRUPADOR . "_elemento
                    WHERE 1
                    AND uid_agrupador   = '{$row['uid_agrupador']}'
                    AND uid_modulo      = '{$row['uid_modulo']}'
                    AND uid_elemento    = '{$row['uid_elemento']}'
                    ";

                    if (!$id = $this->db->query($sql, 0, 0)) {
                        error_log("no id found to emit the assignment store event");
                        continue;
                    }
                }

                $assignment = new \Dokify\Assignment($id);
                $assignmentEntity = $assignment->asDomainEntity();

                $event = new \Dokify\Application\Event\Assignment\Store($assignmentEntity, $companyEntity, $userEntity);
                $this->app->dispatch(\Dokify\Events::POST_ASSIGNMENT_STORE, $event);
            }
        }

        //Indirect assignments
        $subselect  = "SELECT new.uid_agrupador_elemento, aea.uid_agrupador
                        FROM ". TABLE_AGRUPADOR ."_elemento ae
                        INNER JOIN  ". TABLE_AGRUPADOR ."_elemento_agrupador aea
                        ON ae.uid_agrupador_elemento = aea.uid_agrupador_elemento
                        JOIN
                        (
                            SELECT uid_agrupador_elemento, uid_agrupador FROM ". TABLE_AGRUPADOR ."_elemento
                            WHERE 1
                            AND uid_modulo = 1
                            AND uid_elemento = {$newCompany->getUID()}
                        ) new
                        ON new.uid_agrupador = ae.uid_agrupador
                        WHERE 1
                        AND uid_modulo = {$this->getModuleId()}
                        AND uid_elemento = {$this->getUID()}
                    ";

        $indirects  = "INSERT IGNORE INTO ". TABLE_AGRUPADOR . "_elemento_agrupador
                    (`uid_agrupador_elemento` ,`uid_agrupador`)
                    $subselect
                ";

        if (!$this->db->query($indirects)) {
            error_log("error applying indirects assignments for the invitation : " . $this->getUID() . " SQL: ".$indirects);
            return false;
        }

        return true;
    }

    public function obtenerUrlFicha ($text = false) {
        return "#empresa/listado.php?comefrom=invitacion&poid={$this->getCompany()->getUID()}";
    }

    public function setClientesConfigured ($user) {
        return $this->update(array("client_configured" => 1), false, $user);
    }

    public function createInvitationCompanyAlreadyExists () {
        $invitedCompany     = $this->getCompanyInvited();
        if (!$invitedCompany instanceof empresa) return true; //No need to create invitation, the company does not exists
        $inviterCompany     = $this->getCompany();
        $message            = $this->getMessage();
        $user               = $this->getInviterUser();
        if (!solicitud::getFromItem('empresa', $inviterCompany, $invitedCompany, array('estado' => empresasolicitud::ESTADO_CREADA), true)) {
            $resultado = true;
        } else {
            $resultado = false;
        }

        if ($resultado === true) {
            $this->changeStateInvitation(self::STATE_PENDING);
            $log = new log();
            $log->info("empresa", "asignar empresa ya existente {$invitedCompany->getUserVisibleName()}", $inviterCompany->getUserVisibleName());
            $log->resultado("ok", true);
            return true;
        }

        return false;
    }

    public function obtenerAccionesRelacion(agrupamiento $agrupamiento, Iusuario $usuario){
        $tpl = Plantilla::singleton();
        $acciones[] = [
            "innerHTML" => $tpl->getString("configurar_aspectos_relacion"),
            "className" => "box-it",
            "img" => RESOURCES_DOMAIN . "/img/famfam/cog_edit.png",
            "href" => "asignacion.php?m=signinRequest&poid=". $this->getUID()."&oid=%s&o=". $agrupamiento->getUID()
        ];

        return $acciones;
    }

    /**
      * Returns distance between an invitation an a company passed as a param
      *
      * @param company
      * @param toString Means if we want to return as string the result.
      *        It is not really necesary but all over the app it is using at least this param, so...
      *
      * @return integer
      *
      **/
    public function obtenerDistancia (empresa $company, $toString=true) {
        $inviterCompany     = $this->getCompany();
        if (!$inviterCompany) return false;
        if ($inviterCompany->compareTo($company)) {
            if ($toString)  return  self::level2String($process);
            return 0;
        }

        return $inviterCompany->obtenerDistancia($company, $toString) + 1;
    }


    public function getGlobalStatusForClient(empresa $company, Iusuario $user){
        return null;
    }

    /***
       * Indicates if we have to show organizations marked as 'ondemand'
       *
       */
    public function canShowOnDemand () {
        return true;
    }

    public function applyHierarchy (usuario $user, $currentClient) {
        return false;
    }

    public function invitedInApp () {
        return (bool) $this->getCompanyInvited();
    }

    public function getCompanies () {
        return new ArrayObjectList([$this->getCompany()]);
    }

    public function canApplyOnDemand (agrupamiento $organizartion, Iusuario $user) {
        return $this->getCompany()->canApplyOnDemand($organizartion, $user);
    }

    public function canApplyHierarchy (usuario $user, $currentClient) {
        return false;
    }


    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $fieldList = new FieldList();

        if (is_numeric($tab)) {
            switch ($tab) {
            case self::FIRST_STEP:
                $fieldList["usuario"]       = new FormField(array());
                $fieldList["nombre"]        = new FormField(array());
                $fieldList["apellidos"]     = new FormField(array());
                $fieldList["telefono"]      = new FormField(array());
                $fieldList["email"]         = new FormField(array());
                $fieldList["pass"]          = new FormField(array());
                break;
            case self::SECOND_STEP:
                $fieldList["uid_pais"]      = new FormField(array());
                $fieldList["uid_provincia"] = new FormField(array());
                $fieldList["uid_municipio"] = new FormField(array());
                $fieldList["direccion"]     = new FormField(array());
                $fieldList["cp"]            = new FormField(array());
                break;
                break;
            case self::THIRD_STEP:
                $fieldList["cif"]               = new FormField(array());
                $fieldList["nombre_empresa"]    = new FormField(array());
                $fieldList["nombre_comercial"]  = new FormField(array());
                $fieldList["kind"]              = new FormField(array());
                $fieldList["prevention_service"] = new FormField(array());
                $agrupadores = $objeto->getCompany()->obtenerAgrupadoresVisibles(new categoria(categoria::TYPE_TIPOEMPRESA));
                if (count($agrupadores) && is_traversable($agrupadores)) {
                    $fieldList["tipo_empresa"]  = new FormField(array());
                }
                break;
            default:
                return new ArrayObjectList(array());
                break;
            }

            return $fieldList;

        }

        switch ($modo) {
            case elemento::PUBLIFIELDS_MODE_QUERY:
                    $arrayCampos["cif"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                case elemento::PUBLIFIELDS_MODE_FOLDER:
                break;

            case elemento::PUBLIFIELDS_MODE_IMPORT:
                $fieldList["cif"]       = new FormField();
                $fieldList["nombre"]    = new FormField();
                $fieldList['email']     = new FormField();
                break;

            case elemento::PUBLIFIELDS_MODE_INIT:

                $fieldList["cif"]       = new FormField(array("tag" => "input", "type" => "text", "className" => 'needcheck'));
                $fieldList["nombre"]    = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                $fieldList['email']     = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "match" => elemento::getEmailRegExp() ));

                $country ="";
                if ($usuario instanceof usuario) {
                    if ($country = $usuario->getCountry()) $country = $country->getUID();
                }

                $fieldList["uid_pais"] = new FormField(array('tag' => 'select', 'value' => $country, 'data'=> pais::obtenerTodos()));

                break;

            case elemento::PUBLIFIELDS_MODE_MASSIVE:

                $fieldList["deadline_ok"] = new FormField(array("tag" => "input", "type" => "text", "className" => "datepicker", "date_format" => "%d/%m/%Y", "info" => true));

                break;

            default:
                $fieldList["cif"]                   = new FormField();
                $fieldList["token"]                 = new FormField(array());
                $fieldList["nombre"]                = new FormField(array());
                $fieldList['email']                 = new FormField(array());
                $fieldList['date']                  = new FormField(array());
                $fieldList["uid_pais"]              = new FormField(array());
                $fieldList["uid_empresa"]           = new FormField(array());
                $fieldList["uid_usuario"]           = new FormField(array());
                $fieldList["state"]                 = new FormField(array());
                $fieldList["end_date"]              = new FormField(array());
                $fieldList["uid_empresa_invitada"]  = new FormField(array());
                $fieldList["deadline_ok"]           = new FormField(array());
                $fieldList["client_configured"]     = new FormField(array());
                $fieldList["message"]               = new FormField(array());
                $fieldList["tipo"]                  = new FormField(array());
                $fieldList["app_version"]           = new FormField(array());
                $fieldList["expired_email_date"]    = new FormField(array());
                break;

        }
        return $fieldList;
    }

    public function getAlertCount(usuario $user) {

        $count          = 0;
        $statusNumber   = $this->getState();
        $sent           = $statusNumber == signinRequest::STATE_PENDING;
        $needClients    = $this->needConfigureClients($user) && !$sent;
        $needAssigns    = $this->needConfigureAssignment($user) && !$sent;

        if ($needClients) $count += 1;
        if ($needAssigns) $count += 1;
        return $count;
    }

    public function discardPendingNotifications()
    {
        if (false === $this->isInternal()) {
            return null;
        }

        $table             = TABLE_EMPRESA;
        $state             = empresasolicitud::ESTADO_CREADA;
        $stateToUpdate     = empresasolicitud::ESTADO_RECHAZADA;
        $type              = empresasolicitud::TYPE_CONTRATACION;
        $userUid           = $this->getInviterUser()->getUID();
        $companyUid        = $this->getCompany()->getUID();
        $companyInvitedUid = $this->getCompanyInvited()->getUID();

        $sql = "UPDATE {$table}_solicitud
        SET estado = {$stateToUpdate}
        WHERE estado = {$state}
        AND type = '{$type}'
        AND uid_modulo = 1
        AND uid_usuario = {$userUid}
        AND uid_empresa = {$companyInvitedUid}
        AND uid_empresa_origen = {$companyUid}";

        return (bool) $this->db->query($sql);
    }
}
