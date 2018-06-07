<?php
	class asignacionguardada {
		private $path = NULL;
		private $empresa = NULL;

		public function __construct($path, $empresa){
			$this->path = $path;
			$this->empresa = $empresa;
		}

		public function getUserVisibleName(){
			list($tabla, $timestamp, $modulo, $extension) = explode(".", basename($this->path));

			return date('Y/m/d', $timestamp) . " de $modulo";
		}

		public function getUID(){
			return base64_encode( basename($this->path) );
		}

		public function load(){
			list($tabla, $timestamp, $modulo, $extension) = explode(".", basename($this->path));
	
			$temp = $tabla."-".$timestamp."-".uniqid();
			
			$reader = new dataReader($temp, $this->path);
			if( $reader->cargar() ){
				$db = db::singleton();
				$sql = "
					DELETE FROM ". TABLE_AGRUPADOR ."_elemento 
					WHERE uid_modulo = ". util::getModuleId($modulo) ."
					AND uid_elemento IN ( 
						SELECT uid_elemento FROM ". $reader->tabla ."
					)";
				if( !$db->query($sql) ){
					return $db->lastErrorString();
				}

				$sql = "INSERT INTO ". TABLE_AGRUPADOR ."_elemento SELECT * FROM ". $reader->tabla ."";
				if( !$db->query($sql) ){
					return $db->lastErrorString();
				}

				return true;
			} else {
				return $reader->error;
			}
		}

		public static function getFromParam(empresa $empresa, $param){
			$asignacion = null;
			if( $param instanceof asignacionguardada ){
				$asignacion = $param;
			} elseif( is_readable($param) ){
				$asignacion = new asignacionguardada($param, $empresa);
			} elseif( is_string($param) ){
				$path =  DIR_FILES . "/empresa/uid_". $empresa->getUID() ."/assign/".$param;
				if( is_readable($path) ){
					$asignacion = new asignacionguardada($path, $empresa);
			 	} else {
					$path =  DIR_FILES . "/empresa/uid_". $empresa->getUID() ."/assign/". base64_decode($param);
					if( is_readable($path) ){
						$asignacion = new asignacionguardada($path, $empresa);
					}
				}
			}

			return $asignacion;
		}
	}
?>
