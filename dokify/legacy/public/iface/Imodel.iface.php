<?php

	interface Imodel {

		/**	
		  * Recupera el id único del elemento
		  *
		  * 
		  *	@return interger
		  * 
		  */		
		public function getUID();

		/**	
		  * obtiene un dato que le pasemos por parametro
		  *
		  * @param string dato
		  *
		  * @return mixed dato
		  * 
		  */
		public function obtenerDato($dato);

		/**	
		  * Recupera los campos de este modelo
		  *
		  * @param id cuestionario
		  *	@return array
		  * 
		  */		
		public static function fields();

	}
?>
