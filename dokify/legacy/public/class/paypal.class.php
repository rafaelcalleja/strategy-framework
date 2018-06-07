<?php

abstract class paypal extends elemento implements Ipaypal
{

    const BANK_ACCOUNT 				= '0081 5365 19 0001048308';
	const BANK_IBAN 				= 'ES 22 0081 5365 19 0001048308';
	const BANK_SWIFT 				= 'BSABESBB';
    const TEMP_PAYPAL_CUSTOM_HEADER = "TEMP";

    // Objeto db
    protected $db = null;

    public function __construct($param = false, $extra = false){
        throw new Exception("Error calling a construc method paypal");
    }

    public function getConceptName () {
        return __CLASS__;
    }

    public static function isIPNVerified(){
        $api = ( $_POST["business"] == self::BUSINESS_DEV ) ? "ssl://www.sandbox.paypal.com" : "ssl://www.paypal.com";

        // Montamos el POST
        $post = self::getVerificationURL();

        // Cabeceras de la peticion
        $header  = "POST /cgi-bin/webscr HTTP/1.1\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Host: www.paypal.com\r\n";
        $header .= "Connection: close\r\n";
        $header .= "Content-Length: " . strlen($post) . "\r\n\r\n";

        $fp = fsockopen($api, 443, $errno, $errstr, 30);

        if ($fp) {
            fputs($fp, $header . $post);
            $response = "";
            while( !feof($fp) ){
                $response .= trim(fgets ($fp, 1024));
            }

            $valid = strpos($response, "VERIFIED") !== false;

            return $valid ? true : $response;
        } else {
            error_log( "Error al conectar con paypal en $api" );
            throw new Exception("No se puede conectar con el servidor en $api");
        }


    }


    public function saveTransaction($mode="ipn", $post = array()){
        // Tabla donde vamos a guardar los datos
        $tableFields = db::getColumnNames( TABLE_TRANSACTION );
        $db = db::singleton();

        // Buscamos el id de la transacción
        if( isset($post["txn_id"]) ){
            $transactionID = $post["txn_id"];

            // Si la transacción existe, la verificamos
            if( $data = paypal::checkTransactionId($transactionID) ){
                $nocheck = array("payment_status", "mc_fee");
                foreach($data as $field => $value ){
                    if( !in_array($field,$nocheck) && ( !isset($post[$field]) || $post[$field] != $value ) ){
                        throw new Exception("No coinciden los datos de la tabla de transacción con los recibidos mediante $mode");
                    }
                }

                // Si es correcta la información, pasamos a actualizar para saber que hemos recibido todo correctamente.
                $dataPaypal = array($mode => 1, "payment_status" => @$post["payment_status"], "mc_fee" => @$post["mc_fee"]);
                if( paypal::updateTransaction($transactionID, $dataPaypal) ){
                    return $transactionID;
                } else {
                    throw new Exception("No se puede actualizar la información de la transacción");
                }
            }
        } else {
            throw new Exception("No se encuentra el id de la transacción");
        }

        // Recojemos todos los valores
        $fields = $values = array();

        foreach ($tableFields as $colname) {

            if (isset($post[$colname])) {
                $value = $post[$colname];
                $fields[] = "`$colname`";
                $values[] = "'". utf8_decode(db::scape($value)) ."'";
            }
        }

        // Si no hay ningun valor, algo va mal
        if( !count($fields) ){
            throw new Exception("Error al recibir la información");
        }

        // Esto hace que se envíe una peticion http a http://playthesound.dokify.net y ahí ya lo que quieras!
        if( CURRENT_ENV == "prod" ) playthesound();

        // Guardamos el modo en el que recibimos la info
        $fields[] = "`$mode`"; $values[] = 1;

        // Montamos y lanzamos la query
        $sql = "INSERT INTO ". TABLE_TRANSACTION ." ( ". implode(", ", $fields) . " ) VALUES ( ". implode(", ", $values) ." )";
        if( $db->query($sql) ){
            return $transactionID;
        } else {
            throw new Exception("Error sql: ". $db->lastError() ."\nSQL: $sql");
        }
    }

    public static function deleteTransactions($txnParent) {
        $db = db::singleton();

        $sql = "DELETE from ". TABLE_TRANSACTION ." where txn_id = '". $txnParent ."'";
        if (!$db->query($sql)) {
            throw new Exception("Error deleting trasaction sql: ". $db->lastError() ."\nSQL: $sql");
        }

        return true;
    }

    public static function updateTransaction($id, $fields ){

        $updateFields = array();
        foreach ($fields as $nameField => $valueField) {
            $updateFields[] = " `$nameField` = '". db::scape($valueField). "'";
        }

        if (count($updateFields)) {
            $sql = "UPDATE ". TABLE_TRANSACTION ." SET ". implode(" , ", $updateFields) ." WHERE txn_id = '". db::scape($id) ."' ";
            return db::get($sql);
        }

        throw new Exception("Error actualizando la transacción");

    }

