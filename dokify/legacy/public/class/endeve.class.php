<?php
require_once(dirname(__FILE__) . "/quaderno/quaderno_load.php");

class endeve
{
    const INI_NAME = "endeve.account";
    const INI_KEY = "endeve.key";
    CONST PAYMENT_METHOD_PAYPAL = "paypal";
    CONST PAYMENT_METHOD_TRANSFER = "wire_transfer";
    CONST PAYMENT_METHOD_CLIENT = "client";

    private static function init()
    {
        $key = get_cfg_var(endeve::INI_KEY);
        $name = get_cfg_var(endeve::INI_NAME);
        if (!$key) die("No esta definida la clave endeve.key en " . endeve::INI_KEY);
        if (!$name) die("No esta definida la clave endeve.account en " . endeve::INI_NAME);

        QuadernoBase::init($key, $name, CURRENT_ENV === 'dev');
    }

    public static function cronCall($time, $force = false)
    {
        $isTime = (date("H:i", $time) == "06:30");
        if (!$isTime && !$force) return true;

        $db = db::singleton();
        $table = TABLE_INVOICE;
        $SQL = "SELECT uid_invoice FROM {$table} WHERE download_url = ''";
        $rs = $db->query($SQL);
        $total = $db->getNumRows();
        $synced = 0;
        $i = 1;

        if (isset($_SERVER['PWD'])) print "Sync for {$total} invoices...\n";

        while ($row = db::fetch_row($rs)) {
            list ($uid) = $row;
            $invoice = new invoice($uid);

            if (isset($_SERVER['PWD'])) print "Syncing {$i} of {$total} ";

            if ($url = $invoice->getInvoiceURL()) {
                $SQL = "UPDATE {$table} SET download_url = '{$url}' WHERE uid_invoice = {$invoice->getUID()}";
                if ($db->query($SQL)) {
                    if (isset($_SERVER['PWD'])) print "Ok";
                    $synced++;
                } else {
                    if (isset($_SERVER['PWD'])) print "Error!";
                }

            } else {
                if (isset($_SERVER['PWD'])) print "Not found!";
            }

            if (isset($_SERVER['PWD'])) print "\n";

            $i++;
        }

        if (isset($_SERVER['PWD'])) print "{$synced} synced...\n";

        return true;
    }

    public static function getInvoice($id)
    {
        // prevent die...
        if (!get_cfg_var(endeve::INI_KEY)) return false;

        self::init();

        return QuadernoInvoice::find($id);
    }

    public static function getEndeveId(empresa $empresa)
    {
        self::init();
        $name = $empresa->obtenerDato("nombre");
        $contactID = $empresa->obtenerDato("endeve_id");

        if (!$contactID) {
            $cif = $empresa->obtenerDato("cif");

            $data = array(
                'first_name' => $name,
                'full_name' => $name,
                'last_name' => ' ',
                'tax_id' => $cif
            );

            $data['language'] = $empresa->getCompatibleLanguageQuaderno();
            $country = $empresa->getCountry();
            $data['country'] = $country->getCharCode();

            $contacto = $empresa->obtenerContactoPrincipal();
            if ($contacto instanceof contactoempresa) {
                if ($mail = trim($contacto->obtenerDato("email"))) {
                    if (CURRENT_ENV == 'dev') $data["email"] = implode(",", email::$developers);
                    else $data["email"] = str_replace(";", ",", $mail);
                }
                if ($tlf = trim($contacto->obtenerDato("telefono"))) {
                    $data["phone_1"] = $tlf;
                }
                if ($tlf = trim($contacto->obtenerDato("movil"))) {
                    $data["phone_2"] = $tlf;
                }

                // TLF
                if (!isset($data["phone_1"]) && isset($data["phone_2"])) {
                    $data["phone_1"] = $data["phone_2"];
                }

                if ($name = trim($contacto->getUserVisibleName())) {
                    $data["contact_name"] = $name;
                }
            }

            if ($direccion = $empresa->obtenerDato("direccion")) {
                $data["street_line_1"] = mb_substr($direccion, 0, 255, "utf8");
            }
            if ($postalcode = $empresa->obtenerDato("cp")) $data["postal_code"] = mb_substr($postalcode, 0, 5, "utf8");
            if ($city = $empresa->obtenerDato("uid_municipio")) {
                $municipio = new municipio($city);
                $data["city"] = mb_substr($municipio->getUserVisibleName(), 0, 255, "utf8");
            }

            $contact = new QuadernoContact($data);

            if ($contact->save()) {
                $contactID = $contact->id;

                if (!$empresa->update(array("endeve_id" => $contactID), "endeve")) {
                    error_log("No se puede guardar su id de contacto");
                    die("No se puede guardar su id de contacto");
                }
            } else {
                if ($contact->errors && count($contact->errors)) {
                    error_log("endeve.class getEndeveId error company [{$empresa->getUID()}]: ");
                    foreach ($contact->errors as $field => $errors) {
                        $errorString = implode(", ", $errors);
                        error_log("{$field}: " . $errorString);
                    }
                }

                return false;
            }
        }

        return $contactID;
    }

