<?php
	class organizacionPreventiva extends elemento implements Ielemento {

		public function __construct($param, $extra=false){
			$this->tipo = "OrganizacionPreventiva";
			$this->nombre_tabla = "organizacion_preventiva";
			$this->tabla = TABLE_ORGANIZACION_PREVENTIVA;
			$this->instance( $param, $extra );
		}


		public function getUserVisibleName(){
			$tpl = Plantilla::singleton();
			return $tpl->getString($this->obtenerDato("nombre"));
		}

		public static function getAll(){
			$sql = "SELECT uid_organizacion_preventiva FROM ". TABLE_ORGANIZACION_PREVENTIVA ." WHERE 1";
			$items = db::get($sql, "*", 0, "organizacionPreventiva");
			return new ArrayObjectList($items);
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList;
			$fieldList["nombre"] = new FormField( array("tag" => "input", 	"type" => "text", "blank" => false ) );
			return $fieldList;
		}
	}
?>
