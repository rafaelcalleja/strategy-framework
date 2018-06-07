<?php

abstract class childItemEmpresa extends solicitable
{
    public static function getModules()
    {
        return array(
            8 => 'empleado',
            14 => 'maquina'
        );
    }

    public function isTransferable()
    {
        $module = $this->getModuleName();
        $table  = $this->tabla;

        $SQL = "SELECT count(uid_empresa) FROM {$table}_empresa WHERE papelera = 0 AND uid_{$module} = {$this->getUID()}";
        $num = $this->db->query($SQL, 0, 0);

        // si hay 0 o no hay resultados en esta tabla
        return !$num;
    }


    /***
       * Called from buscador.class when search for employees or machines with "empresa" filter
       *
       *
       *
       */
    public static function onSearchByCompany($data, $filter, $param, $query, $trash)
    {
        $value  = reset($filter);
        $SQL    = false;
        $class  = get_called_class();
        $table  = constant('TABLE_' . strtoupper($class));

        if (is_numeric($value)) {
            $companies = "SELECT uid_{$class} FROM {$table}_empresa WHERE uid_empresa = {$value} AND papelera = 0";
        } else {
            $companies = TABLE_EMPRESA;
            $companies = "SELECT uid_{$class}
            FROM {$table}_empresa
            INNER JOIN {$companies} e USING (uid_empresa)
            WHERE 1
            AND papelera = 0
            AND (e.nombre LIKE '%{$value}%' OR e.cif LIKE '%{$value}%')";
        }

        $SQL  = "{$table}.uid_{$class} IN ({$companies})";

        return $SQL;
    }


    /***
       * Called from buscador.class when search for employees or machines with "own" filter
       *
       *
       *
       */
    public static function onSearchByOwn($data, $filter, $param, $query)
    {
        $value  = reset($filter);

        if ('false' !== $value) {
            return true;
        }

        if (empty($data['usuario'])) {
            return false;
        }

        if (false === ($user = $data['usuario']) instanceof usuario) {
            return false;
        }

        $class          = get_called_class();
        $table          = constant('TABLE_' . strtoupper($class));
        $userCompany    = $user->getCompany();
        $allButMines    = $userCompany->getForeignChildsSqlFilter($class, $user);



        $SQL  = "{$table}.uid_{$class} IN ({$allButMines})";

        return $SQL;
    }

    /** DEJAR DE TRABJAR PARA UNA EMPRESA, (ENVIAR A LA PAPELERA) */
    public function enviarPapelera($parent, usuario $usuario)
    {
        if ($parent instanceof empresa) {
            $parent = $parent->getUID();
        }

        // Update relation table to set papelera=1
        $enPapelera = $this->actualizarRelacion($this->tabla."_empresa", "papelera", 1, "uid_{$this->tipo}", "uid_empresa", $parent);

        // To avoid get the item companies from cache when update requests
        $this->cache->delete("empresa-{$this}--");

        if ($enPapelera) {
            $SQL = "DELETE FROM {$this->tabla}_visibilidad WHERE uid_{$this->tipo} = {$this->getUID()} AND uid_empresa_referencia = {$parent}";
            if (!$this->db->query($SQL)) {
                error_log("No se puede borrar la visibilidad del empleado {$this->getUID()} para la empresa {$parent}");
            }
        } else {
            error_log("No se ha podido enviar a la papelera el {$this->tipo} uid:{$this->getUID()} para la empresa {$parent}");
        }

        return $enPapelera;
    }

