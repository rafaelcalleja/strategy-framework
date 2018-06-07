<?php
	include_once( DIR_CLASS . "elemento.class.php" );

	//clase etiqueta
	class plantilla_atributo extends elemento {
		protected $empresa;

		public function __construct( $param , $empresa ){
			$this->tipo = "plantilla_atributo";
			$this->tabla = TABLE_PLANTILLAATRIBUTO;
			$this->empresa = $empresa;
			
			$this->instance( $param, false );
		}

		public function getEmail(){
			if ($email = $this->obtenerDato("email"))
				return new ArrayObjectList(explode(";", $email));
			else
				return new ArrayObjectList;
		}

		public static function publicFields(){
			$arrayCampos = new FieldList();
			$arrayCampos["email"]	= new FormField( array("tag" => "input", 	"type" => "text"));
			return $arrayCampos;
		}

	}

?>
