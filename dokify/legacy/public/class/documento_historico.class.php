<?php
	//clase documento_historico
	//solo se usa para seguir el estandar de metodos
	class documento_historico extends elemento{
		const HISTORICO_POR_PAGINA = 3;


		public function __construct( $param , $modulo/*uid or data*/ ){
			$this->tipo = "documento_historico";
			$this->tabla = PREFIJO_ANEXOS_HISTORICO. strtolower($modulo);
			$this->instance( $param, false );
		}


		public function getUserVisibleName($fn=false){
			$sql = "SELECT alias FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
					WHERE uid_documento_atributo = ( 
						SELECT uid_documento_atributo FROM $this->tabla WHERE uid_$this->nombre_tabla = $this->uid
					)";
			$name = $this->db->query($sql, 0, 0);
			if(is_callable($fn)) return $fn($name);
			return $name;
		}

		public function getAnexo() {
			$m = $this->getDestinyModule();
			$info = $this->getInfo();
			$className = "anexo_historico_{$m}";
			return new $className($info["uid_{$className}"]);
		}

		public function getDestinyModule() {
			$aux = explode("_", $this->tabla);
			return end($aux);
		}
		
		public function getHistoricModule() {
			$aux = explode(".", $this->tabla);
			return end($aux);
		}

		/** 
 		  * Devuelve como un string la ruta absoluta
		  * donde se guarda el archivo en cuestion
		  */		
		public function getFilePath(){
			$info = $this->getInfo();
			return DIR_FILES . $info["archivo"];
		}


		/**
		  * Enviar al navegador los binarios
		  * del archivo para descargar
		  */
		public function downloadFile(){
			return archivo::descargar( $this->getFilePath(), $this->getUserVisibleName() );
		}


		/**
		  * Devolver el estado del documento
		  */
		public function obtenerEstado($toString=true){
			$info = $this->getInfo();
			$estado = $info["estado"];
			
			if( $toString ){
				$estado = documento::status2String($estado);
			}
			return $estado;
		}

		/** 
		  * Devuelve un objeto el cual solicitaba el documento
		  */
		public function obtenerSolicitante(){
			$sql =" SELECT uid_elemento_origen, uid_modulo_origen, uid_agrupador
					FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
					INNER JOIN $this->tabla 
					USING( uid_documento_atributo )
					WHERE uid_$this->nombre_tabla = $this->uid";
			$datosSolicitante = $this->db->query($sql, 0, "*");

			$moduloOrigen = util::getModuleName( $datosSolicitante["uid_modulo_origen"] );
			$objetoSolicitante = new $moduloOrigen( $datosSolicitante["uid_elemento_origen"] );

			if( $datosSolicitante["uid_agrupador"] ){
				$agrupadorReferencia = new agrupador($datosSolicitante["uid_agrupador"]);
				$objetoSolicitante->referencia = $agrupadorReferencia;
			}
		
			return $objetoSolicitante;					
		}

	}
?>
