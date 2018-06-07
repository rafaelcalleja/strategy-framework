<?php

class carpeta extends elemento implements Ilistable, Ielemento
{
    const ERROR_JAVASCRIPT = '<script>alert("No hay ficheros");</script>';

    public function __construct($param, $extra = false)
    {
        $this->tipo = "carpeta";
        $this->tabla = TABLE_CARPETA;
        $this->instance($param, $extra);
    }

    public function getClickURL(Iusuario $usuario = null, $config = false, $data = null)
    {
    }

    public static function getRouteName()
    {
        return 'folder';
    }

    /***
        Método flexible que permite añadir excepciones de visualización de elementos
    **/
    public function canViewBy(Iusuario $usuario, $context, $extraData = null)
    {
        switch ($context) {
            case Ilistable::DATA_CONTEXT_DESCARGABLES:
                $empresa = $usuario->getCompany();
                if ($agrupadores = $empresa->obtenerAgrupadores()->merge($empresa->obtenerAgrupadoresPropios())) {
                    return $agrupadores->contains($this->obtenerAgrupadorContenedor());
                }
                return false;
            break;
        }
        return false;
    }

    /** RETORNA LA URL DEL ICONO */
    public function getIcon($mode = false)
    {
        switch ($mode) {
            case false:
                return  RESOURCES_DOMAIN ."/img/famfam/folder.png";
            break;
            case "open":
                return  RESOURCES_DOMAIN ."/img/famfam/folder_table.png";
            break;
        }
    }

    public function obtenerDocumentosDisponibles($usuario)
    {
        return $usuario->getCompany()->getDocuments(0);
    }

    public function obtenerDocumentos($elemento = false)
    {
        return $this->obtenerObjetosRelacionados(TABLE_CARPETA ."_documento", "documento", false, false, $elemento);
    }

    public function actualizarDocumentos()
    {
        return $this->actualizarRelacionRequest(TABLE_CARPETA ."_documento");
    }

    public function obtenerDocumentoAtributos($usuario = false)
    {
        $agrupador = $this->obtenerAgrupadorContenedor();
        $documentos = $this->obtenerDocumentos();

        $list = elemento::getCollectionIds($documentos);

        if (!count($list)) {
            return false;
        }

        $sql = "SELECT uid_documento_atributo
                FROM ". TABLE_DOCUMENTO_ATRIBUTO ."
                INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." USING(uid_documento_atributo)
                WHERE uid_documento IN (". implode(",", $list) .")
                GROUP BY uid_documento_atributo
        ";

        $list = $this->db->query($sql, "*", 0);

        $atributos = array();
        foreach ($list as $uid) {
            $atributos[] = new documento_atributo($uid);
        }

        return $atributos;
    }

    public function eliminar(Iusuario $usuario = null)
    {
        $this->desindexar();
        return parent::eliminar($usuario);
    }

    public function obtenerElementosConDocumentos($usuario, $modulo, $filtro = false, $limit = false)
    {
        $agrupador = $this->obtenerAgrupadorContenedor();
        $atributos = $this->obtenerDocumentoAtributos($usuario);
        $list = elemento::getCollectionIds($atributos);

        if (!count($list)) {
            return false;
        }

        $idmodulo = ( is_numeric($modulo) ) ? $modulo : util::getModuleId($modulo);
        $modulo = util::getModuleName($idmodulo);

        $campo = ( is_array($limit) ) ? "uid_elemento_destino" : "count( distinct uid_elemento_destino)";

        $sql = "SELECT $campo
                FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." de INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO . " da USING (uid_documento_atributo, uid_modulo_destino )
                WHERE uid_modulo_destino = $idmodulo
                AND uid_documento_atributo IN (". implode(",", $list) .")
                AND papelera = 0 AND activo = 1 AND descargar = 0
                AND uid_elemento_destino IN (
                    SELECT ae.uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento ae
                    WHERE ae.uid_elemento = de.uid_elemento_destino AND ae.uid_modulo = de.uid_modulo_destino
                    AND ae.uid_agrupador = ". $agrupador->getUID() ."
                )
        ";

        if ($filtro instanceof agrupador) {
            $sql .= " AND ( ( da.uid_elemento_origen = {$filtro->getUID()} AND da.uid_modulo_origen = {$filtro->getModuleId()} ) OR ( de.uid_agrupador = {$filtro->getUID()} ) )";
        }

        if (is_array($limit)) {
            $sql .= " GROUP BY uid_elemento_destino, uid_modulo_destino LIMIT ". $limit[0] . "," . $limit[1];
        }

        $list = $this->db->query($sql, "*", 0);

        if (!is_array($limit)) {
            return $list[0];
        }

        $coleccion = array();
        foreach ($list as $uid) {
            $coleccion[] = new $modulo($uid);
        }
        return $coleccion;
    }

