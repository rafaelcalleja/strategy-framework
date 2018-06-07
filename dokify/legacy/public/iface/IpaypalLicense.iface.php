<?php
interface IpaypalLicense
{
    const MAX_ITEMS_MICRO = 3; // Numero de items para set MICRO
    const MAX_ITEMS_PE = 20; // Max de items para ser pequeña empresa
    const MAX_ITEMS_E = 60; // Max de items para ser pequeña empresa

    const ITEM_PRICE = 5.32; // Precio variable por item (empleado y maquina)

    const REGISTER_PRICE_MICRO = 64.00;
    const REGISTER_PRICE_PE = 160.00;
    const REGISTER_PRICE_E = 266.00;
    const REGISTER_PRICE_GE = 373.00;

    const NEW_RANGE_DATE_APPLY = '07/20/2017 00:00:01';

    /**
      * Instanciar objeto
      *
      */
    public function __construct($param = false, $extra = false);


    /**
      * Devuelve la cantidad a pagar dependiendo del numero de items
      *
      * @param $num | int
      *
      *
      * @return int
      */
    public static function getPayPrice($num);


    /**
      * Devuelve el desglose de lo que se tiene que pagar por la licencia
      *
      * @param $elemento
      *
      *
      * @return array<price, discount, quantity, concept, tax, handling, total>
      */
    public function getPayData(Ielemento $elemento);


    /**
      * Resumen de toda la información del pago que se le mostrará a la empresa
      *
      * @param $elemento
      *
      *
      * @return array<text, data, quantity>
      */
    public function getSummary(Ielemento $elemento); //cambiar m


    /**
      * Inserta un nuevo concepto de pago. Crea una nueva entra en la tabla PAYPAL_CONCEPT.
      *
      * @param $usuario
      *
      *
      * @return array | bool - Array con la información del pago de la licencia si existo, false si error.
      */
    public function createPayConcept(usuario $usuario);

}
