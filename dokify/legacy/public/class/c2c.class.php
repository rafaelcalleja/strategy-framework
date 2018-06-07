<?php

	class c2c {
		const TOKEN = 'b21d585820691df06f1998fe8a314b21';
		const API = 'https://c2c.jmeservicios.com/call';
		const CALLER = '100'; // numero de origen


		// --------- Nuestra URI de escucha
		const CALLBACK = 'http://www.dokify.net/agd/call/listen.php';


		// --------- Tipos de llamada
		const DYNAMIC_MESSAGE_CONFIRM = 'MC';
		const DYNAMIC_MESSAGE_ONLY = 'M';


		// --------- Voces para el TTS
		const VOICE_CARMEN = 'Carmen';



		/***
		   * Llama a un destino con 2 mensajes, uno para explicar y otro para realizar una pregunta de confirmacion
		   *
		   */
		public static function confirm ($number, $text, $confirm) {
			$query = self::defaults();

			$query['dynamic'] = self::DYNAMIC_MESSAGE_CONFIRM;
			$query['tts_m'] = $text;
			$query['tts_c'] = $confirm;
			$query['numbers'] = $number;

			return self::exec($query);
		}

		/***
		   * Llama a un destino con 2 mensajes, uno para explicar y otro para realizar una pregunta de confirmacion
		   *
		   */
		public static function call ($number, $text) {
			$query = self::defaults();

			$query['dynamic'] = self::DYNAMIC_MESSAGE_ONLY;
			$query['tts_m'] = $text;
			$query['numbers'] = $number;


			return self::exec($query);
		}



		/***
		   * Call API with params
		   *
		   */
		private static function exec ($query) {
			$URI = self::API . '?' . http_build_query($query);
			if ($res = file_get_contents($URI)) {
				$xml = new SimpleXMLElement($res);

				$code = (string) $xml->attributes()->code;
				$id = (string) $xml->attributes()->id;

				if ($code == 200) {
					return $id;
				}
			}

			return false;
		}

		/***
		   * Query string defaults
		   *
		   */
		private function defaults () {
			return array(
				'token' => self::TOKEN,
				'callback' => self::CALLBACK,
				'voice' => self::VOICE_CARMEN,
				'callerid' => self::CALLER,
				'machine' => 'yes', // detectar buzones de voz
				'tries' => '1'
			);
		}
	}