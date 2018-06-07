<?php

class ArrayValidationStats extends ArrayObjectList
{
    protected $dataSet;
    protected $ref;

    public function __construct($dataSet, $ref)
    {
        $this->dataSet = $dataSet;
        $this->ref = $ref;
    }

    public function getResume()
    {
        $count = $amount = 0;
        foreach ($this->dataSet as $key => $data) {
            if (!count($data)) {
                continue;
            }

            $company = new empresa($data["uid_empresa_propietaria"]);
            $partner = new empresa($data["uid_partner"]);

            $amountPerValidation = $partner->getCost();
            $filters = array('language' => $data["language"]);
            $empresaPartner = empresaPartner::getEmpresasPartners($company, $partner, $filters, true);

            if ($empresaPartner instanceof empresaPartner) {
                $variation = $empresaPartner->getCostVariation();

                if ($variation) {
                    $amountPerValidation = abs($amountPerValidation * $variation/100);
                }

                $count += $data["count"];
                $amount += round($amountPerValidation * round($data["count"], 6), 6);
            } else {
                // Apply the aprtner validation for the language supported. We apply the same amount for the partner bill
                $empresaPartner = empresaPartner::getEmpresasPartners($company, $partner, null, true);
                $count += $data["count"];

                if (false === $empresaPartner instanceof empresaPartner) {
                    $variation = empresaPartner::getDefaultCostVariation($company);
                } else {
                    $variation = $empresaPartner->getCostVariation();
                }

                if ($variation) {
                    $amountPerValidation = abs($amountPerValidation * $variation/100);
                }

                $amount += round($amountPerValidation * round($data["count"], 6), 6);
            }
        }

        return array("item" => $this->ref, "count" => $count, "amount"=> $amount);
    }
}
