<?php

interface Ipaypal
{

    const BUSINESS_DEV = "jandre_1300436749_biz@afianza.net";
    const BUSINESS = "jmedina@dokify.net";

    /**
      * Instanciar objeto
      *
      */
    public function __construct($param = false, $extra = false);


    /**
      *
      *
      * @return string - Cadena que representa lo que se está pagando en la transacción en texto para humanos
      */
    public function getConceptName();


    /**
      * Verificar IPN
      *
      * @return bool | string - Devolverá true si esta verificada o string si ha habido error
      */
    public static function isIPNVerified();


    /**
      * Registra la transacción en la base de datos
      *
      * @param $mode
      *
      * @return int | bool - id de la transacción que se ha guardado si OK. False si error.
      */
    public function saveTransaction($mode = "ipn", $post = array());


    /**
      * Delete a transaction by the txnid
      *
      * @param $txnId to delete
      *
      *
      * @return bool.
      */
    public static function deleteTransactions($txnParent);


    /**
      * Actualizar datos de la transacción.
      *
      * @param $id
      * @param $fields
      *
      *
      * @return bool.
      */
    public static function updateTransaction($id, $fields);


    /**
      * Comprueba si un identificador de transacción es válido.
      *
      * @param $id
      *
      * @return bool.
      */
    public static function checkTransactionId($id);


    /**
      * Devuelve un objeto con toda la información de la transacción.
      *
      * @param $id
      *
      * @return Objeto<mc_gross, payment_status, payment_date, item_name,
      * items, date, tax, mc_fee, total, price, discount, uid_empresa>
      */
    public static function getTransactionData($id);


    /**
      * Obtener el id de la transacción.
      *
      * @param $item IElemento
      *
      * @return int | bool - id de la transacción si existe. False si no existe.
      *
      */
    public static function getTransactionId(Ielemento $item);


    /**
      * Devuelve la fecha de una transacción.
      *
      * @param $txn
      *
      * @return timestamp | bool - timestamp si existe. False si no existe.
      *
      */
    public static function getTransactionTimestamp($txn);


    /**
      * Devuelve el usuario que realizó la transacción.
      *
      * @param $id
      *
      * @return usuario | bool - usuario si existe transacción. False si no existe.
      *
      */
    public static function getUserFromTransaction($id);


    /**
      * Construye un array con el post, util para comprobar el IPN
      *
      *
      * @return array
      *
      */
    public static function getVerificationURL();


    /**
      * Contruye la url de pago de paypal
      *
      * @param $user [because sometimes we need the user data to pre-fill
      * the paypal forms or to creat concept on the fly]
      * @param $company
      * @param $dev
      * @param $formItems campos necesarios para la url de paypal.
      * @param $temporaryAccess para si es un pago temporal.
      *
      * @return url de enlace paypal.
      *
      */
    public function urlToPaypal(
        usuario $usuario = null,
        empresa $company = null,
        $dev = false,
        $formItems = null,
        $temporaryAccess = false
    );
}