    /** METODO PARA DEV */
    public function obtenerFicheros()
    {
        $coleccionFicheros = array();/* defino un array para guardar la coleccion de ficheros de la carpeta*/
        /* metodo que devuelve arra con indices y por cada uno las columnas
            @param #1 => la tabla de la bbdd donde se buscara
            @param #2 => la culmna que utilizamos para filtrar por el objeto actual
            @param #3 => lo que quiero obtener

            Array(
                [0] => Array(
                    [uid_fichero_carpeta] => 1
                    [uid_fichero]
=> 1
                    [uid_carpeta] => 3
                )
            )
        */
        $relacionados = $this->obtenerRelacionados(TABLE_FICHERO."_carpeta", "uid_carpeta", "uid_fichero");

        if (is_array($relacionados) && count($relacionados)) {
            foreach ($relacionados as $informacionRelacion) {
                $coleccionFicheros[] = new fichero($informacionRelacion["uid_fichero"]);
            }
        }

        return $coleccionFicheros;
    }


    /** COMPROBAMOS LA RECURSIVIDAD DE UNA CARPETA, ES DECIR LLEGAR HASTA EL NIVEL SUPERIOR DE UNA CARPETA **/
    public function obtenerCarpetaSuperior()
    {
        $arrayCarpeta = $this->obtenerRelacionados(TABLE_CARPETA_CARPETA, "uid_carpeta_inferior", "uid_carpeta_superior");
        if (count($arrayCarpeta)) {
            return new carpeta($arrayCarpeta[0]["uid_carpeta_superior"]);
        } else {
            return false;
        }
    }

    public function obtenerCarpetaRaiz()
    {
        $carpetaActual = $this;
        while ($carpetaSuperior = $carpetaActual->obtenerCarpetaSuperior()) {
            $carpetaActual = $carpetaSuperior;
    /*      if (!$carpetaActual) {
                return false;
            }
    */
        }
        return $carpetaActual;
    }


    public function getFoldersPath()
    {
        $folders = array();
        $carpetaActual = $this;

        $folders[] = $carpetaActual;
        while ($carpetaSuperior = $carpetaActual->obtenerCarpetaSuperior()) {
            $carpetaActual = $carpetaSuperior;
            array_unshift($folders, $carpetaActual);
        }
        $path = new ArrayObjectList($folders);
        return $path;
    }


    public function getFoldersPathName()
    {
        $path = $this->getFoldersPath();

        if (count($path)) {
            $nameFolder = array();
            foreach ($path as $folder) {
                $name = $folder->getUserVisibleName();
                $nameFolder[] = $name;
            }

            return implode(' / ', $nameFolder);
        }

        return false;
    }

    public function getPathName()
    {
        $parent = $this->getContainer();
        $parentName = $parent->getUserVisibleName();
        $folderName = $this->getUserVisibleName();
        $pathFolderName = $this->getFoldersPathName();

        return $parentName . " / " . $pathFolderName;
    }

    public function getParent()
    {
        $sql = "SELECT uid_agrupador FROM $this->tabla"."_agrupador WHERE uid_". $this->getType() ." = ". $this->uid;
        if ($uidAgrupador = $this->db->query($sql, 0, 0)) {
            return new agrupador($uidAgrupador);
        }

        $sql = "SELECT uid_modulo, uid_elemento FROM $this->tabla"."_solicitable WHERE uid_carpeta = ". $this->getUID();
        $info = $this->db->query($sql, 0, "*");

        if (isset($info["uid_modulo"]) && isset($info["uid_elemento"])) {
            $modulo = util::getModuleName($info["uid_modulo"]);
            return new $modulo($info["uid_elemento"]);
        }

        return false;
    }

