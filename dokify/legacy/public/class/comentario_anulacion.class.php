<?php
	class comentario_anulacion extends elemento implements Ielemento {	  

		public function __construct( $param, $extra=false ){
			$this->tipo = "comentario_anulacion";
			$this->tabla = TABLE_COMENTARIO_ANULACION;

			$this->instance( $param, $extra );
		}

		public function getUserVisibleName(){
			return string_truncate( $this->obtenerDato("comentario"), 180);
		}
			
		static public function publicFields ($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList;
			
			$fieldList["comentario"] = new FormField(array("tag" => "textarea"));

			return $fieldList;

		}
	}
?>