    /**
     * [onRequestResponse se ejecuta desde los metodos aceptar/rechazar/cancelar de solicitud para que el elemento pueda reaccionar a la respuesta de la solicitud]
     * @param  solicitud $solicitud [la solicitud cuyo estado acaba de cambiar]
     * @param  Iusuario  $usuario   [usuario que la ejecuta]
     * @return bool               [si la modificación desencadenada por la solicitud se realizó correctamente]
     */
    public function onRequestResponse(solicitud $solicitud, $usuario = null)
    {
        $class = get_class($this);
        $estadoSolicitud = $solicitud->getState();
        switch ($solicitud->getTypeOf()) {
            case solicitud::TYPE_TRANSFERENCIA:
                // si está rechazada, no hacemos nada más
                if ($estadoSolicitud == solicitud::ESTADO_RECHAZADA) {
                    return true;
                }

                $empresaDestino = $solicitud->getSolicitante();
                // interpretamos aceptar la solicitud como 'ceder' y rechazarla como 'compartir'
                if (isset($usuario)) {
                    $userCompany = $trashCompany = $usuario->getCompany();
                    $targetCompany  = $solicitud->getCompany();

                    if ($estadoSolicitud == solicitud::ESTADO_ACEPTADA) {
                        if ($userCompany->esCorporacion()) {
                            $corp           = $targetCompany->perteneceCorporacion();

                            if ($corp && $corp->compareTo($userCompany)) {
                                $trashCompany = $targetCompany;
                            }
                        }

                        if (!$this->enviarPapelera($trashCompany, $usuario)) {
                            return false;
                        }

                        $this->writeLogUI(logui::ACTION_TRANSFER, 'uid_empresa_origen = ' . $targetCompany->getUID() . ',uid_empresa_destino = ' . $empresaDestino->getUID(), $usuario);
                    } else {
                        $this->writeLogUI(logui::ACTION_SHARE, 'uid_empresa_origen = ' . $targetCompany->getUID() . ',uid_empresa_destino = ' . $empresaDestino->getUID(), $usuario);
                    }
                } else {
                    // viene el empleado desde email a aceptar/rechazar
                    $this->writeLogUI(logui::ACTION_SHARE, 'uid_empresa_destino = ' . $empresaDestino->getUID(), $this);
                }


                // si no está cancelada
                $asignacionOk   = $this->asignarEmpresa($empresaDestino);

                // SACAMOS LOS AGRUPAMIENTOS CON REPLICA Y VEMOS QUE ELEMENTOS TIENEN ASIGNADOS PARA ASIGNARSELOS AL EMPLEADO
                if ($asignacionOk && in_array($class, agrupamiento::getModulesReplicables())) {
                    $requesterUser = $solicitud->getUser();
                    $agrupamientos = $empresaDestino->obtenerAgrupamientosPropios([$requesterUser]);

                    foreach ($agrupamientos as $agrupamiento) {
                        if ($agrupamiento->configValue("replica_empleado")) {
                            $agrupamiento->asignarAgrupamientosAsignadosConReplica($empresaDestino, $this, $requesterUser);
                        }
                    }
                }

                $this->actualizarSolicitudDocumentos();

                return $asignacionOk;
            break;
            case solicitud::TYPE_ASIGNAR:
                if ($estadoSolicitud == solicitud::ESTADO_ACEPTADA) {
                    $agrupadoresRecienAsignados = $this->asignarAgrupadores($solicitud->getValue(), $usuario);

                    if ($usuario) {
                        foreach ($agrupadoresRecienAsignados as $group) {
                            if (!$assignment = $this->getAssignment($group)) {
                                continue;
                            }

                            $entity         = $assignment->asDomainEntity();
                            $userEntity     = $usuario->asDomainEntity();
                            $companyEntity  = $usuario->getCompany()->asDomainEntity();

                            $event = new \Dokify\Application\Event\Assignment\Store($entity, $companyEntity, $userEntity);
                            $this->app->dispatch(\Dokify\Events::POST_ASSIGNMENT_STORE, $event);
                        }
                    }

                    $this->actualizarSolicitudDocumentos();
                }
                return true;
            break;
        }
        return false;
    }

    public function getConnectionDegrees(empresa $company)
    {
        $companies = $this->getCompanies();
        $connection = array();

        if ($companies->contains($company)) {
            $connection[] = 0;
        }

        foreach ($companies as $itemCompany) {
            if ($this->esVisiblePara($company, $itemCompany)) {
                $connection = array_merge($connection, $company->getConnectionDegrees($itemCompany));
            }
        }
        return $connection;
    }

    /***
       * Return the ArrayObjectLiat with the client companies for this item, but only those the $user can see
       *
       * @param Iusuario $user - The user filter
       * @param $requesting (bool) - If we show the clients who request something
       *
       *
       */
    public function getClientCompanies(Iusuario $user, $requesting = null)
    {
        $userCompany    = $user->getCompany();
        $companyClients = $userCompany->getClientCompanies($user)->merge($userCompany);
        $companies      = $this->getCompanies(false, $user);

        $clients = new ArrayObjectList;
        foreach ($companies as $company) {
            foreach ($this->getClientsByCompany($company) as $client) {
                if ($companyClients->contains($client) == false) {
                    continue;
                }

                // skip corps
                if ($client->esCorporacion()) {
                    continue;
                }

                // if asking only for those who request
                if ($requesting == true) {
                    if ($client->countOwnDocuments() == 0 && $client->countCorpDocuments() == 0) {
                        continue;
                    }
                }

                $clients[] = $client;
            }
        }

        return $clients->unique();
    }

