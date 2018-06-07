<?php

class plantillaemail extends basic
{
    const TIPO_ANULACION = 3;
    const TIPO_SUBIR = 6;
    const TIPO_FIRST_COMMENT = 52;
    const TIPO_URGENT_VALIDATION = 53;
    const TIPO_COMPANY_OK = 54;
    const TIPO_INVOICE_NOTIFICATION = 55;

    public static $templatesToAvoid = array(self::TIPO_ANULACION, self::TIPO_SUBIR, self::TIPO_FIRST_COMMENT, self::TIPO_COMPANY_OK);

    protected $vars;
    public $replaced;

    public function __construct( $param , $saveOnSession = true/*uid or data*/ ){
        $this->tipo = "plantillaemail";
        $this->tabla = TABLE_PLANTILLAEMAIL;

        $this->instance( $param, $saveOnSession );
    }

    public static function getRouteName () {
        return 'mailtpl';
    }

    public function getUserVisibleName(){
        return $this->obtenerDato("descripcion");
    }


    public function obtenerPlantillaAtributo($empresa){
        $sql = "SELECT uid_plantilla_atributo FROM ". TABLE_PLANTILLAATRIBUTO ." WHERE uid_plantillaemail = ". $this->uid . " AND uid_empresa = ". $empresa->getUID();
        $uid = $this->db->query($sql, 0, 0);

        if( !is_numeric($uid) ){
            $sql = "INSERT INTO ". TABLE_PLANTILLAATRIBUTO ." ( uid_empresa, uid_plantillaemail ) VALUES ( ". $empresa->getUID() .", ". $this->getUID() ." )";
            if( !$this->db->query($sql) ){
                return $this->db->lastError();
            } else {
                $uid = $this->db->getLastId();
            }
        }

        return new plantilla_atributo($uid, $empresa);
    }

    public function getFileContent( $empresa, $reemplazar = false ){
        $fileContent = archivo::leer($this->getFilePath($empresa));

        // --- buscamos las variables
        $variables = self::obtenerStringsPredefinidos(true);

        foreach ($variables as $var) {
            $val = null;
            if ($metodo = $var["metodo"]) {
                $val = call_user_func($metodo);
            } elseif(isset($_GET[$var["nombre"]])) {
                $val = utf8_decode(db::scape($_GET[$var["nombre"]]));
            } elseif (isset($this->replaced[$var["value"]])) {
                $val = utf8_decode($this->replaced[$var["value"]]);
            } else {
                $val = " ";
            }

            if ($val) $this->vars[$var["value"]] = $val;
        }

        if ($reemplazar) {
            $content = self::reemplazar($fileContent, $this->vars);
        } else {
            $content = $fileContent;
        }

        return utf8_encode($content);
    }

    public function hasAttributes(){
        return ( $this->obtenerDato("atributos") ) ? true : false;
    }

    public function asignar( $var, $val ){
        $this->vars["{%".$var."%}"] = $val;
        return $this->vars;
    }

    public function getVars(){
        return $this->vars;
    }

    public function getFilePath( $empresa ){
        $idEmpresa = $empresa->getUID();
        $filePath = DIR_EMAILTEMPLATES . "empresa_". $idEmpresa ."/". $this->getName() .".html";
        return $filePath;
    }

    public function getName(){
        $sql = "SELECT nombre FROM ". TABLE_PLANTILLAEMAIL ." WHERE uid_plantillaemail = ". $this->getUID();
        return $this->db->query( $sql, 0, 0);
    }


    public static function getMatches($string, $var){
        if( preg_match_all('/{%?'.$var.'\|(.*?)%?}/s', $string, $matches) ){
            $results = array();
            foreach($matches[0] as $i => $match){
                $results[] = array(
                    "var" => $match,
                    "params" => explode(",", $matches[1][$i])
                );
            }
            return $results;
        }
        return false;
    }

    public static function reemplazar($string, $vars){
        $arrayInput = $arrayOutput = array();
        foreach( $vars as $key => $value ){
            //dump( "Buscando '$key' para reemplazarla por '$value' en ". trim($string) );
            //var_dump( strpos(utf8_decode($string), $key) );
            $arrayInput[] = $key;
            $arrayOutput[] = $value;
        }
        return str_replace( $arrayInput, $arrayOutput, $string);
    }

