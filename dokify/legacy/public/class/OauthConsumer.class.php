<?php
	
	class OauthConsumer extends elemento implements IOauthConsumer, Ielemento {

		const TABLE = "agd_api.consumer";

		// Evitamos que se solicite el usuario para guardar el log cuando se cree un objeto de este tipo
		const NO_REGISTER_CREATION = true; 


		public function __construct($uid, $param = false){
			$this->tipo = "OauthConsumer";
			$this->tabla = OauthConsumer::TABLE;
			
			$this->instance($uid, $param);
		}

		public function getUserVisibleName(){
			return $this->obtenerDato("nombre");
		}

		/* return an instance of a IConsumer or return null on not found */
		public static function findByKey($key){
			$sql = "SELECT uid_consumer FROM ". self::TABLE." WHERE consumer_key = '{$key}'";
			$uid = db::get($sql, 0, 0);
			if( is_numeric($uid) ){
				return new self($uid);
			}

			return false;
		}

		public function getIcon($size = false){
			return RESOURCES_DOMAIN . "/img/api/consumer_1.png";
		}

		/**	True si el consumidor esta activo */
		public function isActive(){
			return (bool) $this->obtenerDato("active");
		}

		public function getUserModule(){
			return $this->obtenerDato("uid_modulo");
		}

		/** Obtener consumer key */
		public function getKey(){
			return $this->obtenerDato("consumer_key");
		}

		/* consumer secret */
		public function getSecretKey(){
			return trim($this->obtenerDato("consumer_secret"));
		}

		/* Comprueba que el consumdidor tenga un acceso igual en este mismo momento  */
		public function hasNonce($nonce, $timestamp){
			$db = db::singleton();
			$sql = "SELECT COUNT(uid_consumer_nonce) cnt FROM {$this->tabla}_nonce WHERE timestamp = '{$timestamp}' and nonce = '{$nonce}' and uid_consumer = {$this->uid}";
			$num = $db->query($sql, 0, 0);
			return ( $num ) ? true : false;
		}

		/* Guardar un nonce en cache para contratar las solicitudes */
		public function addNonce($nonce){
			$sql = "INSERT INTO ". self::TABLE ."_nonce (uid_consumer, timestamp, nonce) values ({$this->uid}, ".time().", '{$nonce}')";
			return db::get($sql);
		}


		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = NULL){
			$fieldList = new FieldList();
	
			switch( $modo ){
				default:
					$fieldList["nombre"] = 			new FormField( array("tag" => "input", "type" => "text", "blank" => false ));
					$fieldList["consumer_key"] = 	new FormField( array("tag" => "input", "type" => "text", "blank" => false ));
					$fieldList["consumer_secret"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false ));
					$fieldList["active"] = 			new FormField( array("tag" => "input", "type" => "checkbox", "blank" => false ));
				break;
			}

			return $fieldList;
		}

	}

?>
