<?php
	class fichero extends elemento implements Ielemento, Iactivable, Ilistable {
	
		
		public function __construct($param, $extra = false){
			$this->tipo = "fichero";
			$this->tabla = TABLE_FICHERO;
			$this->instance( $param, $extra );
		}

		public static function getRouteName () {
			return 'file';
		}

		public function getViewData (Iusuario $user = NULL) {
			$viewData = parent::getViewData($user);
			
			$viewData['active'] = $this->archivoAsociado() ? true : false;

			return $viewData;
		}


		/** RETORNA LA URL DEL ICONO */
		public function getIcon($mode=false){
			switch($mode){
				default:
					return RESOURCES_DOMAIN . "/img/famfam/page_white_acrobat.png";
				break;
				case "historico":
					return RESOURCES_DOMAIN . "/img/famfam/time.png";
				break;
			}
		}
		
		public function removeParent(elemento $parent, usuario $usuario = null) {
			return false;
		}

		public static function defaultData($data, Iusuario $usuario = NULL){
		
			if( ( !isset($data["nombre"]) || !trim($data["nombre"]) ) ){
				$files = unserialize($_SESSION["FILES"]);
				if( $files && ($archivo = $files["archivo"]) && !$archivo["error"] ){				
					$data["nombre"] = $archivo["name"];
				}
			}

			return $data;
		}


		public function getUserVisibleName(){
			return $this->obtenerDato("nombre");
		}



		public function getVersions($oidVersion=false){
			$versiones = array();
			$sql = "
				SELECT uid_fichero_archivo, uid_fichero, path, fecha 
				FROM ". $this->tabla ."_archivo 
				WHERE uid_fichero = $this->uid 
				ORDER BY fecha
				";

			$datos = $this->db->query($sql, true);

			if( is_array($datos) && count($datos) ){
				foreach($datos as $linea){
					$linea["realpath"] = DIR_FILES . $linea["path"];
					$linea["size"] = archivo::formatBytes( archivo::filesize( $linea["realpath"] ) );
					$version = (object) $linea;
					if( is_numeric($oidVersion) && $oidVersion == $version->uid_fichero_archivo ){

						return $version;
					}

					$versiones[] = $version;
				}
				return $versiones;
			} else {
				return false;
			}
		}


		public function getAlarmCount(){
			$sql = "SELECT count(a.uid_alarma_elemento) FROM ".TABLE_ALARMA_ELEMENTO." a WHERE a.uid_modulo = 26 AND a.uid_elemento = ". $this->getUID();
			$alarmas = $this->db->query($sql, 0, 0);
			return $alarmas;
		}


		/** En base a todas las alarmas que pueda tener el fichero, nos devuelve un color tratando como referencia la mas cercana **/
		public function getAlarmColor(){
			$alarmas = $this->obtenerAlarmas();
			$dateref = 0;
			foreach($alarmas as $alarma){
				$date = $alarma->getDate();
				if( ( $date < $dateref && $date > time() ) || !$dateref ){
					$dateref = $date;
				}
			}

			if( !$dateref ){ return false; }

			$restante = strtotime($dateref) - time();
			$dias = ceil($restante/60/60/24);

			if( $dias < 3 ) return "red";
			if( $dias < 10 ) return "orange";
			if( $dias > 30 ) return "green";
			return "yellow";
		}

		public function getClickURL(Iusuario $usuario = NULL, $config = false, $data = NULL){
		}

		public function getLineClass($parent, $usuario, $data = NULL){
			$class = false;

			$context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

			switch($context){
				case Ilistable::DATA_CONTEXT_DESCARGABLES:
					return $class;
				break;
				default:
					return $class;
				break;
			}
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
			$info = parent::getInfo(true, elemento::PUBLIFIELDS_MODE_TABLEDATA );

			$data =& $info[$this->getUID()];

			$name = $data['nombre'];
			$data['nombre'] = array(
				'innerHTML' => string_truncate($name, 60),
				'title' => $name
			);


			return $info;
		}

		public function getInlineArray(Iusuario $usuario = NULL, $config = false, $data = NULL){
			$inlineArray = array();
			$context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;
			$lang = Plantilla::singleton();
			switch($context){
				case Ilistable::DATA_CONTEXT_DESCARGABLES:
					if ($this->archivoAsociado()) {
						$lastVersion=$this->getLastVersion();
						$inlineArray[] = array(
							"style" => "text-align: right",
							"img" =>RESOURCES_DOMAIN . "/img/famfam/arrow_down.png",
							array( 
								"nombre" => $lang->getString("descargar"),
								"href" => "../agd/carpeta/fichero/descargar.php?send=1&oid={$lastVersion->uid_fichero_archivo}&poid={$this->getUID()}",  
								"target" => "async-frame"
								),
						);	
					} else {
						$inlineArray[] = array(
							"style" => "text-align: right", 
							"img" =>RESOURCES_DOMAIN . "/img/famfam/cross.png",	
							array( 
								"tagName" => "span",
								"nombre" => $lang->getString("inactivo")
							)	
						);					
					}
					
					return $inlineArray;
				break;	
				default:
					$infoEstado = array();
						$estado = $this->getStatus();
						$infoEstado[] = array( 
							"nombre" => documento::status2String($estado), 
							"className" => "stat stat_".$estado,
							"tagName" => 'span'
						);
						$infoEstado["width"] = "120px";
						$inlineArray[] = $infoEstado;



					if( $usuario instanceof usuario ){
						if( $numeroAlarmas = $this->getAlarmCount() ){
							$tpl = Plantilla::singleton();
							$color = $this->getAlarmColor();

							$img = ( $color ) ? RESOURCES_DOMAIN . "/img/famfam/bell_$color.png" : RESOURCES_DOMAIN . "/img/famfam/bell.png";
							$title = ( $color ) ? $tpl->getString("alarma_estado_". $color) : "";
							$alarms = array( "img" => $img, "title" => $title );

							$optionsAlarm = $usuario->getAvailableOptionsForModule($this->getModuleId(), "alarma");
							if( $accion = reset($optionsAlarm) ){
								$href = $accion["href"] . "&poid=" . $this->getUID();
							}

							$alarms[] = array(
								"nombre" =>  "Alarmas: $numeroAlarmas",
								"href" => $href
							);

							$alarms["width"] = "120px";
							$inlineArray[] = $alarms;
						}
					}
					
					return $inlineArray;
				break;
			}
		}


		public function getLastVersion(){
			$sql = "
				SELECT uid_fichero_archivo, uid_fichero, path, fecha, estado
				FROM ". $this->tabla ."_archivo 
				WHERE uid_fichero = $this->uid 
				AND uid_fichero_archivo = (
					SELECT max(uid_fichero_archivo) FROM ". $this->tabla ."_archivo sub 
					WHERE sub.uid_fichero = $this->uid 
				)";

			$datos = $this->db->query($sql, 0, "*");

			if( is_array($datos) && count($datos) ){
				$datos["realpath"] = DIR_FILES . $datos["path"];
				$datos["size"] = archivo::filesize($datos["realpath"]);
				return (object) $datos;
			} else {
				return false;
			}
		}

		public function getStatus(){
			$file = $this->getLastVersion();
			if( $file ){
				return $file->estado;
			} else {
				return documento::ESTADO_PENDIENTE;
			}
		}

		public function archivoAsociado(){

			$db=db::singleton();

			$sql = "SELECT `path` FROM ". TABLE_FICHERO ."_archivo WHERE uid_fichero = '". $this->getUID() ."'";

			$path = $db->query($sql, 0, 0);

			if( strlen($path) > 0 ){
				return  true;
			}

			return false;
		}


		public function anexar($file){
			//DIR_FILES
			$tmp = $file["tmp_name"];
			$relativepath = "agrupadores/" . time() . "." . archivo::getExtension($file["name"]);
			$path = DIR_FILES . $relativepath;		
			$fechaCaducidad = 0;

			// Calculamos la fecha...
			$caducidad = ( isset($_REQUEST["fecha_caducidad"]) && trim($_REQUEST["fecha_caducidad"]) ) ? db::scape($_REQUEST["fecha_caducidad"]) : 0;
			if ($caducidad != 0) {
				$fecha = explode("/", $caducidad);
			
				if( 	(strlen($fecha[0]) != 2 && $fecha[0] < 32) || 
						(strlen($fecha[1]) != 2 && $fecha[1] < 13) || 
						(strlen($fecha[2]) != 4 && $fecha[2])
					){
					return "error_fecha_incorrecta";
				}

				$fechaCaducidad = array($fecha[2], $fecha[1], $fecha[0]);
				$fechaCaducidad = implode("-",$fechaCaducidad);
				$fechaCaducidad = strtotime($fechaCaducidad);

				if( $fechaCaducidad < time() ){
					return "error_fecha_antigua";
				}
			}

			if ( !archivo::escribir($path, archivo::tmp($tmp)) ) {
				error_log("Unable to write in ". dirname($path));
			    return "error_copiar_archivo";
			} else {
				$sql = "INSERT INTO ". $this->tabla ."_archivo ( uid_fichero, path, fecha_caducidad ) VALUES (
					$this->uid, '". $relativepath ."', '$fechaCaducidad'
				)";

				if( $this->db->query($sql) ){
					return true;
				} else {
				    return "error_guardar_nuevo_archivo";
				}
			}

			//return true;
		}





		/*metodo para mostrar las alarmas que tenga cada fichero - clase alarma*/
		public function obtenerAlarmas() {
			$sql = "
				SELECT a.uid_alarma
				FROM ".TABLE_ALARMA." a
				INNER JOIN ".TABLE_ALARMA_ELEMENTO." ae ON ae.uid_alarma = a.uid_alarma
				WHERE 
					ae.uid_elemento=".$this->getUID()." 
					AND
					ae.uid_modulo=".$this->getModuleId()."
			";
			$uidsAlarmas = $this->db->query($sql,"*",0);
			$objetosAlarmas = array();
			foreach($uidsAlarmas as $uidAlarma ) {
				$objetosAlarmas[] = new alarma($uidAlarma);
			}
			return $objetosAlarmas;
		}

		/*metodo llamado desde la clase alarma para sacar el mail o mails de a quien enviar el aviso*/
		public function obtenerEmailAviso(){
			/*comprobamos a que carpeta pertenece el fichero solo devolverá un objeto carpeta*/
			/*
			if( $manager = $this->getFolder()->obtenerAgrupadorContenedor()->obtenerManager() ){
				$email = $manager->obtenerDato("email");
				return $email;
			}
			*/
			
			return false;
		}


		/** MARCA ESTE FICHERO COMO "HIJO" DE LA CARPETA PADRE */
		public function guardarEn(carpeta $carpeta){
			$sql = "INSERT INTO ". $this->tabla ."_carpeta ( uid_carpeta, uid_fichero ) VALUES (
				". $carpeta->getUID() .", ". $this->getUID() ."
			)";

			return $this->db->query($sql);
		}

		/*
		public function obtenerElementosSuperiores(){
			return $this->getFolder();
		}
		*/

		public function isDeactivable($parent, usuario $usuario){
			return true;
		}

		public function obtenerElementosActivables(usuario $usuario = NULL){
			return $this->getFolder();
		}

		public function getFolder(){
			$relatedFolder = $this->obtenerRelacionados( TABLE_FICHERO_CARPETA, "uid_fichero", "uid_carpeta");
			$ficheroCarpeta = reset($relatedFolder);
			return new carpeta($ficheroCarpeta["uid_carpeta"]);
		}

		public function obtenerAgrupadorContenedor(){
			return $this->getFolder()->obtenerAgrupadorContenedor();
		}


		public function enviarPapelera($parent, usuario $usuario){
			return null; // not implemented yet
		}


		public function restaurarPapelera($parent, usuario $usuario){
			return null; // not implemented yet
		}

		public function getTreeData(Iusuario $usuario){
			
			return array(
				"img" => array(	"normal" => $this->getIcon()	),
				"checkbox" => true
			);

	
		}


		/*************************************************
		 *************************************************
		 ***************	STATIC METHODS 	**************
		 *************************************************
 		 *************************************************/
/*
		public static function optionsFilter( $uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent  ){
		if ($uidelemento && $uidmodulo && $user) {
				$modulo = util::getModuleName($uidmodulo);
				$elemento = new $modulo($uidelemento);
				$empresas = $elemento->obtenerAgrupadorContenedor()->getCompany()->getStartIntList();
				if (!$empresas->contains($user->getCompany()->getUID())) {
					return ' AND 0 ';
				}
 			}

			return false;
		}
*/
		/** 
		  *	NOS INDICA LOS OBJETOS INFERIORES DEL ACTUAL EN EL QUE ESTAN CONTENIDOS 
		  *
		  */
		public static function getSupModules(){
			$modulos = array( util::getModuleId("carpeta") => "getFolder", util::getModuleId("agrupador") => "obtenerAgrupadorContenedor" );
			return $modulos;
		}


		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fields = new FieldList();
			switch( $modo ){
				default:
					$fields["nombre"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false ) );
				break;
			}

			return $fields;
		}


	}
?>
