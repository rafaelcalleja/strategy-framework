<?php

	// Para que base no colisione sería necesario usar namespace, pero para la v1 lo dejamos asi

	// Clase base para todo lo relacionado con los cuestionarios
	abstract class base {

		// Identificador único de nuestro item
		protected $uid;

		// Constructor
		public function __construct($uid){
			$this->uid = $uid;
		}

		public function getUID(){
			return $this->uid;
		}

		public function borrar(){
			$class = get_class($this);

			$sql = "DELETE FROM ". $class::TABLE ." WHERE uid_{$class} = " . $this->getUID();
			return (bool) db::get($sql);
		}

		public function obtenerDato($dato){
			$class = get_class($this);

			// Solicitamos los campos del modelo
			$fields = $class::fields();

			if( !in_array($dato, array_keys($fields)) ) throw new Exception("El dato {$dato} no existe en la clase {$class}");
			
			$sql = "SELECT $dato FROM ". $class::TABLE." WHERE uid_{$class} = ". $this->getUID();
			return db::get($sql, 0, 0);
		}

		public function modificar(array $data){
			// Recuperamos la clase invocada
			$class = get_class($this);

			// Solicitamos los campos del modelo
			$fields = $class::fields();

			// Todos los datos que queramos actualizar
			$updates = array();

			// Recorremos cada campo para hacer las comprobaciones
			foreach($fields as $colname => $default){
				if( isset($data[$colname]) && $val = trim($data[$colname]) ){
					$values[$colname] = "`$colname` = '". self::scape($val) . "'";
				}
			}

			if( !count($values) ) throw new Exception("No se encuentran los indices necesarios para hacer el update de {$class}");

			$sql = "UPDATE ". $class::TABLE ." SET ". implode(",", $values )." WHERE uid_$class = ". $this->getUID();
			return (bool) db::get($sql);
		}

		public static function getAll(array $filters = NULL){
			// Recuperamos la clase invocada
			$class = get_called_class();

			$sql = "SELECT uid_{$class} FROM ". $class::TABLE ." WHERE 1";
			
			if( is_array($filters) ){
				$where = array();
				foreach($filters as $colname => $val){
					$where[] = "$colname = '". self::scape($val) ."'";
				}
				
				$sql .= " AND " . implode(" AND ", $where);
			}

			$items = db::get($sql, "*", 0, $class);
			return $items;
		}

		// Crear cualquier item BASE
		public static function crear(array $data){
			// Recuperamos la clase invocada
			$class = get_called_class();

			// Solicitamos los campos del modelo
			$fields = $class::fields();

			// Definimos las variables donde vamos a guardar los datos necesarios
			$values = array();			

			// Recorremos cada campo para hacer las comprobaciones
			foreach($fields as $colname => $default){
				if( isset($data[$colname]) && $val = trim($data[$colname]) ){
					$values[$colname] = "'". self::scape($val) . "'";				
		
				} else {
					// Si tenemos valores por defecto
					if( $default !== null ){
						$values[$colname] = "'". $default . "'";
					} else {
						throw new Exception("Es necesario definir el valor del campo {$colname} en la clase {$class}");
					}
				}
			}

			// Campos que vamos a insertr
			$fieldNames = array_keys($fields);

			// Get db objet
			$db = db::singleton();

			// Ejecutar el INSERT
			$sql = "INSERT INTO ". $class::TABLE ." (". implode(',', $fieldNames) .") VALUES (". implode(',', $values) .")";
			if( !$db->query($sql) ) throw new Exception($db->lastError());

			// Recuperar el ultimo ID para guardar la referencia al objeto
			$uid = $db->getLastId();
			return new $class($uid);
		}

		public static function scape($str){
			// Aqui usaremos una funcion que escape los caracteres
			return $str;
		}

	}
?>
