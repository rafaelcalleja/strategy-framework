<?php
	class convocatoriamedica extends elemento implements Ielemento {

		public function __construct( $param, $extra = false ){
			$this->uid = $param;
			$this->tipo = "convocatoriamedica";
			$this->tabla = TABLE_CONVOCATORIA_MEDICA;

			$this->instance( $param, $extra );
		}

		public function getUserVisibleName(){
			$start = $this->obtenerDato("fecha_creacion");
			return $start;
		}


		public function getInlineArray(Iusuario $usuarioActivo = NULL, $mode = null , $data = false){
			$inlinearray = array();

			$tpl = Plantilla::singleton();
		
			$citas = array();
				$citas["img"] = RESOURCES_DOMAIN . "/img/famfam/time.png";
				$citas[] = array(
					'nombre' => $this->obtenerCitaMedicas(true) . " " . $tpl("citas"),
					'tagName' => 'span'
				);
			$inlinearray[] = $citas;

			return $inlinearray;
		}

		public function getTreeData(){
			$citas = $this->obtenerCitaMedicas();
			if( $citas instanceof ArrayObjectList && count($citas) ){
				$url = $_SERVER["PHP_SELF"] . "?m=citamedica&poid={$this->obtenerEmpleado()->getUID()}&oid={$this->getUID()}";
			} else {
				$url = false;
			}


			$img = $imgopen = RESOURCES_DOMAIN . "/img/famfam/timeline_marker.png";
			return array(
				"img" => array("normal" => $img, "open" => $imgopen),
				"url" => $url
			);
		}

		public function obtenerCitaMedicas($count=false){
			if( $count ){
				return $this->obtenerConteoRelacionados( TABLE_CITA_MEDICA, "uid_convocatoriamedica", "uid_citamedica" );
			} else {
				$items = $this->obtenerObjetosRelacionados( TABLE_CITA_MEDICA, "citamedica");
				return new ArrayObjectList($items);
			}
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
			$data = array();

			$data[] = $this->obtenerEmpleado()->getUserVisibleName();

			$start = strtotime($this->obtenerDato("fecha_creacion"));
			$data[] = date("d/m/Y", $start);
			
			return array($this->getUID() => $data);
		}

		public function obtenerEmpleado(){
			return new empleado($this->obtenerDato("uid_empleado"));
		}
	
		public function getLineClass(){
			$class = array("color");
			return implode(" ", $class);
		}


		public function obtenerDireccionesReconocimiento() {
			return $this->obtenerEmpleado()->obtenerDireccionesReconocimiento();
		}


		public static function defaultData($data, Iusuario $usuario = NULL){
			if( !isset($data["poid"]) ){
				throw new Exception("error_desconocido");
			}

			if( isset($data["fecha_creacion"]) ){
				$aux = explode("/", $data["fecha_creacion"]);
				if( count($aux) === 3 ){
					$data["fecha_creacion"] = $aux[2]."-".$aux[1]."-".$aux[0] . " 00:00:00";
				} else {
					throw new Exception("error_fecha_incorrecta");
				}
			}

			$data["uid_empleado"] = $data["poid"];

			return $data;
		}


		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList();

			$fieldList["fecha_creacion"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "size" => 15, "date_format" => "%d/%m/%Y", "default" => date("d/m/Y") ));

			switch( $modo ){
				default:
					$fieldList["uid_empleado"] 	= 	new FormField( array("tag" => "input", "type" => "text", "blank" => false ));
				break;
				case elemento::PUBLIFIELDS_MODE_INIT: 
					if( $objeto instanceof empleado && $objeto->estaDeBaja() ) throw new Exception("error_empleado_baja");
				break;				
				case elemento::PUBLIFIELDS_MODE_EDIT:
					
				break;
			}

			return $fieldList;
		}
	}
?>
