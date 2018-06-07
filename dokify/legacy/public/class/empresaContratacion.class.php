<?php

class empresaContratacion extends ArrayObjectList
{
    const MAX_COMPANIES = 4;

    protected $uid;


    public function __construct ($param)
    {
        $this->tipo = "empresaContratacion";
        $this->tabla = TABLE_EMPRESA."_contratacion" ;

        if (is_array($param) || $param instanceof ArrayObjectList) {
            return parent::__construct($param);
        }

        if (is_numeric($param)) {
            $table  = TABLE_EMPRESA ."_contratacion";
            $sql    = "SELECT n1, n2, n3, n4 FROM {$table} WHERE uid_empresa_contratacion = {$param}";

            $data = db::get($sql, true);
            if ($data && count($data) == 1) {
                foreach ($data as $line) {
                    if ($line["n1"] && is_numeric($line["n1"])) {
                        $this[] = new empresa($line["n1"]);
                    }

                    if ($line["n2"] && is_numeric($line["n2"])) {
                        $this[] = new empresa($line["n2"]);
                    }

                    if ($line["n3"] && is_numeric($line["n3"])) {
                        $this[] = new empresa($line["n3"]);
                    }

                    if ($line["n4"] && is_numeric($line["n4"])) {
                        $this[] = new empresa($line["n4"]);
                    }
                }

                $this->uid = $param;
            } else {
                throw new Exception("Imposible instanciar o crear el objeto {get_class($this)}");
            }
        }

        if ($param instanceof empresa) {
            return parent::__construct([$param]);
        }
    }

    public static function getRouteName () {
        return 'contractChain';
    }

    public function getUID(){
        if ( isset($this->uid) ) return $this->uid;
        else {
            $db = db::singleton();
            // DEFINIR SQL QUE OBTENGA uid
            $SQL = "SELECT uid_empresa_contratacion FROM ". TABLE_EMPRESA ."_contratacion
                    WHERE
            ";
            $cadena = array();
            $indice = 1;
            for ($i = 0; $i < count($this); $i++) {
                $cadena[] = " n".$indice." = ".$this->getCompanyWithIndex($i)->getUID()." ";
                $indice++;
            }
            $cadenaSQL = implode(" AND ",$cadena);
            $SQL = $SQL.$cadenaSQL;
            $uid = $db->query($SQL,0,0);
            $this->uid = $uid;
            return  $uid;
        }
    }

    public function delete(){
        $SQL = "DELETE FROM ". TABLE_EMPRESA ."_contratacion
                WHERE uid_empresa_contratacion = {$this->getUID()}
        ";
        return  db::get($SQL);
    }

    public function getIntermediateCompanies(){
        $lastIndex = count($this) - 1;
        $intermediateCompanies = array();
        foreach ($this as $key => $value) {
            if ( ($key != 0) && ($key != $lastIndex) ) {
                $intermediateCompanies[] = $value;
            }
        }
        return $intermediateCompanies;
    }

    public function filterCompanies(ArrayObjectList $empresas = NULL){
        if ($empresas instanceof ArrayObjectList) {
            return $this->discriminar($empresas);
        }
        return false;
    }

    public function getCompanyTail(){
        $lastIndex = count($this)-1;
        return $this->getCompanyWithIndex($lastIndex);
    }

    public function getCompanyHead(){
        return $this->getCompanyWithIndex(0);
    }

    public function getCompanyWithIndex($i){
        return $this[$i];
    }

    public function getResidualChains(){
        $db = db::singleton();
        if ( count($this) == 3 ) {
            $SQL = "SELECT uid_empresa_contratacion FROM ". TABLE_EMPRESA ."_contratacion WHERE ";
            $i = 2;
            foreach ($this as $empresa) {
                $condicionSqlEmpresaInferiores[] = "n" .$i. " = {$empresa->getUID()} ";
                $i++;
            }
            $SQL .= " ( ". implode(' AND ',$condicionSqlEmpresaInferiores) . " AND n1 != 0 ) ";
            $SQL .= "UNION
                    SELECT uid_empresa_contratacion FROM ". TABLE_EMPRESA ."_contratacion WHERE
                    ";
            $i = 1;
            foreach ($this as $empresa) {
                $condicionSqlEmpresaSuperiores[] = "n" .$i. " = {$empresa->getUID()} ";
                $i++;
            }
            $SQL .= " ( ". implode(' AND ',$condicionSqlEmpresaSuperiores) . " AND n4 != 0) ";
            $collection = $db->query($SQL, '*', 0, 'empresaContratacion');
            if ( count($collection) ) return new ArrayObjectList($collection);
        }
        return false;
    }

