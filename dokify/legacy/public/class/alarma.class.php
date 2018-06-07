<?php
	class alarma extends elemento implements Ielemento {

		public function __construct( $param, $extra = false ){
			$this->tipo = "alarma";
			$this->tabla = TABLE_ALARMA;
			$this->instance( $param, $extra );
		}

		public function getUserVisibleName(){
			$datos = $this->getInfo();
			return $datos["nombre"];
		}

		public function getObject(){
			$sql = "SELECT uid_alarma_elemento, uid_alarma, uid_elemento, uid_modulo FROM ".TABLE_ALARMA_ELEMENTO." WHERE uid_alarma = ". $this->getUID();
			$data = $this->db->query($sql, 0, "*");

			$modulo = util::getModuleName($data["uid_modulo"]);
			$objeto = new $modulo($data["uid_elemento"]);		
			return $objeto;		
		}

		public function getDate($format = false){
			$date = $this->obtenerDato("fecha_alarma");
			if ($format) {
				$date = date($format, strtotime($date));
			}
			return $date;
		}

		public function isSend(){
			$datos = $this->getInfo();
			return ( $datos["enviado"] ) ? true : false;
		}

		public function marcarComoEnviado(){
			return $this->updateWithRequest(array( "enviado" => "1"), "enviado");
		}

		public function nuevoRelacionado($elemento) {
			$sql = "INSERT INTO ".TABLE_ALARMA_ELEMENTO." (uid_alarma,uid_elemento,uid_modulo) VALUES (".$this->getUID().",".$elemento->getUID().",".$elemento->getModuleId().")";
			$this->db->query($sql);
		}

		public static function getAllFilter($time, $send=0){
			$sql = "SELECT uid_alarma FROM ".TABLE_ALARMA." WHERE fecha_alarma = '".$time."' AND enviado = $send";
			$coleccion = db::get($sql, "*", 0, "alarma");
			return $coleccion;
		}

		public static function cronCall($time){
			$actual = date("Y-m-d")." 00:00:00";
			$alarmas = alarma::getAllFilter($actual);	

			foreach($alarmas as $alarma){
				$destinatarios = $alarma->obtenerDato("email[]");

				if( !count($destinatarios) ) continue;

				$time = strtotime($alarma->obtenerDato("fecha_alarma"));

				$email = new email($destinatarios);
				$email->establecerAsunto("Recordatorio de Alarma - ". date("d/m/Y", $time) );
				$body="<b>Recordatorio de Alarma: ".$alarma->obtenerDato("nombre")."</b><br><br>&nbsp;&nbsp;&nbsp;&nbsp;".$alarma->obtenerDato("comentario");
				$email->establecerContenido($body);

				echo "Enviando mail a [". implode(", ", $destinatarios) ."] ";
				if( $email->enviar() ){
					echo " OK";
					$alarma->marcarComoEnviado();
				} else {
					echo " ERROR";
				}

				echo "\n";
			}
			return true;
		}


		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$template = Plantilla::singleton();

			$arrayCampos = new FieldList;

			$arrayCampos["nombre"]			= new FormField(array("tag" => "input", "type" => "text", "blank" => false));
			$arrayCampos["comentario"]		= new FormField( array("tag" => "textarea", "type" => "text") );
			$arrayCampos["fecha_alarma"]	= new FormField( array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "size" => "10") );
			$arrayCampos["email[]"]			= new FormField( array("tag" => "input", "type" => "text", "innerHTML" => $template->getString("email") ) );

			if( is_string($modo) ){
				switch( $modo ){
					case "enviado":
						$arrayCampos =  new FieldList(array(
							"enviado"	=> new FormField()
						));
					break;
					case "nuevo":
						$arrayCampos["uid_usuario"] = new FormField();
					break;
				}
			}

			return $arrayCampos;
		}

	}

?>