    public static function createSale(empresa $empresa, $contactID, array $items, $saleNumber = NULL, $method = false, \DateTimeImmutable $date)
    {
        self::init();
        $lang = $empresa->getCountry()->getLanguage();
        $template = new Plantilla();
        $totalAmount = 0;
        $invoiceData = array(
            'contact_id' => $contactID,
            'currency' => 'EUR',
            'items_attributes' => array()
        );

        if ($method) $invoiceData['tag_list'] = $method;

        foreach ($items as $item) {

            $toPay = $item['unit_price'];
            $tax = $empresa->getTax();
            $discount = false;

            $quantity = (isset($item['quantity'])) ? $item['quantity'] : "1";

            if (isset($item['discount']) && $quantity > 0) {
                $discount = round(($item['unit_price'] * $quantity) * ($item['discount'] / 100), 2);
                $toPay = ($item['unit_price'] * $quantity) - $discount;
            } else {
                $toPay = $item['unit_price'] * $quantity;
            }

            $totalAmount += $toPay;

            $description = $template->getString($item['description'], $lang);
            if (true === isset($item['elements'])) {
                $extraDescription = ($item['elements'] > 1) ? 'plus_num_elements' : 'plus_element';
                $description .= ' ' . str_replace('%num%', $item['elements'], $template->getString($extraDescription, $lang));
            }

            $itemData = [
                'description' => $description,
                'unit_price' => $item['unit_price'],
                'quantity' => $quantity,
            ];
            if (isset($item['discount'])) $itemData['discount_rate'] = $item['discount'];

            if ($empresa->mustPayTaxes()) {
                $itemData["tax_1_name"] = "IVA";
                $itemData["tax_1_rate"] = $tax;
            }

            $invoiceData['items_attributes'][] = $itemData;

        }

        $gastosGestion = $empresa->getFeeAmount($totalAmount, $date);

        if (0 !== $gastosGestion) {
            $gastosData = array('description' => $template->getString("gastos_gestion", $lang), 'unit_price' => $gastosGestion, 'quantity' => 1);

            if ($empresa->mustPayTaxes()) {
                $gastosData["tax_1_name"] = "IVA";
                $gastosData["tax_1_rate"] = $tax;
            }

            $invoiceData['items_attributes'][] = $gastosData;
        }

        $sale = new QuadernoInvoice($invoiceData);

        if ($sale->save()) {
            $saleID = $sale->id;
            return $saleID;

        } else {
            if ($sale->errors && count($sale->errors)) {
                $dataParams = json_encode($invoiceData);
                error_log("endeve.class createSale error: company [{$empresa->getUID()}], contactId [$contactID], data: $dataParams\n");
                foreach ($sale->errors as $field => $errors) {
                    $errorString = implode(", ", $errors);
                    error_log("{$field}: " . $errorString);
                }
            }

            return false;
        }
    }

    public static function payItem(empresa $empresa, $saleID, array $payments, $sendEmail = true, $method = endeve::PAYMENT_METHOD_PAYPAL)
    {
        self::init();
        $invoice = QuadernoInvoice::find($saleID);

        if (!$invoice) {
            error_log("error endeve.class payItem : No se encuentra el invoice con uid: " . $saleID);
            return false;
        }

        foreach ($payments as $payment) {

            $itemPayment = new QuadernoPayment(
                array(
                    'date' => date('Y-m-d'),
                    'amount' => $payment["amount"],
                    'currency' => 'EUR',
                    'payment_method' => $method
                )
            );

            $invoice->addPayment($itemPayment);
        }

        if ($invoice->save()) {
            if ($sendEmail) {
                $status = $invoice->deliver();
                return true;
            }
            return true;
        } else {
            if ($invoice->errors && count($invoice->errors)) {
                error_log("endeve.class createSale error: company [{$empresa->getUID()}], saleId [$saleId]\n");
                foreach ($invoice->errors as $field => $errors) {
                    $errorString = implode(", ", $errors);
                    error_log("{$field}: " . $errorString);
                }
            }

            return false;
        }
    }

    public function deleteInvoice($saleID)
    {
        self::init();
        $invoice = QuadernoInvoice::find($saleID);

        if (!$invoice) {
            error_log("error endeve.class deleteInvoice : No se encuentra el invoice con uid: " . $saleID);
            return false;
        }

        if ($invoice->delete()) {
            error_log("endeve.class deleteInvoice: [saleId: $saleID]\n");
            return true;
        } else {
            foreach ($invoice->errors as $error) {
                error_log("endeve.class deleteInvoice error: [{$error}] [saleId: $saleID]\n");
            }
            return false;
        }
    }

    public function notifyError($saleID, $company)
    {
        $template = new Plantilla();
        $htmlPath = 'email/invoice/errordeleting.tpl';

        if (CURRENT_ENV == 'dev') {
            $email = email::$developers;
        } else {
            $email = email::$facturacion;
        }

        $template->assign("saleID", $saleID);
        $template->assign("companyName", $company->getUserVisibleName());

        $email = new email($email);
        $html = $template->getHTML($htmlPath);

        $email->establecerContenido($html);
        $email->establecerAsunto("Error eliminando factura de quaderno: " . $saleID);

        $estado = $email->enviar();

        if ($estado !== true) {
            $estado = $estado && trim($estado) ? trim($estado) : $template('error_desconocido');
            error_log("error enviando email endeve:notifyError error:" . $estado);
        }

        return true;
    }
}

?>