    public function getClientsByCompany(empresa $company)
    {
        $sql = "SELECT uid_empresa
                FROM {$this->tabla}_visibilidad
                WHERE 1
                AND uid_{$this->tipo} = {$this->getUID()}
                AND uid_empresa_referencia = {$company->getUID()}
        ";

        $companies = $this->db->query($sql, "*", 0, "empresa");

        return $companies;
    }

    public function obtenerAccionesRelacion(agrupamiento $agrupamiento, Iusuario $usuario)
    {
        $tpl = Plantilla::singleton();
        $acciones = array();
        $show = false;

        $empresas = $this->getCompanies();
        $empresaUsuario = $usuario->getCompany();

        foreach ($empresas as $empresa) {
            if ($empresaUsuario->esCorporacion()) {
                if ($empresaUsuario->getStartIntList()->contains($empresa->getUID())) {
                    $show = true;
                    break;
                }
            } else {
                if ($empresaUsuario->compareTo($empresa)) {
                    $show = true;
                    break;
                }
            }
        }

        if ($show) {
            $acciones[] = array(
                "innerHTML" => $tpl("configurar_aspectos_relacion"),
                "className" => "box-it",
                "img" => RESOURCES_DOMAIN . "/img/famfam/cog_edit.png",
                "href" => "asignacion.php?m={$this->tipo}&poid=". $this->getUID()."&oid=%s&o=". $agrupamiento->getUID()
            );
        }

        return $acciones;
    }

    public function inTrash($parent)
    {
        $sql = " SELECT papelera FROM {$this->tabla}_empresa WHERE uid_{$this->tipo} = {$this->getUID()} AND uid_empresa = {$parent->getUID()} ";
        return !!$papelera = $this->db->query($sql, 0, 0);
    }

    public function restaurarPapelera($parent, usuario $usuario)
    {
        if ($parent instanceof empresa) {
            $parent = $parent->getUID();
        }

        return $this->actualizarRelacion($this->tabla."_empresa", "papelera", 0, "uid_{$this->tipo}", "uid_empresa", $parent);
    }

    public function esVisiblePara(empresa $empresa, empresa $empresaReferencia = null)
    {

        $match = ($empresaReferencia instanceof empresa) ? $empresaReferencia->getUID() : $this->getCompanies()->toIntList();
        if ($empresa->getStartIntList()->match($match)) {
            return true;
        }

        $empresasReferencia = false;

        if ($empresaReferencia instanceof empresa && $empresaReferencia->esCorporacion()) {
            $empresasReferencia = $empresaReferencia->obtenerIdEmpresasInferiores()->toComaList();
        } elseif ($empresaReferencia instanceof empresa) {
            $empresasReferencia = $empresaReferencia->getUID();
        }

        $SQL = "SELECT count(uid_{$this->tipo}_visibilidad) FROM {$this->tabla}_visibilidad WHERE 1
        AND uid_{$this->tipo} = {$this->getUID()} AND uid_empresa IN ({$empresa->getStartIntList()->toComaList()})";

        if (true === is_countable($empresasReferencia) && 0 < count($empresasReferencia)) {
            $SQL .= " AND uid_empresa_referencia IN ($empresasReferencia)";
        }

        return (bool) $this->db->query($SQL, 0, 0);
    }

    public function eliminarVisibilidad(empresa $empresaReferencia)
    {
        $SQL = "DELETE FROM {$this->tabla}_visibilidad WHERE uid_{$this->tipo} = {$this->getUID()} AND uid_empresa_referencia = {$empresaReferencia->getUID()}";
        return (bool) $this->db->query($SQL);
    }

    public function hacerVisiblePara(empresa $empresa, empresa $empresaReferencia)
    {
        $SQL = "INSERT IGNORE INTO {$this->tabla}_visibilidad (uid_{$this->tipo}, uid_empresa, uid_empresa_referencia)
            VALUES ({$this->getUID()}, {$empresa->getUID()}, {$empresaReferencia->getUID()})";
        return (bool) $this->db->query($SQL);
    }

