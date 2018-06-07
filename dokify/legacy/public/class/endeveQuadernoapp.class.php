<?php
	require_once( dirname(__FILE__) . "/quaderno/quaderno_load.php" );

	class endeveQuadernoapp {
		const INI_KEY = "endeve.key";

		public function getEndeveId(empresa $empresa){
			$key = get_cfg_var( endeve::INI_KEY );
			if( !$key ){ die("No esta definida la clave endeve.key en " . endeve::INI_KEY); }

			QuadernoBase::init($key, 'my-account');

			$name = $empresa->obtenerDato("nombre");
			$contactID = $empresa->obtenerDato("endeve_id");

			if (!is_string($contactID)) {
				$cif = $empresa->obtenerDato("cif");

				$data = array(
					'first_name' => $name,
					'full_name' => $name,
					'last_name' => ' ',
					'tax_id' => $empresa->obtenerDato("cif"),
					'language' => 'ES',
					'country' => 'ES'
				);

				$contacto = $empresa->obtenerContactoPrincipal();
				if( $contacto instanceof contactoempresa ){
					if( $mail = trim($contacto->obtenerDato("email")) ){
						$data["email"] = $mail;
					}
					if( $tlf = trim($contacto->obtenerDato("telefono")) ){
						$data["phone_1"] = $tlf;
					}
					if( $tlf = trim($contacto->obtenerDato("movil")) ){
						$data["phone_2"] = $tlf;
					}

					// TLF
					if( !isset($data["phone_1"]) && isset($data["phone_2"]) ){
						$data["phone_1"] = $data["phone_2"];
					}

					if( $name = trim($contacto->getUserVisibleName()) ){
						$data["contact_name"] = $name;
					}
				}

				if( $direccion = $empresa->obtenerDato("direccion") ){ $data["street_line_1"] = mb_substr($direccion, 0, 255, "utf8"); }
				if( $postalcode = $empresa->obtenerDato("cp") ) $data["postal_code"] = mb_substr($postalcode, 0, 5, "utf8");
				if( $city = $empresa->obtenerDato("uid_municipio") ){
					$municipio = new municipio($city);
					$data["city"] = mb_substr($municipio->getUserVisibleName(), 0, 255, "utf8");
				}

				$contact = new QuadernoContact($data);

				if ($contact->save()) {
					$contactID = $contact->id;

					if( !$empresa->update( array("endeve_id" => $contactID), "endeve") ){
						error_log("No se puede guardar su id de contacto");
						die("No se puede guardar su id de contacto");
					}
				} else {

					foreach($contact->errors as $error) {
						error_log("{$error->on} - {$error->message} [{$empresa->getUID()}]");
						print "{$error->on} - {$error->message}";
					}

					exit;
				}
			}

			return $contactID;
		}

		public function payItem(empresa $empresa, $saleID, $total, $discountRate, $sendEmail = true){
			$key = get_cfg_var( endeve::INI_KEY );
			if( !$key ){ die("No esta definida la clave endeve.key en " . endeve::INI_KEY); }
			QuadernoBase::init($key, 'my-account');

			$invoice = new QuadernoInvoice($saleID);

			$payment = new QuadernoPayment(
				array(
					'date' => date('Y-m-d'),
					'payment_method' => 'credit_card',
					'number' => $totalIVA + $gastosGestion,
					'currency' => 'EUR',
					'payment_method' => "paypal"
				)
			);

			$invoice->addPayment($payment);

			if( $invoice->save() ){
				if ($sendEmail) {
					$status = $invoice->deliver();
				}
			} else {
				error_log("endeve_error_save_payment [$saleID]");
			}
		}
	}
?>