    public static function checkTransactionId($id){
        $sql = "SELECT ". implode(", ", self::getImportantFields() ) ." FROM ". TABLE_TRANSACTION ." WHERE txn_id = '". db::scape($id) ."' LIMIT 1";
        return db::get($sql, 0, "*");
    }

    public static function getImportantFields(){
        return array( "mc_gross", "payer_id", "payment_status");
    }

    public static function getTransactionData($id, $accessTemp = false){
        $db         = db::singleton();
        $caller     = get_called_class();
        $fields     = $caller::getFieldTable();
        $fieldList  = implode(",", $fields);
        $id         = db::scape($id);

        $transactions   = TABLE_TRANSACTION;
        $items          = $caller::TABLE_ITEM;

        $sql = "SELECT mc_gross, payment_status, payment_date, item_name1, tax, mc_fee, paypal.sale_id, {$fieldList}
        FROM {$transactions}  paypal
        INNER JOIN {$items}
        USING(custom)
        WHERE txn_id = '{$id}'
        LIMIT 1";

        $data = $db->query($sql, 0, "*");

        return (object) utf8_multiple_encode($data);
    }

    public function getCompany(){
        $info = $this->getInfo();
        $uid = $info["uid_empresa"];
        if (is_numeric($uid)) {
            return new empresa($uid);
        }

        return false;
    }


    /************ A PARTIR DE AQUI TODAS LAS FUNCIONES SON ESPECIFICAS DE AGD O DE APOYO ******************************/


    public static function getTransactionId(Ielemento $item){
        $cache = cache::singleton();
        $cacheString = "paypal-getTransactionId-{$item}";
        if (null !== $txnId = $cache->getData($cacheString)) {
            return $txnId;
        }

        $db = db::singleton();
        $caller = get_called_class();
        $sql = "SELECT txn_id FROM ". TABLE_TRANSACTION ." INNER JOIN ". $caller::TABLE_ITEM ." USING(custom) WHERE payment_status = 'Completed'";
        switch( get_class($item) ){
            case "empresa":
                $sql .= " AND uid_empresa = {$item->getUID()}";
            break;
            case "usuario":
                $sql .= " AND uid_usuario = {$item->getUID()}";
            break;
        }

        $sql .= " ORDER BY date DESC LIMIT 1";

        $txnId = $db->query($sql, 0, 0);
        if( $txnId = trim($txnId) ){
            $cache->addData($cacheString, $txnId);
            return $txnId;
        }

        $cache->addData($cacheString, false);
        return false;
    }

    public static function getTransactionTimestamp($txn){
        $db = db::singleton();
        $caller = get_called_class();
        $sql = "SELECT `payment_date` FROM ". TABLE_TRANSACTION ." INNER JOIN ". $caller::TABLE_ITEM ." USING(custom) WHERE txn_id = '{$txn}'";
        if( $date = $db->query($sql, 0, 0) ){
            return strtotime($date);
        }
        return false;
    }


    public static function getUserFromTransaction($id){
        $caller = get_called_class();
        $sql = "SELECT uid_usuario FROM ". TABLE_TRANSACTION ." INNER JOIN ". $caller::TABLE_ITEM ." USING(custom) WHERE txn_id = '". db::scape($id) ."' LIMIT 1";
        $uid = db::get($sql, 0, 0);
        if( is_numeric($uid) ){
            return new usuario($uid);
        } else {
            return false;
        }
    }

    public function notifyMismatchPayment($expectedAmount, $payedAmount, empresa $company){

        $plantilla = new Plantilla();
        $plantilla->assign("expectedAmount", $expectedAmount);
        $plantilla->assign("payedAmount", $payedAmount);
        $plantilla->assign("company", $company);
        if ($this instanceof invoice){
            $plantilla->assign("invoice", $this);
        }

        if( CURRENT_ENV == 'dev' ) {
            $email = email::$developers;
        } else{
            $email = email::$facturacion;
        }

        $email = new email($email);
        $htmlPath ='email/paypal/notifyMismatchPayment.tpl';
        $html = $plantilla->getHTML($htmlPath);
        $email->establecerContenido($html);
        $email->establecerAsunto("Pago no coincide");

        $estado = $email->enviar();
        if( $estado !== true ){
            $estado = $estado && trim($estado) ? trim($estado) : $plantilla('error_desconocido');
            error_log("error enviando email paypal:notifyMismatchPayment error:".$estado);
        }

        return true;
    }

    public function regenerateCustom ($short = true) {
        $custom = self::createCustomKey($short);

        $SQL = "UPDATE {$this->tabla} SET custom = '{$custom}' WHERE uid_{$this->tipo} = {$this->getUID()}";
        if (db::get($SQL)) {
            return $custom;
        }

        return false;
    }

