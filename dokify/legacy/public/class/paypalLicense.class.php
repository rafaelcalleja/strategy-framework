<?php
class paypalLicense extends paypal implements IpaypalLicense, Ielemento
{
    const TABLE_ITEM                = "agd_data.paypal_concept";
    const PRIMARY_KEY_TABLE_ITEM    = "uid_paypal_concept";
    const TAG_ENDEVE_LICENSE        = "license";
    const TEMPORARY_PRICE           = "10";
    const DAYS_TEMP_LICENSE         = "5";
    const DAYS_PREMIUM_LICENSE      = "365";

    public function __construct($param = false, $extra = false)
    {
        if ($param) {
            $this->tipo     = "paypal_concept";
            $this->tabla    = self::TABLE_ITEM;

            $this->instance($param, $extra);
        }
    }

    public static function getRouteName ()
    {
        return 'license';
    }

    public function getUserVisibleName()
    {
        return "paypalLicense";
    }

    public function getPathType ()
    {
        $company = $this->getCompany();

        if ($company && $company->isTemporary()) {
            return 'temporary';
        }

        return "license";
    }

    public function getConceptName ()
    {
        $num = $this->obtenerDato('items');

        if ($num <= self::MAX_ITEMS_MICRO) {
            $concept = "singup_micro_business";
        } elseif ($num <= self::MAX_ITEMS_PE) {
            $concept = "singup_small_business";
        } elseif ($num <= self::MAX_ITEMS_E) {
            $concept = "singup_medium_business";
        } else {
            $concept = "singup_business";
        }

        return $concept;
    }


    public function getTypeName ()
    {
        $num = $this->obtenerDato('items');

        if ($num <= self::MAX_ITEMS_MICRO) {
            $concept = "license_micro_business";
        } elseif ($num <= self::MAX_ITEMS_PE) {
            $concept = "license_small_business";
        } elseif ($num <= self::MAX_ITEMS_E) {
            $concept = "license_medium_business";
        } else {
            $concept = "license_business";
        }

        return $concept;
    }


    public function getTotalAmount()
    {
        $info = $this->getInfo();
        return $info["total"];
    }

    public function getDate()
    {
        $info = $this->getInfo();
        return $info["date"];
    }

    public function getPrice()
    {
        $info           = $this->getInfo();
        $price          = $info["price"];
        $discountRate   = $this->getDiscountRate();
        $discounted     = round($price * ($discountRate/100), 2);

        return $info["price"] - $discounted;
    }

    public function getDiscountRate()
    {
        $info = $this->getInfo();
        return (float) $info["discount"];
    }

    public function getCustom()
    {
        $info = $this->getInfo();

        return $info["custom"];
    }

    public function temporaryAccess()
    {
        return $this->obtenerDato("daysValidLicense") == self::DAYS_TEMP_LICENSE;
    }

