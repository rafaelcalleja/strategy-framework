<?php

class jsonAGD {
    protected $json;

    public function __construc(){
        $this->json = array();
    }

    public function ifNoData($data){
        $string = '';
        if( $data["href"] ) {
            $string .= '<a href="'.$data["href"].'" ';
            if(isset($data['class'])) $string .= ' class="'.$data["class"].'" ';
            if(isset($data['target'])) $string .= ' target="'.$data["target"].'" ';
            $string .= '>';
        }

        $string .= $data['innerHTML'];
        if( $data["href"] ) $string .= '</a>';
        $this->json['ifnodata'] = $string;
    }

    /** DADO UN USUARIO EXTRAE LOS HELPERS EN BASE A LOS DATOS ACTUALES **/
    public function addHelpers($usuario){
        $helpers = $usuario->getHelpers($_SERVER["PHP_SELF"] );
        if( $helpers && count($helpers) ){
            if( !isset($this->json["helpers"]) ) $this->json["helpers"] = array();
            foreach($helpers as $helper ){
                $this->json["helpers"][] = $helper->getOutputArray();
            }
        }
    }

    public function addHelper(array $helperData){
        if( !isset($this->json["helpers"]) ) $this->json["helpers"] = array();
        $this->json["helpers"][] = $helperData;
    }

    public function addPubli( $usuario ){
        if( 0 && $usuario->esAdministrador() ){
            $this->json["selector"][ "#adsense" ] = "<iframe src='adsense.php' frameborder='0' ></iframe>";
        }
    }


    /** AÑADIR A LA SALIDA JSON CUALQUIER DATO */
    public function addData( $name, $data ){
        $this->json[ $name ] = $data;
    }

    public function nuevoSelector( $selector, $html ){
        $this->json["selector"][ $selector ] = $html;
    }

    public function addInfoLine($html, $offset = NULL){
        $this->json["extralines"][$offset] = $html;
    }

    public function establecerTipo( $tipo ){
        $tiposDisponibles = array( "data", "bigdata", "simple", "options" );
        if( in_array( strtolower($tipo), $tiposDisponibles) ){
            $this->json["view"] = $tipo;
        }
    }

    public function iface($iface){
        $this->json["iface"] = $iface;
    }

    public function loadScript( $name ){
        $this->json["load"]["script"][] = $name;
    }

    public function loadStyle( $name ){
        $this->json["load"]["style"][] = $name;
    }
    /** DEFINIR DE UNA MANERA MAS SENCILLA ELEMENTOS DE LA PÁGINA */
    public function element( $lugar, $elemento, $atributos ){
        $this->json[ $lugar ][ $elemento ][] = $atributos;
    }




    /**
        LOS DATOS QUE SE USARAN PARA LA TABLA
        Array(
            [0] => Array(
                    [lineas] => Array(
                            [1] => Array(
                                    [cif] => B54745221
                                    [nombre] => Afianza Telecom 2
                                )
                        )
                    [options] => Array
                         [11] => Array
                                (
                                    [name] => Ver Informacion
                                    [uid_accion] => 10
                                    [innerHTML] => Ver Informacion
                                    [img] => /img/famfam/eye.png
                                    [href] => empresa/ficha.php?poid=1
                                )

                    [inline] => Array (
                            [docs] => Array(
                                    [0] => Array(
                                            [nombre] => Pendientes
                                            [className] => stat stat_1
                                            [href] => #documentos.php?m=empresa&poid=1&estado=1
                                       )
                           )
    */

    public function datos( $arrayDatos ){
        $arrayDatos = ( $arrayDatos instanceof ArrayObject ) ? $arrayDatos->getArrayCopy() : $arrayDatos;
        $desplegables = 0;


        $maximunColums = $hasOptions = 0;
        if ( $arrayDatos ) {
            foreach( $arrayDatos as $i => $conjuntoDatos ){
                if( $conjuntoDatos && isset($conjuntoDatos["lineas"]) && count($conjuntoDatos) ){

                    $conjuntoDatosKeys = array_keys($conjuntoDatos["lineas"]);
                    $key = reset($conjuntoDatosKeys);
                    if( is_numeric($key) ) $arrayDatos[$i]["key"] = $key; //eso solo para ids
                    $columnas = $conjuntoDatos["lineas"][$key];
                    $arrayDatos[$i][$key] = $columnas;

                    $arrayDatos[$i]["inlineoptions"] = $desplegables;

                    $totalColumsInThisLine = count( $columnas );
                    if( isset($conjuntoDatos["inline"]) && true === is_countable($conjuntoDatos["inline"]) && count($conjuntoDatos["inline"]) ){ $totalColumsInThisLine += count($conjuntoDatos["inline"]); };
                    $maximunColums = ( $maximunColums < $totalColumsInThisLine ) ? $totalColumsInThisLine : $maximunColums;

                    if( isset($conjuntoDatos["options"]) ){ $hasOptions = 1; };

                    if( isset($arrayDatos[$i]["lineas"]["className"]) && $className = $arrayDatos[$i]["lineas"]["className"] ){
                        $arrayDatos[$i]["className"] = $className;
                    }
                    unset($arrayDatos[$i]["lineas"]);
                }
            }
        }



        if( $maximunColums ){
            $this->json[ "hasoptions" ] = $hasOptions;
            $this->json[ "maxcolums" ] = $maximunColums;
            $this->json[ "datos" ] = $arrayDatos;
        }
    }

