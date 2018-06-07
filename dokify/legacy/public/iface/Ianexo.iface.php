<?php
	
	interface Ianexo {


		/**	
		  * Devuelve el conjunto de validaciones status asociados a un anexo
		  *
		  *
		  * @return arrayObjectList<validationStatus>
		  *
		  */
		public function getValidationStatus();


		/**	
		  * Devuelve true si el anexo se valida urgente, si no False.
		  *
		  *
		  * @return Bool
		  *
		  */
		public function isUrgent();


	}
?>