    public function saveTransaction($mode = "ipn", $post = array())
    {
        $app        = \Dokify\Application::getInstance();
        $amount     = $post["mc_gross_1"];
        $itemPrice  = $this->getPrice();

        if (number_format($amount, 2) != number_format($itemPrice, 2)) {
            $this->notifyMismatchPayment($itemPrice, $amount, $this->getCompany());
        }

        $transactionID  = parent::saveTransaction($mode, $post);
        $data           = self::getTransactionData($transactionID);

        if ($data->sale_id == 0) {
            self::updateTransaction($transactionID, array("sale_id" => -1)); //Estamos procesando el pago
        }

        if (!$data) {
            throw new Exception("No se puede obtener la información de la transacción");
        }

        $isTest         = isset($post["test_ipn"]) && $post["test_ipn"] && CURRENT_ENV !== 'dev';
        $isComplete     = isset($post['payment_status']) && $post['payment_status'] === 'Completed';
        $processPayment = isset($data) && isset($data->sale_id) && is_numeric($data->sale_id) && $data->sale_id == 0 && !$isTest;

        if ($processPayment) {
            if ($isComplete) {
                $empresa = new empresa($data->uid_empresa);

                if ($empresa->exists()) {
                    $empresa->setLicense(empresa::LICENSE_PREMIUM);
                    // Desbloqueamos el alta/baja de empleados y máquinas
                    $empresa->setTransferPending(false);

                    $event = new \Dokify\Application\Event\License\Payment(
                        new \Dokify\Domain\License\Payment\PaymentUid($data->uid_paypal_concept)
                    );

                    $this->dispatcher->dispatch(
                        \Dokify\Events\License\PaymentEvents::LICENSE_PAYMENT_DONE,
                        $event
                    );

                    $endeveId = endeve::getEndeveId($empresa);
                    if ($endeveId) {
                        $description = $data->item_name1;
                        if ($description === "") {
                            $description = $this->getTypeName();
                        }
                        $item = [
                            [
                                "description" => $description,
                                "unit_price" =>  $data->price,
                                "discount" => $data->discount,
                                "elements" => $data->items,
                            ],
                        ];
                        $paymentLicenseDate = new DateTimeImmutable($this->getDate());
                        $saleId = endeve::createSale($empresa, $endeveId, $item, null, self::TAG_ENDEVE_LICENSE, $paymentLicenseDate);

                        if (is_numeric($saleId)) {
                            $method = ($post['txn_type'] == endeve::PAYMENT_METHOD_TRANSFER) ? endeve::PAYMENT_METHOD_TRANSFER : endeve::PAYMENT_METHOD_PAYPAL;
                            $payed  = endeve::payItem($empresa, $saleId, array(array("amount"=>$post["mc_gross"])), true, $method);

                            if (!$payed) {
                                //Tenemos que eliminar el saleID
                                error_log("PaypalLicense Error al realizar el pago para el saleId [$saleId]");
                            } else {
                                //actualizamos la tabla paypal con saleId.
                                $log = log::singleton();
                                $log->info("paypalLicense", "El pago para la transaccion $transactionID se ha registrado correctamente en quaderno con saleId $saleId", "custom: ".$post["custom"], "Ok", true);
                                self::updateTransaction($transactionID, array("sale_id" => $saleId));
                            }
                        } else {
                            //Reiniciamos el saleId para que vuelva a entrar la siguiente petción.
                            self::updateTransaction($transactionID, array("sale_id" => 0));
                            throw new \Dokify\Exception\TransactionException("PaypalLicense Error creando el saleId. No se registra el pago en endeve");
                        }
                    } else {
                        //Reiniciamos el saleId para que vuelva a entrar la siguiente petción.
                        self::updateTransaction($transactionID, array("sale_id" => 0));
                        throw new \Dokify\Exception\TransactionException("PaypalLicense Error creando el contacto para la empresa {$empresa->getUID()}");
                    }
                } else {
                    //Reiniciamos el saleId para que vuelva a entrar la siguiente petción.
                    self::updateTransaction($transactionID, array("sale_id" => 0));
                    throw new Exception("Parece que hay algún problema con los datos de pago: $transactionID");
                }
            } else {
                //Reiniciamos el saleId para que vuelva a entrar la siguiente petción.
                self::updateTransaction($transactionID, array("sale_id" => 0));
                error_log("No generamos factura para la transaccion $transactionID [". @$post['payment_status'] ."]");
            }
        } elseif ($data->sale_id == -1) {
            $app['log']->addWarning("license payment process already in progress", ["txn" => $transactionID, "mode" => $mode]);

            return $transactionID;
            // throw new \Dokify\Exception\TransactionException("Ya se estaba procesando un pago, no procesamos pago que entra con modo: {$mode}");
        } elseif (is_numeric($data->sale_id) && $data->sale_id != -1 && $data->sale_id != 0) {
            //Ya estaba registrado el pago, no hacemos nada
            return $transactionID;
        } else {
            $empresa = new empresa($data->uid_empresa);
            if (!$empresa instanceof empresa) {
                throw new \Dokify\Exception\TransactionException("No se puede insanciar la empresa con uid: {$data->uid_empresa}");
            }

            $empresa->setLicense(empresa::LICENSE_PREMIUM);
            // Desbloqueamos el alta/baja de empleados y máquinas
            $empresa->setTransferPending(false);

            if ($isTest) {
                error_log("Test IPN del que no hacemos factura: $transactionID [{$data->item_name1}, {$data->total}, {$data->discount}, {$data->price}]");
            } else {
                error_log("Ya se ha generado la factura para la transaccion $transactionID [". @$post['payment_status'] ."]");
            }

        }

        return $transactionID;
    }

