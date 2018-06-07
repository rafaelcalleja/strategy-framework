<?php
	require_once("../api.php");
	$template = Plantilla::singleton();


	if( $usuario->configValue("economicos") && $uid = obtener_uid_seleccionado() ){
		$empresa = new empresa($uid);

		if (isset($_REQUEST['pasarfree']) && $_REQUEST['pasarfree'] && $usuario->esStaff()) {
			$empresa->setLicense(empresa::LICENSE_FREE, $usuario);
			$template->display("succes_form.tpl");
			exit;
		}

		if (isset($_REQUEST['event']) && $_REQUEST['event'] && $usuario->esAdministrador()) {
			if (isset($_REQUEST["confirmed"])) {

				$empresa->setLicense(empresa::LICENSE_PREMIUM, $usuario);
				$empresa->setTransferPending(false);

			  	$customKey = paypalLicense::createCustomKey();
			  	$paypal = new paypalLicense;
				$data = $paypal->getPayData($empresa);

				$sql = "SELECT custom FROM ". paypalLicense::TABLE_ITEM ." WHERE custom = '{$customKey}'";
				$existingCustom = db::get($sql, 0, 0);

				while ($existingCustom) {
					$customKey = paypalLicense::createCustomKey();
					$sql = "SELECT custom FROM ". paypalLicense::TABLE_ITEM ." WHERE custom = '{$customKey}'";
					$existingCustom = db::get($sql, 0, 0);
				}



				$sql = "INSERT INTO ". paypalLicense::TABLE_ITEM ." ( uid_usuario, uid_empresa, items, custom, discount, total, price ) VALUES (
						{$usuario->getUID()}, {$empresa->getUID()}, {$data->quantity}, '{$customKey}', '{$data->discount}', '{$data->total}', '{$data->price}'
						)";

				if (!db::get($sql)) {
					$template->display("message", "Ocurrio un error: ".$db->lasterror());
					$template->display("error.tpl");
					exit;
				};

				$country = $empresa->getCountry();
				$countryCode = $country->getCharCode();
				$countryName = $country->getUserVisibleName();
				$municipio = $empresa->obtenerMunicipio();
				$municipio = ($municipio instanceof municipio) ? $municipio->getUserVisibleName() : "";
				$provincia = $empresa->obtenerProvincia();
				$provincia = ($provincia instanceof provincia) ? $provincia->getUserVisibleName() : "";

				$direccion = $empresa->getAddress();
				$cp = $empresa->getPostalCode();
				$concept = utf8_decode($data->concept);

				$sql = "INSERT INTO `agd_data`.`paypal` (`payment_type`,`payment_date`, `payment_status`, `protection_eligibility`, `address_status`, 
											`payer_status`, `first_name`, `last_name`, `payer_email`, `handling_amount`, `address_name`,
											`address_country`,`address_country_code`, `address_state`, `address_city`, `address_street`,
											`business`, `receiver_email`, `receiver_id`, `residence_country`, `item_name1`, `quantity`,
											`tax`, `mc_currency`, `mc_fee`, `mc_gross`, `txn_type`, `txn_id`, `notify_version`, `transaction_subject`, `custom`,
											`charset`)

						 values 

											('". endeve::PAYMENT_METHOD_TRANSFER ."', now(), 'Completed', 'Ineligible', 'unconfirmed',
											 'unverified', '{$usuario->getName()}', '{$usuario->getSurname()}', '{$usuario->getEmail()}',
											 '{$data->handling}', '{$usuario->getHumanName()}',
											 '{$countryName}', '{$countryCode}', '{$cp}','{$provincia}', '{$direccion}',
											 'jmedina@dokify.net', 'jmedina@dokify.net' , '', 'SPAIN', '{$concept}', '{$data->quantity}',
											 '{$data->tax}', 'EUR', '{$data->price}', '{$data->total}' ,'web_accept' ,'{$customKey}', '3.7', '{$customKey}', '{$customKey}',
											 'UTF-8')";





				if (!db::get($sql)) {
					$template->assign("message", "error_desconocido");
					$template->display("error.tpl");
					exit;
				};

				$item = array(array("description" => $data->concept, "unit_price" =>  $data->price, "discount" => $data->discount));
				$endeveId = endeve::getEndeveId($empresa);
				$saleId = endeve::createSale($empresa, $endeveId, $item, NULL, paypalLicense::TAG_ENDEVE_LICENSE, new DateTimeImmutable("now"));
				endeve::payItem($empresa, $saleId, array(array("amount"=>$data->total)), true, endeve::PAYMENT_METHOD_TRANSFER);

				$template->display("succes_form.tpl");
				exit;
			} else {
				$template->assign("html", "confirmar_accion");
				$template->assign("hiddenInput", array(array("name" => "event", "value" => "transfer")));
				$template->display("confirmaraccion.tpl");
				exit;
			}



		}

		$template->assign("empresa", $empresa);
		$template->assign("user", $usuario);
		$template->display("paypal/sumary.tpl");
	}
?>



