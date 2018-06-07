<?php
	class eventdate extends elemento implements Ielemento {
		protected $reference;

		public function __construct($param, $reference=false, $extra = false){
			$this->tipo = "eventdate";
			$this->tabla = TABLE_EVENTOS;
			$this->reference = ( is_numeric($reference) ) ? $reference : self::getModuleId($reference);


			if( is_array($param) && is_numeric($this->reference) ){
				$param["uid_modulo"] = $this->reference;
			}

			$this->instance( $param, $extra );
		}

		// Para compatibilidad
		public function getUserVisibleName(){
			$empresa = $this->getCompany();
			//$usuario = $this->getUser();
			$date = $this->getDate();
			return $date . " - " . $empresa->getUserVisibleName();
		}

		public function getCompany(){
			return new empresa( $this->obtenerDato("uid_empresa") );
		}

		public function getUser(){
			return new usuario( $this->obtenerDato("uid_usuario") );
		}
		public function getTime(){
			return strtotime($this->obtenerDato("fecha"));
		}
		public function getDate(){
			if ($time = $this->getTime()) {
				return date("d/m/Y", $time);
			}
			
			return false;
		}

		public function obtenerEmailAviso(){
			$empresaEvento = $this->getCompany();
			$arrEmails = $empresaEvento->obtenerEmailContactos( plantillaemail::instanciar("eventDate") );
			return $arrEmails;			
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

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false) {
			$fields = new FieldList;

			switch ($modo) {
				case self::PUBLIFIELDS_MODE_NEW:
					$fields["uid_empresa"] = new FormField;
					$fields["uid_usuario"] = new FormField;
					$fields["fecha"] = new FormField;
					$fields["uid_modulo"] = new FormField;
				break;
				case 'simple': case self::PUBLIFIELDS_MODE_TABLEDATA:break;
				case self::PUBLIFIELDS_MODE_EDIT:
					$tpl = Plantilla::singleton();

					if ($objeto instanceof self) {
						// con esto simulamos una lista de datos aceptada por el tpl form
						$user = $objeto->getUser();
						$data = array($user->getUID() => $user->getUserVisibleName());
						$fields["uid_usuario"] = new FormField(array("tag" => "span", "innerHTML" => $tpl("usuario"), "data" => $data));
					}
					
					//$fields["uid_modulo"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "size" => "12"));
				break;
				default:

				break;
			}

			$fields["descripcion"] = new FormField(array("tag" => "textarea", "blank" => false));
			
			return $fields;
		}


	}
?>
