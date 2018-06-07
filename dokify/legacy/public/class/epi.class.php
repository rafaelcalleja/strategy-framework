<?php
	class epi extends elemento implements Ielemento, Iactivable {

		const ESTADO_SOLO_EXISTE = 0; /* VALOR POR DEFECTO DE LA TABLA, SIEMPRE DEBERÍA SER 24 (epi::ESTADO_ALMACEN), PERO ASI NOS ADAPTAMOS A MYSQL */
		const ESTADO_NO_UTIL_MANUAL = 1;/* UNICO ESTADO QUE ES MANUAL, EL RESTO SON CALCULADOS*/
		const ESTADO_NO_UTIL_FECHA = 4;
		const ESTADO_REVISION = 8;
		const ESTADO_PROXIMO_INUTIL = 12;
		const ESTADO_FUERA_REVISION = 16;
		const ESTADO_PROXIMO_REVISION = 20;
		const ESTADO_ALMACEN = 24;
		const ESTADO_ASIGNADO = 28;
		
		const INTERVALO_REVISION = 14; // dias para los que se considera que una revisión está próxima
		const INTERVALO_DURACION = 30; // dias para los que se considera que la duración se agota.

		public function __construct( $param, $extra = false ){
			$this->uid = $param;
			$this->tipo = "epi";
			$this->tabla = TABLE_EPI;

			$this->instance( $param, $extra );
		}

		public static function getRouteName () {
			return 'ppe';
		}

		public function enviarPapelera($parent, usuario $usuario){
			return $this->update( array("papelera" => "1"), elemento::PUBLIFIELDS_MODE_TRASH, $usuario);
		}

		public function restaurarPapelera($parent, usuario $usuario){
			return $this->update( array("papelera" => "0"), elemento::PUBLIFIELDS_MODE_TRASH, $usuario);
		}
		
		public function removeParent(elemento $parent, usuario $usuario = null) {
			return false;
		}

		public function isDeactivable($parent, usuario $usuario){
			return true;
		}

		public function obtenerElementosActivables(usuario $usuario = NULL){
			return new ArrayObjectList(array( $this->getCompany() ));
		}

		
		public function getLineClass($parent, $usuario){
			$class = "green";
			$estados = $this->obtenerEstado(true);

			if( in_array(epi::ESTADO_PROXIMO_REVISION, $estados) || in_array(epi::ESTADO_FUERA_REVISION, $estados) ){
				$class = "orange";
			}

			if( in_array(epi::ESTADO_REVISION, $estados) || in_array(epi::ESTADO_NO_UTIL_FECHA, $estados) || in_array(epi::ESTADO_NO_UTIL_MANUAL, $estados) ){
				$class = "red";
			}

			return "color {$class}";
		}


		public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = NULL, $extra = array(), $force = false){

			if( $publicMode ){
				if( $comeFrom === elemento::PUBLIFIELDS_MODE_TABLEDATA  ){
					$nombre = $this->getUserVisibleName();
					return array( $this->getUID() => array("nombre" => array(
						"class" => "box-it", 	
						"href" => "ficha.php?m=epi&oid=". $this->uid,
						"title" => $nombre,
						"innerHTML" => $nombre,
						"draggable-data" => "mover.php?poid={$this->uid}"
					)));

				} elseif( $comeFrom === elemento::PUBLIFIELDS_MODE_TAB ) {
					$data = array();
					$data["name"] = $this->getUserVisibleName();
					$data['normativa'] = $this->obtenerTipoepi()->obtenerDato("normativa");
					
					if ($caducidad = $this->obtenerFechaCaducidad()) {
						$data["fecha_caducidad"] = date('d-m-Y', $caducidad);
					}

					if ($revision = $this->obtenerFechaUltimaRevision()) {
						$data["revision"] = date('d-m-Y', $revision);
					}

					if ($this->isAssigned() && $entrega = strtotime($this->obtenerFechaEntrega(true))) {
						$data["entrega"] = date('d-m-Y', $entrega);
					}

					return array( $this->getUID() => $data);
				}
			}

			$info = parent::getInfo($publicMode, $comeFrom, $usuario, $extra, $force);

			return $info;
		}

		public function getInlineArray($usuario=false, $mode=null, $data){
			$inline = array();
			
			$inline[] = array(
				'img' => RESOURCES_DOMAIN . "/img/famfam/page_white_text_width.png",
				array('nombre' => $this->obtenerDato("nserie"), 'tagName' => 'span')
			);

			$inline[] = array(
				'tagName' => 'span',
				"img"	=> RESOURCES_DOMAIN . "/img/famfam/bullet_go.png",
				array(
					'nombre' => implode(', ',array_map('epi::status2string',($this->obtenerEstado(true)))),
					'tagName' => 'span'
				)
			);


			if ($date = $this->getDeliveryDate()) {
				$inline[] = array(
					"img"	=> RESOURCES_DOMAIN . "/img/famfam/date_go.png",
					array('nombre' => date('d-m-Y', $date), 'tagName' => 'span')
				);	
			}

			return $inline;
		}

		public function getUserVisibleName(){
			$tipoEPI = $this->obtenerTipoepi();
			return $tipoEPI->getUserVisibleName();
		}

		public function obtenerTipoepi(){
			$uid = $this->obtenerDato("uid_tipo_epi");
			return new tipo_epi($uid);
		}

		public function isAssigned(){
			$sql = "SELECT uid_epi FROM ". TABLE_EMPLEADO_EPI ." WHERE 1 AND uid_epi = {$this->getUID()}";
			if( $this->db->query($sql) ){
				return ($this->db->getNumRows())?true:false;
			}
		}

		/*
			$all (bool) = si quieres que te devuelva todos los posibles estados, no solo el de máxima prioridad.prefiero true o false
		*/

		public function obtenerEstado( $all = false ){
			$estados = array();
			if ( $all === true ) {
				// $estadoManual será solo ESTADO_SOLO_EXISTE o ESTADO_NO_UTIL_MANUAL
				$estadoManual = $this->obtenerDato("estado"); 
				if ( $estadoManual ) {
					$estados[] = $estadoManual;
				} else { // si esta puesto NO UTIL a mano, no calculamos el resto
					$estados[] = ( $this->isAssigned() ) ? epi::ESTADO_ASIGNADO : epi::ESTADO_ALMACEN;
					if ($this->estaCaducado()) {
						$estados[] = epi::ESTADO_NO_UTIL_FECHA;
					} else {						
						if ($this->caducidadProxima()) $estados[] = epi::ESTADO_PROXIMO_INUTIL;
						if ($this->revisionPendiente()) {
							$estados[] = epi::ESTADO_FUERA_REVISION;
						}
						else if ($this->revisionProxima()) {
							$estados[] = epi::ESTADO_PROXIMO_REVISION;
						}
					}					
				}
				return $estados;
			} else {
				return min($this->obtenerEstado(true));
			}
		}
		

		public function obtenerFechaFabricacion($humanReadable = false) {
			if ($fecha = $this->obtenerDato('fecha_fabricacion')) {
				return $humanReadable ? date('Y-m-d', $fecha) : $fecha;
			}
			return false;
		}

		public function obtenerFechaEntrega($humanReadable = false) {
			$SQL = "SELECT UNIX_TIMESTAMP(fecha_entrega) FROM ". TABLE_EMPLEADO . "_epi WHERE uid_epi = {$this->getUID()}";
			if ($fecha = $this->db->query($SQL, 0, 0)) {
				return $humanReadable ? date('Y-m-d',$fecha) : $fecha;
			}
			return false;
		}

		public function obtenerFechaAsignacion($humanReadable = false) {
			$SQL = "SELECT UNIX_TIMESTAMP(fecha_asignacion) FROM ". TABLE_EMPLEADO . "_epi WHERE uid_epi = {$this->getUID()}";
			if( $fecha = $this->db->query($SQL, 0, 0) ){
				return $humanReadable?date('Y-m-d',$fecha):$fecha;
			}
			return false;
		}

		public function obtenerFechaCaducidad($humanReadable = false) {
			$fechaFabricacion = $this->obtenerDato('fecha_fabricacion');
			$duracion = $this->obtenerTipoepi()->obtenerDato('duracion')*24*60*60;
			if ($fechaFabricacion && $duracion) {
				$fechaCaducidad = $fechaFabricacion + $duracion;
				return $humanReadable?date('Y-m-d',$fechaCaducidad):$fechaCaducidad;
			}
			return false;
		}
		
		public function obtenerFechaUltimaRevision($humanReadable = false) {
			$fechaUltimaRevision = $this->obtenerDato('fecha_ultima_revision');
			return $humanReadable?date('Y-m-d',$fechaUltimaRevision):$fechaUltimaRevision;
		}
		
		
		public function caducidadProxima($humanReadable = false) {
			$fechaCaducidad = $this->obtenerFechaCaducidad();
			$hoy = time();
			if ($fechaCaducidad && ($fechaCaducidad - $hoy < self::INTERVALO_DURACION * 24*60*60)) {
				return $humanReadable?date('Y-m-d',$fechaCaducidad):$fechaCaducidad;
			}
			return false;
		}
		
		public function listaRevisiones($humanReadable = false, $nextOnly = false) {
			$listaRevisiones = array();
			$fechaCaducidad = $this->obtenerFechaCaducidad();
			$revision = $this->obtenerTipoepi()->obtenerDato('revision'); // periodicidad en días de la revisión
			if(!$revision) return false;

			$ultimaRevision = ($last=$this->obtenerFechaUltimaRevision()) ? $last : $this->obtenerDato('fecha_fabricacion'); // formato unix timestamp
			$proximaRevision = $ultimaRevision + ($revision * 24*60*60);

			$max = 10;
			while ($proximaRevision < $fechaCaducidad && $max > 0) {
				$listaRevisiones[] = $humanReadable ? date('Y-m-d', $proximaRevision) : $proximaRevision;
				$max-=1;
				if( $nextOnly === true ) return $listaRevisiones[0];
				$proximaRevision += $revision*24*60*60;
			}

			return $listaRevisiones;
		}
		
		public function revisionProxima($humanReadable = false) {
			$revision = $this->listaRevisiones(false, true);
			$hoy = time();

			if( $revision ){
				$esFuturo = ($revision > $hoy);
				$transcurrido = $revision - $hoy;
				if( $esFuturo && $transcurrido < (self::INTERVALO_REVISION * 24*60*60) ){
					return $humanReadable ? date('Y-m-d',$revision) : $revision;
				}
			}

			return false;
		}
		
		public function moverAlmacen(){
			$sql = "DELETE FROM ".TABLE_EMPLEADO_EPI." WHERE uid_epi = {$this->getUID()}";
			$desasignacion =$this->db->query($sql);


			return $desasignacion ? true : false;
		}

		public function obtenerEmpleado(){
			$sql = "SELECT uid_empleado FROM ". TABLE_EMPLEADO_EPI. " WHERE uid_epi = {$this->uid}";
			$uid = db::get( $sql, 0, 0);	

			return ($uid && is_numeric($uid)) ? new empleado($uid) : false ;
		}

		public function getCompany(){
			return new empresa( $this->obtenerDato("uid_empresa") );
		}
		
		public function estaCaducado() {
			if ($fechaCaducidad = $this->obtenerFechaCaducidad()) {
				return ($fechaCaducidad < time());
			}
			return false;
		}
		
		public function revisionPendiente() {
			if ($proximaRevision = $this->listaRevisiones(false, true)) {
				return (bool) ($proximaRevision < time());
			}
			return false;
		}

		public function revisar( usuario $usuario ){
			/*11 =  estado "revision" nuevo para el epi que se desasigna al empleado*/
			// return $this->desasignacionEmpleadoEpi( ESTADO_REVISION );
			$fechaRevision = time();
			$sql = "UPDATE ".TABLE_EPI." SET fecha_ultima_revision = {$fechaRevision} WHERE uid_epi = ".$this->uid;
			$this->writeLogUI(logui::ACTION_REVISAR,$fechaRevision,$usuario);
			return $this->db->query($sql);
		}

		public static function estadosValidos(){
			return array(
				self::ESTADO_PROXIMO_INUTIL,
				self::ESTADO_PROXIMO_REVISION,
				self::ESTADO_ASIGNADO
			);
		}

		public static function getSearchData(Iusuario $usuario, $papelera = false){
			if (!$usuario->accesoModulo(__CLASS__)) return false;

			$limit = "uid_empresa IN (<%companies%>)";


			$searchData[ TABLE_EPI ] = array(
				"type" => "epi",
				"fields" => array("nserie", "( SELECT descripcion FROM ". TABLE_TIPO_EPI ." te WHERE te.uid_tipo_epi = epi.uid_tipo_epi )" ),
				"limit" => $limit,
				"accept" => array(
					"tipo" => "epi",
					"epi" => true,
					"uid" => true,
					"list" => true
				)
			);

			return $searchData;
		}

		public static function status2string($status){
			$template = Plantilla::singleton();
			return $template->getString("epi_estado_$status");
		}

		public static function defaultData($data, Iusuario $usuario = NULL) {
			if( $m = obtener_referencia() ){
				if( $m == "empresa" ){
					$data["uid_empresa"] = obtener_uid_seleccionado();
				} elseif( $m == "empleado" ){
					$uid = obtener_uid_seleccionado();
					$empleado = new empleado($uid);
					$empresas = $empleado->getCompanies();
					if( count($empresas) ){
						if( count($empresas) === 1 ){
							$data["uid_empresa"] = $empresas[0]->getUID();
						}
					} else {
						die("Inaccesible");
					}
				}
			} else {
				die("Inaccesible");
			}
			
			if( isset($data['fecha_fabricacion']) ){
				$data['fecha_fabricacion'] = $data['fecha_ultima_revision'] = documento::parseDate($data['fecha_fabricacion']);
			}	

			if( isset($data['uid_tipo_epi']) ){
				$tipoEpi = new tipo_epi($data['uid_tipo_epi']);
				if ($tipoEpi->obtenerDato('serial_number_required') && (!isset($data['nserie']) || $data['nserie']=='')) {
					throw new Exception("serial_number_required");
				}
			}
			
			return $data;
		}
		

		public function setDeliveryDate($date) {
			if ($employee = $this->obtenerEmpleado()) {
				$SQL = "UPDATE ". TABLE_EMPLEADO_EPI ." SET fecha_entrega = '". $date ."' WHERE uid_epi = {$this->getUID()} and uid_empleado = {$employee->getUID()}";
				return db::get($SQL);
			}

			return null;
		}

		public function getHTMLName() {
			$template = Plantilla::singleton();
			$fecha = $this->obtenerDato("fecha_fabricacion");
			$fechaFabricacion = $fecha ? date('d-m-Y',$fecha) : $template->getString("no_definido");
			$HTMLEpi = '<li>'.$this->getUserVisibleName();
			$HTMLEpi .=  " - ".$this->obtenerDato("nserie");
			$HTMLEpi .=  " - ".$template->getString("fecha_fabricacion").": ".$fechaFabricacion;
			$HTMLEpi .=  " - ".$template->getString("fecha_entrega").": ".date('d-m-Y',strtotime($this->obtenerDato("fecha_alta"))).'</li>';
			return $HTMLEpi;
		}

		public function getDeliveryDate() {
			if ($employee = $this->obtenerEmpleado()) {
				$SQL = "SELECT fecha_entrega FROM ". TABLE_EMPLEADO_EPI ." WHERE uid_epi = {$this->getUID()} and uid_empleado = {$employee->getUID()}";
				if ($date = db::get($SQL, 0, 0)) {
					if ($date && $date != '0000-00-00') return strtotime($date);
				}
			}

			return null;
		}

		public function updateData($data, Iusuario $usuario = NULL, $mode = NULL) {
			switch ($mode) {
				case elemento::PUBLIFIELDS_MODE_TRASH:
					# code...
					break;
				
				default:
					if (isset($data['fecha_fabricacion'])) {
						$data['fecha_fabricacion'] = $data['fecha_ultima_revision'] = documento::parseDate($data['fecha_fabricacion']);
					}

					if (isset($data['fecha_entrega']) && $employee = $this->obtenerEmpleado() && $data['fecha_entrega'] != '') {
						$timestamp = documento::parseDate($data['fecha_entrega']);
						if ($timestamp != 'error_fecha_incorrecta') $date = date('Y-m-d', $timestamp); else throw new Exception("error_fecha_entrega");

						if (isset($data['fecha_fabricacion']) && ($data['fecha_fabricacion'] != '') && ($data['fecha_fabricacion'] > $timestamp)) {
							throw new Exception("fecha_entrega_anterior_fabricacion", 1);
						} elseif ($manufacture = $this->obtenerFechaFabricacion()) {
							if ($manufacture > $timestamp) {
								throw new Exception("fecha_entrega_anterior_fabricacion", 1);
							}
						}


						$this->setDeliveryDate($date);

						// -- tell basic we already process this field
						$data['fecha_entrega'] = true;
					} elseif (obtener_referencia() == 'empleado' || $this->isAssigned()) {
						throw new Exception("error_fecha_entrega");
					}
					
					$tipoEpi = $this->obtenerTipoepi();
					if ($tipoEpi->obtenerDato('serial_number_required') && ((!isset($data['nserie']) || $data['nserie']=='')) && !isset($data['edit'])) {
						throw new Exception("serial_number_required");
					}
					
					break;
			}

			return $data;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList();

			switch ($modo) {
				case elemento::PUBLIFIELDS_MODE_TABLEDATA:
					$fieldList["nserie"] = 				new FormField( array("tag" => "input", "type" => "text"));
				break;
				case elemento::PUBLIFIELDS_MODE_EDIT: case elemento::PUBLIFIELDS_MODE_SEARCH:
					$fieldList["nserie"] = 				new FormField( array("tag" => "input", "type" => "text"));
					$fieldList["estado"] = 				new FormField( array("tag" => "input", "type" => "checkbox", "innerHTML" => "marcar_no_util", "className" => "iphone-checkbox"));
					$fieldList["fecha_fabricacion"] = 	new FormField( array("tag" => "input", "type" => "text", "className" => "datepicker", "date_format" => "%d/%m/%Y", "size" => "15" ));
			

					if ($objeto instanceof self && $employee = $objeto->obtenerEmpleado()) {
						$date = ($date = $objeto->getDeliveryDate()) > 0 ? $date : time();

						$fieldList["fecha_entrega"] = new FormField( array("tag" => "input", "type" => "text", "className" => "datepicker", "size" => "15", 'default' => date("d/m/Y", $date)));
					}
				break;
				default: case elemento::PUBLIFIELDS_MODE_NEW:
					$template = Plantilla::singleton();
					if( !$usuario instanceof usuario ){ throw new Exception("Debes especificar el usuario para poder crear EPIs"); }

					$tipos = $usuario->getCompany()->obtenerTipoepis();
					if( !count($tipos) ){ throw new Exception("tipoepis_sin_configurar"); }

					$fieldList["uid_tipo_epi"] = 		new FormField( array("tag" => "select", "data" => $tipos , "blank" => false, "search" => true));
					$fieldList["nserie"] = 				new FormField( array("tag" => "input", "type" => "text"));
					$fieldList["fecha_fabricacion"] = 	new FormField( array("tag" => "input", "type" => "text", "className" => "datepicker needconfirm", "dataConfirm" => $template->getString('alert_fecha_fabricacion'), "size" => "15"));

					if( $modo === elemento::PUBLIFIELDS_MODE_NEW ){
						$fieldList["uid_empresa"] = 	new FormField( array("tag" => "input", "type" => "text" , "blank" => false));
					}
				break;
				case elemento::PUBLIFIELDS_MODE_TRASH:
					$fieldList["papelera"] = 			new FormField( array("tag" => "input", "type" => "radio" ));
				break;
			}

			return $fieldList;
		}
		
		public function resumen($wrap = true, $headers = false) {
			if (!$wrap) {
				return array(
					'fecha_caducidad' => $this->obtenerFechaCaducidad(),
					'fecha_ultima_revision' => $this->obtenerFechaUltimaRevision(),
					'fecha_proxima_revision' => $this->obtenerFechaUltimaRevision() + self::INTERVALO_REVISION*24*60*60
				);
			}
			$template = Plantilla::singleton();
			$template->assign('elemento',$this);
			$template->assign('headers',$headers);
			$resumen = $template->getHTML('epi/resumen.tpl');
			if (!!$this->caducidadProxima() || !!$this->revisionProxima()) {
				return $resumen; 
			}
			return null;
		}

	}
