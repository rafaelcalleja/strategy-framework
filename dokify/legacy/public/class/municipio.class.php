<?php
	class municipio extends elemento implements Ielemento {

		public function __construct($param, $extra = false){
			$this->tabla = TABLE_MUNICIPIO;
			$this->nombre_tabla = "municipio";
			parent::instance($param);
		}

		public function getUserVisibleName(){
			return $this->obtenerDato("nombre");
		}

		public function getRouteName () {
			return 'town';
		}

		public static function getFromName($name){
			$cache = cache::singleton();
			$name = strtolower($name);
			if( ($cacheString = "municipio-getFromName-{$name}") && ($estado = $cache->getData($cacheString)) !== null ){
				return $estado;
			}



			$db = db::singleton();
			$sql = "SELECT uid_municipio FROM ". TABLE_MUNICIPIO ." WHERE nombre LIKE '%". db::scape($name) ."%'";
			$item = false;
			if( $uid = $db->query($sql, 0, 0) ){
				$item = new self($uid);
			}

			$cache->addData($cacheString, $item); 
			return $item;
		}

		public static function obtenerTodos(){
			$db = db::singleton();
			$cache = cache::singleton();
			$cacheString = __CLASS__.'-'.__FUNCTION__;
			if ($municipios = $cache->getData($cacheString)) {
				return ArrayObjectCities::factory($municipios);
			}
			$sql = "SELECT uid_municipio FROM ". TABLE_MUNICIPIO ." WHERE 1 ORDER BY nombre";
			$municipios = $db->query($sql, "*", 0, "municipio");
			$municipios = new ArrayObjectCities($municipios);

			$cache->addData($cacheString, "$municipios", true);
			return $municipios;
		}
		
		public static function obtenerPorProvincia($param){
			if ($param instanceof provincia) $param = $param->getUID();
			if (!is_numeric($param)) { die; }
			$db = db::singleton();
			$cache = cache::singleton();
			$cacheString = __CLASS__.'-'.__FUNCTION__.'-'.$param;
			if ($municipios = $cache->getData($cacheString)) {
				return new ArrayObjectCities($municipios);
			}
			$sql = "SELECT uid_municipio FROM ". TABLE_MUNICIPIO ." WHERE 1=1 AND uid_provincia = {$param} ORDER BY nombre";
			$municipios = $db->query($sql, "*", 0, "municipio");
			$cache->addData($cacheString,$municipios);
			return new ArrayObjectCities($municipios);
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList;
			$fieldList["nombre"] = new FormField( array("tag" => "input", 	"type" => "text", "blank" => false ) );
			return $fieldList;
		}

		public function getTableFields(){
			return array(
				array("Field" => "uid_municipio",	"Type" => "int(11)", 		"Null" => "NO",		"Key" => "PRI",		"Default" => "",		"Extra" => "auto_increment"),
				array("Field" => "uid_provincia",	"Type" => "int(11)",		"Null" => "NO",		"Key" => "",		"Default" => "",		"Extra" => ""),
				array("Field" => "codigo",			"Type" => "varchar(200)",	"Null" => "NO",		"Key" => "",		"Default" => "",		"Extra" => ""),
				array("Field" => "nombre",			"Type" => "varchar(200)",	"Null" => "NO",		"Key" => "",		"Default" => "",		"Extra" => "")
			);
		}
	}
?>
