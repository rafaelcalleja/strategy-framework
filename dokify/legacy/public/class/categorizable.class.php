<?php
use \Dokify\Assignment;

abstract class categorizable extends etiquetable implements Icategorizable
{

    public static function getModules () {
        return [
            1   => 'empresa',
            8   => 'empleado',
            14  => 'maquina'
        ];
    }

    /***
       *
       *
       *
       */
    public static function onSearchByAsignado ($data, $uids, $param, $query) {

        if (isset($data['usuario']) && $usuario = $data['usuario']) {
            $class      = get_called_class();
            $table      = constant('TABLE_' . strtoupper($class));
            $company    = $usuario->getCompany();
            $uidmodulo  = util::getModuleId($class);
            $startList  = $company->getStartIntList();
            $corp       = $company->perteneceCorporacion();

            $conditions = array();

            foreach ($uids as $uid) {
                $uid = db::scape($uid);

                if (!is_numeric($uid)) {
                    return "(0)";
                }

                $SQLOwner = "SELECT uid_empresa FROM ". TABLE_AGRUPADOR . " WHERE uid_agrupador = {$uid}";
                $SQLasignados = "SELECT uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento WHERE uid_modulo = {$uidmodulo} AND uid_elemento = {$class}.uid_{$class} AND uid_agrupador = {$uid}";

                $owner = db::get($SQLOwner, 0, 0);

                if (!$owner) {
                    return "(0)";
                }

                $ownerCompany = new empresa($owner);
                $ownerStartList = $ownerCompany->getStartIntList();


                $SQL   = "uid_{$class} IN ($SQLasignados)";

                $isOwn = $startList->contains($owner);

                if ($isOwn === false && $corp instanceof empresa) {
                    $groupTable      = TABLE_AGRUPADOR;
                    $companyOrgTable = TABLE_EMPRESA.'_agrupamiento';
                    $companyUid      = $company->getUID();

                    $sqlIsInheritOwner = "
                        SELECT COUNT(a.uid_agrupador)
                        FROM {$groupTable} a
                        JOIN {$companyOrgTable} ea USING (uid_agrupamiento)
                        WHERE a.uid_agrupador = {$uid}
                        AND ea.uid_empresa = {$companyUid}
                    ";

                    $isInheritOwner = db::get($sqlIsInheritOwner, 0, 0);

                    if ($isInheritOwner) {
                        $isOwn = true;
                    }
                }

                // --- si el agrupador no es nuestro, se complica al tener que mirar cadenas de contratacion
                $allowedChains = ['empleado', 'empresa', 'maquina'];
                if ($isOwn === false && true === in_array($class, $allowedChains)) {
                    if ($corp instanceof empresa && $ownerCompany != $corp) {
                        $asignedByMe = "SELECT uid_{$class} FROM {$table}_jerarquia
                                    WHERE uid_{$class} = {$class}.uid_{$class}
                                    AND n1 IN ({$ownerStartList})
                                    AND (n2 IN ($startList) OR n3 IN ($startList))
                                    ";

                        $SQL .= " AND uid_{$class} IN ({$asignedByMe})";

                    }

                    if ($class == "empleado" || $class == "maquina") {
                        // if it is not mine and it is an employee or a machine, we check the visibility.
                        $table = constant("TABLE_". strtoupper($class));
                        $SQL .= " AND uid_{$class} IN (SELECT v.uid_{$class} FROM {$table}_visibilidad v WHERE uid_empresa IN ({$ownerStartList->toComaList()})) ";
                    }
                }

                $conditions[] = $SQL;
            }

            if (count($conditions)) {
                $condition = implode(' AND ', $conditions);
                return $condition;
            }
        }

        return "(0)";
    }



    public function getInlineIcons(Iusuario $usuario){
        //$cacheKey = __CLASS__.'-'.__FUNCTION__.'-'.$this.'-'.$usuario->obtenerPerfil();
        //if (($value = $this->cache->get($cacheKey)) !== NULL) return json_decode($value, true);
        $tpl = Plantilla::singleton();
        $empresaUsuario = $usuario->getCompany();

        // ---- Agrupadores
        $agrupadoresVisibles = $empresaUsuario->obtenerAgrupadoresVisibles(array($usuario, "visible=1"));

        if (is_traversable($agrupadoresVisibles) && count($agrupadoresVisibles)) {
            $asig = array();

            foreach( $agrupadoresVisibles as $agrupador ){

                $informacion = $this->obtenerEstadoEnAgrupador($usuario, $agrupador, true);
                if (!$informacion) continue;

                $status =  $informacion->estado;
                $icon = $agrupador->getIcon(false);
                $literal = ($status == "-1") ? 'definicion_sin_solicitar' : 'explain_icon.stat_'. $status;

                if ($icon) {
                    $name = $agrupador->getIcon(true, null);
                    //$icon = "<div class='line-block stat stat_".$informacion->estado."'>".$agrupador->getIcon()."</div>";
                } else {
                    $name = $informacion->img;
                    //$icon = "<div class='line-block stat stat_".$informacion->estado."'>".$informacion->img."</div>";
                }

                if ($status) {
                    $asig[] = array(
                        "tagName" => "span",
                        "className" => "help line-block stat stat_".$status,
                        "nombre" => $name,
                        "title" => $agrupador->getUserVisibleName() . " - " . $tpl($literal)
                        //"href" => $agrupador->obtenerUrlPreferida()
                    );
                }
            }

            //$this->cache->set($cacheKey, json_encode($asig), 60*60*15);
            return $asig;
        }

        //$this->cache->set($cacheKey, false, 60*60*15);
        return false;
    }


