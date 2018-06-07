<?php
	class actividadempresarial extends elemento implements Ielemento {

		public function __construct($param, $extra = false){
			$this->tabla = TABLE_ACTIVIDADEMPRESARIAL;
			$this->nombre_tabla = "actividadempresarial";
			parent::instance($param, $extra);
		}

		public function getUserVisibleName(){
			return $this->obtenerDato("nombre");
		}

		public static function obtenerTodos(){
			$db = db::singleton();
			/*
				ORDENO POR EL CODIGO PORQUE LA IMPORTACION YA LA HICE ORDENADA POR SU CATEGORIA PRINCIPAL. ORIGINALMENTE PARA LLEGAR A ESTE DATO ES SELECCIONANDO DESDE TRES LISTAS 
				PERO DE MOMENTO SOLO IMPORTAMOS EL DATO FINAL

				JOSE: COMO NOS VAN A PEDIR QUE ESTE POR NOMBRE, LO CAMBIO PARA ORDENAR POR NOMBRE
			*/
			$sql = "SELECT uid_actividadempresarial FROM ". TABLE_ACTIVIDADEMPRESARIAL ." WHERE 1 ORDER BY nombre ASC";
			$actividadesempresariales = $db->query($sql, "*", 0, "actividadempresarial");

			return new ArrayObjectList($actividadesempresariales);
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList;
			$fieldList["nombre"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false ) );
			return $fieldList;
		}
	}
?>
