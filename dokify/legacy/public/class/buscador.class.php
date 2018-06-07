<?php

use Dokify\Infrastructure\Application\Silex\Container;
use \Dokify\SearchSummary;

class buscador extends elemento implements Ielemento {

    const RESULTS_PER_PAGE = 10;
    const CACHE_TIME =300; // IN SECONDS
    const PAGINATION_PER_SEARCH = 100;
    const TABLE_NOT_LOADED = 0;
    const LOADING_TABLE = 1;
    const TABLE_LOADED = 2;

    public function __construct( $param, $saveOnSession = false ) {

        $this->tipo = "buscador";
        $this->tabla = TABLE_BUSQUEDA_USUARIO;
        $this->instance($param, $saveOnSession );


        if (is_array($param) && isset($param["selected"])) {
            $this->asignarReferencias($param["selected"]);
        }
    }

    public static function getRouteName () {
        return 'search';
    }


    /**
     * @param db $database
     * @param string $hash
     *
     * @return array
     */
    private static function getSearchTableData(db $database, string $hash): array
    {
        $table = DB_DATA. '.search_table';
        $query = "SELECT * FROM {$table} WHERE hash = '{$hash}'";
        $searchTableData = $database->query($query, true);

        if (empty($searchTableData)) {
            $currentTime = 'Y-m-d H:i:s';
            $updatedAt = date($currentTime);
            $database->query(
                "INSERT INTO {$table} (hash, updated_at) VALUES ('{$hash}', '{$updatedAt}')"
            );

            return [
                    'uid_search_table' => $database->getLastId(),
                    'updated_at' => strtotime($currentTime),
                    'completed' => null,
                    'loaded' => null,
                    'fullLoaded' => null
                ];
        }

        $currentData = current($searchTableData);
        $currentData['updated_at'] = strtotime($currentData['updated_at']);
        return $currentData;
    }


    public function updateDownloadedDate ($uid, $previous = false) {
        $field = $previous ? 'previous_downloaded' : 'downloaded';
        $value = $previous ? "'" . $previous . "'" : 'NOW()';

        $uid = db::scape($uid);
        $sql = "UPDATE {$this->tabla}_email SET {$field} = {$value} WHERE uid_buscador_email = {$uid} AND uid_buscador = {$this->getUID()}";

        return (bool) $this->db->query($sql);
    }

    public function getDownloadDate ($uid, $previous = false) {
        $field = $previous ? 'previous_downloaded' : 'downloaded';

        $sql = "SELECT IF ({$field}, UNIX_TIMESTAMP({$field}), NULL) FROM {$this->tabla}_email WHERE uid_buscador_email = {$uid} AND uid_buscador = {$this->getUID()}";
        $date = $this->db->query($sql, 0, 0);

        if ($date) return $date;
        return false;
    }


    public function createNotification (Iusuario $usuario, $subject, $comment, $cc = false) {
        $data = array(
            "uid_usuario_busqueda"  => $this->getUID(),
            "uid_usuario"           => $usuario->getUID(),
            "uid_empresa"           => $usuario->getCompany()->getUID(),
            "subject"               => $subject,
            "comment"               => $comment,
            "cc"                    => $cc
        );

        return new SearchNotification($data, $usuario);
    }

    public static function getHumanSearchString($query){
        return mb_substr(strip_tags($query), 0, 100, "utf8");

        $query = buscador::parseSearchString($query);
        $tpl = Plantilla::singleton();

        $searchs = array();
        foreach($query as $and){
            $pieces = array();
            foreach($and as $fname => $val){
                if( count($val) === 1 ){
                    $val = reset($val);
                    if( class_exists($fname) && is_numeric($val) ){
                        $item = new $fname($val);
                        $pieces[] = $fname . ":" . $item->getUserVisibleName();
                        continue;
                    }

                    switch($fname){
                        case "docs":
                            $pieces[] = $fname . ":" . documento::status2string($val);
                        break;
                        default:
                            if( strpos($val, "-") !== false ){
                                $parts = explode("-", $val);
                                $translate = array_map(array($tpl, 'getString'), $parts);
                                $pieces[] = $fname . ":" . implode(" ", $translate);
                            } else {
                                $pieces[] = $fname . ":" . $val;
                            }
                        break;
                    }
                }
            }
            $searchs[] = implode(" ", $pieces);
        }
        return implode(" + ", $searchs);
    }

    public static function export($query, Iusuario $usuario, $mode = "uid", $papelera = false, $all = false)
    {
        $searchResultsTable = DB_DATA. '.search_results';
        $db = db::singleton();
        $searchTableUid = self::get($query, $usuario, false, $papelera, $all, true, true);

        if ($searchTableUid <= 0) {
            $tpl = Plantilla::singleton();
            echo "<script>alert('". $tpl('exportacion_sin_datos') ."');</script>";
            exit;
        }

        switch($mode) {
            case "sql":
                return $sql = "SELECT uid  
                            FROM {$searchResultsTable}
                            WHERE uid_search_table = {$searchTableUid}";
            break;
            case "uid":
                $sql = "SELECT uid  
                    FROM {$searchResultsTable}
                    WHERE uid_search_table = {$searchTableUid}";
                return $db->query($sql, "*", 0);
            break;
            case "xls":
                $sql = "SELECT uid, type  
                    FROM {$searchResultsTable}
                    WHERE uid_search_table = {$searchTableUid}";
                $resultset = $db->query($sql);
                $total = $db->getNumRows();

                // Tabla temporal de los datos
                $tmpTableName = "search_export". md5($query) . uniqid();
                $temporary = new tablatemporal($tmpTableName);
                $temporary->campo("`uid` INT(11)");
                $temporary->campo("`type` VARCHAR(300)");
                $temporary->campo("`nombre` VARCHAR(300)");
                $temporary->campo("`clave` VARCHAR(300)");
                $temporary->campo("`extra` VARCHAR(300)");


                if (!$temporary->crear()) {
                    // dump($temporary);
                    die("Error al crear la tabla temporal");
                }

                $rowIndex = 0;
                while ($line = db::fetch_array($resultset, MYSQLI_ASSOC)) {
                    $percent = round(($rowIndex * 100) / $total, 1);
                    if ($total > 10000 && ($rowIndex % 1000 === 0)) {
                        customSession::set('progress', "{$percent}%");
                    }

                    $type = $line["type"];
                    $uid = $line["uid"];
                    $item = new $type($uid);
                    $sql = false;

                    if ($item instanceof solicituddocumento) {
                        $destino = $item->getElement();
                        $type = $destino->getType();

                        switch ($type) {
                            case 'empresa':
                                $name = '(SELECT nombre FROM '.TABLE_EMPRESA.' e WHERE e.uid_empresa = de.uid_empresa)';
                                break;
                            case 'empleado':
                                $name = '(SELECT concat(nombre, " ", apellidos) FROM '.TABLE_EMPLEADO.' e WHERE e.uid_empleado = de.uid_empleado)';
                                break;
                            case 'maquina':
                                $name = '(SELECT serie FROM '.TABLE_MAQUINA.' e WHERE e.uid_maquina = de.uid_maquina)';
                                break;
                        }

                        $alias = "(SELECT alias FROM ". TABLE_DOCUMENTO_ATRIBUTO ." attr WHERE attr.uid_documento_atributo = de.uid_documento_atributo LIMIT 1)";
                        $estado = reporte::estadoDocs('de');

                        $sql = "
                            INSERT INTO $tmpTableName (uid, nombre, clave, extra, type)
                            SELECT {$uid}, $alias, $name, $estado, 'solicituddocumento'
                            FROM ". TABLE_DOCUMENTO ."_{$type}_estado de
                            WHERE uid_solicituddocumento = {$uid}
                        ";

                    } else {
                        if ($searchData = $type::getSearchData($usuario, $papelera)) {
                            // excepcion para los tipos de documentos
                            if ($type == "tipodocumento") {
                                $type = "documento";
                            }

                            $primaryKey = db::getPrimaryKey($type);
                            $tabla = constant("TABLE_" . strtoupper($type));

                            $data = reset($searchData);

                            $fields = $data["fields"];
                            if (count($fields) !== 3) {
                                $diff = 3 - count($fields);
                                $array = array_fill(0, $diff, "''");
                                $fields = array_merge($fields, $array);
                            }

                            $sql = "
                                INSERT INTO $tmpTableName (uid, nombre, clave, extra, type )
                                SELECT $primaryKey, " . implode(", ", $fields) . ", '{$type}'
                                FROM $tabla WHERE $primaryKey = {$line["uid"]}
                            ";
                        }
                    }

                    if (!$sql) {
                        return false;
                    }

                    $db->query($sql);



                    $rowIndex++;
                }

                if ($usuario->esStaff()) {
                    $lastQuery = "SELECT uid, nombre, clave, extra, type FROM $tmpTableName";
                } else {
                    $lastQuery = "SELECT nombre, clave, extra, type FROM $tmpTableName";
                }


                customSession::set('progress', "-1");


                $excel = new SpreadSheet($lastQuery);

                // --- send to browser
                if (!$excel->send("busqueda")) {
                    if (CURRENT_ENV == 'dev') {
                        dump(db::singleton());
                        exit;
                    }

                    echo "<script>alert('Error al exportar');</script>";
                }

                exit;
            break;
            case "object": case "data":
                $sql = "SELECT uid, type 
                    FROM {$searchResultsTable}
                    WHERE uid_search_table = {$searchTableUid}";

                $data = $db->query($sql, true);
                $items = array_map(function($line) {
                    return new $line["type"]($line["uid"]);
                }, $data);

                return $items;
            break;
            case "objecttype":
                $sql = "SELECT uid, type 
                    FROM {$searchResultsTable}
                    WHERE uid_search_table = {$searchTableUid}";

                $data = $db->query($sql, true);
                $results = array();
                foreach ($data as $i => $line) {
                    $type = $line['type'];
                    if (!isset($results[$type])) {
                        $results[$type] = new ArrayObjectList;
                    }
                    $results[$type][] = new $type($line["uid"]);
                }

                return $results;
            break;
        }

    }


