<?php
	class codigoocupacion extends elemento implements Ielemento {

		public function __construct($param, $extra = false){
			$this->tabla = TABLE_CODIGOOCUPACION;
			$this->nombre_tabla = "codigoocupacion";
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
			/*
				ORDENO POR EL CODIGO PORQUE LA IMPORTACION YA LA HICE ORDENADA POR SU CATEGORIA PRINCIPAL. ORIGINALMENTE PARA LLEGAR A ESTE DATO ES SELECCIONANDO DESDE TRES LISTAS 
				PERO DE MOMENTO SOLO IMPORTAMOS EL DATO FINAL
			*/
			$sql = "SELECT uid_codigoocupacion FROM ". TABLE_CODIGOOCUPACION ." WHERE 1 ORDER BY codigo ASC";
			$codigosocupacion = $db->query($sql, "*", 0, "codigoocupacion");

			return new ArrayObjectList($codigosocupacion);
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList;
			$fieldList["nombre"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false ) );
			return $fieldList;
		}
	}
?>
