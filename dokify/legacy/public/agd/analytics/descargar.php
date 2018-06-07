<?php
include( "../../api.php");

if ($uid = obtener_uid_seleccionado()) {
    session_write_close();

    $tpl = Plantilla::singleton();
    $userCompany = $usuario->getCompany();

    if ($userCompany->isFree()) {
        die("<script>top.agd.func.open('payplugins.php?plugin=table2xls');</script>");
    }

    // El item dataexport que hemos seleccionado para descargar
    $dataExport = new dataexport($uid);

    // Su modelo de datos asociado y el módulo al que hace referencia
    $model = $dataExport->getDataModel();
    $modulo = $model->obtenerModuloDatos();
    $tabla = $model->obtenerTabla();
    $owner = $dataExport->getUser();
    $public = $dataExport->isPublic();

    if (!$modulo) {
        error_log("No module for dataexport {$uid}");
        die("<script>alert('". $tpl('error_desconocido') ."');</script>");
    }

    if (!$model || !$model->exists()) {
        die("<script>alert('". $tpl('no_model_found') ."');</script>");
    }

    if (!$model->isOk()) {
        die("<script>alert('". $tpl('elemento_informe_papelera') ."');</script>");
    }

    if (false === $dataExport->dataCriterionsAreOk()) {
        die("<script>alert('". $tpl('missing_data_criterions') ."');</script>");
    }

    if ($owner->compareTo($usuario) || ( $public && $owner->getCompany()->compareTo($userCompany))) {
        // Si es el propio usuario, o siendo un informe publico es un usuario de su empresa ...
    } else {
        die("Inaccesible");
    }

    // Conjunto de sql para extraer los datos y conjunto de nombre de los campos de la tabla temporal respectivamente
    $SQLcolumns = $tableColumns = array();
    // Por cada campo definido en el modelo de datos
    $fields = $model->obtenerModelFields();
    if (0 === count($fields)) {
        die("<script>alert('No se han encontrado resultados')</script>");
    }

    foreach ($fields as $modelField) {
        // Extraemos el campo de datos
        $dataField = $modelField->getDataField();

        // La columna en formato sql para este campo de datos
        $columna = $modelField->getColumn();

        // Si este campo de datos se extrae mediante SQL (POR AHORA TODOS)
        if ($columnSQL = $modelField->getSQL($dataExport, true)) {
            if (strpos($columnSQL, '%s') !== false) {
                //  if %s exists after parse the sql means that the modelfield is not correctly filled.
                die("<script>alert('{$tpl('need_configure_field')} {$dataField->getUserVisibleName()}')</script>");
            }

            // Alamacenamos los datos para la query final
            // si vamos a trabajar con algun campo que contiene 'datediff' queremos un numero entero de días, no una fecha formateada
            if (stripos($columnSQL, 'fecha') !== false && stripos($columnSQL, 'datediff') === false) {
                $columnSQL = "if(
                    (@date := {$columnSQL}) REGEXP ('[0-9]'),
                    date_format(@date,'%d/%m/%Y'),
                    @date
                )";
            }

            /*
            $extraName = '';
            if($param = $modelField->getParamObject()) {
                switch (get_class($param)) {
                    case 'documento_atributo':
                        $extraName = ' - '.utf8_decode($param->getElement()->getUserVisibleName());
                        break;
                    case 'documento': case 'agrupador':
                        // No añadidmos extraName pòrque viene en el propio nnombre de $modelField->getUserVisibleName
                        break;
                    default:
                        # code...
                        break;
                }

            }
            **/

            $columnSQL = $columnSQL . " as '". utf8_decode($modelField->getUserVisibleName(false)) ."'";
            $SQLcolumns[] = $columnSQL ;
            $tableColumns[] = $columna;
        } else {
            die("<script>alert('Error al procesar el campo {$modelField->getUserVisibleName()}')</script>");
        }
    }

    // Si no hay problemas al crear la tabla temporal
    $db = db::singleton();

    $sql = "SELECT ". implode(",", $SQLcolumns) ." FROM {$tabla} ";

    // Vamos a ver si tenemos que cruzar
    if ($join = $dataExport->getJoin()) {
        $sql .= $join;
    }

    $sql .= " WHERE 1";

    // Y si hay criterios para aplicar, los añadimos al WHERE
    if (($criterios = $dataExport->obtenerDataCriterions()) && count($criterios)) {
        $condiciones = array();
        $prevCriterion = $criterios[0]->getDataField()->getUID();
        $sql .= " AND ( ". $criterios[0]->getSQLFilter();
        $criterios = $criterios->slice(count($criterios)-1, 1);

        foreach ($criterios as $criterio) {
            if ($criterio->getDataField()->getUID() == $prevCriterion) {
                $sql .= ' OR '.$criterio->getSQLFilter();
            } else {
                $prevCriterion = $criterio->getDataField()->getUID();
                $sql .= ' ) AND ( '.$criterio->getSQLFilter();
            }
        }
        $sql .= " ) ";
        foreach ($condiciones as $condicion) {
        }
    }

    $intList = buscador::getCompaniesIntList($usuario);
    $listaUIDSempresas = $intList->getArrayCopy();
    if (CURRENT_ENV == 'dev') {
        asort($listaUIDSempresas); // asi es más fácil depurar y no impacta mucho
    }
    $listaUIDSempresas = count($listaUIDSempresas) ? implode(",", $listaUIDSempresas) : 0;


    $startList = $usuario->getCompany()->getStartIntList()->toComaList();

    if ($modulo == "empresa") {
        $list = $listaUIDSempresas;
    } else {
        $viewFilter = null;

        if ($usuario->isViewFilterByGroups()) {
            $viewFilter = " AND ";

            $condicion = $usuario->obtenerCondicion($modulo, "uid_$modulo");

            $viewFilter .= " uid_$modulo IN ({$condicion})";
        }


        $table = constant("TABLE_". strtoupper($modulo));
        $get = "
            SELECT uid_$modulo FROM {$table}_empresa i WHERE 1 AND uid_empresa IN ({$startList}) AND papelera = 0 $viewFilter
            UNION
            SELECT uid_$modulo FROM {$table}_visibilidad i INNER JOIN {$table}_empresa e USING(uid_{$modulo})
            WHERE i.uid_empresa IN ($startList) AND e.uid_empresa IN ($listaUIDSempresas) AND papelera = 0 $viewFilter
        ";

        $arrayUIDS = $db->query($get, "*", 0);

        // Limitar siempre por los items con acceso y no desactivados
        $list = ($arrayUIDS && count($arrayUIDS)) ? implode(",", $arrayUIDS) : 0;
    }

    $sql .= " AND {$tabla}.uid_$modulo IN ($list)";
    $empresaCliente = $usuario->perfilActivo()->getCompany();
    $idEmpresaUsuario = $usuario->getCompany()->getUID();

    $sql = str_replace("<%empresas%>", $listaUIDSempresas, $sql);
    $sql = str_replace('<%empresaCliente%>', $empresaCliente->getUID(), $sql);
    $sql = str_replace('<%root%>', $idEmpresaUsuario, $sql);
    // Lista de empresas iniciales, puede ser diferente de <%root%>
    $sql = str_replace('<%startlist%>', $startList, $sql);

    /*******************************
    ******* CAMPOS ESPECIALES ******
    ********************************/

    // Calcular lista de empresas OK
    if ($dataExport->isUsing("estado_contratacion")) {
        $owners = ($corp = $empresaCliente->perteneceCorporacion()) ? new ArrayObjectList([$corp, $empresaCliente]): $empresaCliente->getStartList();

        $sqlValidas = "
            SELECT uid_empresa FROM ".TABLE_EMPRESA." LEFT JOIN (
                SELECT dee.uid_empresa
                FROM agd_docs.documento_empresa_estado dee
                WHERE uid_empresa IN ($listaUIDSempresas)
                AND uid_empresa_propietaria IN ({$owners->toComaList()})
                AND (estado != 2 OR estado IS NULL)
                AND obligatorio = 1
                AND descargar = 0
                AND referenciar_empresa = 0
                GROUP BY uid_empresa
            ) as invalid USING (uid_empresa)
            WHERE invalid.uid_empresa IS NULL
            AND uid_empresa IN ($listaUIDSempresas)
        ";

        $listaValidas = $db->query($sqlValidas, "*", 0);

        $listaValidas = count($listaValidas) ? implode(",", $listaValidas) : 0;
        $sql = str_replace('<%empresasvalidas%>', $listaValidas, $sql);
    }

    if ($dataExport->isUsing("cadena_contratacion_cumplimentada")) {
        $owners = ($corp = $empresaCliente->perteneceCorporacion()) ? new ArrayObjectList([$corp, $empresaCliente]): $empresaCliente->getStartList();

        $sqlValidas = "
            SELECT uid_empresa FROM ".TABLE_EMPRESA." LEFT JOIN (
                SELECT dee.uid_empresa
                FROM agd_docs.documento_empresa_estado dee
                WHERE uid_empresa IN ($listaUIDSempresas)
                AND uid_empresa_propietaria IN ({$owners->toComaList()})
                AND (estado NOT IN (1,2) OR estado IS NULL)
                AND obligatorio = 1
                AND descargar = 0
                AND referenciar_empresa = 0
                GROUP BY uid_empresa
            ) as invalid USING (uid_empresa)
            WHERE invalid.uid_empresa IS NULL
            AND uid_empresa IN ($listaUIDSempresas)
        ";

        $listaValidas = $db->query($sqlValidas, "*", 0);
        $listaValidas = count($listaValidas) ? implode(",", $listaValidas) : 0;

        $sql = str_replace('<%empresacumplimentadas%>', $listaValidas, $sql);
    }

    /*******************************
    ******* FIN CAMPOS ESPECIALES ******
    ********************************/

    $sql .= " ORDER BY 1";

    set_time_limit(0);

    $fileType = isset($_REQUEST["filetype"]) && class_exists($_REQUEST["filetype"]) ? $_REQUEST["filetype"] : "excel";
    $fileName = archivo::cleanFileNameString($dataExport->getUserVisibleName());
    $showTitles = (bool) $dataExport->obtenerDato("show_titles");

    if ($fileType == 'excel') {
        ini_set('memory_limit', '350M');
        $spreadSheet = new SpreadSheet($sql);

        if ($spreadSheet->getRowCount() === 0) {
            die("<script>alert('No se han encontrado resultados')</script>");
        }

        if ($spreadSheet->getError()) {
            die("<script>alert('No se han encontrado resultados')</script>");
        }

        if ($headers = $dataExport->obtenerExportHeaders()) {
            foreach ($headers as $header) {
                $spreadSheet->addHeader($header->getArrayCopy());
            }
        }

        $spreadSheet->showTitles($showTitles);
        $spreadSheet->send($fileName, 'xls');
    } else {
        $exportacion = new $fileType($sql);

        ob_start();
        if (!$exportacion->Generar($fileName, $showTitles)) {
            ob_end_clean();
            header_remove();
            die("<script>alert('No se han encontrado resultados')</script>");
        }
    }

}