    public static function getRequestSummaryXLS (Iusuario $user, $query = NULL) {
        $modules = solicitable::getModules();
        $lang = Plantilla::singleton();
        $queries = [];
        $company = $user->getCompany();
        $expand = false;

        $summary = buscador::fetch($query, $user, true, ['modules' => $modules, 'self' => true]);
        $modules = array_keys($summary->getArrayCopy());

        if (1 === count($modules) && $modules[0] === "empresa") {
            $expand = true;
        }

        if ($expand) {
            $modules = solicitable::getModules();

            if ($query) {
                $query = "tipo:empresa {$query}";

                if (!$list = buscador::export($query, $user, 'uid')) {
                    return false;
                }

                $intList = new ArrayIntList($list);
            } else {
                $intList = $company->getStartIntList();
            }
        }

        $yes = utf8_decode($lang('si'));
        $no = utf8_decode($lang('no'));

        $status = "CASE estado
            WHEN 1 THEN 'Anexado'
            WHEN 2 THEN 'Validado'
            WHEN 3 THEN 'Caducado'
            WHEN 4 THEN 'Anulado'
            ELSE 'Sin Anexar'
        END";
        $alias = "(SELECT alias FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_documento_atributo = view.uid_documento_atributo)";
        $document = "(SELECT nombre FROM ". TABLE_DOCUMENTO ." WHERE uid_documento = view.uid_documento)";
        $criticity = "(SELECT criticity FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_documento_atributo = view.uid_documento_atributo)";
        $mandatory = "IF (obligatorio, '{$yes}', '{$no}')";

        foreach ($modules as $uid => $module) {
            // Skip if we cant access this data
            if (!$user->accesoModulo("{$module}_documento")) {
                continue;
            }

            $table = TABLE_DOCUMENTO ."_{$module}_estado";

            switch ($module) {
                case 'empresa':
                    $join = TABLE_EMPRESA;
                    $name = "empresa.nombre";
                    $itemCompany = "empresa.nombre";
                    $identifier = "empresa.cif";
                    break;
                case 'empleado':
                    $itemCompany = "(SELECT nombre FROM ". TABLE_EMPRESA ." WHERE empresa.uid_empresa = empleado_empresa.uid_empresa)";
                    $join = TABLE_EMPLEADO . "_empresa";
                    $name = "(SELECT concat(nombre, ' ', apellidos) FROM ". TABLE_EMPLEADO ." WHERE empleado.uid_empleado = view.uid_empleado)";
                    $identifier = "(SELECT dni FROM ". TABLE_EMPLEADO ." WHERE empleado.uid_empleado = view.uid_empleado)";
                    break;
                case 'maquina':
                    $itemCompany = "(SELECT nombre FROM ". TABLE_EMPRESA ." WHERE empresa.uid_empresa = maquina_empresa.uid_empresa)";
                    $join = TABLE_MAQUINA . "_empresa";
                    $name = "(SELECT concat(nombre, ' ', serie, if (matricula, concat(' - ', matricula), '') ) FROM ". TABLE_MAQUINA ." WHERE maquina.uid_maquina = view.uid_maquina)";
                    $identifier = "(SELECT serie FROM ". TABLE_MAQUINA ." WHERE maquina.uid_maquina = view.uid_maquina)";
                    break;
            }

            Container::instance()['index.repository']->expireIndexOf(
                $module,
                $company->asDomainEntity(),
                $user->asDomainEntity(),
                true
            );

            $indexList = (string) Container::instance()['index.repository']->getIndexOf(
                $module,
                $company->asDomainEntity(),
                $user->asDomainEntity(),
                true
            );

            $userCondition = $user->obtenerCondicionDocumentosView($module);

            // if requirement request are referenced, only show when the ref matches
            $lastInChain = db::getLastFromSet('uid_empresa_referencia');
            $referenceCondition = "(CASE
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_COMPANY ." THEN (
                    uid_empresa_referencia = {$join}.uid_empresa
                )
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_CHAIN ." THEN (
                    {$lastInChain} = {$join}.uid_empresa
                )
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_CONTRACTS ." THEN (
                    1
                ) ELSE (
                    uid_empresa_referencia = 0
                )
            END)";

            $sql = "SELECT
                uid_solicituddocumento,
                {$itemCompany}  as company,
                {$name}         as name,
                {$identifier}   as identifier,
                {$document}     as document,
                {$alias}        as alias,
                {$status}       as status,
                {$mandatory}    as mandatory,
                {$criticity}    as criticity
            FROM {$table} as view
            INNER JOIN {$join} ON view.uid_{$module} = {$join}.uid_{$module} 
            WHERE 1
            AND {$join}.uid_{$module} IN ({$indexList})
            AND descargar = 0
            AND {$referenceCondition}
            {$userCondition}
            ";

            if ($expand) {
                $sql .= "AND {$join}.uid_empresa IN ({$intList})";
            } else {
                $results = buscador::fetch($query, $user, false, ['modules' => [$module]]);

                if (0 === count($results)) {
                    continue;
                }

                $sql .= "AND view.uid_{$module} IN ({$results->toComaList()})";
            }

            if ($module != 'empresa') {
                $sql .= " AND papelera = 0";

                $visibles = self::getCompaniesIntList($user);
                $sql .= " AND uid_empresa IN ({$visibles->toComaList()})";
            }
            $queries[] = $sql;
        }

        if (!count($queries)) {
            return false;
        }

        $langCompany = utf8_decode($lang('empresa'));
        $langName = utf8_decode($lang('nombre'));
        $langDocument = utf8_decode($lang('documento'));
        $langStatus = utf8_decode($lang('estado'));
        $langMandatory = utf8_decode($lang('obligatorio'));
        $langCriticity = utf8_decode($lang('criticity'));
        $langAlias = utf8_decode($lang('client_alias'));

        $unions = implode(' UNION ', $queries);
        $sql = "SELECT
            company     as '{$langCompany}',
            name        as '{$langName}',
            identifier  as 'id',
            document    as '{$langDocument}',
            alias       as '{$langAlias}',
            status      as '{$langStatus}',
            mandatory   as '{$langMandatory}',
            criticity   as '{$langCriticity}'
        FROM ({$unions}) as summary ORDER BY company, name";

        // $db = db::singleton();
        // $data = $db->query($sql, true);
        // var_dump($data, $db);exit;

        return new SpreadSheet($sql);

    /*
    $sql = "SELECT * FROM (
    SELECT
    ,
    (SELECT alias FROM agd_docs.documento_atributo WHERE uid_documento_atributo = estado.uid_documento_atributo) as documento,
    CASE estado
        WHEN 1 THEN 'Anexado'
        WHEN 2 THEN 'Validado'
        WHEN 3 THEN 'Caducado'
        WHEN 4 THEN 'Anulado'
        ELSE 'Sin Anexar'
    END as estado,
    '1-10' as criticidad
    FROM agd_docs.documento_empresa_estado estado WHERE uid_empresa = 33731

    UNION

    SELECT
    CONCAT (
        (SELECT concat(nombre, ' ', apellidos) FROM agd_data.empleado WHERE empleado.uid_empleado = estado.uid_empleado),
        ' - ',
        (SELECT nombre FROM agd_data.empresa WHERE uid_empresa = ee.uid_empresa)
    ) as nombre,

    (SELECT alias FROM agd_docs.documento_atributo WHERE uid_documento_atributo = estado.uid_documento_atributo) as documento,
    CASE estado
        WHEN 1 THEN 'Anexado'
        WHEN 2 THEN 'Validado'
        WHEN 3 THEN 'Caducado'
        WHEN 4 THEN 'Anulado'
        ELSE 'Sin Anexar'
    END as estado,
    '1-10' as criticidad

    FROM agd_docs.documento_empleado_estado estado
    INNER JOIN agd_data.empleado_empresa ee USING (uid_empleado)
    WHERE papelera = 0
    AND uid_empresa = 33731
    )
    ORDER BY empresa";
    */
    }

    public static function getOrder ($search) {
        $orderTypes = array("empresa", "empleado", "maquina", "usuario");
        $orderBy = "type=";
        $order = array();

        foreach ($orderTypes as $type) {
            $order[] = $orderBy . "'{$type}' DESC";
        }


        // -- only show companies so we can do this SQL
        if (isset($search['since'])) {
            $sql = "
                SELECT fecha_primer_acceso
                FROM ". TABLE_USUARIO ." INNER JOIN ". TABLE_PERFIL ."
                USING (uid_usuario)
                WHERE uid_empresa = uid
                AND fecha_primer_acceso
                AND fecha_primer_acceso < UNIX_TIMESTAMP(NOW())
                ORDER BY fecha_primer_acceso ASC
                LIMIT 1
            ";

            $order[] = "($sql)";
        }



        return implode(', ', $order);
    }

    /***
       * New version of the get method
       *
       * @query     String          The user query
       * @user      Usuario         The user object     null
       * @limit     Bool | Array    True counts data, False returns everything, Array limits data by offset/count
       *
       */
    public static function fetch ($queryString, Iusuario $user = null, $limit = false, $options = [])
    {
        $searchResultsTable = DB_DATA. '.search_results';
        $searchTable = DB_DATA. '.search_table';

        $database = db::singleton();
        $company = $user->getCompany();
        $queryString = strtolower($queryString);
        // If the search is performed by a profile, get the user
        if ($user instanceof usuario) {
            $profile = $user->obtenerPerfil();
        } elseif ($user instanceof perfil) {
            $user = $user->getUser();
            $profile = $user;
        }

        // Search related vars
        $queries = self::parseSearchString($queryString);
        $onekind = (count($queries) === 1 && isset($queries[0]['tipo'])) ? $queries[0]['tipo'][0] : false;
        $count = (bool) ($limit === true);
        $all = isset($options['all']) ? (bool) $options['all'] : false;
        $trash = (bool) isset($options['trash']);
        $force = (bool) isset($options['force']);
        $showType = isset($options['type']) ? $options['type'] : null;
        $page = is_array($limit) ? (($limit[0] + $limit[1]) / $limit[1]) : 0;
        $cache = isset($options['cache']) ? $options['cache'] : true;
        $modules = isset($options['modules']) ? $options['modules'] : null;
        $network = isset($options['network']) ? (bool) $options['network'] : true;
        $addSelf = isset($options['self']) ? (bool) $options['self'] : false;

        // incompatible!
        if ($network === false) {
            $all = false;
        }

        $searchHash = $queryString;
        if ($modules !== null) {
            $searchHash .= '_'.implode('_', $modules);
        }
        $searchHash = md5(strtolower($searchHash));

        $expiredDateTime = date('Y-m-d H:i:s', time() - self::CACHE_TIME);
        $database->query("DELETE FROM {$searchTable} WHERE updated_at < '$expiredDateTime'");
        // Temporary table vars
        $tempName = implode('_', ['search', $profile->getUID(), $searchHash, (int) $all, (int) $trash, (int) $network, $company->getUID()]);

        $searchTableData = self::getSearchTableData($database, $tempName);
        $searchTableUid = (int) $searchTableData['uid_search_table'];

        $expiredCache  = ((time() - (int) $searchTableData['updated_at']) > self::CACHE_TIME);

        if ((int) $searchTableUid > 0 && (true === $expiredCache || true === $force )) {
            $database->query("DELETE FROM {$searchTable} WHERE uid_search_table = {$searchTableUid}");

            $searchTableData = self::getSearchTableData($database, $tempName);
            $searchTableUid = (int) $searchTableData['uid_search_table'];
        }

        // how many data do we have in our temporary table?
        $loaded         = isset($searchTableData['loaded']) ? (int) $searchTableData['loaded'] : 0;
        if ($network) {
            $companiesInt = ($all === false) ? self::getCompaniesIntList($user, $trash) : new ArrayIntList;
        } else {
            $companiesInt = $company->getStartIntList();
        }

        $searchable = self::getSearchData($user, $trash, $all, $network, $modules);
        // store the count data
        $searchSummary = new SearchSummary($showType);
        $searchResults = new ArraySearchResults;
        $createIndex = true;

        $searchSummary->query = reset($queries);
        $searchSummary->queries = $queries;

        // If use cache, check if table is already loaded
        if ($cache && isset($searchTableData['completed']) && $searchTableData['completed']) {
            $createIndex = false;
        }

        // Search all the searchable elements
        if ($createIndex) {
            foreach ($queries as $query) {
                $queryConfigs = array();

                foreach ($searchable as $table => $searchConfig) {
                    // Validate the required fields first
                    if (isset($searchConfig["required"]) && $required = $searchConfig["required"]) {
                        $skip = true;

                        foreach ($required as $fname => $value) {
                            // If our query has this filter defined
                            if (isset($query[$fname])) {

                                // If exists is enought or if we have a match
                                if ($value === true || in_array($value, $query[$fname])) {

                                    // Do not skip this search config
                                    $skip = false;
                                }
                            }
                        }

                        if ($skip) {
                            continue;
                        }
                    }


                    $skip = false;

                    // Por cada modificador de la busqueda AND
                    foreach ($query as $fname => $filter) {
                        // Si es un array
                        if (is_array($filter)) {

                            // Métodos aceptados por este tipo de busqueda
                            $accept = array_keys($searchConfig["accept"]);

                            // El filtro que el usuario ha introducido "tipo" esta soportado por este tipo de item
                            if (in_array($fname, $accept)) {
                                // Si únicamente aceptamos este filtro
                                if ($searchConfig["accept"][$fname] === true) {

                                // Si este filtro lo aceptamos con un callback de filtro ...
                                } elseif (is_callable($searchConfig["accept"][$fname])) {

                                // Si esta definido el valor, filtramos directamente
                                } elseif (($accepted = $searchConfig["accept"][$fname]) && in_array($accepted, $filter)) {

                                // Si es un conjunto de posibilidades, vamos a ver si la primera introducida por el usuario es válida
                                } elseif (is_array($accepted) && in_array(reset($filter), $accepted)) {

                                // Si esta definido pero o no es TRUE o no se especifico el filtro
                                } else {
                                    $skip = true;
                                    continue;
                                }
                            } else {
                                $skip = true;
                                continue;
                            }
                        }
                    }

                    if ($skip) {
                        continue;
                    }

                    $queryConfigs[$table] = $searchConfig;
                }

                // Num of rows inserted in temporary table in this request
                $inserted = 0;

                // Run every queryConfig to perform the search
                foreach ($queryConfigs as $table => $searchConfig) {

                    $table = isset($searchConfig["table"]) ? $searchConfig["table"] : $table;
                    $type = $searchConfig["type"];
                    $fields = $searchConfig["fields"];
                    $accept = $searchConfig["accept"];
                    // $src     = isset($searchConfig["src"]) ? $searchConfig["src"] : $table;
                    $where = array();

                    // We need a table
                    if (!is_string($table)) {
                        continue;
                    }

                    // Old command system vars
                    $primaryKey = db::getPrimaryKey($table);
                    $blacklist = array("tipo", "rand");

                    // SQL default limit
                    if (isset($searchConfig["limit"]) && $limitSQL = $searchConfig["limit"]) {
                        $where[] = "($limitSQL)";
                    }


                    $skip = false;
                    foreach ($query as $fname => $filter) {

                        // String search
                        if (!is_array($filter)) {

                            // If our config has basic fields to compare
                            if (count($fields)) {
                                $filter = utf8_decode($filter);
                                $where[] = "( ". implode("OR ", prepareLike($fields, $filter)) ." )";
                            }
                        }

                        // Commands search
                        if (is_array($filter)) {

                            // Do nothing to blacklisted commands
                            if (in_array($fname, $blacklist)) {
                                continue;
                            }

                            switch ($fname) {
                                case "list":
                                    $list = count($filter) ? implode(",", $filter) : 0;
                                    $where[] = "({$primaryKey} IN ({$list}))";

                                    break;
                                default:
                                    $commandFile = DIR_ROOT . "agd/busqueda/commands/{$fname}.search.php";

                                    // Legacy commands system
                                    $objectType = $type;
                                    $value = $filter; //reset($filter);
                                    $filterKeys = array_keys($filter);
                                    $inparam = reset($filterKeys) ? reset($filterKeys) : null;

                                    if (is_callable($accept[$fname]) && $accept[$fname]) {
                                        if ($sqlFilter = call_user_func($accept[$fname], $searchConfig, $filter, $inparam, $query, $trash)) {
                                            $where[] = $sqlFilter;
                                        }
                                    } else {
                                        if (is_readable($commandFile) && include($commandFile)) {

                                        } else {

                                            // Skip this searchConfig
                                            $skip = true;
                                            break;
                                        }
                                    }
                                    break;
                            }

                            if ($skip === true) {
                                break;
                            }
                        }

                    }

                    if ($skip === true) {
                        break;
                    }
                    // All filters done here


                    // Get the fulltext index field
                    $stringsField = "NULL";
                    if (count($fields)) {
                        $stringsField = count($fields) == 1 ? reset($fields) : "concat(". implode(",' ',", $fields) .")";
                    }

                    // Mount the query
                    if ($all === true) {
                        $sqlCompanies = "(SELECT uid_empresa FROM ". TABLE_EMPRESA .")";
                    } else {
                        // remove own company
                        if ($type == 'empresa' && $addSelf === false) {
                            $companiesInt = $companiesInt->remove($company->getUID());
                        }

                        $sqlCompanies = count($companiesInt) ? $companiesInt->toComaList() : 0;
                    }

                    $sqlWhere = count($where) ? " AND (". implode(" AND ", $where) .")" : "";
                    $sqlWhere = str_replace("<%companies%>", $sqlCompanies, $sqlWhere);
                    $table = str_replace("<%companies%>", $sqlCompanies, $table);


                    if ($count) {
                        $sqlCount = "SELECT count({$primaryKey}) FROM {$table} WHERE 1 {$sqlWhere}";
                        $rows = (int) $database->query($sqlCount, 0, 0);

                        // Add to the total
                        if ($rows) {
                            if (isset($searchSummary[$type])) {
                                $searchSummary[$type]['results'] += $rows;
                            } else {
                                $searchSummary[$type] = [
                                    'results'   => $rows,
                                    'route'     => $type::getRouteName(),
                                    'module'    => $type
                                ];
                            }
                        }

                    } elseif ($cache === false) {
                        $sqlSelect = "SELECT {$primaryKey} FROM {$table} WHERE 1 {$sqlWhere}";
                        //$sqlSelect    = "SELECT {$primaryKey} as uid, '{$type}' as type, LOWER({$stringsField}) FROM {$table} WHERE 1 {$sqlWhere}";

                        if (is_array($limit)) {
                            list ($offset, $records) = $limit;
                            $maxrows = ($records - count($searchResults));

                            $sqlSelect .= "LIMIT {$offset}, {$maxrows}";
                        }

                        $items = $database->query($sqlSelect, "*", 0, $type);
                        $searchResults = $searchResults->merge($items);


                        // If we have a limit, and we reach it, return
                        if (is_array($limit)) {
                            if (count($searchResults) >= $records) {

                                return $searchResults;
                            }
                        }

                    } else {

                        $sqlSelect  = "SELECT {$searchTableUid} as uid_search,{$primaryKey} as uid, '{$type}' as type, LOWER({$stringsField}) FROM {$table} WHERE 1 {$sqlWhere}";
                        $sqlInsert  = "INSERT IGNORE INTO $searchResultsTable (uid_search_table, uid, type, strings) {$sqlSelect}";


                        if (is_array($limit)) {
                            $records = ($page * self::RESULTS_PER_PAGE) + self::PAGINATION_PER_SEARCH;
                            $sqlInsert .= "\nLIMIT 0, {$records}";
                        }

                        if (!$database->query($sqlInsert)) {
                            dump($database);
                            throw new Exception($database->lastError());
                        }

                        if (is_array($limit)) {
                            if ($affected = $database->query("SELECT ROW_COUNT()", 0, 0)) {
                                $inserted += $affected;
                            }
                        }
                    }
                } // end of all configs
            } // end of all queries


            if ($count) {
                return $searchSummary;
            }
            if ($cache === false) {
                return $searchResults;
            }

            // Save temporary table as completed
            $completed = true;
            if (is_array($limit)) {
                @list ($offset, $records, $total) = $limit;
                $completed = ($total == ($loaded + $inserted));
            }

            // additional info to result object
            $searchResults->completed = $completed;
            $searchResults->loaded = $loaded + $inserted;

            $database->query(
                sprintf(
                    "UPDATE %s SET completed = %d, loaded = %d WHERE uid_search_table = %s",
                    $searchTable,
                    $completed,
                    $loaded + $inserted,
                    $searchTableUid
                )
            );


        }



        $order = array();

        $query = reset($queries);
        // Order only based on the first query
        if (isset($query["rand"]) && reset($query["rand"]) == "true") {
            $order[] = "RAND()";
        } else {
            if (isset($query)) {
                $order[] = self::getOrder($query);
            }

            // Si no es una busqueda concatenada y no es una busqueda con el formato 'tipo:_*'
            if (count($queries) === 1 && !$onekind) {
                // Extraemos las partes de la busqueda que no son "modificador"
                $strings = array();
                foreach ($query as $fname => $filter) {
                    if (!is_array($filter)) {
                        $strings[] = $filter;
                    }
                }

                // Y si hay alguna, ordenamos mediante relevancia
                if (count($strings)) {
                    $search = utf8_decode(implode(" ", $strings));

                    // https://bugs.mysql.com/bug.php?id=75451
                    if ($search === '*') {
                        $search = '**';
                    }

                    $matches = "MATCH (strings) AGAINST (LOWER('$search') WITH QUERY EXPANSION)";
                    $order[] = $matches . " DESC";
                } else {
                    $order[] = "strings";
                }
            } else {
                $order[] = "strings";
            }
        }

        // count directly
        if ($count) {
            $sql = "SELECT count(*) num, type 
                  FROM {$searchResultsTable} 
                  WHERE uid_search_table = {$searchTableUid} 
                  GROUP BY type ORDER BY ". implode(", ", $order);

            $data = $database->query($sql, true);
            foreach ($data as $row) {
                list ($num, $type) = array_values($row);

                if ($num) {
                    $searchSummary[$type] = [
                        'results'   => $row['num'],
                        'route'     => $type::getRouteName(),
                        'module'    => $type
                    ];
                }
            }

            return $searchSummary;
        }


        $where      = $showType ? "type = '". db::scape($showType) ."'": "1";
        $sqlSelect  = "SELECT uid, type FROM {$searchResultsTable} WHERE {$where} AND uid_search_table = {$searchTableUid} ORDER BY " . implode(", ", $order);

        if (is_array($limit)) {
            list ($offset, $records) = $limit;
            $sqlSelect .= " LIMIT {$offset}, {$records}";
        }

        // get the final results
        $data = $database->query($sqlSelect, true);

        if ($data && count($data)) {
            foreach ($data as $line) {
                $searchResults[] = new $line["type"]($line["uid"]);
            }
        }

        return $searchResults;
    }


    /***
       * Search in all the app
       *
       *
       *
       *
       */
    public static function get($query, Iusuario $usuario, $limit = false, $papelera = false, $all = false, $return = false, $force=false){
        $cache = cache::singleton();
        $db = db::singleton();
        $searchCommentTable = DB_DATA. '.search_table';
        $searchResultsTable = DB_DATA. '.search_results';

        $page = ( isset($_REQUEST["p"]) && is_numeric($_REQUEST["p"]) ) ? $_REQUEST["p"] : '0';
        $perfil = ( $usuario instanceof perfil ) ? $usuario : $usuario->perfilActivo();
        $user = ( $usuario instanceof perfil ) ? $usuario->getUser() : $usuario;

        // Solo staff podrá
        if( !$user->esStaff() ){ $all = false; }
        $tempName = "search_{$perfil->getUID()}_" . md5($query) . "_" . (int)$all . (int)$papelera . $user->getCompany()->getUID() . (int)$return;

        $searchTableData = self::getSearchTableData($db, $tempName);
        $searchTableUid = (int) $searchTableData['uid_search_table'];

        if ((time() - $searchTableData['updated_at']) > self::CACHE_TIME) {
            //actualizamos el campo comentario de la tabla.
            $tableNotLaoded = self::TABLE_NOT_LOADED;
            $db->query(
                "UPDATE {$searchCommentTable} SET fullLoaded = {$tableNotLaoded} WHERE uid_search_table = {$searchTableUid}"
            );
        }

        $query = buscador::parseSearchString($query);

        $fullLoaded = $searchTableData && isset($searchTableData['fullLoaded']) ? $searchTableData['fullLoaded'] : 0;
        $searchTipoQueryUnicaPaginacion = false;

        if ( true === isset($_GET["force"]) || $fullLoaded == self::TABLE_NOT_LOADED || $force ){
            if( $all !== true ){
                $empresasUIDS = buscador::getCompaniesIntList($usuario, $papelera);
                if( !count($empresasUIDS) ) return false;
            }

            $buscable = buscador::getSearchData($usuario, $papelera, $all);
            $expiredCache = ((time() - (int) $searchTableData['updated_at']) > self::CACHE_TIME) || true === $force;


            if ((int) $searchTableUid > 0 && (true === $expiredCache || isset($_GET["force"]) && $_GET["force"]==true )) {
                $db->query("DELETE FROM {$searchResultsTable} WHERE uid_search_table = {$searchTableUid}");

                $searchTableData = self::getSearchTableData($db, $tempName);
                $searchTableUid = (int) $searchTableData['uid_search_table'];
            }

            /*
                Las busquedas simples con formato 'tipo_*' se paginaran en resultados.
                Las busquedas que no sean 'tipo_*' o busquedas compuestas no se paginarán.

            */

            $searchTipoQueryUnicaPaginacion = ( count($query) === 1 && isset($query[0]['tipo']))  ? $query[0]['tipo'][0] : false;

            // Por cada OR
            foreach($query as $search){
                $part = $buscable;
                // Por cada tipo de elementos que se puede buscar
                foreach($part as $i => $searchData){

                    if( isset($searchData["required"]) && $require = $searchData["required"] ){
                        $continue = false;
                        foreach($require as $fname => $value){
                            // Si no esta especificado
                            if( !isset($search[$fname]) ){
                                $continue = true;
                                break;
                            }
                            // Si solo necesitamos que "este"
                            if( $value === true || in_array($value, $search[$fname]) ){

                            } else {
                                $continue = true;
                                break;
                            }
                        }

                        if( $continue ){ unset($part[$i]); continue; }
                    }

                    // Por cada modificador de la busqueda AND
                    foreach($search as $fname => $filter){

                        // Si es un array
                        if( is_array($filter) ){

                            // Métodos aceptados por este tipo de busqueda
                            $accept = array_keys($searchData["accept"]);

                            // El filtro que el usuario ha introducido "tipo" esta soportado por este tipo de item
                            if( in_array($fname, $accept) ){
                                // Si únicamente aceptamos este filtro
                                if( $searchData["accept"][$fname] === true ){

                                // Si este filtro lo aceptamos con un callback de filtro ...
                                } elseif( is_callable($searchData["accept"][$fname]) ){

                                // Si esta definido el valor, filtramos directamente
                                } elseif( ($accepted = $searchData["accept"][$fname]) && in_array($accepted, $filter) ){

                                // Si es un conjunto de posibilidades, vamos a ver si la primera introducida por el usuario es válida
                                } elseif( is_array($accepted) && in_array(reset($filter), $accepted) ){

                                // Si esta definido pero o no es TRUE o no se especifico el filtro
                                } else {

                                    //dump("Acepta el filtro [$fname] pero no concuerda [$accepted NOT IN ".implode(", ",$filter) ."]");
                                    unset($part[$i]); continue;
                                }
                            } else {
                                unset($part[$i]); continue;
                            }
                        }
                    }
                }
                // Aqui $part ya debe estar limitado
                foreach($part as $tabla => $searchData){
                    $tabla = ( isset($searchData["table"]) ) ? $searchData["table"] : $tabla;
                    $type = $searchData["type"];
                    $fields = $searchData["fields"];
                    $accept = $searchData["accept"];
                    $where = array();

                    if( !is_string($tabla) ) continue;

                    // Limitar si es necesario
                    if( isset($searchData["limit"]) && $limitSQL = $searchData["limit"] ){
                        $where[] = "($limitSQL)";
                    }

                    /** VAMOS A HACER COMPATIBLE LOS COMANDOS **/
                    $primaryKey = db::getPrimaryKey($tabla);
                    $idCliente = $usuario->getCompany()->getUID();
                    $inTrash = (int) $papelera;
                    $trashReplace = " = $inTrash";
                    $resultPerPage = self::RESULTS_PER_PAGE;
                    $blacklist = array("tipo", "rand");


                    // Aplicar filtros de busqueda
                    $break = false;
                    foreach($search as $fname => $filter){
                        if( !is_array($filter) && count($fields) ){
                            $filter = utf8_decode($filter);
                            $where[] = "( ". implode("OR ", prepareLike($fields, $filter)) ." )";
                        }

                        // Si es un comando..
                        if( is_array($filter) ){
                            if( in_array($fname, $blacklist) ) continue;

                            switch($fname){
                                case "list":
                                    $list = count($filter) ? implode(",", $filter ) : 0;
                                    $where[] = "( $primaryKey IN ($list) )";
                                break;
                                default:
                                    $commandFile = DIR_ROOT . "agd/busqueda/commands/{$fname}.search.php";

                                    /** VAMOS A HACER COMPATIBLE LOS COMANDOS **/
                                    $objectType = $type;
                                    $value = $filter; //reset($filter);
                                    $filterKeys = array_keys($filter);
                                    $inparam = reset($filterKeys) ? reset($filterKeys) : null;
                                    $docsWhere = array();

                                    if( is_callable($accept[$fname]) && $fn = $accept[$fname] ){
                                        if( $sqlFilter = call_user_func($accept[$fname], $searchData, $filter, $inparam, $search, $papelera) ){
                                            $where[] = $sqlFilter;
                                        }
                                    } else {
                                        if( is_readable($commandFile) && $result = include($commandFile) ){

                                        } else {
                                            $break = true;
                                            break;
                                        }
                                    }
                                break;
                            }
                        }
                    }
                    if( $break ){ break; }

                    if( count($fields) === 1 ){
                        $stringsField = reset($fields);
                    } else {
                        $stringsField = count($fields) ? "concat(". implode(",' ',", $fields) .")" : "NULL";
                    }

                    $sqlSelect = "SELECT {$searchTableUid}, {$primaryKey}, '{$type}', LOWER({$stringsField}) 
                              FROM {$tabla} 
                              WHERE 1";

                    $sql = "INSERT IGNORE INTO $searchResultsTable (uid_search_table, uid, type, strings) {$sqlSelect}";
                    if( count($where) ) $sql .= " AND ( ". implode(" AND ", $where) ." )";

                    $sql = str_replace("<%companies%>", $all === true ? "(SELECT uid_empresa FROM ".TABLE_EMPRESA.")" : $empresasUIDS->toComaList(), $sql);

                    if ( (!isset($_REQUEST["isAsync"])) && $searchTipoQueryUnicaPaginacion && !$force){
                        // Paginamos los resultados.
                        if ($page<5){
                            $limitSearch = 10*$page * self::RESULTS_PER_PAGE + self::PAGINATION_PER_SEARCH;
                        }else{
                            $limitSearch = $page * self::RESULTS_PER_PAGE + self::PAGINATION_PER_SEARCH;
                        }

                        $sql.=" limit 0,$limitSearch";

                    }else{
                        $fullLoaded = self::LOADING_TABLE;
                        $db->query(
                            "UPDATE {$searchCommentTable} SET fullLoaded = {$fullLoaded} WHERE uid_search_table = {$searchTableUid}"
                        );
                    }
                    if( !$db->query($sql) ){
                        if( CURRENT_ENV != 'prod') dump($db);
                        return false;
                    }else{
                        if($fullLoaded == self::LOADING_TABLE){
                            $fullLoaded = self::TABLE_LOADED;
                            $db->query(
                                "UPDATE {$searchCommentTable} SET fullLoaded = {$fullLoaded} WHERE uid_search_table = {$searchTableUid}"
                            );
                        }
                    }
                }
            }
        }
        if( $return ) return (int) $searchTableUid;

        $sql = "SELECT count(uid) 
              FROM {$searchResultsTable}
              WHERE uid_search_table = {$searchTableUid}";

        $total = $db->query($sql, 0, 0);

        $pagination = preparePagination(self::RESULTS_PER_PAGE , $total);

        $order = array();
        if (isset($search["rand"]) && reset($search["rand"]) == "true") {
            $order[] = "RAND()";
        } else {

            if (isset($search)) $order[] = self::getOrder($search);

            // Si no es una busqueda concatenada y no es una busqueda con el formato 'tipo:_*'
            if (count($query) === 1 && !$searchTipoQueryUnicaPaginacion) {
                // Extraemos las partes de la busqueda que no son "modificador"
                $strings = array();
                $part = reset($query);
                foreach($part as $fname => $filter){
                    if (!is_array($filter)) $strings[] = $filter;
                }

                // Y si hay alguna, ordenamos mediante relevancia
                if (count($strings)) {
                    $search = utf8_decode(implode(" ", $strings));

                    // https://bugs.mysql.com/bug.php?id=75451
                    if ($search === '*') {
                        $search = '**';
                    }

                    $matches = "MATCH (strings) AGAINST (LOWER('$search') WITH QUERY EXPANSION)";

                    $order[] = $matches . " DESC";
                } else {
                    $order[] = "strings";
                }
            } else {
                $order[] = "strings";
            }
        }



        $sql = "SELECT uid, type 
              FROM {$searchResultsTable}
              WHERE uid_search_table = {$searchTableUid}
              ORDER BY " . implode(", ", $order);

        if ($limit == true) {
            $sql .= " LIMIT {$pagination["sql_limit_start"]}, {$pagination["sql_limit_end"]}";
        }


        $data = $db->query($sql, true);

        if (!$data || count($data) === 0) {
            // no nos interesa que por este fallo se pare el proceso..
            $db->query("DELETE FROM {$searchResultsTable} WHERE uid_search_table = {$searchTableUid}");
            $items = new ArraySearchResults;
            $items->types = [];
            return $items;
        } else {

            $sql = "SELECT count(uid) 
                  FROM {$searchResultsTable}
                  WHERE uid_search_table = {$searchTableUid}";
            $total = $db->query($sql, 0, 0);

            $sql = "SELECT type 
                  FROM {$searchResultsTable}
                  WHERE uid_search_table = {$searchTableUid}
                  GROUP BY type";
            $types = $db->query($sql, "*", 0);


            $items = array_map(function($line){
                $element = new $line["type"]($line["uid"]);
                if ($element->exists()) return $element;
            }, $data);

            $items = array_filter($items);
            $items = new ArraySearchResults($items);
            $items->types = $types;
            $items->asyncTable = $fullLoaded;
            $items->pagination = $pagination;
            return $items;
        }
    }


    public static function parseSearchString($string){
        $string = trim($string, '+');
        $string = urldecode(trim(str_replace(array("%20","+"), array(" ","%2B"), stripslashes($string))));
        $searchParts = explode("+", $string);

        foreach($searchParts as $i => $part ){
            $search = preg_split( "/[\s]*\"([^\"]+)\"[\s]*|[\s]+/", trim($part), null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            foreach($search as $j => $filter){
                // Se cumple si ponemos "tipo: empleado" en vez de "tipo:empleado"
                if( $filter[strlen($filter)-1] == ":" && isset($search[$j+1]) ){
                    $search[$j] = db::scape($search[$j].$search[$j+1]);
                    unset($search[$j+1]);
                } elseif( isset($search[$j]) ){
                    $search[$j] = db::scape($search[$j]);
                }
            }


            $struct = array();
            foreach($search as $j => $filter){
                if( strpos($filter, ":") === false ){

                    if( vat::isValidSpainVAT($filter, false) ){ // si es un cif, únicamente un cif
                        $struct["tipo"] = array("empleado", "empresa");
                    }

                    if( preg_match("#". elemento::getEmailRegExp()."#", urldecode($filter), $matches) ){
                        $struct["tipo"] = array("empleado", "usuario");
                    }

                    $struct[] = $filter;
                } else {
                    list($name, $value) = explode(":", $filter);

                    // Parametro de comando
                    $inparam = false;
                    if( strpos($name, "-") !== false ){
                        list($name, $inparam) = explode("-", $name);
                    }

                    if( !isset($struct[$name]) ) $struct[$name] = array();

                    if( strpos($value, "#") !== false ){
                        list($value, $list) = explode("#", $value);
                        $struct["list"] = explode(",", $list);
                    }

                    if( $inparam ){
                        $struct[$name][$inparam] = $value;
                    } else {
                        $struct[$name][] = $value;
                    }

                    $struct[$name] = array_unique($struct[$name]);
                }
            }

            // Primero todos los modificadores, nos ayudarán a filtrar mas
            /*uasort($struct, function($a, $b){
                return is_array($a);
            });*/
            uasort($struct, function($arr){ return is_array($arr); });

            //dump($struct);
            $searchParts[$i] = $struct;
        }

        return $searchParts;
    }


    public static function getSearchData($usuario, $papelera = false, $all = false, $network = true, $modules = null, $cache = true) {
        if ($modules) {
            $tipos = $modules;
        } else {
            $tipos = array("empresa", "empleado", "maquina", "agrupamiento", "agrupador", "usuario", "documento_atributo", "tipodocumento", "etiqueta", "epi", "tipo_epi", "solicituddocumento");
        }

        $arrayBusquedas = array();

        foreach($tipos as $tipo){
            $searchData = "$tipo::getSearchData";
            if( !is_callable($searchData)  ){ die("Programador: Debes activar ::getSearchData para los objeto tipo $tipo"); }

            $searchData = $tipo::getSearchData($usuario, $papelera, $all, $network, $cache);

            if ($searchData) {
                foreach( $searchData as $table => $info ){
                    $info['usuario'] = $usuario; //añadimos siempre al usuario por si fuera necesario
                    $arrayBusquedas[ $table ] = $info;
                }
            }
        }

        return $arrayBusquedas;
    }


    public static function getCompaniesIntList($usuario, $papelera = false, $addSelf = true) {
        $cache = cache::singleton();

        $perfil = ( $usuario instanceof perfil ) ? $usuario : $usuario->perfilActivo();
        $cacheString = "buscador-getCompaniesIntList-{$perfil}-$papelera-{$addSelf}";
        if( ($estado = $cache->getData($cacheString)) !== null ){
            return ArrayIntList::factory($estado);
        }

        $empresa = $usuario->getCompany();
        $initial = $empresa->getStartIntList();

        if( $usuario instanceof usuario && !$usuario->accesoAccionConcreta(1, 19) ){
            $cache->addData($cacheString, $initial, 20);
            return $initial;
        }




        $db = db::singleton();

        if( $papelera ){
            // $activeIntList = self::getCompaniesIntList($usuario);
            $sql = "SELECT uid_empresa_inferior FROM ". TABLE_EMPRESA ."_relacion WHERE uid_empresa_superior IN ({$initial}) AND papelera = 1";

            $list = $db->query($sql, "*", 0);
            $list = new ArrayIntList($list);

            // $list[] = $empresa->getUID();
        } else {
            $op = ['strict' => false];

            if ($addSelf === false) {
                $op['black_list'] = $empresa;
            }

            $list = $empresa->getAllCompaniesIntList($usuario, $op);

            if ($usuario instanceof usuario && $usuario->isViewFilterByGroups() && count($list)) {
                $condicion = $usuario->obtenerCondicion("empresa", "uid_empresa");

                // no tenemos ni que hacer la SQL
                if (!$condicion) return new ArrayIntList;

                $sql = "SELECT uid_empresa FROM ". TABLE_EMPRESA ." WHERE uid_empresa IN ({$list->toComaList()}) AND uid_empresa IN ($condicion)";
                $array = $db->query($sql, "*", 0);

                $list = new ArrayIntList($array);
            }

        }

        $cache->addData($cacheString, "$list", 20);
        return $list;
    }



    /** OLD METHODS **/
    public function obtenerUsuariosConAcceso(){
        $coleccionObjetos = array();
        $sql = "SELECT uid_usuario FROM $this->tabla"."_compartida WHERE uid_usuario_busqueda = $this->uid";
        $ids = $this->db->query($sql, "*", 0);
        foreach( $ids as $uid ){
            $coleccionObjetos[] = new usuario($uid, false);
        }
        return $coleccionObjetos;
    }

    public function asignarReferencias($list){
        $inserts = array();
        foreach($list as $uid){
            $inserts[] = "( NULL, ". $this->getUID() .", $uid, NOW() )";
        }
        if( count($inserts) ){
            $sql = "INSERT INTO ". $this->tabla ."_referencia VALUES ". implode(", ", $inserts);
            return $this->db->query($sql);
        } else {
            return false;
        }
    }

    public function getAssignedReferencias($list){
        $sql = "SELECT uid_agrupador FROM ". $this->tabla ."_referencia WHERE uid_usuario_busqueda = ". $this->getUID();
        return new ArrayObjectList( $this->db->query($sql, "*", 0, "agrupador") );
    }

    public function actualizarUsuarios(){
        $this->tipo = "usuario_busqueda";
        $objetos = $this->actualizarTablaRelacional($this->tabla ."_compartida", "usuario" );
        $this->tipo = "buscador";
        return $objetos;
    }

    /**
        PERMITE ASIGNAR Y LEER LOS DOCUMENTOS ASOCIADOS A UNA BUSQUEDA

        ¡¡¡¡ HAY QUE TENER EN CUENTA QUE ESTOS SON LOS QUE NO DEBEN APARECER EN LOS RESULTADOS !!!!
    **/
    public function getAvailableAttributes(usuario $usuario, $data=null){
        if( $data === null || is_string($data) ){
            $sql = "SELECT uid_documento_atributo
                FROM ". TABLE_BUSQUEDA_DOCUMENTO ."
                INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO."
                USING( uid_documento_atributo )
                WHERE uid_usuario_busqueda = " . $this->getUID();


                if( $usuario instanceof usuario && $usuario->isViewFilterByLabel() ){
                    $etiquetas = $usuario->obtenerEtiquetas();
                    if( $etiquetas && count($etiquetas) ){
                        $sql .= " AND uid_documento_atributo IN (
                            SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta WHERE uid_etiqueta IN ({$etiquetas->toComaList()})
                        )";
                    } else {
                        $sql .= " AND uid_documento_atributo NOT IN (SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta)";
                    }
                }


            if( is_string($data) ){
                $sql .=  " ORDER BY $data";
            }

            $coleccion = $this->db->query($sql, "*", 0, "documento_atributo");
            return new ArrayObjectList($coleccion);
        } else {
            if( is_traversable($data) ){
                foreach($data as $uid){
                    $sql = "INSERT IGNORE INTO ". TABLE_BUSQUEDA_DOCUMENTO ." ( uid_documento_atributo, uid_usuario_busqueda )
                            VALUES ( ". db::scape($uid) .", ". $this->getUID() ." )";
                    if( !$this->db->query($sql) ){
                        return false;
                    }
                }
            }

            $list = ( count($data) ) ? implode(",",$data) : 0;
            $sql = "DELETE FROM ". TABLE_BUSQUEDA_DOCUMENTO ."
                WHERE uid_usuario_busqueda = ". $this->getUID() ."
                AND uid_documento_atributo NOT IN ($list)
            ";

            return $this->db->query($sql);
        }
    }

    public function getAssignedAttributes(usuario $usuario, $order = null ){
        $disponibles = $this->getAvailableAttributes($usuario, $order);
        $companyUser = $usuario->getCompany();
        $solicitables = solicitable::getModules();


        $companies = ($corp = $companyUser->perteneceCorporacion()) ? new ArrayObjectList(array($corp, $companyUser)) : $companyUser->getStartList();


        if ($results = $this->getResultObjects($usuario, 'objecttype')) {
            $collection = new ArrayObjectList;

            foreach ($results as $module => $items) {
                if (!in_array($module, $solicitables)) continue;


                $sql = "SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO . "
                INNER JOIN  ". PREFIJO_ANEXOS.$module." USING (uid_documento_atributo)
                WHERE 1
                AND uid_empresa_propietaria IN ({$companies->toComaList()})
                AND descargar = 0
                AND activo = 1
                AND uid_{$module} IN ({$items->toComaList()})";

                if (count($disponibles)) {
                    $sql .= " AND uid_documento_atributo NOT IN ({$disponibles->toComaList()})";
                }

                $sql .= " GROUP BY uid_documento_atributo";

                if ($order) $sql .= " ORDER BY $order";

                $list = $this->db->query($sql, "*", 0, 'documento_atributo');

                $collection = $collection->merge($list);
            }

            return $collection->unique();
        } else {
            return new ArrayObjectList;
        }

    }

    public function getUser(){
        $sql = "SELECT uid_usuario FROM $this->tabla WHERE uid_usuario_busqueda = $this->uid";
        $uid = $this->db->query($sql, 0, 0);
        if( is_numeric($uid) ){
            $usuario = new usuario($uid);
            return $usuario;
        }
    }

    /************
    ** Esta funcion no es exacta, pero intenta devolver el perfil mas adecuado para esta busqueda
    *********************************/
    public function getPerfil(){
        $sql = "SELECT uid_perfil FROM ". TABLE_PERFIL ." WHERE uid_usuario = {$this->getUser()->getUID()} AND uid_empresa = {$this->getCompany()->getUID()} LIMIT 1";
        $uid = $this->db->query($sql, 0, 0);
        if( is_numeric($uid) ){
            $perfil = new perfil($uid);
            return $perfil;
        }

        return false;
    }


    private static function getBusquedas($usuario, $compartidas=false, $filter = false, $count = false) {
        $db = db::singleton();
        $field = $count ? 'count(uid_usuario_busqueda)' : 'uid_usuario_busqueda';

        if( !$compartidas ){
            $sql = "SELECT {$field} FROM ".TABLE_BUSQUEDA_USUARIO." WHERE uid_usuario = {$usuario->getUID()}";

            if( $filter === NULL ){
                // Quiere decir que no queremos aplicar ningun filtro ni si quiera el lógico del cliente en el que estamos
                // He implementado esto para que funcionen SIEMPRE la descargas "publicas"
            } else {
                $userCompany = $usuario->getCompany();
                $companies = new ArrayObjectList(array($userCompany));
                if ($corporation = $userCompany->perteneceCorporacion()){
                    $companies[] = $corporation;
                }
                $sql .= " AND uid_empresa IN ({$companies->toComaList()}) ";
            }

        } else {
            $sql = "SELECT {$field} FROM ".TABLE_BUSQUEDA_USUARIO."_compartida c
                    INNER JOIN ".TABLE_BUSQUEDA_USUARIO." b
                    USING ( uid_usuario_busqueda )
                    WHERE c.uid_usuario = ". $usuario->getUID() ."
                    AND uid_empresa = ". $usuario->getCompany()->getUID();
        }

        if( is_string($filter) ){
            $sql .= " AND $filter";
        }

        $sql .= " ORDER BY uid_usuario_busqueda DESC";

        if ($count) {
            return $db->query($sql, 0, 0);
        }

        $res = $db->query($sql, "*", 0);
        return $res;
    }


    /** DEVUELVE UNA COLECCION DE OBJETOS RESULTANTES DE LA BUSQUEDA **/
    public function getResultObjects($usuario, $mode = "object"){
        $info = $this->getInfo();

        return self::export($info["cadena"], $usuario, $mode);
    }


    public static function search($searchString, $searchExport="array", $usuario=null){
        $inc = realpath( dirname(__FILE__) . "/../agd/buscar.php" );
        return include($inc);
    }

    public static function obtenerBusquedas(usuario $sujeto, $compartidas = false, $filter = false, $count = false){
        $busquedas = self::getBusquedas($sujeto, $compartidas, $filter, $count);

        if ($count) {
            return $busquedas;
        }

        $coleccionBusquedas = array();
        foreach ($busquedas as $uidBusqueda){
            $coleccionBusquedas[] = new buscador($uidBusqueda);
        }
        return $coleccionBusquedas;
    }

    public function getUserVisibleName(){
        return $this->obtenerDato("nombre");
    }

    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
        $info = parent::getInfo(true, elemento::PUBLIFIELDS_MODE_TABLEDATA, $usuario, $extraData);
        $infoBusqueda =& $info[ $this->uid ];
        $infoBusqueda['innerHTML'] = $infoBusqueda['nombre'];
        unset($infoBusqueda['nombre']);
        //$info['href'] = 'buscar.php?q='.$infoBusqueda['cadena'];
        unset($infoBusqueda['cadena']);
        return $info;
    }

    public function getClickURL(){
        $cadena = $this->obtenerDato("cadena");
        return '#buscar.php?p=0&q='. $cadena;
    }

    public static function updateDownloadDates ($objeto, $campo, $values, $ext) {
        preg_match ("((.*)\[(.+)\])" , $campo, $matches);
        list($string, $campo, $rowindex) = $matches;

        $type = $objeto->getType();
        $table = $objeto->tabla."_$campo";
        $date = reset($values);
        if (!$date) return true;

        // Y-m-d
        if (!$value = strtotime($date)) {
            // d-m-Y
            $value = documento::parseDate($date);
        }

        $date = date('Y-m-d', $value);

        $sql = "UPDATE $table SET downloaded = '{$date} 00:00:00', previous_downloaded = '{$date} 00:00:00'  WHERE uid_{$type}_{$campo} = {$rowindex}";

        return db::get($sql);
    }


    public static function registrarFormatoAvisos($objeto, $campo, $values, $ext) {
        preg_match ("((.*)\[(.+)\])" , $campo, $matches);
        list($string, $campo, $rowindex) = $matches;
        $type = $objeto->getType();
        $table = $objeto->tabla."_$campo";

        $zip = is_numeric($values) ? $values : "0";

        $sql = "UPDATE $table SET zip = {$zip}  WHERE uid_{$type}_{$campo} = {$rowindex}";

        return db::get($sql);
    }

    public static function registrarEstadosAvisos($objeto, $campo, $values, $ext){
        preg_match ("((.*)\[(.+)\])" , $campo, $matches);
        list($string, $campo, $rowindex) = $matches;

        $publics = self::publicFields("aviso", $objeto);

        $db = db::singleton();
        $table = $objeto->tabla."_$campo";
        $type = $objeto->getType();

        $multiFieldName = $ext["name"];
        $updates = array();
        $active = is_numeric($values) ? $values : "0";

        $sql = "UPDATE $table SET {$multiFieldName} = $active  WHERE uid_$type"."_$campo = $rowindex";

        return $db::get($sql);
    }

    public static function publicMultipleFields($modo, elemento $objeto = null, usuario $usuario = null, $tab = false){
        $arrayCampos = new FieldList;
        $arrayCampos['email[]'] = new FormField(array('tag' => 'input', 'type' => 'text'));
        return $arrayCampos;
    }


    public static function optionsFilter($uid, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){
        $condiciones = array();

        if ($uid) {
            $search = new buscador($uid);
            $searchUser = $search->getUser();

            if (!$searchUser->compareTo($user)) {
                $condiciones[] = "uid_accion NOT IN (2, 3, 4, 14, 102)";
            }

            if (count($condiciones)) {
                return "AND " . implode(" AND ", $condiciones);
            }
        }

        return false;
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
        //$modo = func_get_args(); $modo = ( isset($modo[0]) ) ? $modo[0] : null;
        //$usuarioActivo = usuario::getCurrent();
        $arrayCampos = new FieldList;

        $arrayCampos['nombre'] = new FormField(array('tag' => 'input', 'type' => 'text', 'blank' => false));
        $arrayCampos['cadena'] = new FormField(array('tag' => 'input', 'type' => 'text', 'blank' => false));

        $showOnHome = new FormField(array('tag' => 'input', 'type' => 'checkbox', "className" => "iphone-checkbox"));

        switch( $modo ){
            case elemento::PUBLIFIELDS_MODE_TABLEDATA:

            break;
            default: case elemento::PUBLIFIELDS_MODE_INIT:
                $arrayCampos['show_on_home'] = $showOnHome;
            break;
            case elemento::PUBLIFIELDS_MODE_NEW:

                $arrayCampos['uid_usuario'] = new FormField;
                $arrayCampos['uid_empresa'] = new FormField;
                $arrayCampos['pkey'] = new FormField;
                $arrayCampos['show_on_home'] = $showOnHome;
                //$arrayCampos['uid_usuario']['value'] = $usuarioActivo->getUID();

            break;
            case elemento::PUBLIFIELDS_MODE_EDIT:
                $arrayCampos['uid_empresa'] = new FormField;
            break;
            case elemento::PUBLIFIELDS_MODE_ATTR:
                $arrayCampos = new FieldList;
                $arrayCampos['show_on_home'] = $showOnHome;
            break;
            case "aviso":
                $arrayCampos = new FieldList;

                $extras = array();

                $extras[] = new FormField(array( "tag" => "input", "type" => "text", "name" => "downloaded", "innerHTML" => "Enviar documentos desde...", "className" => "datepicker", "style" => "width: 120px", "callback" => "updateDownloadDates"));

                $extras[] = new FormField(array( "tag" => "input", "type" => "radio", "name" => "zip", "group" => "opt_formatos", "value" => 1, "innerHTML" => "ZIP", "title" => "link_zip", "callback" => "registrarFormatoAvisos" ));
                $extras[] = new FormField(array( "tag" => "input", "type" => "radio", "name" => "zip", "group" => "opt_formatos", "value" => 0, "innerHTML" => "informe", "title" => "mail_report", "callback" => "registrarFormatoAvisos"));

                $estados = array( 0 /*simulamos pendiente*/, documento::ESTADO_ANEXADO, documento::ESTADO_VALIDADO, documento::ESTADO_CADUCADO, documento::ESTADO_ANULADO  );
                foreach($estados as $intEstado ){
                    $extras[] = new FormField(array( "tag" => "input", "type" => "checkbox", "name" => "estado_{$intEstado}", "group" => "incluir_informe", "value" => 1, "innerHTML" => documento::status2string($intEstado), "callback" => "registrarEstadosAvisos" ));
                }

                $arrayCampos['dia'] = new FormField(array("tag" => "slider", "match" => "^[0-9]$", "className" => "slider", "count" => "31", "hr" => true, "value" => 0));
                $arrayCampos['email[]'] = new FormField(array('tag' => 'input', 'type' => 'text', "extra" => $extras, "match" => elemento::getEmailRegExp()));

                return $arrayCampos;
            break;
        }
        return $arrayCampos;
    }

    public function getLinkDate(){
        $sql = "SELECT `updated` FROM $this->tabla WHERE uid_usuario_busqueda = $this->uid";
        $date = $this->db->query($sql, 0, 0);
        return $date;
    }

    public function regenerarLink(){
        $key = self::getRandomKey();
        $sql = "UPDATE $this->tabla SET `pkey` = '$key' WHERE uid_usuario_busqueda = $this->uid";
        if( $this->db->query($sql) ){
            return $key;
        }
        return false;
    }

    public function getPublicKey(){
        $sql = "SELECT `pkey` FROM $this->tabla WHERE uid_usuario_busqueda = $this->uid";
        return $this->db->query($sql, 0, 0);
    }

    public function getCompany(){
        return new empresa( $this->obtenerDato("uid_empresa") );
    }


    public function getInlineArray(Iusuario $usuario = NULL, $mode = null , $data = array()){
        $inline = array();
        $tpl = Plantilla::singleton();


        $link = $this->getLink(false, $_SERVER["SERVER_NAME"]) . "&action=zip&send=1";
        $title = $tpl("descargar_documentos_validos");


        $descargar = array();
        $descargar["img"] = array(
            'src'   => RESOURCES_DOMAIN . "/img/famfam/drive_web.png",
            'title' => $title
        );

        $descargar[] = array(
            "nombre"    => $tpl("descargar"),
            "title"     => $title,
            "href"      => $link,
            "target"    => "async-frame"
        );

        $inline[] = $descargar;


        $agrupadoresReferencia = $this->getAssignedReferencias($usuario);
        if (is_traversable($agrupadoresReferencia) && count($agrupadoresReferencia)) {
            $names = implode(', ', $agrupadoresReferencia->getNames());
            $title = sprintf($tpl('busqueda_referenciada'), $names);

            $referencia = array();
            $referencia["img"] = RESOURCES_DOMAIN . "/img/famfam/information.png";
            $referencia[] = array(
                'nombre' => $names,
                'title' => $title
            );

            $inline[] = $referencia;
        }


        if ($usuario->accesoAccionConcreta('buscador', 'documentos')) {
            if ($notifications = $this->getDocumentNotificationsData()) {
                $num = count($notifications);
                $title = sprintf($tpl('avisos_periodicos_definidos'), $num);

                $notification = array();
                $notification["img"] = array(
                    'src' => RESOURCES_DOMAIN . "/img/famfam/folder_go.png",
                    'title' => $title
                );


                $notification[] = array(
                    'nombre' => $num . ' ' . $tpl('avisos'),
                    'title' => $title,
                    'href' => 'busqueda/aviso.php?poid=' . $this->getUID()
                );

                $inline[] = $notification;
            }
        }


        if (count($notifications = $this->getEmailNotifications())) {
            $num = count($notifications);

            $title = sprintf($tpl('n_emails_enviados'), $num);

            $notification = array();

            $notification["img"] = array(
                'src' => RESOURCES_DOMAIN . "/img/famfam/email.png",
                'title' => $title
            );

            $notification[] = array(
                'nombre' => $num . ' ' . $tpl('emails'),
                'title' => $title,
                'href' => '#busqueda/notifications.php?poid='. $this->getUID()
            );

            $inline[] = $notification;
        }

        return $inline;
    }


    public function getEmailNotifications () {
        $sql = "SELECT uid_usuario_busqueda_notification FROM ". TABLE_BUSQUEDA_USUARIO ."_notification WHERE uid_usuario_busqueda = {$this->getUID()}";

        if ($array = $this->db->query($sql, "*", 0, 'SearchNotification')) {
            return new ArrayObjectList($array);
        }

        return new ArrayObjectList;
    }

    public function getLink($public = false, $dominio = false)
    {
        $usuario = $this->getUser();
        if (!$usuario instanceof usuario) {
            return false;
        }

        if (!$dominio) {
            $empresaCliente = $this->getCompany();
            $dominio = $empresaCliente->getURLBase();
        } else {
            $dominio = CURRENT_PROTOCOL . "//$dominio/";
        }

        if ($public) {
            $base = $dominio . "download/search";
        } else {
            $base = $dominio . "agd/busqueda/exportar/documentos.php";
        }

        if ($pKey = $this->getPublicKey()) {
            return $base ."?pkey=" . $pKey;
        } else {
            return $base ."?poid=". $this->getUID() ."&action=zip&send=1";
        }
    }

    public function getSelectedStatuses ($notificationID) {
        $sql = "SELECT
            if(estado_1, ". documento::ESTADO_ANEXADO .", NULL) estado_1,
            if(estado_2, ". documento::ESTADO_VALIDADO .", NULL) estado_2,
            if(estado_3, ". documento::ESTADO_CADUCADO .", NULL) estado_3,
            if(estado_4, ". documento::ESTADO_ANULADO .", NULL) estado_4
        FROM ". TABLE_BUSQUEDA_USUARIO ." b
        INNER JOIN ". TABLE_BUSQUEDA_USUARIO ."_email e ON b.uid_usuario_busqueda = e.uid_buscador
        WHERE uid_usuario_busqueda = {$this->getUID()}";

        if ($status = $this->db->query($sql, 0, "*")) {
            return array_values(array_filter($status));
        }

        return array();
    }


    public function getDocumentNotificationsData () {
        $sql = "SELECT e.email, dia FROM ". TABLE_BUSQUEDA_USUARIO ." b INNER JOIN ". TABLE_BUSQUEDA_USUARIO ."_email e
                ON b.uid_usuario_busqueda = e.uid_buscador WHERE uid_usuario_busqueda = {$this->getUID()}";

        $data = $this->db->query($sql, true);

        if ($data && count($data)) {
            return $data;
        }

        return false;
    }

    public static function getRandomKey(){
        return $key = md5(uniqid()).md5(uniqid());
    }

    public static function getFromKey($key){
        $db = db::singleton();
        $sql = "SELECT uid_usuario_busqueda FROM ". TABLE_BUSQUEDA_USUARIO."  WHERE `pkey` = '". db::scape($key) ."'";
        if( $uid = $db->query($sql, 0, 0) ){
            return new buscador($uid);
        }
        return false;
    }

    public static function cronCall($time, $force = false){
        if( $force ) { echo "\tForzando ejecucion!\t"; }

        $force = ($force) ? 1 : 0 ;
        $db = db::singleton();
        $dia = date("d");
        $date = date("Y-m-d");

        $sql = "SELECT uid_usuario_busqueda as uid, uid_buscador_email as uidmail, b.uid_empresa, e.email, e.zip, e.estado_0, e.estado_1, e.estado_2, e.estado_3, e.estado_4
                FROM ".  TABLE_BUSQUEDA_USUARIO ." b
                INNER JOIN ".  TABLE_BUSQUEDA_USUARIO ."_email e
                ON b.uid_usuario_busqueda = e.uid_buscador
                INNER JOIN ". TABLE_PERFIL. " p
                ON  b.uid_usuario = p.uid_usuario
                AND b.uid_empresa = p.uid_empresa
                WHERE dia = $dia
                AND ( enviado != '$date' OR $force )
                AND p.papelera = 0
        ";
        $plantilla = plantillaemail::instanciar("avisobusquedas");
        $lineas = $db->query($sql, true);
        if( $e = $db->lastError() ){ return $e; }

        if( $force ){ echo "\n"; }
        echo "Se han encontrado ". count($lineas) ." busquedas a enviar\n";
        foreach($lineas as $i => $datos ){
            $logEmail = new log();
            extract($datos); // nos devuelve $email, $zip, $uid, $estado_1, $estado_2, $estado_3, $estado_4

            $empresaCliente = new empresa($uid_empresa);
            $buscador = new buscador($uid);
            $usuario = $buscador->getPerfil();
            $userSearch = $buscador->getUser();

            if (!$usuario instanceof perfil) {
                // There is no profile for this search, we update the current search for the current company of the user
                if (!$userSearch) continue;
                $logChange = new log();
                $userCompany = $userSearch->getCompany();
                $searchCompany = $buscador->getCompany();

                $logChange->info("busqueda", "Cambiamos busqueda uid: {$buscador->getUID()} de la empresa: {$searchCompany->getUID()} a la empresa {$userCompany->getUID()}", $buscador->getUserVisibleName(), "ok", true);
                $buscador->update(array("uid_empresa" => $userCompany->getUID()));
                $usuario = $buscador->getPerfil();
                if (!$usuario instanceof perfil) continue;
            }

            $destinatarios = array_filter(preg_split("/(;|,)/", trim($email)));

            if ($force && strstr(implode(",", $destinatarios), "@dokify.net") == false) {
                echo "Saltando ". implode(",", $destinatarios) ." por forzar\n";
                continue;
            }

            if ($force) {
                $destinatarios = email::$developers;
            }

            $email = new email( $destinatarios );
            $template = new Plantilla();
            $language = $userSearch->getCountry()->getLanguage();
            $email->establecerAsunto(sprintf($template->getString("company_documents_mailing", $language), $buscador->getCompany()->getUserVisibleName()));

            if ($zip) {
                $link = $buscador->getLink(true) . '&comefrom=' . $uidmail;
                $plantilla->replaced["{%elemento-nombre%}"] = $plantilla->replaced["{elemento-nombre}"] = $buscador->getUserVisibleName();
                $plantilla->replaced["{%link%}"] = $plantilla->replaced["{link}"] = $link;

                $email->enviardesdePlantilla($plantilla, $empresaCliente);
                $html = $email->obtenerContenido();

                $logEmail->info("busqueda","aviso recordatorio de busqueda a ". implode(",",$destinatarios) , $buscador->getUserVisibleName());
            } else {
                $estados = array();
                for($i=0;$i<5;$i++){
                    $name = "estado_$i";
                    if( isset($$name) && $$name ){ $estados[] = $i; }
                }

                try {
                    $html = include( DIR_ROOT . "agd/busqueda/informe.php" );
                } catch(Exception $e) {
                    echo $e->getMessage()."\n";
                    continue;
                }

                $html = trim($html);

                $email->establecerContenido( $html );

                $logEmail->info("busqueda","aviso resumen de documentos a ". implode(",",$destinatarios) , $buscador->getUserVisibleName());
            }

            if( !$html ){
                $logEmail->resultado("error sin contenido", true);
                echo "No hay contenido. No se enviará nada\n";
                continue;
            }

            $toString = implode(", ", $email->obtenerDestinatarios());
            echo "Enviando a [$toString]... ";
            $estado = $email->enviar();
            if ($estado === true) {
                if ($force) {
                    echo "Enviado correctamente a $toString\n";
                }

                $sql = "UPDATE ".  TABLE_BUSQUEDA_USUARIO ."_email SET enviado = '$date' WHERE uid_buscador_email = $uidmail";
                $db->query($sql);

                $logEmail->resultado("ok", true);
            } else {
                if ($force) {
                    echo "Error enviando a $toString [{$estado}]\n";
                }
                $logEmail->resultado("error $estado", true);
            }
        }

        // Busquedas que no tienen configurado email y que su usuario esta configurado en otra empresa:
        $updateSearch = "UPDATE ".  TABLE_BUSQUEDA_USUARIO ." bu INNER JOIN (
                SELECT uid_usuario_busqueda, b.uid_usuario FROM ".  TABLE_BUSQUEDA_USUARIO ." b
                    LEFT OUTER JOIN ".  TABLE_PERFIL ." p
                    ON  b.uid_usuario = p.uid_usuario
                    AND b.uid_empresa = p.uid_empresa
                    WHERE p.uid_perfil IS NULL

                )  as temp USING (uid_usuario_busqueda)

                SET bu.uid_empresa = (SELECT uid_empresa FROM ".  TABLE_USUARIO ." u INNER JOIN ".  TABLE_PERFIL ." ON uid_perfil = perfil WHERE u.uid_usuario = temp.uid_usuario)

        ";

        if ($db->query($updateSearch)) {
            if ($num = $db->getAffectedRows()) {
                echo "Se han cambiado las empresas de ". $db->getAffectedRows() . " busquedas que estaban desactualizadas";
            }
        } else {
            echo "Ha ocurrido un error actualizando las empresas de las búsquedas de los usuarios no actualizados: ". $db->lastError();
        }

        return true;
    }
}