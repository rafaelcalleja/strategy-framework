<?php

	class Token extends elemento implements IToken {
		const TABLE = "agd_api.token";
		// Evitamos que se solicite el usuario para guardar el log cuando se cree un objeto de este tipo
		const NO_REGISTER_CREATION = true; 


		public function __construct($uid, $param){
			$this->tipo = "Token";
			$this->tabla = Token::TABLE;
			
			$this->instance( $uid, $param);
		}

		public static function createRequestToken(IConsumer $consumer, $token, $tokenSecret, $callback){
			$data = array( "type" => token::TYPE_REQUEST, "uid_consumer" => $consumer->getUID(), "token" => $token, "token_secret" => $tokenSecret, "verifier" =>  APIProvider::generateTokenString(), "callback_url" => $callback );
			$token = new Token($data);
			if( $token->error ){
				return false;
			}
			return $token;
		}

		public static function findByToken($token){
			$sql = "SELECT uid_token FROM ". Token::TABLE . " WHERE token = '{$token}'";
			$uid = db::get($sql, 0, 0);
			if( is_numeric($uid) ){
				return new Token($uid);
			}
			return false;
		}

		public function accessTokenExists($usuario){
			$sql = "SELECT uid_token FROM ". Token::TABLE . " WHERE user = '{$usuario->getUID()}' AND type = ". token::TYPE_ACCESS ." AND uid_consumer = {$this->getOauthConsumer()->getUID()}";
			$uid = db::get($sql, 0, 0);
			if( is_numeric($uid) ){
				return new Token($uid);
			}
			return false;
		}

		/** Eliminar token duplicados **/
		public function removeDuplicates(){
			$user = $this->getUserId();
			$consumer = $this->getOauthConsumer();
			if( $user && $consumer instanceof OauthConsumer && $usertype ){
				$sql = "DELETE FROM {$this->tabla} 
					WHERE user = {$user} 
					AND uid_consumer = {$consumer->getUID()} 
					AND token != '{$this->getPublic()}' 
					AND token_secret != '{$this->getSecret()}' 
					AND verifier != '{$this->getVerifier()}'";
				return $this->db->query($sql);
			}
			return false;
		}

		/* returns true if this is a request token otherwise return false */
		public function isRequest(){
			return ( $this->getType() === Token::TYPE_REQUEST ) ? true : false;
		}

		/* returns true if this is a access token otherwise return false */
		public function isAccess(){
			return ( $this->getType() === Token::TYPE_ACCESS ) ? true : false;
		}

		/* return callback url con los parametros adecuados */
		public function getCallback($deny=false){
			
			$url = $this->obtenerDato("callback_url");
			list($url, $query) = explode("?", $url);

			if( !is_array($query) ){ $query = array(); }
			$query["t"] = time();

			if( !$deny ){
				$query["token_verifier"] = $this->getVerifier();
				$query["oauth_token"] = $this->getPublic();
			} else {
				$query["oauth_error"] = "access_deny";
			}

			$url .= "?" . http_build_query($query);
			return $url;
		}


		public function changeToAccessToken($token, $secret, $user){
			$data = array( "type" => Token::TYPE_ACCESS, "verifier" => "", "callback_url" => "", "token" => $token, "token_secret" => $secret );
			return $this->update($data);
		}

		public function getVerifier(){
			return $this->obtenerDato("verifier");
		}

		public function getType(){
			return (int) $this->obtenerDato("type");
		}

		public function getPublic(){
			return $this->obtenerDato("token");
		}

		public function getSecret(){
			return $this->obtenerDato("token_secret");
		}

		public function getUserID(){
			return (int) $this->obtenerDato("user");
		}

		public function getOauthConsumer(){
			$uid = (int) $this->obtenerDato("uid_consumer");
			if( is_numeric($uid) ){
				return new OauthConsumer($uid);
			}
			return false;
		}

		public function setVerifier($verifier){
			return $this->update( array("verifier" => $verifier) );
		}

		public function setUser($user){
			return $this->update( array("user" => $user) );
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null){
			$fieldList = new FieldList();
	
			switch( $modo ){
				default:
					$fieldList["type"] = 			new FormField( array("tag" => "input", "type" => "text", "blank" => false ));
					$fieldList["uid_consumer"] = 	new FormField( array("tag" => "input", "type" => "text", "blank" => false ));
					$fieldList["user"] = 			new FormField( array("tag" => "input", "type" => "text" ));
					$fieldList["token"] = 			new FormField( array("tag" => "input", "type" => "text", "blank" => false ));
					$fieldList["token_secret"] = 	new FormField( array("tag" => "input", "type" => "text", "blank" => false ));
					$fieldList["callback_url"] = 	new FormField( array("tag" => "input", "type" => "text", "blank" => false ));
					$fieldList["verifier"] = 		new FormField( array("tag" => "input", "type" => "text", "blank" => false ));
				break;
			}

			return $fieldList;
		}

	}

?>