    public static function instanciar( $nombre ){

        $arrayPlantillas = self::obtenerTodosNombres();

        if( !$arrayPlantillas->contains($nombre) ){ return false; }

        $db = db::singleton();
        $sql = "SELECT uid_plantillaemail FROM ". TABLE_PLANTILLAEMAIL ." WHERE nombre = '". db::scape($nombre) ."'";
        $idPlantillaEmail = $db->query($sql, 0, 0);
        if( $idPlantillaEmail && is_numeric($idPlantillaEmail) ){
            return new self($idPlantillaEmail, false);
        } else {
            return false;
        }
    }

    public static function publicFields(){
        $arrayCampos = new FieldList();
        $arrayCampos["nombre"]  = new FormField( array("tag" => "input",    "type" => "text"));
        return $arrayCampos;
    }

    public static function obtenerStringsPredefinidos($includePercentage = false){
        $tpl = Plantilla::singleton();
        /** ESTE ARCHIVO TIENE UN DISEÑO DE 3 COLUMNAS SEPARADAS POR EL CARACTER "="
            password = contrasena_usuario = usuario::randomPassword
                1 · String que usa el usuario
                2 · Reemplazo de texto
                3 · Funcion para obtenerlo | opcional
        */
        $data = archivo::leer( DIR_CONFIG . "predefinido.txt");

        if( !is_callable("splitit") ){
            function splitit( $string ){ $aux = explode("=", $string ); $aux = array_map( "trim", $aux); return $aux; }
        }

        $lineas = explode("\n", $data );
        $lineas = array_map( "splitit", $lineas );

        $arrayObjeto = array();
        foreach( $lineas as $linea ){
            if( isset($linea[0]) && $linea[0] && isset($linea[1]) ){
                $string = $tpl->getString($linea[1]);
                //$arrayObjeto[] = "{ nombre: '".$linea[0]."', value: '{%".$linea[0]."%}', descripcion: '".$string."' }";
                $metodo = ( isset($linea[2]) && trim($linea[2])  ) ? $linea[2] : null;
                $translate = ( isset($linea[3]) && trim($linea[3])  ) ? true : false;
                $arrayObjeto[] = array( "nombre" => $linea[0], "value" => "{".$linea[0]."}", "descripcion" => $string, "metodo" => $metodo, "translate" => $translate );
                if ($includePercentage){
                    $arrayObjeto[] = array( "nombre" => $linea[0], "value" => "{%".$linea[0]."%}", "descripcion" => $string, "metodo" => $metodo, "translate" => $translate );
                }

            }
        }
        return $arrayObjeto;
    }

    public static function obtenerTodosNombres(){
        $nombres = new ArrayObjectList();
        $arrayPlantillasemail = self::obtenerTodas();
        if( count($arrayPlantillasemail) ){
            foreach( $arrayPlantillasemail as $plantillaemail ){
                if( $plantillaemail instanceof plantillaemail ){
                    $nombres[ $plantillaemail->getUID() ] = $plantillaemail->getName();
                }
            }
        }
        return $nombres;
    }

    public static function obtenerTodas($filter = false)
    {
        $dbc = db::singleton();
        $sql = "SELECT uid_plantillaemail FROM ". TABLE_PLANTILLAEMAIL;

        $partnerNotifications = false;
        $invoiceNotifications = true;

        if (isset($filter['company'])) {
            if ($filter['company']->isPartner()) {
                $partnerNotifications = true;
            }

            if ($filter['company']->isEnterprise()) {
                $invoiceNotifications = false;
            }

            unset($filter['company']);
        } else {
            $partnerNotifications = true;
        }


        $sqlFilter = array();

        if ($partnerNotifications == false) {
            $sqlFilter[] = " uid_plantillaemail != " . self::TIPO_URGENT_VALIDATION;
        }

        if ($invoiceNotifications == false) {
            $sqlFilter[] = " uid_plantillaemail != " . self::TIPO_INVOICE_NOTIFICATION;
        }

        if ($filter) {
            foreach ($filter as $field => $value) {
                $sqlFilter[] =" $field = '$value'";
            }
        }

        if (count($sqlFilter)) {
            $sql .= " WHERE 1 AND ". implode(" AND ", $sqlFilter);
        }

        $plantillaEmail = $dbc->query($sql, "*", 0, "plantillaemail");
        return new ArrayObjectTemplateEmail($plantillaEmail);
    }

    public function getVisibleEmailTemplates(){

        $templates = self::obtenerTodas();
        $setTemplates = new ArrayObjectList();
        $hiddenTemplates = array(1,2,3,5,6,10,51,52,53,54);

        foreach( $templates as $template ){
            if (in_array($template->getUID(),$hiddenTemplates) ) continue;
            $setTemplates[] = $template;
        }

        return $setTemplates;
    }

    }
