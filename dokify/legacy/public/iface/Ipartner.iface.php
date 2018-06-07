<?php
	
	interface Ipartner {


		/**	
		  * Devuelve el conjunto de validaciones de una empresa partner
		  *
		  * @param $type tipo de partner
		  *
		  *
		  * @return int
		  *
		  */
		public function isPartner($type);


		/**
		  * Devuelve el conjunto de validaciones de una empresa partner
		  *
		  *
		  * @return int
		  *
		  */
		public function getValidations();
		

		/**	
		  * Obtiene el precio por validación de un partner. isUrgent a true devolverá el precio por validación urgente. 
		  *
		  * @param isUrgent | bool
		  *
		  *
		  * @return int
		  *
		  */
		public function getValidationPrice($isUrgent = false);


		/**	
		  * Devuelve el conjunto de partners. Los parametros son para filtrar por los partner que cumplen unos requisitos.
		  *
		  * @param $language <> [es, en, fr, pt, cl]
		  *
		  *
		  * @return arrayObjectList<company>
		  *
		  */
		public static function getAllPartners($language = null);

		/**	
		  * Devuelve el tiempo medio de validación en segundos
		  *
		  * @param isUrgent | bool
		  *
		  * @return time seconds or false 
		  *
		  */
		public function getAVGTimeValidate ($isUrgent = false);

	}
?>