<?php

	interface Iempresa {

		/**
		  * Devuelve el conjunto de partners de una empresa. Podemos filtrar por language.
		  *
		  * @param $language <> [es, en, fr, pt, cl]
		  *
		  *
		  * @return company
		  *
		  */
		public function getPartner($language);


		/**
		  * Devuelve las validaciones que se han hecho pero todavía no se han cobrado para la empresa
		  *
		  *
		  * @return arrayObjectList<validation>
		  *
		  */
		public function getPendingValidationInvoiced();


		/**
		  * Obtener impuestos del pago
		  *
		  * @param total | int
          * @param \DateTimeImmutable $date
		  *
		  * @return int
		  *
		  */
		public function getFeeAmount($total, \DateTimeImmutable $date);


		/**
		  * Obtener factuas pendientes por pagar. es un alias de la funcion invoice::getPending
		  *
		  *
		  *
		  * @return arrayObjectList<invoice>
		  *
		  */
		public function getPendingInvoices();


		/**
		  * Obtiene el total en euros de las facturas emitidas para esta empresa.
		  *
		  * @param $payed | bool
		  *
		  * @return int
		  *
		  */
		public function getTotalAmountInvoiced($payed = false);


		/**
		  * Envio de informes
		  * Envio de caducidad de pagos
		  * Crea invoices por empresa
		  *
		  *
		  * @param time | timestamp
		  * @param $force | bool
		  * @param $tipo
		  *
	  		*
		  * @return bool
		  *
		  */
		public static function cronCall($time, $force = false, $tipo = NULL);

		/**
		  * Devuelve el conjunto de empresas que no se les ha emitido factura todavía.
		  *
		  *
		  * @return arrayObjectList<empresa>
		  *
		  */
		public static function getCompaniesNotInvoiced();


		/**
		  * Devuelve el conjunto de validation status para una empresa pendiente de validar
		  *
		  *
		  * @return arrayObjectList<empresa>
		  *
		  */
		public function getPendingValidationsStatusNotInvoiced();


	}
?>