<?php
	class adjunto extends elemento implements Ielemento {
	
		public function __construct($param, $extra = false){
			$this->tipo = "adjunto";
			$this->tabla = TABLE_ADJUNTO;
			$this->instance( $param, $extra );
		}

		public function getUserVisibleName(){
			return $this->obtenerDato("name");
		}

		public function download(){
			$file = $this->obtenerDato("file");
			$path = DIR_ADJUNTOS . $file;
			archivo::descargar($path, $this->getUserVisibleName());
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fields = new FieldList();
			switch( $modo ){
				default:
					$fields["name"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false ) );
					$fields["file"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false ) );
				break;
			}

			return $fields;
		}


	}
?>