    public function addDataTabs($tabs){
        $this->json["datatabs"] = $tabs;
    }

    public function busqueda($datosBusqueda){
        $this->json["busqueda"] = $datosBusqueda;
    }

    public function acciones( $name, $img, $href, $class = false ){
        $this->json[ "acciones" ][] = array( "nombre" => $name, "img" => $img, "href" => $href, "clase" => $class );
    }

    /** DEFINIR UN NOMBRE PARA LA TABLA ACTUAL (CUALQUIER VISTA) */
    public function nombreTabla( $nombre ){
        $this->json[ "tabla" ] = $nombre;
    }

    /** ESTABLECER EL JSON MANUALMENTE */
    public function set( $jsonArray ){
        $this->json = $jsonArray;
    }

    public function informacionNavegacion(){
        if( isset($this->json["navegacion"]) && is_array($this->json["navegacion"]) ){
            $this->json["navegacion"] = array_merge($this->json["navegacion"], func_get_args());
        } else {
            $this->json["navegacion"] = func_get_args();
        }
        $pos = 0;
        foreach( $this->json["navegacion"] as $i => $key ){
            if( !$key ){
                array_splice($this->json["navegacion"],$pos,1);
                $pos--;
            }
            $pos++;
        }
    }

    public function menuSeleccionado($modulo){
        $this->json["moduloseleccionado"] = $modulo;
    }

    public function addPagination( $datosPaginacion ){
        $this->json[ "paginacion" ]["href"] = (isset($datosPaginacion["href"])) ? $datosPaginacion["href"] : false;
        $this->json[ "paginacion" ][0] = $datosPaginacion["pagina_anterior"];
        $this->json[ "paginacion" ][1] = $datosPaginacion["pagina_siguiente"];

        $this->json[ "paginacion" ]["total"] = $datosPaginacion["pagina_total"];

        $this->json[ "paginacion" ]["from"] = $datosPaginacion["sql_limit_start"]+1;
        $this->json[ "paginacion" ]["to"] = ($datosPaginacion["sql_total"] > $datosPaginacion["sql_limit_end"]) ? $datosPaginacion["sql_limit_end"] : $datosPaginacion["sql_total"];
        $this->json[ "paginacion" ]["to"] = $this->json[ "paginacion" ]["from"]-1 + $this->json[ "paginacion" ]["to"];

        $this->json[ "paginacion" ]["of"] = $datosPaginacion["sql_total"];

        if( $this->json[ "paginacion" ]["to"] > $this->json[ "paginacion" ]["of"] ){
            $this->json[ "paginacion" ]["to"] = $this->json[ "paginacion" ]["of"];
        }

        parse_str( $_SERVER["QUERY_STRING"], $params  );
        $page = $_SERVER["PHP_SELF"];
            $nextparams = $params; $nextparams["p"] = $datosPaginacion["pagina_siguiente"];
            $prevparams = $params; $prevparams["p"] = $datosPaginacion["pagina_anterior"];

        $this->json[ "paginacion" ]["href"] = array(
            "prox" => $page . "?" . http_build_query($nextparams),
            "prev" => $page . "?" . http_build_query($prevparams),
            "actual" => $page . "?" . http_build_query($params)
        );
    }

    public function display(){
        header('Content-type: text/plain; charset=utf-8');
        header('App-version: ' . VKEY);

        if( !isset($_SERVER["HTTP_X_REQUESTED_WITH"]) ){

            if( $uid = $_SESSION[SESSION_USUARIO]){
                $usuario = new usuario($uid);
                if( $usuario instanceof Iusuario && $usuario->esStaff() ){
                    // No hacemos nada, el usuario podrá acceder para depurar
                } else {
                    $url = $_SERVER["REQUEST_URI"];
                    $log = new log(5);
                    $log->info("core","acceso indirecto", $url, "bloqueado", true);
                    $location = str_replace("/agd/", "/agd/#", $url);
                    //header("Location: $location");
                    //exit;
                }
            }
        }

        // AYUDA EN LA DEPURACION
        $buffer = ob_get_clean();
        if ($buffer) die($buffer);

        print json_encode($this->json);
    }

    /* funciones internas */


    /* funciones estaticas */


    public static function encriptar( $plainString ){
        return $plainString;
    }

    public static function desencriptar( $encodedString ){
        return $encodedString;
    }
}
