<?php
	class cnae extends elemento implements Ielemento {

		public function __construct($param, $extra = false){
			$this->tabla = TABLE_CNAE;
			$this->nombre_tabla = "cnae";
			parent::instance($param);
		}

		public function obtenerCodigo(){
			return $this->obtenerDato("codigo");
		}

		public function getSelectName($fn = false){
			return $this->getUserVisibleName() . " ({$this->obtenerCodigo()})";
		}

		public function getUserVisibleName(){
			return $this->obtenerDato("nombre");
		}

		public static function obtenerTodos(){
			$db = db::singleton();
			$sql = "SELECT uid_cnae FROM ". TABLE_CNAE ." WHERE 1 ORDER BY uid_cnae";
			$cnaes = $db->query($sql, "*", 0, "cnae");

			return new ArrayObjectList($cnaes);
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList;
			$fieldList["nombre"] = new FormField( array("tag" => "input", 	"type" => "text", "blank" => false ) );
			return $fieldList;
		}
	}
?>