    public function obtenerElementosActivables(usuario $usuario = null, $all = false)
    {
        if ($usuario instanceof usuario && !$all) {
            $empresasCorporacion = $usuario->getCompany()->getStartList();
            $empresasEmpleado = $this->getCompanies();

            $elementosActivables = new ArrayObjectList;
            foreach ($empresasEmpleado as $empresa) {
                if ($empresasCorporacion->contains($empresa)) {
                    $elementosActivables[] = $empresa;
                }
            }

            return $elementosActivables;
        }

        return $this->getCompanies();
    }

    /* Obtener todas las empresas de este personaje... XD
     * Podemos indicar el nivel de recursividad
     */
    public function getCompanies($recursividad = false, $usuario = false)
    {
        $arrayObjetos = array();
        $intList    = $this->obtenerIdEmpresas($recursividad, $usuario);
        $empresas   = $intList->toObjectList("empresa");

        if ($recursividad && is_numeric($recursividad)) {
            $empresasSuperiores = array();
            foreach ($empresas as $empresa) {
                $superiores = $empresa->obtenerEmpresasSuperiores($recursividad, $usuario);
                $empresas = $empresas->merge($superiores);
            }
        }

        return $empresas;
    }

    /** Alias de getCompanies */
    public function obtenerEmpresas($eliminadas = false, $limit = false)
    {
        return $this->getCompanies($eliminadas, $limit);
    }

    /****
    **
     * $param1 - si es numerico, indica nivel de recursividad, si es bool indica incluir o no las papeleras
     * $limit - si es array limitar por paginas, si es usuario, limitar por visibilidad
    **/
    public function obtenerIdEmpresas($param = false, $limit = false)
    {
        $cachestring = "empresa-{$this}-". ($param===null?'null':$param) ."-$limit";
        if (($cacheData = $this->cache->getData($cachestring)) !== null) {
            return ArrayIntList::factory($cacheData);
        }

        $sql = "SELECT uid_empresa FROM {$this->tabla}_empresa WHERE uid_{$this->tipo} = {$this->getUID()} ";
        if (is_bool($param)) {
            $sql .= " AND papelera = " . (int) $param;
        }

        if ($limit instanceof Iusuario) {
            $empresaUsuario = $limit->getCompany();
            $sql .= " ORDER BY uid_empresa = {$empresaUsuario->getUID()} DESC";
        }

        $uids = $this->db->query($sql, "*", 0);


        if ($limit instanceof Iusuario) {
            $empresaUsuario = $limit->getCompany();
            foreach ($uids as $i => $uid) {
                $emp = new empresa($uid);

                if (!$this->esVisiblePara($empresaUsuario, $emp)) {
                    unset($uids[$i]);
                    continue;
                }

                $inTrash = is_bool($param) ? $param : false;
                if (!@$limit->accesoElemento($emp, null, $inTrash) && !$limit->esStaff()) {
                    unset($uids[$i]);
                }
            }
        }

        $intList = new ArrayIntList($uids);

        $this->cache->addData($cachestring, "$intList");
        return $intList;
    }


    public function getCompany(Iusuario $usuario = null)
    {
        $companies = $this->getCompanies(false, $usuario);
        return reset($companies);
    }

    public function getGlobalStatusForClient(empresa $client, Iusuario $user)
    {
        $companies = $this->getCompanies(false, $user);

        foreach ($companies as $company) {
            if ($this->getStatusInCompany($user, $company) === false) {
                return false;
            }

            if ($company->getGlobalStatusForClient($client, $user) === false) {
                return false;
            }
        }

        return true;
    }

    public function getAlertCount(usuario $user)
    {
        $invalidElements    = $this->getAlertReqTypes($user, ['mandatory' => true], 'count');
        $missingAssignments = $this->getPendingAssignments($user, ['count' => true]);
        $count = $invalidElements + $missingAssignments;

        if ($count === 0) {
            $mandatorySummary = $this->getReqTypeSummary(['viewer' => $user, 'mandatory' => 1]);

            if (count($mandatorySummary) === 0) {
                $count = 1;
            }
        }

        return $count;
    }
}