    public static function getPayPrice($num)
    {
        //dump( self::MAX_ITEMS_MICRO, self::MAX_ITEMS_PE, self::REGISTER_PRICE_GE);
        if ($num <= self::MAX_ITEMS_MICRO) {
            return self::REGISTER_PRICE_MICRO;
        } elseif ($num <= self::MAX_ITEMS_PE) {
            return self::REGISTER_PRICE_PE;
        } elseif ($num <= self::MAX_ITEMS_E) {
            return self::REGISTER_PRICE_E;
        } else {
            return self::REGISTER_PRICE_GE;
        }
    }

    /**
     * Get the previous range num elements
     * @param  $numItems
     * @return int
     */
    public static function getPreviousRange($numItems)
    {
        if ($numItems <= IpaypalLicense::MAX_ITEMS_MICRO) {
            return 0;
        } elseif ($numItems <= 10) {
            return IpaypalLicense::MAX_ITEMS_MICRO;
        } elseif ($numItems <= IpaypalLicense::MAX_ITEMS_PE) {
            return 10;
        } elseif ($numItems <= 30) {
            return 20;
        } elseif ($numItems <= 40) {
            return 30;
        } elseif ($numItems <= 50) {
            return 40;
        } elseif ($numItems <= IpaypalLicense::MAX_ITEMS_E) {
            return 50;
        } elseif ($numItems <= 70) {
            return IpaypalLicense::MAX_ITEMS_E;
        } elseif ($numItems <= 80) {
            return 70;
        } elseif ($numItems <= 90) {
            return 80;
        } elseif ($numItems <= 100) {
            return 90;
        } elseif ($numItems <= 110) {
            return 100;
        } elseif ($numItems <= 120) {
            return 110;
        } elseif ($numItems <= 130) {
            return 120;
        } elseif ($numItems <= 140) {
            return 130;
        } elseif ($numItems <= 150) {
            return 140;
        } elseif ($numItems <= 160) {
            return 150;
        } elseif ($numItems <= 170) {
            return 160;
        } elseif ($numItems <= 180) {
            return 170;
        } elseif ($numItems <= 190) {
            return 180;
        } elseif ($numItems <= 200) {
            return 190;
        } elseif ($numItems <= 220) {
            return 200;
        } elseif ($numItems <= 240) {
            return 220;
        } elseif ($numItems <= 260) {
            return 240;
        } elseif ($numItems <= 280) {
            return 260;
        } elseif ($numItems <= 300) {
            return 280;
        } elseif ($numItems <= 320) {
            return 300;
        } elseif ($numItems <= 340) {
            return 320;
        } elseif ($numItems <= 360) {
            return 340;
        } elseif ($numItems <= 380) {
            return 360;
        } elseif ($numItems <= 400) {
            return 380;
        } elseif ($numItems <= 420) {
            return 400;
        } elseif ($numItems <= 440) {
            return 420;
        } elseif ($numItems <= 460) {
            return 440;
        } elseif ($numItems <= 480) {
            return 460;
        } elseif ($numItems <= 500) {
            return 480;
        } elseif ($numItems <= 550) {
            return 500;
        } elseif ($numItems <= 600) {
            return 550;
        } elseif ($numItems <= 650) {
            return 600;
        } elseif ($numItems <= 700) {
            return 650;
        } elseif ($numItems <= 750) {
            return 700;
        } elseif ($numItems <= 800) {
            return 750;
        } elseif ($numItems <= 850) {
            return 800;
        } elseif ($numItems <= 900) {
            return 850;
        } elseif ($numItems <= 950) {
            return 900;
        } elseif ($numItems <= 1000) {
            return 950;
        } elseif ($numItems <= 1100) {
            return 1000;
        } elseif ($numItems <= 1200) {
            return 1100;
        } elseif ($numItems <= 1300) {
            return 1200;
        } elseif ($numItems <= 1400) {
            return 1300;
        } elseif ($numItems <= 1500) {
            return 1400;
        } elseif ($numItems <= 1600) {
            return 1500;
        } elseif ($numItems <= 1700) {
            return 1600;
        } elseif ($numItems <= 1800) {
            return 1700;
        } elseif ($numItems <= 1900) {
            return 1800;
        } elseif ($numItems <= 2000) {
            return 1900;
        } else {
            return 2000;
        }

        return false;
    }