    public function notifyIssuePaymentStatus($company, $status, $txnId = false){

        $plantilla = new Plantilla();

        $plantilla->assign("element", $this);
        $plantilla->assign("status", $status);
        $plantilla->assign("company", $company);
        $plantilla->assign("txnId", $txnId);

        if( CURRENT_ENV == 'dev' ) {
            $email = email::$developers;
        } else{
            $email = email::$facturacion;
        }

        $email = new email($email);
        $htmlPath ='email/paypal/notifyStatusPayment.tpl';
        $html = $plantilla->getHTML($htmlPath);
        $email->establecerContenido($html);
        $email->establecerAsunto("Pago Estado {$status}");

        $estado = $email->enviar();
        if( $estado !== true ){
            $estado = $estado && trim($estado) ? trim($estado) : $plantilla('error_desconocido');
            error_log("error enviando email paypal:notifyMismatchPayment error:".$estado);
        }

        return true;
    }

    public static function getCustomFromIntent ($id) {
        $db = db::singleton();
        $id = db::scape($id);

        $sql = "SELECT custom FROM ". TABLE_INVOICE ." where uid_invoice = $id";
        if ($custom = $db->query($sql, 0, 0)) {
            return $custom;
        }

        $sql = "SELECT custom FROM ". paypalLicense::TABLE_ITEM ." where uid_paypal_concept = $id";
        if ($custom = $db->query($sql, 0, 0)) {
            return $custom;
        }

        return false;
    }

    /**
     * Check if the paypal is already completed
     * @return boolean
     */
    public function isCompleted()
    {
        $db = db::singleton();
        $transactions = TABLE_TRANSACTION;
        $custom = $this->getCustom();

        $sql = "SELECT uid_paypal
        FROM {$transactions}
        INNER JOIN {$this->tabla}
        USING (custom)
        WHERE payment_status LIKE 'Completed'
        AND custom LIKE '{$custom}'";

        return (bool) $db->query($sql, 0, 0);
    }

    public static function createCustomKey($short = false){

        if ($short) {
            $customKey = "DKY" . strtoupper(substr(md5(uniqid()), 0, 6));
            // Removing 0 and 0's from the kustom
            $needle = array("O", "0");
            foreach ($needle as $has) {
                if ((strpos($customKey, $has)) !== false) return self::createCustomKey($short);
            }
        } else {
            $customKey =  buscador::getRandomKey();
        }

        // if custom already exists
        if (paypal::instanceFromCustom($customKey)) {
            return self::createCustomKey($short);
        }
        return $customKey;
    }

    // ---------- FUNCIONES ESTATICAS
    public static function getVerificationURL(){
        // Montamos el POST
        $data = array("cmd" => "_notify-validate");
        $data = array_merge($data, $_POST);
        //$data = array_map("urlencode", $data);
        $post = http_build_query($data);
        return $post;
    }

    public static function instanceFromCustom($custom){

        $sql = "SELECT uid_invoice FROM ". TABLE_INVOICE ." where '{$custom}' LIKE CONCAT('%',custom,'%')";
        $uid = db::get($sql, 0, 0, "invoice");
        if (is_numeric($uid)) {
            return new invoice($uid);
        }

        $sql = "SELECT uid_paypal_concept FROM ". paypalLicense::TABLE_ITEM ." where '{$custom}' LIKE CONCAT('%',custom,'%')";
        $uid = db::get($sql, 0, 0);
        if (is_numeric($uid)) {
            return new paypalLicense($uid);
        }

        return false;
    }

    /**
     * Locate the custom of a transaction based on the amount and from (company name)
     * @param  float    $amount
     * @param  string   $from
     * @return string
     */
    public static function locateCustomFromAmout($amount, $from)
    {
        $invoices  = TABLE_INVOICE;
        $licenses  = TABLE_TRANSACTION . '_concept';
        $companies = TABLE_EMPRESA;
        $txns      = TABLE_TRANSACTION;

        $amount  = number_format($amount, 2);
        $customs = [];

        $names   = [];
        $strings = explode(' ', trim($from));
        foreach ($strings as $string) {
            $names[] = "empresa.nombre LIKE '%". db::scape($string) ."%'";
        }

        $names = implode(' AND ', $names);

        $SQL = "SELECT custom
        FROM {$invoices}
        INNER JOIN {$companies}
        USING (uid_empresa)
        LEFT JOIN {$txns}
        USING (custom)
        WHERE uid_paypal IS NULL
        AND amount < {$amount}
        AND sent_date > NOW( ) - INTERVAL 15 DAY
        AND ({$names})";

        $invoiceCustoms = db::query($SQL, '*', 0);

        if ($invoiceCustoms) {
            $customs = array_merge($customs, $invoiceCustoms);
        }

        $SQL = "SELECT custom
        FROM {$licenses}
        INNER JOIN {$companies}
        USING (uid_empresa)
        LEFT JOIN {$txns}
        USING (custom)
        WHERE uid_paypal IS NULL
        AND total = {$amount}
        AND `date` > NOW( ) - INTERVAL 15 DAY
        AND ({$names})";

        $licenseCustoms = db::query($SQL, '*', 0);

        if ($licenseCustoms) {
            $customs = array_merge($customs, $licenseCustoms);
        }

        if (count($customs) === 0) {
            throw new Exception("No customs founds", 1);
        }

        if (count($customs) === 1) {
            return reset($customs);
        }

        $founds = implode(', ', $customs);
        throw new Exception("Too many customs: {$founds}", 1);
    }

