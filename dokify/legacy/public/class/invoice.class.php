<?php

class invoice extends paypal implements Iinvoice, Ielemento
{
    const TABLE_INVOICE = "agd_data.invoice";
    const TABLE_ITEM = "agd_data.invoice";
    const PRIMARY_KEY_TABLE_ITEM = "uid_invoice";
    const TAG_ENDEVE_VALIDATION = "validation";

    const PAYMENT_INFO = 1;
    const REMINDER_PAYMENT = 2;
    const CLOSE_NOTIFICATION = 3;

    const TIME_FRAME_REMINDER_PAYMENT = 7;
    const RESTRICTIVE_TIME_FRAME_CLOSE_NOTIFICATION = 20;
    const TIME_FRAME_CLOSE_NOTIFICATION = 90;
    const DATE_RESTRICT_TIMEFRAME = "2013-10-08";
    const DATE_NOT_APPLY_TAXES = "2013-11-07";

    const DATE_CREDIT_MEMO = "2016-01-01 00:00:00"; // Fecha hasta la cuál se han abonado las facturas pendientes

    const LIMIT_EUROS_INVOICE = 10;
    const MIN_LIMIT_EUROS_INVOICE = 1;
    const MAX_LIMIT_DAYS_TO_INVOICE = 45;

    const DAYS_APP_CLOSED               = 91;
    const RESTRICTIVE_DAYS_APP_CLOSED   = 21;