    /** NOS DEVOLVERA UN CONJUNTO DE DATOS CON UN FORMATO AMIGABLE PARA EL DESARROLLO DE LA GUI DE ASIGNACION.PHP **/
    public function getAssignData (Iusuario $usuario) {
        $debug = (bool) isset($_REQUEST["debug"]) && $usuario->esStaff();
        $modulo = $this->getModuleName();

        $empresaUsuario = $usuario->getCompany();
        $esCorporacion = $empresaUsuario->esCorporacion();


        // --- CALCULAR EMPRESAS CLIENTE

        // Si es un agrupador de un cliente con corporacion,
        // los clientes visibles son tambien los visibles para las empresas del grupo
        $empresasClientes = new ArrayObjectList;
        if ($this instanceof agrupador && $esCorporacion) {
            $empresasGrupo = $empresaUsuario->obtenerEmpresasInferiores();
            foreach($empresasGrupo as $empresaGrupo){
                $empresasClientes = $empresasClientes->merge($empresaGrupo->obtenerEmpresasSolicitantes($usuario));
            }
        } else {
            if ($this instanceof empresa) {
                $empresasClientes = $this->obtenerEmpresasSolicitantes($usuario);
                //$empresasClientes->toUL();exit;
                $empresasClientes = $empresasClientes->merge($this);
            } elseif ($this instanceof childItemEmpresa) {
                $empresasClientes = $this->obtenerEmpresasSolicitantes($usuario);
                if (count($empresasClientes)) $empresasClientes->unique();

                // $empresasClientes = new ArrayObjectList();
                // foreach ($empresasSolicitantesItem as $emp){
                //  // --- solo cuando tenga visibilidad
                //  if ($this->esVisiblePara($emp)) $empresasClientes[] = $emp;
                // }
            } elseif ($this instanceof signinRequest) {
                $empresasClientes = $this->obtenerEmpresasSolicitantes($usuario);
                if (count($empresasClientes)) $empresasClientes->unique();
            } else {
                // --- este else es para usuarios y contactos
                $company = $this->getCompany();
                $empresasClientes = $company->obtenerEmpresasSolicitantes($usuario);
            }
        }

        $data = new extendedArray();

        $listaAgrupamientos = new ArrayObjectList();
        if ($empresaUsuario->esCorporacion()) {
            $empresasInf = $empresaUsuario->obtenerEmpresasInferiores();
            $solicitantesCoporacion = $empresaUsuario->obtenerEmpresasSolicitantes();
            $clientesActivosEmpresaUsuario = $empresasInf->foreachCall("obtenerEmpresasSolicitantes")->merge($solicitantesCoporacion);
            if (count($clientesActivosEmpresaUsuario)) $clientesActivosEmpresaUsuario->unique();
        } else {
            $clientesActivosEmpresaUsuario = $empresaUsuario->obtenerEmpresasSolicitantes();
        }

        $empresasReferencia = ($this instanceof empresa) ? new ArrayObjectList(array($this)) : $this->obtenerElementosSuperiores();

        $setCompaniesVisibility = new ArrayObjectList();


        if (!$usuario->esStaff()) {
            foreach ($clientesActivosEmpresaUsuario as $emp) {
                if ($emp->esCorporacion()){
                    $setCompaniesVisibility = $setCompaniesVisibility->merge($emp->obtenerEmpresasInferiores())->merge($emp)->unique();
                } else {
                    $setCompaniesVisibility[] = $emp;
                }
            }

            $empresasClientes = $empresasClientes->match($setCompaniesVisibility);
            if ($empresasClientes && count($empresasClientes)) $empresasClientes->unique();
        }

        if ($debug) echo " Encontrados ". count($empresasClientes) . " empresasClientes <br />";
        if ($empresasClientes && count($empresasClientes)) {
            foreach($empresasClientes as $empresaCliente){
                if (($count = $empresaCliente->obtenerAgrupamientosPropios([], true)) == 0) continue;

                $clientData = new extendedArray();
                $agrupamientos = new ArrayObjectList();
                if($debug) echo " *********** Vaciando agrupamientos... <br />";

                foreach($empresasReferencia as $empresaReferencia){
                    $corp = null; // default null

                    $esUsuarioEmpresaAsignada = (bool) $empresaUsuario->getStartIntList()->contains($empresaReferencia->getUID());
                    $isDifferentItem = !$this instanceof empresa || !$esUsuarioEmpresaAsignada;

                    if ($empresaReferencia instanceof empresa){
                        $corp = $empresaReferencia->perteneceCorporacion();
                    }

                    // No debería ser necesario diferenciar al empleado... pero es una forma sencilla y rápida de hacer que funcione en el portal del empleado
                    if( !$usuario->accesoElemento($empresaReferencia) && !$usuario instanceof empleado ){
                        if($debug) echo "No se tiene acceso a {$empresaReferencia->getUserVisibleName()} <br>";
                        continue;  // Si no tenemos acceso a la empresa
                    } else {
                        if($debug) echo "Buscando agrupamientos para la empresa {$empresaReferencia->getUserVisibleName()} en la empresa cliente {$empresaCliente->getUserVisibleName()} <br>";
                    }

                    if($debug){ echo "Estamos asignando una empresa de nuestra corporación: "; var_dump($esUsuarioEmpresaAsignada); echo "<br />"; }


                    // Vamos a ver si es necesario filtrar de alguna forma los agrupamientos a mostrar, tomando de referencia las empresas superiores
                    // En primer lugar vemos si nuestro usuario pertenece a una corporación, si es así, debe coincidir con la empresa-cliente que estamos 'recorriendo'
                    // En segundo lugar, vemos si esta asignando a una empresa de su propia corporación
                    // O bien, que este asignando a una empresa contrata / subcontrata
                    $filtrarPorJerarquia = false;

                    if( $empresaCliente->esCorporacion() ){

                        if( ($esUsuarioEmpresaAsignada && !$empresaUsuario->isEnterprise()) || !$esUsuarioEmpresaAsignada ){
                            $filtrarPorJerarquia = true;
                        }
                    }

                    if ( ($empresaUsuario->perteneceCorporacion() || $empresaCliente->perteneceCorporacion()) && !$esUsuarioEmpresaAsignada){
                        $filtrarPorJerarquia = true;
                    }

                    if( $empresaReferencia instanceof solicitable && $filtrarPorJerarquia ){
                        if($debug) echo "La empresa del usuario es diferente a la referencia({$empresaReferencia->getUserVisibleName()})  y además la empresa {$empresaCliente->getUserVisibleName()} tiene corporacion <br>";

                        if (!$empresaUsuario->esCorporacion() && !$empresaUsuario->compareTo($empresaReferencia)) {
                            $caminos = $empresaCliente->obtenerCaminos($empresaReferencia, $empresaUsuario);
                        } else {
                            $caminos = $empresaCliente->obtenerCaminos($empresaReferencia);
                        }


                        if( is_traversable($caminos) ){
                            if($debug) echo "Se han encontrado ". count($caminos) . " caminos desde {$empresaCliente->getUserVisibleName()} ({$empresaCliente->getUID()}) hasta {$empresaReferencia->getUserVisibleName()} ({$empresaReferencia->getUID()}) <br />";
                            foreach($caminos as $camino){
                                $empresaGrupo = reset($camino);

                                $empresasSuperiores = $empresaReferencia->obtenerEmpresasSuperiores(empresa::DEFAULT_DISTANCIA);
                                if (!$empresasSuperiores->contains($empresaGrupo)) continue;

                                // Vamos a sacar los agrupamientos de esta empresa
                                $sourceCompany = $empresaReferencia;

                                if ($empresaGrupo->compareTo($empresaCliente) && $empresaCliente->esCorporacion()) {
                                    if (!$empresaReferencia instanceof empresa) {
                                        $sourceCompany = $empresaReferencia;
                                    }
                                } else {
                                    if ($this instanceof childItemEmpresa && !$this->esVisiblePara($empresaGrupo)) continue;
                                    $sourceCompany = $empresaGrupo;
                                }

                                $agrupamientosVisibles = $sourceCompany->obtenerAgrupamientosVisibles(array('modulo' => $modulo, $usuario, $empresaReferencia));

                                if($debug) echo "Buscando agrupamientos de la empresa {$sourceCompany->getUserVisibleName()} por ser {$empresaCliente->getUserVisibleName()} a una corporacion. Se han encontrado ". count($agrupamientosContrata) ."<br />";
                                if($debug) $agrupamientosVisibles->toUL();

                                if ($agrupamientosVisibles){
                                    $agrupamientos = $agrupamientos->merge($agrupamientosVisibles);
                                }

                            }
                        } else {
                            if($debug) echo "No hay ningún camino desde {$empresaCliente->getUserVisibleName()} ({$empresaCliente->getUID()}) hasta {$empresaReferencia->getUserVisibleName()} ({$empresaReferencia->getUID()}) <br />";
                        }
                    } else {

                        $agrupamientosAsignados = false;

                        // Vamos a sacar los agrupamientos de esta empresa
                        $sourceCompany = $empresaReferencia;

                        if (isset($corp) && $corp && $corp->compareTo($empresaCliente)) {
                            if (!$empresaReferencia instanceof empresa) {
                                $agrupamientosAsignados = $empresaReferencia->getCompany();
                            }
                        } else {
                            // No pertenecemos a una corporación, caso normal, mostramos los visibles de la empresa que estamos mirando.
                            $sourceCompany = $empresaCliente;
                        }


                        if ($agrupamientosAsignados = $sourceCompany->obtenerAgrupamientosVisibles(array('modulo' => $modulo, $usuario, $empresaReferencia))) {
                            $agrupamientos = $agrupamientos->merge($agrupamientosAsignados);
                        }

                        if ($debug) echo "La empresa del usuario es igual que la referencia o el cliente no es corporacion: El cliente tiene ". count($agrupamientos) ." agrupamientos<br />";
                    }



                    $propiosUsuario     = $empresaUsuario->obtenerAgrupamientosPropios(array('modulo' => $modulo, $usuario));
                    $orgsAssignedCorp   = $empresaUsuario->obtenerAgrupamientosCorporacionAsignados($usuario);
                    if (is_traversable($agrupamientos) && count($agrupamientos)){
                        foreach($agrupamientos as $agrupamiento ){

                            if($debug) echo " -- Buscando asignados y disponibles en {$agrupamiento->getUserVisibleName()} de {$agrupamiento->getCompany()->getUserVisibleName()}<br />";
                            $hasHierarchy = $agrupamiento->tieneJerarquia();
                            $groupData = new extendedArray();
                            $disponibles = $asignados = new ArrayObjectList();


                            // Si la empresa empresa cliente esta dentro del conjunto de empresas que pueden ver este elemento
                            if( $empresaReferencia instanceof empresa && !in_array($empresaCliente->getUID(), $empresaReferencia->obtenerEmpresasSolicitantes()->toIntList()->getArrayCopy()) ) continue;

                            $asignados = $agrupamiento->obtenerAgrupadoresAsignados($this, $usuario);

                            $listaTotalAgrupadores = $agrupamiento->obtenerAgrupadores($usuario);


                            $isOwner = is_traversable($propiosUsuario) && $propiosUsuario->contains($agrupamiento);

                            if($hasHierarchy && !$isOwner && $isDifferentItem){
                                // Filtramos por jerarquia si no estamos mirando nuestra propia empresa

                                $asignadosEmpresaUsuario = $agrupamiento->obtenerAgrupadoresAsignados($empresaUsuario, $usuario);
                                $asignadosEmpresaReferencia = $agrupamiento->obtenerAgrupadoresAsignados($empresaReferencia, $usuario);


                                $listaAsignados = $asignadosEmpresaUsuario->merge($asignadosEmpresaReferencia);
                                $listaAsignados = $listaAsignados->merge($agrupamiento->obtenerAgrupadoresAsignadosRelacion($empresaUsuario, $usuario));
                                $listaAsignados = $listaAsignados->merge($agrupamiento->obtenerAgrupadoresAsignadosRelacion($empresaReferencia, $usuario))->unique();


                                $ocultos = $listaTotalAgrupadores->discriminar($listaAsignados);
                                $asignados = $asignados->discriminar($ocultos);
                                $listaTotalAgrupadores = $listaTotalAgrupadores->discriminar($ocultos);
                            }


                            $disponibles = $listaTotalAgrupadores->discriminar($asignados);


                            $isSelfCompany = $empresaUsuario->compareTo($empresaCliente);
                            $isSelfCorp = isset($corp) && $empresaUsuario->compareTo($corp);
                            $isClientCorp = isset($corp) && $empresaCliente->compareTo($corp);
                            $isSelf = $isSelfCompany || $isSelfCorp || $isClientCorp;


                            if ( $isSelf || (!$this instanceof empresa && !$this instanceof usuario) || !$esUsuarioEmpresaAsignada/* || $usuario->esStaff()*/ ){

                                $disponibles = $listaTotalAgrupadores->discriminar($asignados);

                                if($debug) echo " ---- Buscando agrupadores con origen {$agrupamiento->getUserVisibleName()} en el empresaCliente {$empresaCliente->getUserVisibleName()}<br>";
                            } else {
                                $disponibles = "hidden"; // esto nos oculta la caja de la izquierda
                                if($debug) echo " ---- El empresaCliente del usuario {$empresaUsuario->getUserVisibleName()} es un empresaCliente y estamos mirando asignaciones de {$empresaCliente->getUserVisibleName()}: saltamos<br>";
                            }

                            if ($agrupamiento->configValue('readonly') == true) {
                                $disponibles = "readonly";
                            }

                            $listaAgrupamientos[] = $agrupamiento; // Vamos almacenando el array global para luego contrastar al guardar

                            //if corp we do not apply hierarchy to contactoempresa and usuario
                            $applyHierarchyCorp = $isSelfCompany && $empresaUsuario->esCorporacion() && ($this instanceof contactoempresa || $this instanceof usuario);

                            if (($this instanceof childItemEmpresa || $this instanceof contactoempresa || $this instanceof usuario) && $agrupamiento->tieneJerarquia() && !$applyHierarchyCorp) {
                                $listaTotalAgrupadores = $agrupamiento->obtenerAgrupadores($usuario);

                                $asignadosEmpresa = $agrupamiento->obtenerAgrupadoresAsignados($empresaReferencia, $usuario);
                                $asignadosEmpresa = $asignadosEmpresa->merge( $agrupamiento->obtenerAgrupadoresAsignadosRelacion($empresaReferencia, $usuario) )->unique();
                                if($debug){ dump("Asignados de empresa total"); $asignadosEmpresa->toUL(); }


                                $disponiblesEmpresa = $listaTotalAgrupadores->discriminar($asignadosEmpresa);


                                if( $disponibles instanceof ArrayObject ) $disponibles = $disponibles->discriminar($disponiblesEmpresa);
                                $asignados = $asignados->discriminar($disponiblesEmpresa);
                            }

                            if ( !count($asignados) && $disponibles == "hidden" ) continue;

                            if( isset($clientData["$agrupamiento"]) && $disponibles != "hidden" && $disponibles != "readonly" ){
                                $clientData["$agrupamiento"]["asignado"] = $clientData["$agrupamiento"]["asignado"]->merge( $asignados )->unique();
                                $clientData["$agrupamiento"]["disponible"] = $clientData["$agrupamiento"]["disponible"]->merge( $disponibles )->unique();
                            } else {
                                $groupData["disponible"] = $disponibles;
                                $groupData["asignado"] = $asignados;

                                if ((true === is_countable($disponibles) && count($disponibles))
                                    || (true === is_countable($asignados) &&  count($asignados))) {
                                    $clientData["$agrupamiento"] = $groupData;
                                }
                            }
                        }

                        if($debug) echo "---- Guardando info del empresaCliente {$empresaCliente->getUserVisibleName()} (". count($clientData) .")<br>";

                        if ($corp = $empresaCliente->perteneceCorporacion()) {
                            $uidEmpresa = $corp->getUID();
                        } else {
                            $uidEmpresa = $empresaCliente->getUID();
                        }

                        if (count($clientData)) {
                            if (isset($data[$uidEmpresa])){
                                $data[$uidEmpresa] = $data[$uidEmpresa]->mergeWithKeys($clientData);
                                //$data[$uidEmpresa] = $data[$uidEmpresa]->mergeRecursive($clientData);
                                //dump($data[$uidEmpresa]);
                            }else{
                                $data[$uidEmpresa] = $clientData;
                            }
                        }
                    }
                }
            }
        }

        if($debug) exit;

        $data->agrupamientos = $listaAgrupamientos;

        return $data;
    }

