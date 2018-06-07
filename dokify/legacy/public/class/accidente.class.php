<?php
	class accidente extends adjuntable implements Ielemento {

		const PLACE_CENTROHABITUAL = 1;
		const PLACE_OTROCENTRO = 2;
		const PLACE_DESPLAZAMIENTO = 3;
		const PLACE_INITINERE = 4;
		
		const AVISO_DELEGADO = 1;
		const AVISO_EMPLEADO = 2;
		const AVISO_CLIENTE = 3;

		const STATUS_NORMAL = 0;
		const STATUS_ALTA = 1;
		const STATUS_RECAIDA = 2;
		
		const GRADO_LEVE = 1;
		const GRADO_GRAVE = 2;
		const GRADO_MUY_GRAVE = 3;
		const GRADO_FALLECIMIENTO = 4;

		public function __construct( $param , $extra = false ){
			$this->tipo = "accidente";
			$this->tabla = TABLE_ACCIDENTE;
			$this->uid_modulo = 70;
	
			$this->instance( $param, $extra );
		}


		public function getUserVisibleName(){
			$tpl = Plantilla::singleton();
			$places = self::getKindOfPlaces();

			$lugar = $this->obtenerDato("lugar");
			$lugarString = $tpl->getString($places[$lugar]);

			$empleado = $this->obtenerEmpleado();

			return $lugarString . " · " . $empleado->getUserVisibleName();
		}


		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
			$data = array();
	

			$data["nombre"] =  array(
				"innerHTML" => $this->getUserVisibleName(),
				"href" => "../agd/ficha.php?m=accidente&poid=". $this->uid,
				"className" => "box-it link"
			);

			return array($this->getUID() => $data);
		}

		public function getTimestamp(){
			return strtotime($this->obtenerDato("fecha_accidente") . " " . $this->obtenerDato("hora_accidente"));
		}

		public function obtenerBaja(){
			$sql = "SELECT uid_baja FROM ". TABLE_BAJA . " WHERE uid_accidente = {$this->getUID()} ORDER BY uid_baja DESC LIMIT 1";
			if( $uid = $this->db->query($sql, 0, 0) ){
				return new baja($uid);
			}
			return false;
		}
		
		public function obtenerTipoAsistencia() {
			$sql = "SELECT tipo_asistencia FROM ". $this->tabla . " WHERE uid_accidente = {$this->getUID()} LIMIT 1";
			if( $uid = $this->db->query($sql, 0, 0) ){
				return new tipoasistencia($uid);
			}
			return false;
		}
		public function obtenerGradoLesion() {
			$sql = "SELECT grado_lesion FROM ". $this->tabla . " WHERE uid_accidente = {$this->getUID()} LIMIT 1";
			if( $uid = $this->db->query($sql, 0, 0) ){
				return new gradolesion($uid);
			}
			return false;
		}
		
		public function obtenerLesion() {
			$sql = "SELECT descripcion_lesion FROM ". $this->tabla . " WHERE uid_accidente = {$this->getUID()} LIMIT 1";
			if( $uid = $this->db->query($sql, 0, 0) ){
				return new lesion($uid);
			}
			return false;
		}
		
		public function obtenerParteLesionada() {
			$sql = "SELECT parte_cuerpo_lesion FROM ". $this->tabla . " WHERE uid_accidente = {$this->getUID()} LIMIT 1";
			if( $uid = $this->db->query($sql, 0, 0) ){
				return new partelesionada($uid);
			}
			return false;
		}

		public function getInlineArray(Iusuario $usuarioActivo = NULL, $mode = null , $data = false){
			$inlinearray = array();
			$tpl = Plantilla::singleton();


			$baja = array();
				$implicaBaja = $this->obtenerDato("baja");
				$bajaString = ( $implicaBaja ) ? $tpl->getString("con_baja") : $tpl->getString("sin_baja");

				if( $implicaBaja && $bajaAccidente = $this->obtenerBaja() ){
					$estado = $this->obtenerDato("estado");
					if( $estado != self::STATUS_ALTA && !$bajaAccidente->isActive() ){
						$estado = self::STATUS_ALTA;
					}

					if( $estado != self::STATUS_NORMAL ){
						$bajaString .= " · " . self::status2string($estado);
					}
				}

				$baja["img"] = RESOURCES_DOMAIN . "/img/famfam/asterisk_orange.png";
				$baja[] = array( "nombre" => $bajaString );
			$inlinearray[] = $baja;	

			$fecha = array();
				$fecha["img"] = RESOURCES_DOMAIN . "/img/famfam/time.png";
				$fecha[] = array( "nombre" => date("Y/m/d H:i", $this->getTimestamp()) );
			$inlinearray[] = $fecha;


			return $inlinearray;
		}

		public function obtenerEmpleado(){
			if( $uid = $this->obtenerDato("uid_empleado") ){
				return new empleado($uid);
			}
			return false;
		}
		
		public function obtenerPais(){
			if ($uid = $this->obtenerDato('pais')) {
				return new pais($uid);
			}
			return false;
		}
		
		public function obtenerProvincia(){
			if ($uid = $this->obtenerDato('provincia')) {
				return new provincia($uid);
			}
			return false;
		}
		
		public function obtenerMunicipio(){
			if ($uid = $this->obtenerDato('municipio')) {
				return new municipio($uid);
			}
			return false;
		}

		public function obtenerMutua(){
			if ($uid = $this->obtenerDato('entidad_gestora')) {
				return new mutua($uid);
			}
			return false;
		}

		public function triggerAfterCreate(Iusuario $usuario = NULL, Ielemento $elemento = NULL){
			if( $elemento instanceof accidente ){
				if( $elemento->obtenerDato("baja") ){
					$data = array(
						"uid_empleado" => $elemento->obtenerEmpleado()->getUID(), 
						"typeof" => baja::TYPE_ACCIDENT, 
						"fecha_inicio" => date("Y-m-d H:i", $this->getTimestamp()),
						"uid_accidente" => $elemento->getUID()
					);
					$baja = new baja($data, $usuario);
					if( $baja->error ){
						throw new Exception( $baja->error);
					}
				}
			}
		}
	

		public static function status2string($type){
			$lang = Plantilla::singleton();
			switch($type){
				default: return $lang->getString("no_definido"); break;
				case self::STATUS_NORMAL: return $lang->getString("normal"); break;
				case self::STATUS_ALTA: return $lang->getString("alta_recibida"); break;
				case self::STATUS_RECAIDA: return $lang->getString("recaida"); break;
			}
		}

		public static function getKindOfPlaces(Ielemento $item = NULL){
			// El dato self::PLACE_OTROCENTRO no debe ir ni en primera ni en segunda posición, ya que su ONCHANGE desencadena la visión de otros
			// campos, de momento es lo más comodo < JOSE 
			$lugares = array( 
				self::PLACE_CENTROHABITUAL => "centro_habitual",
				self::PLACE_DESPLAZAMIENTO => "desplazamiento_jornada",
				self::PLACE_INITINERE => "in_itinere",
				self::PLACE_OTROCENTRO => "otro_centro",
			);

			if ( $item instanceof empleado ) {
				// Si no conocemos el centro de trabajo habitual ...
				if ( !$item->obtenerCentrocotizacion() ) unset($lugares[self::PLACE_CENTROHABITUAL]);
			}

			return $lugares;
		}


		public static function defaultData($data, Iusuario $usuario = NULL){
			$data["uid_empleado"] = $data["poid"];

			if( isset($data["poid"]) ){
				$empleado = new empleado($data["poid"]);
				if( $empleado->estaDeBaja() ){
					throw new Exception("error_empleado_baja");					
				}

				if( !isset($data["lugar"]) || $data["lugar"] != self::PLACE_OTROCENTRO ){
					if( $centro = $empleado->obtenerCentroCotizacion() ){
						$data["uid_centrocotizacion"] = $centro->getUID();
					} else {
						throw new Exception("error_centrocotizacion_novalido");
					}
				}
			} else {
				throw new Exception("error_ubicar_empleado");
			}

			if( isset($data["fecha_accidente"]) ){
				$aux = explode("/", $data["fecha_accidente"]);
				if( count($aux) === 3 ){
					$data["fecha_accidente"] = $aux[2]."-".$aux[1]."-".$aux[0] . " 00:00:00";
				} else {
					unset($data["fecha_accidente"]);
				}
			}

			// Si no tenemos definido el campo fecha y baja esta marcado como NO, entonces no necesitamos ese dato
			if( ( !isset($data["fecha_baja"]) || !$data["fecha_baja"]) && ( !isset($data["baja"]) || !$data["baja"] ) ){
				$data["fecha_baja"] = "0000-00-00";
			} else {
				$data["fecha_baja"] = date("Y-m-d", documento::parseDate($data["fecha_baja"]));
			}




			return $data;
		}
		

		/*
		 *	Obtener xml para delta
		 *
		 *	@param $list Lista de accidentes a exportar
		 *	@param $type Constantes delta::SIN_BAJA o delta::CON_BAJA
		 */
		public static function deltaXML(ArrayObjectList $list, $type){
			return new delta($list, $type);
		}


		/*
		 *	Accidentes de un mismo tipo ocurridos este mes
		 *
		 *	@param $type Constantes delta::SIN_BAJA o delta::CON_BAJA
		 */
		public static function getCurrentMonth($type = delta::SIN_BAJA){
			$db = db::singleton();
			$sql = "SELECT uid_accidente FROM ". TABLE_ACCIDENTE ." WHERE baja = $type AND DATE_FORMAT(fecha_accidente, '%Y/%m') = '". date("Y/m") ."'";
			$coleccion = $db->query($sql, "*", 0, "accidente");
			return new ArrayObjectList($coleccion);
		}



		public static function checkTypes(ArrayObject $list, $fieldType = "baja"){
			if( count($list) ){
				$db = db::singleton();
				$array = ( $list instanceof ArrayObjectList ) ? $list->toIntList()->getArrayCopy() : $list->getArrayCopy();
				$sql = "SELECT $fieldType FROM ". TABLE_ACCIDENTE ." WHERE uid_accidente IN (". implode(",", $array) .") GROUP BY $fieldType";
				if( $types = $db->query($sql, "*", 0) ){
					return $types;
				}
			}
			return false;
		}


		public function updateData($data, Iusuario $usuario = NULL, $mode = NULL) {
			return $data;
		}
		
		public static function diasSemana() {
			return array('1'=>'Lunes','2'=>'Martes','3'=>'Miercoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sabado','7'=>'Domingo',);
		}
		

		public function obtenerCentroCotizacion(){
			if( $uid = $this->obtenerDato("uid_centrocotizacion") ){
				return new centrocotizacion($uid);
			}

			return false;
		}


		public function obtenerAvisos(Iusuario $usuario){
			// pendiente de implementar roles y permisos
			// $accionData = $usuario->accesoAccionConcreta(get_class($this), "Avisos");
			// if( count($accionData) ){
			$href = 'accidente/avisos.php';
			
			return array(
				array(	
					"innerHTML" => "accidente_aviso_delegado", 
					"string" => "accidente_aviso_delegado", 
					"href" => "{$href}?oid=".$this->getUID()."&aviso=".self::AVISO_DELEGADO,
					"img" => RESOURCES_DOMAIN . "/img/famfam/bell_go.png",
					"className" => "to-iframe-box" 
				),
				array(	
					"innerHTML" => "accidente_aviso_empleado", 
					"string" => "accidente_aviso_empleado", 
					"href" => "{$href}?oid=".$this->getUID()."&aviso=".self::AVISO_EMPLEADO,
					"img" => RESOURCES_DOMAIN . "/img/famfam/bell_go.png",
					"className" => "to-iframe-box" 
				),
				array(	
					"innerHTML" => "accidente_aviso_cliente", 
					"string" => "accidente_aviso_cliente", 
					"href" => "{$href}?oid=".$this->getUID()."&aviso=".self::AVISO_CLIENTE,
					"img" => RESOURCES_DOMAIN . "/img/famfam/information.png",
					"className" => "to-iframe-box" 
				),
			);
			// }
		}
		
		public function sendEmailInfo(Iusuario $usuario = NULL, $recordatorio = false, $aviso, $destinatariosAdicionales = null){
			$logEmail = new log();
			$lang = Plantilla::singleton();
			$empleado = $this->obtenerEmpleado();
			$destinatarios = array();
			switch($aviso){
				case self::AVISO_CLIENTE:
					$plantillaemail = plantillaemail::instanciar("infoaccidente");
					$asunto = "Información de accidente";
				break;
				case self::AVISO_EMPLEADO:			
					if ( $emailEmpleado = $empleado->obtenerDato("email") ) {
						$destinatarios[] = $emailEmpleado;
					}
					$plantillaemail = plantillaemail::instanciar("parteaccidente");
					$asunto = "Medidas preventivas";
				break;
				case self::AVISO_DELEGADO:
					$delegados = array();
					$plantillaemail = plantillaemail::instanciar("parteaccidente");
					$centroCotizacion = $this->obtenerEmpleado()->obtenerCentroCotizacion();
					if ($centroCotizacion){
						$delegados = $centroCotizacion->obtenerDelegados();
					}
					
					foreach ($delegados as $delegado) {
						if ($emailDelegado = $delegado->obtenerDato('email')) {
							$destinatarios[] = $emailDelegado;
						}
					}
					$asunto = "Notificación de accidente";
				break;
				default:
					$logEmail->info("accidente","ERROR: No se encuentra la plantilla. Enviando aviso al empleado" , $empleado->getUserVisibleName());
					return false;
				break;							
			}

			$plantillaemail->replaced["{%evento-estado%}"] = self::status2string($this->obtenerDato("estado"));
			$plantillaemail->replaced["{%elemento-nombre%}"] = $empleado->obtenerDato("nombre").", ".$empleado->obtenerDato("apellidos");
			$plantillaemail->replaced["{%evento-fecha%}"] = $this->obtenerDato("fecha_accidente");
			$plantillaemail->replaced["{%evento-hora%}"] = $this->obtenerDato("hora_accidente");
			$plantillaemail->replaced["{%hora-trabajo%}"] = $this->obtenerDato("hora_jornada");
			$plantillaemail->replaced["{%tipo-trabajo%}"] = ( $asistencia = $this->obtenerTipoAsistencia() ) ? $asistencia->getUserVisibleName() : null;
			$plantillaemail->replaced["{%fecha-baja%}"] = $this->obtenerDato("fecha_baja");
			$plantillaemail->replaced["{%descripcion%}"] = $this->obtenerDato("descripcion");			
			$plantillaemail->replaced["{%telefono%}"] = $empleado->obtenerDato("telefono");
			$plantillaemail->replaced["{%ocupacion%}"] = ( $codigo = $empleado->obtenerCodigoOcupacion() ) ? $codigo->getUserVisibleName() : null;



			$plantillaemail->replaced["{evento-estado}"] = self::status2string($this->obtenerDato("estado"));
			$plantillaemail->replaced["{elemento-nombre}"] = $empleado->obtenerDato("nombre").", ".$empleado->obtenerDato("apellidos");
			$plantillaemail->replaced["{evento-fecha}"] = $this->obtenerDato("fecha_accidente");
			$plantillaemail->replaced["{evento-hora}"] = $this->obtenerDato("hora_accidente");
			$plantillaemail->replaced["{hora-trabajo}"] = $this->obtenerDato("hora_jornada");
			$plantillaemail->replaced["{tipo-trabajo}"] = ( $asistencia = $this->obtenerTipoAsistencia() ) ? $asistencia->getUserVisibleName() : null;
			$plantillaemail->replaced["{fecha-baja}"] = $this->obtenerDato("fecha_baja");
			$plantillaemail->replaced["{descripcion}"] = $this->obtenerDato("descripcion");			
			$plantillaemail->replaced["{telefono}"] = $empleado->obtenerDato("telefono");
			$plantillaemail->replaced["{ocupacion}"] = ( $codigo = $empleado->obtenerCodigoOcupacion() ) ? $codigo->getUserVisibleName() : null;

			if( $lesion = $this->obtenerParteLesionada() ){
				$plantillaemail->replaced["{%lesion%}"] = $lesion->getUserVisibleName();
				$plantillaemail->replaced["{%existe-lesion%}"] = "Si";

				$plantillaemail->replaced["{lesion}"] = $lesion->getUserVisibleName();
				$plantillaemail->replaced["{existe-lesion}"] = "Si";
			} else {
				$plantillaemail->replaced["{%existe-lesion%}"] = "No";
				$plantillaemail->replaced["{%lesion%}"] = "Sin lesiones";

				$plantillaemail->replaced["{existe-lesion}"] = "No";
				$plantillaemail->replaced["{lesion}"] = "Sin lesiones";
			}

			$plantillaemail->replaced["{%baja%}"] = $this->obtenerDato("baja") ? "Si" : "No";
			$plantillaemail->replaced["{baja}"] = $this->obtenerDato("baja") ? "Si" : "No";

			$lugares = self::getKindOfPlaces($empleado);
			$plantillaemail->replaced["{%direccion%}"] = ($lugares[$this->obtenerDato("lugar")]?$lugares[$this->obtenerDato("lugar")]:$this->obtenerDato("lugar"));
			$plantillaemail->replaced["{%comentario%}"] = ($this->obtenerDato("comentarios") ? $this->obtenerDato("comentarios") : " " );
			$plantillaemail->replaced["{%accidente-baja%}"] = ($this->obtenerDato("baja") ?'Si':'No' );
			$plantillaemail->replaced["{%medidas-preventivas%}"] = ($this->obtenerDato("medidas_preventivas") ? $this->obtenerDato("medidas_preventivas") : " " );


			$plantillaemail->replaced["{direccion}"] = ($lugares[$this->obtenerDato("lugar")]?$lugares[$this->obtenerDato("lugar")]:$this->obtenerDato("lugar"));
			$plantillaemail->replaced["{comentario}"] = ($this->obtenerDato("comentarios") ? $this->obtenerDato("comentarios") : " " );
			$plantillaemail->replaced["{accidente-baja}"] = ($this->obtenerDato("baja") ?'Si':'No' );
			$plantillaemail->replaced["{medidas-preventivas}"] = ($this->obtenerDato("medidas_preventivas") ? $this->obtenerDato("medidas_preventivas") : " " );

			$destinatariosPlantilla = $empleado->obtenerEmpresaContexto()->obtenerContactos($plantillaemail);
			if( is_traversable($destinatariosPlantilla) ){
				foreach($destinatariosPlantilla as $destinatarioPlantilla){
					if( $destinatarioPlantilla instanceof contactoempresa ){
						$destinatarios[] = $destinatarioPlantilla->obtenerDato("email");
					}
				}
			}
			if (is_traversable($destinatariosAdicionales)) {
				$destinatarios = array_merge($destinatarios,$destinatariosAdicionales);
			}
			$destinatarios = array_unique($destinatarios);
			if (CURRENT_ENV=='dev') $destinatarios = email::$developers;
			$email = new email($destinatarios);
			$email->enviardesdePlantilla($plantillaemail, $empleado->getCompany());
			$email->establecerAsunto(utf8_decode($asunto));

			if(!$estadoEnvio = $email->enviar()){
				$logEmail->info("citamedica","ERROR: Al enviar aviso ".$aviso." .Enviando aviso cita medica, al empleado" , $empleado->getUserVisibleName());
				return false;
			}

			$this->writeLogUI( logui::ACTION_AVISOEMAIL, implode(", ", $destinatarios) , $usuario);
			$logEmail->info("citamedica","Aviso cita medica, aviso ".$aviso, $empleado->getUserVisibleName());
			return $destinatarios;
		}

		public static function fieldTabs(Iusuario $usuario) {
			$tabs = new extendedArray();
			if ($usuario->accesoAccionConcreta('accidente', 4, null, 'vergeneral')) {
				$tabs[] = (object) array('name' => "general", "icon" => "famfam/group_go.png");
			}
			if ($usuario->accesoAccionConcreta('accidente', 4, null, 'verempresa')) {
				$tabs[] = (object) array('name' => "empresa", "icon" => "famfam/application_view_columns.png");
			}
			if ($usuario->accesoAccionConcreta('accidente', 4, null, 'verlugar')) {
				$tabs[] = (object) array('name' => "lugar", "icon" => "famfam/cup.png");
			}
			if ($usuario->accesoAccionConcreta('accidente', 4, null, 'veractores')) {
				$tabs[] = (object) array('name' => "actores", "icon" => "famfam/package_link.png");
			}
			if ($usuario->accesoAccionConcreta('accidente', 4, null, 'vereconomicos')) {
				$tabs[] = (object) array('name' => "economicos", "icon" => "famfam/package_link.png");
			}
			if ($usuario->accesoAccionConcreta('accidente', 4, null, 'verextra')) {
				$tabs[] = (object) array('name' => "extra", "icon" => "famfam/package_link.png");
			}
			if ($usuario->accesoAccionConcreta('accidente', 4, null, 'verasistencia')) {
				$tabs[] = (object) array('name' => "asistencia", "icon" => "famfam/user_add.png");
			}
			return $tabs;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false) {
			$fieldList = new FieldList();
			if ( !$usuario instanceof usuario && $modo != elemento::PUBLIFIELDS_MODE_DELTA) { 
				trigger_error("Debes especificar el usuario para poder crear Accidentes", E_USER_ERROR);
				return $fieldList;
			}

			switch( $modo ) {
				case elemento::PUBLIFIELDS_MODE_TAB:
					if ( $objeto instanceof self ) {
						$tpl = Plantilla::singleton();
						$lugares = self::getKindOfPlaces();
						$lugar = $tpl->getString($lugares[$objeto->obtenerDato("lugar")]);
						$fieldList["uid_empleado"] = new FormField(array("objeto" => "empleado"));
						$fieldList["lugar"] = new FormField( array('default' => 'Seleccionar', "nodb" => true, "innerHTML" => $lugar) );
					}
				break;
				case elemento::PUBLIFIELDS_MODE_INIT: case elemento::PUBLIFIELDS_MODE_NEW: default: 
					if ( $objeto instanceof empleado && $objeto->estaDeBaja() ) { 
						throw new Exception("error_empleado_baja");
					}
					$fieldList["baja"] = new FormField( array("tag" => "input", "type" => "checkbox") );
					$fieldList["fecha_baja"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "size" => "12", "date_format" => "%d/%m/%Y",  "placeholder" => "DD/MM/YYY", "depends" => "baja" ) );
					$fieldList["fecha_accidente"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "size" => "12", "date_format" => "%d/%m/%Y",  "placeholder" => "DD/MM/YYY" ) );
					$fieldList["hora_accidente"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false, "className" => "timepicker", "size" => "12", "placeholder" => "HH:MM", "date_format" => "%H:%M", "match" => "^[0-9]{2,2}\\:[0-9]{2,2}$" ));
					$fieldList["lugar"] = new FormField( array("tag" => "select","data" => self::getKindOfPlaces($objeto), "blank" => false) );

					if ( $modo == elemento::PUBLIFIELDS_MODE_NEW ) {
						$fieldList["uid_empleado"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false));
						$fieldList["uid_centrocotizacion"] = new FormField( array("blank" => false) );
					} else {
						if ( $objeto instanceof elemento ) {
							if ( $objeto instanceof empleado ) { 
								$centros = $objeto->obtenerEmpresaContexto()->obtenerCentroCotizacions();
							} elseif ( $objeto instanceof accidente ) {
								$centros = $objeto->obtenerEmpleado()->obtenerEmpresaContexto()->obtenerCentroCotizacions();
							} else {
								$centros = array();
							}
							$fieldList["uid_centrocotizacion"] = new FormField( array("tag" => "select", "data" => $centros, "className" => "datepicker", "size" => "15", "depends" => array("lugar", self::PLACE_OTROCENTRO), "blank" => false ) );
						}
					}
				break;
				case elemento::PUBLIFIELDS_MODE_EDIT:
					if ($tab == null) {
						$fieldList["estado"] = new FormField;
					}
					// $arrayReemplazar =  array('1'=>'Codigo 1','2' => 'Codigo 2');
					if ($tab == null || $tab->name == 'general') {
						$permisoGeneral = !!$usuario->accesoAccionConcreta('accidente', 4, null, 'general');
						$input = $permisoGeneral?'input':'span';
						$select = $permisoGeneral?'select':'span';
						$textarea = $permisoGeneral?'textarea':'span';
						$disabled = !$permisoGeneral;
						
						$fieldList['baja'] = new FormField( array("tag" => 'input', "type" => "checkbox", 'disabled' => $disabled));
						$fieldList['fecha_baja'] = new FormField( array("tag" => $input, "type" => "text", "blank" => false, "className" => "datepicker", "size" => "12", "date_format" => "%d/%m/%Y",  "placeholder" => "DD/MM/YYY", "depends" => "baja" ) );
						$fieldList['fecha_accidente'] = new FormField( array("tag" => $input, "type" => "text", "blank" => false, "className" => "datepicker", "size" => "12", "date_format" => "%d/%m/%Y",  "placeholder" => "DD/MM/YYY" ) );
						$fieldList['hora_accidente'] = new FormField( array("tag" => $input, "type" => "text", "blank" => false, "className" => "timepicker", "size" => "12", "placeholder" => "HH:MM", "date_format" => "%H:%M", "match" => "^[0-9]{2,2}\\:[0-9]{2,2}$" ));
						$fieldList['lugar'] = new FormField( array("tag" => $select,"data" => self::getKindOfPlaces($objeto), "blank" => false) );
						$fieldList['dia_semana'] = new FormField( array('tag' => $select, 'type' => 'text', 'default' => 'Seleccionar', 'data' => self::diasSemana()) );
						$fieldList['hora_jornada'] = new FormField( array('tag' => $select, 'type' => 'text', 'default' => 'Seleccionar', 'data' => empleado::obtenerHorasJornada()) );
						$fieldList['trabajo_habitual'] = new FormField( array('tag' => 'input', 'type' => 'checkbox', 'disabled' => $disabled) );
						$fieldList['evaluacion_riesgos'] = new FormField( array('tag' => 'input', 'type' => 'checkbox', 'disabled' => $disabled) );
						$fieldList['descripcion'] =  new FormField(array('tag' => $textarea ));
						$fieldList['tipo_trabajo'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['codigo_tipo_trabajo'] =  new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => tipotrabajo::obtenerTodos()) );
						$fieldList['actividad_fisica'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['codigo_actividad_fisica'] = new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => actividadfisica::obtenerTodos()) );
						$fieldList['codigo_agente_material_actividad'] = new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => agentematerial::obtenerTodos()) );
						$fieldList['desviacion'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['codigo_desviacion'] = new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => desviacion::obtenerTodos()) );
						$fieldList['codigo_agente_material_desviacion'] = new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => agentematerial::obtenerTodos()) );
						$fieldList['forma_lesion'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['codigo_forma_lesion'] = new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => lesion::obtenerTodos()) );
						$fieldList['agente_material_lesion'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['codigo_agente_material_lesion'] = new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => agentematerial::obtenerTodos()) );
						$fieldList['afecta_varios'] = new FormField( array('tag' => 'input', 'type' => 'checkbox', 'disabled' => $disabled) );
						$fieldList['testigos'] = new FormField( array('tag' => $input, 'type' => 'text') );
					}
					
					if ($tab == null || $tab->name == 'empresa') {
						$permisoEmpresa = !!$usuario->accesoAccionConcreta('accidente', 4, null, 'empresa');
						$disabled = !$permisoEmpresa;
						$fieldList['como_subcontrata'] = new FormField( array('tag' => 'input', 'type' => 'checkbox', 'disabled' => $disabled) );
						$fieldList['como_ett'] = new FormField( array('tag' => 'input', 'type' => 'checkbox', 'disabled' => $disabled) );
					}
					
					if ($tab == null || $tab->name == 'lugar') {
						$permisoLugar = !!$usuario->accesoAccionConcreta('accidente', 4, null, 'lugar');
						$input = $permisoLugar?'input':'span';
						$select = $permisoLugar?'select':'span';
						$textarea = $permisoLugar?'textarea':'span';
						$disabled = !$permisoLugar;
						$srcMunicipios = "m=accidente&field=municipio";
						if ( $objeto instanceof accidente ) { 
							$srcMunicipios .= "&poid={$objeto->getUID()}"; 
						}
						$fieldList['accidente_trafico'] = new FormField( array('tag' => 'input', 'type' => 'checkbox', 'disabled' => $disabled) );
						$fieldList['pais'] = new FormField(array('tag' => $select, 'default' => 'Seleccionar', 'data' => pais::obtenerTodos(), 'depends' => array('accidente_trafico', 1)));
						$fieldList['provincia'] = new FormField(array('tag' => $select, 'default' => 'Seleccionar', 'data' => provincia::obtenerTodos(), "depends" => array("pais", pais::SPAIN_CODE) ));
						$fieldList['municipio'] = new FormField(array('tag' => $select, 'default' => 'Seleccionar', 'async' => $srcMunicipios, 'data' => 'municipio::obtenerPorProvincia', 'depends' => 'provincia' ));
						$fieldList['direccion'] = new FormField(array('tag' => $input, 'type' => 'text'/*, 'depends' => array('accidente_trafico', 1)*/ ) );
						$fieldList['via_km'] = new FormField(array('tag' => $input, 'type' => 'text'/*, 'depends' => array('accidente_trafico', 1)*/) );
						$fieldList['comentarios'] = new FormField(array('tag' => $textarea/*, 'depends' => array('accidente_trafico', 1)*/));
						$fieldList['ccc_externo'] = new FormField( array('tag' => $input, 'type' => 'text') );
					}
					
					if ($tab == null || $tab->name == 'asistencia') {
						$permisoAsistencia = !!$usuario->accesoAccionConcreta('accidente', 4, null, 'asistencia');
						$input = $permisoAsistencia?'input':'span';
						$select = $permisoAsistencia?'select':'span';
						$disabled = !$permisoAsistencia;
						$fieldList['descripcion_lesion'] = new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => lesion::obtenerTodos()) );
						$fieldList['grado_lesion'] = new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => gradolesion::obtenerTodos()) );
						$fieldList['parte_cuerpo_lesion'] = new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => partelesionada::obtenerTodos()) );
						$fieldList['medico_asistencia'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['tipo_asistencia'] = new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => tipoasistencia::obtenerTodos()) );
						$fieldList['lugar_hospitalizacion'] = new FormField( array('tag' => $input, 'type' => 'text') );
					}
					
					if ($tab == null || $tab->name == 'actores') {
						$permisoActores = !!$usuario->accesoAccionConcreta('accidente', 4, null, 'actores');
						$input = $permisoActores?'input':'span';
						$select = $permisoActores?'select':'span';
						$fieldList['nombre_firmante_parte'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['cargo_firmante_parte'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['entidad_gestora'] = new FormField( array('tag' => $select, 'default' => 'Seleccionar', 'data' => mutua::obtenerTodos()) );
					}
					
					if ($tab == null || $tab->name == 'economicos') {
						$permisoEconomicos = !!$usuario->accesoAccionConcreta('accidente', 4, null, 'economicos');
						$input = $permisoEconomicos?'input':'span';
						$fieldList['base_cotizacion_ultimo_mes'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['dias_cotizados_ultimo_mes'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['base_reguladora_a'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['base_cotizacion_b1'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['otros_conceptos_b2'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['b1_b2'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['promedio_diario_base_cotizacion'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['base_reguladora_b'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['total_base_reguladora_diaria'] = new FormField( array('tag' => $input, 'type' => 'text') );
						$fieldList['cuantia_subsidio'] = new FormField( array('tag' => $input, 'type' => 'text') );
					}
					
					if ($tab == null || $tab->name == 'extra') {
						$permisoExtra = !!$usuario->accesoAccionConcreta('accidente', 4, null, 'economicos');
						$input = $permisoExtra?'input':'span';
						$select = $permisoExtra?'select':'span';
						$textarea = $permisoExtra?'textarea':'span';
						$fieldList['causa'] = new FormField(array('tag' => $select,'data' => causa::obtenerTodos()));
						$fieldList['medidas_preventivas'] = new FormField(array('tag' => $textarea ));
						$fieldList['codigo_referencia_delta'] = new FormField( array('tag' => $input, 'type' => 'text') );
					}
				break;
				case elemento::PUBLIFIELDS_MODE_DELTA:
					$camposAccidente = array('fecha_baja','dia_semana','hora_accidente','descripcion',
						'tipo_trabajo','codigo_tipo_trabajo','actividad_fisica','codigo_agente_material_actividad',
						'desviacion','codigo_desviacion','codigo_agente_material_desviacion','forma_lesion',
						'codigo_forma_lesion','agente_material_lesion','codigo_agente_material_lesion');
					$camposAsistencia = array('grado_lesion','descripcion_lesion','parte_cuerpo_lesion',
						'tipo_asistencia');
					$camposEconomicos = array('base_cotizacion_ultimo_mes','dias_cotizados_ultimo_mes',
						'base_reguladora_a','base_cotizacion_b1','otros_conceptos_b2','b1_b2',
						'promedio_diario_base_cotizacion','base_reguladora_b','total_base_reguladora_diaria',
						'cuantia_subsidio');
					$camposActores = array('cargo_firmante_parte','entidad_gestora');
					$fieldList = new FieldList(array_merge($camposAccidente,$camposAsistencia,$camposEconomicos,$camposActores));
				case elemento::PUBLIFIELDS_MODE_TRASH:
					//$fieldList["papelera"] = new FormField( array("tag" => "input", "type" => "radio" ));
				break;
			}
			return $fieldList;
		}
	}
	
	
