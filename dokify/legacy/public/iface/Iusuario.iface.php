<?php

interface Iusuario
{
    const PUBLIFIELDS_MODE_USERAGENT = 'useragent';

    /***
       *
       *
       *
       *
       *
       */
    public function setLatLng($location);


    /***
       *
       *
       *
       *
       *
       */
    public function getLatLng();

    public function getAddress();

    public function getEmail();

    // Retornara un objeto Iusuario si se ha podido logear y false si no
    public static function login($usuario, $password = false);

    // Enviar email de reseteo
    public function sendRestoreEmail();

    // Nos indica si es necesario que se cambie la password
    public function necesitaCambiarPassword();

    // Cambia el password del usuario
    public function cambiarPassword($password, $marcarParaRestaurar = false);

    // Indica se es la primera vez que se hace login
    public function checkFirstLogin();

    // Nombre "humano" para mostrar por pantalla
    public function getHumanName();

    // Return URL String | Imagen del usuario
    public function getImage();

    // Retorna un array formateado para usarse en los #main-menu
    public function obtenerElementosMenu();

    // Nos indica la empresa por defecto al que pertence
    public function getCompany();

    // Comprobar accesso
    public function accesoElemento(Ielemento $elemento, empresa $empresa = null, $papelera = false, $bucle = 0);

    public function esAdministrador();

    public function esStaff();

    public function esSATI();

    public function getHelpers($href = false);

    public function isViewFilterByGroups();

    public function isViewFilterByLabel();

    public function configValue($value);

    public function accesoModulo($idModulo, $config = null);

    public function accesoModificarElemento(Ielemento $elemento, $config = 0);

    public function getOptionsMultipleFor($modulo, $config = 0, Ielemento $parent = null);

    public function getOptionsFastFor($modulo, $config = 0, Ielemento $parent = null);

    public function getAvailableOptionsForModule($idModulo, $idAccion = false, $config = null, $referencia = null, $parent = null, $type = null);

    public function accesoAccionConcreta($idModulo, $accion, $config = null, $ref = null);

    // Devuelve un interger del tipo 0 o 1 por el momento
    public function opcionesDesplegable();

    public function buscarPerfilAcceso(Ielemento $objeto);

    public function getUnreadAlerts();

    public function obtenerBusquedas($filter = false);

    public function touch();

    public function esValidador();

    public function getLastPage();

    public function verEstadoConexion();

    public function obtenerCondicionDocumentos();

    public function obtenerCondicionDocumentosView($module);

    public function canView($item, $context, $extraData);

    public function getEmpresaSolicitudPendientes($type = false, $status = solicitud::ESTADO_CREADA);

    public function maxUploadSize($size = false, $reset = false);

    public function obtenerPerfil();

    public static function instanceFromCookieToken($username, $cookiepass);

    public function getCookieToken();

    public function setTimezoneOffset($offset);

    public function getTimezoneOffset();

    public function watchingThread($element, $requirements);

    public function unWatchThread($element, $requirements);

    public function wacthThread($element, $requirements);

    /**
      * Returns if a user can modify others users visibility
      *
      *
      * @return bool
      *
      */
    public function canModifyVisibilityOfUsers();

    /**
      * Returns the user that limit the current profile for the company given as a parameter
      *
      * @param empresa. The company we want to check if the profile has the visibility limited
      *
      * @return usuario
      *
      */
    public function getUserLimiter(empresa $company);

    /**
      * It creates or deletes the realtion beetween perfil and company. When the realtion is created it means the profile has not
      * visibility for the company.
      *
      * @param empresa. Company we want to remove visibility.
      * @param hidden. hidde = true means we create the relation. hidde = false we remove the relation.
      * @param usuario. When we create the relatión it is necessary to store which user remove the visibility for that company,.
      *
      * @return bool
      *
      */
    public function setCompanyWithHiddenDocuments(empresa $company, $hidden = true, usuario $usuario = null);


    /**
      * It allows the user profile to see all the client companies documents.
      *
      * @return bool
      *
      */
    public function setVisibilityForAllCompanies();


    /***
       * Tell us if the user can do the @option over the @item (@parent is aux)
       *
       *
       *
       *
       */
    public function canAccess($item, $option = \Dokify\AccessActions::VIEW, $parent = null);

    /**
     * If the user can receive comment notificacions
     * @return boolean
     */
    public function isActiveWatcher();
}
