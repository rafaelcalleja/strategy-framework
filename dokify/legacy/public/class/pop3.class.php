<?php

	class pop3{
		public $servidor;
		public $usuario;
		public $pass;
		public $puerto;
		public $tipo;
		public $imap;
		public $error;
	
		/** ESTABLECE LA CONEXION IMAP **/
		public function __construct($servidor,$usuario,$pass,$puerto=110,$tipo="pop3"){
			$this->pass = $pass;
			$this->servidor = $servidor;
			$this->usuario = $usuario;
			$this->puerto = $puerto;
			$this->tipo = $tipo;
			$conexion = "{".$this->servidor.":".$this->puerto."/".$this->tipo."/ssl/novalidate-cert}";
			$this->imap = imap_open($conexion, $this->usuario, $this->pass) or die( "No se puede conectar al servidor imap"/*imap_last_error()*/ );
		}

		/** NOS DA EL CONTENIDO DE EL MENSAJE */
		public function mensaje($numero, $parte){
			return imap_fetchbody($this->imap  , $numero , $parte );
		}


		/** DATOS DE LAS PERSONAS QUE ENVIAN */
		public function from($numero){
			$cabeceras = $this->cabeceras($numero);
			return $cabeceras->from[0];
		}

		/** CABECERAS DEL MENSAJE */	
		public function cabeceras($numero){
			return imap_headerinfo($this->imap, $numero);
		}

		/** OBTENER INFORMACION */
		public function getInfo($numero){
			$info =  imap_fetch_overview( $this->imap, $numero );
			if( $info[0] ){ return $info[0]; }
		}

		/** NUMERO DE EMAILS */
		public function listado(){
			return imap_num_msg($this->imap);
		}

		/** BORRA EL MENSAJE */
		public function borrar( $numero ){
			if( imap_delete( $this->imap, $numero ) ){
				if( imap_expunge( $this->imap ) ){
					return true;
				} else {
					print( "Error en expunge\n" );
					return false;
				}
			} else {
				print( "Error en delete\n" );
				return false;
			}
			return true;
		}

		public function cerrar(){
			return imap_close( $this->imap );
		}
	}

?>
