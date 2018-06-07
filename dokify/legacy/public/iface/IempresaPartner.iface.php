<?php
	interface IempresaPartner {

		/**	
		  * Devuelve la empresa que tiene relación con el partner
		  *
		  *
		  * @return company
		  *
		  */
		public function getCompany();

		/**	
		  * Devuelve la empresa partner que ofrece el servicio
		  *
		  *
		  * @return company
		  *
		  */
		public function getPartner();


		/**	
		  * Devuelve el tipo de la asociació
		  *
		  *
		  * @return company
		  *
		  */
		public function getType();


		/**	
		  * Devuelve el idioma en el que se está dando el permiso
		  *
		  *
		  * @return language: ['es', 'en', 'pt', 'fr']
		  *
		  */
		public function getLanguage();


		/**	
		  * Devuelve quien va a pagar el servicio
		  *
		  *
		  * @return language: ['custom', 'both', 'general']
		  *
		  */
		public function getValidationDocs();


		/**	
		  * Devuelve quien va a pagar el servicio
		  *
		  *
		  * @return int i [-100> int <100]
		  *
		  */
		public function getVariation();


		/**	
		  * Devuelve quien va a pagar el servicio
		  *
		  *
		  * @return language: ['self', 'all']
		  *
		  */
		public function getPaymentMethod();

		/**	
		  * Devuelve quien va a pagar el servicio
		  *
		  * @param empresa - Empresa que solicita servicio partner
		  * @param partner - Empresa partner
		  * @param language - Lenguage en que hace la validación
		  * @param type - tipo de empresa partner
		  *
		  *
		  * @return ArrayObjectList(empresaPartner)
		  *
		  */
		public static function getEmpresasPartners(empresa $company = null, empresa $partner = null, $language = false, $type = false);

	}
?>