    public static function actualizarAgrupadoresMasivamente($elementos, $list = array(), Iusuario $usuario = NULL, $addOnly = true ){
        if( count($elementos) && is_traversable($elementos) ){

            $done = true;

            $empresa = $usuario->getCompany();
            $aux = reset($elementos);
            $db = db::singleton();
            $app = \Dokify\Application::getInstance();


            $agrupadores = $empresa->obtenerAgrupadores(false, true);

            if( count($list) ){
                $sql = "SELECT uid_agrupador FROM ". TABLE_AGRUPADOR . " WHERE uid_agrupador IN (". implode(",", $list) .") AND uid_agrupador IN ({$agrupadores->toIntList()}) ";
                $list = $db->query($sql, "*", 0);
            }


            if( $addOnly == false ){
                $sql = "DELETE FROM " . TABLE_AGRUPADOR ."_elemento WHERE uid_modulo = {$aux->getModuleId()} AND uid_elemento IN ({$elementos->toComaList()}) AND uid_agrupador IN ({$agrupadores->toIntList()})";
                if( count($list) ){
                    $sql .= " AND uid_agrupador NOT IN (". implode(",", $list) .")";
                }

                $db->query($sql);
            }


            // Si hay algun agrupador que asignar...
            if( count($list) ){
                foreach($elementos as $item){
                    $assignedGroups = $item->asignarAgrupadores($list, $usuario);
                    if ($assignedGroups) {
                        foreach ($assignedGroups as $group) {
                            if (!$assignment = $item->getAssignment($group)) {
                                continue;
                            }

                            $entity         = $assignment->asDomainEntity();
                            $userEntity     = $usuario->asDomainEntity();
                            $companyEntity  = $usuario->getCompany()->asDomainEntity();

                            $event = new \Dokify\Application\Event\Assignment\Store($entity, $companyEntity, $userEntity);
                            $app->dispatch(\Dokify\Events::POST_ASSIGNMENT_STORE, $event);
                        }
                    } else {
                        $done = false;
                    }
                }
            }

            return $done;
        }
        return null;
    }


    public function obtenerAgrupamientos(
        $idModulo = false,
        $elementoFiltro = false,
        $elemento = false
    ) {
        $sql = "SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ." WHERE 1 AND nombre != ''";

        if( $idModulo ){
            if( !is_numeric($idModulo) ){
                $idModulo = elemento::obtenerIdModulo($idModulo);
            }
            $filters["modulo"] = "uid_agrupamiento IN ( SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ."_modulo WHERE uid_modulo = $idModulo)";
        }


        if( $elementoFiltro instanceof empresa ){
            $filters[] = "uid_empresa =". $elementoFiltro->getUID();
        }


        if( $elementoFiltro instanceof usuario ){
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
        }

        if( isset($filter) && count($filters) ){
            $lastSQL =  $sql . " AND " . implode(" AND ", $filters);
        } else {
            $lastSQL =  $sql;
        }

        if( isset($start) && $length && is_numeric($start) && is_numeric($length) ){
            $lastSQL .= " LIMIT $start, $length";
        }
        $lastSQL .= " ORDER BY nombre";
        $data = $this->db->query($lastSQL, "*", 0, "agrupamiento");
        $coleccionAgrupamientos = new ArrayObjectList( $data );

