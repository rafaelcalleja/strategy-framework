<?php
	class elementorelacion extends elemento implements Ielemento {	  
		
		// Evitamos que se solicite el usuario para guardar el log cuando se cree un objeto de este tipo
		const NO_REGISTER_CREATION = true; 

		public function __construct( $param, $extra = false ){
			$this->tipo = "elementorelacion";
			$this->tabla = TABLE_ELEMENTO_RELACION;

			$this->instance( $param, $extra );
		}
			
		public function getUserVisibleName(){
			$agrupador = new agrupador(  $this->obtenerDato("uid_agrupador") );
			return $agrupador->getUserVisibleName();
		}

		public function getModuleId($tipo = false){
			return util::getModuleId("certificacion");
		}
	
		public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = NULL, $extra = array(), $force = false){
			//public function getInfo( $publicMode = false, $comeFrom = null, $usuario=false, $force = false){
			$data = parent::getInfo($publicMode, $comeFrom, $usuario, $force);

			if( $publicMode ){
				$uid = $data[ $this->uid ]["uid_agrupador"];
				$agrupador = new agrupador($uid);
				$data[ $this->uid ]["uid_agrupador"] = $agrupador->getUserVisibleName();
			}

			return $data;
		}

		static public function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = null){
			$arrayCampos = new FieldList();

			if( $modo == elemento::PUBLIFIELDS_MODE_NEW || $modo == elemento::PUBLIFIELDS_MODE_INIT || $modo == elemento::PUBLIFIELDS_MODE_TABLEDATA || $modo == elemento::PUBLIFIELDS_MODE_EDIT ){

				$arrayCampos["uid_agrupador"] = new FormField( array( "tag" => "select", "data" => certificacion::obtenerConceptosPago($usuario), "innerHTML" => "Seleccionar elemento" ) );
				$arrayCampos["precio_unitario"] = new FormField( array("tag" => "input") );



				if( $modo == elemento::PUBLIFIELDS_MODE_EDIT && $objeto ){
					$agrupador = new agrupador(  $objeto->obtenerDato("uid_agrupador") );
					$arrayCampos["uid_agrupador"]["innerHTML"] = $agrupador->getUserVisibleName();
					$arrayCampos["uid_agrupador"]["tag"] = "span";
				}
			}

			if( $modo == elemento::PUBLIFIELDS_MODE_NEW ){
				$arrayCampos["uid_elemento"] = new FormField( array("tag" => "input", "type" => "text" ) );
				$arrayCampos["uid_modulo"] = new FormField( array("tag" => "input", "type" => "text" ) );
			}

			return $arrayCampos;
		}
	}
?>
