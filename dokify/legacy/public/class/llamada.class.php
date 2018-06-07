<?php

class llamada extends elemento implements Ielemento
{

    public function __construct( $param, $extra = false ){
        $this->tipo = "llamada";
        $this->tabla = TABLE_LLAMADA;

        $this->instance( $param, $extra );
    }

    public static function defaultData($data, Iusuario $usuario = null)
    {
        if (empty($data['uid_empresa'])) {
            $data['uid_empresa'] = '0';
        }

        if (empty($data['uid_usuario_atendido'])) {
            $data['uid_usuario_atendido'] = '0';
        }

        if (empty($data['uid_hilo'])) {
            $data['uid_hilo'] = '0';
        }

        if (false === llamada::isValidScope($data['ambito'])) {
            throw new Exception(_('Select a valid call scope'));
        }

        if (false === llamada::isValidStatus($data['estado'])) {
            throw new Exception(_('Select a valid call status'));
        }

        return $data;
    }

    public function getCompanies() {

        $empresas = empresa::getEnterpriseCompanies();
        $arrAsoc = array();
        foreach($empresas as $empresa) {
            $arrClientes[$empresa->getUID()] = $empresa->getUserVisibleName();
        }
        return($arrClientes);
    }

    public function getUserVisibleName(){
        $date = $this->obtenerDato("fecha_llamada_sati");
        return $this->obtenerUsuarioStaff()->getUserVisibleName() . " - " . $this->obtenerUsuario()->getUserVisibleName() . " - " . date("d/m/Y", strtotime($date));
    }

    public function getInlineArray($usuario){
        $inlineArray = array();

        $datos = $this->getInfo();
            // TIME
            $date = date("d/m/Y", strtotime($datos["fecha_llamada_sati"]) );

            $time = array();
            $time["img"] = RESOURCES_DOMAIN . "/img/famfam/time.png";

            $time[] = array( "nombre" => $date . " " . $datos["hora_llamada_sati"] . " - " . $datos["hora_fin_llamada_sati"] );

        $inlineArray[] = $time;
        return $inlineArray;
    }

