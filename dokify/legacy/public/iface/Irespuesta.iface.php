<?php
	interface Irespuesta {
		
		const TABLE =TABLE_CUESTIONARIO_RESPUESTA;
			
			
		/**	
		  * Constructor. Crea un nuevo objeto
		  *
		  * @param id respuesta
		  * 
		  */		
		public function __construct($id);
		
		
		/**	
		  * Guarda en base de datos un nuevo elemento respuesta
		  *
		  * @param objeto cuestionario_estado
		  * @param objeto pregunta
		  * @param objeto usuario (que contesta al cuestionario)
		  * @param string respuesta
		  * @param objeto estado
		  *
		  * @return object
		  * 
		  */	
		public static function create(cuestionario_cumplimentacion $cuestionario, cuestionario_pregunta $pregunta, usuario $usuario, $text); 
		
		
		/**	
		  * Modifica datos de una respuesta, puede ser el texto respondido por el usuario, la valoracion, validacion...
		  *
		  * @param array parametros
		  *
		  * @return bool
		  * 
		  */
		public function modificar(array $data);
	
		
		/**	
		  * Borrar permanentemente una respuesta
		  *
		  * @return bool
		  * 
		  */
		public function borrar();
		
		/**	
		  * Permite modificar la validacion a una respuesta
		  *
		  * @param int valor
		  *
		  * @return bool
		  * 
		  */
		public function validar($valor);
		
		
		/**	
		  * obtiene la respuesta en texto
		  *
		  *
		  * @return string dato
		  * 
		  */
		public function getText();
	}
?>