    public function getPayData(
        Ielemento $elemento,
        $temporaryAccess = false,
        $forceItems = null
    ) {
        if (!$elemento instanceof usuario && !$elemento instanceof empresa) {
            return false;
        }

        $empresa = $elemento instanceof usuario ? $elemento->getCompany() : $elemento;

        if ($forceItems) {
            $num = $forceItems;
        } else {
            $num = $empresa->countElements();
        }

        if ($temporaryAccess) {
            $price              = self::TEMPORARY_PRICE;
            $discount           = 0;
            $discountRate       = 0;
            $concept            = "temporary_premium_license";
            $daysValidLicense   = self::DAYS_TEMP_LICENSE;
        } else {
            $price = self::ITEM_PRICE;

            if ($num <= self::MAX_ITEMS_MICRO) {
                $base       = self::REGISTER_PRICE_MICRO;
                $concept    = "singup_micro_business";
            } elseif ($num <= self::MAX_ITEMS_PE) {
                $base       = self::REGISTER_PRICE_PE;
                $concept    = "singup_small_business";
            } elseif ($num <= self::MAX_ITEMS_E) {
                $base       = self::REGISTER_PRICE_E;
                $concept    = "singup_medium_business";
            } else {
                $base       = self::REGISTER_PRICE_GE;
                $concept    = "singup_business";
            }

            $price = $base + ($price * $num);

            $dateTime = null;
            if ($this->tipo === "paypal_concept") {
                $date = $this->getDate();
                $dateTime = new DateTime($date);
            }

            $discountRate = $empresa->getNextPayDiscount($price, $dateTime);
            $discount = round($price * ($discountRate/100), 2);
            $daysValidLicense = self::DAYS_PREMIUM_LICENSE;
        }

        $toPay = $price-$discount;
        $multiplier = $empresa->getTax()/100;

        $paypalLicenseDate = new DateTimeImmutable("now");
        if (null !== $this->getUID()) {
            $paypalLicenseDate = new DateTimeImmutable($this->getDate());
        }

        $tasas = $empresa->getFeeAmount($toPay, $paypalLicenseDate);

        $dataPyament = array(
            "price" => $price,
            "discount" => $discountRate,
            "quantity" => $num,
            "concept" => $concept,
            "handling" => $tasas,
            "daysValidLicense" => $daysValidLicense
        );

        if ($empresa->mustPayTaxes()) {
            $totalAmount    = $toPay+$tasas;
            $iva            = round($totalAmount *$multiplier, 2);

            $dataPyament["total"]   = $toPay+$tasas+$iva;
            $dataPyament["tax"]     = $iva;
        } else {
            $dataPyament["total"]   = $toPay+$tasas;
            $dataPyament["tax"]     = 0;
        }

        return ( object ) $dataPyament;
    }

    public function getSummary(Ielemento $item, $temporaryAccess = false, $forceItems = null)
    {
        $tpl                = Plantilla::singleton();
        $data               = $this->getPayData($item, $temporaryAccess, $forceItems);
        $empresa            = ( $item instanceof usuario ) ? $item->getCompany() : $item;
        $info               = array();
        $argument           = false;
        $needsPay           = $empresa->needsPay();
        $renewTime          = $empresa->timeFreameToRenewLicense();
        $expiredLicense     = $empresa->hasExpiredLicense();
        $isTemporary        = $empresa->isTemporary();
        $optionalPayment    = $empresa->hasOptionalPayment();

        if ($needsPay || $isTemporary  || $renewTime || (($optionalPayment && $renewTime) || ($optionalPayment && $expiredLicense))) {
            $argument   = $tpl("expl_carga_express");
            $info       = array();

            $info["elementos_a_pagar"]      = $data->concept;
            $info["precio_sin_impuestos"]   = $data->price . "€";

            if (@$data->discount) {
                $info["descuento"] = $data->discount . "%";
            }

            if (0 !== $data->handling) {
                $info["gastos_gestion"] = $data->handling."€";
            }

            if ($empresa->mustPayTaxes()) {
                $info["iva"] = $data->tax."€";
            }

            //$info["precio_sin_descuento"] = "<strong>{$data->total}€</strong>";
            $info["precio_total"] = "<strong>{$data->total}€</strong>";

            $info = array(
                "text" => $argument,
                "data" => $info,
                "quantity" => $data->quantity
            );
        }

        return $info;
    }

