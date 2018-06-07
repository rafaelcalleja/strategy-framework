<?php
	class citamedica extends adjuntable implements Ielemento {

		//const ESTADO_PENDIENTE = 0;
		//const ESTADO_CONVOCADO = 1;
		//const ESTADO_REALIZADO = 2;
		const ESTADO_APTITUD_NO_VALORADA = 4;

		const AVISO_CITA = 1;//"citamedica";
		const AVISO_APTITUD = 2;//"aptitudmedica";

		const DIAS_PREVIOS_AVISO = 7;

		const CARACTER_OBLIGATORIO = "obligatorio";
		const CARACTER_OPCIONAL = "opcional";

		public function __construct( $param, $extra = false ){
			$this->uid = $param;
			$this->tipo = "citamedica";
			$this->tabla = TABLE_CITA_MEDICA;

			$this->instance( $param, $extra );
		}

		public function getUserVisibleName(){
			$start = $this->obtenerDato("fecha_cita");
			return $start;
		}


		public function getInlineArray(Iusuario $usuarioActivo = NULL, $mode = null , $data = false){
			$inlinearray = array();

			$tpl = Plantilla::singleton();

			if( $envios = $tpl->getString("numero_envios") ){
				$numeroEnvios = array();
					$numeroEnvios["img"] = RESOURCES_DOMAIN . "/img/famfam/email.png";
					$numeroEnvios["title"] = string_truncate($envios, 30);
					$numeroEnvios[] = array(
						'nombre' => ( is_numeric($this->obtenerNumeroAvisos()) ) ? $this->obtenerNumeroAvisos() : "0",
						'className' => 'help',
						'tagName' => 'span'
					);

				$inlinearray[] = $numeroEnvios;
		 	}
		
			$uidEstado = $this->obtenerDato("uid_estado");
			if( is_numeric($uidEstado) ){
				$estado = array();
					$estado["img"] = RESOURCES_DOMAIN . "/img/famfam/newspaper.png";
					$estado["title"] = $tpl->getString("uid_estado");
					$estado[] = array(
						'nombre' => $this->obtenerEstadoMutua($uidEstado),
						'className' => 'help',
						'tagName' => 'span'
					);
				$inlinearray[] = $estado;
			}

		
			$lugar = $this->obtenerDato("direccion");
			$direccion = array();
				$direccion["img"] = RESOURCES_DOMAIN . "/img/famfam/arrow_in.png";
				$direccion["title"] = $lugar;
				$direccion[] = array(
					'nombre' => string_truncate($lugar, 60),
					'tagName' => 'span'
				);
			$inlinearray[] = $direccion;
	 

			if( $text = trim($this->obtenerDato("comentario_interno")) ){
				$comentario = array();
					$comentario["img"] = RESOURCES_DOMAIN . "/img/famfam/comment.png";
					$comentario["title"] = $tpl->getString("comentario_interno");
					$comentario[] = array(
						'nombre' => $this->obtenerDato("comentario_interno"),
						'tagName' => 'span'
					);
				$inlinearray[] = $comentario;
			}


			return $inlinearray;
		}


		public function obtenerNumeroAvisos(){
			return $this->getLogUI( logui::ACTION_AVISOEMAIL, true);
		}

		public function obtenerEstado(){
			return $this->obtenerDato("uid_estado");
		}

		public function getLineClass(Ielemento $parent = NULL, Iusuario $usuario = NULL){
			$class = array("color");

	
			$whitelist = array(1,4,2,5,7,9,14,16,17);
			if( in_array($this->obtenerEstado(), $whitelist) ){
				$class[] = "green";
			} else {
				$class[] = "red";
			}

			return implode(" ", $class);
		}
		
		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
			$data = array();

			//$data[] = $this->obtenerEmpleado()->getUserVisibleName();
			if( ($start = strtotime($this->obtenerDato("fecha_cita"))) && $hour = $this->getHour() ){
				$data[] = array(
					"innerHTML" => date("d/m/Y", $start) . " $hour",
					"class" => "box-it",
					"href" => "../agd/ficha.php?m=". get_class($this) . "&poid={$this->getUID()}"
				);
			}
			
			return array($this->getUID() => $data);
		}

		public function getHour(){
			if( ($hour = $this->obtenerDato("hora_cita")) && $hour != "00:00:00" ){
				$parts = explode(":", $hour);
				array_pop($parts);
				return implode(":", $parts);
			}
			return false;
		}

		public function getTreeData(){
			$img = $imgopen = RESOURCES_DOMAIN . "/img/famfam/time.png";
			return array(
				"checkbox" => true,
				"img" => array("normal" => $img, "open" => $imgopen),
			);
		}

		public function obtenerEmpleado(){
			return new empleado($this->obtenerDato("uid_empleado"));
		}

		public function obtenerConvocatoriaMedica(){
			return new convocatoriamedica($this->obtenerDato("uid_convocatoriamedica"));
		}

		public static function defaultData($data, Iusuario $usuario = NULL) {
			/*if( isset($data["poid"]) ){
				$convocatoria = new convocatoriamedica($data["poid"]);
				$empleado = $convocatoria->obtenerEmpleado();
				$data["uid_convocatoriamedica"] = $convocatoria->getUID();
				$data["uid_empleado"] = $empleado->getUID();
			}*/	

			if( isset($data["fecha_cita"]) ){
				$aux = explode("/", $data["fecha_cita"]);
				if( count($aux) === 3 ){
					$data["fecha_cita"] = $aux[2]."-".$aux[1]."-".$aux[0] . " 00:00:00";

					$time = strtotime($data["fecha_cita"]);
					if( $time < time() ){
						throw new Exception("error_fecha_antigua");
					}
				} else {
					unset($data["fecha_cita"]);
				}
			}

			if( isset($data["poid"]) ){
				if($data["comefrom"] == "empleado"){
					$empleado = new empleado($data["poid"]);
					$convocatoria = new convocatoriamedica( array("fecha_creacion" => $data["fecha_cita"], "uid_empleado" => $empleado->getUID()), $usuario );
				} elseif ($data["comefrom"] == "convocatoriamedica"){
					$convocatoria = new convocatoriamedica($data["poid"]);
					$empleado = $convocatoria->obtenerEmpleado();
				}
			}


			$data["uid_convocatoriamedica"] = $convocatoria->getUID();
			$data["uid_empleado"] = $empleado->getUID();


			if ($solicitudes = $empleado->obtenerSolicitudDocumentos($usuario, array('modulo_salud' => 1))) {
				$atributos = $solicitudes->foreachCall('obtenerDocumentoAtributo')->unique();
				$list = $atributos->toIntList();

				$SQL = "SELECT min(duracion) FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_documento_atributo IN ({$list})";
				$minimo = db::get($SQL, 0, 0);
				if (is_numeric($minimo)) $data["duracion"] = $minimo;

				$SQL = "SELECT obligatorio FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_documento_atributo IN ({$list}) AND obligatorio = 1 LIMIT 1";
				$obligatorio = db::get($SQL, 0, 0);
				if ($obligatorio) $data["obligatorio"] = $obligatorio;
			}
		


			if( isset($data["hora_cita"]) ){
				$string = str_replace(":", "", $data["hora_cita"]);
				$int = (int) $string;
				if( !$int || strlen($string) !== 4 ){
					throw new Exception("hora_no_valida");
				} else {
					$data["hora_cita"] = $data["hora_cita"] . ":00";
				}
			}

			//La fecha de la citamedica no puede ser inferior a la del Item de la cita
			if( $data["fecha_cita"] < $convocatoria->obtenerDato("fecha_creacion") ){
				throw new Exception("error_fecha_antigua_citamedica");
			}


			
			

			return $data;
		}

		public function updateData($data, Iusuario $usuario = NULL, $mode = NULL) {
			if( isset($data["poid"]) ){
				$citamedica = new citamedica($data["poid"]);
				$convocatoriamedica = new convocatoriamedica( $citamedica->obtenerDato("uid_convocatoriamedica") );
			}

			$fechaConvocatoriamedica = $convocatoriamedica->obtenerDato("fecha_creacion");

			if( isset($data["fecha_cita"]) && strlen($data["fecha_cita"]) ){
				$aux = explode("/", $data["fecha_cita"]);
			
				// No verificamos la fecha al actualizar ya que si no, no nos dejaria cambiar registros antiguos, verificamos solo si es fecha
				if( count($aux) !== 3 ){
					throw new Exception("error_fecha_incorrecta");
				}
			}


			//La fecha de la citamedica no puede ser inferior a la del Item de la cita
			if( documento::parseDate($data["fecha_cita"]) < strtotime($fechaConvocatoriamedica) ){
				throw new Exception("error_fecha_antigua_citamedica");
			}

			if( $citamedica ){
				$info = $citamedica->getInfo();
				if( $info["fecha_cita"]." 00:00:00" === $data["fecha_cita"] ) unset($data["fecha_cita"]);
				if( $info["hora_cita"] === $data["hora_cita"].":00" ) unset($data["hora_cita"]);
			}

			return $data;
		}

		public function obtenerEstadoMutua($uidEstado = false){
			if ($this instanceof citamedica) {
				$uidEstado = $this->obtenerEstado();
				$estado = self::obtenerEstadosMutua($this->isMandatory(), $uidEstado);

				/*$lang = Plantilla::singleton();

				switch ($uidEstado){
					case self::ESTADO_APTITUD_NO_VALORADA:
						$obligatorio = (bool) $this->obtenerDato("obligatorio");

						if ($obligatorio) {
							$estado .= ", " . $lang("siendo_no_apto");
						} else {
							$estado .= ", " . $lang("siendo_apto");
						}
					break;
				}*/

				return $estado;
			}
		}

		public static function obtenerEstadosMutua($mandatory, $uidEstado=false){
			$estados = array (	"0"	=> "Pendiente",
								"1" => "Apto",
								"2"	=> "No apto medicamente",
								"3" => "No apto por no asistencia",
								self::ESTADO_APTITUD_NO_VALORADA => "Aptitud no valorada por falta de asistencia",
								"5" => "Apto con restricciones",
								"6" => "Pendiente de resultados aptitud",
								"7" => "Renuncia",
								//"8" => "Renuncia B",
								"9" => "Anulado por PRL",
								"10"=> "Aptitud no valorada por falta de pruebas",
								"11"=> "Baja por enfermedad",
								"12"=> "Baja por maternidad",
								"13"=> "Excedencia",
								"14"=> "Trabajador sindical liberado",
								"15"=> "Solicita cambio de cita",
								"16"=> "Apto con Observaciones",
								"17"=> "No acude a cita"
						);

			if (is_bool($mandatory)) {
				if ($mandatory) {
					unset($estados[self::ESTADO_APTITUD_NO_VALORADA]);
				} else {
					unset($estados['3']);
				}
			}

			// Queremos conocer el texto de un estado definido
			if (is_numeric($uidEstado)) {
				if (isset($estados[$uidEstado])) return $estados[$uidEstado]; 
				return "Por definir";
			}

			return $estados;	
		}

		public function obtenerAvisos(Iusuario $usuario){
			$accionData = $usuario->accesoAccionConcreta(get_class($this), "Avisos");
			if( count($accionData) ){
				return array(
					array(	
						"innerHTML" => "citamedica_aviso_cita", 
						"string" => "citamedica_aviso_cita", 
						"href" => $accionData["href"]. get_concat_char($accionData["href"]) . "oid=".$this->getUID()."&aviso=".self::AVISO_CITA,
						"img" => RESOURCES_DOMAIN . "/img/famfam/bell_go.png",
						"className" => "to-iframe-box" 
					),
					array(	
						"innerHTML" => "citamedica_aviso_aptitud", 
						"string" => "citamedica_aviso_aptitud", 
						"href" => $accionData["href"]. get_concat_char($accionData["href"]) . "oid=".$this->getUID()."&aviso=".self::AVISO_APTITUD,
						"img" => RESOURCES_DOMAIN . "/img/famfam/rosette.png",
						"className" => "to-iframe-box" 
					)
				);
			}
		}

		public function obtenerObligatoriedad(){
			//Documentos atributo de salud y obligatorios para el elemento referenciado
			$documentosObligatorios = $this->obtenerEmpleado()->getDocumentsId(0, true, false, array("modulo_salud" => "1"));

			if( count($documentosObligatorios) && is_traversable($documentosObligatorios) ){
				return self::CARACTER_OBLIGATORIO; 
			}

			return self::CARACTER_OPCIONAL;
		}

		/**
		  * Nos devolverá el número de cita que representa dentro de una convocatoria
		  */
		public function getIndex(){
			$empleado = $this->obtenerEmpleado();
			$convocatoria = $this->obtenerConvocatoriaMedica();

			$sql = "SELECT count(uid_citamedica) FROM {$this->tabla} WHERE 1 
				AND uid_empleado = {$empleado->getUID()} 
				AND uid_convocatoriamedica = {$convocatoria->getUID()}
				AND uid_citamedica < {$this->getUID()}
			";

			$num = $this->db->query($sql, 0, 0);

			return $num+1;
		}

		public function getTimestamp(){
			return strtotime( $this->obtenerDato("fecha_cita") . " " . $this->obtenerDato("hora_cita") );
		}

		public function sendEmailInfo(Iusuario $usuario = NULL, $recordatorio = false, $aviso){
			$logEmail = new log();
			$lang = Plantilla::singleton();
			$empleado = $this->obtenerEmpleado();
			$empresa = $empleado->obtenerEmpresaContexto();


			if (!$empresa) return false;


			$estado = $this->obtenerEstadoMutua();
			$status = $this->obtenerEstado();

			switch($aviso){
				case citamedica::AVISO_CITA:
					$plantillaemail = plantillaemail::instanciar("citamedica");

					$time = $this->getTimestamp();

					$citaCaracter= $lang->getString("reconocimiento_" . $this->obtenerObligatoriedad());
					$plantillaemail->replaced["{%elemento-nombre%}"] = $empleado->obtenerDato("nombre")." ".$empleado->obtenerDato("apellidos");
					$plantillaemail->replaced["{%evento-fecha%}"] = date("d-m-Y", $time);
					$plantillaemail->replaced["{%evento-hora%}"] = date("h:i", $time);
					$plantillaemail->replaced["{%direccion%}"] = $this->obtenerDato("direccion");
					$plantillaemail->replaced["{%evento-estado%}"] = $estado;
					$plantillaemail->replaced["{%comentario%}"] = ($this->obtenerDato("comentario_empleado") ? $this->obtenerDato("comentario_empleado") : " " );
					$plantillaemail->replaced["{%documento-obligatoriedad%}"] = $citaCaracter;
			
			
					$index = $this->getIndex();
					$asunto = $defaultAsunto = "{$index}ª Cita para reconocimiento médico. {$lang->getString("caracter")} $citaCaracter. {$empleado->getUserVisibleName()}";


					if( $recordatorio ){
						$asunto = "{$defaultAsunto} (RECORDATORIO)";
					} elseif( $this->getLogUI(logui::ACTION_AVISOEMAIL) ){
						$asunto = "{$defaultAsunto} (ACTUALIZADO)";
					}
					
				break;
				case citamedica::AVISO_APTITUD:
					$plantillaemail = plantillaemail::instanciar( "aptitudmedica" );
					$plantillaemail->replaced["{%elemento-nombre%}"] = $empleado->obtenerDato("nombre")." ".$empleado->obtenerDato("apellidos");
					$plantillaemail->replaced["{%evento-fecha%}"] = $this->obtenerDato("fecha_cita");
					$plantillaemail->replaced["{%evento-hora%}"] = $this->obtenerDato("hora_cita");
					$plantillaemail->replaced["{%direccion%}"] = $this->obtenerDato("direccion");
					$plantillaemail->replaced["{%evento-estado%}"] = $estado;
					$plantillaemail->replaced["{%comentario%}"] = ($this->obtenerDato("comentario_empleado") ? $this->obtenerDato("comentario_empleado") : " " );
					$plantillaemail->replaced["{%documento-obligatoriedad%}"] = $lang->getString( $this->obtenerObligatoriedad() );	

					$asunto = "{$empleado->getUserVisibleName()}. Disponible el resultado de su cita médica";
				break;	
				default:
					$logEmail->info("citamedica","ERROR: No se encuentra la plantilla. Enviando aviso al empleado" , $empleado->getUserVisibleName());
					return false;
				break;							
			}



			$html = $plantillaemail->getFileContent($empresa, true);
			$tpl = new Plantilla();

			$tpl->assign('status', $status);
			$body = $tpl->parseHTML($html);
			$body = utf8_decode($body);


			// --- CALCULAR DESTINATARIOS
			$destinatarios = array();

			if ($emailEmpleado = $empleado->obtenerDato("email")) {
				$destinatarios = array($emailEmpleado);
			}

			// --- contactos de la empresa que quieran recibir estos avisos			
			$destinatariosPlantilla = $empresa->obtenerContactos($plantillaemail);
			if (is_traversable($destinatariosPlantilla)) {
				foreach($destinatariosPlantilla as $destinatarioPlantilla) {
					if ($destinatarioPlantilla instanceof contactoempresa) {
						$destinatarios[] = $destinatarioPlantilla->obtenerDato("email");
					}
				}
			}

			// --- manager y secretaria del empleado, si los hubiera
			if (($unit = $empleado->getUnitWork()) && ($manager = $unit->getManager($empleado))) {
				if ($emailResponsable = $manager->obtenerDato("email")) {
					$destinatarios[] = $emailResponsable;
				}

				if ($emailSecretaria = $manager->obtenerDato("email_secretaria")) {
					$destinatarios[] = $emailSecretaria;
				}
			}

			// --- no enviar duplicados
			$destinatarios = array_unique($destinatarios);

			// --- prevenir test
			if (CURRENT_ENV=='dev') $destinatarios = email::$developers;

			$email = new email($destinatarios);
			$email->establecerContenido($body);
			$email->establecerAsunto(utf8_decode($asunto));
			
			$convocatoria = $this->obtenerConvocatoriaMedica();
			//Obtenemos las citas médicas y quitamos la actual
			$appointments = $convocatoria->obtenerCitamedicas()->unique();
			$emailhistorico = false;
			if ($count = count($appointments)) {
				//Sascamos la última enviada
				foreach ($appointments as $appointment) {
					if ($this->getUID() > $appointment->getUID()) {
						// Solo si la que enviamos es mayor que las que hay (tenemos historico).
						$historicEmail = $email->getRelated($appointment->getModuleId(), $appointment->getUID());
						if ($historicEmail) {
							$emailhistorico =  $historicEmail;
						}
					}
				}
			}
			if ($emailhistorico) {
				//Añadimos el historico desde plantilla.
				$plantilla = new Plantilla();
				$plantilla->assign("emailhistorico", $emailhistorico);
				$plantilla->assign("from", $email->getFrom());
				$contenidoHistorico = $plantilla->getHTML('email/historicoCitas.tpl');
				$email->establecerContenido($contenidoHistorico, true);
			}

			if (($estadoEnvio = $email->enviar()) !== true) {
				$logEmail->info("citamedica","ERROR: Al enviar aviso ".$aviso." .Enviando aviso cita medica, al empleado" , $empleado->getUserVisibleName());
				$email->saveLog($this->getModuleId(), $this->getUID(), email::STATUS_ERROR);
				return $estadoEnvio;
			}

			$email->saveLog($this->getModuleId(), $this->getUID(), email::STATUS_OK);
			$this->writeLogUI( logui::ACTION_AVISOEMAIL, implode(", ", $destinatarios) , $usuario);
			$logEmail->info("citamedica","Aviso cita medica, aviso ".$aviso, $empleado->getUserVisibleName());
			return $destinatarios;
		}


		public function obtenercallBack(Iusuario $usuario){
			$accionData = $usuario->accesoAccionConcreta(get_class($this), "Avisos");
			if( $accionData ){
				return array(
					"acciones" => array(
						array(	
							"string" => "citamedica_aviso_cita", 
							"href" => $accionData["href"]. get_concat_char($accionData["href"]) . "oid=".$this->getUID()
						)
					)
				);
			}
		}

		public function obtenerDireccionesReconocimiento(){
			return $this->obtenerEmpleado()->obtenerDireccionesReconocimiento();
		}

		public function isMandatory () {
			return (bool) $this->obtenerDato('obligatorio');
		}
		
		public static function export(Iusuario $usuario, empresa $empresa){
			$db = db::singleton();
			$empresaCliente = $usuario->getCompany();
			$tzSecondsOffset = $usuario instanceof usuario ? (3600 * $usuario->getTimezoneOffset()): 0;

			$empresasSolicitantes = new ArrayObjectList();
			if ( $corp = $empresaCliente->perteneceCorporacion() ){
				$empresasSolicitantes[] = $corp->getUID();
				$empresasSolicitantes[] = $empresaCliente->getUID();
			}else{
				$empresasSolicitantes[] = $empresaCliente->getUID();
			}

			$sql = "SELECT max(uid_convocatoriamedica) FROM ". TABLE_CONVOCATORIA_MEDICA ." WHERE uid_empleado IN (
				SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa WHERE uid_empresa = {$empresa->getUID()} AND papelera = 0
			) GROUP BY uid_empleado ";
			//dump($sql);exit;
			$convocatorias = $db->query($sql, "*", 0);
			if( !count($convocatorias) ) die("Error, no hay convocatorias");
			$list = implode(",", $convocatorias);
	

			$sql = "SELECT count(uid_convocatoriamedica) FROM ". TABLE_CITA_MEDICA ." WHERE uid_convocatoriamedica IN ($list) GROUP BY uid_convocatoriamedica ORDER BY count(uid_convocatoriamedica) DESC LIMIT 1";
			$max = $db->query($sql, 0, 0);


			$sql = "SELECT uid_documento, GROUP_CONCAT(uid_documento_atributo) as list, duracion FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE modulo_salud = 1 
			AND uid_empresa_propietaria IN ({$empresasSolicitantes}) AND replica = 0 AND descargar = 0 AND uid_modulo_destino = 8 GROUP BY uid_documento, duracion ORDER BY count(*) DESC LIMIT 2";
			$docsINFO = $db->query($sql, true);

			
			
			$alturas = false;
			if( $docsINFO && count($docsINFO) ){
				$UIDDocumento = $docsINFO[0]["uid_documento"];
				$normales =  $docsINFO[0]["list"];

				if( count($docsINFO) > 1 ){
					$alturas =  $docsINFO[1]["list"];
					if ( $docsINFO[1]['duracion'] != 365 ){
						$normales = $alturas;
						$alturas = $docsINFO[0]["list"];
					}
				}else{
					$alturas = "0";
				}

			} else {
				die("NO HAY DOCUMENTO DE RECONOCIMIENTO MEDICO");
			}


			$sql = "SELECT sa.uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ." sa WHERE uid_documento = $UIDDocumento	AND uid_empresa_propietaria IN ({$empresasSolicitantes}) AND descargar = 0 AND activo = 1 AND replica = 0";

			$doclist = $db->query($sql, "*", 0);
			if( !$doclist || !count($doclist) ) die("NO HAY DOCUMENTO DE RECONOCIMIENTO MEDICO");
			$doclist = implode(",", $doclist);
		


			$isAlturas = "(SELECT count(*) FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." de WHERE uid_modulo_destino = 8 AND uid_elemento_destino = uid_empleado AND uid_documento_atributo IN ($alturas))";
			$isNormal = "(SELECT count(*) FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." de WHERE uid_modulo_destino = 8 AND uid_elemento_destino = uid_empleado AND uid_documento_atributo IN ($normales))";

			$campos = array();
			//$campos[] = "uid_empleado Id";
			$campos[] = "DNI as NIF";
			$campos[] = "alias as 'Alias'";
			$campos[] = "Apellidos";
			$campos[] = "empleado.Nombre";
			$campos[] = "(SELECT GROUP_CONCAT(empresa.nombre) FROM ". TABLE_EMPRESA ." INNER JOIN ". TABLE_EMPLEADO ."_empresa ee USING(uid_empresa) WHERE ee.uid_empleado = empleado.uid_empleado AND papelera = 0 GROUP BY uid_empleado LIMIT 1) as Empresa";
			$campos[] = "empleado.numero_empleado as 'Numero empleado'";
			$campos[] = "empleado.sap as 'SAP'";

			$campos[] = "empleado.descripcion_puesto as 'Puesto'";
			$campos[] = "empleado.fecha_alta_empresa as 'Fecha Alta'";
			$campos[] = "empleado.fecha_nacimiento as 'Fecha Nacimiento'";
			$campos[] = "if(empleado.es_manager = 1, 'Si', 'No') as 'Es Manager'";
			$campos[] = "empleado.email_secretaria as 'Email Secretaria'";
			$campos[] = "empleado.ceco as 'Ceco'";
			$campos[] = "empleado.delegado_prevencion as 'Delegado Prevencion'";
			//$campos[] = "empleado.assistant as 'Es Secretaria'";

			$campos[] = "(SELECT nombre FROM ". TABLE_CENTRO_COTIZACION . " c WHERE c.uid_centrocotizacion = empleado.uid_centrocotizacion ) as 'CCC'";
			$campos[] = "empleado.numero_seguridad_social as 'Seguridad Social'";
			$campos[] = "(SELECT nombre FROM ". TABLE_PROVINCIA . " p WHERE p.uid_provincia = empleado.uid_provincia  ) as 'Provincia'";
			$campos[] = "(SELECT nombre FROM ". TABLE_MUNICIPIO . " p WHERE p.uid_municipio = empleado.uid_municipio  ) as 'Ciudad'";
			$campos[] = "(SELECT nombre FROM ". TABLE_AGRUPADOR . " a WHERE a.uid_agrupador = empleado.uid_agrupador_cliente ) as 'Cedido Cliente'";
			$campos[] = "empleado.telefono";
			$campos[] = "empleado.email";

			$campos[] = "empleado.edificio";
			$campos[] = "empleado.planta_modulo as 'Planta-Modulo'";
			$campos[] = "unitwork as 'Unidad'";
			//$campos[] = "responsable as 'Responsable'";
			$campos[] = "( SELECT concat(nombre, ' ', apellidos) FROM ". TABLE_EMPLEADO ." e WHERE e.uid_empleado = empleado.uid_responsable ) as 'Responsable'";


			
			$duracionMeses = "( SELECT FLOOR(min(duracion)/30)
				FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." de
				LEFT JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." da
				USING(uid_documento_atributo, uid_modulo_destino) 
				WHERE de.uid_modulo_destino = 8 AND de.uid_elemento_destino = empleado.uid_empleado
				AND da.uid_documento = $UIDDocumento AND duracion
				GROUP BY uid_documento
			)";

			$campos[] = "$duracionMeses as 'Caducidad'";

		
			$campos[] = "if($isNormal,'X','') as 'Protocolo A'";
			$campos[] = "if($isAlturas,'X','') as 'Protocolo B'";
			

			if( $alturas ){
				$campos[] = "if($isAlturas, 'X', '') as 'Alturas'";
			}

			$comentarios = "(
				SELECT comentario FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." de 
				LEFT JOIN ". PREFIJO_ANEXOS ."empleado a
				ON a.uid_empleado = de.uid_elemento_destino AND a.uid_agrupador = de.uid_agrupador AND a.uid_empresa_referencia = de.uid_empresa_referencia AND a.uid_documento_atributo = de.uid_documento_atributo
				LEFT JOIN ". TABLE_DOCUMENTO_ATRIBUTO ."_comentario c 
				ON de.uid_elemento_destino = c.uid_elemento AND de.uid_documento_atributo = c.uid_documento_atributo
				WHERE de.uid_elemento_destino = empleado.uid_empleado AND c.uid_modulo = 8 AND c.uid_documento_atributo IN ($doclist) ORDER BY fecha DESC LIMIT 1
			)";
			$campos[] = "$comentarios as 'Comentario/Acude'";

			// AptoNoApto as 'Apto/No Apto',


			$fechaemision = "(SELECT FROM_UNIXTIME(fecha_emision-$tzSecondsOffset) FROM ". PREFIJO_ANEXOS ."empleado a INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." de
			ON a.uid_empleado = de.uid_elemento_destino 
			AND a.uid_agrupador = de.uid_agrupador 
			AND a.uid_empresa_referencia = de.uid_empresa_referencia 
			AND a.uid_documento_atributo = de.uid_documento_atributo
			AND de.uid_modulo_destino = 8
			WHERE a.uid_documento_atributo IN ($doclist) AND a.uid_empleado = empleado.uid_empleado ORDER BY fecha_emision DESC LIMIT 1)";


			$campos[] = "DATE_FORMAT($fechaemision,'%d/%m/%Y') AS 'Fecha Reconocimiento'";


			$campos[] = "DATE_FORMAT(ADDDATE($fechaemision, INTERVAL $duracionMeses MONTH),'%d/%m/%Y') as 'Fecha Caducidad'";
			$campos[] = "DATE_FORMAT(ADDDATE($fechaemision, INTERVAL $duracionMeses MONTH),'%M - %y') as 'Mes Caducidad'";
			$campos[] = "IF( ADDDATE($fechaemision, INTERVAL $duracionMeses MONTH ) > CURRENT_TIMESTAMP, 'Correcto', 'Caducado') as 'Estado Documento'";

			if( $alturas ){
				$campos[] = "if($isAlturas, if($isNormal, 'A+B', 'A'), 'B') as 'Protocolo Documento'";
			} else {
				$campos[] = "'A' as 'Protocolo Documento'";
			}

			$fechaexpiracion = "(SELECT if(fecha_expiracion, FROM_UNIXTIME(fecha_expiracion), 0) FROM ". PREFIJO_ANEXOS ."empleado a INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." de
			ON a.uid_empleado = de.uid_elemento_destino AND de.uid_modulo_destino = 8 AND a.uid_agrupador = de.uid_agrupador AND a.uid_empresa_referencia = de.uid_empresa_referencia AND a.uid_documento_atributo = de.uid_documento_atributo
			WHERE a.uid_documento_atributo IN ($doclist) AND a.uid_empleado = empleado.uid_empleado AND fecha_expiracion ORDER BY fecha_expiracion ASC LIMIT 1)";

			$campos[] = "if( $fechaemision, DATE_FORMAT(ADDDATE($fechaemision, INTERVAL $duracionMeses MONTH),'%d/%m/%Y'), '') as 'Caducidad Documento'";



			$sql = "SELECT uid_agrupador FROM ". TABLE_AGRUPADOR . " 
				INNER JOIN ". TABLE_AGRUPAMIENTO ." agrupamiento USING(uid_agrupamiento)
				WHERE agrupamiento.uid_empresa IN ({$empresasSolicitantes}) 
				AND agrupamiento.nombre LIKE '%riesgo%'
			";

			$agrupadores = $db->query($sql, "*", 0, "agrupador");
			foreach($agrupadores as $agrupador){
				$asignado = "(SELECT count(*) FROM ". TABLE_AGRUPADOR ."_elemento WHERE uid_modulo = 8 AND uid_elemento = empleado.uid_empleado AND uid_agrupador = {$agrupador->getUID()})";
				$campos[] = "if($asignado, 'X', '') as '". utf8_decode($agrupador->getUserVisibleName()) ."'";
			}



			$estados = self::obtenerEstadosMutua();
			$columnSQLEstado = "CASE uid_estado";
			foreach($estados as $cod => $string){
				$columnSQLEstado .= " WHEN $cod THEN '". ($string) ."' ";
			}
			$columnSQLEstado .= " ELSE '' END";

			for($i=0;$i<$max;$i++){
				$num = $i+1;

				$campos[] = "(SELECT DATE_FORMAT(fecha_cita,'%d/%m/%Y') FROM ". TABLE_CITA_MEDICA ." WHERE uid_empleado = empleado.uid_empleado AND uid_convocatoriamedica IN (". implode(",",$convocatorias) .") ORDER BY uid_citamedica ASC LIMIT $i,1) as 'Fecha Cita $num'";
				$campos[] = "(SELECT TIME_FORMAT(hora_cita,'%h:%i') FROM ". TABLE_CITA_MEDICA ." WHERE uid_empleado = empleado.uid_empleado AND uid_convocatoriamedica IN (". implode(",",$convocatorias) .")  ORDER BY uid_citamedica ASC LIMIT $i,1) as 'Hora Cita $num'";
				$campos[] = "(SELECT direccion FROM ". TABLE_CITA_MEDICA ." WHERE uid_empleado = empleado.uid_empleado  AND uid_convocatoriamedica IN (". implode(",",$convocatorias) .") ORDER BY uid_citamedica ASC LIMIT $i,1) as 'Direccion Cita $num'";
				$campos[] = "(SELECT comentario_interno FROM ". TABLE_CITA_MEDICA ." WHERE uid_empleado = empleado.uid_empleado  AND uid_convocatoriamedica IN (". implode(",",$convocatorias) .") ORDER BY uid_citamedica ASC LIMIT $i,1) as 'Comentario Interno Cita $num'";
				$campos[] = "(SELECT comentario_empleado FROM ". TABLE_CITA_MEDICA ." WHERE uid_empleado = empleado.uid_empleado  AND uid_convocatoriamedica IN (". implode(",",$convocatorias) .") ORDER BY uid_citamedica ASC LIMIT $i,1) as 'Comentario Empleado Cita $num'";


				$campos[] = "(SELECT if(duracion, if(duracion=365,'A','B'), 'N/A') FROM ". TABLE_CITA_MEDICA ." WHERE uid_empleado = empleado.uid_empleado  AND uid_convocatoriamedica IN (". implode(",",$convocatorias) .") ORDER BY uid_citamedica ASC LIMIT $i,1) as 'Protocolo Fecha Cita $num'";

				$campos[] = "(SELECT $columnSQLEstado FROM ". TABLE_CITA_MEDICA ."  WHERE uid_empleado = empleado.uid_empleado  AND uid_convocatoriamedica IN (". implode(",",$convocatorias) .")  ORDER BY uid_citamedica ASC LIMIT $i,1) as 'Estado Cita $num'";
			}



			// Campo estado final de la convocatoria	
			$columnSQL = "%s";
			for($i=$max-1;$i>=0;$i--){
				$estadoFinal = "(SELECT $columnSQLEstado FROM ". TABLE_CITA_MEDICA ." WHERE uid_empleado = empleado.uid_empleado  AND uid_convocatoriamedica IN (". implode(",",$convocatorias) .") ORDER BY uid_citamedica ASC LIMIT $i,1)";

				$ifSQL = "if(
					(SELECT uid_citamedica FROM ". TABLE_CITA_MEDICA ." WHERE uid_empleado = empleado.uid_empleado AND uid_convocatoriamedica IN (". implode(",",$convocatorias) .") ORDER BY uid_citamedica ASC LIMIT $i,1),
					$estadoFinal, 
					%s
				)
				";
				$columnSQL = str_replace("%s", $ifSQL, $columnSQL);
			}
			$columnSQL = str_replace("%s", "NULL", $columnSQL);
			$columnSQL .= "as 'Estado Convocatoria'";
			$campos[] = $columnSQL;



			$campos[] = "(SELECT count(uid_citamedica) FROM ". TABLE_CITA_MEDICA ." WHERE uid_empleado = empleado.uid_empleado AND uid_convocatoriamedica IN (". implode(",",$convocatorias) .") ORDER BY uid_citamedica ASC LIMIT 0, $max) as 'Numero Citas'";

			/* 
			BAJASSS!?,
			*/

			$sql = "
				SELECT ". implode(", ", $campos) . " FROM ". TABLE_CONVOCATORIA_MEDICA ." INNER JOIN ". TABLE_EMPLEADO ." USING(uid_empleado) 
				WHERE uid_convocatoriamedica IN ($list)
				ORDER BY ADDDATE($fechaemision, INTERVAL $duracionMeses MONTH)
			";


			$exportacion = new excel($sql);
			if( !$exportacion->generar("CitasMedicas.xls", true) ){
				dump($exportacion);exit;
				die("<script>alert('No se han encontrado resultados')</script>");
			}

		}	

		
		public static function cronCall($time, $force = false){
			if( date("H:i") == "01:05" || $force ){
				$db = db::singleton();
				$sql = "SELECT uid_citamedica FROM ". TABLE_CITA_MEDICA ." WHERE DATEDIFF( fecha_cita, NOW() ) = " . self::DIAS_PREVIOS_AVISO . ";";
				$citas = $db->query($sql, "*", 0, "citamedica");
				foreach($citas as $cita){
					$cita->sendEmailInfo(NULL, true, self::AVISO_CITA);
				}
			}
		}
		
		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList();

			$direccionFormField = array("tag" => "input", "type" => "text", "blank" => false );

			if($objeto instanceof empleado || $objeto instanceof citamedica || $objeto instanceof convocatoriamedica){

				$direcciones = $objeto->obtenerDireccionesReconocimiento();
				if(is_traversable($direcciones)){
					$direccionFormField = array("tag" => "select", "type" => "text", "blank" => false, "data" => $direcciones, "others" => true, "default" => "Seleccionar");
				}

			} elseif( $modo == elemento::PUBLIFIELDS_MODE_INIT ) {
					throw new Exception("Unable to find parent element");
			}

			$fieldList["direccion"]				= 	new FormField( $direccionFormField );
			$fieldList["fecha_cita"] 			= 	new FormField( array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "size" => "15", "date_format" => "%d/%m/%Y" ));
			$fieldList["hora_cita"] 			= 	new FormField( array("tag" => "input", "type" => "text", "blank" => false, "className" => "timepicker", "placeholder" => "HH:MM" ));
			
			if ($modo == elemento::PUBLIFIELDS_MODE_EDIT && $objeto instanceof citamedica) {
				$data = self::obtenerEstadosMutua($objeto->isMandatory());
				$fieldList["uid_estado"] 			= 	new FormField( array("tag" => "select", "type" => "text", "blank" => false, "data" => $data));
			}

			$fieldList["comentario_interno"] 	= 	new FormField( array("tag" => "textarea", "type" => "text" ));
			$fieldList["comentario_empleado"] 	= 	new FormField( array("tag" => "textarea", "type" => "text" ));
			
			switch( $modo ){
				case elemento::PUBLIFIELDS_MODE_INIT: 
					if( $objeto instanceof convocatoriamedica && $objeto->obtenerEmpleado()->estaDeBaja() ) throw new Exception("error_empleado_baja");
				break;
				case elemento::PUBLIFIELDS_MODE_EDIT: case elemento::PUBLIFIELDS_MODE_TAB:
				break;
				default:
					$fieldList["uid_empleado"] 				= 	new FormField( array("tag" => "input", "type" => "text", "blank" => false));
					$fieldList["uid_convocatoriamedica"] 	= 	new FormField( array("tag" => "input", "type" => "text", "blank" => false));
					$fieldList["duracion"] 					= 	new FormField( array("tag" => "input", "type" => "text"));
					$fieldList["obligatorio"] 					= 	new FormField( array("tag" => "input", "type" => "text"));
				break;
			}

			return $fieldList;
		}
	}
?>
