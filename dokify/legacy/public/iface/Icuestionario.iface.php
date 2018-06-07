<?php

	interface Icuestionario {

		const TABLE = TABLE_CUESTIONARIO;
		
		/**	
		  * Constructor. Crea un nuevo objeto
		  *
		  * @param id cuestionario
		  * 
		  */		
		public function __construct($id);
	
		/**	
		  * Guarda en base de datos un nuevo elemento cuestionario
		  *
		  * @param objeto usuario
		  * @param string nombre
		  * @param bool disponible
		  *
		  * @return object
		  * 
		  */	
		public static function crear(array $data);
		  	
		
		/**	
		  * Obtiene todos los objetos de esta clase, filtrando si se quere, por ejemplo por usuario
		  *
		  * @param array clave(colname) valor(dato) 
		  *
		  * @return array objeto
		  * 
		  */
		public static function getAll(array $filters = NULL); 

		
		/**	
		  * Obtiene id de las preguntas según el this->cuestionario_id.
		  *
		  * @return array de ids
		  * 
		  */
		public function getPreguntas();
		  
		  
		/**	
		  * Obtiene estado según los parámetros.
		  *
		  * @param objeto usuario
		  *
		  * @return array id_estado
		  * 
		  */
		public function getEstado(usuario $usuario);
			
			
		/**	
		  * Activar un cuestionario
		  *
		  * @return bool
		  * 
		  */
		public function activar();
		
		
		/**	
		  * Desactivar un cuestionario
		  *
		  * @return bool
		  * 
		  */
		public function desactivar();
		
		
		/**	
		  * modifica los siguientes datos del cuestionario:
		  *
		  * @param array parametros
		  *
		  * @return bool
		  *
		  * EJEMPLO
		  * $param = array();  $param['nombre']='Mi primer cuestionario',
		  * $cuestionario->modificar($param); tendría que pasarle un array clave:valor
		  * y luego, dentro de la funcion recupero los indices (claves) para formar la SQL con los valores del array
		  * 
		  */
		public function modificar(array $data);
		
		/**	
		  * Borrar permanentemente un cuestionario (debería borrar todas sus depencias, preguntas, respuestas de usuarios, estados ...)
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
