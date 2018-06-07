<?php
	class tipocontrato extends elemento implements Ielemento {

		public function __construct($param, $extra = false){
			$this->tabla = TABLE_TIPOCONTRATO;
			$this->nombre_tabla = "tipocontrato";
			parent::instance($param);
		}

		public function getUserVisibleName(){
			return $this->obtenerDato("nombre");
		}


		public static function obtenerTodos(){
			$db = db::singleton();
			$sql = "SELECT uid_tipocontrato FROM ". TABLE_TIPOCONTRATO ." WHERE 1 ORDER BY uid_tipocontrato";
			$tiposcontrato = $db->query($sql, "*", 0, "tipocontrato");

			return new ArrayObjectList($tiposcontrato);
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList;
			$fieldList["nombre"] = new FormField( array("tag" => "input", 	"type" => "text", "blank" => false ) );
			return $fieldList;
		}
	}
?>