    public function createPayConcept(usuario $usuario, $short = false, $temporaryAccess = false, $forceItems = null)
    {
        $db              = db::singleton();
        $customKey       = self::createCustomKey($short);
        $data            = $this->getPayData($usuario, $temporaryAccess, $forceItems);
        $data->customKey = $customKey;
        $data->required  = $usuario->getCompany()->isPaymentRequired();

        $required = (int) $data->required;

        // Insertamos el concepto de pago
        $concepts = self::TABLE_ITEM;
        $sql = "INSERT INTO {$concepts} (uid_usuario, uid_empresa, items, custom, discount, total, price, daysValidLicense, required)
        VALUES (
            {$usuario->getUID()},
            {$usuario->getCompany()->getUID()},
            {$data->quantity},
            '$customKey',
            '{$data->discount}',
            '{$data->total}',
            '{$data->price}',
            '{$data->daysValidLicense}',
            {$required}
        )";

        if ($db->query($sql)) {
            $data->intentId = $db->getLastId();

            return $data;
        }

        return false;
    }

    public function paymentInfo()
    {
        $info               = $this->getInfo();
        $numItems           = (int) $info['items'];
        $company            = $this->getCompany();
        $temporaryAccess    = $this->temporaryAccess();
        $data               = $this->getPayData($company, $temporaryAccess, $numItems);

        return [
            'total'     => $data->total,
            'subtotal'  => $data->total - $data->tax,
            'price'     => $data->total - $data->tax - $data->handling,
            'tax'       => $data->tax,
            'fee'       => $data->handling
        ];
    }

    public function urlToPaypal (
        usuario $usuario = null,
        empresa $company = null,
        $dev = false,
        $formItems = null,
        $temporaryAccess = false,
        $forceItems = null
    ) {
        if ($payData = $this->createPayConcept($usuario, false, $temporaryAccess, $forceItems)) {
            $template   = Plantilla::singleton();

            if (!$company instanceof empresa) {
                $company = $usuario->getCompany();
            }

            $lang = $company->getCountry()->getLanguage();

            $formItems = [
                "item_name_1"   => $template->getString($payData->concept, $lang),
                "amount_1"      => ($payData->total - $payData->handling - $payData->tax),
                "custom"        => $payData->customKey,
                "first_name"    => $usuario->obtenerDato("nombre"),
                "last_name"     => $usuario->obtenerDato("apellidos"),
                "email"         => $usuario->getEmail()
            ];

            if (0 !== $payData->handling) {
                $formItems["item_name_2"] = $template->getString("gastos_gestion");
                $formItems["amount_2"] = $payData->handling;
            }

            if ($company->mustPayTaxes()) {
                $totalTax               = $formItems["amount_1"] + $formItems["amount_2"];
                $tax                    = $company->getTax()/100;
                $formItems["tax_cart"]  = round($totalTax*$tax, 2);
            }

            return parent::urlToPaypal($usuario, $company, $dev, $formItems);
        } else {
            return false;
        }
    }

    public static function getFieldTable()
    {
        return ["uid_paypal_concept", "total", "price", "discount", "uid_empresa", "items", "date", "daysValidLicense"];
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $arrayCampos = new FieldList();

        return $arrayCampos;
    }

    public function sendTransferDoneEmail()
    {
        // leter it will be re-translated, but we need here the _() for the parser
        $subject = "We have received the payment for your license";
        $view = 'transfer/received.html';

        $this->sendEmail($subject, $view);
    }

    /**
     * @return The user who created the paypalLicense in the BD
     * (The user who initiated the transfer payment process)
     */
    public function getPayerUser()
    {
        return new usuario($this->obtenerDato('uid_usuario'));
    }

    public function toArray($app = null)
    {
        $data = [
            'item_count'    => $this->obtenerDato('items'),
            'type_name'     => $this->getTypeName()
        ];

        return $data;
    }

    /**
     * @return int
     */
    public function getSaleId(): int
    {
        $info = $this->getInfo();
        $custom =  $info['custom'];

        $join = 'agd_data.paypal';

        $sql = "SELECT p.sale_id FROM {$this->tabla} pc
                INNER JOIN {$join} p
                ON p.custom = pc.custom
                WHERE p.custom = '{$custom}'";

        $saleId = $this->db->query($sql, 0, 0);

        return (int) $saleId;
    }
}