    // In order to know if the object is a residual chain we need to know the companies Head and Tail to compare with 
    public function isResidualChain(empresa $companyHead, empresa $companyTail){
        if ($companyHead->esCorporacion()) {
            $childCompanies = $companyHead->obtenerEmpresasInferiores();
            foreach ($childCompanies as $parentItem) {
                if ( $this->getCompanyHead()->compareTo($parentItem) ) {
                    $companyHead = $parentItem;
                    break;
                }
            }
        }
        if ( !$this->getCompanyHead()->compareTo( $companyHead ) ||  !$this->getCompanyTail()->compareTo( $companyTail ) ) return true;
        return false;
    }

    public function getArrayStatusChain() {
        $return = array();
        $return['globalStatus'] = true;
        $return['notRequested']['invalid'] = new ArrayObjectList;
        $return['requested']['valid'] = new ArrayObjectList;
        $return['requested']['invalid'] = new ArrayObjectList;
        foreach ($this as $key => $company) {
            if (isset($this[$key+1]) && $nextCompany = $this[$key+1]) {
                $statusClient = $nextCompany->getStatusWithCompany(null, 0, 1, $this);
                if ($statusClient == solicitable::STATUS_NO_REQUEST) {
                    $return['notRequested']['invalid'][] = $nextCompany;
                } else {
                    if ($statusClient == solicitable::STATUS_VALID_DOCUMENT) {
                        $return['requested']['valid'][] = $nextCompany;
                    } else {
                        $return['requested']['invalid'][] = $nextCompany;
                    }
                }
                if ($statusClient == solicitable::STATUS_INVALID_DOCUMENT || $statusClient == solicitable::STATUS_NO_REQUEST) $return['globalStatus'] = false;
            }
        }
        return $return;
    }

    public function getGlobalStatusChain() {
        $status = $this->getArrayStatusChain();
        return $status['globalStatus'];
    }

    public function getMessageStatusChain() {
        $tpl = Plantilla::singleton();
        $status = $this->getArrayStatusChain();
        if (count($status['notRequested']['invalid'])) {
            $companyNames = $status['notRequested']['invalid']->getUserVisibleName();
            $message = sprintf($tpl->getString('cadena_contra_no_valida_not_request'),$this->getCompanyHead()->getUserVisibleName(),$companyNames);
        } elseif (count($status['requested']['invalid'])) {
            $companyNames = $status['requested']['invalid']->getUserVisibleName();
            $message = sprintf($tpl->getString('cadena_no_valida'),$this->getCompanyHead()->getUserVisibleName(),$companyNames);
        } else {
            $message = $tpl->getString('cadena_valida');
        }
        return $message;
    }

    public function getUserVisibleName() {
        $names = array();
        foreach ($this as $key => $company) {
            $names[] = $company->getUserVisibleName();
        }
        return implode(" -> ",$names);
    }

    public function getHtmlName() {
        $names = array();
        $status = $this->getArrayStatusChain();
        $return = '<span><div style=\'white-space:nowrap; float:left\'> ';
        $nowrap = '<div style=\'white-space:nowrap; float:left\' >';
        foreach ($this as $key => $company) {
            $invalid = ($status['notRequested']['invalid']->contains($company) || $status['requested']['invalid']->contains($company));
            $names[] = $invalid ? '<font color=red>'.$company->getShortName().'</font></div>' : $company->getShortName().'</div>';
        }
        $img = $nowrap."<img src=\"".RESOURCES_DOMAIN."/img/famfam/bullet_go.png\" style=\"vertical-align: center; margin: 5px; margin-top: 0px;\" />";

        $return .= implode($img, $names);
        $return .= '</span> ';
        return $return;
    }

    public function isConsistent () {

        foreach ($this as $key => $company) {
            if (isset($this[$key+1]) && $nextCompany = $this[$key+1]) {
                if (!$company->esSuperiorDe($nextCompany)) return false;
            }
        }

        return true;
    }
}
