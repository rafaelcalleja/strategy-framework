<?php

	/** CLASE MODULO PARA UTILIZAR AL TRABJAR CON CONCEPTOS... (EMPRESA COMO SOLICITANTE DE DOCUMENTOS POR EJEMPLO */
	class modulo extends basic{

		public function __construct($uidModulo, $tipo){
			$this->tipo = $tipo;
			$this->uid_modulo = $uidModulo;
		}
		
		public function getType(){
			return $this->tipo;
		}

		public function getUserVisibleName(){
			return $this->tipo;
		}

		public function getModuleId($tipo=null){
			return $this->uid_modulo;
		}

	}
?>