    public function urlToPaypal(
        usuario $usuario = null,
        empresa $company = null,
        $dev = false,
        $formItems = null,
        $temporaryAccess = false
    ) {
        $api = 'https://www.paypal.com/cgi-bin/webscr';
        $business = paypal::BUSINESS;

        if ($dev == true) {
            $api = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
            $business = paypal::BUSINESS_DEV;
        }

        $dominio = CURRENT_DOMAIN;
        $ipn = CURRENT_DOMAIN . "/paypal";

        $formfields = [
            "cmd" => "_cart",
            "upload" => 1, //Upload the contents of a shopping cart
            "rm" => 2, //form method: redirected to the return URL by the POST method
            "quantity" => 1,
            "business" => $business,
            "currency_code" => "EUR",
            "no_shipping" => "1",
            "cancel_return" => "$ipn/response.php?action=error",
            "return" => "$ipn/response.php",
            "notify_url" => "$ipn/ipn/",
            "cbt" => "Volver a la aplicación",
            "charset" => "UTF-8",
            "lc" => strtoupper(Plantilla::getCurrentLocale()),
        ];

        if (pais::SPAIN_CODE === (int) $company->obtenerDato('uid_pais')) {
            $cityName = "";
            $stateName = "";

            if ($city = $company->obtenerMunicipio()) {
                $cityName = $city->getUserVisibleName();
            }

            if ($state = $company->obtenerProvincia()) {
                $stateName = archivo::cleanFilenameString($state->getUserVisibleName());
            }

            $addressData = [
                "address_override" => "1",
                "address1" => $company->obtenerDato("direccion"),
                "address2" => "",
                "city" => $cityName,
                "state" => $stateName,
                "country" => "ES",
                "zip" => $company->obtenerDato("cp"),
            ];

            $formfields = array_merge($formfields, $addressData);
        }

        $paypalFields = array_merge($formfields, $formItems);
        $url = $api . "?" . http_build_query($paypalFields);

        return $url;
    }

    protected function sendEmail($subject, $view)
    {
        $recipients = $this->getRecipientsToBeNotified();

        // the "view-ready" name for the model
        $class = get_called_class();
        $type = $class::getRouteName();
        $model = $this->toArray();

        foreach ($recipients as $emailAddress => $locale) {
            // using a translated string
            $translatedSubject = new Dokify\I18NString($subject, $locale);
            $template = "email/{$type}/{$view}";

            $email = new Dokify\TwigEmail($translatedSubject, $template, [$type => $model]);
            $email->setLocale($translatedSubject->getLocale());

            if (CURRENT_ENV === 'dev') {
                if (php_sapi_name() === 'cli') {
                    print "Real notification email: {$emailAddress}\n";
                }

                $emailAddress = email::$developers;
            }

            $email->send($emailAddress);
        }
    }

    private function getRecipientsToBeNotified()
    {
        $recipients = [];
        $localeMap = getLocaleMap();

        $company = $this->getCompany();
        $plantillaemail = new plantillaemail(plantillaemail::TIPO_INVOICE_NOTIFICATION);
        $contacts = $company->obtenerContactos($plantillaemail);

        foreach ($contacts as $contact) {
            $language = $contact->getLanguage();
            $locale = $localeMap[$language];

            $recipients[$contact->obtenerEmail()] = $locale;
        }

        if ($companyMainContact = $company->obtenerContactoPrincipal()) {
            $language = $companyMainContact->getLanguage();
            $locale = $localeMap[$language];

            $recipients[$companyMainContact->obtenerEmail()] = $locale;
        }

        if ($payerUser = $this->getPayerUser()) {
            $info = $payerUser->getInfo();

            if (true == $userLang = $info['locale']) {
                $locale = $localeMap[$userLang];
            } else {
                $locale = 'es_ES';
            }

            $recipients[$payerUser->getEmail()] = $locale;
        }

        return $recipients;
    }


    /**
     * [getPayerUser returns the user who make this transaction, the child classes should implement it]
     * @return [void]
     */
    public function getPayerUser()
    {
    }

}
