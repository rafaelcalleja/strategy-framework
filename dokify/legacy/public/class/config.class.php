<?php
    class config{

        public static function getAllAvailableStylesheets(){
            $arrayStyles = array();

            $currentStyleObject = new StdClass();

            $currentStyleObject->name = "normal";
            $currentStyleObject->ruta = "";
            $currentStyleObject->href = "style.css.php";

            $arrayStyles[] = $currentStyleObject;

            foreach( glob(DIR_CSS."cliente/*.css.php") as $file){
                $currentStyleObject = new StdClass();

                $currentStyleObject->name = str_replace(".css.php","",basename($file));
                $currentStyleObject->ruta = str_replace(".php","",basename($file));
                $currentStyleObject->href = "style.css.php?s=". $currentStyleObject->ruta;

                $arrayStyles[] = $currentStyleObject;
            }
            return $arrayStyles;
        }

        public static function obtenerComentariosAnulacion(){
            $db = db::singleton();
            $coleccion = array();
            $sql = "SELECT uid_comentario_anulacion FROM ". TABLE_COMENTARIO_ANULACION . " WHERE 1";
            $list = $db->query($sql, "*", 0);
            foreach($list as $uid ){
                $coleccion[] = new comentario_anulacion($uid);
            }
            return $coleccion;
        }

        public static function getCountOf($tblName){
            $database = db::singleton();
            $sql = "SELECT count(*) FROM $tblName";
            return $database->query($sql, 0, 0);
        }

        /** ARRAY DE TODOS LOS USUARIOS DE LA APLICACION */
        public static function obtenerArrayUsuarios($start=false,$length=false){
            $database = db::singleton();
            $sql = "SELECT uid_usuario, usuario FROM ".TABLE_USUARIO;

            if( isset($start) && $length && is_numeric($start) && is_numeric($length) ){
                $sql .= " LIMIT $start, $length";
            }


            $info = $database->query( $sql, true );
            if( !is_array($info) ){ return false; }

            return array_map("utf8_multiple_encode",$info );
        }


        /**
                 * DEVOLVER EN FORMA DE ARRAY DE DATOS TODOS LOS TIPOS DE DOCUMENTOS CREADOS EN LA APLICACION
                 * incides - array( uid_documento, nombre, flags )
        */
        public static function obtenerArrayDocumentos($limit = false, $filter = false, $onlyPublic = true)
        {
            $database = db::singleton();

            $sql = "SELECT uid_documento, nombre, flags FROM ". DB_DOCS . ".documento";
            $where = " WHERE 1 ";

            if ($onlyPublic) {
                $where .= " AND is_public = 1 ";
            }

            if (is_array($filter)) {
                if ($filter["uid_empresa"]) {
                    $sql .= " INNER JOIN " . TABLE_DOCUMENTO_ATRIBUTO
                        . " USING(uid_documento) " . $where
                        . " AND uid_empresa_propietaria = "
                        . $filter["uid_empresa"] ." GROUP BY uid_documento";
                    $where = "";
                }
            }

            $sql .= $where . " ORDER BY nombre";

            if (is_array($limit) ) {
                $sql .= " LIMIT " . reset($limit) . ", " . end($limit);
            }

            $info = $database->query($sql, true);
            return array_map("utf8_multiple_encode", $info);
        }

        public static function obtenerConteoDocumentos($filter){
            $database = db::singleton();
            $sql = "SELECT count(distinct uid_documento) FROM ". DB_DOCS . ".documento";
            if( is_array($filter) ){
                if( $filter["uid_empresa"] ){
                    $sql .=  " INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." USING(uid_documento) WHERE uid_empresa_propietaria = ". $filter["uid_empresa"];
                }
            }
            //$sql .= " ORDER BY nombre";
            return $database->query( $sql, 0, 0 );
        }


        /** RETORNA UNA COLECCION DE OBJETOS DE TIPO AGRUPAMIENTO
                Se le puede indicar $idModulo (string||int) para filtrar los asignados a un modulo en concreto
                Segundo parametro indica un elemento por el cual podemos filtrar
        */
        public static function obtenerAgrupamientos($idModulo=false, $elementoFiltro=false, $start=false,$length=false, $elemento=false, $config=false, $uidCategoria=false){
            $db = db::singleton();

            $sql = "SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ." WHERE 1 AND nombre != ''";

            $filters = array();

            if( is_numeric($uidCategoria) ){
                $filters[] = " uid_categoria = {$uidCategoria}";
            }

            if( $idModulo ){
                if( !is_numeric($idModulo) ){
                    $idModulo = elemento::obtenerIdModulo($idModulo);
                }
                $filters["modulo"] = "uid_agrupamiento IN ( SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ."_modulo WHERE uid_modulo = $idModulo)";
            }

            if( $elemento instanceof usuario ){
                $list = $elemento->getCompany()->getStartIntList();
                $filters[] = "uid_empresa IN ({$list->toComaList()})";
            }

            if( $elementoFiltro instanceof usuario ){
                if( $elemento instanceof elemento ){
                    if( !$elemento instanceof usuario ){
                        $empresas = $elemento->obtenerEmpresasSolicitantes($elementoFiltro);
                        $ids = elemento::getCollectionIds($empresas);
                        $filters[] = "uid_agrupamiento IN ( SELECT uid_agrupamiento FROM ". TABLE_CLIENTE ."_agrupamiento WHERE uid_empresa IN (". implode(",",$ids) ."))";
                    }
                }
                //FILTER BY LABELS
                if($elementoFiltro->isViewFilterByLabel()){
                    $etiquetasUsuario = $elementoFiltro->obtenerEtiquetas()->toIntList()->getArrayCopy();

                    if( is_array($etiquetasUsuario) && count($etiquetasUsuario) ){
                        $filters[] = "uid_agrupamiento IN (
                                    SELECT uid_agrupamiento FROM ".TABLE_AGRUPAMIENTO."_etiqueta WHERE uid_etiqueta IN (".implode(",",$etiquetasUsuario).")
                                )
                                ";
                    } else{
                        $filters[] = "uid_agrupamiento NOT IN (
                                    SELECT uid_agrupamiento FROM ".TABLE_AGRUPAMIENTO."_etiqueta WHERE uid_agrupamiento = uid_agrupamiento
                                )";
                    }
                }
                //FIN FILTER BY LABELS
            }

            if( isset($empresaCliente) && $empresaCliente instanceof empresa ){
                $filters[] = " agrupamiento.uid_empresa = ". $empresaCliente->getUID();
            }


            if( $elementoFiltro instanceof empresa ){
                $filters[] = " uid_agrupamiento IN ( SELECT uid_agrupamiento FROM ". TABLE_EMPRESA ."_agrupamiento WHERE uid_empresa = ". $elementoFiltro->getUID().")
                                OR
                                uid_agrupamiento IN ( SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ." WHERE uid_empresa = ". $elementoFiltro->getUID().")";
            }

            if( is_string( $config ) ){
                $filters[] = " config_$config = 1";
            }

            if( count($filters) ){
                $lastSQL =  $sql . " AND " . implode(" AND ", $filters);
            } else {
                $lastSQL =  $sql;
            }

            $lastSQL .= " ORDER BY nombre";

            if( isset($start) && $length && is_numeric($start) && is_numeric($length) ){
                $lastSQL .= " LIMIT $start, $length";
            }

            $data = $db->query($lastSQL, "*", 0, "agrupamiento");
            $coleccionAgrupamientos = new ArrayObjectList( $data );


            // Mostrar todos si es STAFF y son empleados o maquinas
            if( $elementoFiltro instanceof usuario && $elementoFiltro->esStaff() && ( $elemento instanceof empleado || $elemento instanceof maquina ) ){
                $list = $coleccionAgrupamientos->toIntList()->getArrayCopy();

                unset($filters["modulo"]);
                $showSQL = $sql . " AND " . implode(" AND ", $filters);


                $coleccionAgrupamientos = new ArrayObjectList( $db->query($showSQL, "*", 0, "agrupamiento") );

                foreach( $coleccionAgrupamientos as &$agrupamiento ){
                    if( !in_array($agrupamiento->getUID(), $list) ){
                        $agrupamiento->hidden = true;
                    }
                }
            }

            return $coleccionAgrupamientos;


            /*
            if( is_array($datos) && count($datos) ){
                $coleccionAgrupamientos = array();
                foreach($datos as $datosAgrupamiento){
                    $coleccionAgrupamientos[] = new agrupamiento($datosAgrupamiento["uid_agrupamiento"]);
                }



                return $coleccionAgrupamientos;
            }
            /*
            $datos = utf8_multiple_encode($datos);
            return $datos;
            */
        }


        /** RETORNA UNA COLECCION DE OBJETOS DE TIPO AGRUPADOR
            */
        public static function obtenerAgrupadores(){
            $db = db::singleton();
            //buscamos el id_modulo del AGRUPAMIENTO
            $sql = "SELECT uid_agrupador, nombre, 'agrupador' as tipo, ( SELECT uid_modulo FROM ". TABLE_MODULOS ." WHERE nombre = 'Agrupador' ) uid_modulo FROM ". TABLE_AGRUPADOR ." WHERE 1";
            $datos = $db->query($sql,true);
            $agrupadores = array();
            foreach($datos as $dato){
                $agrupadores[] = new agrupador($dato["uid_agrupador"]);
            }

            return $agrupadores;
        }

        /** RETORNA UNA COLECCION DE OBJETOS agrupamiento y modulo CREADO ASI EL CONJUNTO DE TIPOS DE SOLICITANTES
                Se utiliza en solicitud de documentos
        */
        public static function obtenerAgrupamientosGlobales($idModulo=false,$usuario=false, $blacklist=array()){
            $db = db::singleton();

            $sql = "SELECT uid_modulo, nombre, nombre as tipo FROM ". TABLE_MODULOS ." WHERE solicitante";
            if( count($blacklist) ){ $sql .= " AND uid_modulo NOT IN (". implode(",", $blacklist) .")"; }
            $datosModulos = $db->query($sql, true );

            $modulos = array();
            foreach($datosModulos as $modulo){
                $modulos[] = new modulo( $modulo["uid_modulo"],  $modulo["tipo"] );
            }

            $datosAgrupadores = config::obtenerAgrupamientos($idModulo, $usuario->getCompany() );

            if( !count($datosAgrupadores) ){ return $modulos; }

            $datos = array_merge_recursive( $modulos, $datosAgrupadores->getArrayCopy() );
            return $datos;
        }

        /** RETORNAR UN ARRAY DE OBJETOS modulo y agrupador
                Se puede indicar $idModulo (int||string) para filtrar
                Como segundo parametro pasar el usuario actual
            */
        public static function obtenerSolicitantesGlobales($idModulo=false,$usuario=false, $blacklist=array() ){
            $db = db::singleton();

            $sql = "SELECT uid_modulo, nombre, nombre as tipo FROM ". TABLE_MODULOS ." WHERE solicitante";
            if( count($blacklist) ){ $sql .= " AND uid_modulo NOT IN (". implode(",", $blacklist) .")"; }
            $datosModulos = $db->query($sql, true );


            $modulos = array();
            foreach($datosModulos as $modulo){
                $modulos[] = new modulo( $modulo["uid_modulo"],  $modulo["tipo"] );
            }

            $datosAgrupadores = config::obtenerAgrupadores($idModulo, $usuario);
            //dump($datosAgrupadores);
            $datos = array_merge_recursive( $modulos, $datosAgrupadores );
            return $datos;
        }


        /** OBTENER EL METODO PARA HACER ACCIONES COMUNES
                $modulo - El modulo donde se buscara
                $accion - El nombre de la accion a buscar
            *

        public static function obtenerMetodo($modulo,$accion){
            trace(); exit;
            switch( strtolower($modulo) ){
                case "empresa":
                    switch($accion){
                        case "enviar_papelera": return "dejarDeSerInferiorDe"; break;
                        case "restaurar_papelera": return "restaurarComoInferiorDe"; break;
                        case "listar_papelera": return "obtenerEmpresasInferiores"; break;
                    }
                break;
                case "usuario":
                    switch($accion){
                        case "enviar_papelera": return "bloquearPerfil"; break;
                        case "restaurar_papelera": return "desbloquearPerfil"; break;
                        case "listar_papelera": return "obtenerUsuarios"; break;
                    }
                break;
                case "empleado":
                    switch($accion){
                        case "enviar_papelera": return "desasignarTemporalmenteEmpresa"; break;
                        case "restaurar_papelera": return "reasignarEmpresa"; break;
                        case "listar_papelera": return "obtenerEmpleados"; break;
                    }
                break;
                case "maquina":
                    switch($accion){
                        case "enviar_papelera": return "desasignarTemporalmenteEmpresa"; break;
                        case "restaurar_papelera": return "reasignarEmpresa"; break;
                        case "listar_papelera": return "obtenerMaquinas"; break;
                    }
                break;
                case "perfil":
                    switch($accion){
                        case "enviar_papelera": return "bloquearPerfil"; break;
                        case "listar_papelera": return "obtenerPerfiles"; break;
                        case "restaurar_papelera": return "desbloquearPerfil"; break;
                    }
                break;
            }
        }*/


        /**
        * CONSEGUIR UN ARRAY DE LAS OPCIONES PARA CADA CASO
        * @param integer $uidelemento uid del elemento cuyas opciones queremos obtener
        * @param integer|string $uidmodulo nombre o uid del modulo al que pertenece el elemento
        * @param usuario|empleado $user
        * @param bool $publicMode
        * @param integer $config 0 o 1, falso booleano para introducir directamente en una query sql
        * @param integer $tipo 0 o 1, falso booleano para introducir directamente en una query sql
        * @param bool $groups
        * @param bool|string $ref false o cadena para la busqueda por el campo 'referencia' en la bd
        * @param bool|elemento $parent
        * @return
        */
        public static function obtenerOpciones($uidelemento, $uidmodulo, Iusuario $user, $publicMode = false, $config = 0, $tipo = 1, $groups=true, $ref=false, $parent=false, $extraData = null){

            if (!is_numeric($uidmodulo)) $uidmodulo = (int) util::getModuleId($uidmodulo);
            if (!is_numeric($uidmodulo)) throw new Exception("module not found");


            // Cachear resultados
            // $cache = cache::singleton();
            // $cacheKey = implode('-', array(__CLASS__, __FUNCTION__, $uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $groups, $ref, $parent));
            // if (($value = $cache->getData($cacheKey)) !== NULL) return json_decode($value, true);

            $moduleName = util::getModuleName($uidmodulo);
            $db         = db::singleton();
            $campos     = [];
            $profile    = $user->perfilActivo();


            if ($publicMode) {
                $nameField = "innerHTML"; // Para usar luego en temas de traduccion...

                $concatenar = "if(INSTR(href,'busqueda/nueva')=0,if(INSTR(href,'buscar.php')=0,if(INSTR(href,'?')=0,'?poid=','&poid='),''),'')";
                $campos[] = "alias as name";
                $campos[] = "uid_accion";
                $campos[] = "alias as innerHTML";
                $campos[] = "concat('".RESOURCES_DOMAIN."',icono,'?".VKEY."') as img";
                $campos[] = "tipo";
                $campos[] = "shortcut";
                $campos[] = "expected";
                if (is_numeric($uidelemento)) {
                    $campos[] = "concat(href,$concatenar,'$uidelemento') as href";
                } else {
                    $campos[] = "href";
                }
            } else {
                $nameField = "alias"; // Para usar luego en temas de traduccion...
                $campos[] = "uid_accion";
                $campos[] = "alias";
                $campos[] = "concat('".RESOURCES_DOMAIN."',icono) as icono";
                $campos[] = "href";
                $campos[] = "tipo";
                $campos[] = "expected";
            }

            $campos[] = "class";
            // if (!is_ie() && !is_webkit()) {
            //  $campos[] = "grupo";
            //  $campos[] = "if( grupo, (
            //      SELECT string FROM ". TABLE_MODULOS ."_accion_grupo mag
            //      WHERE mag.uid_modulo_accion_grupo = a.grupo
            //  ), '') as grupo_string ";
            // }


            $fields = implode(", ", $campos);

            if ($user instanceof usuario) {


                $sql = "SELECT $fields
                    FROM ". TABLE_ACCIONES ."
                    INNER JOIN ". TABLE_MODULOS ."_accion a USING (uid_accion)
                    INNER JOIN ". TABLE_PERFIL ."_accion USING (uid_modulo_accion)
                    WHERE 1
                    AND uid_modulo  = {$uidmodulo}
                    AND uid_perfil  = {$profile->getUID()}
                    AND tipo        = {$tipo}
                    AND activo      = 1
                    AND (
                            (
                                a.uid_modulo_referencia IN (
                                    SELECT uid_modulo
                                    FROM ". TABLE_MODULOS ."_accion ma
                                    INNER JOIN ". TABLE_PERFIL ."_accion pa USING (uid_modulo_accion)
                                    WHERE 1
                                    AND ma.uid_modulo = a.uid_modulo_referencia
                                    AND ma.uid_accion = 21
                                    AND pa.uid_perfil = {$profile->getUID()}
                                )
                                OR a.uid_modulo_referencia = 0
                            )
                    )
                    AND a.activo = 1
                ";
            }

            if ($user instanceof empleado || $user->esStaff()) {
                $sql = "SELECT $fields
                    FROM ". TABLE_ACCIONES ."
                    INNER JOIN ". TABLE_MODULOS ."_accion a USING( uid_accion )
                    WHERE 1
                    AND uid_modulo  = {$uidmodulo}
                    AND tipo        = $tipo
                    AND activo      = 1
                ";

                // Si el usuario es agente tiene capacidad total de visualización, pero no de gestion
                if ($user instanceof usuario && $user->isAgent()) {
                    $blacklist = $user->getAgentActionsBlackList();
                    $sql .= " AND uid_accion NOT IN (". implode(", ", $blacklist) .")";
                }
            }

            // opciones de configuracion
            if ($config === 0 || $config === 1) {
                $sql .= " AND config = $config";
            }


            // Si el modulo en el que estamos es de documento de elementos (maquina, empleado, empresa )...
            if ($uidmodulo == 3 || $uidmodulo == 9 || $uidmodulo == 15) {
                $moduleName = "documento";
            }


            // custom item filters
            $func = "{$moduleName}::optionsFilter";
            if (is_callable($func)) {
                $filter = call_user_func ($func, $uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData);
                if ($filter !== false) {
                    $sql .= " $filter";
                }
            }

            // ref filters
            if ($ref) {
                $sql .= " AND referencia = '$ref'";
            } else {
                $sql .= " AND referencia = ''";
            }



            if (is_numeric($uidelemento)) {
                $objeto = new $moduleName ($uidelemento);
            } elseif (is_object($parent)) {
                $objeto = $parent;
            }


            if ($user instanceof empleado) {
                if ($rol = $user->getRol()) {
                    $sql .= " AND uid_modulo_accion IN (
                        SELECT l.uid_modulo_accion FROM ". TABLE_ROL ."_accion as l WHERE l.uid_rol = {$rol->getUID()}
                    )";
                }
            }


            // modules wich accept custom permissions: groups, folders and files
            $permissionsWhiteList = [11, 25, 26];

            // Vamos con los permisos especificos si hay objeto
            if (in_array($uidmodulo, $permissionsWhiteList) && $user instanceof usuario && isset($objeto) && is_object($objeto) && is_callable(array($objeto,"getModuleName"))) {
                $objectModule   = $objeto->getModuleName();

                // por defecto no hay que filtrar si o si.. - Solo si algun objeto superior tiene algo y el actual no
                $forcefilter    = false;

                // Almacenamos todos los uid_modulo_accion aqui
                $list           = array();

                // Vamos por los permisos heredados..
                $supmodulos     = $objectModule::getSupModules();

                if ($supmodulos) {
                    foreach ($supmodulos as $uidmodulosup => $method) {
                        $objetoSup = $objeto->$method();
                        if (!$objetoSup) continue; // jose ... parece que no debería llegar aqui..

                        $moduloSup = $objetoSup->getModuleName();
                        if ($especificos = $profile->obtenerOpcionesObjeto($objetoSup, true)) {
                            foreach($especificos as $group => $actions) {
                                if (is_array($actions) && count($actions)) $forcefilter = true; // indica que el objeto superior tiene algo, deberemos filtrar si o si
                            }

                            $sublist    = array_keys($especificos[$moduleName]);
                            $list       = array_merge($list, $sublist);
                        }
                    }
                }

                $especificos = $profile->obtenerOpcionesObjeto($objeto, true);
                if (is_array($especificos)) {

                    if (is_object($parent)) {
                        $module = $parent->getModuleName();
                        if (is_array($especificos[$module]) && count($especificos[$module])) {
                            $forcefilter = true;
                        }
                    }

                    if (isset($especificos[$moduleName]) && is_array($especificos[$moduleName]) && count($especificos[$moduleName])) {
                        $curlist    = array_keys($especificos[$moduleName]);
                        $list       = array_merge($list, $curlist);
                    }
                }


                // si tenemos que filtrar algun accion
                if (is_array($list) && count($list)) {
                    $list = array_unique($list);
                    $sql .= " AND uid_modulo_accion IN ( ". implode(",", $list) ." ) ";

                // si hay que filtrar si o si, y no hay ningun accion para el objeto concreto
                } elseif ($forcefilter === true) {
                    return array();
                }
            }




            $sql    .= " GROUP BY uid_accion ORDER BY config, prioridad, alias";
            $datos   = $db->query($sql, true);
            if (!is_array($datos)) return [];


            $options    = [];
            $grupos     = [];
            $lang       = Plantilla::singleton();

            // Debemos recorrer los datos para la traduccion...
            foreach( $datos as $i => $dato ){
                if (isset($dato["name"])) $dato["name"] = utf8_encode($dato["name"]);

                if( $groups && (isset($dato["grupo"]) && $groupID = $dato["grupo"]) ){
                    if( isset($grupos[$groupID]) ){
                        $currentName = str_replace(" ","_", strtolower($dato[$nameField]));
                        $dato["innerHTML"] = $lang("opt_".$currentName);

                        $grupos[ $groupID ]["options"][] = $dato;
                        unset($datos[$i]);
                    } else {
                        // Group entry
                        $langString = "opt_". $dato["grupo_string"];
                        $grupo = array(
                            "innerHTML" => $lang($langString),
                            "img" => "",
                            "options" => array($dato)
                        );

                        // Asignamos la accion
                        $currentName = str_replace(" ","_", strtolower($dato[$nameField]));
                        $grupo["options"][0]["innerHTML"] = $lang("opt_".$currentName);
                        $options[] = $grupo;

                        // Lo guardamos por si hay mas opciones de este grupo
                        $grupos[ $groupID ] =& $options[count($options)-1];
                    }


                } else {
                    $currentName = str_replace(" ","_", strtolower($dato[$nameField]));
                    $langString = "opt_$currentName";

                    $transString = $lang($langString);

                    if( $transString != $langString ){
                        $dato[$nameField] = $transString;
                    } else {
                        $dato[$nameField] = $dato[$nameField];
                    }

                    $options[] = $dato;
                }
            }

            return $options;
        }




        /** NOS DEVOLVERÁ TODOS LOS ELEMENTOS "CAMPO" CREADOS EN EL SISTEMA */
        public static function obtenerCamposDinamicos(){
            $db = db::singleton();
            $sql = "SELECT uid_campo, nombre, uid_modulo FROM ". TABLE_CAMPO;

            $datosCampos = $db->query($sql, true);
            if( is_array($datosCampos) && count($datosCampos) ){
                $campos = array();
                foreach($datosCampos as $datosCampo){
                    $campos[] = new campo($datosCampo["uid_campo"]);
                }
                return $campos;
            }
        }

        /** CON UN idModulo DADO, NOS DEVUELVE UN ARRAY DE QUIEN PIDE DOCUMENTACION */
        public static function modulosSolicitantes( $uidModulo ){
            $db = db::singleton();
            $sql = "
            SELECT
                uid_modulo_solicitante as uid_modulo,
                metodo,
                (SELECT nombre FROM ". TABLE_MODULOS ." WHERE uid_modulo = uid_modulo_solicitante ) as nombre
            FROM
                solicitante
            WHERE
                uid_modulo_solicitado = $uidModulo";
            $datos = $db->query($sql, true);
            return $datos;
        }



        /*
         * BORRAR UN ELEMENTO DE UNA TABLA A TRAVES DE SU ID UNICO
         */
        public static function eliminarElemento( $uidelemento, $tabla ){
            $db = db::singleton();
            $nombretabla = explode(".",$tabla);
            $nombretabla = end($nombretabla);

            $sql = "DELETE FROM $tabla WHERE uid_$nombretabla = $uidelemento";
            $resultset = $db->query( $sql );
            if( $resultset ){
                return true;
            } else {
                return $db->lastErrorString();
            }
        }


        /** NOS RETORNA UN ARRAY CON TODOS LOS OBJETOS CLIENTES EXISTENTES
        public static function obtenerRoles(){
            $db = db::singleton();
            $sql = "SELECT uid_rol FROM ". TABLE_ROL ." WHERE 1";

            $datos = $db->query($sql, true);

            if( is_array($datos) && count($datos) ){
                $roles = array();
                foreach($datos as $linea){
                    $roles[] = new rol($linea["uid_rol"]);
                }
                return $roles;
            }
        }
        */

    }
?>
