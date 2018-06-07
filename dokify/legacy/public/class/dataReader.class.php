<?php
	/** A PARTIR DE UN CSV O UN EXCELL, MANEJAR DATOS */
	class dataReader{
		public $tabla;
		public $campos;
		public $archivo;
		public $separador;
		public $encapsulador;
		public $engine;
		public $titulos;
		public $filasCSV;
		public $filasCargadas;
		public $defaultField;
		public $colnames;
		protected $db;
		protected $conexion;

		/** INTANCIAR EL OBJETO
				@param1: nombre de la tabla mysql con la que trabajar
				@param2: archivo a leer
				@param3: conexion mysql
		*/
		public function __construct($nombreTabla, $archivo, $forcedtype=false, $colnames = false){
			$this->db = new db(DB_TMP);
			$this->conexion = $this->db->conexion;
			$this->archivo = $archivo;
			$this->nombre_tabla = $nombreTabla;
			$this->tabla = DB_TMP .".`". $nombreTabla."`";
			$this->separador = ";";
			$this->error = false;
			$this->encapsulador = "\"";
			$this->engine = "MYISAM";
			$this->modo = ( $forcedtype ) ? strtolower($forcedtype) : strtolower( substr( strrchr($archivo,"."),1,5 ) );
			$this->titulos = 1;
			$this->keyField = "VARCHAR( 256 ) NOT NULL";
			$this->defaultField = "VARCHAR( 400 ) NOT NULL";
			$this->colnames = $colnames;

			if( !$data = archivo::tmp($this->archivo) ){
				$this->error = "Error al leer el csv";
				return false;
			}

			$path = "/tmp/". str_replace("`","",$this->tabla);
			if( !archivo::escribir($path, $data) ){
				$this->error = "Error al copiar el archivo";
				return false;
			}

			if( $this->modo != "csv" ){
				$this->archivo = $this->convertirCSV($path);
			} else {
				$this->archivo = $path;
			}

		}
		


		protected function loadBuffer(){
			$buffer = array();
			$data = trim(archivo::leer($this->archivo));
			$lines = explode("\n",$data);

			foreach($lines as $i => $line ){
				// Saltamos la cabecera de los titulos
				if( $i >= $this->titulos ){
					$cols = explode( $this->separador, $line );

					array_walk($cols, function(&$string, $key, $encapsulador){
						$string = str_replace($encapsulador,"",$string);
					}, $this->encapsulador);
	
					$buffer[] = $cols;
				}
			}
			return $buffer;
		}

		protected function temporalDiff(){
			$buffer = $this->loadBuffer();

			if( $this->colnames ){
				$arr = ( $this->colnames instanceof ArrayObject ) ? $this->colnames->getArrayCopy() : $this->colnames;
				$fields = implode(", ", $arr);
			} else {
				$fields = implode(", ", $this->campos);
			}

			$sql = "SELECT $fields FROM ".$this->tabla." ;";

			if( !$resultset = $this->db->query($sql) ){
				$this->verError();
			}

			$diffs = $rows = array();
			$i = 0;
			while($row = db::fetch_row($resultset) ){
				//$rows[] = $row;
				$hashRowTable = implode("",$row);
				$hasRowFile = implode("", $buffer[$i]);
				if( $hasRowFile != $hashRowTable ){
					$errorFila = $i + 1 + $this->titulos;

					$values = array();
					foreach( $buffer[$i] as $col ){
						$values[] = "'". $col ."'";
					}

					// Vemos por que no se inserta...
					$sql = "INSERT INTO ". $this->tabla ." ( $fields ) VALUES ( ". implode(",", $values) .")";
					$result = $this->db->query($sql);
					$error = $this->convertErrorString( $this->db->lastError(), $this->db->lastErrorNo() );

					return array(
						"fila" => $errorFila,
						"datos" => $buffer[$i],
						"error" => $error
					);
				}
				$i++;
				unset($row);
			}

			if( isset($buffer[$i]) ){
				$error = array(
					"fila" => $i + $this->titulos,
					"datos" => $buffer[$i],
				);

				if( is_array($buffer[$i]) ){
					if( count( end($rows) ) != count($buffer[$i]) ){
						$error["error"] = "La última fila esta vacia.";
					} else {
						throw new Exception("Parece que hay un fallo no contemplado en la última fila #1");
					}					
				} else {
					throw new Exception("Parece que hay un fallo no contemplado en la última fila #2");
				}

				return $error;
			}

			return false;
		}

		protected function convertirCSV($path){
			switch( $this->modo ){
				case "xls":
					require_once('excel/reader.php');
					$data = new Spreadsheet_Excel_Reader();
					$data->setOutputEncoding('CP1251');
					$data->read($path);
					$sheet = $data->sheets[0];
					$newfilename = $path . ".csv";

					$csvFile = "";

					$numRows = $sheet['numRows'];
					
					for ($i = 1; $i <= $numRows; $i++) {
						$cols = array();

						$numCols = $sheet['numCols'];
						
						for ($j = 1; $j <= $numCols; $j++){
							if( isset($sheet['cells'][$i]) && isset($sheet['cells'][$i][$j]) ){
								
								$value = trim($sheet['cells'][$i][$j]);
		
								if (isset($sheet['cellsInfo'][$i][$j]) 
								&& $sheet['cellsInfo'][$i][$j]['type'] == 'date' 
								&& isset($sheet['cellsInfo'][$i][$j]['raw']) ) {
									// WTF restando un día a la fecha. por algún motivo la fecha almacenada en timestamp es 1 día despues de lo que pone en el xls...
									$value = date('d/m/Y',$sheet['cellsInfo'][$i][$j]['raw']- 60*60*24);
								}
								/*

								$encoding = mb_detect_encoding($value, mb_detect_order(), true);
								if( $encoding != "UTF-8" ){
									//echo "La codificacion es $encoding\n\n";
									$data = utf8_encode($data);
								}
								*/

								$cols[] = $this->encapsulador. $value .$this->encapsulador;
							} else {
								$this->error = "No se encuentran la celda $j de la linea $i: el archivo esta mal formado";
							}
						}
						
						$csvFile .= implode($this->separador, $cols);
						$csvFile .= "\n";
					}
					

					if( file_exists($newfilename) ){
						@unlink( $newfilename);
					}

					// error_log($csvFile);
					if( archivo::escribir($newfilename, $csvFile) ){
						return $newfilename;
					} else {
						$this->error = "No se puede escribir al archivo ($newfilename)";
						return false;
					}

					/*
					if( !$gestor = fopen( "/tmp/".$this->tabla, 'a') ){
						$this->error = "No se puede abrir el archivo ($nombre_archivo)";
						return false;
					}

					if( fwrite($gestor, $csvFile) === FALSE){
						$this->error = "No se puede escribir al archivo ($nombre_archivo)";
						return false;
					}

			    	fclose($gestor);
					*/
					return true;

				break;
			}
			$this->error = "No se reconoce el tipo de archivo";
		}




		/* 
		 * NUMERO DE LINEAS QUE SE IGNORARAN
		 */
		public function numeroTitulos( $numero ){
			$this->titulos = $numero;
		}



		/** PROCESO DE CARGA DEL CSV 
			@param1 = true o false si borra o no la tabla temporal al cargar de nuevo
			@param2 = false o nombre del campo que actuará de clave unica
		*/
		public function cargar($borrar=true, $unique=false){
			if($borrar){ $this->borrar(); }

			if( !$this->campos ){ 
				$this->leerCampos(); 
			}
			if( $this->crearTabla($unique) ){
				return $this->upload();
			}
		}	

	
		/** ENVIA LOS DATOS A LA TABLA REAL */
		public function mover($tabla, $campos){
			if( strpos($tabla, ".") === false ){
				$nombreTabla = $tabla;
				$nombreBD = DB_TMP;
			} else {
				$nombreTabla = end( new ArrayObject( explode(".",$tabla) ) );
				$nombreBD = reset( new ArrayObject( explode(".",$tabla) ) );
			}
			if( !$this->campos ){ $this->campos = $this->leerCampos(); }

			//el ultimo id actual
			//$currentMAXID = $this->db->query("SELECT max(uid_$nombreTabla) max FROM $tabla", 0, 0);
			$currentMAXID = $this->db->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$nombreBD' AND TABLE_NAME = '$nombreTabla'", 0, 0);

			$sql = "INSERT IGNORE INTO $tabla (". implode(",",$campos) .") SELECT ". implode(",",$this->campos) ." FROM $this->tabla";

			if( $this->db->query($sql) ){
				$sql = "SELECT uid_$nombreTabla FROM $tabla WHERE uid_$nombreTabla >= $currentMAXID";

				$nuevosRegistros = $this->db->query($sql, "*", 0);
				$affected = $this->db->getAffectedRows();

				if( is_array($nuevosRegistros) && count($nuevosRegistros) ){
					//TODAS LAS LINEAS SE HAN AÑADIDO CORRECTAMENTE
					if( $affected == count($nuevosRegistros) ){
						return $nuevosRegistros;
					} else {
						return "Algunos registros no se han movido correctamente";
						//algunas lineas no se han guardado
					}
				}
			} else {
				return "No se pueden mover los registros para la carga temporal" . $this->db->lastErrorString();
			}
		}
	



		/** BORRAR LA TABLA  */
		public function borrar(){
			$sql = "DROP TABLE IF EXISTS ".$this->tabla;
			if( !$this->db->query($sql) ){
				$this->verError();
				return false;
			}

			return true;
		}



		/** SI ALGUN SQL DA ERROR */
		protected function verError(){
			$this->error = "Error de mysql: ". $this->db->lastError();
			//dump("Viendo error: ". $this->error);
		}

		protected function convertErrorString($string, $num){
			if( $string ){
				switch( $num ){
					case 1062:
						return "Los datos estan duplicados";
					break;
				}
				return $string;
			}
			return "";
		}
	




		/** COMPROBAR SI MYSQL DEVOLVIO ALGUN ERROR */
		protected function comprobarCarga(){
			$info = $this->db->info();
			preg_match("/Records: ([0-9]*)/", $info, $records);$records = $records[1];
			preg_match("/Warnings: ([0-9]*)/", $info, $alertas); $alertas = $alertas[1];
			preg_match("/Skipped: ([0-9]*)/", $info, $saltos); $saltos = $saltos[1];

			if( $this->filasCargadas != $this->filasCSV ){
				//if( $records == $this->filasCSV && $saltos == 0 ){
					$diff = $this->temporalDiff();
					$this->error = "
						El numero de filas del fichero (".$this->filasCSV.") no corresponde con el numero de filas cargadas en la tabla temporal (".$this->filasCargadas.")
						<hr />
						La primera fila con errores es la fila ". $diff["fila"] .". ". $diff["error"] ."
						<hr />
						Warnings: {$alertas} - Saltadas: {$saltos} - Registros {$records}
					";
				//} else $this->error = "Warnings: {$alertas} - Saltadas: {$saltos} - Registros {$records}";
				return false;
			}



			if( $alertas || $saltos ){
				$rs = $this->db->query("SHOW WARNINGS");
				while( $error = db::fetch_array( $rs ) ){
					$this->error = $error["Message"];
					return false;
				}
			} else {
				return true;
			}
		}





		/** CARGAR EL ARCHIVO EN LA BBDD */
		protected function upload(){
			
			$sql = "
			LOAD DATA LOCAL INFILE '".$this->archivo."' 
			IGNORE
			INTO TABLE ".$this->tabla." 
			CHARACTER SET 'latin1'
			FIELDS 
				TERMINATED BY '".$this->separador."' 
				ENCLOSED BY '".$this->encapsulador."'
				IGNORE ".$this->titulos." LINES
			;";

			if( !$loadCSV = $this->db->query($sql) ){
				$this->verError();
				$this->error = $this->db->lastError();
				return false;
			}

			$this->filasCargadas = $this->db->getAffectedRows();

			return $this->comprobarCarga();
		}





		/** LEERA LOS CAMPOS DEL CSV DIRECTAMENTE */
		public function leerCampos(){
			if( !is_readable($this->archivo) ){
				throw new Exception("No se puede leer el fichero");
			}

			$contenido = archivo::leer($this->archivo);
			//$puntero = fopen($this->archivo, 'r');
			//$contenido = fread($puntero, filesize($this->archivo));
			//fclose($puntero);
			
			$contenidoCSV = trim(str_replace( $this->encapsulador, "", $contenido));
			$lineas = explode("\n", $contenidoCSV);
			$linea = $lineas[0];
	
			$this->filasCSV = count($lineas) - $this->titulos;
			$this->campos = explode($this->separador, $linea);
			$this->campos = array_map("trim", $this->campos);

			return $this->campos;
		}


		/* Numero de lineas del csv */
		public function contarLineas(){
			$contenido = archivo::leer($this->archivo);
			$lineas = explode("\n", $contenido);
			return count($lineas)-1-$this->titulos;
		}

		public function getAsArray(){
			$contenido = archivo::leer($this->archivo);
			$lineas = explode("\n", $contenido);

			$array = array();
			foreach($lineas as $i => $ln){
				if( trim($ln) && $i){
					$array[] = explode($this->separador, str_replace($this->encapsulador, "", trim($ln) ));
				}	
			}
			return $array;
		}


		/**	CREARA TABLA MYSQL */
		protected function crearTabla($unique=false, $campos=false){
			if( is_array($campos) ){ $this->campos = $campos; }

			$sql  = "CREATE TABLE IF NOT EXISTS ".$this->tabla." (";
			if( $this->colnames ){
				foreach( $this->colnames as $campo ){
					if( $unique && strtolower($unique) == strtolower($campo) ){
						$sqlUNIQUE = " , UNIQUE KEY `UNIQUE` (`$campo`) ";
						$sqlCampos[] = "`$campo` {$this->keyField}";
					} else {
						$sqlCampos[] = "`$campo` {$this->defaultField}";
					}
				}
			} else {
				foreach( $this->campos as $campo ){
					if( $unique && strtolower($unique) == strtolower($campo) ){
						$sqlUNIQUE = " , UNIQUE KEY `UNIQUE` (`$campo`) ";
						$sqlCampos[] = "`$campo` {$this->keyField}";
					} else {
						$sqlCampos[] = "`$campo` {$this->defaultField}";
					}
				}
			}

			$sql .= implode(",", $sqlCampos);
			if( isset($sqlUNIQUE) ){ $sql .= $sqlUNIQUE; }
			$sql .= ") ENGINE = ".$this->engine." ;"; 

			if( !$this->db->query($sql) ){
				$this->verError();
				return false;
			}

			return true;
		}



	}
?>
