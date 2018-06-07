<?php
	class centrocotizacion extends elemento implements Ielemento {

		public function __construct($param, $extra = false){
			$this->tabla = TABLE_CENTRO_COTIZACION;
			$this->nombre_tabla = "centrocotizacion";
			parent::instance($param, $extra);
		}

		public static function getFromCode($code) {

			$cache = cache::singleton();
			if( ($cacheString = __CLASS__ . '-' .__FUNCTION__ .'-'.$code) && ($estado = $cache->getData($cacheString)) !== null ){
				return $estado;
			}

			$db = db::singleton();
			$sql = "SELECT uid_centrocotizacion FROM ". TABLE_CENTRO_COTIZACION ." WHERE codigo = '". db::scape($code). "'";
			$item = false;

			$centros = db::get($sql, "*", 0, "centrocotizacion");
			if ($centros) $centro = reset($centros);
			else $centro = false;

			$cache->addData($cacheString, $centro); 
			return $centro;

		}

		public function obtenerDelegados() {
			$sql = " SELECT uid_empleado FROM ".TABLE_EMPLEADO." WHERE 1=1 AND uid_centrocotizacion='{$this->uid}' AND delegado_prevencion='1' ";
			return $this->db->query( $sql, "*", 0, "empleado" );
		}
		
		public function obtenerEmpleados() {
			$sql = " SELECT uid_empleado FROM ".TABLE_EMPLEADO." WHERE 1=1 AND uid_centrocotizacion='{$this->uid}' ";
			return $this->db->query( $sql, "*", 0, "empleado" );
		}

		public function getUserVisibleName(){
			return $this->obtenerDato("nombre");
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
			$data = array();

				$data[] = $this->obtenerDato("nombre");
				$data[] = $this->obtenerDato("domicilio");

			return array($this->getUID() => $data);
		}

		public static function defaultData($data, Iusuario $usuario = NULL){
			if( !isset($data["poid"]) ){
				throw new Exception("error_desconocido");
			}

			if( ($comefrom = obtener_comefrom_seleccionado()) && ($uidmodulo = elemento::obtenerIdModulo($comefrom)) ){	
				$data["uid_elemento"] = $data["poid"];
				$data["uid_modulo"] = $uidmodulo;
			} else {
				throw new Exception("error_desconocido");
			}

			return $data;
		}

		static public function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList;

			$fieldList["nombre"] = new FormField(array("tag" => "input", 	"type" => "text", "blank" => false ));
			$fieldList["codigo"] = new FormField(array("tag" => "input", 	"type" => "text", "blank" => false ));
			$fieldList["texto_actividad_empresarial"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false ));
			$fieldList["codigo_actividad_empresarial"] = new FormField(array("tag" => "select",  "data" => actividadempresarial::obtenerTodos(), "blank" => false, "search" => true ));
			$fieldList["domicilio"] = new FormField(array("tag" => "textarea" ));

			switch( $modo ){
				case elemento::PUBLIFIELDS_MODE_INIT: case elemento::PUBLIFIELDS_MODE_EDIT: case elemento::PUBLIFIELDS_MODE_TABLEDATA:
				break;
				case elemento::PUBLIFIELDS_MODE_DELTA: 
					$fieldList = new FieldList(array('texto_actividad_empresarial','texto_actividad_empresarial'));
				break;
				default:
					$fieldList["uid_elemento"] 	= 	new FormField( array("tag" => "input", "type" => "text", "blank" => false));
					$fieldList["uid_modulo"] 	= 	new FormField( array("tag" => "input", "type" => "text", "blank" => false));
				break;
				
			}


			return $fieldList;
		}
	}
?>
