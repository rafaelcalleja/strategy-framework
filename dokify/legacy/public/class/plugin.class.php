<?php
	//clase plugin
	class plugin {
		//instancia objeto de base de datos
		protected $db;
		//nombre de la tabla
		protected $nombre_tabla;
		//tabla donde se guardar la informacion
		protected $tabla;
		//la carpeta donde se alamacena
		protected $carpeta;
		//id unico del plugin dado por el creador
		protected $id_plugin;
		//nombre del plugin dado por el creador
		protected $nombre_plugin;
		//nombre del plugin dado por el creador
		protected $version_plugin;
		//plugin data
		protected $info;
		//archivo configuracion
		protected $configFile;
		//archivos del plugin
		protected $files;
		//plugin key
		protected $key;
		//uid elemento
		protected $uid;

		//constructor
		public function __construct( $folder ){
			$this->tabla = TABLE_PLUGINS;
			$tableNameExploded = new ArrayObject(explode(".", $this->tabla));
			$this->nombre_tabla = end($tableNameExploded);
			$this->db = db::singleton();

			//---- ES NUMERICO
			if( is_numeric($folder) ){
				$folder = $this->getFolderFromId( $folder );
			}
			//---- SI SOLO SE INDICA EL NOMBRE
			if( !is_dir($folder) ){
				$posibleDirectory = DIR_PLUGINS . $folder;
				if( is_dir($posibleDirectory) ){
					$folder = $posibleDirectory;
				}
			}
			$this->configFile = $folder."/config.xml";



			$this->files = array();
			foreach( glob($folder."/*.*") as $file){
				//buscamos la extension para diferenciar
				$extExploded = new ArrayObject(explode(".", $file));
				$ext = end($extExploded);

				//excepcion archivos js y css
				if( strpos($file, ".js") !== false ){ $ext = "js"; }
				if( strpos($file, ".css") !== false ){ $ext = "css"; }

				//si tiene extension
				if( $ext ){
					if( strpos(basename($file), "~") !== false ){ continue; }

					//if( $aux[1] == "~" ){ die("no copy"); }
					//guardamos la referencia
					$this->files[$ext][] = basename($file);
				}
			}

			$datos = $this->getConfig();
			$this->info = @$datos["plugin"];

			$this->id_plugin = db::scape( $this->info["aplicacion"]["id"] );
			$this->nombre_plugin = db::scape( $this->info["nombre"] );
			$this->version_plugin = db::scape( $this->info["aplicacion"]["version"] );

			$this->key = explode("@",$this->id_plugin); $this->key = reset($this->key);

			$this->carpeta = $folder;
		}

		public static function getAll($modo=false){
			$db = db::singleton();

			//----------- SQL PARA OBTENER LA INFORMACIÓN
			$sql = "SELECT uid_plugin FROM ". TABLE_PLUGINS;

			//----------- SI QUEREMOS FILTRAR
			if ($modo) {
				$sql .= " AND modo = '$modo' ";
			}

			return $coleccion = $db->query($sql, "*", 0, 'plugin');
		}

		public function getType(){
			return __CLASS__;
		}

		public function getFiles(){
			return $this->files;
		}

		public function getFolder(){
			return $this->carpeta;
		}

		public function getUserVisibleName(){
			return utf8_encode($this->nombre_plugin);
		}

		public function getAssignName(){
			return $this->getUserVisibleName();
		}

		public function getUID(){
			$sql = "SELECT uid_plugin FROM $this->tabla WHERE aplicacion_id = '$this->id_plugin'";
			return $this->db->query( $sql, 0, 0 );
		}

		/** RETORNAR ARRAY DE DATOS DEL PLUGIN */
		public function getData(){
			return utf8_multiple_encode($this->info);
		}

		public function getFolderFromId( $uidplugin ){
			$sql = "SELECT aplicacion_id FROM $this->tabla WHERE uid_plugin = $uidplugin";
			$datos = $this->db->query( $sql, true );
			if( !isset($datos[0]) ){ return false;}

			$auxiliar = explode("@",$datos[0]["aplicacion_id"]);
			return DIR_PLUGINS . reset($auxiliar);
		}

		public function remove(){
			if( $this->removeRecord() ){
				return true;
			} else {
				return false;
			}
		}

		public function getVersion(){
			return $this->version_plugin;
		}

		public function getInstalledVersion(){
			$sql = "SELECT aplicacion_version FROM $this->tabla WHERE aplicacion_id = '$this->id_plugin'";
			$datos = $this->db->query( $sql, true );
			if( !isset($datos[0]) ){ return 0; }

			$version = $datos[0]["aplicacion_version"];
			return $version;
		}

		public function exists(){
			$sql = "SELECT uid_plugin FROM $this->tabla WHERE aplicacion_id = '$this->id_plugin'";
			$resultset = $this->db->query( $sql );
			return ( $this->db->getNumRows( $resultset ) ) ? true : false;
		}

		public function removeRecord(){
			$sql = "DELETE FROM $this->tabla WHERE aplicacion_id = '$this->id_plugin'";
			return $this->db->query( $sql );
		}
		/*
		public function removeScript(){
			$file = DIR_JS . "plugin/$this->key.js";
			if( file_exists($file) ){
				return unlink( $file );
			}
			return true;
		}

		public function copyScript(){
			if( file_exists($this->scriptFile) ){
				if( !copy($this->scriptFile, DIR_JS . "plugin/$this->key.js") ){
					return false;
				}
			}
			return true;
		}


		public function removeStyle(){
			$file = DIR_CSS . "plugin/$this->key.css";
			if( file_exists($file) ){
				return unlink( $file );
			}
			return true;
		}

		public function copyStyle(){
			if( file_exists($this->styleFile) ){
				if( !copy($this->styleFile, DIR_CSS . "plugin/$this->key.css") ){
					return false;
				}
			}
			return true;
		}
		*/

		public function load(){
			$elemento = ( isset($this->info["cuerpo"]) && isset($this->info["cuerpo"]["elemento"]) ) ? serialize($this->info["cuerpo"]["elemento"]) : "";
			$selector = ( isset($this->info["cuerpo"]) && isset($this->info["cuerpo"]["selector"]) ) ? $this->info["cuerpo"]["selector"] : "";
			$modo = ( isset($this->info["cuerpo"]) && isset($this->info["cuerpo"]["modo"]) ) ? $this->info["cuerpo"]["modo"] : "";
			$prioridad = ( isset($this->info["cuerpo"]) && isset($this->info["cuerpo"]["prioridad"]) ) ? $this->info["cuerpo"]["prioridad"] : "";

			$sql = "INSERT INTO $this->tabla (
				". implode(",",self::getFields()) ."
			) VALUES (
				'".$this->info["nombre"]."',
				'".$this->info["descripcion"]."',
				'".$this->info["aplicacion"]["id"]."',
				'".$this->info["aplicacion"]["version"]."',
				'".$this->info["aplicacion"]["creador"]."',
				'".$elemento."',
				'".$selector."',
				'".$modo."',
				'".$prioridad."'
			)";
			if( $this->db->query( $sql ) ){
				/*
				if( isset($this->info["cuerpo"]) && isset($this->info["cuerpo"]["script"]) ){
					$this->saveScripts( $this->db->getLastId(), $this->info["cuerpo"]["script"] );
				}
				*/

				return true;
			} else {
				return $this->db->lastErrorString();
			}
		}

		public function update(){

			$elemento = ( isset($this->info["cuerpo"]) && isset($this->info["cuerpo"]["elemento"]) ) ? serialize($this->info["cuerpo"]["elemento"]) : "";
			$selector = ( isset($this->info["cuerpo"]) && isset($this->info["cuerpo"]["selector"]) ) ? $this->info["cuerpo"]["selector"] : "";
			$modo = ( isset($this->info["cuerpo"]["modo"]) ) ? $this->info["cuerpo"]["modo"] : "";
			$prioridad = ( isset($this->info["cuerpo"]) && isset($this->info["cuerpo"]["prioridad"]) ) ? $this->info["cuerpo"]["prioridad"] : "";
/*
			$sql = "DELETE FROM ".$this->tabla."_script WHERE uid_plugin IN (
				SELECT uid_plugin
				FROM $this->tabla
				WHERE aplicacion_id = '$this->id_plugin'
			)";
			$this->db->query( $sql );
*/
			$sql = "
			UPDATE $this->tabla SET
				nombre = '".$this->info["nombre"]."',
				descripcion = '".$this->info["descripcion"]."',
				aplicacion_version = '".$this->info["aplicacion"]["version"]."',
				elemento = '".$elemento."',
				selector = '".$selector."',
				modo = '".$modo."',
				prioridad = '$prioridad'
			WHERE aplicacion_id = '$this->id_plugin'";
			/*
			if( isset($this->info["cuerpo"]) && isset($this->info["cuerpo"]["script"]) ){
				$this->saveScripts( $this->getUID(), $this->info["cuerpo"]["script"]);
			}
			*/
			if( $this->db->query( $sql ) ){
				return true;
			} else {
				return $this->db->lastErrorString();
			}
		}

		public static function getFields(){
			return array("nombre","descripcion","aplicacion_id","aplicacion_version","aplicacion_creador","elemento","selector","modo","prioridad");
		}

		/*
		protected function saveScripts( $uidplugin, $elementoScript ){
			if( !is_array($elementoScript) ){
				$elementoScript = array($elementoScript);
			}

			if( is_array($elementoScript) ){
				$valores = array();
				foreach( $elementoScript as $script ){
					$valores[] = "( $uidplugin, '$script' )";
				}
				$sql = "INSERT INTO ".$this->tabla."_script ( uid_plugin, script ) VALUES ". implode(",",$valores);
			}

			return $this->db->query( $sql );
		}
		*/
		protected function getConfig(){
			include_once( DIR_FUNC . "xml2array.php");
			$xml = archivo::leer( $this->configFile );
			return xml2array( $xml );
		}
	}
?>
