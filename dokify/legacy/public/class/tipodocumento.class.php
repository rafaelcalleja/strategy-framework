<?php

    class tipodocumento extends elemento implements Ielemento {

        const  TIPO_DOCUMENTO_ITA       = 1;
        const  TIPO_DOCUMENTO_TC2       = 2;
        const  TIPO_DOCUMENTO_TC1       = 3;
        const  TIPO_DOCUMENTO_RECO      = 4;
        const  TIPO_DOCUMENTO_ALTASS    = 5;
        const  TIPO_DOCUMENTO_AUTONOMOS = 6;

        public function __construct( $param , $extra = true ){
            $this->tipo = "tipodocumento";
            $this->tabla = TABLE_DOCUMENTO;
            $this->instance( $param, $extra );
        }

        public static function getRouteName () {
            return 'reqtype';
        }

        public function getUserVisibleName(){
            $datos = $this->getInfo();
            return $datos["nombre"];
        }

        public function getCustomId(){
            $datos = $this->getInfo();
            return $datos["custom_id"];
        }

        public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()) {
            $info = parent::getInfo(false);

            $data = array();

            $data["nombre"] =  array(
                "innerHTML" => string_truncate($info["nombre"], 60),
                "href" => "../agd/ficha.php?m=".get_class($this)."&poid={$this->uid}",
                "className" => "box-it link",
                "title" => $info["nombre"]
            );


            return array( $this->getUID() => $data );
        }


        public function getAvailableOptions(Iusuario $user = NULL, $publicMode = false, $config = 0, $groups=true, $ref=false, $extraData = null) {
            return config::obtenerOpciones( $this->getUID(), $this->tipo, $user, $publicMode, $config, 1, $groups, $ref);
        }


        public static function getSearchData(Iusuario $usuario, $papelera = false, $all = false){
            if (!$usuario->accesoModulo(__CLASS__, true)) return false;

            if( !$all ){
                $limit = " documento.uid_documento IN (
                    SELECT attr.uid_documento FROM ". TABLE_DOCUMENTO_ATRIBUTO ." attr WHERE uid_empresa_propietaria = {$usuario->getCompany()->getUID()}";
                    if( is_bool($papelera) ) $limit .= " AND activo = ". ($papelera ? 0 : 1);
                $limit .= ")";
            }
            //dump($limit);exit;

            $searchData[ TABLE_DOCUMENTO ] = array(
                "type" => "tipodocumento",
                "fields" => array("nombre"),
                "limit" => isset($limit) ? $limit : "",
                "accept" => array(
                    "tipo" => "tipodocumento",
                    "documento" => true,
                    "list" => true
                )
            );

            return $searchData;
        }

        public static function crearNuevo($informacion, $usuario)
        {
            $db = db::singleton();
            $fields = self::publicFields(elemento::PUBLIFIELDS_MODE_NEW);
            $datos = $fields->keys();
            $values = array();

            // Si se ha selecciona creacion multiple
            if( isset($informacion["agrupamiento"]) ){
                $empresa  = $usuario->getCompany();
                $agrupamientos = $empresa->obtenerAgrupamientos(); // comprobacion de acceso
                if( in_array($informacion["agrupamiento"], elemento::getCollectionIds($agrupamientos)) ){

                    // Instanciamos el agrupamiento seleccionado
                    $agrupamiento = new agrupamiento($informacion["agrupamiento"]);

                    // Recorrer cada agrupador para crear un tipo por cada uno de ellos
                    $coleccion = array();
                    $agrupadores = $agrupamiento->obtenerAgrupadores();
                    foreach( $agrupadores as $agrupador ){
                        $attr = [
                            'nombre' => $informacion['nombre'] . ' - ' . $agrupador->getTypeString() . ' ' . $agrupador->getUserVisibleName(),
                            'flags' => $informacion['flags'],
                            'description' => $informacion['description'],
                            'date_criteria' => $informacion['date_criteria'],
                        ];

                        // Preparamos un nombre que se entienda y creamos el tipo de documento
                        $tipodocumento = self::crearNuevo($attr, $usuario);

                        if ($tipodocumento instanceof tipodocumento && $tipodocumento->getUID()) {
                            // Preparamos los datos para insertar un nuevo atributo
                            $attr = [
                                'nombre_documento' => $informacion['alias'],
                                'documento_obligatorio' => $informacion['obligatorio'],
                                'referenciar_empresa' => isset($informacion['referenciar_empresa']) ? $informacion['referenciar_empresa'] : '0',
                                'documento_descarga' => $informacion['descargar'],
                                'documento_duracion' => $informacion['duracion'],
                                'documento_grace_period' => $informacion['grace_period'],
                                'documento_codigo' => $informacion['codigo'],
                                'doc_ejemplo' => null,
                                'id_solicitante' => [
                                    $agrupador->getUID()
                                ],
                                'req_type' => $informacion['req_type'],
                                'tipo_documento' => $tipodocumento->getUID(),
                                'tipo_solicitante' => 'agrupador',
                                'tipo_receptores' => [
                                    util::getModuleName($informacion['modulo_destino'])
                                ]
                            ];

                            // Creamos el atributo
                            $atributos = documento_atributo::crearNuevo($attr, $usuario);

                            if( !is_array($atributos) || !count($atributos) ){
                                echo "Algunos de los atributos no se han podido crear";
                            }
                            $coleccion[] = $tipodocumento;
                        } else {
                            return "Error al crear tipos de documento";
                        }
                    }
                    return $coleccion;
                }
            } else {

                if (count($datos)) {
                    foreach( $datos as $campo ){
                        if( isset($informacion[$campo]) ){
                            $values[] = "'". utf8_decode(db::scape($informacion[$campo])) ."'";
                        }
                    }

                    $sql = "INSERT INTO ". TABLE_DOCUMENTO ." ( ". implode(",",$datos) ." ) VALUES (".implode(",",$values) .")";
                    if( !$db->query($sql) ){ return $db->lastErrorString(); }

                    return new self($db->getLastId());
                }

                return false;
            }
        }

        public function eliminar(Iusuario $usuario = NULL){
            if ($this->getCustomId()) {
                return "cannot_be_delete";
            }
            return parent::eliminar($usuario);
        }

        static public function optionsFilter($uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){
            $condicion = array();

            if ($uidelemento) {
                $tipodocumento = new self($uidelemento);
                if ($tipodocumento->getCustomId()) {
                    $condicion[] = " uid_accion NOT IN (14) ";
                }
            }

            if( count($condicion) ){
                return " AND ". implode(" AND ", $condicion);
            }

            return false;
        }

        public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false) {
            $campos = new FieldList;
            switch ($modo) {
                default:
                    $campos["nombre"] = new FormField(array("tag" => "input",   "type" => "text"));
                    $campos["flags"] = new FormField(array("tag" => "input",    "type" => "text"));
                    $campos["description"]  = new FormField(array("tag" => "textarea", "maxlength" => "500", "rows" => 4));
                    $campos["date_criteria"]  = new FormField(array("tag" => "textarea", "maxlength" => "500", "rows" => 4));

                    if ($usuario instanceof usuario && $usuario->esStaff()) {
                        $campos["is_standard"]  = new FormField(array("tag" => "input",     "type" => "checkbox", "className" => "iphone-checkbox" ));
                    }
                break;
                case "new-multiple":
                    // Seleccion de multiplicador

                    $empresa = $usuario->getCompany();
                    $campos["agrupamiento"] = new FormField(array( "tag" => "select", "hr" => true,
                        "data" => $empresa->obtenerAgrupamientosPropios()
                    ));

                    // Campos de tipo de documento
                    $campos["nombre"] = new FormField(array("tag" => "input", "type" => "text", "className" => "update-input", "target" => "input[name='alias']"));
                    $campos["flags"] = new FormField(array("tag" => "input",    "type" => "text", "hr" => true));
                    $campos["description"]  = new FormField(array("tag" => "textarea", "maxlength" => "500", "rows" => 3));
                    $campos["date_criteria"]  = new FormField(array("tag" => "textarea", "maxlength" => "500", "rows" => 3));

                    // Campos de atributo de documento
                    $campos["modulo_destino"] = new FormField(array("tag" => "select", "innerHTML" => "destino",
                        "data" =>  solicitable::getModules()
                    ));


                    $fields = documento_atributo::publicFields("edit", null, $usuario);
                    // Valores mas comunes por defecto
                    $fields["duracion"]["value"] = 0;
                    $fields["obligatorio"]["value"] = 1;
                    $fields["recursividad"]["value"] = empresa::DEFAULT_DISTANCIA;


                    // Finalmente unimos todos los campos
                    $campos = $campos->merge($fields);

                break;
            }
            return $campos;
        }

    }
?>
