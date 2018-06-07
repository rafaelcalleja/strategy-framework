<?php
	
	class AuthController extends Sabre_DAV_Auth_Backend_AbstractDigest {


		public function getDigestHash ($realm, $username) {
			if ($user = usuario::login($username)) {
				return $user->obtenerDato('pass_apache2');
			}
		}

	}