        return $coleccionAgrupamientos;

    }


    public function quitarAgrupadores($arrayIDS, Iusuario $usuario = null, $asignados = false)
    {
        $agrupadores    = array();
        $list           = implode(", ", $arrayIDS);
        $assignmentsSet = [];

        // si nuestro elemento no es un agrupador
        if (!$this instanceof agrupador) {
            $sqlRelaciones = "
                FROM ". TABLE_AGRUPADOR ."_elemento_agrupador r
                INNER JOIN ". TABLE_AGRUPADOR ."_elemento ae USING(uid_agrupador_elemento)
                WHERE ae.uid_modulo = ". $this->getModuleId() ."
                AND ae.uid_elemento = ". $this->getUID() ."
                AND ae.uid_agrupador IN ( $list )
            ";

            $arrayReboteRelacion = $this->db->query("SELECT r.uid_agrupador " . $sqlRelaciones, "*", 0);

            if (is_array($arrayReboteRelacion) && count($arrayReboteRelacion)) {
                $partSQL = " FROM " . TABLE_AGRUPADOR ."_elemento
                    WHERE uid_modulo = ". $this->getModuleId() ."
                    AND uid_elemento = ". $this->getUID() ."
                    AND rebote IN ( ". implode(", ", $arrayReboteRelacion) ." )
                ";

                $deleteSQL = "DELETE ". $partSQL;
                $selectSQL = "SELECT uid_agrupador ". $partSQL;

                $agrupadoresRelacion = $this->db->query($selectSQL, "*", 0, "agrupador"); // reemplazamos ya que aun no se ha tocado
                $agrupadores = array_merge($agrupadores, $agrupadoresRelacion);

                if ($this->db->query($deleteSQL)) {

                }
            }




            $arrayIDSRebotados = array();
            // Por cada elemento agrupador a eliminar
            foreach ($arrayIDS as $uid) {
                $agrupador = new agrupador($uid, false);
                $agrupadoresAgrupador = $agrupador->obtenerAgrupadores(null, $usuario);
                foreach ($agrupadoresAgrupador as $agrupadorRebote) {
                    $arrayIDSRebotados[] = $agrupadorRebote->getUID();
                }
            }

            if (is_array($arrayIDSRebotados) && count($arrayIDSRebotados)) {
                $arrayIDSRebotados = array_unique($arrayIDSRebotados); // eliminamos posibles duplicados

                // SQL previa para ver que ids se va a eliminar de rebote
                $from = " FROM ". TABLE_AGRUPADOR ."_elemento ae
                    WHERE ae.uid_modulo = ". $this->getModuleId() ."
                    AND ae.uid_elemento = ". $this->getUID() ."
                    AND ae.uid_agrupador IN (". implode(",", $arrayIDSRebotados) .")
                    AND ae.rebote != 0
                    AND ae.rebote NOT IN (
                        SELECT aea.uid_agrupador FROM ". TABLE_AGRUPADOR ."_elemento_agrupador aea
                        INNER JOIN ". TABLE_AGRUPADOR ."_elemento sub USING (uid_agrupador_elemento)
                        WHERE sub.uid_elemento = ae.uid_elemento AND sub.uid_modulo = ae.uid_modulo
                    )
                ";
                $agrupadoresRebote = $this->db->query("SELECT uid_agrupador ".$from, "*", 0, "agrupador");
                $agrupadores = array_merge($agrupadores, $agrupadoresRebote);

                // Retrive the bouncend assignments before delete them
                // $bouncedGroups   = new ArrayObjectList($agrupadores);
                // $intGroupsAssigned   = (count($assignedGroups)) ? $assignedGroups->toIntList() : 0;

                $sqlBouncedAssigns = "SELECT ae.uid_agrupador_elemento, ae.uid_agrupador {$from}";
                $bouncedAssignments = $this->db->query($sqlBouncedAssigns, true);

                foreach ($bouncedAssignments as $row) {
                    $uidAssignment  = $row['uid_agrupador_elemento'];
                    $uidGroup       = $row['uid_agrupador'];

                    $group      = new agrupador($uidGroup);
                    $assigment  = new Assignment($uidAssignment);

                    // neccesary to cache the group. If not, will not exists when query for it
                    $assigment->group   = $group;
                    $assigment->element = $this;

                    $assignmentsSet[] = $assigment;
                }

                $removeList = $this->db->query("SELECT uid_agrupador_elemento ".$from, "*", 0);
                if (count($removeList)) {
                    $delete = "DELETE FROM ". TABLE_AGRUPADOR ."_elemento WHERE uid_agrupador_elemento IN (". implode(",", $removeList) .")";
                    $this->db->query($delete);
                    if ($this->db->lastError()) {
                        echo "Error al eliminar los rebotes<br />";
                    }
                }
            }
        }

        // Filtro constante
        $sqlWhere = " WHERE uid_modulo = ". $this->getModuleId() ." AND uid_elemento = ". $this->getUID() ."
                      AND uid_agrupador IN (". implode(",", $arrayIDS) .")";

        if ($asignados) {
            $sqlWhere .= " AND uid_agrupador NOT IN (". implode(",", $asignados) .")";
        }


        $selectSQL = "SELECT uid_agrupador  FROM ". TABLE_AGRUPADOR ."_elemento " . $sqlWhere;
        $agrupadoresEliminar = $this->db->query($selectSQL, "*", 0, "agrupador");

        // Recogemos los objetos...
        $total = 0;
        // por cada agrupador a eliminar
        foreach ($agrupadoresEliminar as $agrupador) {
            if ($this instanceof empresa && $agrupador->esJerarquia()) {
                $elementosAsignados = $agrupador->obtenerElementosAsignados(false, $this);
                foreach ($elementosAsignados as $elemento) {
                    if ($elemento instanceof childItemEmpresa) {
                        if (!$assignment = $elemento->getAssignment($agrupador)) {
                            continue;
                        }

                        if ($usuario) {
                            $entity     = $assignment->asDomainEntity();
                            $repo       = $this->app['assignment_version.repository'];
                            $versions   = $repo->fromAssignment($entity);
                            $company    = $this->asDomainEntity();

                            $valids = $versions->filter(function($version) use ($company) {
                                return $company->equals($version->company);
                            });

                            if (count($valids)) {
                                // remove the deleted version
                                $repo->delete($valids[0]);
                            }

                            $numVersions = count($versions);
                            if ($numVersions === 1 || $numVersions === 0) {
                                $elemento->quitarAgrupadores([$agrupador->getUID()]);
                            }
                        } else {
                            $elemento->quitarAgrupadores(array($agrupador->getUID()));
                        }

                        // mark for update the request today
                        $elemento->updateNeeded(true);
                    }
                }
            }

            // si quitamos la asignacion a un agrupador, actualizamos los rebotes
            if ($this instanceof agrupador) {

                if ($agrupador->esAnclaje()) {
                    if ($num = $this->clear()) {
                        $total += $num;
                    }
                }
                $sql = "
                    DELETE FROM ". TABLE_AGRUPADOR ."_elemento
                    WHERE uid_agrupador = ". $agrupador->getUID() ."
                    AND rebote = ". $this->getUID() ."
                ";
                if (!$this->db->query($sql)) {
                    echo "<br>Error al quitar rebotes";
                }
            }

            $atributos = $agrupador->obtenerDocumentoAtributos();

            foreach ($atributos as $docat) {
                if ($docat->obtenerDato('caducidad_automatica') == 1 && $this instanceof solicitable && $anexos = $docat->getAnexo($this, false, true)) {
                    foreach ($anexos as $anexo) {
                        $result = $anexo->update(
                            array(
                                'estado' => documento::ESTADO_CADUCADO,
                                "uid_anexo_renovation" => "NULL",
                                "reverse_status" => "NULL",
                                "reverse_date" => "NULL",
                                "validation_argument" => "NULL"
                            ),
                            elemento::PUBLIFIELDS_MODE_ATTR,
                            $usuario
                        );
                        if ($result === false) {
                            echo "No se pueden caducar algunos documentos<br />";
                        }
                    }
                    $arrayAnexoList = new ArrayAnexoList($anexos);
                    $arrayAnexoList->saveComment('', null, comment::ACTION_EXPIRE, false);

                }
            }

            $agrupadores[] = $agrupador;
        }


        if ($total) {
            echo "Se han eliminado $total relacion(es)<br /><br />";
        }

        $bouncedGroups      = new ArrayObjectList($agrupadores);
        $intGroupsAssigned  = (count($bouncedGroups)) ? $bouncedGroups->toIntList() : 0;

        $sqlAssignments = "SELECT uid_agrupador_elemento, uid_agrupador FROM ". TABLE_AGRUPADOR ."_elemento
        WHERE uid_elemento = {$this->getUID()}
        AND uid_modulo = {$this->getModuleId()}
        AND uid_agrupador IN ($intGroupsAssigned)";

        $rows = $this->db->query($sqlAssignments, true);
        foreach ($rows as $row) {
            $uidAssignment  = $row['uid_agrupador_elemento'];
            $uidGroup       = $row['uid_agrupador'];

            $group      = new agrupador($uidGroup);
            $assigment  = new Assignment($uidAssignment);

            // neccesary to cache the group. If not, will not exists when query for it
            $assigment->group   = $group;
            $assigment->element = $this;

            $assignmentsSet[] = $assigment;
        }

        $deletedSelectSQL = "SELECT uid_agrupador_elemento  FROM ". TABLE_AGRUPADOR ."_elemento " . $sqlWhere;
        $deletedAssignments = $this->db->query($deletedSelectSQL, true);
        foreach ($deletedAssignments as $deletedRow) {
            $uidAssignment  = $deletedRow['uid_agrupador_elemento'];
            $assignment  = new Assignment($uidAssignment);

            $event = new \Dokify\Application\Event\Assignment\Destroy($assignment->asDomainEntity());
            $this->app->dispatch(\Dokify\Events::POST_ASSIGNMENT_DESTROY, $event);
        }

        $sqlBorrar = "DELETE FROM ". TABLE_AGRUPADOR ."_elemento " . $sqlWhere;
        if ($this->db->query($sqlBorrar)) {
            //echo "Se han eliminado ". $this->db->getAffectedRows() ." asignaciones<br /><br />";
            return new ArrayAssignmentList($assignmentsSet);
        } else {
            return false;
        }
    }



    public function obtenerAgrupadoresDiff($difflist){
        // SI NO ES UN ARRAY QUE CONTENGA SOLO NUMEROS
        if (!is_array($difflist) || !is_numeric(implode('', $difflist))) return new ArrayObjectList();

        $SQL = "SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ." WHERE uid_agrupamiento IN (
            SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ."_modulo WHERE uid_modulo = {$this->getModuleId()}
        ) AND uid_agrupador IN (". implode(",", $difflist) .") ";
        $difflist = $this->db->query($SQL, "*", 0);

        // SI NO ES UN ARRAY QUE CONTENGA SOLO NUMEROS
        if (!is_array($difflist) || !is_numeric(implode('', $difflist))) return new ArrayObjectList();

        $asignados = $this->obtenerAgrupadores()->toIntList()->getArrayCopy();
        $diff = array_diff($difflist, $asignados);

        if (!count($diff)) return new ArrayObjectList();

        $list = new ArrayIntList($diff);
        $sugeridos = $list->toObjectList('agrupador');


        return $sugeridos;
    }


    /**
     * Get the assigned groups of this item
     * @param  null         $recursividad       DEPRECATED
     * @param  boolean      $usuario
     * @param  boolean      $agrupamientos
     * @param  boolean      $condicion
     * @param  boolean      $forceCurrentClient
     * @param  null|array   $categories
     * @param  array        $sqlOptions
     * @return ArrayAgrupadorList
     */
    public function obtenerAgrupadores(
        $recursividad = null,
        $usuario = false,
        $agrupamientos = false,
        $condicion = false,
        $forceCurrentClient = false,
        $categories = null,
        $sqlOptions = [],
        $applyGroupFilter = false
    ) {
        $count  = isset($sqlOptions['count']) ? $sqlOptions['count'] : false;
        $limit  = isset($sqlOptions['limit']) ? $sqlOptions['limit'] : [];
        $groups = new ArrayAgrupadorList();


        $cacheString = implode('-', array($this, __FUNCTION__, $usuario, $condicion, $count, implode('-', $limit), "$agrupamientos"));
        if (($estado = $this->cache->getData($cacheString)) !== null) {
            if ($count) {
                return (int) $estado;
            }

            return ArrayAgrupadorList::factory($estado);
        }

        // Empresas del elemento categorizable
        if ($this instanceof empresa) {
            $elementCompanies       = $this->getOriginCompanies();
            $empresasCliente        = $this->obtenerEmpresasSolicitantes();
            $empresasSuperiores     = $this->obtenerEmpresasSuperiores(empresa::DEFAULT_DISTANCIA);
        } else if ($this instanceof childItemEmpresa) {
            $elementCompanies       = $this->getCompanies(false, $usuario);
            $empresasCliente        = $this->obtenerEmpresasSolicitantes($usuario);
            $empresasSuperiores     = $this->obtenerEmpresasSolicitantes($usuario, false);
        } else {
            $company                = $this->getCompany();
            $elementCompanies       = $company->getOriginCompanies();
            $empresasCliente        = $company->obtenerEmpresasSolicitantes();
            $empresasSuperiores     = $company->obtenerEmpresasSuperiores(empresa::DEFAULT_DISTANCIA);
        }

        $uidModuloActivo    = $this->getModuleId();
        $moduloActivado     = ($this instanceof perfil) ? util::getModuleId("usuario") : $uidModuloActivo;

        // When the item is in the trash, $this may has no companies
        if (0 === count($elementCompanies)) {
            if ($count) {
                return 0;
            }

            return $groups;
        }

        $fields = $count ? "count(uid_agrupador)" : "uid_agrupador, rebote";

        $groupsTable    = TABLE_AGRUPADOR;
        $assignsTable   = TABLE_AGRUPADOR ."_elemento";
        $orgTable       = TABLE_AGRUPAMIENTO;
        $orgTargetTable = TABLE_AGRUPAMIENTO ."_modulo";
        $versionsTable  = DB_DATA . '.assignment_version';

        $clientCondition  = "1";
        $sqlFilter        = [];

        // si forzamos al cliente actual no necesitamos conocer las empresas solicitantes
        if ($forceCurrentClient && $usuario instanceof usuario) {
            $empresaUsuario = $usuario->getCompany();
            if ($corp = $empresaUsuario->perteneceCorporacion()) {
                $list               = $corp->getStartIntList()->toComaList();
                $clientCondition    = "{$orgTable}.uid_empresa IN ({$list})";
            } else if ($empresaUsuario->esCorporacion()) {
                $list               = $empresaUsuario->getStartIntList()->toComaList();
                $clientCondition    = "{$orgTable}.uid_empresa IN ({$list})";
            } else {
                $empresas = $empresaUsuario->obtenerEmpresasSolicitantes()->merge($empresaUsuario);
                $list               = $empresas->toComaList();
                $clientCondition    = "{$orgTable}.uid_empresa IN ({$list})";
            }
        }

        if (0 === count($empresasCliente)) {
            if ($count) {
                return 0;
            }

            return $groups;
        }

        $visibilityWhere = "{$orgTable}.uid_empresa IN ({$empresasCliente->toComaList()})";

        if ($usuario instanceof usuario) {
            $userCompany = $usuario->getCompany();
            $userClients = $userCompany->obtenerEmpresasSolicitantes();

            $visibilityWhere .= " AND {$orgTable}.uid_empresa IN ({$userClients->toComaList()})";
        }

        $conditionCompanyOR = [];

        if ($this instanceof signinRequest) {
            $conditionCompanyOR[] = "(
                visible_others = 1
                AND {$orgTable}.uid_empresa IN ({$elementCompanies->toComaList()})
            )";
        } else {
            // Agrupamientos visibles para las contratas
            foreach ($elementCompanies as $elementCompany) {
                $conditionCompanyOR[] = "(
                    visible_others = 1
                    AND {$orgTable}.uid_empresa != {$elementCompany->getUID()}
                )";
            }

            // Agrupamientos visibles para mi propia empresa
            $conditionCompanyOR[] = "(
                visible_self = 1
                AND {$orgTable}.uid_empresa IN ({$elementCompanies->toComaList()})
            )";
        }

        $visibilityOr = implode(' OR ', $conditionCompanyOR);
        $visibilityWhere .= " AND ({$visibilityOr}) ";

        // for groups the query is different
        if ($this instanceof agrupador) {
            $sql = "SELECT {$fields}
            FROM {$groupsTable}
            LEFT JOIN {$assignsTable}
            USING (uid_agrupador)
            WHERE {$assignsTable}.uid_elemento = {$this->getUID()}
            AND {$assignsTable}.uid_modulo = {$uidModuloActivo}";
        }

        // the query for any other categorizable
        if (!$this instanceof agrupador) {
            $limitByVersion = false;

            if ($usuario instanceof usuario) {
                $userCompany = $usuario->getCompany();

                if ($this instanceof empleado && $userCompany->hasEmployee($this)) {
                    $limitByVersion = true;
                }

                if ($this instanceof maquina && $userCompany->hasMachine($this)) {
                    $limitByVersion = true;
                }
            }

            $sql = "SELECT {$fields}
            FROM {$groupsTable}
            INNER JOIN {$orgTable}
            USING (uid_agrupamiento)
            INNER JOIN {$orgTargetTable}
            USING (uid_agrupamiento)
            INNER JOIN {$assignsTable}
            USING (uid_agrupador)
            ";

            if ($this instanceof childItemEmpresa) {
                $sql .= "LEFT JOIN {$versionsTable}
                ON {$versionsTable}.uid_assignment = {$assignsTable}.uid_agrupador_elemento";
            }

            $sql .= "
            WHERE 1
            AND {$orgTargetTable}.uid_modulo = {$moduloActivado}
            AND {$assignsTable}.uid_elemento = {$this->getUID()}
            AND {$assignsTable}.uid_modulo = {$uidModuloActivo}
            AND {$visibilityWhere}
            ";

            if ($this instanceof childItemEmpresa) {
                $uids = [];
                foreach ($elementCompanies as $elementCompany) {
                    $origins = $elementCompany->getOriginCompanies();
                    $uids = array_merge($uids, $origins->toIntList()->getArrayCopy());
                }

                $versions = implode(',', $uids);

                $sql .= " AND (
                    {$versionsTable}.uid_company IN ({$versions})
                    OR
                    {$versionsTable}.uid_company IS NULL
                )";
            }

            if ($limitByVersion) {
                $userCompanies = $usuario->getCompany()->getStartIntList();

                $sql .= " AND (
                    {$versionsTable}.uid_company IN ({$userCompanies})
                    OR
                    {$versionsTable}.uid_company IS NULL
                )";
            }

            if ($forceCurrentClient) {
                $sqlFilter[] = $clientCondition;
            }
        }

        if ($agrupamientos) {
            if (!is_traversable($agrupamientos)) {
                $agrupamientos = array($agrupamientos);
            }

            $coleccion = elemento::getCollectionIds($agrupamientos);
            if (is_array($coleccion) && count($coleccion)) {
                $sqlFilter[] = "{$groupsTable}.uid_agrupamiento IN ( ". implode(", ", $coleccion) ." )";
            } else {
                $sqlFilter[] = "0";
            }
        }

        if ($categories) {
            $sqlFilter[] = "{$groupsTable}.uid_agrupamiento IN (
            SELECT {$orgTable}.uid_agrupamiento
            FROM {$orgTable}
            WHERE {$groupsTable}.uid_agrupamiento = {$orgTable}.uid_agrupamiento
            AND {$orgTable}.uid_categoria IN (". implode(',', $categories) ."))";
        }

        if ($condicion) {
            $sqlFilter[] = "{$condicion}";
        }

        $sqlFilter[] = "{$groupsTable}.papelera = 0";

        $sql .= " AND " . implode(' AND ', $sqlFilter);

        if ($usuario instanceof usuario
            && $usuario->isViewFilterByGroups()) {
            $userCompany = $usuario->getCompany();
            $agrupadores = $usuario->obtenerAgrupadores();
            $list = count($agrupadores) ? $agrupadores->toComaList() : "0";

            if (true === $applyGroupFilter) {
                $sql .= " AND (
                    (
                        (uid_agrupador IN ($list)
                        AND {$groupsTable}.uid_empresa = {$userCompany->getUID()}
                        AND config_filter = 1
                        )
                        OR
                        ({$groupsTable}.uid_empresa = {$userCompany->getUID()}
                        AND config_filter = 0)
                        )
                        OR (
                        {$groupsTable}.uid_empresa != {$userCompany->getUID()}
                    )
                )
                ";
            }
        }

        if ($count) {
            $groupsCount = $this->db->query($sql, 0, 0);
            $this->cache->addData($cacheString, $groupsCount);
            return $groupsCount;
        }

        if ($limit) {
            $sql .= " LIMIT {$limit[0]}, {$limit[1]}";
        }

        $groupsSQL  = $this->db->query($sql, true);

        if (count($groupsSQL)) {
            foreach ($groupsSQL as $groupSQL) {
                $uid   = $groupSQL["uid_agrupador"];
                $group = new agrupador($uid);

                if ($groupSQL["rebote"]) {
                    $group->rebote = true;
                }

                $groups[] = $group;
            }
        }
        $this->cache->addData($cacheString, "$groups");
        return $groups;
    }


    public function asignarAgrupadores(
        $arrayIDS,
        $usuario = null,
        $rebote = 0,
        $replicar = false,
        $doBounce = true
    ) {
        if( $arrayIDS instanceof agrupador ) $arrayIDS = array($arrayIDS->getUID());
        if( is_numeric($arrayIDS) ) $arrayIDS = array($arrayIDS);
        //$arrayIDS = array_unique($arrayIDS);

        //DEL ARRAY DE UIDAGRUPADORES SACAMOS LOS QUE QUE NO TENGAN EL AGRUPAMIENTO REPLICADO
        $lastAgrupamiento = 0;
        $arrayIDSreal = array();

        $m = $this->getModuleName();

        $inserts = $insertRebote = $asignados = array();  // --- array de elementos para insertar
        if ($this instanceof empresa) {
            $empresas = $this->obtenerEmpresasSolicitantes($usuario);
        } else {
            $empresas = $this instanceof perfil ? $this->getCompany()->getStartList() : $this->obtenerEmpresasSolicitantes($usuario);

        }

        foreach( $arrayIDS as $uid ){ // por cada id de agrupador a asignar
            $agrupador = new agrupador( $uid, false);

            if (true === $agrupador->inTrash()) {
                continue;
            }

            //echo $agrupador->getUserVisibleName() . " <br>";
            //SALATAMOS AL SIGUIENTE LOOP SI LOS UIDAGRUPADORES DE LOS AGRUPAMEINTO NO TIENEN REPLICA
            if( !$agrupador->esReplicable($m) && $replicar ){ continue; }

            if( $this instanceof elemento ){ // si nuestro objeto a asignar no es un agrupador

                if( !$rebote ){ // si no es un rebote

                    if ($empresas && true === $doBounce) {
                        $agrupadoresAgrupador = $agrupador->obtenerAgrupadores(null, $usuario);  // extraemos los agrupadores
                        foreach ($agrupadoresAgrupador as $agrupadorRebote) {
                            // si el agrupador de rebote no es de "anclaje" y no esta marcada la opcion de cancelar rebote
                            if (!$agrupadorRebote->esAnclaje() && !$agrupadorRebote->cancelarReboteDesde($agrupador)) {

                                $reboteOwner = $agrupadorRebote->getCompany();

                                if (false === $empresas->contains($reboteOwner)){
                                    continue;
                                }

                                $asignados[] = $agrupadorRebote;

                                if ($this->getAssignment($agrupadorRebote) !== false){
                                    continue;
                                }

                                if ($this instanceof agrupador && $this->compareTo($agrupadorRebote)) {
                                    continue;
                                }

                                $insertRebote[] = "(".$this->getModuleId().",".$this->getUID().", ". $agrupadorRebote->getUID() .", ". $agrupador->getUID() .")";
                            }
                        }
                    }

                    if( !$this instanceof agrupador ){
                        // Vamos a extrar de todos los elementos as asignar sus relaciones
                        $SQLRelaciones = "
                            SELECT r.uid_agrupador
                            FROM ". TABLE_AGRUPADOR ."_elemento_agrupador r
                            INNER JOIN ". TABLE_AGRUPADOR ."_elemento ae USING(uid_agrupador_elemento)
                            WHERE ae.uid_modulo = ". $this->getModuleId() ."
                            AND ae.uid_elemento = ". $this->getUID() ."
                            AND ae.uid_agrupador = $uid
                        ";
                        $agrupadoresRelacion = $this->db->query($SQLRelaciones, "*", 0, "agrupador");
                        foreach( $agrupadoresRelacion as $agrupadorRel ){ // POR CADA RELACION
                            $SQLReboteRelaciones = "
                                SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ."_elemento
                                WHERE uid_elemento = ". $agrupadorRel->getUID() ."
                                AND uid_modulo = 11
                            ";
                            $rebotesRelacion = $this->db->query($SQLReboteRelaciones, "*", 0, "agrupador");
                            foreach( $rebotesRelacion as $agrupadorReboteRelacion ){ // POR CADA REBOTE DE LA RELACION
                                $asignados[] = $agrupadorRel;
                                $insertRebote[] = "(".$this->getModuleId().",".$this->getUID().", ". $agrupadorReboteRelacion->getUID() .", ". $agrupador->getUID() .")";
                            }
                        }
                    }

                }
            }

            if( $this instanceof agrupador && $agrupador->esAnclaje() ){ // si el objeto sobre el que ejecutamos la accion es un agrupador
                // Asignamos el anclaje a todos aquellos elementos que lo necesiten cuando se asigne desde agrupador - agrupador
                $sql = "
                    INSERT IGNORE INTO ". TABLE_AGRUPADOR ."_elemento_agrupador (uid_agrupador_elemento, uid_agrupador)
                    SELECT uid_agrupador_elemento, ".  $agrupador->getUID() ." FROM ". TABLE_AGRUPADOR ."_elemento
                    INNER JOIN ". TABLE_AGRUPADOR ." USING (uid_agrupador)
                    WHERE uid_agrupamiento IN (
                        SELECT sup.uid_agrupamiento_superior
                        FROM ". TABLE_AGRUPAMIENTO ."_agrupador as sub
                        INNER JOIN ". TABLE_AGRUPAMIENTO ."_agrupamiento as sup
                        ON sub.uid_agrupamiento = sup.uid_agrupamiento_inferior
                        WHERE sub.uid_agrupador = ".  $agrupador->getUID() ."
                    )
                    AND uid_modulo = 1
                    AND agrupador_elemento.uid_agrupador = ". $this->getUID() ."
                    "; // solo para las empresas

                if( !$this->db->query($sql) ){
                    echo "Ocurrió algun error al asignar el anclaje<br />";
                } else {
                    $num = $this->db->getAffectedRows();
                    // if( $num ) echo "$num elemento(s) asignados para <strong>". $agrupador->getUserVisibleName()." por ". $this->getUserVisibleName() ."</strong><br /><br />";
                }
            }


            if( !isset($hiddenClients) || isset($hiddenClients) && !in_array($agrupador->getEmpresasCliente()->getUID(), $hiddenClients->toIntList()->getArrayCopy()) ){
                // Almacenamos el insert del agrupador manual
                $inserts[] = "(".$this->getModuleId().",".$this->getUID().", $uid, $rebote)";

                $asignados[] = $agrupador;
            } else {
                //die("No se asignará el agrupador {$agrupador->getUserVisibleName()} a {$this->getUserVisibleName()} por que no tiene visibilidad");
            }
        }


        // ---- lo diferenciamos, para que los inserts provocados por rebote, vaya despues, y tengan preferencia las selecciones de usuario
        if( count($insertRebote) ) $inserts = array_merge_recursive($inserts, $insertRebote);
        if( !count($inserts) ){ return null; }


        $sql = "INSERT IGNORE INTO ". TABLE_AGRUPADOR ."_elemento
        ( uid_modulo, uid_elemento, uid_agrupador, rebote ) VALUES
        ". implode(",",$inserts);


        if( $this->db->query($sql) ){ // Lanzamos la query
            if( $this instanceof empresa && !$rebote ){ // si nuestro objeto es una empresa y la asignacion es directa
                // Aplicamos el anclaje a la relacion del elemento y el nuevo agrupador asignado, cuando corresponda
                foreach( $asignados as $asignado ){ // por cada elemento recien asignado
                    if( $agrupamientos = $asignado->agrupamientosAnclados() ){ // vemos si pertenece a algun agrupamiento con anclaje
                        $num = 0;

                        $list = implode(",", elemento::getCollectionIds($agrupamientos));

                        $assigns    = TABLE_AGRUPADOR . "_elemento";
                        $groups     = TABLE_AGRUPADOR;

                        $relationKey = "SELECT uid_agrupador_elemento
                        FROM {$assigns} sub
                        WHERE sub.uid_modulo = {$this->getModuleId()}
                        AND sub.uid_elemento = {$this->getUID()}
                        AND sub.uid_agrupador = ae.uid_elemento";

                        $sql = "INSERT IGNORE INTO {$assigns}_agrupador (uid_agrupador_elemento, uid_agrupador)
                        SELECT
                            ({$relationKey}) as uid_agrupador_elemento,
                            ae.uid_agrupador
                        FROM {$assigns} ae
                        INNER JOIN {$groups}
                        ON agrupador.uid_agrupador = ae.uid_agrupador
                        WHERE 1
                        AND ae.uid_elemento = {$asignado->getUID()}
                        AND uid_modulo = 11
                        AND agrupador.uid_agrupamiento IN ({$list})
                        ";

                        if( !$this->db->query($sql) ){
                            echo "Ocurrió algun error al asignar el anclaje<br />";
                        } else {
                            $num = $this->db->getAffectedRows();

                            // Controlar los rebotes
                            $asignadosRelacion = $asignado->obtenerAgrupadoresRelacionados($this, reset($agrupamientos) );
                            foreach( $asignadosRelacion as $asignadoRelacion ){
                                $this->asignarAgrupadores( elemento::getCollectionIds($asignadoRelacion->obtenerAgrupadores()), false, $asignadoRelacion->getUID() );
                                //$num += count($asignados);
                            }

                            //
                            // if( $num ) echo "$num elemento(s) asignados para <strong>". $asignado->getUserVisibleName()."</strong><br /><br />";
                        }
                    }
                }
            }


            if( $this instanceof agrupador ){
                $this->aplicarRebotes();
            }

            return new ArrayAgrupadorList($asignados);
        } else {
            return false;
        }
    }

    public function estadoAgrupador(agrupador $agrupador){
        $sql = "SELECT SUM(total.num) FROM (SELECT count(uid_agrupador_elemento) as num
                    FROM ". TABLE_AGRUPADOR ."_elemento
                    WHERE uid_agrupador = ". $agrupador->getUID() ."
                    AND uid_elemento = ". $this->getUID() ."
                    AND uid_modulo = ". $this->getModuleId() ."

                    UNION

                    SELECT count(ae.uid_agrupador_elemento) as num
                    FROM ". TABLE_AGRUPADOR ."_elemento ae
                    INNER JOIN ". TABLE_AGRUPADOR ."_elemento_agrupador aea
                    USING(uid_agrupador_elemento)
                    WHERE aea.uid_agrupador = ". $agrupador->getUID() ."
                    AND uid_elemento = ". $this->getUID() ."
                    AND uid_modulo = ". $this->getModuleId() ."
                ) as total
        ";

        $result = $this->db->query($sql, 0, 0);
        return ( $result ) ? true : false;
    }


    public function obtenerAgrupamientosConRebotes($usuario, $condicion=false, $self=true){
        $coleccion = $this->obtenerAgrupadores(null, $usuario, false, $condicion);
        $list = $coleccion->toIntList()->getArrayCopy();
        if( !count($list) ){ return array(); }

        $sql = "
            SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ."_agrupador
            WHERE uid_agrupador IN ( ". implode(",", $list) . ")
        ";

        if( !$self ){
            $sql .= " AND uid_agrupamiento NOT IN ( SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ."_agrupador WHERE uid_agrupador = ".$this->getUID()." )  ";
        }

        $sql .= "GROUP BY uid_agrupamiento";



        $agrupamientos = $this->db->query($sql, "*", 0, "agrupamiento");
        return $agrupamientos;
    }


    /** NOS RETORNA UN ARRAY DE OBJETOS AGRUPAMIENTO QUE TENGAN AL MENOS UN AGRUPADOR ASIGNADO A ESTE ELEMENTO */
    public function obtenerAgrupamientosAsignados(
        $usuario = false,
        $includeRelations = false,
        $categories = [],
        $excludeCategories = false,
        $forceCurrentClient = false
    ) {
        $list = $this->obtenerAgrupadores(false, $usuario);

        if (count($list)) {
            // Si queremos incluir a los agrupadores por relacion para conocer los agrupamientos a los que pertenecen
            if ($includeRelations) {
                if ($relations = $list->obtenerAgrupadoresRelacionados($this)) $list = $list->merge($relations)->unique();
            }

            $conjunto = $list->toComaList();
            $sql = "SELECT uid_agrupamiento FROM ". TABLE_AGRUPADOR ." WHERE uid_agrupador IN ($conjunto)";

            if ($categories && count($categories)) {
                if (is_numeric($categories)) $categories = new ArrayIntList([$categories]);
                if (is_array($categories)) $categories = new ArrayIntList($categories);


                $kinds = "SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ." WHERE uid_categoria IN ({$categories})";

                if ($excludeCategories) {
                    $sql .= " AND uid_agrupamiento NOT IN ($kinds)";
                } else {
                    $sql .= " AND uid_categoria IN ($kinds)";
                }
            }

            $sql .= " GROUP BY uid_agrupamiento";
            $coleccion = $this->db->query($sql, '*', 0, 'agrupamiento');
            return new ArrayAgrupamientoList($coleccion);
        }

        return new ArrayAgrupamientoList;
    }


    public function lockAll(agrupamiento $agrupamiento, $lock = true ){
        $sql = "
            UPDATE ". TABLE_AGRUPADOR ."_elemento INNER JOIN ". TABLE_AGRUPAMIENTO ."_agrupador USING( uid_agrupador )
            SET bloqueado = ". (int) $lock ."
            WHERE uid_elemento = ". $this->getUID() ."
            AND uid_modulo = ". $this->getModuleId() ."
            AND uid_agrupamiento = ". $agrupamiento->getUID() ."
        ";
        $result =  $this->db->query($sql);
        //dump($this->db);exit;
        return $result;
    }

    public function isAllLocked(agrupamiento $agrupamiento){
        $sql = "
            SELECT count(uid_agrupador_elemento)
            FROM ". TABLE_AGRUPADOR ."_elemento INNER JOIN ". TABLE_AGRUPAMIENTO ."_agrupador USING( uid_agrupador )
            WHERE uid_elemento = ". $this->getUID() ."
            AND uid_modulo = ". $this->getModuleId() ."
            AND uid_agrupamiento = ". $agrupamiento->getUID() ."
            AND bloqueado = 0
        ";
        return $this->db->query($sql, 0, 0) ? false : true;
    }


    /**
        RETORNA UNA COLECCION DE OBJETOS elementorelacion CUYO FIN ES PARAMETRIZAR LAS RELACIONES DE UN ELEMENTO
        CON SUS --- POSIBLES --- AGRUPADORES
    **/
    public function obtenerParametrosDeRelacion($filtro=false){
        // , uid_elemento, uid_modulo, uid_agrupador
        $sql = "SELECT uid_elemento_relacion
                FROM ". TABLE_ELEMENTO_RELACION ."
                WHERE uid_elemento = ". $this->getUID() ."
                AND uid_modulo = ". $this->getModuleId() ."
        ";

        if( $filtro instanceof agrupador ){
            $sql .= " AND uid_agrupador = ". $filtro->getUID();
        }

        $coleccion = array();
        $list = $this->db->query($sql, "*", 0);
        foreach( $list as $uid ){
            $coleccion[] = new elementorelacion($uid);
        }

        return $coleccion;
    }

    public function getStartDate(agrupador $agrupador){
        $sql = "SELECT if (fecha_inicio, DATE_FORMAT(fecha_inicio, '%d/%m/%Y'), NULL) as fecha FROM ". TABLE_AGRUPADOR ."_elemento
            WHERE uid_elemento = ". $this->getUID() ."
            AND uid_modulo = ". $this->getModuleId() ."
            AND uid_agrupador = ". $agrupador->getUID() ."
            AND fecha_inicio
            ";

        return $this->db->query($sql, 0, 0);
    }

    public function getAssignExpirationDate(agrupador $agrupador){
        $sql = "SELECT DATE_FORMAT(DATE_ADD(fecha_inicio, INTERVAL duracion DAY), '%d/%m/%Y') FROM ". TABLE_AGRUPADOR ."_elemento
            WHERE uid_elemento = ". $this->getUID() ."
            AND uid_modulo = ". $this->getModuleId() ."
            AND uid_agrupador = ". $agrupador->getUID() ."
            AND duracion AND fecha_inicio
            ";

        $duracion = $this->db->query($sql, 0, 0);
        return $duracion;
    }


    public function getDuracionValue(agrupador $agrupador){
        $sql = "SELECT duracion FROM ". TABLE_AGRUPADOR ."_elemento
            WHERE uid_elemento = ". $this->getUID() ."
            AND uid_modulo = ". $this->getModuleId() ."
            AND uid_agrupador = ". $agrupador->getUID() ."";

        $duracion = $this->db->query($sql, 0, 0);
        return $duracion;
    }

    public function setDuracionValue(agrupador $agrupador, $duracion, $startDate){
        $duracion = db::scape($duracion);
        $startDate = db::scape($startDate);

        if (is_numeric($timestamp = documento::parseDate($startDate))) {
            $startDate = date('Y-m-d', $timestamp);
        }

        $sql = "UPDATE ". TABLE_AGRUPADOR ."_elemento SET duracion = $duracion, fecha_inicio = '{$startDate}'
            WHERE uid_elemento = ". $this->getUID() ."
            AND uid_modulo = ". $this->getModuleId() ."
            AND uid_agrupador = ". $agrupador->getUID() ."";

        if ($this->db->query($sql)) {
            return $this->db->getAffectedRows() ? true : null;
        }

        return false;
    }


    /**
      * Elimina todas las asignaciones de los agrupadores que son propriedad de la empresa @param en este item
      *
      */
    public function quitarAgrupadoresPropiedadDe(empresa $empresa){
        $idmodulo = $this->getModuleId();
        $SQL = "DELETE ". TABLE_AGRUPADOR ."_elemento FROM ". TABLE_AGRUPADOR ."_elemento INNER JOIN ". TABLE_AGRUPADOR ." USING(uid_agrupador)
        WHERE uid_modulo = $idmodulo AND uid_elemento = {$this->getUID()} AND uid_empresa = {$empresa->getUID()}";
        return $this->db->query($SQL);
    }

    public function obtenerAccionesRelacion(agrupamiento $agrupamiento, Iusuario $user){
        return array();
    }


    /***
       * Return ArrayObjectList of the assigments of this items
       *
       * @user - the viewer user
       * @opts - array with key value to handle variable filters and options
       *
       *
       */
    public function getAssignments (Iusuario $user, $opts = []) {
        $assigments = new ArrayAssignmentList;
        $table      = TABLE_AGRUPADOR . '_elemento INNER JOIN ' . TABLE_AGRUPADOR . ' a USING (uid_agrupador)';

        $SQL        = "SELECT uid_agrupador_elemento as uid, uid_agrupador, rebote, a.nombre, bloqueado
        FROM {$table}
        INNER JOIN " . TABLE_AGRUPAMIENTO . "
        USING(uid_agrupamiento)
        WHERE 1";
        $org        = isset($opts['organization']) ? $opts['organization'] : null;
        $group      = isset($opts['group']) ? $opts['group'] : null;
        $where      = [];

        $where[] = "uid_elemento = {$this->getUID()}";
        $where[] = "uid_modulo = {$this->getModuleId()}";
        $where[] = "papelera = 0";

        if ($group) {
            if ($group instanceof ArrayObjectList && count($group) === 0) {
                return $assigments;
            }

            $groupList  = $group instanceof ArrayObjectList ? $group->toComaList() : $group->getUID();
            $where[]    = "uid_agrupador IN ({$groupList})";
        }

        if ($user instanceof Iusuario) {
            if ($user->isViewFilterByGroups()) {
                $userGroups = $user->obtenerAgrupadores();

                // the query should return nothing, so skip it
                if (count($userGroups) === 0) {
                    return $assigments;
                }

                $userCompany = $user->getCompany();

                $where[] = "(
                    (
                        (uid_agrupador IN ({$userGroups->toComaList()})
                        AND a.uid_empresa = {$userCompany->getUID()}
                        AND config_filter = 1
                        )
                        OR
                        (a.uid_empresa = {$userCompany->getUID()}
                        AND config_filter = 0)
                        )
                        OR (
                        a.uid_empresa != {$userCompany->getUID()}
                    )
                )";
            }

            if ($user->isViewFilterByLabel()) {
                $userLabels = $user->obtenerEtiquetas();
                $labelsTable = TABLE_AGRUPADOR . '_etiqueta';
                $userCompany = $user->getCompany();
                $baseUserFilter = "uid_agrupamiento IN (
                    SELECT uid_agrupamiento
                    FROM agd_data.agrupamiento org
                    WHERE org.uid_empresa != {$userCompany->getUID()}
                    OR (
                        org.uid_empresa = {$userCompany->getUID()}
                        AND org.config_filter = 0
                    )
                )";

                // check both, old api
                if (count($userLabels) == 0 || $userLabels == false) {
                    $groupsWithLabel = "SELECT uid_agrupador FROM {$labelsTable} WHERE uid_agrupador = a.uid_agrupador";
                    $where[] = "({$baseUserFilter} OR uid_agrupador NOT IN ({$groupsWithLabel}))";
                } else {
                    $groupsSameLabel = "SELECT uid_agrupador FROM {$labelsTable} WHERE uid_etiqueta IN ({$userLabels->toComaList()})";
                    $where[] = "({$baseUserFilter} OR uid_agrupador IN ({$groupsSameLabel}))";
                }
            }

            // if we have to filter with an organization...
            if ($org instanceof agrupamiento) {
                $where[] = "a.uid_agrupamiento = {$org->getUID()}";

                $userCompany = $user->getCompany();
                $orgCompany  = $org->getCompany();

                if ($org->tieneJerarquia() && false === $orgCompany->compareTo($userCompany)) {
                    $groups = new ArrayObjectList;

                    if ($userCompany->esCorporacion()) {
                        $corpCompanies = $userCompany->obtenerEmpresasInferiores();
                        foreach ($corpCompanies as $child) {
                            $groups = $child->obtenerAgrupadores()->merge($groups);
                        }
                    } else {
                        $groups = $userCompany->obtenerAgrupadores();
                    }


                    $relations  = $groups->obtenerAgrupadoresRelacionados($userCompany);
                    $groups     = $groups->merge($relations);

                    if (count($groups) === 0) {
                        return $assigments;
                    }

                    $originCompanies = $userCompany->getOriginCompanies();
                    $where[] = "(uid_agrupador IN ({$groups->toComaList()}) OR a.uid_empresa IN ({$originCompanies->toComaList()}))";
                }
            }
        }

        if (count($where)) {
            $SQL .= " AND " . implode(" AND ", $where);
        }


        if ($rows = $this->db->query($SQL, true)) {
            foreach ($rows as $row) {
                $uid        = $row['uid'];
                $groupId    = $row['uid_agrupador'];
                $bounce     = $row['rebote'];

                $group      = new agrupador($groupId);
                $assigment  = new Assignment($uid);

                // performance
                $group->name            = $group->getUserVisibleName();
                $assigment->group       = $group;
                $assigment->element     = $this;
                $assigment->locked      = (bool) $row['bloqueado'];

                if ($bounce) {
                    $assigment->bounce = new agrupador($bounce);
                }

                $assigments[] = $assigment;
            }
        }

        return $assigments;
    }


    /***
       * Return the related Assigment of this item base on $group param or false
       *
       * @group - the group to compare
       *
       *
       */
    public function getRelatedAssignment (agrupador $group, agrupador $direct = NULL) {
        $assigments = TABLE_AGRUPADOR. "_elemento";
        $relations  = TABLE_AGRUPADOR. "_elemento_agrupador";

        $assigned = "SELECT uid_agrupador_elemento FROM {$assigments}
        WHERE 1
        AND uid_elemento    = {$this->getUID()}
        AND uid_modulo      = {$this->getModuleId()}
        ";

        if ($direct instanceof agrupador) {
            $assigned .= " AND uid_agrupador = {$direct->getUID()}";
        }

        $sql = "SELECT uid_agrupador FROM {$relations} r
        WHERE uid_agrupador_elemento IN ({$assigned})
        AND uid_agrupador = {$group->getUID()}
        LIMIT 1
        ";

        if ($id = $this->db->query($sql, 0, 0)) {
            $assigment = new Assignment($id);

            // performance only
            $assigment->group   = $group;
            $assigment->element = $this;

            return $assigment;
        }

        return false;
    }


    /***
       * Return Assigment of this item base on $group param or false
       *
       * @group - the group to compare
       *
       *
       */
    public function getAssignment (agrupador $group) {

        $sql = "SELECT uid_agrupador_elemento
        FROM ". TABLE_AGRUPADOR. "_elemento ae
        INNER JOIN ". TABLE_AGRUPADOR." a using(uid_agrupador)
        WHERE uid_elemento = {$this->getUID()}
        AND uid_modulo = {$this->getModuleId()}
        AND uid_agrupador = {$group->getUID()}";

        if ($this instanceof childItemEmpresa) {
            $module = $this->getModuleName();
            $table  = constant('TABLE_' . strtoupper($module)) . '_visibilidad';

            $visibility = "uid_elemento IN (
                SELECT uid_$module
                FROM $table tmp WHERE
                (
                    tmp.uid_empresa IN (
                        SELECT uid_empresa_inferior as empresa FROM ". TABLE_EMPRESA ."_relacion relacion
                        INNER JOIN ". TABLE_EMPRESA ." empresa ON relacion.uid_empresa_superior = empresa.uid_empresa
                        WHERE relacion.uid_empresa_superior = a.uid_empresa
                        AND relacion.papelera = 0
                        AND empresa.activo_corporacion = 1
                    ) OR
                    tmp.uid_empresa = a.uid_empresa
                )
                AND uid_$module = uid_elemento
            )";

            $table  = constant('TABLE_' . strtoupper($module)) . '_empresa';
            $own = "uid_elemento IN (
                SELECT uid_$module
                FROM $table tmp WHERE
                tmp.uid_empresa = a.uid_empresa
                AND uid_$module = uid_elemento
                AND papelera = 0
            )";

            $corp = "uid_elemento IN (
                SELECT uid_$module
                FROM $table tmp
                INNER JOIN ". TABLE_EMPRESA ."_relacion relacion ON relacion.uid_empresa_inferior = tmp.uid_empresa
                INNER JOIN ". TABLE_EMPRESA ." empresa ON relacion.uid_empresa_superior = empresa.uid_empresa
                WHERE relacion.uid_empresa_superior = a.uid_empresa
                AND uid_$module = uid_elemento
                AND tmp.papelera = 0
                AND empresa.activo_corporacion = 1
            )";

            $sql .= " AND ($visibility OR $own OR $corp)";
        }

        if ($id = $this->db->query($sql, 0, 0)) {
            $assigment = new Assignment($id);

            // performance only
            $assigment->group   = $group;
            $assigment->element = $this;

            return $assigment;
        }

        return false;
    }


    public function canApplyOnDemand (agrupamiento $organizartion, Iusuario $user) {
        return false;
    }


    public function getMandatoryOrganizations (Iusuario $user = NULL) {
        $organizations      = new ArrayAgrupamientoList;
        $companies          = $this->obtenerEmpresasSolicitantes($user);
        $elementCompany     = ($this instanceof empresa) ? $this : $this->getCompany($user);
        $module             = $this->getModuleName();


        if ($user) {
            $userCompany        = $user->getCompany();
            $startList          = $userCompany->getStartList();
            $isSelf             = $startList->contains($this);
            $isCorp             = $userCompany->esCorporacion();

            // for corps, we need to check the clients of every group company
            if ($isCorp) {
                $userRequesters = new ArrayObjectList;
                foreach ($startList as $company) {
                    $companyRequesters  = $company->obtenerEmpresasSolicitantes($user);
                    $userRequesters     = $userRequesters->merge($companyRequesters);
                }

                $userRequesters = $userRequesters->unique();
            } else {
                $userRequesters = $userCompany->obtenerEmpresasSolicitantes($user);
            }
        }

        foreach ($companies as $company) {
            if ($company->esCorporacion()) {
                continue;
            }

            // if user is present...
            if ($user) {

                // match the requesters
                if ($userRequesters->contains($company) === false) {
                    continue;
                }

                if ($this instanceof empresa && true === $this->esContrata($company)) {
                    $organizationsCompany = $company->obtenerAgrupamientosVisibles([
                        'mandatory',
                        'modulo' => $module,
                        'self_assignable' => '1',
                        $this,
                    ]);

                    if (count($organizationsCompany) > 0) {
                        $organizations = $organizations->merge($organizationsCompany);
                    }
                    continue;
                }

                if ($this instanceof signinRequest && $this->getCompany()->compareTo($company)) {
                    $organizationsCompany = $company->obtenerAgrupamientosVisibles([
                        'mandatory',
                        'modulo' => $module,
                        'self_assignable' => '0',
                        $company,
                    ]);

                    if (count($organizationsCompany) > 0) {
                        $organizations = $organizations->merge($organizationsCompany);
                    }

                    continue;
                }

                // if we are asking for ourselves
                if ($isSelf) {
                    $origin = $isCorp ? $startList : $userCompany->getOriginCompanies();

                    // if the company is not us, we cannot do anything, continue
                    if ($origin->contains($company) === false) {
                        continue;
                    }
                }
            }




            $organizationsCompany = $company->obtenerAgrupamientosVisibles(['mandatory', 'modulo' => $module, $elementCompany]);

            if ($organizationsCompany) {
                $organizations = $organizations->merge($organizationsCompany);
            }
        }

        return $organizations->unique();
    }

    public function getPendingAssignments (Iusuario $user = NULL, $sqlOptions) {
        $modules = agrupamiento::getModulesToApplyMandatory();
        if ($modules->contains(strtolower($this->getModuleName())) == false) {
            return false;
        }

        $groupsSet      = $this->obtenerAgrupadores();
        $orgsAssigned   = $groupsSet->toOrganizationList();
        $organizations  = $this->getMandatoryOrganizations($user);
        $pending        = $organizations->diff($orgsAssigned);

        if (isset($sqlOptions['count'])) return count($pending);
        return $pending;
    }

    public function getPendingSelfAssignableAssignments(Iusuario $user = null, $sqlOptions)
    {
        $modules = agrupamiento::getModulesToApplyMandatory();
        if (false === $modules->contains(strtolower($this->getModuleName()))) {
            return false;
        }

        $groupsSet = $this->obtenerAgrupadores();
        $orgsAssigned = $groupsSet->toOrganizationList();
        $organizations = $this->getMandatoryOrganizations($user);
        $pending = $organizations->diff($orgsAssigned);

        $selfAssignPending = new ArrayObjectList;

        foreach ($pending as $orgPending) {
            if ($orgPending->obtenerDato('self_assignable') == 1) {
                $selfAssignPending[] = $orgPending;
            }
        }

        if (true === isset($sqlOptions['count'])) {
            return count($selfAssignPending);
        }

        return $selfAssignPending;
    }

    /***
       * Indicates if we have to show organizations marked as 'ondemand'
       *
       */
    public function canShowOnDemand () {
        return false;
    }

    /***
       * Indicates if we have to hide empty organizations
       *
       */
    public function filterEmptyOrganizations () {
        return true;
    }

    /**
     * Return true if a group has been suggested to the element by the company given as a second parameter
     * @param  $group agrupador
     * @param  $company empresa
     *
     * @return bool
     */
    public function hasSuggested (agrupador $group, empresa $company) {

        $sql = "SELECT uid_empresa_solicitud
        FROM ". TABLE_EMPRESA ."_solicitud
        WHERE uid_empresa_origen    = {$company->getUID()}
        AND uid_elemento = {$this->getUID()}
        AND uid_modulo = {$this->getModuleId()}
        AND type = '" . empresasolicitud::TYPE_ASIGNAR . "'
        AND estado = '" . empresasolicitud::ESTADO_CREADA . "'
        AND data LIKE '%{$group->getUID()}%'";

        if ($this->db->query($sql, 0, 0)) {
            return true;
        }

        return false;
    }
}

