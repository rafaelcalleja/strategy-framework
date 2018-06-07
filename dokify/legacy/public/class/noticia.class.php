<?php
	class noticia extends elemento implements Ielemento {
		/**
			CONSTRUIR EL OBJETO, LLAMA AL METODO INSTANCE DE LA CLASE ELEMENTO
		*/
		public function __construct($param, $extra=false){
			$this->tipo = "noticia";
			$this->tabla = TABLE_NOTICIA;

			$this->instance( $param, $extra );
		}

		//public function getModuleId(){ return 13; }
		//public function getModuleName(){ return "Noticia"; }

		public function getUserVisibleName(){
			$info = $this->getInfo();
			return $info["titulo"];
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()) {
			$info = parent::getInfo(true);

			$data =& $info[ $this->uid ];
			$texto = strip_tags( string_truncate($data["texto"], 100) );
			$data = array();


			$data["titulo"] = $this->getUserVisibleName();
			$data["texto"] = $texto;
			$data["fecha_alta"] = $this->getDate();

			return $info;
		}

		public function getTimestamp () {
			return strtotime($this->obtenerDato('fecha_alta'));
		}

		public function getDate(){
			$time = $this->getTimestamp();
			
			$mes = date("m", $time);
			return date("d", $time) . " · ". get_month_name($mes) ." · ". date("Y", $time);
		}

		public function getMinDate(){
			$info = $this->getInfo();
			$time = strtotime( $info["fecha_alta"] );
			return date("d/m/Y", $time);
		}

		public function getHTML(){
			$info = $this->getInfo();
			$text = $info["texto"];


			$maxlength = 40;
			$regexp = "/<a[^>]+href\s*=\s*[\"']([^\"']+)[\"'][^>]*>(.*?)<\/a>/mis";
			$offset = 0;
			while(preg_match($regexp, $text, $match, PREG_OFFSET_CAPTURE, $offset)) {
				list ($a, $href, $inner) = $match;
				$offset = $a[1] + 1; // move offset

				$string = $inner[0];
				$len = strlen($string);
				if ($len > $maxlength) {
					//$offset -= ($len - $maxlength); // ajustamos el offset para prevenir bugs!

					$short = str_replace(array('https://', 'http://'), '', $string);
					$short = trim(substr($short, 0, $maxlength)) . "...";
					$text = substr_replace($text, $short, $inner[1], $len);
				}
			}

			return $text;
		}	

		public function getCompany(){

			$info = $this->getInfo();
			return new empresa($info["uid_empresa"]);
		}			

		public function actualizarTitulo( $titulo ){
			$sql = "UPDATE $this->tabla SET titulo = '". db::scape($titulo) ."' WHERE uid_noticia = ". $this->getUID();
			return $this->db->query($sql);
		}

		public function actualizarTexto( $contenido ){
			$sql = "UPDATE $this->tabla SET texto = '". db::scape($contenido) ."' WHERE uid_noticia = ". $this->getUID();
			return $this->db->query($sql);
		}


		public static function crearNueva($empresa, $informacion){
			$db = db::singleton();
			$datos = array_keys( self::publicFields("edit") );
			$values = array();

			foreach( $datos as $campo ){
				if( isset($informacion[$campo]) )
					$values[] = "'". utf8_decode(db::scape($informacion[$campo])) ."'";
			}

			$sql = "INSERT INTO ". TABLE_NOTICIA ." ( uid_empresa, ". implode(",",$datos) ." ) VALUES (
				".$empresa->getUID().", ". implode(",",$values) ."
			)";

			if( !$db->query($sql) ){ return $db->lastErrorString(); }

			return new self( $db->getLastId() );
		}


		public static function defaultData($data, Iusuario $usuario = null) {
			if( isset($data["poid"]) && !isset($data["uid_empresa"]) ){
				$data["uid_empresa"] = $data["poid"];
			}
			return $data;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fields = new FieldList;
			switch( $modo ){
				case elemento::PUBLIFIELDS_MODE_INIT: case elemento::PUBLIFIELDS_MODE_NEW: case elemento::PUBLIFIELDS_MODE_EDIT:  
					$fields["titulo"] 		= new FormField( array("tag" => "input", "type" => "text", "blank" => false) );
					$fields["empleados"]	= new FormField( array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox") );
					$fields["managers"]		= new FormField( array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox") );
					$fields["visible_usuarios"]	= new FormField( array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox") );
					$fields["visible_usuarios_contratas"]		= new FormField( array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox") );
					$fields["visible_usuarios_externo"]		= new FormField( array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox") );

					if( $modo === elemento::PUBLIFIELDS_MODE_NEW ){
						$fields["uid_empresa"] 		= new FormField( array("tag" => "input", "type" => "text", "blank" => false) );
					}
				break;
			}

			if( !$modo ){
				$fields["texto"]	= new FormField( array("tag" => "input", "type" => "text") );
			}

			return $fields;
		}

	}
?>