    public function getContainer()
    {
        return $this->obtenerCarpetaRaiz()->getParent();
    }

    public function padreAgrupador()
    {
        if ($this->getParent() instanceof agrupador) {
            return true;
        } else {
            return false;
        }
    }

    /** COMPROBAMOS SI LA CARPETA ES PUBLICA **/
    public function esPublica()
    {
        $rootFolder = $this->obtenerCarpetaRaiz();
        $public     = $rootFolder->obtenerDato("es_publica");
        return (bool)$public;
    }

    public function indexada()
    {
        $indexado = $this->obtenerDato("fecha_indexado");
        return (bool)$indexado;
    }


    public function obtenerAgrupadorContenedor()
    {
        $carpetaSuperior = $this->obtenerCarpetaSuperior();
        if ($carpetaSuperior instanceof carpeta) {
            return $carpetaSuperior->obtenerAgrupadorContenedor();
        } else {
            $arrayAgrupador = @$this->obtenerRelacionados(TABLE_CARPETA_AGRUPADOR, "uid_carpeta", "uid_agrupador")[0];
            if ($arrayAgrupador["uid_agrupador"]) { // si hay resultados
                return new agrupador($arrayAgrupador["uid_agrupador"]);
            } else { //No pertenece a ningún agrupador
                return false; // error
            }
        }
    }


