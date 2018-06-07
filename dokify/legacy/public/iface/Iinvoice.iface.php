<?php
	interface Iinvoice {

		/**	
		  * Obtiene la fecha en la que se emitio la factura
		  *
		  *
		  * @return timestamp
		  *
		  */
		public function getInvoiceDate();


		/**	
		  * Obtiene el precio a pagar sin iva  ni tasas (si aplicado el descuento)
		  *
		  *
		  * @return float
		  *
		  */
		public function getPrice();

		/**	
		  * Obtiene la fecha del ultimo envio de la factura
		  *
		  *
		  * @return timestamp
		  *
		  */
		public function getSentDate();


		/**	
		  * Devuelve el id de la venta de la factura de endeve
		  *
		  *
		  * @return int
		  *
		  */
		public function getSaleId();


		/**	
		  * Empresa a la que emite la factura
		  *
		  *
		  * @return usuario
		  *
		  */
		public function getCompany();


		/**	
		  * Devuelve el identificador único del pago generado por paypal
		  *
		  *
		  * @return string
		  *
		  */
		public function getCustom();


		/**	
		  * Obtener precio total de la factura. Guardamos el amount en la tabla invoices.
		  *
		  *
		  * @return int
		  *
		  */
		public function getTotalAmount();


		/**	
		  * Envia correos electronicos sobre la factura a la empresa que tiene que pagar.
		  *		Dependiendo del campo 'sentDate' enviará diferentes email
		  * 		- Email con la factura - Si sentDate == NULL
		  *			- Email con aviso de cierre de aplicacion - Pasada una semana de sentDate.
		  *			- Email de cierre de aplicación - Pasado diez dias desde el email de aviso de ecierre
		  *
		  * @param action - Tipo de email que se va a enviar
		  * @param items - Items de la factura.
		  * @param force - Si forzamos desde develop.
		  *
		  * @return bool
		  *
		  */
		public function sendEmailNotification($action, empresa $company, $items = false, $force = false);


		/**	
		  * Comprobar si la factura esta pagada
		  * Cruzaremos con la tabla de transacciones de paypal (custom)
		  *
		  *
		  * @return bool
		  *
		  */
		public function isPayed();


		/**	
		  * Devuelve conjunto de invoice_items asociados a una factura
		  *
		  *
		  *
		  * @return arrayObjectList<invoiceItem>
		  *
		  */
		public function getItems();


		/**	
		  * Función auxiliar para sacr los items de un invoice en formato de quaderno.
		  *
		  * @return array(itmes("description", "unit_price", "discount", "quantity", "iva", "subtotal"))
		  *
		  */
		public function getInvoicedItemsFormated();


		/** Obtener factuas pendientes por pagar. Si no recibe parametros devuelve todoas las pendientes
		  *
		  * @param empresa
		  *
		  *
		  * @return arrayObjectList<invoice>
		  *
		  */
		public static function getPending(empresa $company = null);


		/**	
		  * Obtiene el total en euros de las facturas emitidas. Se puede filtrar por una empresa para 
		  * saber cuanto se le ha factuaro. Aparte con el paramtro $payed podemos saber el total pagado.
		  *
		  * @param empesa
		  * @param $payed | bool
		  *
		  * @return int
		  *
		  */
		public static function getTotalAmountInvoiced(empresa $company = null, $payed = false); /*pending confirm*/


		/**	
		  * Obtiene la colección de empresas que no han pagado
		  *
		  * @return arrayObjectList<empresas>
		  *
		  */
		public static function getDefaulterCompanies();  /*pending confirm*/

		/**	
		  * Crea una clave única custom por invoice
		  *
	  	  *
		  * @return string
		  *
		  */
		public static function createCustomKey();


		/**	
		  * Devuelve que en que estado esta la factura.
		  *
	  	  *
		  * @return status = PAYMENT_INFO OR REMINDER_PAYMENT OR CLOSE_NOTIFICATION.
		  *
		  */
		public function getActionPendingInvoice();


		/**	
		  * Envia emails de notificaciones de pagos.
		  *
		  * @param time
		  * @param $force | bool
		  * @param $tipo
		  *	
	  	  *
		  * @return string
		  *
		  */
		public static function cronCall($time, $force = false, $tipo = NULL);

	}