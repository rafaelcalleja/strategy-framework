<?php
/**
 * This class has an excesive coupling between other objects,
 * if you know how, please, try to reduce it
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * This class is bad named. Change it can have an unknown impact in the application,
 * if you are sure, please change it and refactorize the application
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class empresaPartner extends solicitable implements IempresaPartner, Ielemento
{
    const TYPE_VALIDATOR                = "1"; // Validador
    const PAYMENT_SELF                  = "self";
    const PAYMENT_ALL                   = "all";
    const VALIDATION_TARGET_SELF        = 'self';
    const VALIDATION_TARGET_CONTRACTS   = 'contracts';
    const VALIDATION_TARGET_ALL         = 'all';

    public function __construct($param, $extra = false)
    {
        $this->tipo = "empresaPartner";
        $this->tabla = TABLE_EMPRESA_PARTNER;
        $this->instance($param, $extra);

    }

    public static function defaultData($data, Iusuario $usuario = null)
    {
        if ($usuario instanceof usuario) {
            $data["uid_empresa"] = $usuario->getCompany()->getUID();
            $data["type"] = empresaPartner::TYPE_VALIDATOR;
        }

        return $data;
    }

    public function getUserVisibleName()
    {
        return $this->getCompany() ."-". $this->getPartner();
    }


    public function getCompany()
    {
        $info = $this->getInfo();
        return new empresa($info["uid_empresa"]);
    }

    public function getPartner()
    {
        $info = $this->getInfo();
        return new empresa($info["uid_partner"]);
    }

    public function getType()
    {
        $info = $this->getInfo();
        return $info["type"];
    }

    public function getValidationTarget()
    {
        $info = $this->getInfo();
        return $info["validation_target_docs"];
    }

    public function getLanguage()
    {
        $info = $this->getInfo();
        return $info["language"];
    }

    public function getValidationDocs()
    {
        $info = $this->getInfo();
        return $info["validation_docs"];
    }

    public function getVariation()
    {
        $cacheString = "getVariation-empresaPartner-{$this}";
        if (($variation = $this->cache->getData($cacheString)) !== null) {
            return $variation;
        }

        $info       = $this->getInfo();
        $variation  = $info["variation"];

        $this->cache->addData($cacheString, $variation);


        return $variation;

    }

    public function getPaymentMethod()
    {
        $info = $this->getInfo();
        return $info["validation_payment_method"];
    }

    public static function getDefaultCostVariation($company)
    {
        $dataBase = db::singleton();

        $validationConfigHistoricTable = TABLE_EMPRESA_PARTNER . "_historic";
        $sql = "SELECT cost_variation
        FROM {$validationConfigHistoricTable}
        WHERE uid_empresa = {$company->getUID()}
        LIMIT 1";

        $costVariation = $dataBase->query($sql, 0, 0);

        if (null !== $costVariation) {
            return (int) $costVariation;
        }

        return -60;
    }

    public function getCostVariation()
    {
        $cacheString = "getCostVariation-empresaPartner-{$this}";

        if (($costVariation = $this->cache->getData($cacheString)) !== null) {
            return $costVariation;
        }

        $info           = $this->getInfo();
        $costVariation  = $info["cost_variation"];

        $this->cache->addData($cacheString, $costVariation);

        return $costVariation;
    }

    public function isCustom()
    {
        $validationDocs = $this->getValidationDocs();

        if ($validationDocs == 'custom') {
            return 1;
        } else if ($validationDocs == 'general') {
            return  0;
        } else {
            return null;
        }
    }


    /**
     * Calculate the validation price
     * @param  number $partnerValidationPrice The partner validation price
     * @param  number $variation              The variation with respect to the partner validation price
     * @return number                         The result of the variation applied to the partner validation price
     *
     * For me, the variable $partnerValidationPrice has a properly long
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    private function calculateValidationPrice($partnerValidationPrice, $variation)
    {
        $percentage = $variation / 100;
        $variation  = $partnerValidationPrice * $percentage;

        return round($partnerValidationPrice + $variation, 2);
    }

    /**
     * Get the validation price
     * @return number The result of the variation applied to the partner validation price
     *
     * For me, the variable $partnerValidationPrice has a properly long
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function getValidationPrice()
    {
        $partner                = $this->getPartner();
        $partnerValidationPrice = $partner->getValidationPrice();
        $variation              = $this->getVariation();

        return $this->calculateValidationPrice($partnerValidationPrice, $variation);
    }

    /**
     * The $usuario and $parent variables are necessary
     * because getTableInfo is a common function used in many objects
     * and these parameters are required
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTableInfo(Iusuario $usuario = null, Ielemento $parent = null, $data = array())
    {
        $tpl = Plantilla::singleton();

        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;
        $linedata = array();

        switch($context){
            case Ilistable::DATA_CONTEXT_LIST_PRICES:
                $linedata["empresa"]       = $tpl->getString('solicitante').": ".$this->getCompany()->getUserVisibleName();
                $linedata["uid-empresa"]   = $this->getCompany()->getUID();
                $linedata["language"]      = $tpl->getString('idioma').": ".$tpl->getString(system::getLanguages($this->getLanguage()));
                $linedata["price"]         = $tpl->getString('validation_prices').": ".$this->getValidationPrice(). " €";
                break;

            default:
                $linedata["empresa"]    = $tpl->getString('empresa').": ".$this->getCompany()->getUserVisibleName();
                $linedata["partner"]    = $tpl->getString('partner').": ".$this->getPartner()->getUserVisibleName();
                $linedata["language"]   = $tpl->getString('opt_idiomas').": ".$tpl->getString(system::getLanguages($this->getLanguage()));
                $variation = $this->getVariation();

                if ($variation != 0) {
                    if ($variation < 0) {
                        $linedata["variation"] = $tpl->getString('descuento')." ".abs($this->getVariation())."%";
                    } else {
                        $linedata["variation"] = $tpl->getString('incremento')." ".abs($this->getVariation())."%";
                    }
                } else {
                    $linedata["variation"] = $tpl->getString('no_discount');
                }

                $linedata["target"] = $tpl->getString('docs_to_validate').": ".$tpl->getString($this->getValidationTarget());
                $partner    = $this->getPartner();
                $timeNormal = $partner->getAVGTimeValidate();
                $timeUrgent = $partner->getAVGTimeValidate(true);
                $linedata["avg_time_validation"] = "";

                if ($timeNormal) {
                    $linedata["avg_time_validation"] .= sprintf($tpl->getString('time_to_validate_normal'), util::secsToHuman($timeNormal));
                }

                if ($timeUrgent) {
                    if (trim($linedata["avg_time_validation"])) {
                        $linedata["avg_time_validation"] .= " - ";
                    }

                    $linedata["avg_time_validation"] .= sprintf($tpl->getString('time_to_validate_urgent'), util::secsToHuman($timeUrgent));
                }

                break;
        }

        return array($linedata);

    }


    public static function getEmpresasPartners(empresa $company = null, empresa $partner = null, $filters = null, $limit = false, $byExpensive = false)
    {
        $sql = "SELECT uid_empresa_partner FROM ". TABLE_EMPRESA_PARTNER ." ptr ";

        if ($byExpensive) {
            $sql .= " INNER JOIN ". TABLE_EMPRESA ." emp on emp.uid_empresa = ptr.uid_partner ";
        }

        $sql .= "  WHERE 1 ";

        if ($partner instanceof empresa) {
            $sql .= " AND uid_partner = " . $partner->getUID();
        }

        if ($company instanceof empresa) {
            $sql .= " AND ptr.uid_empresa = " . $company->getUID();
        }

        if (isset($filters)) {
            foreach ($filters as $key => $filter) {
                switch($key){
                    case "validation_payment_method": case "language":
                        $sql .= " AND " .$key." = '".$filter."'";
                        break;
                    default:
                        $sql .= " AND " .$key." = ".$filter;
                        break;
                }
            }
        }

        if ($byExpensive) {
            $sql .= " ORDER BY partner_validation_price DESC, variation DESC ";
        }
        if ($limit) {
            $result = db::get($sql, 0, 0);
            if (is_numeric($result)) {
                return new empresaPartner($result);
            }
            return false;
        }

        $result = db::get($sql, "*", 0, "empresaPartner");

        if ($result) {
            return new ArrayObjectList($result);
        } else {
            return new ArrayObjectList;
        }

    }

    /**
     * Convert instance of this object to Array
     * @param  Dokify\Application $app The application context
     * @return Array      The most relevance values of the intance of this object in Array format
     *
     * The $app variable is necessary in order to mantain the function signature (this function is used in many objects)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toArray($app = null)
    {
        $localemap = getLocaleMap();

        $validationConfig = [
            'language'  => $localemap[system::getLanguages($this->getLanguage())],
            'price'     => $this->getValidationPrice()
        ];

        return $validationConfig;
    }

    /**
     * The $tab variable is necessary in order to mantain the function signature (this function is used in many objects)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $arrayCampos = new FieldList();

        $arrayCampos['uid_partner']     = new FormField(
            array(
                "tag" => "select",
                'default' => 'Seleccionar',
                "data" => empresa::getAllPartners(),
                "objeto" => "empresa",
                "blank" => false
            )
        );

        $arrayCampos['validation_docs'] = new FormField(
            array(
                'tag' => 'select',
                'default'=>'Seleccionar...',
                'data'=> array(
                    'custom' => "especificos",
                    'general' => "generales",
                    'both' => "ambos"
                ),
                "blank" => false
            )
        );

        if ($objeto instanceof empresaPartner) {
            $companyName = $objeto->getCompany()->getUserVisibleName();
        } else if ($usuario instanceof usuario) {
            $companyName = $usuario->getCompany()->getUserVisibleName();
        } else {
            $companyName = "cliente";
        }

        $arrayCampos['language'] = new FormField(
            array(
                'tag' => 'select',
                'default'=>'Seleccionar...',
                'data'=> system::getLanguages(),
                "blank" => false
            )
        );

        $arrayCampos['validation_payment_method'] = new FormField(
            array(
                'tag' => 'select',
                'default'=>'Seleccionar...',
                'data'=> array(
                    self::PAYMENT_SELF => $companyName,
                    self::PAYMENT_ALL => "contratas"
                ),
                "blank" => false
            )
        );

        $arrayCampos['validation_target_docs'] = new FormField(
            array(
                'tag' => 'select',
                'default'=>'Seleccionar...',
                'data'=> array(
                    self::VALIDATION_TARGET_ALL => "all",
                    self::VALIDATION_TARGET_SELF => "propios",
                    self::VALIDATION_TARGET_CONTRACTS => "contracts"
                ),
                "blank" => false
            )
        );

        $arrayCampos['variation'] = new FormField(
            array(
                "tag" => "input",
                "type" => "text",
                "value" => 0
            )
        );

        $arrayCampos['cost_variation']  = new FormField(
            array(
                "tag" => "input",
                "type" => "text"
            )
        );

        if ($modo == elemento::PUBLIFIELDS_MODE_NEW) {
            $arrayCampos['uid_empresa'] = new FormField(array());
            $arrayCampos['type']    = new FormField(array());
        }

        return $arrayCampos;
    }
}