    /** MARCA ESTA CARPETA COMO "HIJA" DE LA PASADA POR PARAMETRO O EL AGRUPADOR */
    public function guardarEn($padre, usuario $usuario = null)
    {
        if ($padre instanceof carpeta) {
            $sql = "INSERT INTO ". $this->tabla ."_carpeta ( uid_carpeta_superior, uid_carpeta_inferior ) VALUES (
                ". $padre->getUID() .", ". $this->getUID() ."
            )";
        } elseif ($padre instanceof agrupador) {
            $sql = "INSERT INTO ". $this->tabla ."_agrupador ( uid_agrupador, uid_carpeta ) VALUES (
                ". $padre->getUID() .", ". $this->getUID() ."
            )";
        } elseif ($padre instanceof solicitable) {
            if ($usuario instanceof usuario) {
                $empresa = $usuario->getCompany();

                $sql = "INSERT INTO ". $this->tabla ."_solicitable ( uid_elemento, uid_modulo, uid_carpeta, uid_empresa_referencia ) VALUES (
                    {$padre->getUID()}, {$padre->getModuleId()}, {$this->getUID()}, {$empresa->getUID()}
                )";
            } else {
                $sql = "INSERT INTO ". $this->tabla ."_solicitable ( uid_elemento, uid_modulo, uid_carpeta ) VALUES (
                    {$padre->getUID()}, {$padre->getModuleId()}, {$this->getUID()}
                )";
            }
        }

        if (!isset($sql)) {
            return false;
        }
        return $this->db->query($sql);
    }

    public function getTreeData(Iusuario $usuario, $data = array())
    {
        $m = obtener_modulo_seleccionado(); // ESTO DEBEMOS CAMBIARLO PARA QUE SEA COMPATIBLE CON CLI
        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

        switch ($context) {
            case Ilistable::DATA_CONTEXT_DESCARGABLES:
                return array(
                    "img" => array(
                        "normal" => $this->getIcon(),
                        "open" => $this->getIcon("open")
                    ),
                    "url" => "../agd/list.php?comefrom=carpeta&m=carpeta&action=carpetasmasficheros&poid={$this->getUID()}&data[context]=descargables&options=0"
                );
            break;
            case Ilistable::DATA_CONTEXT_LISTADO:
                $parametros = array(
                    "m" => $data['modulo'],
                    "poid" => $data['referencia']->getUID(),
                    "folder" => $this->getUID()
                );
                return array(
                    "img" => array(
                        "normal" => $this->getIcon(),
                        "open" => $this->getIcon("open")
                    ),
                    "checkbox" => true,
                    "url" => $_SERVER["PHP_SELF"] . "?". http_build_query($parametros)
                );
            break;
            default:
                $parametros = array(
                    //"m" => $modulo,
                    //"poid" => $referencia->getUID(),
                    "folder" => $this->getUID()
                );

                return array(
                    "img" => array(
                        "normal" => $this->getIcon(),
                        "open" => $this->getIcon("open")
                    ),
                    "checkbox" => true,
                    "url" => "list.php?comefrom=carpeta&m=carpeta&action=carpetasmasficheros&poid={$this->getUID()}"
                );
            break;
        }
    }

    public function getInlineArray(Iusuario $usuario = null, $config = false, $data = null)
    {
        $tpl = Plantilla::singleton();
        $inlineArray = array();
        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;
        $inline = array();

        switch ($context) {
            case Ilistable::DATA_CONTEXT_DESCARGABLES:
                return $inlineArray;
            break;

            default:
                $aviso='';
                if ($this->padreAgrupador() && $this->esPublica()) {
                    $inline = array(
                            "style" => "text-align: center",
                            "img" => array( "src" => RESOURCES_DOMAIN."/img/famfam/world.png",
                                            "title" => $tpl->getString("mensaje_carpeta_publica")
                                        ),
                            array(
                                "tagName" => "span",
                                "nombre" => $tpl->getString("carpeta_publica")
                                )
                            );
                };

                $inlineArray[] = $inline;

                return $inlineArray;
            break;
        }

        return $inlineArray;
    }


    public function obtenerCarpetasMasFicheros($recursive = false, $level = 0, usuario $usuario = null)
    {
        $carpetas = $this->obtenerCarpetas($recursive, $level, $usuario);
        $ficheros = $this->obtenerFicheros();

        return $carpetas->merge($ficheros);
    }


    /** RETORNA UN ARRAY DE OBJETOS CARPETA **/
    public function obtenerCarpetas($recursive = false, $level = 0, Iusuario $usuario = null)
    {
        @list($recursive,$level,$usuario) = order_parameters();
        //$usuario = usuario::getCurrent();
        //$condicion = false;
        //$condicion = ' uid_carpeta_superior NOT IN ( SELECT uid_carpeta FROM carpeta_usuario WHERE uid_usuario = '.$usuario->getUID().' ) ';
        $coleccionCarpetas = array();

        $relacionados = $this->obtenerRelacionados($this->tabla."_carpeta", "uid_carpeta_superior", "uid_carpeta_inferior");
        if (is_array($relacionados) && count($relacionados)) {
            foreach ($relacionados as $informacionRelacion) {
                $carpeta = new carpeta($informacionRelacion["uid_carpeta_inferior"]);
                if ($recursive) {
                    $subCarpetas = $carpeta->obtenerCarpetas(true, ($level+1), $usuario);
                    $coleccionCarpetas =$subCarpetas->merge($coleccionCarpetas);
                }
                $coleccionCarpetas[] = $carpeta;
            }
        }
        $coleccionCarpetas = carpeta::filtrarNoVisibles($coleccionCarpetas, $usuario);
        return new ArrayObjectList($coleccionCarpetas);
    }


    public function getUserVisibleName()
    {
        $info = $this->getInfo();
        return $info["nombre"];
    }

    public static function descargarZip($elementos)
    {
        //nombre temporal del fichero
        $tempName = "/tmp/".time().".zip";

        $files = array();
        foreach ($elementos as $carpeta) {
            $filesInFolder = carpeta::getAllFiles($carpeta);
            $files = array_merge($files, $filesInFolder);
        }

        $zip = null;
        foreach ($files as $file) {
            $version = $file->getLastVersion();

            if (false === $version) {
                continue;
            }

            //$fileData = is_readable($version->realpath) ? file_get_contents($version->realpath) : archivo::leer($version->realpath);
            if ($fileData = archivo::leer($version->realpath)) {
                if (null === $zip) {
                    $zip = archivo::getZipInstance($tempName);
                }

                $ext = archivo::getExtension($version->realpath);
                $zip->addFromString(archivo::cleanFilenameString($file->getUserVisibleName()).".$ext", $fileData);
            }
        }

        if ($zip) {
            $zip->close();
            unset($zip);
        }

        if (is_readable($tempName)) {
            archivo::descargar($tempName, "ficheros");
        } else {
            die(self::ERROR_JAVASCRIPT);
        }
    }

    public static function getAllFiles(carpeta $carpeta, $usuario = null)
    {
        $ficheros = $carpeta->obtenerFicheros();
        $carpetas = $carpeta->obtenerCarpetas(false, false, $usuario);
        if (is_array($carpetas) && count($carpetas)) {
            foreach ($carpetas as $subcarpeta) {
                $ficheros = array_merge($ficheros, carpeta::getAllFiles($subcarpeta, $usuario));
            }
        }
        $ficheros = array_unique($ficheros, SORT_REGULAR);
        return $ficheros;
    }

    public static function renovarIndexado($todasCarpetas = true)
    {
        $db = db::singleton();

        $sql = "SELECT uid_carpeta FROM ". TABLE_CARPETA ."
            WHERE datediff( now(), if(fecha_indexado>0, FROM_UNIXTIME(fecha_indexado), 0) ) > 60
        ";

        $desfasada = $db->query($sql, "*", 0, 'carpeta');

        if (!empty($desfasada)) {
            foreach ($desfasada as $carpeta) {
                $carpeta->indexar();
            }
        }

        return false;
    }

    public function indexar()
    {
        $db = db::singleton();
        $listaSubcarpetas = array();
        if ($this->padreAgrupador()) {
            if ($this->esPublica()) {
                $subcarpetas = $this->obtenerCarpetas(true, 0);
                if (count($subcarpetas)) {
                    foreach ($subcarpetas as $subcarpeta) {
                        $listaSubcarpetas[] = $subcarpeta->getUID();
                        $sqlinsert =    "UPDATE $this->tabla SET `fecha_indexado` = ".time()."
                            WHERE uid_carpeta = ".$subcarpeta->getUID();
                        $indexado = $db->query($sqlinsert);
                    }
                }

                $stringSubcarpetas = implode(',', $listaSubcarpetas);

                $sqlinsert =    "UPDATE $this->tabla SET `fecha_indexado` = ".time().", `subcarpetas` = '".$stringSubcarpetas."'
                            WHERE uid_carpeta = ".$this->getUID();
                $indexado = $db->query($sqlinsert);
            } else {
                // No es publica y de momento no contemplamos indexarla
            }
        } else {
            //Añadir esta subcarpeta al campo subcarpetas de su raiz
            $carpetaRaiz = $this->obtenerCarpetaRaiz();
            if ($carpetaRaiz->padreAgrupador()) {
                $carpetaRaiz->indexar();
            } else {
                return false;
            }
        }
        return true;
    }

    public function desindexar()
    {
        $db = db::singleton();
        if ($this->padreAgrupador()) {
            $raiz = $this;
        } else {
            $raiz = $this->obtenerCarpetaRaiz();
            if (!$raiz->padreAgrupador()) {
                return false;
            }
        }

        $sql =  "SELECT subcarpetas FROM $this->tabla WHERE uid_carpeta = $raiz->uid";

        $subcarpetasRaiz = $db->query($sql, 0, 0);

        $arraysubcarpetas = explode(',', $subcarpetasRaiz);

        $key = array_search($this->getUID(), $arraysubcarpetas);

        if ($key || $key === 0) {
            unset($arraysubcarpetas[$key]);
            $subcarpetasRaiz = implode(',', $arraysubcarpetas);
            $sqlinsert =    "UPDATE $this->tabla SET `fecha_indexado` = ".time().", `subcarpetas` = '".$subcarpetasRaiz."'
                            WHERE uid_carpeta = ".$raiz->getUID();
            $indexado = $db->query($sqlinsert);
        }

        return true;
    }

    public function obtenerVisibilidad()
    {
        $sql = "SELECT uid_usuario FROM $this->tabla"."_usuario WHERE uid_carpeta = $this->uid";

        $datos = $this->db->query($sql, "*", 0);
        $coleccionUsuarios = array();
        foreach ($datos as $uidUsuario) {
            $coleccionUsuarios[] = new usuario($uidUsuario);
        }

        return $coleccionUsuarios;
    }

    public function obtenerEmpresaReferencia()
    {
        $carpetaSuperior = $this->obtenerCarpetaSuperior();
        $carpetaSuperior =  $carpetaSuperior ? $carpetaSuperior : $this;
        $sql = "SELECT uid_empresa_referencia FROM $this->tabla"."_solicitable WHERE uid_carpeta = ". $carpetaSuperior->getUID() ;
        if ($uid_empresa_referencia = $this->db->query($sql, 0, 0)) {
            return new empresa($uid_empresa_referencia);
        } else {
            return false;
        }
    }

    public function actualizarVisibilidad()
    {
        return $this->actualizarTablaRelacional($this->tabla ."_usuario", "usuario", true);
    }

    /** NOS INDICA LOS OBJETOS SUPERIRORES DEL ACTUAL EN EL QUE ESTAN CONTENIDOS **/
    public static function getSubModules()
    {
        $modulos = array(util::getModuleId("fichero"));
        return $modulos;
    }

    /** NOS INDICA LOS OBJETOS INFERIORES DEL ACTUAL EN EL QUE ESTAN CONTENIDOS
    **/
    public static function getSupModules()
    {
        $modulos = array( util::getModuleId("agrupador") => "obtenerAgrupadorContenedor" );
        return $modulos;
    }

    public static function filtrarNoVisibles($folderSet, Iusuario $usuario = null)
    {
        if (count($folderSet)) {
            $folderSet          = new ArrayObjectList($folderSet);
            $notVisibleFolders  = new ArrayObjectList;
            foreach ($folderSet as $i => $carpeta) {
                $sinVisibilidad = $carpeta->obtenerVisibilidad();
                foreach ($sinVisibilidad as $usuarioQueNoVe) {
                    if ($usuario instanceof usuario && $usuarioQueNoVe instanceof usuario && $usuarioQueNoVe->getUID() == $usuario->getUID()) {
                        $notVisibleFolders[] = $folderSet[$i];
                    }
                }
            }
            $visibles =  $folderSet->diff($notVisibleFolders);
            return $visibles->getArrayCopy();
        }

        return $folderSet;
    }

    public static function cronCall($time, $force = false, $items = null)
    {
        $m = date("i", $time);
        $h = date("H", $time);
        $w = date("w", $time);

        if (($h == 01 && $m == 05) || $force) {
            $updates = self::renovarIndexado();
        }

        return true;
    }

    public static function optionsFilter($uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null)
    {
        if ($uidelemento && $uidmodulo && $user) {
            $modulo = util::getModuleName($uidmodulo);
            $elemento = new $modulo($uidelemento);
            $agrupador = $elemento->obtenerAgrupadorContenedor();
            if ($agrupador instanceof agrupador) {
                $empresa = $agrupador->getCompany();
                if ($empresa instanceof empresa) {
                    $empresas = $empresa->getStartIntList();
                } else {
                    return false;
                }
            } else {
                return false;
            }
            if (!$empresas->contains($user->getCompany()->getUID())) {
                return ' AND 0 ';
            }
        }

        return false;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $arrayCampos = new FieldList();
        switch ($modo) {
            case elemento::PUBLIFIELDS_MODE_EDIT:
                $arrayCampos["nombre"] = new FormField(array("tag" => "input",  "type" => "text", "blank" => false));

                if ($objeto instanceof self && $objeto->padreAgrupador()) {
                    $tpl = Plantilla::singleton();
                    $arrayCampos["es_publica"] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox", "innerHTML" => $tpl->getString("carpeta_publica"), "info" => true));
                }

                break;
            case elemento::PUBLIFIELDS_MODE_NEW:
                if ($objeto instanceof agrupador && $objeto->getModuleName() == 'agrupador') {
                    $tpl = Plantilla::singleton();
                    $arrayCampos["nombre"] = new FormField(array("tag" => "input",  "type" => "text", "blank" => false ));
                    $arrayCampos["es_publica"] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox", "innerHTML" => $tpl->getString("carpeta_publica"), "info" => true));
                } else {
                    $arrayCampos["nombre"] = new FormField(array("tag" => "input",  "type" => "text", "blank" => false ));
                }
                break;
            default:
                $arrayCampos["nombre"] = new FormField(array("tag" => "input",  "type" => "text", "blank" => false ));
                break;
        }
        return $arrayCampos;
    }
}
