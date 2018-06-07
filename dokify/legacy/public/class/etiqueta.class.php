<?php

class etiqueta extends elemento implements Ielemento
{
    public function __construct( $param , $extra = false ){
        $this->tipo = "etiqueta";
        $this->tabla = TABLE_ETIQUETA;

        $this->instance( $param, $extra );
    }

    public static function getRouteName () {
        return 'label';
    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Label\Label
     */
    public function asDomainEntity()
    {
        return $this->app['label.repository']->factory($this->getInfo());
    }

    /***
       * return ArrayObjectList of labels of the requested documents
       *
       *
       *
       */
    public function countRequests (solicitable $element, Iusuario $user = null)
    {
        $reqTypes = $element->getReqTypes(['viewer' => $user]);

        // merge all requests
        $requests = new ArrayRequestList;
        foreach ($reqTypes as $reqType) {
            $requests = $requests->merge($reqType->requests);
        }

        if (count($requests) === 0) {
            return new ArrayObjectList;
        }

        // define and set base filters
        $filters    = [];
        $filters[]  = "uid_etiqueta = {$this->getUID()}";
        $filters[]  = "uid_solicituddocumento IN ({$requests->toComaList()})";

        $where      = implode(" AND ", $filters);
        $class      = get_class($element);
        $view       = TABLE_DOCUMENTO . "_{$class}_estado";
        $labels     = TABLE_DOCUMENTO_ATRIBUTO . "_etiqueta";

        $SQL = "SELECT count(uid_solicituddocumento) as count FROM {$view}
        INNER JOIN {$labels} USING (uid_documento_atributo)
        WHERE 1
        AND {$where}
        ";

        $num = (int) $this->db->query($SQL, 0, 0);

        return $num;
    }


    public static function getSearchData(Iusuario $usuario, $papelera = false){
        if (!$usuario->accesoModulo(__CLASS__)) return false;

        $limit = "uid_etiqueta IN ( SELECT uid_etiqueta FROM ". TABLE_EMPRESA ."_etiqueta WHERE uid_empresa = {$usuario->getCompany()->getUID()} )";


        $searchData[ TABLE_ETIQUETA ] = array(
            "type" => "etiqueta",
            "fields" => array("nombre"),
            "limit" => $limit,
            "accept" => array(
                "tipo" => "etiqueta"
            )
        );

        return $searchData;
    }

    public function getUserVisibleName(){
        $datos = $this->getInfo();
        return $datos["nombre"];
    }

    public function triggerAfterCreate(Iusuario $usuario = NULL, Ielemento $elemento = NULL){
        if( $elemento instanceof etiqueta && $usuario instanceof usuario ){
            $sql = "INSERT INTO ". TABLE_EMPRESA ."_etiqueta (uid_empresa, uid_etiqueta)
            VALUES ({$usuario->getCompany()->getUID()}, {$elemento->getUID()})";

            if( !$this->db->query( $sql ) ){
                return $database->lastErrorString();
            }
        }
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        $fields = new FieldList;
        $fields["nombre"] = new FormField( array("tag" => "input",  "type" => "text", "blank" => false));
        return $fields;
    }
}