    public function __construct($param = false, $extra = false)
    {
        $this->tipo = "invoice";
        $this->tabla = TABLE_INVOICE;

        $this->instance($param, $extra);
    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Invoice\Invoice
     */
    public function asDomainEntity()
    {
        $info = $this->getInfo();

        $invoiceCreationDate = new DateTime($info['date']);
        $invoiceSentDate     = ($tstmp = $info['sent_date']) ? new DateTime($tstmp) : null;

        // Instance the entity
        $entity = new \Dokify\Domain\Invoice\Invoice(
            new \Dokify\Domain\Invoice\InvoiceUid($this->getUID()),
            new \Dokify\Domain\Company\CompanyUid($info['uid_empresa']),
            $invoiceCreationDate,
            $invoiceSentDate,
            $info['sale_id'],
            $info['custom'],
            $info['amount'],
            $info['download_url']
        );

        return $entity;
    }

    public static function defaultData($data, Iusuario $usuario = null)
    {
        $data["date"] = date("Y-m-d H:i:s");
        $data["custom"] = self::createCustomKey(true);
        $data["sent_date"] = 'null';

        return $data;
    }

    public function updateData($data, Iusuario $usuario = null, $mode = null)
    {
        if (false === isset($data['sale_id'])) {
            return $data;
        }

        // if sale_id is updated we should update the URLs too
        if (false === $invoice = endeve::getInvoice($data['sale_id'])) {
            throw new \Exception(_('Invoice in Quaderno not found'));
        }

        $amount = number_format($this->getTotalAmount(), 2, '.', ',');
        $totalAmount = (float) str_replace('€', '', $invoice->total);
        $totalNumber = number_format($totalAmount, 2, '.', ',');
        $amountDiff = $totalNumber - $amount;

        if ($amountDiff >= 0.020001 || $amountDiff < -0.02 && $amount > 0) {
            throw new \Exception(_('Invalid invoice amount'));
        }

        $data['download_url'] = $invoice->permalink;
        return $data;
    }

    public static function getRouteName()
    {
        return 'invoice';
    }

    public function getConceptName()
    {
        return $this->getUserVisibleName();
    }

    public function getUserVisibleName()
    {
        return "invoice";
    }

    public function getPathType()
    {
        return "invoice";
    }

    public function getTotalAmount($method = endeve::PAYMENT_METHOD_PAYPAL)
    {
        return $this->paymentInfo()["total"];
    }

    public function paymentInfo()
    {
        $company = $this->getCompany();
        $price = $this->getPrice();
        $invoiceSentDate = new DateTimeImmutable($this->getSentDate());
        $fee = $company->getFeeAmount($price, $invoiceSentDate);
        $taxIndex = $company->mustPayTaxes() ? $company->getTax() / 100 : 0;

        $taxesRestrict = new DateTime(self::DATE_NOT_APPLY_TAXES);
        $invoiceDate = new DateTime($this->getInvoiceDate());
        $applyTaxToFee = $invoiceDate > $taxesRestrict; //if invoice is greater than the restrict time , apply fee to tax

        $amountApplyTaxes = $price;
        if ($applyTaxToFee) $amountApplyTaxes += $fee;

        $tax = round($amountApplyTaxes*$taxIndex,2); //adding taxes to the management expenses
        $subtotal = round($price + $fee, 2);
        $total = round($price + $tax + $fee ,2);

        return [
            'total'         => $total,
            'subtotal'      => $subtotal,
            'price'         => $price,
            'tax'           => $tax,
            'taxIndex'      => $taxIndex,
            'fee'           => $fee,
            'applyToFee'    => $applyTaxToFee
        ];
    }

    /**
      * iface/Ilistable.iface.php
      *
      */
    public function getInlineArray(Iusuario $usuario = null, $config = false, $data = null)
    {
        $inline = [];
        $lang   = Plantilla::singleton();

        if ($url = $this->getInvoiceURL()) {
            $download   = array('img' => RESOURCES_DOMAIN . '/img/famfam/page_white_go.png');
            $download[] = array(
                "nombre"    => $lang('factura'),
                "href"      => $url,
                "target"    => "_blank"
            );

            $inline[] = $download;
        }


        return $inline;
    }


    public function getInvoiceURL()
    {
        if (!$url = trim($this->obtenerDato('download_url'))) {

            if (!$invoice = $this->getInvoice()) {
                return false;
            }

            $url = $invoice->permalink;
        }

        $url = str_replace('http://', 'https://', $url);
        return $url;
    }

    public function getInvoice()
    {
        if (!$saleId = $this->getSaleId()) return false;
        if (!$invoice = endeve::getInvoice($saleId)) return false;

        return $invoice;
    }


    public function getInvoiceDate()
    {
        $info = $this->getInfo();
        return $info["date"];
    }

    public function getInvoiceTimestamp()
    {
        $info = $this->getInfo();
        return strtotime($info["date"]);
    }

    public function getSentTimestamp()
    {
        $info = $this->getInfo();
        return strtotime($info["sent_date"]);
    }

    public function getSentDate()
    {
        $info = $this->getInfo();
        return $info["sent_date"];
    }

    public function getSaleId()
    {
        $info = $this->getInfo();
        return $info["sale_id"];
    }

    public function getCustom()
    {
        $info = $this->getInfo();
        return $info["custom"];
    }

    public function getPrice()
    {
        $info = $this->getInfo();
        $amount = (float)$info["amount"];
        //Redondeamos a tres decimales
        $amount = number_format($amount,2 , '.', '');
        return $amount;
    }

    public function getTypesInvoiced()
    {
        $SQL = "SELECT uid_modulo FROM ". TABLE_INVOICE . " INNER JOIN ". TABLE_INVOICE_ITEM . " USING (uid_invoice)
        WHERE uid_invoice = {$this->getUID()} GROUP BY uid_modulo";

        $modules = $this->db->query($SQL, "*", 0);

        $types = array();

        if ($modules) foreach ($modules as $module) {
            $types[] = util::getModuleName($module);
        }


        return $types;
    }

    public function sendEmailNotification($action, empresa $company, $items = false, $force = false)
    {
        $invoiceItems = $this->getItems();
        if (!$invoiceItems) {
            return false;
        }

        $contactEmail = array();
        $firstDate = $lastDate = false;
        $tpl = "invoice/paymentInfo";

        switch ($action) {
            case self::PAYMENT_INFO:
                $title = "pago_pendiente";
                $subject = "resumen_pago_pendiente";
                break;
            case self::REMINDER_PAYMENT:
                $title = "pago_pendiente";
                $subject = "recordatorio_pago_pendiente";
                break;
            case self::CLOSE_NOTIFICATION:
                $title = "acceso_bloqueado";
                $subject = "resumen_acceso_bloqueado";
                break;
            default:
                break;
        }

        if ($company->obtenerContactoPrincipal()) {
            $contactEmail = null;
        } else {
            $subject .= "_no_contact";
            $contactEmail = email::$facturacion;
        }

        $firstDate = $this->getFirstDateInvoced();
        $lastDate = $this->getLastDateInvoced();
        if ($company->isEnterprise()) {
            $firstDate = new DateTime($firstDate);
            $month     = $firstDate->format('m');
            $year      = $firstDate->format('Y');
            $firstDate = $firstDate->setDate($year, $month, "01");
            $firstDate = $firstDate->format('d-m-Y');

            $lastDate  = new DateTime($lastDate);
            $day       = $lastDate->format('t');
            $month     = $lastDate->format('m');
            $year      = $lastDate->format('Y');
            $lastDate  = $lastDate->setDate($year, $month, $day);
            $lastDate  = $lastDate->format('t-m-Y');

        } else {
            $firstDate = date("d-m-Y", strtotime($firstDate));
            $lastDate = date("d-m-Y", strtotime($lastDate));
        }

        $paymentInfo    = $this->paymentInfo();
        $tax            = $paymentInfo["tax"];
        $fee            = $paymentInfo["fee"];
        $total          = $paymentInfo["total"];
        $subtotal       = $paymentInfo["subtotal"];

        $params = array(
            "invoice"   => $this,
            "items"     => $items,
            "company"   => $company,
            "force"     => $force,
            "total"     => $total,
            "fee"       => $fee,
            "action"    => $action,
            "title"     => $title,
            "firstDate" => $firstDate,
            "lastDate"  => $lastDate,
            "subtotal"  => $subtotal
        );
        if ($tax > 0) {
            $params["tax"] = $tax;
        }

        $infolog = "email: Envio pago invoice {$this->getUID()}";
        $log = array('Pago validación',$infolog , $this->getUID());

        if ($company->isEnterprise()) {
            $contactEmail = email::$facturacion;
        }

        $plantillaemail = new plantillaemail(plantillaemail::TIPO_INVOICE_NOTIFICATION);

        return $this->getCompany()->sendEmailWithParams($subject, $tpl, $params, $log, $plantillaemail, $contactEmail);
    }

    public function getFirstDateInvoced()
    {
        $firstInvoiceItem = $this->getItemOrdered();

        if ($firstInvoiceItem instanceof invoiceItem) {
            $itemReference = $firstInvoiceItem->getItem();
            if ($itemReference) {
                return $itemReference->getDate();
            }
        }

        return false;

    }

    public function getLastDateInvoced()
    {
        $lastInvoiceItem = $this->getItemOrdered("DESC");

        if ($lastInvoiceItem instanceof invoiceItem) {
            $itemReference = $lastInvoiceItem->getItem();
            if ($itemReference) {
                return $itemReference->getDate();
            }
        }

        return false;

    }

    public function isPayed()
    {
        $isPayed = $this->db->query("SELECT uid_invoice FROM " .TABLE_INVOICE. " INNER JOIN " .TABLE_TRANSACTION. " using(custom) WHERE uid_invoice = {$this->getUID()}", 0, 0);
        return (bool)$isPayed;
    }

    public function getNumItems()
    {
        $SQL = "SELECT count(uid_invoice_item) FROM ". TABLE_INVOICE_ITEM ." WHERE uid_invoice = {$this->getUID()}";
        return $this->db->query($SQL, 0, 0);
    }

    public function getItems($limit = false, $description = false)
    {
        $condicion = elemento::construirCondicion(null, $limit);

        $SQL = "SELECT uid_invoice_item FROM ". TABLE_INVOICE_ITEM ." WHERE uid_invoice = {$this->getUID()}";

        if ($description) {
            $SQL .= " AND description = '{$description}'";
        }

        if ($condicion) {
            $SQL .= " AND {$condicion}";
        }

        $items = $this->db->query($SQL, "*", 0, "invoiceItem");

        if ($items) return new ArrayObjectList($items);
        return new ArrayObjectList();
    }

    public function getItemOrdered($order = 'ASC')
    {
        $SQL = "SELECT uid_invoice_item FROM ". TABLE_INVOICE_ITEM ."
                WHERE uid_invoice = {$this->getUID()}
                ORDER BY date $order  LIMIT 1";

        $invoiceItemId = $this->db->query($SQL, 0, 0);

        if (is_numeric($invoiceItemId)) return new invoiceItem($invoiceItemId);

        return false;
    }

    // Existe para usar list.php.
    public function obtenerItems($limit = false)
    {
        return $this->getItems($limit);
    }


    public static function addExportSqlHeaders()
    {
        return true;
    }

    public static function getExportSQL($usuario, $uids, $forced, $parent=false)
    {
        $modules = anexo::getModules();

        $modulosToGetInfo = [
            'validationStatus' => 95,
            'paypalLicense' => 106
        ];

        // Global where
        $where = "";
        if (is_array($uids) && count($uids)) {
            $where .= " AND ii.uid_invoice in (". implode(",", $uids) .")";
        } else {
            if (is_numeric($parent)) {
                $where .= " AND i.uid_empresa = $parent ";
            }
        }

        // Query for paypal license
        $item           = "(SELECT nombre  FROM ". TABLE_EMPRESA ." e WHERE e.uid_empresa = pc.uid_empresa)";
        $attachCompany  = "(SELECT nombre  FROM ". TABLE_EMPRESA ." e WHERE e.uid_empresa = i.uid_empresa)";
        $attachUser     = "(SELECT usuario FROM ". TABLE_USUARIO ." u WHERE u.uid_usuario = pc.uid_usuario)";

        $sql = "SELECT
            uid_invoice_item,
            CONCAT(ii.amount, ' euros')     AS amount,
            ''                              AS doc,
            {$item}                         AS item,
            'licencia'                      AS module,
            ''                              AS status,
            {$attachCompany}                AS company,
            {$attachUser}                   AS user,
            DATE_FORMAT(ii.date,'%d/%m/%Y') AS invoice_date,
            'client'      AS client
        FROM " .TABLE_INVOICE_ITEM. " ii
            INNER JOIN " .TABLE_INVOICE. " i USING(uid_invoice)
            INNER JOIN " . paypalLicense::TABLE_ITEM ." pc ON ii.uid_reference = pc.uid_paypal_concept
        WHERE 1
        AND ii.uid_modulo = {$modulosToGetInfo['paypalLicense']}
        " . $where;

        $unionPart[] = $sql;

        foreach ($modules as $moduleUID => $module) {
            $moduloSinHistorico = str_replace("historico_", "", $module);
            $tableModule = constant("TABLE_".strtoupper($moduloSinHistorico));

            $onClausure = (strstr($module, 'historico')) ?  "USING(uid_anexo)" : "ON uid_anexo_{$module} = uid_anexo";

            if ($moduloSinHistorico === "empresa") {
                $itemSQLName = "item.nombre";
            } elseif ($moduloSinHistorico === "empleado") {
                $itemSQLName = "CONCAT(item.nombre, ' ', item.apellidos)";
            } elseif ($moduloSinHistorico === "maquina") {
                $itemSQLName = "CONCAT(item.nombre, ' ', item.serie)";
            }

            // Query for validation status
            $attachCompany = "(SELECT nombre FROM  ". TABLE_EMPRESA ." e WHERE e.uid_empresa = anexo.uid_empresa_anexo)";
            $attachUser    = "(SELECT usuario FROM ". TABLE_USUARIO ." u WHERE u.uid_usuario = anexo.uid_usuario)";
            $clientCompany = "(SELECT nombre FROM  ". TABLE_EMPRESA ." e WHERE e.uid_empresa = vs.uid_empresa_propietaria)";

            $sql = "SELECT
                uid_invoice_item,
                ii.amount                                                                                      AS amount,
                document.nombre                                                                                         AS doc,
                $itemSQLName                                                                                            AS item,
                '$moduloSinHistorico'                                                                                   AS module,
                IF(anexo.estado = 2, 'validado', IF(anexo.estado = 4, 'anulado', IF(anexo.estado = 3, 'caducado', ''))) AS status,
                {$attachCompany}                                                                                        AS company,
                {$attachUser}                                                                                           AS user,
                DATE_FORMAT(ii.date,'%d/%m/%Y')                                                                         AS invoice_date,
                {$clientCompany}                                                                                    AS client
                FROM " .TABLE_INVOICE_ITEM. " ii
            INNER JOIN " .TABLE_INVOICE. " i USING(uid_invoice)
            INNER JOIN " .TABLE_VALIDATION_STATUS. " vs ON uid_reference = uid_validation_status AND vs.uid_modulo = $moduleUID
            INNER JOIN " .TABLE_VALIDATION. " USING(uid_validation)
            INNER JOIN " .PREFIJO_ANEXOS. "$module anexo $onClausure
            INNER JOIN " .TABLE_DOCUMENTO_ATRIBUTO. " ON anexo.uid_documento_atributo = documento_atributo.uid_documento_atributo
            INNER JOIN " .TABLE_TIPODOCUMENTO. " document  USING(uid_documento)
            INNER JOIN " .$tableModule. " item on item.uid_$moduloSinHistorico = anexo.uid_$moduloSinHistorico
            WHERE 1
            AND ii.uid_modulo = {$modulosToGetInfo['validationStatus']}
            " . $where;

            $unionPart[] = $sql;
        }

        $sql = implode(" UNION ", $unionPart);

        $lang = Plantilla::singleton();
        $amount = $lang('precio_total') . ' (euros)';
        $doc = $lang('documento');
        $item = $lang('item');
        $status = $lang('estado');
        $attached = $lang('anexado');
        $date = $lang('fecha');
        $module = $lang('tipo');
        $user = $lang('usuario');
        $client = $lang('cliente');

        $sql = "SELECT
        amount as '{$amount}',
        doc as '{$doc}',
        item as '{$item}',
        module as '{$module}',
        status as '{$status}',
        company as '{$attached}',
        user as '{$user}',
        invoice_date as '{$date}',
        client as '{$client}'
        FROM ($sql) as invoices
        GROUP BY uid_invoice_item ";

        return $sql;
    }


    public function getInvoicedItemsFormatedByDescription($description = null)
    {
        if (!isset($description)) return false;

        $items = array();
        switch ($description) {
            case invoiceItem::DESCRIPTION_LICENSE:
                $licenseItems = $this->getItems(false, $description);

                foreach ($licenseItems as $licenseItem) {
                    $amount         = $licenseItem->getAmount();
                    $paypalLicense  = $licenseItem->getItem();
                    $description    = $paypalLicense->getTypeName();
                    $discount       = $paypalLicense->getDiscountRate();
                    $companyname    = $paypalLicense->getCompany()->getUserVisibleName();
                    $items[] = array(
                                "description"       => $description,
                                "unit_price"        =>  $amount,
                                "discount_table"    => $discount,
                                "discount"          => 0,
                                "quantity"          => 1,
                                "iva"               => true,
                                "subtotal"          => $amount,
                                "staticDescription" => " (". $companyname .")"
                            );
                }
                break;

            case invoiceItem::DESCRIPTION_VALIDATION:
                $sql = "SELECT neto, bruto, uid_empresa_payment, count(*) as num_items, uid_partner, SUM(neto) as totalAmount, is_urgent, uid_empresa_propietaria, language
                        FROM
                        (
                            SELECT uid_empresa_payment, uid_partner, SUM(item.amount) as neto
                                , SUM(status.amount) as bruto, uid_validation, is_urgent, uid_empresa_propietaria, language
                            FROM " .TABLE_INVOICE_ITEM. " item
                            INNER JOIN " .TABLE_VALIDATION_STATUS. " status on uid_reference = uid_validation_status
                            INNER JOIN " .TABLE_VALIDATION. " using(uid_validation)
                            WHERE  uid_invoice = {$this->getUID()}
                            AND status.amount != 0
                            AND uid_empresa_payment = {$this->getCompany()->getUID()}
                            GROUP BY uid_empresa_payment, uid_validation

                        ) invoiceInfo  GROUP BY neto";

                $validationInvoices = db::get($sql, true);

                $total = 0;
                foreach ($validationInvoices as $invoice) {
                    $partner = new empresa($invoice["uid_partner"]);

                    $solicitante = new empresa($invoice['uid_empresa_propietaria']);

                    $filters = array('language' => $invoice['language']);
                    $empresaPartner =  empresaPartner::getEmpresasPartners($solicitante, $partner, $filters, true, true);
                    if (!$empresaPartner) {
                        $empresaPartner =  empresaPartner::getEmpresasPartners($solicitante, $partner, null, true, true);
                    }

                    $discount = ($empresaPartner instanceof empresaPartner && ($variation = $empresaPartner->getVariation()) < 0 && !$invoice['is_urgent']) ? abs($variation) : 0;
                    $unitPrice = $invoice["bruto"];
                    $amount = $invoice["neto"];
                    $subtotal = $invoice["totalAmount"];


                    if ($partner->getValidationPrice(true) == $unitPrice) {
                        $description = "validacion_urgente";
                    } elseif ($partner->getValidationPrice() == $unitPrice) {
                        $description = "validacion_normal";
                    } elseif ($amount > $partner->getValidationPrice(true)) {
                        $description = "validacion_urgente_incremento";
                    } elseif ($amount > $partner->getValidationPrice()) {
                        $description = "validacion_normal_incremento";
                    } else {
                        $description = "validacion_varios_clientes";
                    }

                    $subtotal =  round($subtotal, 2);


                    $items[] = array("description" => $description, "unit_price" =>  $amount, "discount_table" => $discount, "discount" =>0, "quantity"=> $invoice["num_items"], "iva" => true, "subtotal" =>  $subtotal);
                    $total += $subtotal;
                }
                break;

            default:
                return false;
        }

        return $items;
    }


    public function getInvoicedItemsFormated()
    {
        $types = invoiceItem::getAllTypes();

        $items = array();
        foreach ($types as $type) {
            $items = array_merge($items, $this->getInvoicedItemsFormatedByDescription($type));
        }

        return $items;

    }

    public static function getTotalAmountInvoiced(empresa $company = null, $payed = false)
    {
        return true;
    } /*pending confirm*/

    public static function getDefaulterCompanies()
    {
        return true;
    }  /*pending confirm*/

    /**
     * @param empresa|null $company
     * @return ArrayObjectList
     */
    public static function getPending(empresa $company = null)
    {
        $sql = "SELECT uid_invoice FROM " . TABLE_INVOICE . "  LEFT OUTER JOIN " . TABLE_TRANSACTION . " paypal using(custom)
        WHERE paypal.uid_paypal IS null
        AND sent_date IS NOT NULL
        AND sent_date > '" . self::DATE_CREDIT_MEMO . "'";

        if ($company) {
            $sql .= " AND uid_empresa = {$company->getUID()} ";
        }
        $sql .= " ORDER BY sent_date ASC ";

        $invoices = db::get($sql, "*", 0, "invoice");

        return new ArrayObjectList($invoices);
    }

    public function regenerateCustom ($short = false) {
        $custom = self::createCustomKey($short);

        $SQL = "UPDATE {$this->tabla}
        SET custom = '{$custom}'
        WHERE uid_{$this->tipo} = {$this->getUID()}";

        if (db::get($SQL)) {
            return $custom;
        }

        return false;
    }

    public static function cronCall($time, $force = false, $tipo = null)
    {
        $isTime = (date("H:i", $time) == "06:00");
        if (!$isTime && !$force) return true;

        $invoices = invoice::getPending();
        $numberInvoices = count($invoices);
        echo "Tenemos {$numberInvoices} empresas pendientes de crear invoices.\n\n";
        foreach ($invoices as $key => $invoice) {
            $company = $invoice->getCompany();
            echo $key+1 ." de {$numberInvoices} Recordatorio Invoice: id:{$invoice->getUID()} company:{$company->getUserVisibleName()}\n";
            if ($company->isEnterprise()) {
                echo " Es una empresa enterprise, no enviamos recordatorios.\n";
                continue;
            }

            $now = new DateTime();
            $invoiceDate = new DateTime($invoice->getSentDate());
            $datediff = date_diff($invoiceDate, $now);
            $items = $invoice->getInvoicedItemsFormated();
            echo "Han pasado {$datediff->days} ";
            switch ($datediff->days) {
                case self::TIME_FRAME_REMINDER_PAYMENT:
                    echo "Enviamos email recordatorio\n\n";
                    $invoice->sendEmailNotification(self::REMINDER_PAYMENT, $company, $items, $force);
                    break;
                case self::DAYS_APP_CLOSED:
                    $restrictTime = new DateTime(self::DATE_RESTRICT_TIMEFRAME);
                    if ($restrictTime < $invoiceDate) {
                        //we not send
                        echo "No enviamos email por fecha invoice superior a 2013-10-08\n\n";
                        continue;
                    }
                    echo "Enviamos email cierre de aplicacion\n\n";
                    $invoice->sendEmailNotification(self::CLOSE_NOTIFICATION, $company, $items, $force);
                    break;
                case self::RESTRICTIVE_DAYS_APP_CLOSED:
                    $restrictTime = new DateTime(self::DATE_RESTRICT_TIMEFRAME);
                    if ($restrictTime > $invoiceDate) {
                        //we not send
                        echo "No enviamos email por fecha invoice inferior a 2013-10-08\n\n";
                        continue;
                    }
                    echo "Enviamos email cierre de aplicacion\n\n";
                    $invoice->sendEmailNotification(self::CLOSE_NOTIFICATION, $company, $items, $force);
                    break;
                default:
                    // Send remainder email three times during a year.
                    if (true === in_array($datediff->days, [180, 270, 360])) {
                        echo "Enviamos aviso de pago pendiente.\n";
                        $invoice->sendEmailNotification(self::REMINDER_PAYMENT, $company, $items, $force);
                    } else {
                        echo "No enviamos recordatorio.\n";
                    }
                    break;
            }
        }

        return true;

    }

    public function getDaysToClose()
    {
            $dateInvoice = new DateTime($this->getSentDate());
            $now = new DateTime();
            $datediff = date_diff($now, $dateInvoice);
            $restrictTime = new DateTime(self::DATE_RESTRICT_TIMEFRAME);

            if ($restrictTime < $dateInvoice) {
                return self::RESTRICTIVE_TIME_FRAME_CLOSE_NOTIFICATION - $datediff->days ;
            }

            return self::TIME_FRAME_CLOSE_NOTIFICATION - $datediff->days ;


    }

    public function getActionPendingInvoice()
    {
            $dateInvoice = new DateTime($this->getSentDate());
            $now = new DateTime();
            $datediff = date_diff($now, $dateInvoice);
            $restrictTime = new DateTime(self::DATE_RESTRICT_TIMEFRAME); /*HACK to let invoices created before this date until 90 days to be paid*/

            $timeFramebeforeRestrict = $dateInvoice < $restrictTime && $datediff->days >= self::TIME_FRAME_CLOSE_NOTIFICATION;
            $timeFrameAfterRestrict = $dateInvoice > $restrictTime && $datediff->days >= self::RESTRICTIVE_TIME_FRAME_CLOSE_NOTIFICATION;

            if ($timeFrameAfterRestrict || $timeFramebeforeRestrict) {
                return self::CLOSE_NOTIFICATION;
            } elseif ($datediff->days > self::TIME_FRAME_REMINDER_PAYMENT) {
                return self::REMINDER_PAYMENT;
            } else {
                return self::PAYMENT_INFO;
            }
            return false;
    }

    public function saveTransaction($mode="ipn", $post = array())
    {
        $app = \Dokify\Application::getInstance();

        if (isset($post["mc_gross_1"])) {
            $amount = $post["mc_gross_1"];
        } else {
            $amount = $post["mc_gross"] - @$post["handling_amount"] - @$post["tax"];
        }

        $total = $post["mc_gross"];
        $invoiceAmount = $this->getPrice();

        if (number_format($amount,2, '.', '') != $invoiceAmount) {
            $this->notifyMismatchPayment($invoiceAmount, $amount, $this->getCompany());
        }

        $transactionID = parent::saveTransaction($mode, $post);
        $data = self::getTransactionData($transactionID);

        if ($data->sale_id == 0) {
            self::updateTransaction($transactionID, array("sale_id" => -1)); //Estamos procesando el pago
        }

        if (!$data) throw new Exception("No se puede obtener la información de la transacción");

        $isTest = isset($post["test_ipn"]) && $post["test_ipn"] && CURRENT_ENV !== 'dev';
        $isComplete = isset($post['payment_status']) && $post['payment_status'] === 'Completed';
        $processPayment = isset($data) && isset($data->sale_id) && is_numeric($data->sale_id) && $data->sale_id == 0 && !$isTest;

        if ($processPayment) {
            if ($isComplete) {
                $data = self::getTransactionData($transactionID);
                $empresa = new empresa($data->uid_empresa);
                if ($empresa->exists()) {
                    $saleId = $this->getSaleId();
                    if (is_numeric($saleId)) {
                        $method = ($post['txn_type'] == endeve::PAYMENT_METHOD_TRANSFER) ? endeve::PAYMENT_METHOD_TRANSFER : endeve::PAYMENT_METHOD_PAYPAL;
                        $payed = endeve::payItem($empresa, $saleId, array(array("amount"=>$total)), true, $method);
                        if (!$payed) {
                            //Reiniciamos el saleId para que vuelva a entrar la siguiente petción.
                            self::updateTransaction($transactionID, array("sale_id" => 0));
                            throw new \Dokify\Exception\TransactionException("Invoice Error al realizar el pago para el saleId [$saleId]");
                        } else {
                            self::updateTransaction($transactionID, array("sale_id" => $saleId));
                            $log = log::singleton();
                            $log->info("invoice", "El pago para la transaccion $transactionID se ha registrado correctamente en quaderno con saleId $saleId", "custom: ".$post["custom"], "Ok", true);
                        }
                    } else {
                        //Reiniciamos el saleId para que vuelva a entrar la siguiente petción.
                        self::updateTransaction($transactionID, array("sale_id" => 0));
                        throw new \Dokify\Exception\TransactionException("Invoice Error no sale_id para la transaccion $transactionID");
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
            $app['log']->addWarning("invoice payment process already in progress", ["txn" => $transactionID, "mode" => $mode]);

            return $transactionID;
            // throw new \Dokify\Exception\TransactionException("Ya se estaba procesando un pago, no procesamos pago que entra con modo: {$mode}");
        } elseif (is_numeric($data->sale_id) && $data->sale_id != -1 && $data->sale_id != 0){
            //Ya estaba registrado el pago, no hacemos nada
            return $transactionID;
        } else {
            if ($isTest) {
                error_log("Test IPN del que no hacemos factura: $transactionID [{$data->item_name1}, {$data->amount}]");
            } else {
                error_log("Ya se ha generado la factura para la transaccion $transactionID [". @$post['payment_status'] ."]");
            }

        }

        return $transactionID;
    }


    public function getTableInfo(Iusuario $usuario = null, Ielemento $parent = null, $data = array())
    {
        $tpl = Plantilla::singleton();
        $linedata = array();
        $typesInvoiced = $this->getTypesInvoiced();

        $typesNames = array();
        foreach ($typesInvoiced as $type) {
            $typesNames[] = $tpl->getString("invoice_".$type);
        }

        $stringTypes = implode(" - ", $typesNames);

        $linedata["tipo"] = $stringTypes;
        $linedata["fecha"] = date("d-m-Y", $this->getSentTimestamp());
        $linedata["amount"] = $this->getPrice() . " €";
        $linedata["totalItems"] = $this->getNumItems() . " " . $tpl->getString("elementos");

        $tableInfo = array($this->getUID() => $linedata);

        return $tableInfo;
    }

    public function getTreeData(Iusuario $usuario, $extraData = array())
    {
        return array(
            "checkbox" => true,
            "img" => array(
                "normal" => RESOURCES_DOMAIN ."/img/famfam/page_white_text.png"
                ),
            "url" => "../agd/list.php?m=invoice&action=Items&poid={$this->getUID()}&comefrom=invoice&data[parent]={$this->getCompany()}"
        );
    }

    public function urlToPaypal(usuario $usuario = null, empresa $company = null,  $dev = false, $formItems = null, $temporaryAccess = false)
    {
        $contact = $company->obtenerContactoPrincipal();
        if (!$contact) return false;
        $amount = $this->getPrice();
        //paypal needs two decimals
        $amount = number_format($amount,2, '.', '');
        $template = new Plantilla();

        $ipn = CURRENT_DOMAIN . "/paypal";
        $dominio = CURRENT_DOMAIN;
        $lang = $company->getCountry()->getLanguage();

        $formItems = [
            "item_name_1" => $template->getString("validacion", $lang),
            "amount_1" => $amount,
            "custom" => $this->getCustom(),
            "first_name" => $contact->getContactName(),
            "last_name" => $contact->getContactSurname(),
            "email" => $contact->obtenerEmail(),
        ];

        $invoiceSentDate = new DateTimeImmutable($this->getSentDate());
        $fees = $company->getFeeAmount($amount, $invoiceSentDate);
        if (0 !== $fees) {
            $formItems["item_name_2"] = $template->getString("gastos_gestion");
            $formItems["amount_2"] = $fees;
        }

        if ($company->mustPayTaxes()) {
            $totalTax = $formItems["amount_1"];
            $paymentInfo = $this->paymentInfo();

            if (true === isset($formItems["amount_2"]) && true === isset($paymentInfo["applyToFee"]) && true === $paymentInfo["applyToFee"]) {
                $totalTax += $formItems["amount_2"];
            }

            $tax = $company->getTax()/100;
            $formItems["tax_cart"] = round($totalTax*$tax, 2);
        }

        return parent::urlToPaypal($usuario, $company, $dev, $formItems);
    }

    public static function getFieldTable()
    {
        return array("uid_empresa", "date", "amount");
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $arrayCampos = new FieldList();

        $arrayCampos["uid_empresa"] = new FormField();
        $arrayCampos["date"] = new FormField();
        $arrayCampos["sent_date"] = new FormField();
        $arrayCampos["amount"] = new FormField();
        $arrayCampos["custom"] = new FormField();
        $arrayCampos["sale_id"] = new FormField();
        $arrayCampos["download_url"] = new FormField();

        return $arrayCampos;
    }

    public function sendTransferDoneEmail()
    {
        // leter it will be re-translated, but we need here the _() for the parser
        $subject = "We have received the payment for your invoice";
        $view = 'transfer/received.html';

        $this->sendEmail($subject, $view);
    }

    public function getTableFields()
    {
        return array(
            array("Field" => "uid_invoice",     "Type" => "int(10)",        "Null" => "NO",     "Key" => "PRI", "Default" => "",        "Extra" => "auto_increment"),
            array("Field" => "uid_empresa",     "Type" => "int(10)",        "Null" => "NO",     "Key" => "MUL", "Default" => "",        "Extra" => ""),
            array("Field" => "date",            "Type" => "timestamp",      "Null" => "YES",    "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "sent_date",       "Type" => "timestamp",      "Null" => "YES",    "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "sale_id",         "Type" => "int(10)",        "Null" => "YES",    "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "custom",          "Type" => "varchar(500)",   "Null" => "YES",    "Key" => "",    "Default" => "",        "Extra" => ""),
            array("Field" => "amount",          "Type" => "decimal(10,3)",  "Null" => "NO",     "Key" => "",    "Default" => "0.000",   "Extra" => ""),
            array("Field" => "download_url",    "Type" => "text",           "Null" => "NO",     "Key" => "",    "Default" => "",        "Extra" => "")
        );
    }
}