    public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = NULL, $extra = array(), $force = false){
        $info = parent::getInfo($publicMode, $comeFrom, $usuario, $extra, $force );

        if( $comeFrom == "table" ){
            $data =& $info[ $this->uid ];
            $data["comentario"] = string_truncate($data["comentario"], 110);
        }

        return $info;
    }

    /** RETORNA LA URL DEL ICONO */
    public function getIcon($mode=false){
        switch($mode){
            case false:
                return RESOURCES_DOMAIN ." /img/famfam/folder.png";
            break;
            case "open":
                return RESOURCES_DOMAIN ." /img/famfam/folder_table.png";
            break;
        }
    }

    public function obtenerHilo() {
        $sql = "SELECT uid_hilo FROM ".TABLE_LLAMADA." WHERE uid_llamada=".$this->getUID();
        $uidHilo = $this->db->query($sql,0,0);
        if($uidHilo) {
            return $uidHilo;
        }
        return $this->getUID();
    }

    public function obtenerLlamadasHijas(){
        return llamada::obtenerTodasLlamadas( $this->getUID() );
    }

    public function numeroHijas(){
        $sql = "SELECT COUNT(uid_llamada) FROM ".$this->tabla." WHERE uid_hilo=".$this->getUID();
        $total = $this->db->query($sql,0,0);
        return $total;
    }

    public function obtenerUsuario(){
        return new usuario($this->obtenerDato("uid_usuario_atendido"));
    }

    public function obtenerUsuarioStaff(){
        return new usuario($this->obtenerDato("uid_usuario_sati"));
    }

    public static function getChartData($start=false, $end=false, $type=false){
        $db = db::singleton();
        $series = array();
        $series = array();

        switch( $type ){
            case false : // por defecto usuarios
                $titulo = "Grafico de llamadas mensual por usuario";
                $sql = "SELECT uid_usuario_sati FROM ".  TABLE_LLAMADA ." WHERE uid_usuario_sati GROUP BY uid_usuario_sati";
                $list = $db->query($sql, "*", 0, "usuario");

                $data = $series = array();
                foreach($list as $user ){
                    $SQL = "
                        SELECT count(uid_llamada) as cuenta, uid_usuario_sati, DATE_FORMAT(fecha_llamada_sati, '%d')  as date
                        FROM ". TABLE_LLAMADA ."
                        WHERE 1 AND uid_usuario_sati = ". $user->getUID() ."
                    ";

                    if( $start ){ $SQL .= " AND fecha_llamada_sati >= '". db::scape($start) ."'"; }
                    if( $end ){ $SQL .= " AND fecha_llamada_sati <= '". db::scape($end) ."'"; }

                    $SQL .="
                        GROUP BY uid_usuario_sati,  DATE_FORMAT(fecha_llamada_sati, '%d')
                        ORDER BY fecha_llamada_sati, uid_usuario_sati
                    ";

                    $userdata = array();
                    $result = $db->query($SQL, true);
                    foreach( $result as $i => $line ){
                        $xaxis[] = $line["date"];
                        $yaxis[] = $line["cuenta"];
                        $userdata[] = array( $line["date"], $line["cuenta"], $line["cuenta"]);
                    }
                    if( count($userdata) ){
                        $series[] = array("label" => $user->getUserName("utf8_encode") );
                        $data[] = $userdata;
                    }
                }

                $output = self::getDefaultCharData($yaxis, $xaxis);
            break;
            case "ambito":
                $titulo = "Grafico de llamadas mensual por ambito";
                $sql = "SELECT ambito FROM ".  TABLE_LLAMADA ." WHERE ambito != '' GROUP BY ambito";
                $list = $db->query($sql, "*", 0);

                $data = $series = array();
                foreach($list as $ambito ){
                    $SQL = "
                        SELECT count(uid_llamada) as cuenta, ambito, DATE_FORMAT(fecha_llamada_sati, '%d')  as date
                        FROM ". TABLE_LLAMADA ."
                        WHERE 1 AND ambito = '$ambito'
                    ";

                    if( $start ){ $SQL .= " AND fecha_llamada_sati >= '". db::scape($start) ."'"; }
                    if( $end ){ $SQL .= " AND fecha_llamada_sati <= '". db::scape($end) ."'"; }

                    $SQL .="
                        GROUP BY ambito,  DATE_FORMAT(fecha_llamada_sati, '%d')
                        ORDER BY fecha_llamada_sati, ambito
                    ";

                    $userdata = array();
                    $result = $db->query($SQL, true);
                    foreach( $result as $i => $line ){
                        $xaxis[] = $line["date"];
                        $yaxis[] = $line["cuenta"];
                        $userdata[] = array( $line["date"], $line["cuenta"], $line["cuenta"]);
                    }
                    if( count($userdata) ){
                        $series[] = array("label" => utf8_encode($ambito) );
                        $data[] = $userdata;
                    }
                }

                $output = self::getDefaultCharData($yaxis, $xaxis);
            break;
        }


        $output["data"] = $data;
        $output["series"] = $series;
        $output["title"] = $titulo;

        return $output;
    }


    public static function obtenerConteoTodasLlamadas(){
        $db = db::singleton();
        $sql = "SELECT count(uid_llamada) FROM ".TABLE_LLAMADA." WHERE uid_hilo = 0 ORDER BY hora_fin_llamada_sati DESC";
        return $db->query($sql,0,0);
    }

    public static function obtenerTodasLlamadas($uidLlamada=null, $limit=false) {
        $db = db::singleton();

        if( isset($uidLlamada) ) {
            $where = " uid_hilo = ".$uidLlamada;
        } else {
            $where = " uid_hilo = 0 ";
        }


        $sql = "SELECT uid_llamada FROM ".TABLE_LLAMADA." WHERE ".$where." ORDER BY fecha_fin_llamada DESC, hora_fin_llamada_sati DESC";
        if( $limit ){
            $sql .= " LIMIT ". reset($limit) .", ". end($limit);
        }

        //$sql = "SELECT uid_llamada FROM ".TABLE_LLAMADA." ORDER BY fecha_llamada_sati DESC";
        $uidLlamadas = $db->query($sql,"*",0);
        if($uidLlamadas) {
            foreach($uidLlamadas as $uidLlamada) {
                $arrObLlamadas[] = new llamada($uidLlamada);
            }
        }

        return $arrObLlamadas;
    }

    public static function getEstados() {
        return array("Resuelta" => "Resuelta", "En Proceso" => "En Proceso", "Pendiente" => "Pendiente");
    }

    public static function getAmbitos(){
        $sql = "SELECT ambito FROM ". TABLE_LLAMADA ." WHERE 1
            AND ambito != ''
            AND DATEDIFF(now(), fecha_llamada_sati) < 160
            GROUP BY ambito
        ";

        $values = db::get($sql, "*", 0);

        $options =  array();
        $options["Otros"] = array("value" => "Otros", "innerHTML" => "Otros");
        //$options[] = array("innerHTML" => "-----> Añadir otro", "className" => "other");
        foreach( $values as $i => $val ){
            $options[ucfirst(trim(utf8_encode($val)))] = array("value" => ucfirst(trim(utf8_encode($val))), "innerHTML" => ucfirst(trim(utf8_encode($val))) );
        }

        $options = array_unique($options, SORT_REGULAR);

        return $options;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        //RECOGEMOS DATOS QUE NOS HACEN FALTA
        //$modo = func_get_args(); $modo = ( isset($modo[0]) ) ? $modo[0] : null;

        $fields = new FieldList;

        //$fields["uid_empresa"] = new FormField(array("tag" => "select","data" => llamada::getCompanies(),"type" => "text", "blank" => false));
        //$fields["uid_empresa"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "className" => "autocomplete-input", "href" => "t=empresa&f=nombre", "rel" => "empresa"));
        $fields["uid_usuario_atendido"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "className" => "autocomplete-input", "href" => "t=usuario&f=usuario", "rel" => "usuario"));
        $fields["comentario"] = new FormField(array("tag" => "textarea", "type" => "text"));
        $fields["estado"] = new FormField(array("tag" => "select", "data" => self::getEstados() ,"type" => "text", "blank" => false));
        $fields["ambito"] = new FormField(array("tag" => "select", "others" => true, "data" => self::getAmbitos(), "blank" => false ));

        if ($usuario instanceof usuario) {
            // We show the user the his current time
            $currentTimeStamp   = $usuario->getCurrentTime();
            $beginDate          = new DateTime();
            $beginDate          = $beginDate->setTimestamp($currentTimeStamp);

            $endDate            = new DateTime();
            $endDate            = $endDate->setTimestamp($currentTimeStamp);
            $endDate            = $endDate->modify('+5 minute');
        } else {
            // case we do not have user, UTC time
            $beginDate          = new DateTime();
            $endDate            = new DateTime();
            $endDate            = $endDate->modify('+5 minute');
        }

        $fields["fecha_llamada_sati"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "size" => "10", "value" => $beginDate->format('d/m/Y')));
        $fields["hora_llamada_sati"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "size" => "11", "value" => $beginDate->format('H:i')));
        $fields["hora_fin_llamada_sati"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false, "size" => "11", "value" => $endDate->format('H:i')));

        switch( $modo ){
            case "":
                unset($fields["uid_usuario_atendido"] );
            break;
            case "table": case "edit":
                unset($fields["uid_usuario_atendido"]);
                unset($fields["fecha_llamada_sati"]);
                unset($fields["hora_llamada_sati"]);
                unset($fields["hora_fin_llamada_sati"]);
                unset($fields["estado"]);

                if ($objeto instanceof self) {
                    $staff = $objeto->obtenerUsuarioStaff();
                    $data = array($staff->getUID() => $staff->getUserName());

                    $fields["uid_usuario_sati"] = new FormField(array("tag" => "span", "blank" => false, "data" => $data));
                }

                $fields["estado"] = new FormField(array("tag" => "span", "blank" => false));
            break;
            case "nuevo":
                $fields["uid_usuario_sati"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                $fields["uid_hilo"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                $fields["uid_empresa"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
            break;
        }

        return $fields;
    }

    public static function isValidScope($scope)
    {
        if ($scope) {
            return in_array($scope, array_keys(self::getAmbitos()), true);
        }
        return false;
    }

    public static function isValidStatus($status)
    {
        if ($status) {
            return in_array($status, self::getEstados(), true);
        }
        return false;
    }
}
