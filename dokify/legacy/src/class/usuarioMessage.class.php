<?php

	class usuarioMessage extends elemento implements Ielemento {
		/**
			CONSTRUIR EL OBJETO, LLAMA AL METODO INSTANCE DE LA CLASE ELEMENTO
		*/
		public function __construct($param, $extra=false){
			$this->tipo = "usuarioMessage";
			$this->tabla = TABLE_USUARIO ."_message";
			$this->instance( $param, $extra );
		}

		public function getUserVisibleName(){
			return "usuarioMessage";
		}

		public function getUser(){
			$info = $this->getInfo();
			if (($uidUsuario = $info["uid_usuario"]) && is_numeric($uidUsuario)) {
				return new usuario($uidUsuario);
			}
			return false;
			
		}

		public function getDate(){
			$info = $this->getInfo();
			return $info["fecha"];
		}

		public function getTimestamp () {
			return strtotime($this->getDate());
		}

		public function getTreeData(Iusuario $usuario, $extraData = array()){
		
			return array(
				"checkbox" => false,
				"img" => array(
					"normal" => RESOURCES_DOMAIN ."/img/famfam/time.png"
				)
			);
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()) {
			$tpl = Plantilla::singleton();
			$dataTable = array();

			$user = $this->getUser();

			$dataTable["usuario"] = $user->getUserVisibleName();
			$dataTable["empresa"] = $user->getCompany()->getUserVisibleName();
			$dataTable["date"] = date("d-m-Y", $this->getTimestamp());

			$tableInfo = array($this->getUID() => $dataTable);
			return $tableInfo;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fields = new FieldList;
			return $fields;
		}

	}
?>
