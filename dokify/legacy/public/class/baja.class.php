<?php
	class baja extends elemento implements Ielemento {

		const TYPE_UNDEFINED = 0;
		const TYPE_ACCIDENT = 1;
		const TYPE_SICKNESS = 2;
		const TYPE_MATERNITY = 3;
		
		public function __construct( $param, $extra = false ){
			$this->uid = $param;
			$this->tipo = "baja";
			$this->tabla = TABLE_BAJA;

			$this->instance( $param, $extra );
		}

		public function getUserVisibleName(){
			$start = $this->obtenerDato("fecha_inicio");
			$type = $this->obtenerDato("type");

			return self::type2string($type) . " · " . $start;
		}

		public function isActive(){
			$fin = strtotime($this->obtenerDato("fecha_fin"));
			if( ($fin && ($fin > time())) || !$fin || 0 > $fin){
				return true;
			}
			return false;
		}

		public function obtenerAccidente(){
			if( $uid = $this->obtenerDato("uid_accidente") ){
				return new accidente($uid);
			}
			return false;
		}

		public function getTypeOf(){
			return $this->obtenerDato("typeof");
		}

		public function getTypeString(){
			return self::type2string($this->getTypeOf());
		}


		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
			$data = array();
				$data[] = $this->getTypeString();
				

				$timestart = strtotime($this->obtenerDato("fecha_inicio"));
				$date = date("d/m/Y", $timestart);
				$timeend = $this->obtenerDato("fecha_fin");

				if( ($timeend && $timeend != "0000-00-00 00:00:00") && $timeend = strtotime($timeend) ){
					$date .= " - " . date("d/m/Y", $timeend);
				}

				$data[] = $date;
			//$data =  $this->getInfo(true, elemento::PUBLIFIELDS_MODE_TABLEDATA, $usuario);
			return array($this->getUID() => $data);
		}

	
		public function getLineClass(){
			$class = array("color");
			$end = strtotime($this->obtenerDato("fecha_fin"));

			if( $end && $end < time() ){
				$class[] = "green";
			} else {
				$class[] = "red";
			}

			return implode(" ", $class);
		}

		public static function getTypes($modo=false){

			$types = array();
				//$types[ self::TYPE_UNDEFINED ] = self::type2string(self::TYPE_UNDEFINED);
				if( $modo !== elemento::PUBLIFIELDS_MODE_INIT && $modo !== elemento::PUBLIFIELDS_MODE_EDIT ){
					$types[ self::TYPE_ACCIDENT ] = self::type2string(self::TYPE_ACCIDENT);
				}

				$types[ self::TYPE_SICKNESS ] = self::type2string(self::TYPE_SICKNESS);
				$types[ self::TYPE_MATERNITY ] = self::type2string(self::TYPE_MATERNITY);

			return $types;
		}

		public static function type2string($type){
			$lang = Plantilla::singleton();
			switch($type){
				default: return $lang->getString("no_definido"); break;
				case self::TYPE_ACCIDENT: return $lang->getString("accidente"); break;
				case self::TYPE_SICKNESS: return $lang->getString("enfermedad"); break;
				case self::TYPE_MATERNITY: return $lang->getString("maternidad"); break;
			}
		}

		public function obtenerEmpleado(){
			return new empleado($this->obtenerDato("uid_empleado"));
		}


		public function isLast(){
			if( $accidente = $this->obtenerAccidente() ){
				$sql = "SELECT uid_baja FROM ". TABLE_BAJA ." WHERE uid_accidente = {$accidente->getUID()} AND uid_baja > {$this->getUID()}";
				if( $this->db->query($sql, 0, 0) ){
					return true;
				}
			}
			return false;
		}

		public function triggerAfterCreate(Iusuario $usuario = NULL, Ielemento $elemento = NULL){
			if( $elemento instanceof baja ){
				if( $accidente = $elemento->obtenerAccidente() ){
					$sql = "SELECT uid_baja FROM {$this->tabla} WHERE uid_accidente = {$accidente->getUID()} AND uid_baja < {$this->getUID()}";
					if( $uid = $this->db->query($sql, 0, 0) ){
						$accidente->update( array("estado" => accidente::STATUS_RECAIDA), false, $usuario);
					}					
				}
			}
		}

		public function triggerAfterUpdate(Iusuario $usuario = NULL, $data){
			if( $this->getTypeOf() == self::TYPE_ACCIDENT && ($accidente = $this->obtenerAccidente()) && $accidente->exists() ){
				$estado = $accidente->obtenerDato("estado");

				// Si la baja se esta produciendo ahora mismo el accidente vinculado debe ser recaida si ya estaba un alta anterior
				if( $this->isActive() ){
					// Si está de alta, pasamos a normal de nuevo (se entiende error)
					if( $estado == accidente::STATUS_ALTA ){
						$accidente->update( array("estado" => accidente::STATUS_NORMAL) , false, $usuario);
					}
				} else {
					// Si no esta de alta, pasamos a alta (se entiende fin de baja)
					if( $estado != accidente::STATUS_ALTA ){
						$accidente->update( array("estado" => accidente::STATUS_ALTA) , false, $usuario);
					}
				}
			}
		}


		public static function optionsFilter($uid, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){
			$condiciones = array();

			if( is_numeric($uid) ){
				$baja = new baja($uid);
				if( $baja->isActive() && $baja->getTypeOf() == self::TYPE_ACCIDENT && $accidente = $baja->obtenerAccidente() ){
					if( $baja->isLast() ) $condiciones[] = " uid_accion != 133 ";
				}
			}

			if( count($condiciones) ){
				return "AND " . implode(" AND ", $condiciones);
			}

			return false;
		}

		public static function defaultData($data, Iusuario $usuario = NULL){
			if( !isset($data["poid"]) && !isset($data["uid_empleado"])  ){
				throw new Exception("error_desconocido");
			} elseif( obtener_comefrom_seleccionado() == "baja" && isset($data["poid"]) ){
				$baja = new baja($data["poid"]);
				$data["uid_empleado"] = $baja->obtenerEmpleado()->getUID();
			
				if( $accidente = $baja->obtenerAccidente() ){
					$data["typeof"] = baja::TYPE_ACCIDENT;
					$data["uid_accidente"] = $accidente->getUID();
				}
			} elseif( !isset($data["uid_empleado"]) ){
				$data["uid_empleado"] = $data["poid"];
			}

			if( isset($data["uid_empleado"]) ){
				$empleado = new empleado($data["uid_empleado"]);
				if( $empleado->estaDeBaja() ){
					throw new Exception("error_empleado_baja");					
				}
			}

			if( isset($data["fecha_inicio"]) && strlen($data["fecha_inicio"]) ){
				$aux = explode("/", $data["fecha_inicio"]);
				if( count($aux) === 3 ){
					$data["fecha_inicio"] = $aux[2]."-".$aux[1]."-".$aux[0] . " 00:00:00";
				} elseif( !count($aux) ){
					throw new Exception("error_fecha_incorrecta");
				}
			}

			if( isset($data["fecha_fin"]) && strlen($data["fecha_fin"]) ){
				$aux = explode("/", $data["fecha_fin"]);
				if( count($aux) === 3 ){
					$data["fecha_fin"] = $aux[2]."-".$aux[1]."-".$aux[0] . " 00:00:00";
				} elseif( !count($aux) ){
					throw new Exception("error_fecha_incorrecta");
				}
			}

			if( isset($data["fecha_fin"]) && strlen($data["fecha_fin"]) && strlen($data["fecha_inicio"]) && ($data["fecha_fin"] < $data["fecha_inicio"]) ){
				throw new Exception("error_fechas_intervalo");
			}
			
			if( !isset($data["uid_empleado"]) ) $data["uid_empleado"] = $data["poid"];
			return $data;
		}

		public function updateData($data, Iusuario $usuario = NULL, $mode = NULL) {
			$inicio = documento::parseDate($data["fecha_inicio"]);
			$fin = documento::parseDate($data["fecha_inicio"]);

			if( strlen($data["fecha_fin"]) && strlen($data["fecha_inicio"]) && ( $fin < $inicio  ) ){
				throw new Exception("error_fechas_intervalo");
			}

			return $data;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList();

			if( !($objeto instanceof self) || ( $objeto instanceof self && $objeto->getTypeOf() != self::TYPE_ACCIDENT ) ){
				$fieldList["typeof"]	 	= 	new FormField( array("tag" => "select", "data" => self::getTypes($modo) ));	
			}

			$fieldList["fecha_inicio"] 	= 	new FormField( array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "size" => 15, "date_format" => "%d/%m/%Y" ));
			$fieldList["fecha_fin"] 	= 	new FormField( array("tag" => "input", "type" => "text", "className" => "datepicker", "size" => 15, "date_format" => "%d/%m/%Y" ));
			$fieldList["comentarios"] 	= 	new FormField( array("tag" => "textarea", "type" => "text" ));
	

			switch( $modo ){
				default:
					$fieldList["uid_empleado"]		= 	new FormField( array("blank" => false));
					$fieldList["uid_accidente"] 	= 	new FormField;
					$fieldList["uid_accidente"]	= 	new FormField( array("tag" => "input", "type" => "text" ));
					$fieldList["uid_recaida"] 	= 	new FormField( array("tag" => "input", "type" => "text" ));
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
