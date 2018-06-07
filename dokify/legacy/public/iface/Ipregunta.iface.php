<?php

	interface Ipregunta {
		
		const TABLE = TABLE_CUESTIONARIO_PREGUNTA;
		
		/**	
		  * Constructor. Crea un nuevo objeto
		  *
		  * @param id pregunta
		  * 
		  */		
		public function __construct($id);
	
		/**	
		  * Guarda en base de datos un nuevo elemento pregunta
		  *
		  * @param objeto cuestionario
		  * @param string pregunta
		  *
		  * @return object
		  * 
		  */	
		public static function create(cuestionario $cuestionario, $text);		  
		
		
		/**	
		  * Obtiene objeto respuesta según parametro
		  *
		  * @param objeto usuario
		  * @param objeto estado
		  *
		  * @return array id_respuestas
		  * 
		  */
		public function getRespuesta(usuario $usuario, cuestionario_cumplimentacion $estado);
		
		
		/**	
		  * Modifica el contenido de una pregunta
		  *
		  * @param string text
		  *
		  * @return bool
		  * 
		  */
		public function modificar(array $data);
		
		
		/**	
		  * Borrar permanentemente una pregunta (debería borrar sus dependencias, en este caso respuestas)
		  *
		  * @return bool
		  * 
		  */
		public function borrar();
		
		
		/**	
		  * obtiene un dato que le pasemos por parametro
		  *
		  * @param string dato
		  *
		  * @return mixed dato
		  * 
		  */
		// public function obtenerDato($dato);
		
	}
		
?>
