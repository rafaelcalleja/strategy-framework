<?php
	interface Icumplimentacion {
	
		const TABLE = TABLE_CUESTIONARIO_CUMPLIMENTACION;

		const VALIDACION_DEFAULT 	= 0;
		const VALIDACION_OK 		= 1;
		const VALIDACION_ERROR		= 2;
		
	    /**	
		  * Constructor. Crea un nuevo objeto
		  *
		  * @param id estado
		  * 
		  */		
		public function __construct($id);
	
	
		/**	
		  * Guarda en base de datos un nuevo elemento estado
		  *
		  * @param objeto usuario
		  * @param objeto cuestionario
		  * 
		  *
		  * @return object
		  * 
		  */	
		public static function crear(array $data);
		
		
		/**	
		  * nos devolverá el cuestionario al que pertenece
		  *
		  *
		  * @return object cuestionario
		  * 
		  */
		public function getCuestionario();
		
		/**	
		  * Modifica el estado de un cuestionario
		  *
		  * @param array data
		  *
		  * @return bool
		  * 
		  */
		public function validar($estado);

	
		/**	
		  * nos indicará el estado seguna la valoración de esta 'cumplimentación' de cuestionario
		  * LOS ESTADOS SE REFLEJA EN LAS CONSTANTES Iestado::VALIDACION_xxxxxx
		  *
		  * @param string dato
		  *
		  * @return int dato
		  * 
		  */
		public function getValidacion();

		

		/**	
		  * Borrar permanentemente un estado
		  *
		  * @return bool
		  * 
		  */
		public function borrar(); 
	}
?>
