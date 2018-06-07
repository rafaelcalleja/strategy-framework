<?php
	interface IinvoiceItem {

		const TABLE_INVOICE_ITEM = "agd_data.invoice_item";
		/**	
		  * Instanciar objeto
		  * 
		  */
		public function __construct($param, $extra = false);


		/**	
		  * Devuelve el invoice asociado a este item
		  *
		  *
		  * @return invoice
		  *
		  */
		public function getInvoice();


		/**	
		  * Devuelve la descripción de un item
		  *
		  *
		  * @return string | nombre de documento vs nombre documento
		  *
		  */
		public function getDescription();


		/**	
		  * Devuelve el cargo por item
		  *
		  *
		  * @return int
		  *
		  */
		public function getAmount();


		/**	
		  * Devuelve el numero de items por invoiceItem
		  *
		  *
		  * @return int
		  *
		  */
		public function getNumItems();


		/**	
		  * Devuelve ena referencia a la validacion asociada a este item
		  *
		  *
		  * @return int
		  *
		  */
		public function getReferenceId();
	}


