<?php

	interface IToken{

		// Constantes para indentificar el tipo de token
		const TYPE_REQUEST = 1;
		const TYPE_ACCESS = 2;


		// Crear un token de solicitud
		public static function createRequestToken(IConsumer $consumer,$token,$tokensecret,$callback);

		// Conseguir un objeto Token en base a un string token
		public static function findByToken($token);

		// Devuelve true si el token es de solicitud
		public function isRequest();

		// Devuelve true si el token es de acceso
		public function isAccess();

		// Nos indica la url de callback para redirigir
		public function getCallback($error);

		// Nos muestra el dato codificado verificador
		public function getVerifier();

		// Nos indica el tipo de token que es segun las constantes Token::TYPE_REQUEST y Token::TYPE_ACCESS
		public function getType();

		// Nos retorna el dato token_secret
		public function getSecret();

		// Nos retorna el uid del usuario al que pertence el token
		public function getUserID();

		// Establece el dato verificador de este token
		public function setVerifier($verifier);

		// Establece el usuario al que pertenece este token
		public function setUser($user);

	}

?>
