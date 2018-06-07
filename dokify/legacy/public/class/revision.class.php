<?php
	class revision extends elemento implements Ielemento {

		/** $item = false por compatibilidad con la interface **/
		public function __construct($uid, $item = false){
			$this->tipo = "revision";

			if( $item instanceof solicitable ){
				$this->tabla = constant("PREFIJO_ANEXOS_ATRIBUTOS") . strtolower($item->getType());
			} elseif( is_string($item) ){
				$this->tabla = constant("PREFIJO_ANEXOS_ATRIBUTOS") . $item;
			}

			$this->instance($uid);
		}

		public function getDate(){
			$time = strtotime($this->obtenerDato("fecha"));
			return date("d-m-Y H:i", $time);
		}

		public function getUser(){
			$uid = $this->obtenerDato("uid_usuario");
			return new usuario($uid);
		}

		public function getUserVisibleName(){
			return true;
		}

		public function getReviewerName(){
			$user = $this->getUser();
			return $user->getHumanName();
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			return new FieldList();
		}

	}
?>
