<?php
	interface Ivalidation {

		/**	
		  * Se creará la validación cuando se valide o rechace un documento. Esta función es la encargada de crear un validationStatus por 
		  * elemento del array y calcular precios y guardarlos.
		  *
		  *
		  * @return bool
		  *
		  */
		public static function create(empresa $company, usuario $usuario,$estado = validationSatus::VALIDATED, $attachments);


		/**	
		  * Devuelve el numero de anexos de una validación
		  *
		  *
		  * @return int
		  *
		  */
		public function getNumAttachments();


		/**	
		  * Devuelve la empresa validadora
		  *
		  *
		  * @return empresa | null si no partner
		  *
		  */
		public function getPartner();


		/**	
		  * Devuelve la empresa validadora
		  *
		  *
		  * @return empresa
		  *
		  */
		public function getCompany();


		/**	
		  * Devuelve el usuario que realizó la validación
		  *
		  *
		  * @return usuario
		  *
		  */
		public function getUser();


		/**	
		  * Devuelve la fecha de la validación
		  *
		  *
		  * @return timestamp
		  *
		  */
		public function getDate();

		/**	
		  * Obtener el coste total de la validación
		  *
		  *
		  * @return int
		  *
		  */
		public function getAmoutValidation();

		/**	
		  * Obtenemos el conjunto de validacion_status asociados a la validación
		  *
		  *
		  * @return arrayObjectList<validationStatus>
		  *
		  */
		public function getValidationAttachmentsStatus();


		/**	
		  * Obtener las validaciones que todavía no se han cobrado
		  *
		  * @param empresa
		  *
		  *
		  * @return arrayObjectList<validation>
		  *
		  */
		public static function getPendingValidationInvoiced(empresa $empresa = null);

	}