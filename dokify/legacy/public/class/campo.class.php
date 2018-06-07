<?php

	class campo extends elemento {

		public function __construct( $param , $saveOnSession = true/*uid or data*/ ){
			$this->tipo = "campo";
			$this->tabla = TABLE_CAMPO;

			$this->instance( $param, $saveOnSession );
		}


		public function obtenerModuloDestino($string=false){
			$sql = "SELECT uid_modulo FROM $this->tabla WHERE uid_$this->nombre_tabla = $this->uid";
			$uidModulo = $this->db->query($sql, 0, 0);
			if( $string ){
				return util::getModuleName($uidModulo);
			}

			return $uidModulo;
		}

		/** RETORNAR EL NOMBRE DEL ELEMENTO */
		public function getUserVisibleName(){
			$datos = $this->getInfo();
			$buscar = array("_");
			$reemplazar = array(" ");
			$nombre = str_replace( $buscar, $reemplazar, $datos["nombre"]);

			return $nombre;
		}

		public function getAssignName($fn, $parent = NULL){
			return $this->getUserVisibleName() . " - " . $this->obtenerModuloDestino(true);
		}

		/** TRUE/FALSE SI EL VALOR ESTA ESTABLECIDO (EL REGISTRO EXISTE) O NO */
		public function getValue($elemento){
			$modulo = $elemento->getModuleName();
			$name = $this->getFormName();
			$sql = "SELECT $name FROM ". $this->tabla ."_$modulo WHERE uid_$modulo = ". $elemento->getUID();
			$exists = $this->db->query( $sql, 0, 0 );
			return trim($exists);
		}

		public function registroExiste($elemento){
			$modulo = $elemento->getModuleName();
			$name = $this->getFormName();
			$sql = "SELECT uid_$modulo FROM ". $this->tabla ."_$modulo WHERE uid_$modulo = ". $elemento->getUID();
			$exists = $this->db->query( $sql, 0, 0 );
			return ( trim($exists) ) ? true : false;
		}

		/** ESTABLECER EL VALOR DEL CAMPO DINAMICO PARA UN ELEMENTO */		
		public function setValue($elemento, $valor){
			$modulo = $elemento->getModuleName();
			$name = $this->getFormName();

			if( $this->registroExiste($elemento) ){
				if( $this->getValue($elemento) == $valor ){
					return null;
				}
				$sql = "UPDATE ". TABLE_CAMPO ."_$modulo 
					SET $name = '$valor'
					WHERE uid_$modulo = ". $elemento->getUID();

			} else {

				$sql = "INSERT INTO ". TABLE_CAMPO ."_$modulo 
					( uid_$modulo, $name ) VALUES 
					( ". $elemento->getUID() .", '$valor' )";

			}

			return ( $this->db->query($sql) ) ? true : false;
		}

		/** DEVOLVER EL NOMBRE PARA EL FORMULARIO */

		public function getFormName(){
			$datos = $this->getInfo();
			return strtolower($datos["nombre"]);
		}

		public function getTag(){
			$datos = $this->getInfo();
			$tags = self::obtenerTags();
			$tag = $tags[$datos["tag"]];
			return $tag;
		}

		public function getFieldType(){
			$datos = $this->getInfo();
			$tipos = self::obtenerTipos();
			$tipo = $tipos[$datos["tipo"]];
			return $tipo;
		}

		public function getData(){
			$datos = $this->getInfo();
			if( strlen($datos["datos"]) ){
				$datos = explode(";", $datos["datos"]);
				return $datos;
			} else {
				return null;
			}
		}

		static public function publicFields(){
			$modo = func_get_args(); $modo = ( isset($modo[0]) ) ? $modo[0] : null;
			
			//BUSCAMOS LOS DATOS DE LOS MODULOS
			$database = db::singleton();
			$arrayModulos = array();
			$arrayCampos = new FieldList();

			$datos = $database->query("SELECT uid_modulo, nombre FROM ". TABLE_MODULOS ." WHERE asignacion", true);
			foreach($datos as $dato){
				$arrayModulos[ $dato["uid_modulo"] ] = $dato["nombre"];
			}

				$arrayCampos["nombre"]		= new FormField( array("tag" => "input", 	"type" => "text"));
				$arrayCampos["uid_modulo"]	= new FormField( array("tag" => "select",	"data"  => $arrayModulos ));

			switch( $modo ){
				case "edit":
						$arrayCampos["nombre"]		= new FormField( array("tag" => "span", "transform" => true ));
						$arrayCampos["uid_modulo"]	= new FormField( array("tag" => "span",  "data"  => $arrayModulos ));
						$arrayCampos["tag"]			= new FormField( array("tag" => "select", "data" => self::obtenerTags() ));
						$arrayCampos["tipo"]		= new FormField( array("tag" => "select", "data" => self::obtenerTipos() ));
						$arrayCampos["datos"]		= new FormField( array("tag" => "textarea" ));
						$arrayCampos["prioridad"]	= new FormField( array("tag" => "slider",  "type" => "text",	"match" => "^([0-9][0-9])$",	"count" => "20"));
				break;
			}

			return $arrayCampos;
		}


		static public function obtenerTipos(){
			return array("text", "checkbox");
		}

		static public function obtenerTags(){
			return array("input", "select", "textarea");
		}

		public function getTableFields(){
			return array (
				array ("Field" => "uid_campo",		"Type" => "int(10)", 		"Null" => "NO",		"Key" => "PRI",		"Default" => "",		"Extra" => "auto_increment"),
				array ("Field" => "nombre",			"Type" => "varchar(255)",	"Null" => "NO",		"Key" => "",		"Default" => "",		"Extra" => ""),
				array ("Field" => "uid_modulo",		"Type" => "int(10)",		"Null" => "NO",		"Key" => "",		"Default" => "",		"Extra" => ""),
				array ("Field" => "tag",			"Type" => "int(1)",			"Null" => "NO",		"Key" => "",		"Default" => "",		"Extra" => ""),
				array ("Field" => "tipo",			"Type" => "int(1)",			"Null" => "NO",		"Key" => "",		"Default" => "",		"Extra" => ""),
				array ("Field" => "datos",			"Type" => "text",			"Null" => "NO",		"Key" => "",		"Default" => "",		"Extra" => ""),
				array ("Field" => "prioridad",		"Type" => "int(2)",			"Null" => "NO",		"Key" => "",		"Default" => "10",		"Extra" => ""),
				array ("Field" => "obligatorio",	"Type" => "int(1)",			"Null" => "NO",		"Key" => "",		"Default" => "0",		"Extra" => "")
			);
		}

	}
?>
