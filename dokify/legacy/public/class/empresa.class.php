<?php

use logui as LegacyLogUI;
use Dokify\Application\Money;
use Dokify\Domain\Payment\Fees;

class empresa extends solicitable implements Ielemento, Iactivable, Iparent, IfolderContainer, Ilistable, Irequestable, Ipartner {

    const LICENSE_FREE = 0;
    const LICENSE_PREMIUM = 1;
    const LICENSE_ENTERPRISE = 2;
    const DEFAULT_DISTANCIA = 3;
    const TIME_NOTIFICCATION_PAYMENT_FRAME = 20;
    const TIME_NOTIFICCATION_PAYMENT = 0;

    const PUBLIFIELDS_MODE_LICENSE = 'license';
    const PUBLIFIELDS_MODE_PARTNER = 'partner';
    const PUBLIFIELDS_MODE_ASSIGN_PARTNER = 'assign_partner';
    const PUBLIFIELDS_MODE_PARTNER_EDIT = 'partner_edit';

    const SUMMARY_NOTIFY_DAYS_TO_EXPIRE = 15;

    const INAPP_POST_CATEGORY = 'novedades';

    const FILTER_EMPLOYER_ME = 'me';
    const FILTER_EMPLOYER_CONTRACTOR = 'contractor';

    const FILTER_WORKPLACE_OWN = 'own';
    const FILTER_WORKPLACE_FOREIGN = 'foreign';

    const MAX_LOGO_SIZE = 4194304; // Maximum logo size 4MB

    public function __construct( $param , $extra = false ){
        $this->tipo = "empresa";
        $this->tabla = TABLE_EMPRESA;

        $this->instance( $param, $extra );
    }

    /**
     * A temporary method to convert a legacy class in a repo/entity class
     * @return Company\Company
     */
    public function asDomainEntity()
    {
        return $this->app['company.repository']->find($this->getUID());
    }

    public static function getRouteName () {
        return 'company';
    }


    /***
       * Get a set of companies, (me and my corp) or (just me).
       *
       *
       *
       * @return ArrayObjectList
       *
       */
    public function getOriginCompanies () {
        $corp = $this->perteneceCorporacion();

        if ($corp) {
            return new ArrayObjectList([$corp, $this]);
        }

        return new ArrayObjectList([$this]);
    }

    /**
     * get the chains between $this and $company, where $this is the first node, limit 30 chains
     * @param  empresa $company
     * @return ArrayObjectList Data for graph view
     */
    public function getChainsWithCompany(empresa $company)
    {
        $cacheString = "chains-from-{$this}-to-{$company}";

        if (($state = $this->cache->getData($cacheString)) !== null) {
            $chainsList = $state;
        } else {
            $startList = $this->getStartIntList();
            if (count($startList) === 0) {
                return false;
            }

            $startComaList = $startList->toComaList();
            $companyUid = $company->getUID();

            $employees = TABLE_EMPLEADO . "_empresa";
            $count = "SELECT count(uid_empleado)
            FROM {$employees} e
            WHERE e.uid_empresa = ej.uid_empresa
            ";

            $hierarchy = TABLE_EMPRESA ."_jerarquia";

            $sql = "SELECT n1, n2, n3, n4
            FROM {$hierarchy} ej
            WHERE n1 IN ({$startComaList})
            AND (
                (n2 = {$companyUid})
                OR  (n3 = {$companyUid})
                OR  (n4 = {$companyUid})
            )
            ORDER BY ({$count}) DESC
            LIMIT 30
            ";

            $chainsList = $this->db->query($sql, true);
            // set here cache in order to get better readability
            $this->cache->addData($cacheString, $chainsList);
        }

        $chains = new ArrayObjectList;

        foreach ($chainsList as $chainIDS) {
            $chain = new ArrayObjectList;
            foreach ($chainIDS as $nodeID) {
                if ($nodeID && is_numeric($nodeID)) {
                    $node = new empresa($nodeID);
                    $chain[] = $node;
                }
            }

            if (count($chain)) {
                $chains[] = $chain;
            }
        }

        return $chains;
    }

    /***
       *
       *
       *
       *
       *
       *
       */
    public function getRelevantChains ($user) {
        $table = TABLE_EMPRESA . "_jerarquia";
        $chains = new ArrayObjectList;

        $indexList = $this->app['index.repository']->getIndexOf(
            'empresa',
            $this->asDomainEntity(),
            $user->asDomainEntity(),
            true
        );

        $dataSetTable = $indexList->toUnionTable();

        $employees = TABLE_EMPLEADO . "_empresa";

        $sql = "SELECT DISTINCT e.uid_empresa AS uid, COUNT(uid_empleado) AS size
                   FROM ($dataSetTable) e 
                   LEFT JOIN {$employees} ee ON ee.uid_empresa = e.uid_empresa
                   GROUP BY e.uid_empresa
                ORDER BY size desc
                LIMIT 30";
        $rows = $this->db->query($sql, true);
        $list = [];
        $sizes = [];

        foreach ($rows as $row) {
            $uid = $row['uid'];
            $list[] = $uid;
            $sizes[$uid] = $row['size'];
        }

        $where = [];
        $where[] = "n1 = {$this->getUID()}";

        if ($list) {
            $set = implode(',', $list);

            $sub = [];
            $sub[] = "(n2 IN ({$set}) AND n3 IS NULL)";
            $sub[] = "(n3 IN ({$set}) AND n4 IS NULL)";


            $where[] = "(". implode(" OR ", $sub) .")";
        }

        $where = implode(" AND ", $where);
        $sql = "SELECT n1, n2, n3, n4 FROM {$table} WHERE {$where}";

        $rows = $this->db->query($sql, true);

        if (!$rows) {
            return $chains;
        }

        foreach ($rows as $i => $row) {
            $chain = new ArrayObjectList;
            foreach ($row as $n => $uid) {
                if ($uid) {
                    $child = new self($uid);

                    if (isset($sizes[$uid])) {
                        $child->size = $sizes[$uid];
                    }

                    $chain[] = $child;
                }
            }

            $chains[] = $chain;
        }

        return $chains;
    }


    /**
     * Set the company logo
     * @throws Exception If an error occurs scaling the image or the image is too big
     * @param  string $path The logo path or an uri
     * @return bool   Returns true if the image has been set as company logo
     */
    public function setLogo ($path)
    {
        $ext = archivo::getExtension($path);

        $whiteList = ['jpg', 'jpeg', 'png'];

        if (in_array($ext, $whiteList) === false) {
            throw new Exception(_("The image has not a valid format"));
        }

        if (strpos($path, 'http') !== false) {
            if (url_exists($path) === false) {
                throw new Exception(_("The image has not found"));
            }
            $data = file_get_contents($path);
        } else {
            $data = archivo::tmp($path);
        }

        $size = strlen($data);

        if ($size > self::MAX_LOGO_SIZE) {

            throw new Exception(str_replace(
                '%s',
                archivo::formatBytes(self::MAX_LOGO_SIZE),
                _('The image size is too big, try to upload an image smaller than %s')
            ));
        }

        $temp = tempnam('/tmp', 'logo-') . "." . $ext;
        if (file_put_contents($temp, $data) === false) {
            throw new Exception(_("Error writing the image"));
        }

        if ($ext === 'png') {
            $tempJpg = tempnam('/tmp', 'logo-') . ".jpg";
            exec("convert -flatten $temp $tempJpg");
            $temp = $tempJpg;
        }

        $imagineGd = new Imagine\Gd\Imagine();
        $image = $imagineGd->open($temp);

        try {
            $image->thumbnail(new Imagine\Image\Box(140, 100), Imagine\Image\ImageInterface::THUMBNAIL_INSET)
                ->save($temp);
        } catch (Exception $e) {
            throw new Exception(_("Error scaling the logo"));
        }

        $link = archivo::getPublicLink($temp, false, true);

        if ($link === false) {
            throw new Exception(_("Error creating the public link"));
        }

        $link = db::scape($link);

        $sql = "UPDATE {$this->tabla} SET logo = '{$link}' WHERE uid_empresa = {$this->getUID()}";
        return $this->db->query($sql);
    }

    public function setTransferPending ($isPending = true) {
        return $this->update(array("is_transfer_pending" => (int) $isPending), elemento::PUBLIFIELDS_MODE_SYSTEM);
    }

    public function hasTransferPending () {
        return  (bool) $this->obtenerDato("is_transfer_pending");
    }

    /**
     * Returns a SQL Select to filter employees wich are working for this company (sub)contracts
     * @param  string $module empleado|maquina
     * @return string the SQL Select
     */
    public function getForeignChildsSqlFilter($module, $user)
    {
        if (false === in_array($module, ['empleado', 'maquina'])) {
            throw new \InvalidArgumentException;
        }

        $itemCompany = constant('TABLE_' . strtoupper($module)) . '_empresa';
        $indexList = (string) $this->app['index.repository']->getIndexOf(
            'empresa',
            $this->asDomainEntity(),
            $user->asDomainEntity(),
            true
        );

        $primaryKey = 'uid_' . $module;

        /**
         * This SQL returns all the employees currently working for any (sub)contract
         *
         * We cannot call self::getViewIndexTable($module, $user, $network = false) because
         * what we need is a list of employees that works for any of our contracts
         * or subcontracts, and that call will give us the list of our emplyees. Since
         * employees can work in more than one company we can't use a NOT IN (LIST) condition
         *
         * @var string
         */
        $allButMines = "SELECT {$primaryKey}
        FROM {$itemCompany} ic
        WHERE 1
        AND ic.{$primaryKey} = {$primaryKey}
        AND uid_empresa IN ({$indexList})
        AND papelera = 0
        AND uid_empresa NOT IN ({$this->getStartIntList()})";

        return $allButMines;
    }

    public function getRequestFilter($module = null, Iusuario $user = null)
    {
        $cacheString = __CLASS__ . '-' . __FUNCTION__ . '-' . $this->getUID() . $module . $user;
        $app = Dokify\Application::getInstance();
        $cache = $app['cache'];
        $cacheItem = $cache->getItem($cacheString);

        if (true === $cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $esCorporacion = $this->esCorporacion();
        $hiddenCompanies = $user instanceof Iusuario ? $user->getCompaniesWithHiddenDocuments(true) : [];
        $agrupadoresPropiosEmpresaInicial = $this->obtenerAgrupadoresPropios();



        $condiciones = array();
        $views = $this->getStartIntList();
        $empresa = ($corp = $this->perteneceCorporacion()) ? $corp : $this;

        foreach ($views as $uid){
            $empresaReferencia = new empresa($uid);

            $list = $empresaReferencia->obtenerEmpresasSolicitantes();
            if( !is_traversable($list) || !count($list) ){ return false; }
            $empresasSolicitantes = count($list) ? $list->toComaList() : '0';

            $superiores = $empresaReferencia->obtenerEmpresasSuperiores();
            $empresasSuperiores = count($superiores) ? $superiores->toComaList() : '0';

            // Agrupadores asignados
            $agrupadores = $empresaReferencia->obtenerAgrupadores();

            // Agrupadores propios de la corporacion
            if( $esCorporacion && $agrupadoresPropiosEmpresaInicial ){
                $agrupadores = $agrupadores->merge($agrupadoresPropiosEmpresaInicial);
            }

            // Agrupadores de esta empresa en concreto
            if($agrupadoresPropiosEmpresa = $empresaReferencia->obtenerAgrupadoresPropios() ){
                $agrupadores = $agrupadores->merge($agrupadoresPropiosEmpresa);
            }

            if( $indirectos = $empresaReferencia->obtenerSolicitantesIndirectos() ){
                $agrupadores = $agrupadores->merge($indirectos);
            }

            if ($coordinatedGroups = $empresaReferencia->getCoordinatedGroups()) {
                $agrupadores = $agrupadores->merge($coordinatedGroups);
            }

            // Convertir en una liesta
            $agrupadores = $agrupadores && count($agrupadores) ? $agrupadores->toComaList() : '0';

            // Para empezar, empresas solicitantes
            $visibleCompanies = count($hiddenCompanies) ? $list->discriminar($hiddenCompanies) : $list;
            $visibleCompaniesList = count($visibleCompanies) ? $visibleCompanies->toComaList() : '0';
            $condicion = "( uid_empresa_propietaria IN ({$visibleCompaniesList}) )";

            // Todos los agrupadores tipo de empresa
            $sqlAgrupadoresEmpresa = "SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ." INNER JOIN ". TABLE_AGRUPAMIENTO ." ta USING(uid_agrupamiento) WHERE uid_categoria =" . categoria::TYPE_TIPOEMPRESA . " AND ta.uid_empresa IN ({$empresasSolicitantes}) ";

            $agrupadoresEmpresa = $this->db->query($sqlAgrupadoresEmpresa, "*", 0);
            $agrupadoresEmpresa = $agrupadoresEmpresa && count($agrupadoresEmpresa) ? implode(",", $agrupadoresEmpresa) : '0';

            /*

            QUIZAS SERÍA MAS OPTIMO PEDIR LOS UIDS, PERO TENDRÍAMOS IMPLICACIONES CON LA CACHE

            $sqlEmpleados = "SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa WHERE uid_empresa = {$empresaReferencia->getUID()})";
            $uidEmpleados = ( $uids = $this->db->query($sqlEmpleados, "*", 0) && count($uids) ) ? implode(",", $uids) : "0";

            $sqlMaquinas = "SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa WHERE uid_empresa = {$empresaReferencia->getUID()})";
            $uidMaquinas = ( $uids = $this->db->query($sqlMaquinas, "*", 0) && count($uids) ) ? implode(",", $uids) : "0";

            */
            // Si es un documento de subcontratacion, esta empresa debe estar en la lista, o bien ser la propia empresa
            // Los comentarios dentro de la SQL a continuación son ABSOLUTAMENTE NECESARIOS si queremos reutilizar esta SQL modulo por modulo
            /* Cuando un elemento_documento esta referenciado por una empresa inferior los documento NO se estaban mostrando ya que
             no se cumple la condición de  "uid_empresa_referencia = 0 " y tampoco se cumple "uid_empresa = {$empresaReferencia->getUID()}"
             ya que el documento no es de la empresa actual sino que es de una empresa inferior. Asi que he añadido los dos ultimos OR's
             a la siguiente condición buscando en table_maquina/empleado_empresa para los uid_empresa de la lista de empresas inferiores de las
             que tenemos visibilidad.
            */

            $empresasInferiores = $empresaReferencia->getAllCompaniesIntList($user, ['strict' => false]);
            $listaEmpresasInferiores = count($empresasInferiores) ? $empresasInferiores->toComaList() : '0';

            // Si nuestro usuario es de una corporación necesitaremos ajustar la sql para ver todos los documentos
            // referenciados a cada una de sus contratas inferiores
            $compareToUidEmpresa = "(
                SELECT uid_empresa_inferior
                FROM ". TABLE_EMPRESA ."_relacion r INNER JOIN ". TABLE_EMPRESA ." em ON r.uid_empresa_superior = em.uid_empresa
                WHERE uid_empresa_superior = uid_empresa_propietaria
                AND uid_empresa_inferior = uid_empresa_referencia
                AND activo_corporacion = 1
                AND uid_empresa_superior = {$this->getUID()}
            )";

            $getFirstFromSet = db::getFirstFromSet("uid_empresa_referencia");

            $manualVisibility = " (CASE
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_COMPANY ." THEN (
                    1
                )
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_CHAIN ." THEN (
                    $getFirstFromSet IN ({$visibleCompaniesList})
                )
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_CONTRACTS ." THEN (
                    1
                ) ELSE (
                    uid_empresa_referencia = 0
                )
            END) ";


            // ---- podríamos pensar que hacer 2 SQL para preguntar por una columna que se puede hacer
            // ---- simplemente con un OR es una locura, pero la mejora de rendimiento es sustancial
            // ---- con respecto a si hacemos uid_elemento_destino IN (n3, n4)
            $empresasLimitOrigin = "(
                uid_elemento_destino IN ($views)
                OR
                IF (
                    (
                        SELECT n1
                        FROM ". TABLE_EMPRESA ."_contratacion
                        WHERE n4 = uid_elemento_destino
                        AND n3 = {$empresaReferencia->getUID()}
                        AND FIND_IN_SET(n1, uid_empresa_views)
                        LIMIT 1
                    )
                    OR
                    (
                        SELECT n1
                        FROM ". TABLE_EMPRESA ."_contratacion
                        WHERE n4 = uid_elemento_destino
                        AND n2 = {$empresaReferencia->getUID()}
                        AND FIND_IN_SET(n1, uid_empresa_views)
                        LIMIT 1
                    )
                    OR
                    (
                        SELECT n1
                        FROM ". TABLE_EMPRESA ."_contratacion
                        WHERE n3 = uid_elemento_destino
                        AND n2 = {$empresaReferencia->getUID()}
                        AND FIND_IN_SET(n1, uid_empresa_views)
                        LIMIT 1
                    ), TRUE, FALSE
                )
            )";


            // Si un documento está referenciado por empresa cuando miramos si esa referencia es una de nuestras contratas
            // debemos mirar si es una contrata directa
            // or
            // si es una subcontrata a la que nosotros hemos dado visibilidad para la empresa que solicita
            // or
            //
            $isVisibleReference = "(
                (
                    SELECT n1
                    FROM ". TABLE_EMPRESA ."_contratacion
                    WHERE 1
                    AND FIND_IN_SET(n1, uid_empresa_views)
                    AND n1 = {$empresaReferencia->getUID()}
                    AND n2 = uid_empresa_referencia
                    LIMIT 1
                )
                OR
                (
                    SELECT n1
                    FROM ". TABLE_EMPRESA ."_contratacion
                    WHERE 1
                    AND FIND_IN_SET(n1, uid_empresa_views)
                    AND n2 = {$empresaReferencia->getUID()}
                    AND n3 = uid_empresa_referencia
                    LIMIT 1
                )
                OR
                (
                    SELECT n1
                    FROM ". TABLE_EMPRESA ."_contratacion
                    WHERE 1
                    AND FIND_IN_SET(n1, uid_empresa_views)
                    AND n2 = {$empresaReferencia->getUID()}
                    AND n4 = uid_empresa_referencia
                    LIMIT 1
                )
                OR
                (
                    SELECT n1
                    FROM ". TABLE_EMPRESA ."_contratacion
                    WHERE 1
                    AND FIND_IN_SET(n1, uid_empresa_views)
                    AND n3 = {$empresaReferencia->getUID()}
                    AND n4 = uid_empresa_referencia
                    LIMIT 1
                )
            )";


            $getLastFromSet = db::getLastFromSet("uid_empresa_referencia");
            $companyReferenceVisibility = " (CASE
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_COMPANY ." THEN (
                    uid_empresa_referencia = {$empresaReferencia->getUID()}
                    OR  (
                        uid_empresa_referencia IN ({$listaEmpresasInferiores})
                        AND
                        {$isVisibleReference}
                    )
                    OR  ((uid_empresa_referencia IN (uid_empresa_views)) AND FIND_IN_SET({$empresaReferencia->getUID()}, uid_empresa_views))
                    OR  uid_empresa_referencia = 0
                )
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_CHAIN ." THEN (
                    FIND_IN_SET('{$empresaReferencia->getUID()}', uid_empresa_referencia)

                )
                WHEN referenciar_empresa = ". documento_atributo::REF_TYPE_CONTRACTS ." THEN (
                    uid_empresa_referencia != 0
                ) ELSE (
                    uid_empresa_referencia = 0
                )
            END) ";

            $empleadosMios = "SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa WHERE uid_empresa = {$empresaReferencia->getUID()}";
            $empleadosVisibles = "SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_visibilidad WHERE uid_empresa = {$empresaReferencia->getUID()}";
            $empresasEmpleadoVisible = "SELECT uid_empresa_referencia FROM ". TABLE_EMPLEADO ."_visibilidad WHERE uid_empresa = {$empresaReferencia->getUID()} AND uid_empleado = uid_elemento_destino";
            $empleadosLimitOrigin = "(
                IF (
                    (
                        SELECT v.uid_empresa FROM ". TABLE_EMPLEADO ."_visibilidad v
                        WHERE FIND_IN_SET(v.uid_empresa, uid_empresa_views)
                        AND v.uid_empresa_referencia IN ($listaEmpresasInferiores)
                        AND v.uid_empleado = uid_elemento_destino
                        LIMIT 1
                    ),
                    TRUE,
                    FALSE
                )
                OR ( uid_elemento_destino IN (
                    SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa WHERE uid_empresa = uid_empresa_propietaria AND uid_empresa IN ($views)
                ))
            )
            ";

            $maquinasMias = "SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa WHERE uid_empresa = {$empresaReferencia->getUID()}";
            $maquinasVisibles = "SELECT uid_maquina FROM ". TABLE_MAQUINA ."_visibilidad WHERE uid_empresa = {$empresaReferencia->getUID()}";
            $empresasMaquinaVisible = "SELECT uid_empresa_referencia FROM ". TABLE_MAQUINA ."_visibilidad WHERE uid_empresa = {$empresaReferencia->getUID()} AND uid_maquina = uid_elemento_destino";

            $maquinasLimitOrigin = "(
                IF (
                    (
                        SELECT v.uid_empresa FROM ". TABLE_MAQUINA ."_visibilidad v
                        WHERE FIND_IN_SET(v.uid_empresa, uid_empresa_views)
                        AND v.uid_empresa_referencia IN ($listaEmpresasInferiores)
                        AND v.uid_maquina = uid_elemento_destino
                        LIMIT 1
                    ),
                    TRUE,
                    FALSE
                )
                OR ( uid_elemento_destino IN (
                    SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa WHERE uid_empresa = uid_empresa_propietaria AND uid_empresa IN ($views)
                ))
            )
            ";

            $isValidSource = "IF (generated_by IS NULL, TRUE,
                FIND_IN_SET({$empresaReferencia->getUID()}, generated_by)
                OR
                FIND_IN_SET({$empresa->getUID()}, generated_by)
            )";


            $condicion .= " AND
            (
                uid_empresa_propietaria = {$empresaReferencia->getUID()}
                OR uid_empresa_propietaria = {$empresa->getUID()}
                OR (
                    -- start-empresa
                        (
                        da.uid_modulo_destino = 1
                        AND $manualVisibility
                        AND $empresasLimitOrigin
                        AND ($companyReferenceVisibility)
                        AND (uid_elemento_destino = {$empresaReferencia->getUID()} OR uid_elemento_destino IN ({$listaEmpresasInferiores}))
                    )
                    -- end-empresa

                    -- start-empleado
                    OR  (
                        da.uid_modulo_destino = 8
                        AND $manualVisibility
                        AND IF (
                            referenciar_empresa = ". documento_atributo::REF_TYPE_COMPANY .",
                            (
                                uid_empresa_referencia IN ($empresasEmpleadoVisible)
                                OR uid_empresa_referencia = {$empresaReferencia->getUID()}
                            ),
                            TRUE
                        )
                        AND $empleadosLimitOrigin
                        AND (uid_elemento_destino IN ($empleadosMios) OR uid_elemento_destino IN ($empleadosVisibles))
                        AND ($companyReferenceVisibility)
                        AND (
                            IF (uid_modulo_origen = 11 AND uid_elemento_destino IN ({$empleadosMios}),
                                {$isValidSource},
                                TRUE
                            )
                        )
                    )
                    -- end-empleado

                    -- start-maquina
                    OR  (
                        da.uid_modulo_destino = 14
                        AND $manualVisibility
                        AND IF (
                            referenciar_empresa = ". documento_atributo::REF_TYPE_COMPANY .",
                            (
                                uid_empresa_referencia IN ($empresasMaquinaVisible)
                                OR uid_empresa_referencia = {$empresaReferencia->getUID()}
                            ),
                            TRUE
                        )
                        AND $maquinasLimitOrigin
                        AND (uid_elemento_destino IN ($maquinasMias) OR uid_elemento_destino IN ($maquinasVisibles))
                        AND ($companyReferenceVisibility)
                        AND (
                            IF (uid_modulo_origen = 11 AND uid_elemento_destino IN ({$maquinasMias}),
                                {$isValidSource},
                                TRUE
                            )
                        )
                    )
                    -- end-maquina
                )

            )";

            $subCondiciones = array();
            $subCondiciones[] = "(uid_modulo_origen = 1 AND uid_elemento_origen IN ({$empresasSolicitantes}))";
            $subCondiciones[] = "(uid_modulo_origen = 11 AND uid_elemento_origen IN ({$agrupadoresEmpresa}))";
            $subCondiciones[] = "(uid_modulo_origen = 11 AND uid_elemento_origen IN ({$agrupadores}))";
            $subCondiciones[] = "(uid_modulo_origen = 12 AND uid_elemento_origen IN (SELECT uid_agrupamiento FROM ". TABLE_AGRUPADOR ." WHERE uid_agrupador IN ({$agrupadores})))";

            $condicion .= " AND (" . implode(" OR ", $subCondiciones) . ")";


            // Si hay mas de una empresa, tenemos que separar en subconjuntos de empresas
            if( count($views) > 1 ){

                $subCondiciones = array();

                $condicion .= " AND (

                    -- start-empresa
                    ( da.uid_modulo_destino = 1 AND uid_elemento_destino IN ({$listaEmpresasInferiores}))
                    -- end-empresa

                    -- start-empleado
                    OR ( da.uid_modulo_destino = 8 AND uid_elemento_destino IN (
                        SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa WHERE uid_empresa IN ({$listaEmpresasInferiores}) AND papelera = 0
                    ))
                    -- end-empleado

                    -- start-maquina
                    OR ( da.uid_modulo_destino = 14 AND uid_elemento_destino IN (
                        SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa WHERE uid_empresa IN ({$listaEmpresasInferiores}) AND papelera = 0
                    ))
                    -- end-maquina

                )
                ";
            }


            $condiciones[] = $condicion;
        }

        if (count($condiciones) && is_array($condiciones)) {
            $filter = " (" . implode(" OR ", $condiciones) .")";

            if ($module) {
                $filter = perfil::transformSQLForView($filter, $module);
            }

            $cacheItem->set($filter);
            $cacheItem->setExpiration(60);

            $app['cache']->save($cacheItem);

            return $filter;
        }
    }


    public function updateEmployeeList ($list, usuario $user) {
        return $this->updateItemList($list, 'empleado', $user);
    }


    /***
       *
       *
       *
       *
       *
       *
       */
    public function updateMachineList ($list, usuario $user) {
        return $this->updateItemList($list, 'maquina', $user);
    }



    /***
       *
       *
       *
       *
       *
       *
       */
    private function updateItemList ($list, $type, $user) {
        // check if we have ids
        if (isset($list[0]) && is_numeric($list[0])) {
            $intList = new ArrayIntList($list);
            $list = $intList->toObjectList($type);
        }

        // convert to object list
        if (is_array($list)) $list = new ArrayObjectList($list);

        $table = constant('TABLE_' . strtoupper($type)) . '_empresa';
        $primaryKey = "uid_{$type}";
        $valids = count($list) ? $list->toComaList() : "0";

        $sql = "SELECT uid_{$type} FROM {$table}
                WHERE 1
                AND uid_empresa = {$this->getUID()}
                AND {$primaryKey} NOT IN ({$valids})";

        $items = $this->db->query($sql, "*", 0, $type);

        foreach ($items as $item) {
            if (!$item->inTrash($this)) {

                if ($item->enviarPapelera($this, $user)) {
                    $item->actualizarSolicitudDocumentos($user);
                    $item->writeLogUI(logui::ACTION_DISABLE, "uid_empresa:{$this->getUID()}", $user);
                }
            }
        }

        return true;
    }


    /***
       * Get company alert count
       *
       *
       *
       * Get employees with wrong mandatory documents
       * Get employees without assignments
       *
       * Get machines with wrong mandatory documents
       * Get machines without assignments
       *
       */
    public function getAlertCount (Iusuario $user) {
        $company = $user->getCompany();

        $employees = 0;
        if ($user->canAccess($company, \Dokify\AccessActions::EMPLOYEES)) {
            // The "alerts" option means we want only the wrong items but those which the $user can handle. Right now only affects to the documents with status = attached
            $employees = $this->getWrongChilds($user, 'empleado', ['alerts' => true], 'count');
        }

        $machines = 0;
        if ($user->canAccess($company, \Dokify\AccessActions::MACHINES)) { // permiso maquinas FALLA?
            // The "alerts" option means we want only the wrong items but those which the $user can handle. Right now only affects to the documents with status = attached
            $machines = $this->getWrongChilds($user, 'maquina', ['alerts' => true], 'count');
        }

        $invalids = 0;
        if ($user->canAccess($company, \Dokify\AccessActions::REQTYPES)) {
            $invalids = $this->getAlertReqTypes($user, ['mandatory' => true], 'count');
        }

        $assignments = $this->getPendingAssignments($user, ['count' => true]);

        return ($machines + $employees + $invalids + $assignments);
    }


    /***
       *
       *
       *
       *
       *
       *
       */
    public function getActivitySummary ($days = 1, $cache = true) {
        $modules = solicitable::getModules();
        $activity = [
            documento::ESTADO_ANEXADO   => 0,
            documento::ESTADO_VALIDADO  => 0,
            documento::ESTADO_ANULADO   => 0,
            documento::ESTADO_CADUCADO  => 0
        ];


        if ($cache) {
            $sql = "SELECT req_attached, req_validated, req_rejected, req_expired FROM ". TABLE_EMPRESA ." WHERE uid_empresa = {$this->getUID()}";
            $values = $this->db->query($sql, 0, '*');

            $activity = array_combine(array_keys($activity), $values);
            return $activity;
        }


        $validation = TABLE_VALIDATION;
        $attrs = TABLE_DOCUMENTO_ATRIBUTO;
        foreach ($modules as $module) {
            $table = PREFIJO_ANEXOS . $module;
            $historic = PREFIJO_ANEXOS_HISTORICO . $module;
            $primaryKey = "uid_anexo_{$module}";

            $sql = "SELECT count({$primaryKey}) as num
            FROM {$table} INNER JOIN {$attrs} USING (uid_documento_atributo) WHERE 1
            AND uid_empresa_propietaria = {$this->getUID()}
            AND fecha_anexion > UNIX_TIMESTAMP(DATE_ADD(NOW(), interval -{$days} day))";

            // sum attached
            if ($num = $this->db->query($sql, 0, 0)) {
                $activity[documento::ESTADO_ANEXADO] += $num;
            }

            $sql = "SELECT count(uid_anexo_historico_{$module}) as num
            FROM {$historic} INNER JOIN {$attrs} USING (uid_documento_atributo) WHERE 1
            AND uid_empresa_propietaria = {$this->getUID()}
            AND fecha_anexion > UNIX_TIMESTAMP(DATE_ADD(NOW(), interval -{$days} day))";

            // sum historic attached
            if ($num = $this->db->query($sql, 0, 0)) {
                $activity[documento::ESTADO_ANEXADO] += $num;
            }

            $sql = "SELECT count(uid_validation_status) FROM {$validation}
            INNER JOIN {$validation}_status USING (uid_validation)
            INNER JOIN {$table} ON uid_anexo = uid_anexo_{$module}
            INNER JOIN {$attrs} ON {$table}.uid_documento_atributo = {$attrs}.uid_documento_atributo
            WHERE 1
            AND {$attrs}.uid_empresa_propietaria = {$this->getUID()}
            AND validation_status.status = 2
            AND validation.date > DATE_ADD(NOW(), interval -{$days} day)";

            // sum attached
            if ($num = $this->db->query($sql, 0, 0)) {
                $activity[documento::ESTADO_VALIDADO] += $num;
            }

            $sql = "SELECT count(uid_validation_status) FROM {$validation}
            INNER JOIN {$validation}_status USING (uid_validation)
            INNER JOIN {$historic} USING (uid_anexo)
            INNER JOIN {$attrs} ON {$historic}.uid_documento_atributo = {$attrs}.uid_documento_atributo
            WHERE 1
            AND {$attrs}.uid_empresa_propietaria = {$this->getUID()}
            AND validation_status.status = 2
            AND validation.date > DATE_ADD(NOW(), interval -{$days} day)";

            // sum attached
            if ($num = $this->db->query($sql, 0, 0)) {
                $activity[documento::ESTADO_VALIDADO] += $num;
            }

            $sql = "SELECT count(uid_validation_status) FROM {$validation}
            INNER JOIN {$validation}_status USING (uid_validation)
            INNER JOIN {$table} ON uid_anexo = uid_anexo_{$module}
            INNER JOIN {$attrs} ON {$table}.uid_documento_atributo = {$attrs}.uid_documento_atributo
            WHERE 1
            AND {$attrs}.uid_empresa_propietaria = {$this->getUID()}
            AND validation_status.status = 4
            AND validation.date > DATE_ADD(NOW(), interval -{$days} day)";

            // sum attached
            if ($num = $this->db->query($sql, 0, 0)) {
                $activity[documento::ESTADO_ANULADO] += $num;
            }

            $sql = "SELECT count(uid_validation_status) FROM {$validation}
            INNER JOIN {$validation}_status USING (uid_validation)
            INNER JOIN {$historic} USING (uid_anexo)
            INNER JOIN {$attrs} ON {$historic}.uid_documento_atributo = {$attrs}.uid_documento_atributo
            WHERE 1
            AND {$attrs}.uid_empresa_propietaria = {$this->getUID()}
            AND validation_status.status = 4
            AND validation.date > DATE_ADD(NOW(), interval -{$days} day)";

            // sum attached
            if ($num = $this->db->query($sql, 0, 0)) {
                $activity[documento::ESTADO_ANULADO] += $num;
            }


            $sql = "SELECT count({$primaryKey}) as num FROM {$table}
            INNER JOIN {$attrs} USING (uid_documento_atributo)
            WHERE 1
            AND estado = 3
            AND uid_empresa_propietaria = {$this->getUID()}
            AND fecha_actualizacion > DATE_ADD(NOW(), interval -{$days} day)";

            // sum attached
            if ($num = $this->db->query($sql, 0, 0)) {
                $activity[documento::ESTADO_CADUCADO] += $num;
            }


            $sql = "SELECT count(uid_anexo_historico_{$module}) as num
            FROM {$historic}
            INNER JOIN {$attrs}
            ON {$historic}.uid_documento_atributo = {$attrs}.uid_documento_atributo
            WHERE 1
            AND estado = 3
            AND uid_empresa_propietaria = {$this->getUID()}
            AND fecha_actualizacion > DATE_ADD(NOW(), interval -{$days} day)";

            // sum attached
            if ($num = $this->db->query($sql, 0, 0)) {
                $activity[documento::ESTADO_CADUCADO] += $num;
            }


        }

        return $activity;
    }


    /***
       * Returns the related information about the geographical position of the employees of the enterprise network
       * of @param user.
       *
       * @param [Iusario]           $usuario    used for the company or companies list.
       * @param [Array]             $filter     filters for queries.
       *                                [Empresa|Usuario]   "employer"      filter if the employee works for us or
       *                                                                    a contract
       *                                [String]            "workplace"     filter if the employee is working at our
       *                                                                    center or in one foreign
       *                                [String]            "item"          if is a company we will show only their
       *                                                                    employees, if is a employee we will show
       *                                                                    only them.
       * @return [Object]           retuns an object-array with two arrays containing markers and points.
       */
    public function getGeoData(Iusuario $usuario, $filter = null)
    {

        $tpl = Plantilla::singleton();
        $startIntList = $this->getStartIntList();

        $company = $usuario->getCompany();
        $origins = $company->getOriginCompanies();
        $list = $origins->toComaList();
        $workplace = isset($filter['workplace']) ? $filter['workplace'] : null;
        $employer = isset($filter['employer']) ? $filter['employer'] : null;
        $item = isset($filter['item']) ? $filter['item'] : null;
        $locations = ['markers' => [], 'points' => []];
        $employeeTable = TABLE_EMPLEADO;
        $userTable = TABLE_USUARIO;
        $profileTable = TABLE_PERFIL;
        $where = array();

        $sqlOwnUsers = "SELECT uid_usuario FROM {$userTable}
        INNER JOIN {$profileTable}
        USING (uid_usuario)
        WHERE uid_empresa IN ({$list})";

        try {
            if ($workplace) {
                switch ($workplace) {
                    case self::FILTER_WORKPLACE_OWN:
                        $where[] = "uid_usuario_location IN ({$sqlOwnUsers})";
                        break;
                    case self::FILTER_WORKPLACE_FOREIGN:
                        $where[] = "uid_usuario_location NOT IN ({$sqlOwnUsers})";
                        break;
                    default:
                        throw new Exception('Invalid filter params');
                        break;
                }
            }

            if ($employer) {
                switch ($employer) {
                    case self::FILTER_EMPLOYER_ME:
                        $where[] = "uid_empleado IN (SELECT uid_empleado
                        FROM {$employeeTable}_empresa
                        WHERE papelera = 0
                        AND uid_empresa IN ({$list}))";
                        break;
                    case self::FILTER_EMPLOYER_CONTRACTOR:
                        $allCompaniesList = $company->getAllCompaniesIntList($usuario, [
                            'black_list' => $company->getUID()
                        ]);

                        $allCompaniesList = $allCompaniesList->toComaList();

                        if (empty($allCompaniesList)) {
                            throw new Exception('The result list is empty', 1);
                        }

                        $where[] = "uid_empleado IN (SELECT uid_empleado
                        FROM {$employeeTable}_empresa
                        WHERE papelera = 0
                        AND uid_empresa IN ({$allCompaniesList}))";
                        break;
                    default:
                        throw new Exception('Invalid filter params');
                        break;
                }
            }

            if ($item) {
                if ($item instanceof empresa) {
                    $where[] = "uid_empresa = {$item->getUID()}";
                } elseif ($item instanceof empleado) {
                    $where[] = "uid_empleado = {$item->getUID()}";
                } else {
                     throw new Exception('Invalid filter params');
                }
            }
        } catch (Exception $e) {
            if ($e->getCode() != 1) {
                error_log("{$e->getMessage()} - code: {$e->getCode()}");
            }

            return (object) $locations;
        }

        // Build final query
        $visibles = "SELECT uid_empleado, uid_empresa
        FROM {$employeeTable}_empresa
        WHERE papelera = 0
        AND uid_empresa IN ({$startIntList})
        UNION
        SELECT e.uid_empleado, uid_empresa_referencia
        FROM {$employeeTable}_visibilidad v
        INNER JOIN {$employeeTable} e
        ON e.uid_empleado = v.uid_empleado
        WHERE 1
        AND uid_empresa IN ({$startIntList})
        AND uid_usuario_location IN ({$sqlOwnUsers})";

        if ($usuario->isViewFilterByGroups()) {
            $indexList = (string) $this->app['index.repository']->getIndexOf(
                'empleado',
                $this->asDomainEntity(),
                $usuario->asDomainEntity(),
                true
            );

            $where[] = "uid_empleado IN ({$indexList})";
        } else {
            $visibility = null;
        }

        $sql = "SELECT uid_empleado, latlng,
        concat(e.nombre, ' ', e.apellidos) as name,
        e.apellidos, uid_usuario_location
        FROM {$employeeTable} e
        INNER JOIN ({$visibles}) as v
        USING (uid_empleado)
        WHERE latlng != ''
        ";

        if ($where) {
            $sql .= "AND ". implode(" AND ", $where);
        }

        $points = array();
        $results = array();

        $rows = $this->db->query($sql, true);
        if (empty($rows)) {
            return (object) $locations;
        }

        foreach ($rows as $data) {
            $latlng = $data['latlng'];
            $title = utf8_encode($data['name']);
            $append = '';

            if (is_numeric($data['uid_usuario_location']) && $userId = $data['uid_usuario_location']) {
                $user = new usuario($userId);
                $append .= ' ' . $tpl('con') . ' ' . $user->getHumanName();

                $userLatLng = $user->getLatLng();
                $userLatLngArray = explode(',', $userLatLng);
                $userLatLngArray = array_map('floatval', $userLatLngArray);

                $latlngArray = explode(',', $latlng);
                $latlngArray = array_map('floatval', $latlngArray);

                $distance = $this->app['map']->distance($latlngArray, $userLatLngArray);
                // closer than 2km
                $isNear = $distance < 2000;

                // -- use the user if is near and we want to get employees grouped
                if ($isNear) {
                    $latlng = $userLatLng;
                }
            }

            if (isset($results[$latlng])) {
                $results[$latlng]["title"] = ($results[$latlng]['num'] + 1) . " empleados" . $append;
            } else {
                $search = "near:{$data['uid_usuario_location']}";

                if ($item instanceof empresa) {
                    $search .= " empresa:{$item->getUID()}";
                }
                if ($item instanceof empleado) {
                    $search .= " uid:{$item->getUID()}";
                }

                $results[$latlng] = array(
                    "num"       => 0,
                    "title"     => $title . $append,
                    "address"   => explode(',', $latlng),
                    "href"      => "#buscar.php?q={$search}"
                );
            }

            $points[] = $latlng;
            $results[$latlng]['num']++;
        }

        $locations['markers'] = array_values($results);
        $locations['points'] = $points;

        return (object) $locations;
    }

    public function asyncUpdateChildRequests ($assigned = NULL) {
        return archivo::php5exec(DIR_ROOT . "func/cmd/updatechildrequests.php", [$this->getUID(), "$assigned"]);
    }


    /**
     * [countOwnDocuments count the number of active requirements]
     * @return [int] [the number of actived requirements of this company]
     */
    public function countOwnDocuments() {
        return $this->getAttributesDocuments(false, false, true);
    }


    /**
     * [countCorpDocuments count both, the own documents and the documents from the corp if exists]
     * @return [int] [the number of actived requirements of this company and its corp]
     */
    public function countCorpDocuments() {
        return $this->getAttributesDocuments(false, false, true, false, true);
    }

    public function getShortName()
    {
        $name = trim(preg_replace('/\s*\([^)]*\)/', '', $this->getUserVisibleName()));

        $patterns = [
            '/ETT/',
            '/(, *| +)S\.A\.U/',
            '/(, *| +)SAU/',
            '/(, *| +)S\.A/',
            '/(, *| +)SA(\W\Z|\Z)/',
            '/(, *| +)S A/',
            '/(, *| +)S\.L\.U/',
            '/(, *| +)S\.L\.U\./',
            '/(, *| +)SLU/',
            '/(, *| +)S\.L/',
            '/(, *| +)S\.L/',
            '/(, *| +)SL/',
            '/(, *| +)CB/',
            '/(, *| +)C\.B/'
        ];

        $name = rtrim(preg_replace($patterns, '', $name), '.');
        return $name;
    }

    public function obtenerLogo($default=true) {

        $nombreLogo = $this->obtenerDato("logo");
        if (!trim($nombreLogo) && $corp = $this->perteneceCorporacion()){
            $nombreLogo = $corp->obtenerDato("logo");
        }

        if (trim($nombreLogo)){
            $pieces = parse_url($nombreLogo);

            if (strpos($nombreLogo, 'http') !== false) {
                return $nombreLogo;
            }

            $posibles = glob( DIR_LOGOS . $nombreLogo . ".*"  );
            $logo = basename(reset($posibles));

            if (!trim($logo)){
                if ($default == false) return false;
                return RESOURCES_DOMAIN . "/img/dokify-google-logo.png";
            }
            return RESOURCES_DOMAIN . "/img/logos/$logo";
        }

        if ($default == false) return false;
        return RESOURCES_DOMAIN . "/img/dokify-google-logo.png";
    }

    public function getCountry(){

        $cacheString = __CLASS__."-".__FUNCTION__."-".$this->getUID();
        if (($dato = $this->cache->getData($cacheString)) !== null) return pais::factory($dato);

        $idCountry = $this->obtenerDato("uid_pais");

        if (is_numeric($idCountry) && $idCountry > 0) {
            $pais = new pais($idCountry);
        } else {
            $this->update(array("uid_pais"=>pais::SPAIN_CODE));
            $pais = $this->getCountry();
        }

        $this->cache->set($cacheString, "$pais");
        return $pais;

    }

    public function getId($toLower = false){
        $cif = trim($this->obtenerDato("cif"));
        if ($toLower) $cif = strtolower($cif);
        return $cif;
    }

    public function getTax(){
        return 21;
    }


    public function getAddress(){
        $info = $this->getInfo();
        return $info["direccion"];
    }

    public function getPostalCode(){
        $info = $this->getInfo();
        return $info["cp"];
    }
    /**
      * Recuperar un ArrayObjectList de objetos "dataexport" que los usuarios activos de esta empresa han generado y han marcado como publicos
      *
      * @return ArrayObjectList | dataexport
      */
    public function getPublicDataExports(Iusuario $user = null)
    {
        $dataExportTable = TABLE_DATAEXPORT;
        $userTable = TABLE_USUARIO;
        $profileTable = TABLE_PERFIL;

        $companyUid = $this->getUID();
        $companyCondition = "uid_empresa = {$companyUid}";

        if ($corporation = $this->perteneceCorporacion()) {
            $corporationUid = $corporation->getUID();
            $companyCondition = "(" . $companyCondition . " OR uid_corporation = {$corporationUid})";
        }

        $publicCondition = "(
            uid_usuario IN (
                SELECT uid_usuario
                FROM {$profileTable}
                WHERE
                {$companyCondition}
                AND papelera = 0
            )
            AND config_admin = 0
            AND config_sat = 0
        )";

        $sql = "
            SELECT  uid_dataexport
            FROM    {$dataExportTable}
            JOIN    {$userTable} USING (uid_usuario)
            WHERE is_public = 1
            AND {$publicCondition}
        ";

        if ($user) {
            $userUid = $user->getUID();
            $userCondition = "(uid_usuario = {$userUid})";
            $sql .= " OR {$userCondition}";
        }

        $items = $this->db->query($sql, "*", 0, "dataexport");
        return new ArrayObjectList($items);
    }

    /**
    * Devuelve la información que se muestra en la ficha de empleado.
    */
    public function getMiniArray(Iusuario $usuario = null) {
        $inline = $this->getInlineArray($usuario);

        $miniArray = array();
        $miniArray['nombre'] = $this->getUserVisibleName();
        $miniArray['href'] = $this->obtenerUrlFicha();
        $miniArray['inlineArray'] = reset($inline); // solo nos interesa el primer elemento
        $miniArray['hrefdocs'] = '#documentos.php?m=empresa&poid='.$this->getUID();
        $miniArray['imgdocs'] = RESOURCES_DOMAIN . "/img/famfam/folder.png";
        $miniArray['estado'] = $this->getStatusImage($usuario);
        return $miniArray;
    }

    public function getTreeData(Iusuario $usuario, $extraData = array()){

        if (isset($extraData[Ilistable::DATA_CONTEXT])){
            if (isset($extraData[Ilistable::DATA_ELEMENT])){
                $elemento = $extraData[Ilistable::DATA_ELEMENT];
                return array(
                    "img" => array(
                        "normal" => RESOURCES_DOMAIN ."/img/famfam/folder.png",
                        "open" => RESOURCES_DOMAIN ."/img/famfam/folder_table.png"
                    ),
                    "url" => "../agd/list.php?comefrom={$elemento->getType()}&m=empresa&action=CarpetasDocumentosDescargables&poid={$elemento->getUID()}&data[context]=descargables&data[parent]=$elemento&params[]={$this}&params[]={$usuario}&options=0"
                );
            }
        } else return false;
    }

    /** Recuperar un ArrayObjectList de objetos "empresasolicitud" en estado pendiente de acción por parte del usuario
      *
      * @return ArrayObjectList | empresasolicitud
      */
    public function solicitudesPendientesElementos($filter = NULL){
        $sql = "SELECT uid_empresa_solicitud FROM ". TABLE_EMPRESA ."_solicitud
        WHERE uid_empresa = {$this->getUID()} AND estado = " . solicitud::ESTADO_CREADA;
        if (is_traversable($filter)) {
            foreach ($filter as $field => $value) {
                if (!empty($field)) {
                    $sql .= " AND {$field} = '{$value}'";
                }
            }
        }
        $items = $this->db->query($sql, "*", 0, 'empresasolicitud');
        return new ArrayObjectList($items);
    }

    public function solicitudesPendientesFiltros($filter = NULL){
        $sql = "SELECT uid_empresa_solicitud FROM ". TABLE_EMPRESA ."_solicitud
        WHERE estado = " . solicitud::ESTADO_CREADA;
        if (is_traversable($filter)) {
            foreach ($filter as $field => $value) {
                if (!empty($field)) {
                    $sql .= " AND {$field} = '{$value}'";
                }
            }
        }
        $items = $this->db->query($sql, "*", 0, 'empresasolicitud');
        return new ArrayObjectList($items);
    }


    public function getAllCompaniesIntList (Iusuario $user = null, $sqlOptions = []) {
        $count = isset($sqlOptions['count']) ? $sqlOptions['count'] : false;
        $limit = isset($sqlOptions['limit']) ? $sqlOptions['limit'] : false;
        $order = isset($sqlOptions['order']) ? $sqlOptions['order'] : false;
        $strict = isset($sqlOptions['strict']) ? $sqlOptions['strict'] : true;
        $query = isset($sqlOptions['q']) ? utf8_decode(db::scape($sqlOptions['q'])) : false;


        $blackList = isset($sqlOptions['black_list']) ? $sqlOptions['black_list'] : false;

        // convert to a set
        if ($blackList instanceof self) {
            $blackList = $blackList->getUID();
        } elseif (is_array($blackList)) {
            $blackList = implode(',', $blackList);
        }

        $cacheKey = implode('-', [$this, __FUNCTION__, $user, $count, ($limit ? implode(',', $limit) : $limit), $order, $strict, $query, $blackList]);
        if (null !== ($set = $this->cache->getData($cacheKey))) {
            return ArrayIntList::factory($set);
        }

        $userAsDomainEntity = $legacyUser = null;

        if ($user instanceof usuario) {
            $legacyUser = $user;
        }

        if ($user instanceof perfil) {
            $legacyUser = $user->getUser();
        }

        if ($legacyUser instanceof usuario) {
            $userAsDomainEntity = $legacyUser->asDomainEntity();
        }

        $table = TABLE_EMPRESA;
        if (true === (bool) $strict) {
            $this->app['index.repository']->expireIndexOf(
                'empresa',
                $this->asDomainEntity(),
                $userAsDomainEntity,
                true
            );
        }

        $indexList = (string) $this->app['index.repository']->getIndexOf(
            'empresa',
            $this->asDomainEntity(),
            $userAsDomainEntity,
            true
        );

        $field = $count ? "count(uid_empresa)" : "uid_empresa";
        $sql = "SELECT {$field} FROM {$table} e WHERE uid_empresa IN ({$indexList})";

        if ($blackList) {
            $sql .= " AND uid_empresa NOT IN ({$blackList})";
        }

        if ($query) {
            $sql .= " AND (nombre LIKE '%{$query}%' OR cif LIKE '%{$query}%')";
        }

        if ($count) {
            return  $this->db->query($sql, 0, 0);
        }


        switch ($order) {
            case 'relevance':
                $relationship = TABLE_EMPLEADO . '_empresa';
                $count = "SELECT count(uid_empleado) FROM {$relationship} r WHERE r.uid_empresa = i.uid_empresa AND papelera = 0";

                $sql .= " ORDER BY ({$count}) DESC";
                break;

            default:
                $sql .= " ORDER BY nombre";
                break;
        }


        if ($limit) {
            $sql .= " LIMIT {$limit[0]}, {$limit[1]}";
        }

        if ($uids = $this->db->query($sql, '*', 0)) {
            $intList = new ArrayIntList($uids);
        } else {
            $intList = new ArrayIntList;
        }

        $this->cache->set($cacheKey, $intList->toComaList());
        return $intList;
    }

    public function getCompaniesInTrash ($sqlOptions = [], $returnType = 'objectList') {
        $limit = isset($sqlOptions['limit']) ? $sqlOptions['limit'] : false;
        $query = isset($sqlOptions['q']) ? utf8_decode(db::scape($sqlOptions['q'])) : false;

        $key = "uid_empresa_inferior";
        $field = $returnType == 'count' ? "count({$key})" : $key;
        $table = TABLE_EMPRESA ."_relacion INNER JOIN ". TABLE_EMPRESA." ON uid_empresa_inferior = uid_empresa";
        $sql = "SELECT {$field} FROM {$table} WHERE uid_empresa_superior = {$this->getUID()} AND papelera = 1";

        if ($query) {
            $sql .= " AND (nombre LIKE '%{$query}%' OR cif LIKE '%{$query}%')";
        }

        $sql .= " ORDER BY nombre";

        if ($limit) {
            $sql .= " LIMIT {$limit[0]}, {$limit[1]}";
        }

        switch ($returnType) {
            case 'count':
                return (int) $this->db->query($sql, 0, 0);
            case 'objectList':
                $items = $this->db->query($sql, "*", 0, "empresa");;
                return new ArrayObjectList($items);
        }
    }

    public function getStartList(){
        return $this->esCorporacion() ? $this->obtenerEmpresasInferioresMasActual() : new ArrayObjectList(array($this));
    }

    public function getNoCorporationStartList(){
        return $this->esCorporacion() ? $this->obtenerEmpresasInferiores() : new ArrayObjectList(array($this));
    }

    public function getStartIntList(){
        return $this->esCorporacion() ? $this->getStartList()->toIntList() : new ArrayIntList(array($this->getUID()));
    }


    /**
      * Nos indicará si trabaja para la empresa @param
      *
      *
      * @param [] · número indeterminado de parametros, representa la cadena de subcontratacion
      *
      * @return bool
      *
      **/
    public function esSubcontrataDe(){
        $args = func_get_args();
        if( count($args) < 2 ) throw new Exception("Should pass at least two companies");


        $sql = "SELECT count(*) FROM ". TABLE_EMPRESA . "_contratacion WHERE 1";

        // Almacenar filtros
        $where = array();

        // Aplicar el filtro de la empresa actual
        $maxdepth = count($args) + 1;
        $where[] = "n{$maxdepth} = {$this->getUID()}";

        // Crear filtros a partir de los argumentos
        foreach($args as $i => $sup){
            if (is_null($sup)) {
                continue;
            }
            $level = $maxdepth - 1 - $i;
            $where[] = "n{$level} = {$sup->getUID()}";
        }

        if( count($args) < 3 ){
            $where[] = "(n4 = 0 OR n4 IS NULL)";
        }


        $sql .= " AND ". implode(" AND ", $where);
        $res = $this->db->query($sql, 0, 0);
        // dump($sql, $res);

        return (bool) $res;
    }

    public function obtenerEmpresasClienteConDocumentos($excludeCorporations = false) {
        $clientsWithDocuments = new ArrayObjectList;
        $clients = $this->obtenerEmpresasCliente();

        foreach ($clients as $client) {
            if (true === $excludeCorporations && true === $client->esCorporacion()) {
                continue;
            }

            $hasDocuments = (bool) $client->countOwnDocuments();

            if ($hasDocuments||$client->perteneceCorporacion()) {
                $clientsWithDocuments[] = $client;
            }
        }

        return $clientsWithDocuments;
    }


    /***
       * Return the ArrayObjectLiat with the client companies for this item, but only those the $user can see
       *
       * @param Iusuario $user - The user filter
       * @param $requesting (bool) - If we show the clients who request something
       *
       *
       */
    public function getClientCompanies(Iusuario $user, $requesting = null) {
        return $this->obtenerEmpresasCliente(null, $user);
    }


    /**
      * Nos retornará las empresas clientes de esta empresa, pero a través de @param
      *
      *
      * @param empresa · La empresa 'intermedia'
      *
      * @return ArrayObjectList | false
      *
      **/
    public function obtenerEmpresasCliente(empresa $empresa = NULL, $user = NULL) {
        if (($cacheKey = implode('-', array(__FUNCTION__, $this, $empresa, $user))) && ($val = $this->cache->getData($cacheKey)) !== null) {
            // si en la cache no tenemos empresas
            if ($val === false) return new ArrayObjectList();

            // si tenemos la lista de empresas la recuperamos
            $intList = ArrayIntList::factory($val);
            return $intList->toObjectList('empresa');
        }

        // if we are the same, dont filter anything
        if ($this->compareTo($empresa)) $empresa = NULL;

        // if we are a corp watching our companies, or contracts of our companies...
        if ($empresa instanceof empresa && $empresa->esCorporacion()) {
            $startList = $empresa->getStartList();
            if ($startList->contains($this)) $empresa = NULL;
            else $empresa = $startList;
        } elseif ($empresa instanceof empresa) {
            $empresa = new ArrayObjectList(array($empresa));
        }

        if ($empresa) {
            $condition = "
                (n1 IN ({$empresa->toComaList()}) AND n2 IN ({$this->getUID()}) AND n4 IS NULL AND n3 IS NULL)
                OR
                (n3 = {$this->getUID()} AND n4 IS NULL AND (n2 IN ({$empresa->toComaList()}) OR n1 IN ({$empresa->toComaList()})))
                OR
                (n4 = {$this->getUID()} AND (n3 IN ({$empresa->toComaList()}) OR n2 IN ({$empresa->toComaList()}) OR n1 IN ({$empresa->toComaList()})))
            ";

        } else {
            $condition = "
                (n3 = {$this->getUID()} AND n4 IS NULL)
                OR
                (n4 = {$this->getUID()})
            ";
        }


        $sql = "SELECT n1, n2, n3 FROM ". TABLE_EMPRESA . "_contratacion WHERE 1 AND ($condition)";
        $chains = $this->db->query($sql, true);

        $relationship = TABLE_EMPRESA ."_relacion";
        $sql = "SELECT uid_empresa_superior as n1, NULL as n2, NULL as n3 FROM {$relationship} WHERE 1
        AND uid_empresa_inferior = {$this->getUID()} AND papelera = 0";
        if ($empresa) {
            $sql .= " AND uid_empresa_superior IN ({$empresa->toComaList()})";
        }

        if ($directs = $this->db->query($sql, true)) {
            $chains = array_merge($chains, $directs);
        }

        $uid = $this->getUID();
        $companies = new ArrayObjectList();

        foreach ($chains as $array) {
            if (isset($array["n1"]) && is_numeric($array["n1"]) && $array["n1"] != $uid) $companies[] = new empresa($array["n1"]);
            if (isset($array["n2"]) && is_numeric($array["n2"]) && $array["n2"] != $uid) $companies[] = new empresa($array["n2"]);
            if (isset($array["n3"]) && is_numeric($array["n3"]) && $array["n3"] != $uid) $companies[] = new empresa($array["n3"]);
        }

        // The limiter user is the user that has limited the visibility to the target user.
        if ($user instanceof usuario) {
            $visiblesCompanies = new ArrayObjectList;
            foreach ($companies as $company) {
                $limiterUser = $user->getUserLimiter($company);
                if ($limiterUser && !$limiterUser->compareTo($user)) continue;
                    $visiblesCompanies[] = $company;
            }
            $companies = $visiblesCompanies;
        }

        $companies = $companies->unique();

        $this->cache->set($cacheKey, (count($companies) ? $companies->toComaList() : false), 60*60*15);
        return $companies;
    }

    /**
      * Nos retornará las empresas clientes de esta empresa, pero a través de @param
      * Si no se pasa parámetro listará todas las empresas superiores que no sean una empresa corporación
      *
      * @param empresa · La empresa 'intermedia'
      *
      * @return ArrayObjectList | false
      *
      **/
    public function obtenerEmpresasSuperioresSubcontratando(empresa $empresa = NULL, empresa $empresaCliente = NULL, $notTrash = true, $user = NULL){

        if ($empresa instanceof empresa) {
            if ($empresaCliente instanceof empresa) {
                $sql = "SELECT n1 FROM ". TABLE_EMPRESA . "_contratacion WHERE 1
                    AND n2 = {$empresaCliente->getUID()} AND n3 = {$this->getUID()} AND n4 = {$empresa->getUID()}
                    GROUP BY n1
                ";
            } else {
                $sql = "SELECT n1 FROM ". TABLE_EMPRESA . "_contratacion WHERE 1
                    AND n2 = {$this->getUID()} AND n3 = {$empresa->getUID()} AND (n4 = 0 OR n4 IS NULL)
                    GROUP BY n1
                ";
            }

            $empresas = $this->db->query($sql, "*", 0, "empresa");

        } else {
            $sql = "SELECT uid_empresa_superior FROM ". TABLE_EMPRESA ."_relacion WHERE uid_empresa_superior NOT IN (
                SELECT uid_empresa FROM ". TABLE_EMPRESA ." WHERE activo_corporacion = 1
            ) AND uid_empresa_inferior = {$this->getUID()} AND uid_empresa_superior != {$this->getUID()}";

            if ($notTrash) $sql .= " AND papelera = 0";

            $empresas = $this->db->query($sql, "*", 0, "empresa");
        }

        if (is_array($empresas)) {

            $companies = new ArrayObjectList($empresas);

            if ($user instanceof usuario) {

                $visiblesCompanies = new ArrayObjectList;
                foreach ($companies as $company) {
                    $limiterUser = $user->getUserLimiter($company);

                    if ($limiterUser && !$limiterUser->compareTo($user)) continue;
                    $visiblesCompanies[] = $company;
                }
                return $visiblesCompanies;
            }
            return $companies;
        }

        return false;
    }

    /**
      * void que actualiza la subcontratación para esta empresa
      *
      *
      * @param args · Lista de empresas superiores de una cadena de subcontratación
      *
      * @return bool
      *
      **/
    public function crearCadenaContratacion(){
        $args = func_get_args();
        if( count($args) < 2 ) throw new Exception("Should pass at least two companies");

        // Profundidad de subcontratación
        $maxdepth = count($args) + 1;

        // Array de campos y valores
        $fields = array("n" . $maxdepth );
        $values = array($this->getUID());

        // Cada nive superior
        foreach($args as $i => $empresa){
            $level = $maxdepth - 1 - $i;
            $fields[] = "n" . $level;
            $values[] = $empresa->getUID();
        }

        if (count(array_unique($values)) !== count($values)) return false;

        // persist tail company
        $tail = reset($values);
        $fields[] = "ntail";
        $values[] = $tail;

        $sql = "INSERT INTO ". TABLE_EMPRESA ."_contratacion (". implode(",", $fields) .") VALUES (". implode(",", $values).")";
        if( !$this->db->query($sql) ) throw new Exception($this->db->lastError());

        $this->cache->clear('obtenerEmpresasCliente*');

        return true;
    }

    protected function crearRelacion($tabla, $actual, $idactual, $asignar, $idasignar){
        $relacion = parent::crearRelacion($tabla, $actual, $idactual, $asignar, $idasignar);

        $this->cache->clear('obtenerEmpresasCliente*');

        return $relacion;
    }


    /**
      * void · elimina toda la cadena de contratacion de esta empresa con relación a la empresa @param
      *
      *
      * @param $empresa - Empresa superior a $this de la que queremos eliminar 'residuos'
      * @param $get - Obtener los residuos...
      * @return bool
      *
      **/
    public function eliminarCadenasContratacionResiduales(empresa $empresa, $get = false){
        /**
          * Cuando se elimina una relación de empresa_contratacion del tipo:
          *     X, N, N, NULL
          *     Donde N = uid de la empresa, X uid de una empresa que no nos interesa y NULL son columnas vacias
          *
          * Es posible que se queden residuos por ejemplo:
          *     N1=X, N2=2, N3=3, N4=0
          * Se quedaría el residuo de todas las empresas inferiores de 3, que trabajan a su vez para 2, es decir: 2,3,(varias empresas)
          * Y debemos eliminarlo
          */

        $cases = array();
        $cases[] = "(
                n2 = {$empresa->getUID()}
            AND n3 = {$this->getUID()}
            AND (n4 != 0 OR n4 IS NOT NULL)
        )";
        $cases[] = "( n2 AND n2 = n3 )";
        $cases[] = "( n3 AND n3 = n4 )";

        $sql = "SELECT uid_empresa_contratacion FROM ". TABLE_EMPRESA ."_contratacion c WHERE " . implode(' OR ', $cases);

        $ids = $this->db->query($sql, "*", 0);

        if( $get === true ) return $ids;

        if( is_array($ids) && count($ids) ){
            $sql = "DELETE FROM ". TABLE_EMPRESA ."_contratacion WHERE uid_empresa_contratacion IN (". implode(",", $ids) .")";
            if( !$this->db->query($sql) ) return true;
        }



        $empresasCliente = $this->obtenerEmpresasCliente();

        // ELIMINAR VISIBILIDAD DE EMPLEADOS
        $sql = 'DELETE FROM '. TABLE_EMPLEADO .'_visibilidad WHERE uid_empleado IN ( SELECT uid_empleado FROM '. TABLE_EMPLEADO .'_empresa WHERE uid_empresa = '. $this->getUID() .' )';
        if( $empresasCliente && count($empresasCliente) ){
            $sql .= ' AND uid_empresa NOT IN ('. $empresasCliente->toComaList() .')';
        }
        $this->db->query($sql);


        // ELIMINAR VISIBILIDAD DE MAQUINAS
        $sql = 'DELETE FROM '. TABLE_MAQUINA .'_visibilidad WHERE uid_maquina IN ( SELECT uid_maquina FROM '. TABLE_MAQUINA .'_empresa WHERE uid_empresa = '. $this->getUID() .' )';
        if( $empresasCliente && count($empresasCliente) ){
            $sql .= ' AND uid_empresa NOT IN ('. $empresasCliente->toComaList() .')';
        }
        $this->db->query($sql);

        $this->cache->clear('obtenerEmpresasCliente*');

        return true;
    }


    /**
      * void · recupera la cadena de contratacion especificada entre 2 empresas
      *
      *
      * @param  $empresa - Empresa inferior a $this de la que queremos concer las cadenas de contratacion
      *         $posicionesSubcontratacion - Array donde se especifica la cadena de contratacion, aportando la posicion/es donde aparece $empresa
      *         $posicionesEmpresa - Array donde se especifica la cadena de contratacion, aportando la posicion/es donde aparece $this
      * @return ArrayObjectList
      *
      **/
    public function obtenerCadenasContratacion( empresa $empresa = NULL, $posicionesSubcontratacion = array(2), $posicionesEmpresa = array(1), $residual = true ){
        $collection = new ArrayObjectList;
        // Si es corporacion obtenemos las cadenas de todas las emrpesas de la corporacion
        if ( $this->esCorporacion() ) {
            $empresasCorporacion = $this->obtenerEmpresasInferiores();
            foreach ($empresasCorporacion as $empresaCorporacion) {
                $collection = $collection->merge( $empresaCorporacion->obtenerCadenasContratacion($empresa,$posicionesSubcontratacion,$posicionesEmpresa) );
            }
        } else {
            $sql = "SELECT uid_empresa_contratacion FROM ". TABLE_EMPRESA ."_contratacion WHERE ";
            // Obtenemos todas las posiciones especificadas para la empresa $this
            foreach ($posicionesEmpresa as $posicionEmpresa) {
                $condicionSqlEmpresa[] = "n" .$posicionEmpresa. " = {$this->getUID()} ";
            }
            // Obtenemos todas las posiciones especificadas para $empresa
            $sql .= " ( ". implode(' OR ',$condicionSqlEmpresa) . " ) ";
            if ( ( $empresa instanceof empresa ) ) {
                foreach ($posicionesSubcontratacion as $posicionContratacion) {
                    $posicion = "n" .$posicionContratacion. " = {$empresa->getUID()} ";
                    $condicionSqlSubcontrata[] = (!$residual && $posicionContratacion == 3)? $posicion . "AND n4 IS NULL" : $posicion;
                }
                $sql .= " AND  ( ". implode(' OR ',$condicionSqlSubcontrata) . " ) ";
            }

            $collection = $this->db->query($sql, '*', 0, 'empresaContratacion');
            $collection = new ArrayObjectList($collection);
        }
        return $collection;
    }

    public function getClientChains(empresa $company){
        if (!isset($company)) return false;

        $chains = $this->obtenerCadenasContratacion($company, array(1), array(3));
        $chains2 = $this->obtenerCadenasContratacion($company, array(1), array(4));

        if ($chains && count($chains) && $chains2 && count($chains2)) {
            $chains = $chains->merge($chains2)->unique();
        } else if ( !count($chains) ) {
            $chains = $chains2;
        }

        $finalChains = new ArrayObjectList;
        foreach ($chains as $key => $chain) {
            if ($chain->isResidualChain($company, $this) == false) {
                $finalChains[] = $chain;
            }
        }

        return $finalChains;
    }

    public function getGlobalStatusForClient(empresa $company, Iusuario $user)
    {
        $chains = $this->getClientChains($company);

        if ($this->obtenerEmpresasSuperiores()->contains($company) || $this->compareTo($company)) {
            $companyFilter = new ArrayObjectList([$company, $this]);
            $statusWithCompany = $this->getStatusWithCompany(null, 0, 1, $companyFilter);

            if ($statusWithCompany == solicitable::STATUS_NO_REQUEST || $statusWithCompany == solicitable::STATUS_INVALID_DOCUMENT) {
                return false;
            }
        }

        if (count($chains)) {
            foreach ($chains as $chain) {
                if (!$chain->getglobalStatusChain()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
      * void · elimina toda la cadena de contratacion de esta empresa con relación a la empresa @param
      *
      *
      *
      * @return bool
      *
      **/
    public function eliminarCadenasContratacion(empresa $empresa, $subs = false){
        // Cuando se quiera borrar toda la cadena, por ejemplo al enviar una empresa a la papelera ...
        if( $subs !== false ){
            $sql = "DELETE FROM ". TABLE_EMPRESA ."_contratacion WHERE n1 = {$empresa->getUID()} AND n2 = {$this->getUID()}";
            if( !$this->db->query($sql) ) throw new Exception($this->db->lastError());
        }

        // Borramos las subcontrataciones donde nosotros somos la subcontratada y la superior el usuario, teniendo un cliente a su vez
        $sql = "DELETE FROM ". TABLE_EMPRESA ."_contratacion WHERE n2 = {$empresa->getUID()} AND n3 = {$this->getUID()}";
        if( $subs === false ) $sql .= " AND (n4 = 0 OR n4 IS NULL)";
        // dump($sql);
        if( !$this->db->query($sql) ) throw new Exception($this->db->lastError());

        // Borramos las subcontrataciones donde nosotros somos la subcontratada y la superior el usuario, teniendo 2 clientes por encima
        $sql = "DELETE FROM ". TABLE_EMPRESA ."_contratacion WHERE n3 = {$empresa->getUID()} AND n4 = {$this->getUID()}";
        // dump($sql);
        if( !$this->db->query($sql) ) throw new Exception($this->db->lastError());

        $this->cache->clear('obtenerEmpresasCliente*');

        return true;
    }

    public function hasGroup($agrupador, $condicion = false, $return = false) {
        $agrupadores = $this->obtenerAgrupadores($condicion, $return);
        return $agrupadores->contains($agrupador);
    }

    /**
     * Get the groups qhen the company is coordinator
     * @param  empresa $owner the owner of groups
     * @return ArrayAgrupadorList
     */
    public function getCoordinatedGroups($owner = null)
    {
        $ownerCondition = "";
        if (null !== $owner) {
            $ownerCondition = "AND uid_empresa = {$owner->getUID()}";
        }

        $sql = "
        SELECT uid_agrupador
        FROM ". TABLE_AGRUPADOR ."
        WHERE uid_coordinator_company = {$this->getUID()}
        {$ownerCondition}
        AND papelera = 0
        ";

        $groups = $this->db->query($sql, "*", 0, "agrupador");

        if (count($groups) > 0) {
            return new ArrayAgrupadorList($groups);
        }

        return new ArrayAgrupadorList;
    }


    /** AHORA MISMO ESTA FUNCION ES UN ALIAS DE cliente::obtenerAgrupadores PERO LA CREO CON VISTAS A QUE SEA AUTONOMA **/
    public function obtenerAgrupadoresPropios ($conditions = array(), $return = false) {
        $cacheKey = implode('-', array(__FUNCTION__, $this, implode('-', $conditions), $return));
        if (null !== ($val = $this->cache->getData($cacheKey))) {
            if ($return) return $val;

            $intList = ArrayIntList::factory($val);
            return $intList->toObjectList('agrupador', 'ArrayAgrupadorList');
        }

        $groups = $this->obtenerAgrupamientosPropios($conditions)->toAgrupadorList($conditions, $return);

        $this->cache->set($cacheKey, ($return ? $groups : $groups->toComaList()));
        return $groups;
    }



    public function obtenerAgrupadoresCorporacionAsignados ($conditions = array(), $return = false) {
        return $this->obtenerAgrupamientosCorporacionAsignados($conditions)->toAgrupadorList($conditions, $return);
    }


    public function obtenerAgrupadoresVisibles ($condicion = false) {
        $condicion = is_array($condicion) ? $condicion : array($condicion);

        $scondicion = implode('-',$condicion);
        if (($cacheKey = implode('-', array(__FUNCTION__, $this, $scondicion))) && ($val = $this->cache->getData($cacheKey)) !== null) {
            if ($val === false) return false;

            $intList = ArrayIntList::factory($val);
            $agrupadorList = $intList->toObjectList('agrupador');
            return new ArrayAgrupadorList($agrupadorList);
        }

        $asignadosCorporacion = $this->obtenerAgrupadoresCorporacionAsignados($condicion);
        $propiosEmpresa = $this->obtenerAgrupadoresPropios($condicion);


        $return = false;

        if ($asignadosCorporacion && $propiosEmpresa){
            $return = $propiosEmpresa->merge($asignadosCorporacion)->unique();
        } elseif ($asignadosCorporacion) {
            $return = $asignadosCorporacion;
        } elseif ($propiosEmpresa) {
            $return = $propiosEmpresa;
        }

        $this->cache->set($cacheKey, ($return ? $return->toComaList() : false), 60*60*15);
        return $return;
    }


    /** Devolver los agrupamientos de los que esta empresa es propietaria **/
    public function obtenerAgrupamientosPropios ($condiciones = array(), $count = false) {
        $field = $count ? "count(uid_agrupamiento)" : "uid_agrupamiento";
        $table = TABLE_AGRUPAMIENTO;

        if (is_array($condiciones) && isset($condiciones['modulo'])) {
            $table .= " INNER JOIN " . TABLE_AGRUPAMIENTO . "_modulo USING (uid_agrupamiento)";
        }


        $sql = "SELECT {$field} FROM {$table} WHERE uid_empresa = {$this->getUID()}";

        if ($condition = agrupamiento::conditionSQL($condiciones)) $sql .= " AND " . $condition;
        $sql .= " ORDER BY nombre";

        if ($count) return (int) $this->db->query($sql, 0, 0);

        $coleccion = $this->db->query($sql, "*", 0, 'agrupamiento');
        if ($coleccion) return new ArrayAgrupamientoList($coleccion);


        return new ArrayAgrupamientoList;
    }

    /** RETORNAR UNA COLECCION DE OBJETOS AGRUPAMIENTO ASIGNADOS A ESTA EMPRESA */
    public function obtenerAgrupamientosCorporacionAsignados ($condiciones = array(), $count = false) {
        $field = $count ? "count(uid_agrupamiento)" : "uid_agrupamiento";
        $table = TABLE_AGRUPAMIENTO;

        if (is_array($condiciones) && isset($condiciones['modulo'])) {
            $table .= " INNER JOIN " . TABLE_AGRUPAMIENTO . "_modulo USING (uid_agrupamiento)";
        }

        // Si pertenece a corporacion devolvemos los asignados de la corporacion y los propios.
        if ($corp = $this->perteneceCorporacion()) {

            $sql = "SELECT {$field} FROM {$table} WHERE uid_empresa = {$corp->getUID()} AND
                    uid_agrupamiento IN ( SELECT uid_agrupamiento FROM ". TABLE_EMPRESA ."_agrupamiento WHERE uid_empresa = {$this->getUID()})";

        } elseif ($this->esCorporacion()) {

            // Si es corporación solo devolvemos los de la corporación.
            $sql = "SELECT {$field} FROM {$table} WHERE uid_empresa = {$this->getUID()} AND
                    uid_agrupamiento IN ( SELECT uid_agrupamiento FROM ". TABLE_EMPRESA ."_agrupamiento WHERE uid_empresa = {$this->getUID()})";

        }

        if (isset($sql)) {
            if ($condition = agrupamiento::conditionSQL($condiciones)) {
                $sql .= " AND " . $condition;
            }

            if ($count) return (int) $this->db->query($sql, 0, 0);

            $coleccion = $this->db->query($sql, "*", 0, 'agrupamiento');
            if ($coleccion) return new ArrayAgrupamientoList($coleccion);
        }

        if ($count) return 0;

        return new ArrayAgrupamientoList;
    }


    public function obtenerAgrupamientosVisibles($condiciones = array(), $count = false) {

        $condiciones = is_array($condiciones) ? $condiciones : array($condiciones);
        $propiosEmpresa = $this->obtenerAgrupamientosPropios($condiciones, $count);
        $asignadosCorporacion = $this->obtenerAgrupamientosCorporacionAsignados($condiciones, $count);

        if ($count) return ($propiosEmpresa + $asignadosCorporacion);

        if ($asignadosCorporacion && $propiosEmpresa) {
            return $propiosEmpresa->merge($asignadosCorporacion)->unique();
        } elseif ($asignadosCorporacion) {
            return $asignadosCorporacion;
        } elseif ($propiosEmpresa) {
            return $propiosEmpresa;
        }


        return false;
    }

    public function obtenerElementosActivables(usuario $usuario = NULL){
        $superiores = $this->obtenerEmpresasSuperiores();
        $empresaUsuario = $usuario->getCompany();
        if( $empresaUsuario->esCorporacion() ){
            $empresas = $empresaUsuario->obtenerEmpresasInferiores();

            // Con este IF preguntamos si la empresa a desactivar es una de la propia corporación
            if( $empresas->contains($this) ){
                return new ArrayObjectList(array($empresaUsuario));
            }

            $activable = new ArrayObjectList;
            foreach($empresas as $empresa){
                $contratas = $empresa->obtenerEmpresasInferiores();
                if( $contratas->contains($this) ){
                    $activable[] = $empresa;
                }
            }

            return $activable;

        } else {
            if( $superiores->contains($empresaUsuario) ){
                return new ArrayObjectList(array($empresaUsuario));
            }
        }

    }

    public function isDeactivable($parent, usuario $usuario){
        if( $num = $this->numSubcontracts($parent) ){
            if( $num > 15 ){
                $tpl = Plantilla::singleton();
                return sprintf($tpl->getString("demasiadas_cadenas_activas"), $num);
            }
        }
        return true;
    }

    public function numSubcontracts($parent) {
        if( $cadenas = $parent->obtenerCadenasContratacion($this) ){
            return count($cadenas);
        }
        return false;
    }

    public function enviarPapelera($parent, usuario $usuario){
        return $this->dejarDeSerInferiorDe($parent, $usuario) && $this->deleteElementsVisibility($parent, $usuario);
    }

    public function restaurarPapelera($parent, usuario $usuario){
        $restore = $this->restaurarComoInferiorDe($parent, $usuario);

        // update company contracts tmp table
        $this->app['index.repository']->expireIndexOf(
            'empresa',
            $parent->asDomainEntity(),
            $usuario->asDomainEntity(),
            true
        );

        return $restore;
    }


    public function obtenerCarpetas($recursive = false, $level = 0, Iusuario $usuario = NULL){
        return parent::obtenerCarpetas($recursive, $level, $usuario);
    }

    public function removeParent( elemento $parent, usuario $usuario = null ){
        if ($parent instanceof empresa) {
            return $parent->borrarRelacionPara($this, $usuario);
        }
    }

    public function obtenerElementosPapelera(usuario $usuario, $modulo){
        switch($modulo){
            case "empresa":
                return $this->obtenerEmpresasInferiores(true, false, $usuario);
            break;
            case "empleado":
                return $this->obtenerEmpleados(true, false, $usuario );
            break;
            case "maquina":
                return $this->obtenerMaquinas(true, false, $usuario );
            break;
            case "usuario":
                return $this->obtenerUsuarios(true, false, $usuario );
            break;
            case "epi":
                return $this->obtenerEpis(true, false, $usuario );
            break;
            case "exportacion_masiva":
                return $this->obtenerExportacionmasivas(true);
            break;
        }
    }

    public function inTrash($parent) {
        $sql = "SELECT papelera FROM ". TABLE_EMPRESA ."_relacion WHERE uid_empresa_inferior = {$this->getUID()} AND uid_empresa_superior = {$parent->getUID()}";
        return (bool) $this->db->query($sql, 0, 0);
    }

    /** (La ultima "s" no es un error, es por compatibilidad
      *
      *
      *
      */
    public function obtenerCentroCotizacions($count = false){
        if( $count ){
            $sql = "SELECT count(uid_elemento) FROM ". TABLE_CENTRO_COTIZACION ." WHERE uid_elemento = {$this->getUID()} AND uid_modulo = {$this->getModuleId()}";
            return $this->db->query($sql, 0, 0);
        } else {
            $sql = "SELECT uid_centrocotizacion FROM ". TABLE_CENTRO_COTIZACION ." WHERE uid_elemento = {$this->getUID()} AND uid_modulo = {$this->getModuleId()}";
            $items = $this->db->query($sql, "*", 0, "centrocotizacion");
            return new ArrayObjectList($items);
        }
    }

    /** INDICA SI ES ACCESIBLE O NO PARA UN USUARIO DADO */
    public function accesiblePara( $usuarioActivo ){
        return $usuarioActivo->accesoElemento( $this );
    }

    public static function updateActivityData () {
        $db = db::singleton();
        $sql = "SELECT uid_empresa_propietaria FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE activo = 1 GROUP BY uid_empresa_propietaria ORDER BY fecha DESC";
        $companies = $db->query($sql, '*', 0, 'empresa');

        foreach ($companies as $company) {
            print "Company {$company->getUserVisibleName()}... ";
            $activity = $company->getActivitySummary(1, false);

            if (!count($activity)) {
                print " Error!";
                continue;
            }

            list($attached, $validated, $rejected, $expired) = array_values($activity);

            print implode(':', $activity);

            $sql = "UPDATE ". TABLE_EMPRESA ." SET
            req_attached    = {$attached},
            req_validated   = {$validated},
            req_rejected    = {$rejected},
            req_expired     = {$expired}
            WHERE uid_empresa = {$company->getUID()}
            ";

            if ($db->query($sql)) {
                print " Ok";
            } else {
                print " Error";
            }

            print "\n";
        }

    }

    public static function cronCall($time, $force = false, $tipo = NULL){
        ini_set('memory_limit', '512M');
        $cache = cache::singleton();

        // solo se lanza una vez al dia
        $eachHour = (date("i", $time) == "00");
        if ($eachHour) {
            print "Generating validation AVG... \n";
            self::updateAVGValidation();
        }

        // solo se lanza una vez al dia
        $isTime = (date("H:i", $time) == "00:00");
        if (!$isTime && !$force) return true;
        $activeCompanies = self::getActiveCompanies();

        $dayTimeStamp = 86400; //un dia en milisegundos, en formato timestamp
        $activeCompanies = self::getActiveCompanies();

        $minute = date('i', $time);
        try{
            foreach ($activeCompanies as $company){
                if (true === $company->isEnterprise()) {
                    // Do not send emails to enterprise companies
                    continue;
                }

                $numDaysDifference = 365 - $company->getPaidDate(true);

                if ($numDaysDifference == self::TIME_NOTIFICCATION_PAYMENT_FRAME || $numDaysDifference  == -abs(self::TIME_NOTIFICCATION_PAYMENT_FRAME)
                    || $numDaysDifference == self::TIME_NOTIFICCATION_PAYMENT ) {
                    $company->sendCompanyNotificationPayment($time, $force, $numDaysDifference);
                }
            }
        } catch(Exception $e){
            echo "Ha habido un error al enviar informes o emails de pago ".$e->getMessage()."\n";
        }
    }

    public static function updateAVGValidation () {

        $pwd = isset($_SERVER["PWD"]);
        $db = db::singleton();
        $partners = self::getAllPartners();
        foreach ($partners as $partner) {

            $urgentValues = array(0,1);
            foreach ($urgentValues as $urgent) {
                $moduloItems = solicitable::getModules();
                $unionPart = array();
                $previousDays = 7;
                $time = time();

                $curhour = date('H', $time);
                $minhour = date('H', $time - 3600);
                $maxhour = date('H', $time + 3600);
                $timeFilter = "DATE_FORMAT(FROM_UNIXTIME(fecha_anexion), '%H') = {$curhour}";


                foreach ($moduloItems as $modulo) {
                        $unionPart[] = "
                            SELECT AVG(time_to_validate) as avg
                            FROM  " .TABLE_VALIDATION. " v
                            INNER JOIN  " .TABLE_VALIDATION_STATUS. " using(uid_validation)
                            INNER JOIN " .PREFIJO_ANEXOS. "$modulo a ON uid_anexo_$modulo = uid_anexo
                            WHERE uid_empresa_validadora = {$partner->getUID()}
                            AND a.is_urgent = $urgent
                            AND $timeFilter
                            AND fecha_anexion > (UNIX_TIMESTAMP() - (3600*24*{$previousDays}))
                            AND time_to_validate IS NOT NULL
                        ";

                        $unionPart[] = "
                            SELECT AVG(time_to_validate) as avg
                            FROM  " .TABLE_VALIDATION. " v
                            INNER JOIN  " .TABLE_VALIDATION_STATUS. " USING (uid_validation)
                            INNER JOIN " .PREFIJO_ANEXOS_HISTORICO. "$modulo a USING (uid_anexo)
                            WHERE uid_empresa_validadora = {$partner->getUID()}
                            AND a.is_urgent = $urgent
                            AND $timeFilter
                            AND fecha_anexion > (UNIX_TIMESTAMP() - (3600*24*{$previousDays}))
                            AND time_to_validate IS NOT NULL
                        ";
                }

                $sql = "SELECT AVG(avg) FROM (". implode(" UNION ", $unionPart) .") AS tmp WHERE avg IS NOT NULL";
                $avgTime = (float)$db->query($sql, 0, 0);

                $insert = "INSERT INTO ". TABLE_AVG_TIME ." (uid_partner, is_urgent, time) VALUES ('{$partner->getUID()}', '{$urgent}', '{$avgTime}')
                           ON DUPLICATE KEY UPDATE time = '{$avgTime}'";

                if (!$db->query($insert)) {
                    if ($pwd) echo "error while updating partner [uid: {$partner->getUID()}][urgent: {$urgent}] average validation time. ".$db->lastError()."\n";
                }
            }
        }

        return true;

    }


    public function getInvoiceDaysPeriodicity () {
        $months = $this->obtenerDato('invoice_periodicity');

        // debe ser un número entre 1 y 6 ambos inclusive
        if (!is_numeric($months) || $months < 1 || $months > 6) {
            $months = 1;
        }

        // asumimos que todos los meses tienen 30 días
        return $months * 30;
    }


    public function canPay() {
        $needsPay = $this->needsPay();
        $renewTime = $this->timeFreameToRenewLicense();
        $temporaryLicense = $this->isTemporary();
        $optionalPayment = $this->hasOptionalPayment();
        $expiredLicense = $this->hasExpiredLicense();
        $canPay = ($needsPay || $renewTime || $temporaryLicense || (($optionalPayment && $expiredLicense) || ($optionalPayment && $renewTime)));
        return $canPay;
    }


    public function delegatePaymentLicense (usuario $user) {
        $app = Dokify\Application::getInstance();

        $clientPayResponse = $app['company.client_pay']->execute(
            $app['company.client_pay']->createRequest((int) $this->getUID())
        );

        if (false === $clientPayResponse->hasClientPay()) {
            return false;
        }

        $delegateCompany = $clientPayResponse->client()->getLegacyInstance();
        $paypal = new paypalLicense;
        $company = $user->getCompany();
        $data = $paypal->createPayConcept($user);

        $table = TABLE_TRANSACTION;
        $payType = endeve::PAYMENT_METHOD_CLIENT;

        $sql = "INSERT INTO {$table}
                (payment_type, payment_date, payment_status, custom, txn_id)
                VALUES ('{$payType}', NOW(), 'Completed', '{$data->customKey}', '{$data->customKey}')
                ";

        $result = $this->db->query($sql);

        $invoice = $delegateCompany->getCurrentInvoice();
        $price = $data->price * ((100 - $data->discount)/100);

        $dataItem = array(  "uid_invoice" => $invoice->getUID(),
                            "uid_reference" => $data->intentId,
                            "description" => invoiceItem::DESCRIPTION_LICENSE,
                            "amount" => $price,
                            "num_items" => 1,
                            "uid_modulo" => util::getModuleId("paypalLicense")
                        );

        $invoiceItem = new invoiceItem($dataItem, NULL);

        if ($invoiceItem->exists()) {
            $currentAmount = (float) $invoice->obtenerDato("amount");
            $newAmount = $currentAmount + $price;

            $invoice->update(["amount" => $newAmount]);
        }

        $licensePremium = $this->setLicense(empresa::LICENSE_PREMIUM);

        $this->setTransferPending(false);

        $event = new \Dokify\Application\Event\License\Payment(
            new \Dokify\Domain\License\Payment\PaymentUid($data->intentId)
        );

        $this->dispatcher->dispatch(
            \Dokify\Events\License\PaymentEvents::LICENSE_PAYMENT_DONE,
            $event
        );

        return ($licensePremium && $result && $invoiceItem instanceof invoiceItem);
    }


    /***
       * Get the company invoice
       * If it hasn't been created yet returns a new invoice
       *
       */
    public function getCurrentInvoice () {
        $pendingInvoice = $this->getPendingInvoice();

        if ($pendingInvoice instanceof invoice) return $pendingInvoice;

        $invoice = new invoice(array("uid_empresa" => $this->getUID()), NULL);

        return $invoice;
    }


    public function getPendingInvoice() {
        $sql = "SELECT uid_invoice FROM ". TABLE_INVOICE . "
        WHERE uid_empresa = {$this->getUID()}
        AND sent_date IS NULL";

        $uid = $this->db->query($sql, 0, 0);

        if ($uid) return new invoice($uid);

        return false;
    }


    public function createInvoicesPayments($force = false)
    {
        $pwd = isset($_SERVER["PWD"]);
        $isEnterprise = $this->isEnterprise();

        if ($isEnterprise) {
            if ($pwd) {
                print "Enterprise are now in /crontab/company/invoices.php\n";
            }

            return true;
        }

        try{
            $limit = NULL;
            $actualDay = date("d");
            if ($isEnterprise && $actualDay != '01') $limit = date('Y-m-01');

            $validationsStatus = $this->getPendingValidationsStatusNotInvoiced($limit);
            if (!$validationsStatus) return;

            $lastInvoice = $this->getLastInvoicedEmited();
            $minDays = $this->getInvoiceDaysPeriodicity();

            if ($lastInvoice instanceof invoice) {
                $invoiceDate = new DateTime($lastInvoice->getSentDate());
                $now = new DateTime();
                $datediff = date_diff($invoiceDate, $now);
                $days = $datediff->days;

                $monthChanged = $invoiceDate->format('m') != $now->format('m');

                // para facilitar el trabajo a facturacion también comprobamos el mínimo de dias aqui
                $sendEnterprise = $isEnterprise && $monthChanged && $days > $minDays;

            } elseif ($isEnterprise && $actualDay != '01') {
                if ($pwd) echo "No creamos invoice por ser enterprise y no ser dia 1.\n";
                return false;
            }

            if (!isset($days) || $days > $minDays || $sendEnterprise) {
                $invoice = $this->getCurrentInvoice();

                $validationInvoiceItems = $validationsStatus->foreachCall("createInvoiceItem", array($invoice));
                $invoiceItems = $invoice->getItems();
                if (count($invoiceItems) && is_traversable($invoiceItems)) {

                    $amountSet = $invoiceItems->each("getAmount");
                    $amount = array_sum($amountSet->getArrayCopy());

                    if ($amount < INVOICE::LIMIT_EUROS_INVOICE) {

                        if ($amount > INVOICE::MIN_LIMIT_EUROS_INVOICE) {
                            $lastItem = $invoice->getItemOrdered("DESC");
                            $dateLastInoviceItem = new DateTime($lastItem->getDate());
                            // Do not create invoice when last item previous to 2015
                            $lastDayToCreateInvoice = new DateTime('2015-01-01');
                            $now = new DateTime();
                            $datediff = date_diff($dateLastInoviceItem, $now);
                            if ($datediff->days < INVOICE::MAX_LIMIT_DAYS_TO_INVOICE || $dateLastInoviceItem < $lastDayToCreateInvoice) {
                                $invoice->eliminar();
                                if ($pwd) echo "Han pasado {$datediff->days} no emitimos invoice todavía para una factura de {$amount} €. Eliminamos invoice\n";
                                return false;
                            }
                        } else {
                            $invoice->eliminar();
                            if ($pwd) echo "No emitimos invoice no llega al minimo. Eliminamos invoice\n";
                            return false;
                        }
                    }

                    $items = $invoice->getInvoicedItemsFormated();
                    $saleId = 0;
                    if (!$isEnterprise) {
                        $endeveId = endeve::getEndeveId($this);
                        if ($endeveId) {
                            $invoiceSentDate = new DateTimeImmutable($invoice->getSentDate());
                            $saleId = endeve::createSale($this, $endeveId, $items, NULL, invoice::TAG_ENDEVE_VALIDATION, $invoiceSentDate);
                            if (!is_numeric($saleId) || $saleId == 0) {
                                $invoice->eliminar();
                                if ($pwd) echo "Ha habido un error creando la factura en quaderno para el endeveId: $endeveId.\n";
                                return false;
                            }
                        } else {
                            $invoice->eliminar();
                            if ($pwd) echo "Error creando contacto para la empresa {$this->getUID()}. Eliminamos invoice\n";
                            return false;
                        }
                    }

                    try {
                        try {
                            $invoice->update(["amount" => number_format($amount, 2, '.', '')]);
                            $invoice->update(["sale_id" => $saleId]);
                        } catch (Exception $e) {
                            $invoice->eliminar();
                            throw new Exception("Cannot update invoice data. Eliminamos invoice");
                        }

                        $status = $invoice->sendEmailNotification(invoice::PAYMENT_INFO, $this, $items, $force);
                        if (true) {
                            if ($pwd) echo "Emitimos invoice \n";
                            $invoice->update(array("sent_date" => date("Y-m-d H:i:s")));
                            return true;
                        } else if($saleId) {
                            $invoice->eliminar();
                            throw new Exception("Error enviando email. Eliminamos invoice. Eliminamos invoice\n");
                        }
                    } catch(Exception $e) {
                        if ($pwd) echo "Error enviando email invoice: ". $e->getMessage() ."\n";
                        if ($saleId) {
                            //Error, tenemos que eliminar el invoice de quaderno
                            if (endeve::deleteInvoice($saleId)) {
                                if ($pwd) echo "Invoice eliminiado: ". $saleId ."\n";
                            } else {
                                //Error eliminando el invoice de quaderno
                                if ($pwd) echo "Error eliminando el invoice de quaderno para el saleId: ". $saleId ."\n";
                                endeve::notifyError($saleId, $this);
                            }
                        }
                        return false;
                    }

                } else {
                    $invoice->eliminar();
                    if ($pwd) echo " No creamos invoice y lo eliminamos\n";
                    return false;
                }

            }else{
                if ($pwd) echo " No creamos invoice por limite de tiempo.\n";
                return false;
            }
        } catch(Exception $e){
            if ($pwd) echo "Error creando invoice: ".$e->getMessage();
        return false;
        }
    }

    public function sendCompanyNotificationPayment($time, $force, $numDaysDifference)
    {

        $log = log::singleton();
        $plantilla = new Plantilla();

        $txn = paypalLicense::getTransactionId($this);
        $timestamp = paypalLicense::getTransactionTimestamp($txn);

        if ($numDaysDifference == self::TIME_NOTIFICCATION_PAYMENT_FRAME) {
            $plantilla->assign('expired', false);
        } else if ($numDaysDifference  == -abs(self::TIME_NOTIFICCATION_PAYMENT_FRAME)) {
            $plantilla->assign('expired', true);
        } else if ($numDaysDifference != self::TIME_NOTIFICCATION_PAYMENT) {
            echo "Email de pago: Nada que enviar a {$this->getUserVisibleName()} [{$this->getUID()}] ... \n";
            return false;
        }

        $plantilla->assign('daysExpire', abs($numDaysDifference));
        $plantilla->assign('dateExpire', strtotime('+366 days', $timestamp));

        $emailTemplate = plantillaemail::instanciar("invoicenotification");
        $contacts = $this->obtenerContactos($emailTemplate);

        if (trim($logo = $this->obtenerLogo())) {
            $plantilla->assign('elemento_logo', $this->obtenerLogo());
        } else {
            $plantilla->assign('elemento_logo', RESOURCES_DOMAIN . '/img/dokify-google-logo.png');
        }

        foreach ($contacts as $contact) {
            $contactName = $contact->getUserVisibleName();
            if (!$contactName) {
                $contactName = "contact - ".$contact->getUID();
            }

            $log->info($contactName, "aviso pago empresa ". $this->getUserVisibleName() ." a ".$contact->obtenerDato('email'), $this->getUserVisibleName());
            echo "Enviando email de pago a {$this->getUserVisibleName()} [{$this->getUID()}]... \n";

            if ($force) {
                $emails = email::$developers;
            } else {
                $emails = array($contact->obtenerDato('email'));
            }
            $language = $contact->getLanguage();
            $plantilla->assign("contactName", $contact->getContactName());
            $plantilla->assign("lang", $language);

            if (!is_array($emails) || !count($emails)) {
                $log->resultado("No hay datos de correo en la empresa ". $this->getUserVisibleName(), true);
            } else {
                echo implode(", ", $emails) . "... ";
                $html = $plantilla->getHTML('email/paymentExpired.tpl');
                $email = new email($emails);
                $email->establecerContenido($html);
                $email->establecerAsunto($plantilla->getString("title_email_license", $language));

                if (($status = $email->enviar()) === true) {
                    echo " OK";
                    $log->resultado("Ok", true);
                    $contact->writeLogUI(
                        logui::ACTION_AVISOEMAIL_LICENCIA,
                        "license = ".implode(", ", $emails),
                        null,
                        $this
                    );
                } else {
                    echo " ¡error!: {$email->mailer->ErrorInfo}";
                    $log->resultado("Ocurrió un error al enviar el email de pago a ". $this->getUserVisibleName().": {$email->mailer->ErrorInfo} - status : {$status}", true);
                }
            }
        }
        echo "\n";
        return false;
    }

    public static function onSearchByTxn($data, $filters, $param, $query)
    {
        $value = reset($filters);
        $value = db::scape($value);

        $transactions = TABLE_TRANSACTION . '_concept';
        $txnCompanySQL = "SELECT t.uid_empresa FROM {$transactions} t WHERE custom = '{$value}' LIMIT 1";

        $invoices = TABLE_INVOICE;
        $invoiceCompanySQL = "SELECT i.uid_empresa FROM {$invoices} i WHERE custom = '{$value}' LIMIT 1";

        $sql = "(empresa.uid_empresa = ({$txnCompanySQL}) OR empresa.uid_empresa = ({$invoiceCompanySQL}))";

        return $sql;
    }

    public static function getSearchData(Iusuario $usuario, $papelera = false)
    {
        $searchData = array();
        if (!$usuario->accesoModulo(__CLASS__)) {
            return false;
        }

        $limit = "uid_empresa IN (<%companies%>)";

        if ($usuario->isViewFilterByGroups()) {
            // Es posible que nos pasen un perfil por parámetro
            if ($usuario instanceof perfil) {
                $usuario = $usuario->getUser();
            }

            $userCondition = $usuario->obtenerCondicion(false, "uid_empresa");
            if ($userCondition) {
                $limit .= " AND uid_empresa IN ($userCondition)";
            }
        }

        $searchData[ TABLE_EMPRESA ] = array(
            "type" => "empresa",
            "fields" => array("nombre", "nombre_comercial", "cif"),
            "limit" => $limit,
            "accept" => array(
                "tipo" => "empresa",
                "uid" => true,
                "docs" => true,
                "in" => "old",
                "list" => true,
                "created" => true
            )
        );

        $searchData[TABLE_EMPRESA]['accept']['asignado'] = array(__CLASS__, 'onSearchByAsignado');
        $searchData[TABLE_EMPRESA]['accept']['docs'] = array(__CLASS__, 'onSearchByDocs');
        $searchData[TABLE_EMPRESA]['accept']['estado'] = array(__CLASS__, 'onSearchByStatus');
        $searchData[TABLE_EMPRESA]['accept']['completed'] = array(__CLASS__, 'onSearchByCompleted');
        $searchData[TABLE_EMPRESA]['accept']['txn'] = array(__CLASS__, 'onSearchByTxn');

        $searchData[TABLE_EMPRESA]['accept']['empresa'] = function($data, $filter, $param, $query) {
            $value = reset($filter);
            $sql = false;
            $companies = TABLE_EMPRESA;

            if (is_numeric($value)) {
                $companies = "SELECT uid_empresa_inferior FROM {$companies}_relacion WHERE uid_empresa_superior = {$value}";
            } elseif (is_string($value)) {
                $value = db::scape($value);

                $companies = "SELECT uid_empresa_inferior FROM {$companies}_relacion er
                INNER JOIN {$companies} e ON e.uid_empresa = er.uid_empresa_superior
                WHERE (e.nombre LIKE '%{$value}%' OR e.cif LIKE '%{$value}%')";
            }

            $sql = "uid_empresa IN ({$companies})";

            return $sql;
        };

        $searchData[TABLE_EMPRESA]['accept']['licencia'] = function($data, $filter, $param, $query) {
            $value = reset($filter);

            $premiumCompanies = "
                SELECT uid_empresa FROM ". TABLE_TRANSACTION ."_concept INNER JOIN ". TABLE_TRANSACTION ." USING(custom)
                WHERE uid_empresa AND payment_status = 'Completed' AND DATEDIFF(NOW(), date) < 365
            ";

            $sql = "";


            switch ($value) {
                case 'free':
                    $list = db::get($premiumCompanies, "*", 0);
                    $comaList = implode(',', $list);

                    $sql = "(is_enterprise = 0 AND uid_empresa NOT IN ({$comaList}) )";
                    break;

                case 'premium':
                    $list = db::get($premiumCompanies, "*", 0);
                    $comaList = implode(',', $list);

                    $sql = "(is_enterprise = 0 AND uid_empresa IN ({$comaList}))";
                    break;

                case 'enterprise':
                    $companies = TABLE_EMPRESA;
                    $relationships = TABLE_EMPRESA . '_relacion';

                    $corps = "(
                        SELECT r.uid_empresa_inferior
                        FROM {$relationships} r
                        INNER JOIN {$companies} c ON r.uid_empresa_superior = c.uid_empresa
                        WHERE is_enterprise = 1 AND c.activo_corporacion = 1
                    )";

                    $sql = "(is_enterprise = 1 OR uid_empresa IN ({$corps}))";

                    break;

                default:
                    return false;
                    break;
            }


            return $sql;
        };

        $searchData[TABLE_EMPRESA]['accept']['provincia'] = function($data, $filter, $param, $query) {
            $value = reset($filter);
            $provincia = provincia::getFromName($value);

            if ($provincia instanceof provincia) {
                $sql = " ( uid_empresa IN (
                    SELECT uid_empresa FROM ". TABLE_EMPRESA ." WHERE uid_provincia =". $provincia->getUID() ."
                ) ) ";
            } else {
                $sql = " ( 0 ) ";
            }

            return $sql;
        };

        $searchData[TABLE_EMPRESA]['accept']['aptitud'] = function($data, $filter, $param, $query) {
            $value = reset($filter);

            $sqlUnsuitableCompanies = "SELECT uid_item FROM ". TABLE_EMPRESA ."_item ei WHERE ei.uid_modulo = 1 AND suitable = 0 ";

            switch ($value) {
                case 'si':
                    $sql = " ( uid_empresa NOT IN ($sqlUnsuitableCompanies) ) ";
                    break;

                case 'no':
                    $user = $data['usuario'];
                    $userCompany = $user->getCompany();
                    $startCompany = $userCompany->getStartList();
                    $clients = $userCompany->obtenerEmpresasCliente();
                    $listCompanies = count($clients) ? $clients->merge($startCompany)->toComaList() : $startCompany->toComaList();
                    $sqlUnsuitableCompanies .= "AND uid_empresa IN ($listCompanies)";

                    $sql = " ( uid_empresa IN ($sqlUnsuitableCompanies) ) ";
                    break;

                default:
                    return false;
                    break;
            }

            return $sql;
        };

        $searchData[TABLE_EMPRESA]['accept']['since'] = function($data, $filter, $param, $query) {
            $value = reset($filter);
            $sql = false;

            if (is_numeric($value)) {
                $companies = "
                    SELECT uid_empresa
                    FROM ". TABLE_USUARIO ." INNER JOIN ". TABLE_PERFIL ."
                    USING (uid_usuario)
                    WHERE 1
                    AND fecha_primer_acceso
                    AND fecha_primer_acceso < UNIX_TIMESTAMP(NOW())
                    AND DATEDIFF(NOW(), FROM_UNIXTIME(fecha_primer_acceso)) >= {$value}
                ";

                $sql = "uid_empresa IN ({$companies})";
            }

            return $sql;
        };

        return $searchData;
    }

    /***
       * Get first login from company. We dont have old data, so can return false
       *
       *
       *
       */
    public function getFirstLoginTimestamp () {
        $sql = "
            SELECT fecha_primer_acceso
            FROM ". TABLE_USUARIO ." INNER JOIN ". TABLE_PERFIL ."
            USING (uid_usuario)
            WHERE uid_empresa = {$this->getUID()}
            AND fecha_primer_acceso
            AND fecha_primer_acceso < UNIX_TIMESTAMP(NOW())
            ORDER BY fecha_primer_acceso ASC
        ";


        if ($time = $this->db->query($sql, 0, 0)) {
            return $time;
        }

        return false;
    }


    public function getAssignCopies(){
        $dir = DIR_FILES . "/empresa/uid_". $this->getUID() ."/assign/";

        $coleccion = new ArrayObjectList();
        if( is_dir($dir) && is_readable($dir) ){
            foreach( glob($dir."/*.csv") as $file ){
                $coleccion[] = new asignacionguardada($file, $this);
            }
        }
        return $coleccion;
    }

    /**
        RESTAURA UNA COPIA DE ASIGNACION
    **/
    public function applyAssignCopy($param){
        $asignacion = asignacionguardada::getFromParam($this, $param);
        if( $asignacion instanceof asignacionguardada ){
            return $asignacion->load();
        } else {
            return false;
        }
    }

    public function createAssignCopy($modulo){
        $errorLog = new log();
        $errorLog->info( $this->getModuleName(), 'copia de asignacion '.$modulo, $this->getUserVisibleName() );

        if( !$modulo || ( $modulo != "empleado" && $modulo != "maquina" ) ){
            $errorLog->resultado('error nombre modulo', true);
            return false;
        }


        $dir = DIR_FILES . "empresa/uid_". $this->getUID() ."/assign/";
        if( !is_dir($dir) ){
            mkdir($dir, 0777, true);
        }

        $filename = "agrupador_elemento.". time() .".$modulo.csv";

        $uidModulo = util::getModuleId($modulo);
        $table = constant("TABLE_". strtoupper($modulo)) . "_empresa";

        $sql = "SELECT * FROM ". TABLE_AGRUPADOR ."_elemento
            WHERE ( uid_modulo = $uidModulo AND uid_elemento IN (
                SELECT uid_$modulo FROM $table WHERE uid_empresa = ". $this->getUID() ."
            ))
        ";

        $csv = new csv($sql);
        $datos = $csv->getData(true);

        if( trim($datos) ){
            $path = $dir . $filename;

            if( archivo::escribir($path, $datos) ){
                $errorLog->resultado('ok', true);
                return true;
            } else {
                $errorLog->resultado('error escribir datos', true);
                return false;
            }
        } else {
            $errorLog->resultado('error sin datos', true);
            return false;
        }
    }

    public function obtenerExportaciones($tipo="empresa", $multiple = true ){
        $coleccion = array();
        switch($tipo){
            case "empresa":
                $coleccion[] = new exportacion( exportacion::TYPE_ASIGNACION, "empresa");
                if( !$multiple ){
                    $coleccion[] = new exportacion( exportacion::TYPE_ASIGNACION, "empleado");
                    $coleccion[] = new exportacion( exportacion::TYPE_ASIGNACION, "maquina");
                }

                $coleccion[] = new exportacion( exportacion::TYPE_ASIGNACIONRELACIONES, "empresa");
            break;
            case "empleado":
                $coleccion[] = new exportacion( exportacion::TYPE_ASIGNACION, "empleado");
                $coleccion[] = new exportacion( exportacion::TYPE_ASIGNACIONRELACIONES, "empleado");
            break;
            case "maquina":
                $coleccion[] = new exportacion( exportacion::TYPE_ASIGNACION, "maquina");
                //$coleccion[] = new exportacion( exportacion::TYPE_ASIGNACIONRELACIONES, "maquina");
            break;
            case "agrupador":
                $coleccion[] = new exportacion( exportacion::TYPE_ASIGNACIONEMPRESA, "agrupador");
                //$coleccion[] = new exportacion( exportacion::TYPE_ASIGNACIONRELACIONES, "maquina");
            break;
        }
        return $coleccion;
    }


    public function afterInsertOptions($usuario){
        $modulo = $this->getModuleName();
        $options = array();

        $options[] = array("href" => "usuario/nuevo.php?poid=" . $this->getUID(), "innerHTML" => "crear_usuario_empresa", "className" => "box-it reloader");
        $options[] = array("href" => "$modulo/nuevo.php", "innerHTML" => "insertar_otro_$modulo", "className" => "box-it");
        return $options;
    }

    public function obtenerCertificaciones($agrupador=false, $datestart=false, $dateend=false){
        $condiciones = array();
        $condicion = false;

        if( $agrupador instanceof agrupador ){
            $condiciones[] = " ( uid_agrupador = " . $agrupador->getUID() ." ) ";
        }

        if( $datestart ){
            if( is_numeric($datestart) ){
                $time = $datestart;
            } else {
                if( strpos($datestart, "/") !== false ){ $datestart = str_replace("/","-", $datestart); }
                $time = strtotime($datestart);
            }
            $month = date("m", $time);
            $year = date("Y", $time);

            if( $datestart && $dateend ){
                $condiciones[] = " ( month >= $month && year >= $year ) ";
            } else {
                $condiciones[] = " ( month = $month && year = $year ) ";
            }
        }

        if( $dateend ){
            if( is_numeric($dateend) ){
                $time = $dateend;
            } else {
                if( strpos($dateend, "/") !== false ){ $dateend = str_replace("/","-", $dateend); }
                $time = strtotime($dateend);
            }
            $month = date("m", $time);
            $year = date("Y", $time);

            $condiciones[] = " ( month <= $month && year <= $year ) ";
        }

        if( count($condiciones) ){
            $condicion = implode(" AND ", $condiciones);
        }

        $arrayRelaciones = $this->obtenerRelacionados( TABLE_CERTIFICACION, "uid_empresa", "uid_certificacion", $condicion );
        $certificaciones = new ArrayObjectList();


        if( is_array($arrayRelaciones) && count($arrayRelaciones) ){
            foreach( $arrayRelaciones as $datosRelacion ){
                $certificaciones[] = new certificacion($datosRelacion["uid_certificacion"]);
            }
        }

        return $certificaciones;
    }

    /**
            PARA LA EMPRESA EN CUESTIÓN, DADO UN AGRUPAMIENTO COMO PARAMETRO, TRATARÁ DE UBICAR
            LOS AGRUPADORES DE ESTE QUE POR ASIGNACIONES RECURSIVAS TENGAN FINALMENTE UNA CERTIFICACION

            EJ:
                1. $empresa->obtenerAgrupadoresCertificaciones($usuario, "PAISES", true);
                2. Tiene algun agrupador de "PAISES" una certificacion? (NO)
                3. Que agrupadores de PAISES tienen asignado algun agrupador que a su vez tenga ( recursivamente ) una certificacion
                3. ----- otra vez
                3. ----- otra vez
                4. OK. PAISES - REGIONES - PROVINCIAS - PROYECTOS - TORRE ( torre tiene certificacion ) RETURN

    **/
    public function obtenerAgrupadoresCertificaciones($usuario, $agrupamiento = null, $follow = true){
        $sql = "SELECT c.uid_agrupador, aa.uid_agrupamiento
                FROM ". TABLE_CERTIFICACION ." c
                INNER JOIN ". TABLE_AGRUPAMIENTO ."_agrupador aa
                USING ( uid_agrupador )
                WHERE c.uid_empresa = ". $this->getUID();

        $lineas = $this->db->query($sql, TRUE);

        $agrupadores = array();

        foreach($lineas as $linea){
            $agrupador = new agrupador( $linea["uid_agrupador"] );

            if( $linea["uid_agrupamiento"] === $agrupamiento->getUID() ){
                $agrupadores[] = $agrupador;
            } else {
                $agrupadores = $agrupador->closest($agrupamiento);
            }
        }

        return $agrupadores;
    }



    /** ARRAY CON LOS FECHAS EN LAS QUE ESTA EMPRESA TIENE ALGUNA CERTIFICACION **/
    public function obtenerFechasCertificaciones($limit=null){
        $sql = "
            SELECT concat(year,'-',month,'-01') as date
            FROM ". TABLE_CERTIFICACION ."
            WHERE uid_empresa = ". $this->getUID() ."
            AND uid_agrupador IN (
                SELECT aa.uid_agrupador FROM ". TABLE_AGRUPAMIENTO ."_agrupador aa
                INNER JOIN ". TABLE_AGRUPAMIENTO ."_modulo USING(uid_agrupamiento)
                WHERE uid_modulo = ". util::getModuleId("certificacion") ."
            )
            AND year AND month
            GROUP BY year, month
            ORDER BY year DESC, month DESC
        ";

        if( is_numeric($limit) ){ $sql .= " LIMIT $limit"; }

        $dates = $this->db->query($sql, "*", 0);
        return $dates;
    }


    /** PASANDO COMO PARAMETRO UN EMPRESA, SI ESTA HA HECHO UNA SOLICITUD DE SER CLIENTE, ESTA SE CONFIRMA */
    public function confirmarEmpresaSuperior($empresaSolicitante, $reanexar = false, $usuario = false ){
        $sql = "INSERT INTO ".$this->tabla."_relacion (uid_empresa_inferior, uid_empresa_superior)
            SELECT uid_empresa_inferior, uid_empresa_superior
            FROM ".$this->tabla."_relacion_temporal
            WHERE uid_empresa_superior = ". $empresaSolicitante->getUID() ." AND uid_empresa_inferior = ". $this->getUID();
        $resultado = $this->db->query($sql);
        if( $resultado ){

            $sql = "DELETE FROM ".$this->tabla."_relacion_temporal
            WHERE uid_empresa_superior = ". $empresaSolicitante->getUID() ." AND uid_empresa_inferior = ". $this->getUID();
            $borrado = $this->db->query($sql);
            if( $borrado ){

                // Si hay que reanexar, procedemos...
                if( $reanexar ){
                    $m = "empresa";
                    $list = array( $this->uid );
                    $this->actualizarSolicitudDocumentos($usuario);
                    $result = include( DIR_ROOT . "agd/reanexar.php");
                }


                return true;
            }
        }
    }

    public function newRequest($tipo, elemento $elemento, usuario $usuario, $filter = null, $empresaOrigen = null)
    {
        if (!isset($filter)) {
            $filter = [
                'type' => $tipo,
                'uid_elemento' => $elemento->getUID(),
            ];
        }

        $pendingRequests = $this->solicitudesPendientesFiltros($filter);
        $solicitud = reset($pendingRequests);

        $empresa = $elemento instanceof empresa ? $elemento : $elemento->getCompany();
        $empresaOrigen = isset($empresaOrigen) ? $empresaOrigen : $usuario->getCompany();

        if (!$solicitud instanceof empresasolicitud) {
            $data = [
                'uid_empresa' => $empresa->getUID(),
                'uid_empresa_origen' => $empresaOrigen->getUID(),
                'uid_elemento' => $elemento->getUID(),
                'uid_modulo' => $elemento->getModuleId(),
                'uid_usuario' => $usuario->getUID(),
                'estado' => solicitud::ESTADO_CREADA,
                'type' => $tipo,
            ];
            $solicitud = new empresasolicitud($data, $usuario);
        }
        return true;
    }

    public function myRequest(empresasolicitud $solicitud) {
        return $this->getStartIntList()->contains($solicitud->getCompany()->getUID());
    }

    private function setEmpresaItem(solicitable $item, $data = array(), $usuario = null) {
        if (!count($data)) return false;
        $empresas = $this->getStartList();
        foreach ($empresas as $empresa) {
            if ($empresaItem = $empresa->getEmpresaItem($item)) {
                $empresaItem->setSuitable($data["suitable"]);
            } else {
                $data['uid_modulo'] = $item->getModuleId();
                $data['uid_item'] = $item->getUID();
                $data['uid_empresa'] = $empresa->getUID();
                $newEmpresaItem = new empresaitem($data, $usuario);
            }

            $action = LegacyLogUI::ACTION_SET_SUITABLE;
            if (0 === $data['suitable']) {
                $action = LegacyLogUI::ACTION_UNSET_SUITABLE;
            }

            $item->writeLogUI($action, null, $usuario, $empresa);
        }
        return true;
    }

    public function canSetSuitableItem(solicitable $item) {
        if ($empresaCorp = $this->perteneceCorporacion()) {
            if ($empresaItemCorp = $empresaCorp->getEmpresaItem($item)) {
                if (!$empresaItemCorp->isSuitable()) {
                    return false;
                }
            }
        }
        return true;
    }

    public function setSuitableItem(solicitable $item, $suitable = 1, $usuario = null) {
        if (!$this->canSetSuitableItem($item)) throw new Exception('error_set_item_corp');
        return $this->setEmpresaItem($item, array('suitable' => $suitable), $usuario);
    }

    public function isSuitableItem(solicitable $item) {
        if ($empresaItem = $this->getEmpresaItem($item)) {
            return $empresaItem->isSuitable();
        }
        return true;
    }

    public function getUnsuitableItemClient(solicitable $item) {
        $starList = $this->getStartList();
        $myClients = $this->obtenerEmpresasCliente();
        $companies = $starList->merge($myClients);
        $unsuitableItemClient = new ArrayObjectList;
        foreach ($companies as $company) {
            if (($company->getUID() != $this->getUID()) && (!$company->isSuitableItem($item))) $unsuitableItemClient[] = $company;
        }
        return $unsuitableItemClient;
    }

    public function deletedSubcontractorNotification ( empresaContratacion $SubcontractorChain, usuario $usuario ) {
        $emailTemplate = plantillaemail::instanciar("subcontratacion");
        $contacts = $this->obtenerContactos($emailTemplate);
        foreach ($contacts as $contact) {
            $contact->sendEmailDeletedSubcontractor( $SubcontractorChain, $usuario );
        }
        return true;
    }

    /*
     * Elimina las entradas en empresa_relacion_temporal para no volver a mostrar avisos a la empresa de solicitud de contratacion
     *
     **/
    public function cancelarSolicitudContratacion( empresa $empresaOrigen ){
        return $this->eliminarRelacion( $this->tabla ."_relacion_temporal", "uid_empresa_superior", $empresaOrigen->getUID(), "uid_empresa_inferior" );
    }

    /**
     * [obtenerEmpresasSolicitantes DEPRECATED, use self::getRequesterCompanies instead]
     * @param  boolean $usuario          [description]
     * @param  boolean $includeSelf      [description]
     * @param  boolean $includeCorps     [description]
     * @param  boolean $withRequirements [description]
     * @return [type]                    [description]
     */
    public function obtenerEmpresasSolicitantes ($usuario = null, $self = true, $corps = true, $requirements = false)
    {
        if (!$usuario instanceof usuario) {
            $usuario = null;
        }

        return $this->getRequesterCompanies($usuario, $self, $corps, $requirements);
    }

    /**
     * ONLY USED FROM Requestable::documents for bug #7814
     * Return ArrayObjectList of companies wich are requesting any document to this item. Never return a corporation
     * @param  usuario $user         the user to filter with
     *
     * @return ArrayObjectList       the collection of requester companies
     * @SuppressWarnings("unused")
     */
    public function getRequesterCompaniesWithRequests(usuario $user)
    {
        return parent::getRequesterCompanies($user, true, false, true);
    }

    /**
     * [getRequesterCompanies return a list of the clients of this company who have requirements]
     * @param  boolean $usuario          [description]
     * @param  boolean $includeSelf      [description]
     * @param  boolean $includeCorps     [description]
     * @param  boolean $withRequirements [description]
     * @return [ArrayObjectList]         [the companies collection]
     */
    public function getRequesterCompanies (usuario $user = null, $self = true, $corps = true, $requirements = false)
    {
        $actives = new ArrayObjectList();
        $company = $user instanceof usuario ? $user->getCompany() : null;
        $requesters = $this->obtenerEmpresasCliente($company, $user);


        // loop over each requester
        foreach ($requesters as $requester) {
            $actives[] = $requester;

            if ($corps === true && $corp = $requester->perteneceCorporacion()) {
                $actives[] = $requester;

                if ($user instanceof usuario) {
                    // Need to check the visibility to the corp
                    $limiterUser = $user->getUserLimiter($corp);
                    if ($limiterUser && $limiterUser->compareTo($user) === false) {
                        continue;
                    }
                }

                $actives[] = $corp;
            }
        }

        if ($self === true) {
            $limiterUser = $user instanceof usuario ? $user->getUserLimiter($this) : false;

            // if the user who hide the visibility is the same as $user
            // we have an optional visibility, and we dont want to hide
            // those clients here
            if ($limiterUser === false || $limiterUser->compareTo($user)) {

                //adding own company if the user has visibility
                $actives[] = $this;
            }
        }

        // filter the companies with docs if neeeded
        if ($requirements) {
            $withRequirements = new ArrayObjectList();
            foreach ($actives as $i => $active) {
                if ($active->countCorpDocuments() > 0) {
                    $withRequirements[] = $active;
                }
            }

            $actives = $withRequirements;
        }

        return $actives->unique();
    }


    /** ALIAS PARA ESTANDARIZACION, RETORNARÁ EL MISMO OBJETO **/
    public function obtenerEmpresaContexto(Iusuario $usuario = NULL){
        return $this;
    }

    /* Crea un valor del 1 al 100 en funcion del acceso de sus usuarios a la
     * aplicacion, para medir su uso
     */
    public function getKarmaLevel(){
        $sql = "SELECT round(avg(fecha_ultimo_acceso))
                FROM agd_data.usuario
                WHERE perfil IN (
                    SELECT uid_perfil
                    FROM agd_data.perfil
                    WHERE uid_empresa = $this->uid
                )
                AND fecha_ultimo_acceso > 0";
        $dato = $this->db->query($sql, 0, 0);

        // ----- dias
        $days = date("d",(time()-$dato));
        return $karma = 100 - $days;
        return $days;
    }

    public function solicitudesTransferenciaEmpleado(empleado $empleadoConcreto = null) {
        $empleados = $empleadoConcreto?array($empleadoConcreto):$this->obtenerEmpleados();
        $relaciones = array();
        foreach ($empleados as $empleado) {
            if ($relacionesEmpleado = $empleado->obtenerRelacionados(TABLE_EMPLEADO.'_empresa_temporal', 'uid_empleado', 'uid_empresa')) {
                $relaciones = array_merge($relaciones,$relacionesEmpleado);
            }

        }
        return $relaciones;
    }

    /** RESTAURAR ESTA EMPRESA COMO CONTRATA DE LA EMPRESA PASADA POR PARAMETRO (SE PUEDE PASAR DIRECTAMENTE EL ID EMPRESA) */
    public function restaurarComoInferiorDe( $empresa ){
        if( $empresa instanceof empresa ){ $empresa = $empresa->getUID(); }
        return $this->actualizarRelacion(  $this->tabla."_relacion", "papelera", 0, "uid_empresa_inferior", "uid_empresa_superior", $empresa );
    }
    /** RESTAURAR ESTA EMPRESA COMO CLIENTE DE LA EMPRESA PASADA POR PARAMETRO (SE PUEDE PASAR DIRECTAMENTE EL ID EMPRESA) */
    public function restaurarComoSuperiorDe( $empresa ){
        if( $empresa instanceof empresa ){ $empresa = $empresa->getUID(); }
        return $this->actualizarRelacion( $this->tabla."_relacion", "papelera", 0, "uid_empresa_superior", "uid_empresa_inferior", $empresa );
    }

    /** DEJA DE SER CONTRATA DE LA EMPRESA PASADA POR PARAMETRO (SE PUEDE PASAR DIRECTAMENTE EL ID EMPRESA) */
    public function dejarDeSerInferiorDe( $empresa ){
        if( $this->eliminarCadenasContratacion($empresa, true) ){
            if( $empresa instanceof empresa ){ $empresa = $empresa->getUID(); }

            return $this->actualizarRelacion( $this->tabla."_relacion", "papelera", 1, "uid_empresa_inferior", "uid_empresa_superior", $empresa );
        }

        return false;
    }

    /** DEJA DE SER CONTRATA DE LA EMPRESA PASADA POR PARAMETRO (SE PUEDE PASAR DIRECTAMENTE EL ID EMPRESA) */
    public function borrarRelacionPara( $elemento, $usuario ){
        $estat = null;

        if( $elemento instanceof empresa || is_numeric($elemento) ){

            if( $elemento instanceof empresa ){

                //Al borrar una relacion debemos borrar las asignaciones...
                $agrupamientos = $this->obtenerAgrupamientosPropios();


                foreach($agrupamientos as $i => $agrupamiento){
                    // Necesitamos contratas, empleados y maquinas, para quitarle los agrupadores
                    $contratas = $elemento->obtenerEmpresasInferiores(null, false, $usuario, 2);
                    $contratas[] = $elemento;


                    foreach( $contratas as $contrata ){
                        $agrupadores = $agrupamiento->obtenerAgrupadoresAsignados($contrata);

                        if( count($agrupadores) ){
                            $contrata->quitarAgrupadores( elemento::getCollectionIds($agrupadores), $usuario);
                        }

                        $empleados = $contrata->obtenerEmpleados(null, false, $usuario);
                        foreach ($empleados as $empleado) {
                            $agrupadores = $agrupamiento->obtenerAgrupadoresAsignados($empleado);
                            if( count($agrupadores) ){
                                $empleado->quitarAgrupadores( elemento::getCollectionIds($agrupadores), $usuario);
                            }
                        }

                        $maquinas = $contrata->obtenerMaquinas(null, false, $usuario);
                        foreach ($maquinas as $maquina) {
                            $agrupadores = $agrupamiento->obtenerAgrupadoresAsignados($maquina);
                            if( count($agrupadores) ){
                                $maquina->quitarAgrupadores( elemento::getCollectionIds($agrupadores), $usuario);
                            }
                        }
                    }
                }

            }

            $empresa = ( is_numeric($elemento) ) ? $elemento : $elemento->getUID();
            $estat = $this->eliminarRelacion($this->tabla."_relacion", "uid_empresa_inferior", $empresa, "uid_empresa_superior");
        } elseif ( $elemento instanceof empleado ){
            $empleado = $elemento->getUID();
            $estat = $this->eliminarRelacion(TABLE_EMPLEADO."_empresa", "uid_empleado", $empleado, "uid_empresa");
        } elseif ( $elemento instanceof maquina ){
            $maquina = $elemento->getUID();
            $estat = $this->eliminarRelacion(TABLE_MAQUINA."_empresa", "uid_maquina", $maquina, "uid_empresa");
        }

        if( !$estat ){ return false; }
        // Al eliminar una empresa de otra, se deben actualizar las solicitudes.. las de los elemenotos
        // inferiores las dejamos en cola para no ralentizar...
        // $elemento->actualizarSolicitudDocumentos();
        return $estat;
    }

    public function deleteElementsVisibility (empresa $parent, Iusuario $user, $deleteforClientes = true) {
        if (true === $parent->companyExistsInChainOf3Levels($this->getUID())) {
            //if there is another chain between the parent and the elemnent we are moving to the trash
            //we cannot delete the visibilities.
            return true;
        }

        //Deleting emplyees visibility
        $empleados = $this->obtenerEmpleados(null, false, $user);
        $empleados = (count($empleados)) ? $empleados->toComaList() : 0;
        $sql = "DELETE FROM ". TABLE_EMPLEADO ."_visibilidad
        WHERE uid_empresa = {$parent->getUID()}
        AND uid_empresa_referencia = {$this->getUID()}
        AND uid_empleado IN ($empleados)";
        if (!$this->db->query($sql)) {
            error_log("erro deleting visibility of the employees of the company {$this->getUID()} to the company {$this->getUID()}");
        }

        //Deleting machines visibility
        $machines = $this->obtenerMaquinas(null, false, $user);
        $machines = (count($machines)) ? $machines->toComaList() : 0;
        $sql = "DELETE FROM ". TABLE_MAQUINA ."_visibilidad
        WHERE uid_empresa = {$parent->getUID()}
        AND uid_empresa_referencia = {$this->getUID()}
        AND uid_maquina IN ($machines)";
        if (!$this->db->query($sql)) {
            error_log("erro deleting visibility of the employees of the company {$this->getUID()} to the company {$this->getUID()}");
        }

        if ($deleteforClientes) {
            $clients = $parent->obtenerEmpresasSuperioresSubcontratando(NULL, NULL, false);
            foreach ($clients as $client) {
                if (!$this->esSubcontrataDe($parent, $client)) {
                    $this->deleteElementsVisibility($client, $user, false);
                }
            }
        }


        return true;
    }


    /** DEJA DE SER CLIENTE DE LA EMPRESA PASADA POR PARAMETRO (SE PUEDE PASAR DIRECTAMENTE EL ID EMPRESA) */
    public function dejarDeSerSuperiorDe( $empresa ){
        if( $empresa instanceof empresa ){ $empresa = $empresa->getUID(); }
        return $this->actualizarRelacion( $this->tabla."_relacion", "papelera", 1, "uid_empresa_superior", "uid_empresa_inferior", $empresa );
    }

    /**  */
    public function esSuperiorDe( $empresa ){
        if( $empresa instanceof empresa ){ $empresa = $empresa->getUID(); }

        $sql = "SELECT uid_empresa_relacion FROM {$this->tabla}_relacion
                WHERE uid_empresa_superior = {$this->getUID()}
                AND uid_empresa_inferior = {$empresa}
            ";

        if ($this->db->query($sql, true)) {
            return true;
        }

        return false;
    }


    /**
     * un paso previo a la creación de una solicitud. si la empresa actual tiene ningún cliente, no se consulta y se
     * añade automáticamente.
     * @param  empresa  $empresa la empresa para la que vamos a trabajar
     * @param  Iusuario $usuario usuario que ejecuta la accion
     * @return bool            resultado de la operacion
     */
    public function hacerInferiorDe( empresa $empresa, Iusuario $usuario = null, $message = NULL, $force = false){
        $uidEmpresa = $empresa->getUID();
        if ($force){
            return $this->crearRelacion( $this->tabla.'_relacion', "uid_empresa_superior", $uidEmpresa, "uid_empresa_inferior", $this->getUID() );
        }

        return false;
    }

    public function createInvitation(empresa $company, usuario $usuario, $message = NULL){
        $conditionsInvitation = array ("cif" => $company->getId());
        $alreadyInvited = signinRequest::checkInvitationCompany($company->getUID(), $conditionsInvitation);
        if ($alreadyInvited) {
            return false;
        }

        $data = array(
            "cif"               => $company->getId(),
            "nombre"            => $company->getUserVisibleName(),
            "uid_pais"          => $company->getCountry()->getUID(),
            "uid_empresa"       => $this->getUID(),
            "state"             => signinRequest::STATE_NOT_SENT,
            "end_date"          => time(),
            "uid_empresa_invitada" => $company->getUID(),
            "message"           => $message,
            "tipo"              => signinRequest::TYPE_INTERNAL
        );

        return new signinRequest($data, $usuario);
    }

    /**
     * se ejecuta cuando se responde una solicitud, en este caso de contratacion
     * @param  solicitud $request la solicitud cuyo estado acaba de cambiar
     * @param  Iusuario    $usuario usuario que ejecuta el cambio
     * @return bool             resultado de la operacion
     */
    public function onRequestResponse(solicitud $request, $usuario = null) {
        switch ($request->getTypeOf()) {
            case solicitud::TYPE_CONTRATACION:
                switch ($requestStatus = $request->getState()) {
                    case solicitud::ESTADO_CANCELADA: case solicitud::ESTADO_RECHAZADA;
                        return true;
                    break;
                    case solicitud::ESTADO_ACEPTADA:
                        $relacionOK = $this->crearRelacion( $this->tabla.'_relacion',
                                    "uid_empresa_superior", $request->getItem()->getUID(),
                                    "uid_empresa_inferior", $request->getCompany()->getUID() );
                        if( $relacionOK ){
                            if (@$_REQUEST['reanexar']) {
                                $m = "empresa";
                                $list = array($request->getCompany()->getUID());
                                $request->getCompany()->actualizarSolicitudDocumentos($usuario);
                                $result = include( DIR_ROOT . "agd/reanexar.php");
                            }
                            return true;
                        }
                    break;
                }
            break;
            case solicitud::TYPE_ELIMINARCONTRATA: case solicitud::TYPE_ELIMINARCLIENTE: case solicitud::TYPE_SUBCONTRATA:
                return true;
            break;
        }
        return false;
    }


    public function obtenerSolicitudesEmpresa(elemento $parent = null, $filter = null) {
        return solicitud::getFromItem('empresa',$this, $parent, $filter);
    }

    public function hasPendingRequests(elemento $parent = null) {
        return (bool) solicitud::getFromItem('empresa',$this,$parent,array('estado'=>empresasolicitud::ESTADO_CREADA),true);
    }

    public function getRequestInvitation (elemento $parent = null) {
        $setRequest = solicitud::getFromItem('empresa', $this, $parent, array('estado'=>empresasolicitud::ESTADO_CREADA));
        if ($setRequest && count($setRequest)) return reset($setRequest);
        return false;
    }

    /** EMPEZAR A SER CLIENTE DE LA EMPRESA PASADA POR PARAMETRO (SE PUEDE PASAR DIRECTAMENTE EL ID EMPRESA) */
    public function hacerSuperiorDe( $empresa ){
        if( $empresa instanceof empresa ){ $empresa = $empresa->getUID(); }
        return $this->crearRelacion( $this->tabla."_relacion", "uid_empresa_inferior", $empresa, "uid_empresa_superior", $this->uid );
    }

    /** RETORNA TRUE SI TIENE ALGUNA EMPRESA SUPERIOR, FALSE SI NO TRABAJA PARA NADIE (EN AGD) */
    public function tieneSuperior(){
        $sql = "SELECT uid_empresa_superior FROM ". $this->tabla."_relacion WHERE uid_empresa_inferior = ".$this->getUID()." LIMIT 1";
        $uid = $this->db->query($sql, 0, 0);
        return ( $uid ) ? true : false;
    }

    /** INDICAMOS OTRA EMPRESA COMO PARAMETRO, POR DEFECTO BUSCA HACIA ARRIBA
                El primero parametro un objeto empresa, si no se define, busca el objeto empresa del cliente activo actual
                El segundo indica si es true que el numero de nivel se pase a texto
                El tercero opcional, si es true, busca hacia abajo
                El cuarto es interno, usado para recursividad
    */
    public function obtenerDistancia($empresa, $toString=true, $inferiores=false, $process=0, $previous=null){
        $cacheString = "{$this}-obtenerDistancia-{$empresa}-{$toString}-{$inferiores}-{$process}";
        if( $process === 0 && ($estado = $this->cache->getData($cacheString)) !== null ){
            return $estado;
        }
        $debug = false;
        if( $empresa instanceof empresa ){
            if($debug) echo "Calculando la distancia de {$this->getUserVisibleName()} a {$empresa->getUserVisibleName()} {$empresa}";


            //dump( "\tComparando #1: ". get_exec_microtime());
            //si es la propia = 0
            if( $empresa->getUID() === $this->getUID() ){
                if( $toString === true ){
                    $string = self::level2String($process);
                    return $string;
                }
                //dump( "\tComparando OK END #1: ". get_exec_microtime());
                return $process;
            }

            $process++;

            if( $inferiores === true ){ die("DESDE AQUI"); }
            // Funcion a usar
            $func = ( $inferiores === true ) ? 'obtenerEmpresasInferiores' : 'obtenerEmpresasSuperiores';
            //si no, superiores
            $arrayFunc = array($this, $func);

            //empresas relacionadas de primer nivel
            $empresasSuperiores = call_user_func($arrayFunc, null);//$this->obtenerEmpresasSuperiores();
            if( is_traversable($empresasSuperiores) && count($empresasSuperiores) ){
                //definimos el nivel
                $collection = new ArrayObjectList($empresasSuperiores);
                $list = $collection->toIntList()->getArrayCopy();
                if( in_array($empresa->getUID(), $list) ){
                    if( $debug ){ dump("La empresa buscada {$empresa->getUserVisibleName()} tiene conexion directa con {$this->getUserVisibleName()} ({$this->getUID()}). Distancia {$process}!"); }
                    if( $toString === true ){
                        $resultString = self::level2String($process);
                        return $resultString;
                    }
                    return $process;
                }


                $sql = "
                    SELECT r1.uid_empresa_superior as r1, r2.uid_empresa_superior as r2, r3.uid_empresa_superior as r3
                    FROM ". TABLE_EMPRESA ."_relacion r1
                    LEFT JOIN ". TABLE_EMPRESA ."_relacion r2
                    ON r2.uid_empresa_inferior = r1.uid_empresa_superior
                    LEFT JOIN ". TABLE_EMPRESA ."_relacion r3
                    ON r3.uid_empresa_inferior = r2.uid_empresa_superior
                    WHERE r1.uid_empresa_inferior IN (". implode(",",$list) .")
                    AND ( r1.uid_empresa_superior = {$empresa->getUID()} OR r2.uid_empresa_superior = {$empresa->getUID()} OR r3.uid_empresa_superior = {$empresa->getUID()} )
                    ORDER BY
                        r1.uid_empresa_superior = {$empresa->getUID()} DESC, r2.uid_empresa_superior = {$empresa->getUID()} DESC, r3.uid_empresa_superior = {$empresa->getUID()} DESC
                ";

                $niveles = $this->db->query($sql, 0, "*");
                if( true === is_countable($niveles) && 0 < count($niveles) ){
                    $distancia = 1;
                    foreach($niveles as $i => $uid){
                        $distancia++;
                        if( $uid === $empresa->getUID() ){

                            if( $toString === true ){
                                $distancia = self::level2String($distancia);
                            }

                            $this->cache->addData($cacheString, $distancia);
                            return $distancia;
                        }

                    }
                }

            }
        } elseif( $empresa instanceof agrupador ){
            $agrupamiento = reset( $empresa->obtenerAgrupamientosContenedores() ); // tomamos el inicial
            $empresa = reset($agrupamiento->getEmpresasClientes()); //tomamos el inicial de nuevo
            $distancia = $this->obtenerDistancia( $empresa, $toString, $inferiores,  $process );
            $this->cache->addData($cacheString, $distancia);
            return $distancia;
        } else {
                throw new Exception( "Error al calcular la distancia" );
        }
        return null;
    }

    /** DEVUELVE UN ARRAY DE ID DE TODOS LOS CLIENTES
            Si el parametro 1 es numerico indicará el nivel de recursividad, si no, indica si queremos ver bloqueadas o no
    */
    public function obtenerIdEmpresasSuperiores($eliminadas = false, $limit = false){
        $cacheString = "idEmpresasSuperiores-{$this}-$eliminadas-$limit";
        if( ($estado = $this->cache->getData($cacheString)) !== null ){
            $intList = ArrayIntList::factory($estado);
            return $intList ? $intList : new ArrayIntList;
        }

        //$condicion = elemento::construirCondicion($eliminadas, $limit);
        $companies = false; // no filter
        if( $limit instanceof usuario && !$limit->getCompany()->compareTo($this) && !$limit->esStaff() ){
            $companies = buscador::getCompaniesIntList($limit);

            if( !count($companies) ){
                $this->cache->addData($cacheString, false);
                return new ArrayIntList;
            }
        } else {
            //dump("No mirarmos acceso en {$this->getUserVisibleName()}");
        }


        // Montar el SELECT basico
        $fields = array("r1.uid_empresa_superior as r1"); // r1.uid_empresa_superior as r1, r2.uid_empresa_superior as r2, r3.uid_empresa_superior as r3
        if( is_numeric($eliminadas) && $eliminadas > 0 ){
            for($i=2;$i<($eliminadas+2);$i++){
                $fields[] = "r$i.uid_empresa_superior as r$i";
            }
        }
        $sql = "SELECT ". implode(",", $fields) ." FROM ". TABLE_EMPRESA ."_relacion r1";


        // Cruces para calcular tablas superiores
        if( is_numeric($eliminadas) ){
            if( $eliminadas > 0 ){
                $cross = array();
                for($i=2;$i<($eliminadas+2);$i++){
                    $cross[] = " LEFT JOIN ". TABLE_EMPRESA ."_relacion r$i ON r$i.uid_empresa_inferior = r".($i-1).".uid_empresa_superior";
                }
                $sql .= implode(" ", $cross);
            }

            $eliminadas = false;
        }
        $sql .= " WHERE r1.uid_empresa_inferior = {$this->getUID()}";



        // Solo listar bloqueados / no bloqueados...
        if( is_bool($eliminadas) ){
            $papelera = ( is_bool($eliminadas) ) ? $eliminadas : false;
            $sql .= " AND r1.papelera = ". ((int) $papelera);
            if( is_numeric($eliminadas) && $eliminadas > 0 ){
                $filter = array();
                for($i=2;$i<($eliminadas+2);$i++){
                    $filter[] = "( r$i.papelera = ". ((int) $papelera) ." OR r$i.papelera IS NULL )";
                }
                $sql .= " AND " . implode(" AND ", $filter);
            }
        }


        if( $companies ){
            $list = $companies->toComaList();
            $sql .= " AND r1.uid_empresa_superior IN ($list)";
            if( is_numeric($eliminadas) && $eliminadas > 0 ){
                $filter = array();
                for($i=2;$i<($eliminadas+2);$i++){
                    $filter[] = "( r$i.uid_papelera_superior IN ($list) )";
                }
                $sql .= " AND " . implode(" AND ", $filter);
            }
        }

        $list = array();

        $set = $this->db->query($sql);
        while($line = db::fetch_row($set)){
            if( is_array($line) ){
                foreach($line as $val){
                    if( is_numeric($val) ) $list[] = $val;
                }
            }
        }


        $list = new ArrayIntList(array_unique($list));
        $this->cache->addData($cacheString, "$list");
        return $list;
    }

    /**
        RETORNA UNA COLECCION DE OBJETOS DE EMPRESAS SI SE INDICA COMO PRIMER PARAMETRO
        FALSE INDICAMOS QUE EL VALORE DE LA PAPELERA SERA 0, ES DECIR, NO SE MOSTRARAN LAS OCULTAS
        SI ES TRUE SE MOSTRARAN LAS OCULTAS Y NULL NO SE FILTRA NADA
    */
    public function obtenerEmpresasSuperiores( $eliminadas = false, $limit = false ){
        $arrayObjetos = array();
        $arrayUIDS = $this->obtenerIdEmpresasSuperiores($eliminadas, $limit);

        $intList = new ArrayIntList($arrayUIDS);
        return $intList->toObjectList("empresa");
    }

    /**METODO ALIAS PARA DEVOLVER LA EMPRESA A LA QUE PERTENECE LA EMPRESA REFERENCIADA DESDE LA LLAMADA DEL METODO
    */
    public function getCompanies($eliminadas = false, $limit = false){
        return $this->obtenerEmpresasSuperiores($eliminadas, $limit);
    }

    /** Alias de getCompanies
    */
    public function obtenerEmpresas($eliminadas = false, $limit = false){
        return $this->getCompanies($eliminadas, $limit);
    }

    /**
        RETORNA UN ARRAY DE DATOS  SI SE INDICA COMO PRIMER PARAMETRO
        FALSE INDICAMOS QUE EL VALORE DE LA PAPELERA SERA 0, ES DECIR, NO SE MOSTRARAN LAS OCULTAS
        SI ES TRUE SE MOSTRARAN LAS OCULTAS Y NULL NO SE FILTRA NADA

        UNA ALIAS PARA ESTANDARIZAR
    */
    public function obtenerIdEmpresas( $eliminadas = false, $limit = false, $usuario = false, $contar = false, $returnSQL = false ){
        return $this->obtenerIdEmpresasInferiores($eliminadas, $limit, $usuario, 0, $contar, $returnSQL);
    }

    public function obtenerIdEmpresasInferiores( $eliminadas = false, $limit = false, $usuario = false, $recursive=0, $contar=false, $returnSQL = false, $condicionExtra=""){
        $cacheString = "empresa-subcontratas-{$this->uid}-". ($eliminadas===null?'null':$eliminadas). (($limit)?implode("-",$limit):"") ."-{$usuario}-{$recursive}-{$contar}-{$returnSQL}-{$condicionExtra}";
        if (($estado = $this->cache->getData($cacheString)) !== null) {
            if ($contar || $returnSQL) return $estado;
            return ArrayIntList::factory($estado);
        }

        $condicion = elemento::construirCondicion( $eliminadas , $limit );

        if( $usuario instanceof usuario ){
            if( $usuario->isViewFilterByGroups() ){
                $userCondition = $usuario->obtenerCondicion($this, "uid_empresa_inferior");
                if( $userCondition ){
                    $condicion = " uid_empresa_inferior IN ( $userCondition ) AND $condicion ";
                } else {
                    if ($contar) return 0;
                    return new ArrayIntList();
                }
            }

            if( isset($_GET["forcevisible"]) && $usuario->esStaff() ){
                // Para permitir ver todo a sati
            } else {
                $empresaUsuario = $usuario->getCompany();
                $empresaCorporacion = $empresaUsuario->esCorporacion() && $empresaUsuario->obtenerEmpresasInferiores()->contains($this);

                if( !$empresaUsuario->compareTo($this) && !$empresaCorporacion ){
                    $condicion = " uid_empresa_inferior IN (
                        SELECT n3 FROM ". TABLE_EMPRESA ."_contratacion WHERE n1 IN ({$empresaUsuario->getStartIntList()->toComaList()}) AND n2 = {$this->getUID()}
                        UNION
                        SELECT n4 FROM ". TABLE_EMPRESA ."_contratacion WHERE n1 IN ({$empresaUsuario->getStartIntList()->toComaList()}) AND n3 = {$this->getUID()}
                    ) AND " . $condicion;
                }
            }
        } else if ($usuario instanceof empresa) {
            $empresaCliente = $usuario;
            $empresaCorporacion = $empresaCliente->esCorporacion() && $empresaCliente->obtenerEmpresasInferiores()->contains($this);

            if( !$empresaCliente->compareTo($this) && !$empresaCorporacion ){
                $condicion = " uid_empresa_inferior IN (
                    SELECT n3 FROM ". TABLE_EMPRESA ."_contratacion WHERE n1 IN ({$empresaCliente->getStartIntList()->toComaList()}) AND n2 = {$this->getUID()}
                    UNION
                    SELECT n4 FROM ". TABLE_EMPRESA ."_contratacion WHERE n1 IN ({$empresaCliente->getStartIntList()->toComaList()}) AND n3 = {$this->getUID()}
                ) AND " . $condicion;
            }
        }


        if( $condicionExtra ){
            $condicion = " $condicionExtra";
        }

        if ( $contar === true ) {
            return $this->obtenerConteoRelacionados( $this->tabla."_relacion", "uid_empresa_superior", "uid_empresa_inferior", $condicion );
        } else {

            $tableSearch = $this->tabla."_relacion INNER JOIN agd_data.empresa ON uid_empresa_inferior = uid_empresa";
            $result = $this->obtenerRelacionados( $tableSearch, "uid_empresa_superior", "uid_empresa_inferior", $condicion, "nombre", $returnSQL );

            if( $returnSQL === true ){
                $this->cache->addData( $cacheString, $result, 2000);
                return $result;
            } else {
                $arrayIDS = new ArrayIntList();
                if (is_array($result) && count($result)) {
                    foreach ($result as $datosRelacion){
                        $arrayIDS[] = $datosRelacion["uid_empresa_inferior"];
                    }
                }

                $arrayIDS = $arrayIDS->unique();

                if ($recursive > 0) {
                    foreach($arrayIDS as $id) {
                        $empresaInferior = new empresa($id, false);
                        $arrayIDS = $arrayIDS->merge( $empresaInferior->obtenerIdEmpresasInferiores($eliminadas, $limit, $usuario, ($recursive-1)) )->unique();
                    }
                }
            }
        }


        $this->cache->addData($cacheString, "$arrayIDS", 20);
        return $arrayIDS;
    }


    public function obtenerContratasConEmpleados(){
        return $this->obtenerContratasConElementos('empleado');
    }

    public function obtenerContratasConMaquinas(){
        return $this->obtenerContratasConElementos('maquina');
    }


    private function obtenerContratasConElementos($modulo){
        $table = constant('TABLE_' . strtoupper($modulo));
        $sql = "SELECT uid_empresa_inferior FROM ". TABLE_EMPRESA . "_relacion er
                INNER JOIN {$table}_empresa ee ON ee.uid_empresa = er.uid_empresa_inferior
                WHERE er.uid_empresa_superior = {$this->getUID()} AND er.papelera = 0 AND ee.papelera = 0
                GROUP BY uid_empresa_inferior ORDER BY count(*) DESC
        ";

        $array = $this->db->query($sql, "*", 0, 'empresa');
        return new ArrayObjectList($array);
    }

    /** ESTA FUNCION NO ES NECESARIA REALMENTE ES SOLO PARA "AÑADIDOS" EN LA APLICACION
        QUE PERMITE EXTRAER EMPRESAS QUE REALMENTE TIENEN UN VOLUMEN **/
    public function obtenerEmpresasInferioresRelevantes($n=40, $level=0){
        if( $n === false ){ $n = 110; }
        $minimun = 7 - (3 * $level);

        $condicion = " (
            ( SELECT count(uid_empleado) FROM ". TABLE_EMPLEADO ."_empresa ee WHERE ee.uid_empresa = uid_empresa_inferior ) >= $n
        )";
        $list = $this->obtenerIdEmpresasInferiores( false, array(0,$minimun), false/*$usuario*/, 0, false, false, $condicion);


        //dump("En el nivel $level. Obtengo menos de $minimun empresas (". count($list) .") para $n empleados. Prueba con ". ($n-10) ." empleados");
        if( count($list)<$minimun && $n>10 ){

            //dump("Si");
            return $this->obtenerEmpresasInferioresRelevantes( ($n-10), $level );
        }
        $coleccion = new ArrayObjectList();
        foreach($list as $uid){
            $coleccion[] = new empresa($uid);
        }

        return $coleccion;
    }

    public function isEnterprise(){
        return ($this->getLicense() === empresa::LICENSE_ENTERPRISE);
    }

    public function isFree(){
        return ((int) $this->getSelectedLicense() === empresa::LICENSE_FREE || $this->getLicense() === empresa::LICENSE_FREE);
    }

    public function isPremium(){
        return ((int) $this->getSelectedLicense() === empresa::LICENSE_PREMIUM && $this->getLicense() === empresa::LICENSE_PREMIUM);
    }

    public function isTemporary () {
        return $this->isPremium() && $this->hasTemporaryPayment() && !$this->hasExpiredLicense();
    }

    public function hasTemporaryPayment () {
        $currentservice = $this->getPaidInfo();
        if (count((array)$currentservice)) {
            return $currentservice->daysValidLicense == paypalLicense::DAYS_TEMP_LICENSE;
        }
        return false;

    }

    /** NOS DA EL SKIN SELECCIONADO PARA ESTE CLIENTE */
    public function getSkinName(){
        $info = $this->getInfo();
        return $info["skin"];
    }

    /** AGREGA AL CONJUNTO DE EMPRESAS INFERIORES LA PROPIA EMPRESA */
    public function obtenerEmpresasInferioresMasActual( $eliminadas = false, $limit = false, $usuario = false, $recursive=0 ){
        $datos = $this->obtenerEmpresasInferiores( $eliminadas, $limit, $usuario, $recursive )->getArrayCopy();

        array_unshift($datos, $this);
        return new ArrayObjectList($datos);
    }

    /**
        RETORNA UNA COLECCION DE OBJETOS DE EMPRESAS SI SE INDICA COMO PRIMER PARAMETRO
        FALSE INDICAMOS QUE EL VALORE DE LA PAPELERA SERA 0, ES DECIR, NO SE MOSTRARAN LAS OCULTAS
        SI ES TRUE SE MOSTRARAN LAS OCULTAS Y NULL NO SE FILTRA NADA

        Condicion permite aplicar una condicion adicional a la sql
    */
    public function obtenerEmpresasInferiores( $eliminadas = false, $limit = false, $usuario = false, $recursive=0, $contar=false ){
        $arrayObjetos = array();
        $arrayUIDS = $this->obtenerIdEmpresasInferiores( $eliminadas, $limit, $usuario, $recursive, $contar );
        if( $contar === true ){
            return $arrayUIDS;
        }
        foreach( $arrayUIDS as $uidEmpresa ){
            $arrayObjetos[] = new self($uidEmpresa, false);
        }
        return new ArrayObjectList($arrayObjetos);
    }

    public function getCompanySummary (usuario $user = NULL, $network = true, $contracts = false) {
        return $this->getCountSummary('empresa', $user, $network, $contracts);
    }

    public function getEmployeeSummary (usuario $user, $network = true, $contracts = false) {
        return $this->getCountSummary('empleado', $user, $network, $contracts);
    }

    public function getMachineSummary (usuario $user, $network = true, $contracts = false) {
        return $this->getCountSummary('maquina', $user, $network, $contracts);
    }

    private function getCountSummary ($class, usuario $user, $network = true, $contracts = false)
    {
        $primaryKey = "uid_{$class}";
        $table = constant('TABLE_' . strtoupper($class));
        $status = [documento::ESTADO_ANEXADO, documento::ESTADO_CADUCADO, documento::ESTADO_ANULADO];
        $filter = $user instanceof usuario ? $user->obtenerCondicionDocumentosView($class) : '';
        $viewTable = TABLE_DOCUMENTO ."_{$class}_estado";
        $conditions = [];
        $condition = "1";

        $indexSet = (string) $this->app['index.repository']->getIndexOf(
            $class,
            $this->asDomainEntity(),
            $user->asDomainEntity(),
            $network
        );

        $indexArr = explode(',', $indexSet);
        // don't count our company
        if ($class === 'empresa') {
            $selfKey = array_search((int) $this->getUID(), $indexArr);
            if (true == $selfKey) {
                unset($indexArr[$selfKey]);
            }

            $indexSet = implode(',', $indexArr);
        }

        if ($contracts) {
            $companies = TABLE_EMPRESA . '_relacion';
            $relationship = $table . '_empresa';
            $fromContracts = "SELECT r.{$primaryKey}
            FROM {$companies} c
            INNER JOIN {$relationship} r
            ON c.uid_empresa_inferior = r.uid_empresa
            WHERE c.uid_empresa_superior = {$this->getUID()}";
            $conditions[] = "i.uid_{$class} IN ({$fromContracts})";
        }

        if (count($conditions)) {
            $condition = implode(" AND ", $conditions);
        }
        // Count totals
        $sqlTotal = "SELECT count({$primaryKey})
        FROM {$table} i
        WHERE 1
        AND {$primaryKey} IN ({$indexSet})
        AND {$condition}";

        $sqlWrong = "SELECT count(distinct i.{$primaryKey}) num
        FROM {$table} i
        INNER JOIN {$viewTable} view
        ON i.{$primaryKey} = view.{$primaryKey}
        AND i.{$primaryKey} IN ({$indexSet})
        WHERE 1
        AND {$condition}
        AND descargar = 0
        AND obligatorio = 1
        AND (estado IS NULL or estado IN (". implode(',', $status) ."))
        {$filter}
        ";

        $sqlInactive = "SELECT count(i.{$primaryKey}) num
        FROM {$table} i
        WHERE {$primaryKey} IN ({$indexSet})
        AND {$primaryKey} NOT IN (
            SELECT {$primaryKey}
            FROM {$viewTable} view
            WHERE view.{$primaryKey} = i.{$primaryKey}
            AND descargar = 0
            AND obligatorio = 1
            {$filter}
        ) AND {$condition}";

        $total = (int) $this->db->query($sqlTotal, 0, 0);
        $wrong = (int) $this->db->query($sqlWrong, 0, 0);
        $inactive = (int) $this->db->query($sqlInactive, 0, 0);
        $invalid = $wrong + $inactive;

        $results = (object) array(
            'total'     => $total,
            'valid'     => abs($total - $invalid),
            'invalid'   => $invalid,
            'inactive'  => $inactive
        );

        return $results;
    }


    public function getWrongChilds (Iusuario $user, $class, $opts = [], $returnType = 'objectList') {
        $primaryKey = "uid_{$class}";
        $userAsDomainEntity = $legacyUser = null;

        if ($user instanceof usuario) {
            $legacyUser = $user;
        }

        if ($user instanceof perfil) {
            $legacyUser = $user->getUser();
        }

        if ($legacyUser instanceof usuario) {
            $userAsDomainEntity = $legacyUser->asDomainEntity();
        }

        $indexList = $this->app['index.repository']->getIndexOf(
            $class,
            $this->asDomainEntity(),
            $userAsDomainEntity,
            false
        );

        $dataSetTable = $indexList->toUnionTable();

        // The "alerts" option means we want only the wrong items but those which the $user can handle. Right now only affects to the documents with status = attached
        $alerts = isset($opts['alerts']) ? $opts['alerts'] : false;

        if ($alerts) {
            $status = [documento::ESTADO_CADUCADO, documento::ESTADO_ANULADO];
        } else {
            $status = [documento::ESTADO_ANEXADO, documento::ESTADO_CADUCADO, documento::ESTADO_ANULADO];
        }

        $filter = $user instanceof Iusuario ? $user->obtenerCondicionDocumentosView($class) : '';
        $viewTable = TABLE_DOCUMENTO ."_{$class}_estado";

        $limit = isset($opts['limit']) ? $opts['limit'] : false;

        $valid = documento::ESTADO_VALIDADO;
        $attached = documento::ESTADO_ANEXADO;
        $rejected = documento::ESTADO_ANULADO;
        $near = solicituddocumento::getNearExpireSQL($class);

        $statuses = [];
        $statuses[] = "estado IS NULL";
        $statuses[] = "estado IN (". implode(',', $status) .")";
        $statuses[] = "reverse_status = {$rejected}";
        $statuses[] = "(estado = {$valid} OR estado = {$attached}) AND uid_anexo_{$class} IN ($near)";

        $status = "(" . implode(" OR ", $statuses) . ")";

        $wrongs = "{$primaryKey} IN (
            SELECT {$primaryKey} FROM {$viewTable} as view WHERE 1
            AND view.{$primaryKey} = i.{$primaryKey}
            AND descargar = 0
            AND obligatorio = 1
            AND ({$status})
            {$filter}
        )";

        $invalids = "{$primaryKey} NOT IN (
            SELECT {$primaryKey}
            FROM {$viewTable} view
            WHERE view.{$primaryKey} = i.{$primaryKey}
            AND descargar = 0
            AND obligatorio = 1
            {$filter}
        )";


        switch ($returnType) {
            case 'count':
                $sql = "SELECT count({$primaryKey}) FROM ({$dataSetTable}) i WHERE ($wrongs) OR ($invalids)";

                return (int) $this->db->query($sql, 0, 0);
                break;
        }

        $sql = "SELECT {$primaryKey} FROM ({$dataSetTable}) i WHERE ($wrongs) OR ($invalids) GROUP BY {$primaryKey}";

        if ($limit) {
            $sql .= " LIMIT {$limit[0]}, {$limit[1]}";
        }

        if ($list = $this->db->query($sql, '*', 0, $class)) {
            return new ArrayObjectList($list);
        }

        return new ArrayObjectList;
    }




    public function getEmployees(Iusuario $user = null, $sqlOptions = [])
    {
        $count = isset($sqlOptions['count']) ? $sqlOptions['count'] : false;
        $limit = isset($sqlOptions['limit']) ? $sqlOptions['limit'] : false;
        $order = isset($sqlOptions['order']) ? $sqlOptions['order'] : false;
        $query = isset($sqlOptions['q']) ? utf8_decode(db::scape($sqlOptions['q'])) : false;

        $table = TABLE_EMPLEADO;
        $userAsDomainEntity = $legacyUser = null;

        if ($user instanceof usuario) {
            $legacyUser = $user;
        }

        if ($user instanceof perfil) {
            $legacyUser = $user->getUser();
        }

        if ($legacyUser instanceof usuario) {
            $userAsDomainEntity = $legacyUser->asDomainEntity();
        }

        $indexList = (string) $this->app['index.repository']->getIndexOf(
            'empleado',
            $this->asDomainEntity(),
            $userAsDomainEntity,
            false
        );

        $name = "concat(nombre, ' ', apellidos)";
        $key = "uid_empleado";
        $field = $count ? "count({$key})" : $key;
        $sql = "SELECT {$field} FROM {$table} e WHERE {$key} IN ({$indexList})";

        if ($query) {
            $sql .= " AND ({$name} LIKE '%{$query}%' OR dni LIKE '%{$query}%')";
        }

        if ($count) {
            return  $this->db->query($sql, 0, 0);
        }

        switch ($order) {
            case 'surname':
                $sql .= " ORDER BY apellidos";
                break;
            default:
                $sql .= " ORDER BY {$name}";
                break;
        }

        if ($limit) {
            $sql .= " LIMIT {$limit[0]}, {$limit[1]}";
        }

        if ($list = $this->db->query($sql, '*', 0, 'empleado')) {
            return new ArrayObjectList($list);
        }

        return new ArrayObjectList();
    }

    public function getEmployeesInTrash ($sqlOptions = [], $returnType = 'objectList') {
        $limit = isset($sqlOptions['limit']) ? $sqlOptions['limit'] : false;
        $query = isset($sqlOptions['q']) ? utf8_decode(db::scape($sqlOptions['q'])) : false;

        $key = "uid_empleado";
        $field = $returnType == 'count' ? "count({$key})" : $key;
        $table = TABLE_EMPLEADO."_empresa INNER JOIN ". TABLE_EMPLEADO." USING ({$key})";
        $name = "concat(nombre, ' ', apellidos)";
        $sql = "SELECT {$field} FROM {$table} WHERE uid_empresa = {$this->getUID()} AND papelera = 1";

        if ($query) {
            $sql .= " AND ({$name} LIKE '%{$query}%' OR dni LIKE '%{$query}%')";
        }

        $sql .= " ORDER BY {$name}";

        if ($limit) {
            $sql .= " LIMIT {$limit[0]}, {$limit[1]}";
        }

        switch ($returnType) {
            case 'count':
                return (int) $this->db->query($sql, 0, 0);
            case 'objectList':
                $items = $this->db->query($sql, "*", 0, "empleado");;
                return new ArrayObjectList($items);
        }
    }



    public function obtenerIdEmpleados( $eliminadas = false, $limit = false, $usuario = false, $contar = false, $returnSQL = false ){
        $cacheString = "empresa-obtenerIdEmpleados-{$this}-{$eliminadas}-". (($limit)?implode("-",$limit):"") ."-{$usuario}-{$contar}-{$returnSQL}";
        if( ($estado = $this->cache->getData($cacheString)) !== null ){
            if ($contar || $returnSQL) return $estado;
            return ArrayIntList::factory($estado);
        }

        $condicion = elemento::construirCondicion( $eliminadas, $limit );

        if( $usuario instanceof usuario ){
            if( $usuario->isViewFilterByGroups() ){
                $userCondition = $usuario->obtenerCondicion($this, "uid_empleado");
                if( $userCondition ){
                    $condicion = " uid_empleado IN ($userCondition) AND $condicion ";
                } else {
                    if ($contar) return 0;
                    return new ArrayIntList();
                }
            }

            if( isset($_REQUEST["forcevisible"]) && $usuario->esStaff() ){
                // Para permitir ver todo a sati
            } else {
                $empresaUsuario = $usuario->getCompany();
                $empresaCorporacion = $empresaUsuario->esCorporacion() && $empresaUsuario->obtenerEmpresasInferiores()->contains($this);

                if( !$empresaUsuario->compareTo($this) && !$empresaCorporacion ){
                    $condicion = " uid_empleado IN (
                        SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_visibilidad WHERE 1
                        AND uid_empresa IN ({$empresaUsuario->getStartIntList()->toComaList()})
                        AND uid_empresa_referencia = {$this->getUID()}
                    ) AND " . $condicion;
                }
            }


        }


        if( $contar === true && !$returnSQL ){
            return $this->obtenerConteoRelacionados( TABLE_EMPLEADO."_empresa", "uid_empresa", "uid_empleado", $condicion );
        }


        $table = TABLE_EMPLEADO."_empresa INNER JOIN ". TABLE_EMPLEADO . " USING(uid_empleado)";
        $result = $this->obtenerRelacionados($table, "uid_empresa", "uid_empleado", $condicion, "concat(nombre,' ',apellidos)", $returnSQL);
        if( $returnSQL === true ){
            $this->cache->addData( $cacheString, $result, 2000);
            return $result;
        } else {
            $arrayUIDS = new ArrayIntList();
            if( is_array($result) && count($result) ){
                foreach($result as $datosRelacion ){
                    $arrayUIDS[] = $datosRelacion["uid_empleado"];
                }
            }

            $this->cache->addData($cacheString, "$arrayUIDS");
            return $arrayUIDS;
        }

    }

    public function obtenerEmpleados( $eliminadas = false, $limit = false, $usuario = false, $contar=false ){
        $intList = $this->obtenerIdEmpleados($eliminadas, $limit, $usuario, $contar);
        return $contar ? $intList : new ArrayEmployeeList($intList->toObjectList('empleado'));;
    }

    public function obtenerIdUsuarios($eliminadas = false, $limit = false, $usuario = false, $contar = false, $search = false) {
        $condicion = elemento::construirCondicion( $eliminadas , $limit );


        if ($search) {
            $search = db::scape($search);

            if('UTF-8' === mb_detect_encoding($search, 'auto')){
                $search = utf8_decode($search);
            }

            $fields = ['concat(TRIM(nombre), " ", TRIM(apellidos))', 'email', 'usuario'];
            $where = [];
            foreach ($fields as $field) {
                $where[] = $field . " LIKE '%{$search}%'";
            }

            $filter = implode(" OR ", $where);
            $condicion = "({$filter}) AND {$condicion}";
        }


        if (!$usuario instanceof usuario || !$usuario->esStaff()) {
            $condicion = " !config_sat  AND !config_admin AND " . $condicion;
        }


        if( $contar === true ){
            $tableSearch = TABLE_PERFIL." INNER JOIN agd_data.usuario USING(uid_usuario) ";
            return $this->obtenerConteoRelacionados( $tableSearch, "uid_empresa", "uid_usuario", $condicion );
        } else {
            $arrayUIDS = array();
            $tableSearch = TABLE_PERFIL." INNER JOIN agd_data.usuario USING(uid_usuario) ";
            $arrayRelaciones = $this->obtenerRelacionados( $tableSearch, "uid_empresa", "uid_usuario", $condicion, "usuario" );
            if( is_array($arrayRelaciones) && count($arrayRelaciones) ){
                foreach( $arrayRelaciones as $datosRelacion ){
                    $arrayUIDS[] = $datosRelacion["uid_usuario"];
                }
            }
            return $arrayUIDS;
        }
    }

    public function hasEmployee(empleado $employee){
        $sql = "SELECT uid_empleado
                FROM ". TABLE_EMPLEADO ."_empresa INNER JOIN ". TABLE_EMPLEADO ." USING(uid_empleado)
                WHERE uid_empresa IN (".$this->getStartIntList().")
                AND papelera = 0
                AND uid_empleado = ".$employee->getUID()."
                GROUP BY uid_empleado";
        $uid = $this->db->query($sql, 0, 0);
        if( is_numeric($uid) ) return true;
        return false;
    }

    public function hasMachine(maquina $machine){
        $sql = "SELECT uid_maquina
                FROM ". TABLE_MAQUINA ."_empresa
                WHERE uid_empresa IN (".$this->getStartIntList().")
                AND papelera = 0
                AND uid_maquina = ".$machine->getUID()."
                GROUP BY uid_maquina";
        $uid = $this->db->query($sql, 0, 0);
        if( is_numeric($uid) ) return true;
        return false;
    }

    public function obtenerUsuariosOnline(){
        //SELECT uid_perfil, uid_usuario, uid_empresa FROM agd_data.perfil WHERE uid_empresa = 3
        return $this->obtenerUsuarios(" uid_usuario IN ( SELECT uid_usuario FROM ". TABLE_USUARIO ." u WHERE u.uid_usuario = uid_usuario AND conexion = 1 ) ");
    }

    /** RETORNARA UNA COLECCION DE OBJETOS QUE ESTE CLIENTE NO PUEDE VER (elemento_cliente)
            @param $usuario -> usuario actual
            @param $tipo -> tipo de objeto que estará oculto
    */
    public function elementosOcultos(usuario $usuario, $tipo, $contar = false){
        $tipo = strtolower($tipo);
        $tabla = constant("TABLE_". strtoupper($tipo)) . "_cliente";
        $fn = "obtenerId{$tipo}s";


        $sqlAll = call_user_func( array($this, $fn), false, false, false, false, true);
        $sqlVisibles = call_user_func( array($this, $fn),  false, false, $usuario, false, true );

        list($sqlAll, $limit) = explode("GROUP BY ", $sqlAll);
        list($sqlVisibles, $limitVisibles) = explode("GROUP BY ", $sqlVisibles);

        $fieldname = ( $tipo == "empresa" ) ? "empresa_inferior" : $tipo;

        //$filter = $this->obtenerIdEmpleados(false, false, $usuario, false, true);
        if( $contar === true ){
            $sqlAll = str_replace("SELECT uid_$fieldname", "SELECT count(uid_$fieldname)", $sqlAll);
            $sql = "$sqlAll AND uid_$fieldname NOT IN ($sqlVisibles)";
            return (int) $this->db->query($sql, 0, 0);
        } else {
            $sql = "$sqlAll AND uid_$fieldname NOT IN ($sqlVisibles) GROUP BY $limit";
            return $this->db->query($sql, "*", 0, $tipo);
        }
    }

    public function obtenerUsuarios( $eliminadas = false, $limit = false, $usuario = false, $contar=false, $search = false ){
        $arrayObjetos = array();
        $arrayUIDS = $this->obtenerIdUsuarios( $eliminadas, $limit, $usuario, $contar, $search );
        if( $contar === true ){
            return $arrayUIDS;
        }
        if( count($arrayUIDS) ){
            foreach( $arrayUIDS as $uidUsuario ){
                $arrayObjetos[] = new usuario($uidUsuario);
            }
        }

        if (count($arrayObjetos)) return new ArrayObjectList($arrayObjetos);
        //$arrayObjetos = elemento::orderObjects($arrayObjetos);
        return new ArrayObjectList();
    }

    public function obtenerValidadores () {
        $sql = "
            SELECT uid_usuario FROM ". TABLE_PERFIL ." p INNER JOIN ". TABLE_USUARIO ." u USING(uid_usuario)
            WHERE uid_empresa = {$this->getUID()} AND config_validador = 1
        ";

        if (CURRENT_ENV == 'prod') {
            $sql .= " AND config_admin = 0";
        }

        $sql .= " GROUP BY uid_usuario";

        $collection = $this->db->query($sql, '*', 0, 'usuario');
        return new ArrayObjectList($collection);
    }

    public function obtenerIdMaquinas( $eliminadas = false, $limit = false, $usuario=false, $contar = false, $returnSQL = false ){
        $cacheString = __CLASS__."-".__FUNCTION__."-{$this}-{$eliminadas}-". (($limit)?implode("-",$limit):"") ."-{$usuario}-{$contar}-{$returnSQL}";
        if (($estado = $this->cache->getData($cacheString)) !== null) {
            if ($contar || $returnSQL) return $estado;
            return ArrayIntList::factory($estado);
        }

        $condicion = elemento::construirCondicion( $eliminadas, $limit );

        if( $usuario instanceof usuario ){
            if(  $usuario->isViewFilterByGroups() ){
                $userCondition = $usuario->obtenerCondicion($this, "uid_maquina");
                if( $userCondition ){
                    $condicion = " uid_maquina IN ( $userCondition ) AND $condicion ";
                } else {
                    if ($contar) return 0;
                    return new ArrayIntList();
                }
            }

            if( isset($_REQUEST["forcevisible"]) && ( $usuario->esAdministrador() || $usuario->esSATI() ) ){
                // Para permitir ver todo a sati
            } else {
                $empresaUsuario = $usuario->getCompany();
                $empresaCorporacion = $empresaUsuario->esCorporacion() && $empresaUsuario->obtenerEmpresasInferiores()->contains($this);

                if( !$empresaUsuario->compareTo($this) && !$empresaCorporacion ){
                    $condicion = " uid_maquina IN (
                        SELECT uid_maquina FROM ". TABLE_MAQUINA ."_visibilidad WHERE 1
                        AND uid_empresa IN ({$empresaUsuario->getStartIntList()->toComaList()})
                        AND uid_empresa_referencia = {$this->getUID()}
                    ) AND " . $condicion;
                }
            }
        }

        if( $contar === true ){
            return $this->obtenerConteoRelacionados( TABLE_MAQUINA . "_empresa", "uid_empresa", "uid_maquina", $condicion );
        }

        $tableSearch = TABLE_MAQUINA."_empresa INNER JOIN agd_data.maquina USING(uid_maquina)";
        $result = $this->obtenerRelacionados( $tableSearch, "uid_empresa", "uid_maquina", $condicion, "serie, nombre", $returnSQL);
        //$result = $this->obtenerRelacionados($table, "uid_empresa", "uid_empleado", $condicion, "concat(nombre,' ',apellidos)", $returnSQL);
        if( $returnSQL === true ){
            return $result; // lo cual no es en realidad un array si no una .sql
        } else {
            $arrayUIDS = new ArrayIntList;
            if( is_array($result) && count($result) ){
                foreach( $result as $datosRelacion ){
                    $arrayUIDS[] = $datosRelacion["uid_maquina"];
                }
            }

            $this->cache->addData($cacheString, "$arrayUIDS");
            return $arrayUIDS;
        }
    }

    public function obtenerMaquinas($eliminadas = false, $limit = false, $usuario=false, $contar = false){
        $intList = $this->obtenerIdMaquinas($eliminadas, $limit, $usuario, $contar);
        return $contar ? $intList : $intList->toObjectList('maquina');
    }


    public function getMachines (Iusuario $user = null, $sqlOptions = []) {
        $count = isset($sqlOptions['count']) ? $sqlOptions['count'] : false;
        $limit = isset($sqlOptions['limit']) ? $sqlOptions['limit'] : false;
        $order = isset($sqlOptions['order']) ? $sqlOptions['order'] : false;
        $query = isset($sqlOptions['q']) ? utf8_decode(db::scape($sqlOptions['q'])) : false;


        $table = TABLE_MAQUINA;
        $userAsDomainEntity = $legacyUser = null;

        if ($user instanceof usuario) {
            $legacyUser = $user;
        }

        if ($user instanceof perfil) {
            $legacyUser = $user->getUser();
        }

        if ($legacyUser instanceof usuario) {
            $userAsDomainEntity = $legacyUser->asDomainEntity();
        }

        $indexList = (string) $this->app['index.repository']->getIndexOf(
            'maquina',
            $this->asDomainEntity(),
            $userAsDomainEntity,
            false
        );

        $name = "concat(nombre, ' ', serie, ' ', ifnull(matricula, ''))";
        $key = "uid_maquina";
        $field = $count ? "count({$key})" : $key;
        $sql = "SELECT {$field} FROM {$table} e WHERE {$key} IN ({$indexList})";


        if ($query) {
            $sql .= " AND {$name} LIKE '%{$query}%'";
        }

        if ($count) {
            return  $this->db->query($sql, 0, 0);
        }

        switch ($order) {
            case 'serie':
                $sql .= " ORDER BY serie";
                break;
            default:
                $sql .= " ORDER BY {$name}";
                break;
        }


        if ($limit) {
            $sql .= " LIMIT {$limit[0]}, {$limit[1]}";
        }

        if ($list = $this->db->query($sql, '*', 0, 'maquina')) {
            return new ArrayObjectList($list);
        }

        return new ArrayObjectList;
    }

    public function getMachinesInTrash ($sqlOptions = [], $returnType = 'objectList') {
        $limit = isset($sqlOptions['limit']) ? $sqlOptions['limit'] : false;
        $query = isset($sqlOptions['q']) ? utf8_decode(db::scape($sqlOptions['q'])) : false;

        $key = "uid_maquina";
        $field = $returnType == 'count' ? "count({$key})" : $key;
        $table = TABLE_MAQUINA."_empresa INNER JOIN ". TABLE_MAQUINA." USING ({$key})";
        $name = "concat(nombre, ' ', serie, ' ', ifnull(matricula, ''))";
        $sql = "SELECT {$field} FROM {$table} WHERE uid_empresa = {$this->getUID()} AND papelera = 1";

        if ($query) {
            $sql .= " AND {$name} LIKE '%{$query}%'";
        }

        $sql .= " ORDER BY {$name}";

        if ($limit) {
            $sql .= " LIMIT {$limit[0]}, {$limit[1]}";
        }

        switch ($returnType) {
            case 'count':
                return (int) $this->db->query($sql, 0, 0);
            case 'objectList':
                $items = $this->db->query($sql, "*", 0, "maquina");;
                return new ArrayObjectList($items);
        }
    }

    public function obtenerEmailContactos($plantilla=false){
        $emails = array();
        $contactos = $this->obtenerContactos($plantilla);
        foreach($contactos as $contacto){
            $email = $contacto->obtenerDato("email");
            if( $email = trim($email) ){
                $emails[] = $email;
            }
        }
        $emails = new ArrayObjectList($emails);
        return $emails->unique();
    }


    public function obtenerContactos() {
        $arrayContactos = array();
        $sql = "SELECT uid_empresa_contacto FROM ". $this->tabla ."_contacto pc WHERE pc.uid_empresa = $this->uid ";

        $arguments = func_get_args();

        foreach ($arguments as $arg) {
            if ($arg instanceof ArrayObjectList) {
                $templates = $arg->toComaList();
                $notMandatoryTemplates = new ArrayObjectTemplateEmail(plantillaemail::$templatesToAvoid);

                $sql .= "
                    AND (
                        pc.uid_empresa_contacto IN (
                            SELECT c.uid_contacto FROM ". TABLE_CONTACTO_PLANTILLAEMAIL ." c
                            WHERE c.uid_contacto = pc.uid_empresa_contacto
                            AND c.uid_plantillaemail IN ($templates)
                        )
                    ";

                if (!$arg->match($notMandatoryTemplates)) {
                    $sql .=  " OR principal = 1";
                }

                $sql .= ")";
            }

            if ($arg instanceof plantillaemail) {
                $sql .= "
                    AND (
                        pc.uid_empresa_contacto IN (
                            SELECT c.uid_contacto FROM ". TABLE_CONTACTO_PLANTILLAEMAIL ." c
                            WHERE c.uid_contacto = pc.uid_empresa_contacto
                            AND c.uid_plantillaemail = ". $arg->getUID() ."
                        )
                    ";

                if (!in_array($arg->getUID(), plantillaemail::$templatesToAvoid)) {
                    $sql .=  " OR principal = 1 ";
                }

                $sql .= ")";
            }

            if (is_string($arg)) {
                $search = db::scape($arg);
                $fields = ['nombre', 'apellidos', 'email'];
                $where = [];
                foreach ($fields as $field) {
                        $where[] = $field . " LIKE '%{$search}%'";
                }

                $filter = implode(" OR ", $where);
                $sql    .= " AND ($filter)";
            }

        }


        $sql .= " ORDER BY principal DESC, nombre ASC";

        $items = $this->db->query($sql, "*", 0, "contactoempresa");
        return new ArrayObjectList($items);
    }

    public function obtenerContactoPrincipal(){
        $sql = "SELECT uid_empresa_contacto FROM ". $this->tabla ."_contacto WHERE uid_empresa = $this->uid AND principal = 1";
        $uid = $this->db->query($sql, 0, 0);
        if( is_numeric($uid) ){
            return new contactoempresa($uid);
        } else {
            $sql = "SELECT uid_empresa_contacto FROM ". $this->tabla ."_contacto WHERE uid_empresa = $this->uid LIMIT 1";
            $uid = $this->db->query($sql, 0, 0);
            if( is_numeric($uid) ){
                return new contactoempresa($uid);
            }
            return false;
        }
    }

    public function getLineClass($parent, $usuario, $data = NULL){
        $class = false;

        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

        switch($context){
            case Ilistable::DATA_CONTEXT_DESCARGABLES:
                return $class;
            break;
            default:
                $perfil = $usuario instanceof Iusuario ? $usuario->obtenerPerfil() : null;
                $cacheKey = implode('-', array(__CLASS__, __FUNCTION__, $context, $this, $parent, $perfil));
                if (($value = $this->cache->getData($cacheKey)) !== NULL) return $value;


                if( $parent && $usuario instanceof usuario ){
                    // ---- Informacion de documentos, filtrado por usuario, documentos de subida y obligatorios
                    $informacionDocumentos = $this->obtenerEstadoDocumentos($usuario, 0, true);

                    $estadoDocumentos = ( count($informacionDocumentos) == 1 && isset($informacionDocumentos[2]) ) ? 'green':'red';

                    //$datosEmpresaInferior['estado'] = $empresaInferior->getApta() == '1' ? 'apta'.$estadoDocumentos : 'noapta';
                    $userCompany = $usuario->getcompany();
                    $class = $userCompany->isSuitableItem($this)?'color '.$estadoDocumentos:'color black';
                };

                //$this->cache->addData($cacheKey, $class);
                return $class;
            break;
        }
    }

    public function isOk($parent, $usuario, $data = NULL){
        $class = false;

        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

        $perfil = $usuario instanceof Iusuario ? $usuario->obtenerPerfil() : null;
        $cacheKey = implode('-', array(__CLASS__, __FUNCTION__, $context, $this, $parent, $perfil));
        if (($value = $this->cache->getData($cacheKey)) !== NULL) return $value;


        if( $parent && $usuario instanceof usuario ){
            // ---- Informacion de documentos, filtrado por usuario, documentos de subida y obligatorios
            $informacionDocumentos = $this->obtenerEstadoDocumentos($usuario, 0, true);

            $class = ( count($informacionDocumentos) == 1 && isset($informacionDocumentos[2]) ) ? true : false;
        };

        //$this->cache->addData($cacheKey, $class);
        return $class;
    }

    /***
        @Override from basic
        - getInfo( $publicMode = false, $comeFrom = null, $usuario=false, $parent = false){
    */
    public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = NULL, $extra = array(), $force = false){
        $parent = isset($extra[Ielemento::EXTRADATA_PARENT]) ? $extra[Ielemento::EXTRADATA_PARENT] : false;

        $info = parent::getInfo($publicMode, $comeFrom, $usuario);

        if( $publicMode ){
            $datosEmpresa =& $info[ $this->getUID() ];

            if( $comeFrom == "table" || $comeFrom == "folder" ){
                $datosEmpresa["nombre"] = array(
                    "class" => "box-it",
                    "href" => "ficha.php?m=empresa&oid=". $this->uid,
                    "title" => $datosEmpresa["nombre"] . " - CIF: ". $datosEmpresa["cif"],
                    "innerHTML" => string_truncate($datosEmpresa["nombre"], 60)
                );
                unset($datosEmpresa["cif"]);
            }

            if( $comeFrom == "ficha" && $this->esCorporacion()){
                unset($datosEmpresa["cif"]);
            }
        }

        $info["className"] = $this->getLineClass($parent, $usuario);

        return $info;
    }


    public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $data = array()){
        $info = parent::getInfo(false);
        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

        switch($context){
            case Ilistable::DATA_CONTEXT_DESCARGABLES:
                $data = array("nombre" => string_truncate($info["nombre"], 60)
                    );

                $tableInfo = array( $this->uid => $data );
                return $tableInfo;
            break;

            default:
                $tpl = Plantilla::singleton();
                $data = array();
                $userCompany = $usuario->getCompany();

                $innerHTML = string_truncate($info["nombre"], 60);
                $title = $info["nombre"] . " - CIF: ". $info["cif"];

                if (count($userCompany->getUnsuitableItemClient($this))) {
                    $title .= ". ".$tpl->getString('title_warning');
                    $innerHTML .= "<img src=\"".RESOURCES_DOMAIN ."/img/famfam/bell_error.png\"/>";
                }

                $data["nombre"] = array(
                    "class" => "box-it add-icon",
                    "href" => "ficha.php?m=empresa&oid={$this->uid}",
                    "title" => $title,
                    "innerHTML" => $innerHTML
                );

                if ($usuario->esStaff() && !$usuario->getCompany()->compareTo($this)) {
                    $data["nombre"]["icon"] = array(
                        "class" => "clickable changeprofile",
                        "rel" => "company",
                        "to" => $this->getUID(),
                        "src" => RESOURCES_DOMAIN ."/img/famfam/user_go.png",
                        "title" => "Saltar a esta empresa"
                    );
                }


                $tableInfo = array( $this->uid => $data );

                return $tableInfo;
            break;
        }
    }

    public function obtenerAccionesRelacion(agrupamiento $agrupamiento, Iusuario $usuario){
        $tpl = Plantilla::singleton();
        $acciones = array();

            if( ($usuario->getCompany()->getUID() != $this->getUID()) || ($usuario->getCompany()->getUID() == $this->getUID()) ){
                $acciones[] = array(
                    "innerHTML" => $tpl->getString("configurar_aspectos_relacion"),
                    "className" => "box-it",
                    "img" => RESOURCES_DOMAIN . "/img/famfam/cog_edit.png",
                    "href" => "asignacion.php?m=empresa&poid=". $this->getUID()."&oid=%s&o=". $agrupamiento->getUID()
                );
            }


            $options = $usuario->getAvailableOptionsForModule($this->getModuleId(), 1);
            if( $op = reset($options) ){
                $acciones[] = array(
                    "innerHTML" => $tpl->getString("buscar_empleados_asignacion"),
                    "className" => "",
                    "img" => $op["icono"],
                    "href" => "#buscar.php?p=0&q=asignado:%s tipo:empleado empresa:".$this->getUID()
                );
            }

            $options = $usuario->getAvailableOptionsForModule($this->getModuleId(), 24);
            if( $op = reset($options) ){
                $acciones[] = array(
                    "innerHTML" => $tpl->getString("buscar_maquinaria_asignacion"),
                    "className" => "",
                    "img" => $op["icono"],
                    "href" => "#buscar.php?p=0&q=asignado:%s tipo:maquina empresa:".$this->getUID()
                );
            }

            $options = $usuario->getAvailableOptionsForModule($this->getModuleId(), "documentos");
            if( $op = reset($options) ){
                $acciones[] = array(
                    "innerHTML" => $tpl->getString("documentacion_relativa_elemento"),
                    "className" => "",
                    "img" => $op["icono"],
                    "href" => $op["href"] . "&relsource=%s&poid=".$this->getUID()
                );
            }
/*
            if( $op = reset($usuario->getAvailableOptionsForModule($this->getModuleId(), "bloquear")) ){
                $acciones[] = array(
                    "innerHTML" => $tpl->getString("bloquear_asignacion"),
                    "className" => $op["class"],
                    "img" => $op["icono"],
                    "href" => $op["href"] . "&poid=".$this->getUID()
                );
            }
        */
        return $acciones;
    }

    /** OBTENER EL CIF DE ESTA EMPRESA **/
    public function getCIF(){
        $info = $this->getInfo();
        return $info["cif"];
    }

    /** OBTENER EL NOMBRE VISIBLE DEL ELEMENTO, EN ESTE CASO SIMPLEMENTE EL NOMBRE */
    public function getUserVisibleName(){
        $info = $this->getInfo();

        if (isset($info["nombre"])) {
            return $info["nombre"];
        }

        error_log("no name for company {$this->getUID()}");
        return "";
    }

    public function getDateEvents($start, $end){
        $startDate = date("Y-m-d", $start);
        $endDate = date("Y-m-d", $end);

        $sql = "SELECT uid_evento FROM ". TABLE_EVENTOS ." WHERE uid_empresa = " . $this->getUID() ."
                AND fecha > '$startDate' AND fecha < '$endDate' ";

        $list = $this->db->query($sql, "*", 0);

        $coleccion = array();
        foreach($list as $uid){
            $coleccion[] = new eventdate($uid, "empresa");
        }
        return $coleccion;
    }

    /** NOS RETORNARA UN ARRAY DE DATOS ORDENADO POR FECHAS DE LOS EVENTOS QUE AFECTAN A ESTA EMPRESA
        COMO CADUCIDAD DE ELEMENTOS, ARCHIVOS ANEXADOS, ANULADOS **/
    public function obtenerEventos(Iusuario $usuario, $start, $end, $filter = false, $timeZoneName = 'Europe/Berlin') {

        // --- instanciamos una plantilla para trabajar con cadenas de texto
        $tpl = Plantilla::singleton();

        // --- Save all events
        $datosEventos = array();

        // --- kind of events
        $kinds = isset($filter['kind']) ? $filter['kind'] : array();

        if (count($kinds)) {
            $fields = $statuses = array();

            if (in_array('attached', $kinds)) $fields[] = 'fecha_anexion';
            if (in_array('expired', $kinds)) $fields[] = 'fecha_expiracion';

            if (in_array('validated', $kinds)) $statuses[] = documento::ESTADO_VALIDADO;
            if (in_array('rejected', $kinds)) $statuses[] = documento::ESTADO_ANULADO;
        } else {
            $fields = array('fecha_anexion', 'fecha_expiracion');
            $statuses = array(documento::ESTADO_VALIDADO, documento::ESTADO_ANULADO);
        }


        // --- event srcs
        $srcs = isset($filter['src']) ? $filter['src'] : array();

        // --- where
        $filterSQL = ($usuario instanceof Iusuario && $condicion = $usuario->obtenerCondicionDocumentosView('empresa')) ? $condicion : '';


        // --- child items
        $modules = childItemEmpresa::getModules();


        // --- doc status with expired events
        $expiredStatus = array(documento::ESTADO_ANEXADO, documento::ESTADO_VALIDADO, documento::ESTADO_CADUCADO);


        // --- where to start
        $startList = $this->getStartIntList();

        // allow big group concats
        $this->db->query("SET @@group_concat_max_len = 1000000");


        // --- Eventos registrados manualmente
        $showManuals = (!count($srcs) && !count($kinds)) || in_array('manual', $srcs);
        if ($showManuals) {
            $dateEvents = $this->getDateEvents($start, $end);

            foreach ($dateEvents as $event) {
                /*
                if (count($event->obtenerAlarmas())) {
                    $opciones[] = array(
                        "title" => "alarma",
                        "href" => "alarma/alarma.php?poid=".$event->getUID()."&m=eventdate",
                        "img" => RESOURCES_DOMAIN."/img/famfam/bell.png"
                    );
                }
                */

                $datosEventos[] = array(
                    'title'         => $event->obtenerDato("descripcion"),
                    'date'          => $event->getTime(),
                    'origin'        => $event,
                    'description'   => "",
                    'href'          => "empresa/eventos.php?poid=". $this->getUID() ."&oid=" . $event->getUID() ."&m=empresa",
                    'editable'      => true,
                    'type'          => 'manual',
                    'items'         => array(array(
                        'date'      => $event->getDate(),
                        'instance'  => $this
                    ))
                );
            }
        }


        $assign = isset($filter['assign']) ? $filter['assign'] : false;


        $showCompany = !count($srcs) || in_array('empresa', $srcs);
        if ($showCompany) {

            foreach($fields as $field) {
                $dateFilter = "($field BETWEEN $start AND $end)";

                // --- no mostrar eventos de caducidad para documentos anulados
                if ($field == 'fecha_expiracion') {
                    $dateFilter .= " AND view.estado IN (". implode(',', $expiredStatus) .")";

                    $status = documento::ESTADO_CADUCADO;
                    $type = 'expired';
                } else {
                    $status = documento::ESTADO_ANEXADO;
                    $type = 'attached';
                }


                $sql = "
                    SELECT
                        uid_anexo_empresa, uid_documento_atributo, uid_documento,
                        uid_solicituddocumento, {$field} as date,
                        GROUP_CONCAT(uid_solicituddocumento) as list, COUNT(uid_solicituddocumento) num
                    FROM ". TABLE_DOCUMENTO ."_empresa_estado view
                    INNER JOIN ". PREFIJO_ANEXOS ."empresa anexo
                    USING (uid_anexo_empresa, uid_empresa_referencia, uid_agrupador, uid_empresa, uid_documento_atributo)
                    WHERE anexo.uid_empresa IN ($startList)
                    AND descargar = 0
                    AND {$dateFilter}
                    {$filterSQL}
                    GROUP BY uid_documento,  DATE_FORMAT(FROM_UNIXTIME({$field}), '%Y-%m-%d %h:%i')
                ";

                $anexos = $this->db->query($sql, true);
                if ($anexos && count($anexos)) {
                    foreach ($anexos as $rawAnexo) {
                        $anexo = new anexo($rawAnexo['uid_anexo_empresa'], 'empresa');

                        $list = $rawAnexo['list'];
                        $num = $rawAnexo['num'];
                        $date = $rawAnexo['date'];
                        $doc = new documento($rawAnexo['uid_documento']);

                        if ($num == 1) {
                            $attr = new documento_atributo($rawAnexo['uid_documento_atributo']);
                            $title = $attr->getUserVisibleName();
                        } else {
                            $title = $doc->getUserVisibleName();
                        }


                        $event = array(
                            'title'         => $title,
                            'description'   => "",
                            'origin'        => $doc,
                            'href'          => "#buscar.php?p=0&q=tipo:anexo-empresa#{$list}",
                            'allDay'        => false,
                            'editable'      => false,
                            'className'     => 'stat stat_' . $status,
                            'type'          => $type,
                            'date'          => $date,
                            'items'         => array(array(
                                'date'      => $date,
                                'instance'  => $this
                            ))
                        );


                        $datosEventos[] = $event;

                    }
                }
            }


            $anexoModule = util::getModuleId('anexo_empresa');
            $dateFilter = "(UNIX_TIMESTAMP(log.fecha) BETWEEN $start AND $end)";

            foreach ($statuses as $status) {
                $sql = "
                    SELECT anexo.uid_anexo_empresa, uid_solicituddocumento, valor,
                    UNIX_TIMESTAMP(fecha) as fecha, anexo.uid_documento_atributo, uid_documento,
                    GROUP_CONCAT(uid_solicituddocumento) as list, COUNT(uid_solicituddocumento) num
                    FROM ". TABLE_DOCUMENTO ."_empresa_estado view
                    INNER JOIN ". PREFIJO_ANEXOS ."empresa anexo
                    ON anexo.uid_anexo_empresa = view.uid_anexo_empresa
                    AND anexo.uid_empresa_referencia = view.uid_empresa_referencia
                    AND anexo.uid_agrupador = view.uid_agrupador
                    AND anexo.uid_empresa = view.uid_empresa
                    AND anexo.uid_documento_atributo = view.uid_documento_atributo
                    INNER JOIN ". TABLE_LOGUI ." log
                    ON log.uid_modulo = {$anexoModule}
                    AND uid_elemento = anexo.uid_anexo_empresa
                    WHERE anexo.uid_empresa IN ($startList)
                    AND descargar = 0
                    AND texto = 'cambiar_estado'
                    AND valor = {$status}
                    AND {$dateFilter}
                    GROUP BY view.uid_documento, DATE_FORMAT(fecha, '%Y-%m-%d %h:%i')
                ";

                $validations = $this->db->query($sql, true);
                if ($validations) foreach ($validations as $rawValidation) {
                    $anexo = new anexo($rawValidation['uid_anexo_empresa'], 'empresa');
                    $status = $rawValidation['valor'];
                    $date = $rawValidation['fecha'];
                    $list = $rawValidation['list'];
                    $num = $rawValidation['num'];
                    $doc = new documento($rawValidation['uid_documento']);

                    if ($num == 1) {
                        $attr = new documento_atributo($rawValidation['uid_documento_atributo']);
                        $title = $attr->getUserVisibleName();
                    } else {
                        $title = $doc->getUserVisibleName();
                    }

                    $className = 'stat stat_' . $status;
                    $type = ($status == 2) ? 'validated' : 'rejected';

                    $event = array(
                        'title'         => $title,
                        'date'          => $date,
                        'origin'        => $doc,
                        'className'     => $className,
                        'description'   => '',
                        'href'          => "#buscar.php?p=0&q=tipo:anexo-empresa#{$list}",
                        'allDay'        => false,
                        'type'          => $type,
                        'editable'      => false,
                        'items'         => array(array(
                            'date'      => $date,
                            'instance'  => $this
                        ))
                    );

                    $datosEventos[] = $event;
                }
            }
        }



        foreach ($modules as $module) {
            $showThisKind = !count($srcs) || in_array($module, $srcs);
            if (!$showThisKind) continue;

            $uidModule = util::getModuleId($module);
            $filterSQL = ($usuario instanceof Iusuario && $condicion = $usuario->obtenerCondicionDocumentosView($module)) ? $condicion : '';

            $itemTable = constant('TABLE_'.strtoupper($module));

            $assignFilter = "1";
            if ($assign instanceof agrupador) {
                $assignFilter = "uid_{$module} IN (
                    SELECT uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento
                    WHERE uid_modulo = {$uidModule}
                    AND uid_elemento = view.uid_{$module}
                    AND uid_agrupador = {$assign->getUID()}
                )";
            }

            $userAsDomainEntity = $legacyUser = null;

            if ($usuario instanceof usuario) {
                $legacyUser = $usuario;
            }

            if ($usuario instanceof perfil) {
                $legacyUser = $usuario->getUser();
            }

            if ($legacyUser instanceof usuario) {
                $userAsDomainEntity = $legacyUser->asDomainEntity();
            }

            $indexList = (string) $this->app['index.repository']->getIndexOf(
                $module,
                $this->asDomainEntity(),
                $userAsDomainEntity,
                true
            );

            foreach($fields as $field) {
                $dateFilter = "($field BETWEEN $start AND $end)";
                $ofString = $tpl('de') .' '. $tpl($module.'s');

                // --- no mostrar eventos de caducidad para documentos anulados
                if ($field == 'fecha_expiracion') {
                    $dateFilter .= " AND view.estado IN (". implode(',', $expiredStatus) .")";
                }


                $sql = "
                    SELECT
                        uid_anexo_{$module},
                        count(uid_anexo_{$module}) as num,
                        GROUP_CONCAT(uid_solicituddocumento) as list,
                        GROUP_CONCAT(uid_{$module}) as uids,
                        GROUP_CONCAT({$field}) as date,
                        view.uid_documento,
                        view.uid_documento_atributo
                    FROM ". TABLE_DOCUMENTO ."_{$module}_estado view
                    INNER JOIN ". PREFIJO_ANEXOS ."{$module} anexo
                    USING (uid_anexo_{$module}, uid_empresa_referencia, uid_agrupador, uid_{$module}, uid_documento_atributo)
                    INNER JOIN {$itemTable}_empresa USING (uid_{$module})
                    WHERE uid_empresa IN ($startList)
                    AND papelera = 0
                    AND descargar = 0
                    AND {$dateFilter}
                    AND {$assignFilter}
                    AND uid_{$module} IN ({$indexList})
                    $filterSQL
                    GROUP BY uid_documento, DATE_FORMAT(FROM_UNIXTIME({$field}), '%Y-%m-%d')";

                $anexos = $this->db->query($sql, true);
                if ($anexos && count($anexos)) foreach ($anexos as $rawAnexo) {
                    $list = $rawAnexo['list'];
                    $uids = explode(',', $rawAnexo['uids']);
                    $uid = $rawAnexo['uid_anexo_'.$module];
                    $num = $rawAnexo['num'];
                    $doc = new documento($rawAnexo['uid_documento']);

                    if ($num == 1) {
                        $attr = new documento_atributo($rawAnexo['uid_documento_atributo']);
                        $title = $attr->getUserVisibleName();
                    } else {
                        $title = $doc->getUserVisibleName();
                    }

                    $dates = explode(',', $rawAnexo['date']);
                    $date = $dates[0];

                    $items = [];
                    foreach ($uids as $i => $uid) {
                        $items[] = array(
                            'date' => $dates[$i],
                            'instance' => new $module($uid)
                        );
                    }


                    $event = array(
                        'title'         => $title,
                        'origin'        => $doc,
                        'date'          => $date,
                        'description'   => '',
                        'href'          => "#buscar.php?p=0&q=tipo:anexo-{$module}#{$list}",
                        'allDay'        => ($num != 1),
                        'editable'      => false,
                        'items'         => $items
                    );

                    switch ($field) {
                        case 'fecha_anexion':
                            $status = documento::ESTADO_ANEXADO;
                            $type = 'attached';
                            break;

                        case 'fecha_expiracion':
                            $status = documento::ESTADO_CADUCADO;
                            $type = 'expired';
                            break;
                    }

                    $className = 'stat stat_' . $status;
                    $event['className'] = $className;
                    $event['type'] = $type;

                    $datosEventos[] = $event;
                }
            }


            $anexoModule = util::getModuleId('anexo_'. $module);
            $dateFilter = "(UNIX_TIMESTAMP(log.fecha) BETWEEN $start AND $end)";

            foreach ($statuses as $status) {
                $sql = "
                    SELECT uid_anexo_{$module}, valor,
                        COUNT(uid_solicituddocumento) as num,
                        GROUP_CONCAT(uid_solicituddocumento) as list,
                        GROUP_CONCAT(uid_{$module}) as uids,
                        GROUP_CONCAT(UNIX_TIMESTAMP(fecha)) as fecha,
                        view.uid_documento
                    FROM ". TABLE_DOCUMENTO ."_{$module}_estado view
                    INNER JOIN ". PREFIJO_ANEXOS ."{$module} anexo
                    USING (uid_anexo_{$module}, uid_empresa_referencia, uid_agrupador, uid_{$module}, uid_documento_atributo)
                    INNER JOIN {$itemTable}_empresa USING (uid_{$module})
                    INNER JOIN ". TABLE_LOGUI ." log
                    ON log.uid_modulo = {$anexoModule} AND uid_elemento = uid_anexo_{$module}
                    WHERE {$itemTable}_empresa.uid_empresa IN ($startList)
                    AND papelera = 0
                    AND descargar = 0
                    AND texto = 'cambiar_estado'
                    AND valor = {$status}
                    AND {$dateFilter}
                    AND {$assignFilter}
                    AND uid_{$module} IN ({$indexList})
                    {$filterSQL}
                    GROUP BY uid_documento, DATE_FORMAT(fecha, '%Y-%m-%d')
                ";

                $validations = $this->db->query($sql, true);
                if ($validations) foreach ($validations as $rawValidation) {
                    $list = $rawValidation['list'];
                    $uids = explode(',', $rawValidation['uids']);
                    $num = $rawValidation['num'];


                    $status = $rawValidation['valor'];
                    $anexo = new anexo($rawValidation['uid_anexo_'.$module], $module);
                    $doc = new documento($rawValidation['uid_documento']);

                    $dates = explode(',', $rawValidation['fecha']);
                    $date = $dates[0];


                    $className = 'stat stat_' . $status;
                    $type = ($status == 2) ? 'validated' : 'rejected';

                    $title = $doc->getUserVisibleName();

                    $items = [];
                    foreach ($uids as $i => $uid) {
                        $items[] = array(
                            'date'      => $dates[$i],
                            'instance'  => new $module($uid)
                        );
                    }

                    $event = array(
                        'title'         => $title,
                        'date'          => $date,
                        'origin'        => $doc,
                        'className'     => $className,
                        'type'          => $type,
                        'description'   => '',
                        'href'          => "#buscar.php?p=0&q=tipo:anexo-{$module}#{$list}",
                        'allDay'        => ($num != 1),
                        'editable'      => false,
                        'items'         => $items
                    );

                    $datosEventos[] = $event;
                }
            }
        }

        // rever group concat to default
        $this->db->query("SET @@group_concat_max_len = 1024");

        foreach ($datosEventos as &$event) {
            $timezoneOffset = 0;

            if (null !== $timeZoneName && '' !== $timeZoneName) {
                $dateTimeZone = new \DateTimeZone($timeZoneName);
                $dateTime = new \DateTime("@".$event['date']);
                $timezoneOffset = $dateTimeZone->getOffset($dateTime);
            }

            $event['utc'] = $event['date'];
            $event['date'] = $event['date'] + $timezoneOffset;
            foreach ($event['items'] as &$item) {
                $item['utc'] = $item['date'];
                $item['date'] = $item['date'] + $timezoneOffset;
            }
        }
        return $datosEventos;
    }


    /***
       * return bool
       *
       * @param empresa Object empresa | Object ArrayObjectList
       *
       */
    public function esContrata ($empresa, $trash = false) {
        if ($this->compareTo($empresa)) {
            return false;
        }

        $comaList = $empresa instanceof empresa ? $empresa->getUID() : $empresa->toComaList();
        $cacheKey = implode('-', [$this, __FUNCTION__, $comaList, $trash]);

        if (null !== ($bool = $this->cache->getData($cacheKey))) {
            return $bool;
        }

        $sql = "SELECT uid_empresa_inferior FROM ". TABLE_EMPRESA ."_relacion
                WHERE uid_empresa_superior IN ({$comaList})
                AND uid_empresa_inferior = {$this->getUID()}
        ";

        if (is_bool($trash)) {
            $sql .= " AND papelera = " . (int) $trash;
        }

        $uid = $this->db->query($sql, 0, 0);
        $isContract = (bool) is_numeric($uid);

        $this->cache->set($cacheKey, $isContract);
        return $isContract;
    }

    public function esAptaPara($empresaSuperior){
        // ALTER TABLE `empresa_relacion` ADD `apta` TINYINT( 1 ) NOT NULL AFTER `uid_empresa_inferior`;
        // por si acaso: una empresa siempre es apta para si misma
        // TODO añadir otras comprobaciones? existe $empresaSuperior, por ejemplo

        $uidSuperior = $empresaSuperior;
        if( $empresaSuperior instanceof empresa ){
            $uidSuperior = $empresaSuperior->getUID();
        }

        if( !$this->esContrata($empresaSuperior) && $this->getUID() != $empresaSuperior->getUID() ){
            $empresas = $this->obtenerEmpresasSuperiores();
            $uidSuperior = reset( elemento::getCollectionIds($empresas) );
        }


        //mirar elemento->obtenerRelacionados();
        if( $this->getUID() == $uidSuperior ) return true;

        $campos = array('apta');

        $sql = ' SELECT '. implode(',', $campos) .'
        FROM '. TABLE_EMPRESA .'_relacion'.'
        WHERE uid_empresa_superior = '. $uidSuperior .'
        AND uid_empresa_inferior = '. $this->getUID();
        //dump("-- esAptaPara --- " . $sql);
        // ejecutamos la query pidiendo el primer (y único) campo de la primera fila
        return $this->db->query($sql, 0, 0)==1?true:false;
    }


    public function aptitudDe($empresaInferior){
        $uidInferior = $empresaInferior;
        if( $empresaInferior instanceof empresa ){
            $uidInferior = $empresaInferior->getUID();
        }

        if( !$this->esContrata( new empresa($uidInferior) ) ){
            $empresas = $this->obtenerEmpresasSuperiores();
            $uidInferior = reset( $empresas )->getUID();
        }

        $campos = array('apta');

        $sql = ' SELECT '. implode(',', $campos) .'
        FROM '. TABLE_EMPRESA .'_relacion'.'
        WHERE uid_empresa_superior = '. $this->getUID() .'
        AND uid_empresa_inferior = '. $uidInferior;
        //dump("-- aptitudDe -- " . $sql);
        // ejecutamos la query pidiendo el primer (y único) campo de la primera fila
        return $this->db->query($sql, 0, 0)==1?true:false;

    }

    public function guardarAptitud(empresa $inferior, $apta = false){
        //$uidInferior = ( is_numeric($inferior) ) ? $inferior : db::scape($_REQUEST['uid_empresa_inferior']);
        //$uidSuperior = $this->getUID();

        //$empresaInferior = new empresa($uidInferior);

        $superior = $this;
        if( !$inferior->esContrata($this) ){
            $empresas = $inferior->obtenerEmpresasSuperiores();
            $superior = reset($empresas);
        }


        //$apta = db::scape($_REQUEST['apta']);
        $apta = (int) $apta;

        $sql = ' UPDATE '. $this->tabla .'_relacion SET apta = '. $apta .'
        WHERE uid_empresa_superior = '. $superior->getUID() .'
        AND uid_empresa_inferior = '. $inferior->getUID();

        //dump("-- guardarAptitud -- " . $sql);
        $this->db->query($sql);
        return $this->db->getAffectedRows()==1?true:false;
    }

    /**
      * iface/Ilistable.iface.php
      *
      */
    public function getClickURL(Iusuario $usuario = NULL, $config = false, $data = NULL){
        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;

        if( $context === Ilistable::DATA_CONTEXT_LIST_EMPLEADO ){
            return '#empleado/listado.php?poid=' . $this->getUID();
        }

        if( $context === Ilistable::DATA_CONTEXT_LIST_MAQUINA ){
            return '#maquina/listado.php?poid=' . $this->getUID();
        }

        return false;
    }

    /**
      * iface/Ilistable.iface.php
      *
      */
    public function getInlineArray(Iusuario $usuario = NULL, $config = false, $data = NULL){
        $dataString = is_array($data) ? json_encode($data) : false;
        $cacheKey = implode('-', array($this, __FUNCTION__, $usuario->obtenerPerfil(), $config, $dataString));
        if (($value = $this->cache->get($cacheKey)) !== NULL) return json_decode($value, true);

        $inlineArray = array();
        $comefrom = isset($data[Ilistable::DATA_COMEFROM]) ? $data[Ilistable::DATA_COMEFROM] : false;
        $context = isset($data[Ilistable::DATA_CONTEXT]) ? $data[Ilistable::DATA_CONTEXT] : false;
        $search = isset($data[Ilistable::DATA_SEARCH]) ? $data[Ilistable::DATA_SEARCH] : false;
        $staff = (bool) $usuario->esStaff();
        $lang = Plantilla::singleton();

        switch($context){
            case Ilistable::DATA_CONTEXT_LIST_EMPLEADO:
                $num = $this->obtenerEmpleados(false, false, $usuario, true);
                $inlineArray[] = array(
                    "img" => RESOURCES_DOMAIN . '/img/famfam/group_go.png',
                    array( "nombre" => $num . " " . $lang('empleado')  )
                );
                return $inlineArray;
            break;
            case Ilistable::DATA_CONTEXT_LIST_MAQUINA:
                $num = $this->obtenerIdMaquinas(false, false, $usuario, true);
                $inlineArray[] = array(
                    "img" => RESOURCES_DOMAIN . '/img/famfam/group_go.png',
                    array( "nombre" => $num . " " . $lang('maquina')  )
                );
                return $inlineArray;
            break;
            case Ilistable::DATA_CONTEXT_DESCARGABLES:
                $inlineArray[] = array();
                return $inlineArray;
            break;
            default:

                $searchingSince = $search && strstr(urldecode($search), "since:");

                // Si nos encontramos con una empresa corporación, no nos intenresan la mayor parte de los datos
                if ($this->esCorporacion()) {
                    $inlineArray[] = array(
                        "img" => RESOURCES_DOMAIN . "/img/famfam/arrow_divide.png",
                        array( "nombre" => "CORP" )
                    );
                    return $inlineArray;
                }

                //------------- INFORMACIÓN RÁPIDA DE LOS DOCUMENTOS
                if ($usuario instanceof usuario && !$comefrom) {
                    $docs = parent::getDocsInline($usuario);
                    $docs["img"]["className"] = "extended-cell clickable";
                    $docs["img"]["title"] = $lang("resumen_cumplimentacion");
                    $docs["href"] = "empresa/resumendocumentos.php?oid=" . $this->getUID();
                    $inlineArray[] = $docs;
                }

                if (!$comefrom && !$searchingSince && $usuario instanceof usuario) {
                    $clients = array( 'img' =>  RESOURCES_DOMAIN . "/img/famfam/sitemap_color_inverse.png" );
                    $includeTrash = $staff ? null : false;
                    $empresasSuperiores = $this->obtenerEmpresasSuperiores($includeTrash, $usuario);

                    if (count($empresasSuperiores)) {
                        foreach( $empresasSuperiores as $i => $empresaSuperior ){
                            if( $i > 1 ){
                                $rest = count($empresasSuperiores)-2;
                                $clients[] = array("nombre" => "+ $rest");
                                break;
                            } else {

                                if ($usuario->esStaff() || $usuario->accesoElemento($empresaSuperior)) {
                                    $className = "box-it";
                                    $tagName = "a";
                                } else {
                                    $className = "";
                                    $tagName = "span";
                                }

                                $title = $empresaSuperior->getUserVisibleName();

                                if ($staff && $this->inTrash($empresaSuperior)) {
                                    $className .= " light";
                                    $title .= " - {$this->getUserVisibleName()} está en su papelera";
                                }

                                $clients[] = array(
                                    "tagName" => $tagName,
                                    "title" => $title,
                                    "className" => $className,
                                    "nombre" => $empresaSuperior->getUserVisibleName(),
                                    "oid" => $empresaSuperior->getUID(),
                                    "tipo" => "empresa",
                                );
                            }
                        }

                        $clients["className"] = "extended-cell solicitantes-cell";
                        $clients["href"] = "listarclientes.php?poid={$this->getUID()}&inline=1";
                    }

                    $inlineArray[] = $clients;
                }

                // Si encontramos este elemento procedente de una búsqueda, es posible que añadamos mas datos
                if ($search && strpos($search,"in:old") !== false) {
                    $tpl = Plantilla::singleton();

                    $inlineArray[] = array(
                        "img" => RESOURCES_DOMAIN . "/img/famfam/clock.png",
                        "title" => $tpl("fecha_ultimo_acceso"),
                        array( "nombre" => $this->lastAccessDate() )
                    );
                }



                // Buscamos aquellos grupos donde se deba mostrar el estado de la documentacion
                if ($usuario instanceof usuario && !$comefrom) {
                    // Iconos de los agrupadores asignados
                    if ($icons = $this->getInlineIcons($usuario)) $inlineArray[] = $icons;
                }




                if ($searchingSince) {
                    $tpl = Plantilla::singleton();

                    $inlineArray[] = array(
                        "img" => RESOURCES_DOMAIN . "/img/famfam/clock.png",
                        "title" => $tpl("fecha_primer_acceso"),
                        array("nombre" => date('Y-m-d', $this->getFirstLoginTimestamp()))
                    );
                }


                if ($usuario instanceof usuario && $usuario->configValue("economicos") == 1) {
                    $pago = array();

                    $class = "";
                    $title = "No ha pagado aún";
                    $name = "Free";

                    if ($this->isEnterprise()||($corp=$this->perteneceCorporacion())) {
                        $name = $title = "Enterprise";
                        if (@$corp) {
                            $name .= " (C)";
                            $title .= " - Pertence a la corporación {$corp->getUserVisibleName()}";
                        }
                    } elseif($this->isTemporary()) {
                        $name = $title = "Premium Temporal";

                    } elseif($this->isPremium() && !$this->hasTemporaryPayment()) {
                        $name = $title = "Premium";

                        if ($this->mustPay()) {
                            $name .= " (E)";
                            $class = "red";
                            $title .= " - Licencia expirada";
                        }
                    } else {
                        if ($this->mustPay() && !$this->hasTemporaryPayment()) {
                            $name = "Free";
                            $title .= " - Pago obligatorio";
                            $class = "red";
                        } else {
                            $title .= " - Opcional";
                            $name = "Free (OP)";
                        }
                    }


                    $force = (bool) $this->obtenerDato("pago_no_obligatorio");
                    if ($force) {
                        $name .= " (F)";
                        $class = "red";
                        $title .= " - Forzado";
                    }

                    $class .= " box-it";
                    $pago["title"] = $title;
                    $pago["img"] = RESOURCES_DOMAIN . "/img/famfam/money.png";
                    $pago[] = array( "nombre" => $name, "className" => $class, "href" => "../paypal/info.php?poid={$this->getUID()}" );

                    $inlineArray[] = $pago;
                }
            break;
        }

        $this->cache->set($cacheKey, json_encode($inlineArray), 60*60*15);
        return $inlineArray;
    }


    public static function getExportSQL($usuario, $uids, $forced, $parent=false){

        $campos = array();
        if( $usuario->esStaff() ){
            $campos[] = "uid_empresa";
        }

        $campos[] = "nombre";
        $campos[] = "cif";

        $sql = "SELECT ". implode(",", $campos) ." FROM ". TABLE_EMPRESA ." WHERE 1";

        if( is_array($uids) && count($uids) ){
            $sql .=" AND uid_empresa in (". implode(",", $uids ) .")";
        }

        $sql .=" AND uid_empresa IN (". implode(",", $forced) .")";

        if( is_numeric($parent) ){
            $parent = new empresa($parent);
            $coleccion = $parent->obtenerEmpresasInferioresMasActual(false, false, $usuario, empresa::DEFAULT_DISTANCIA);

            $list = elemento::getCollectionIds($coleccion);
            if( count($list) ){
                $sql .= "AND uid_empresa IN (". implode(",", $list) .")";
            } else {
                $sql .= "AND uid_empresa IN (0)";
            }
        }

        return $sql;
    }

    public function lastAccessDate(){
        $sql = "SELECT DATE_FORMAT(accion, '%d-%m-%Y') as date FROM $this->tabla WHERE uid_empresa = $this->uid";
        $date = $this->db->query($sql, 0, 0);
        return $date;
    }


    public static function level2String( $level ){
        if( $level === null ) return "??";

        $lang = Plantilla::singleton();
        switch( $level ){
            //case null: return "??"; break;
            default: return $lang->getString("subcontrata") . " ". ($level-2) ; break;
            case 0: return $lang->getString("principal"); break;
            case 1: return $lang->getString("contrata"); break;
            case 2: return $lang->getString("subcontrata"); break;
        }
    }

    static public function obtenerEmpresasConflictivas($usuario, $numero=3){
        // Objeto database
        $db = db::singleton();
        $intList = buscador::getCompaniesIntList($usuario);

        if( !$intList || !count($intList) ) return array();

        $minDate = time() - ( 60*60*24*30 );
        $sql = "
            SELECT e.uid_empresa
            FROM ". TABLE_EMPRESA ." e
            WHERE uid_empresa IN (". $intList->toComaList() ." )
            AND UNIX_TIMESTAMP(accion) < $minDate AND accion
            ORDER BY accion ASC
            LIMIT 0, $numero
        ";
        //$coleccion = array();
        $list = $db->query($sql, "*", 0, "empresa");
        return $list;

        /*foreach($list as $uid){
            $coleccion[] = new empresa($uid);
        }
        return $coleccion;*/
    }

    /** NOS INDICA DONDE BUSCAR LAS EMPRESAS SUPERIORES **/
    static public function getParentTable(){
        return TABLE_EMPRESA . "_relacion";
    }
    /** NOS INDICA EL CAMPO QUE REPRESENTA LA EMPRESA EN LA TABLA DE RELACIONES **/
    static public function getParentRelationalField(){
        return "uid_empresa_inferior";
    }
    /** NOS INDICA EL CAMPO QUE REPRESENTA LA EMPRESA EN LA TABLA DE RELACIONES **/
    static public function getParentTableRelationalField(){
        return "uid_empresa_superior";
    }

    public static function importFromFile($file, $empresa, $usuario, $post = null)
    {
        // Objeto database
        $db = db::singleton();

        // Importamos los elementos a la tabla
        $results = self::importBasics($usuario,$file,"empresa","cif");

        if( count($results["uids"]) ){

            //Relacionamos los elementos con nuestra empresa
            $sql = "INSERT IGNORE INTO ". TABLE_EMPRESA ."_relacion ( uid_empresa_superior, uid_empresa_inferior )
            SELECT ". $empresa->getUID() .", uid_empresa FROM ". TABLE_EMPRESA ." WHERE uid_empresa IN (". implode(",", $results["uids"]) .")
            ";
            if( $db->query($sql) ){
                return $results;
            } else {
                throw new Exception( "Error al tratar de relacionar" );
            }
        } else {
            throw new Exception( "No hay elementos para relacionar" );
        }
    }

    public function getTransactions(){
        $sql = "SELECT *, DATEDIFF(NOW(), date) as en_uso FROM ". paypalLicense::TABLE_ITEM ." LEFT JOIN ". TABLE_TRANSACTION ." USING(custom) WHERE uid_empresa = {$this->getUID()} ORDER BY date DESC ";
        $data = $this->db->query($sql, true);
        return utf8_multiple_encode($data);
    }

    public function getPaidInfo () {
        $cacheKey = implode('-', [$this, __FUNCTION__]);
        if (null !== ($data = $this->cache->getData($cacheKey))) {
            return json_decode($data);
        }

        $data = paypalLicense::getTransactionData(paypalLicense::getTransactionId($this));

        $this->cache->set($cacheKey, json_encode($data));
        return $data;
    }

    public function getPayInfo(){
        $paypal = new paypalLicense;
        $data = $paypal->getPayData($this);
        $data->sumary = $paypal->getSummary($this);
        return $data;
    }

    public function getNextPayDiscount($total, $originDate = null) {
        if ($this->isTemporary()) return 0;
        $quantity = $this->getUnusedAmount($originDate);

        if ($quantity) {
            $rate = ($quantity * 100) / $total;
            return round($rate, 2);
        }

        return 0;
    }

    public function getUnusedAmount($originDate = null) {
        if ($this->isPremium() && !$this->hasTemporaryPayment()) {
            $info = $this->getPaidInfo();
            if ($info && count((array)$info)) {
                $payDate = new DateTime($info->date);

                $payDate = new DateTime($payDate->format('Y-m-1'));

                if ($originDate instanceof DateTime) {
                    $currentDate = $originDate;
                } else {
                    $currentDate = new DateTime('now');
                }


                $interval = $payDate->diff($currentDate);
                $months = ((int) $interval->format('%M')) + 1;

                // Si la licencia tiene ya mas de 11 meses no hay descuento
                if ($months > 11 || $interval->days > 355) return 0;

                // usamos la base sin impuestos ni tasas, que es lo que realmente se paga
                $perMonth = $info->price / 12;

                $pending = $perMonth * (12 - $months); // meses que quedan por usar por precio unitario mes

                return round($pending, 2);
            }

        }
    }

    public function hasPaid(){
        return ( paypalLicense::getTransactionId($this) ) ? true : false;
    }

    public function getPaidDate($diff=false, $returnTimestamp = false){
        if( $txn = paypalLicense::getTransactionId($this) ){
            $timestamp = paypalLicense::getTransactionTimestamp($txn);

            if ($returnTimestamp) {
                return $timestamp;
            }

            if ($diff) {
                $secondsdiff = $timestamp - time();
                return floor(abs( $secondsdiff / 3600 / 24 ));
            } else {
                return date("Y/m/d", $timestamp);
            }
        }
        return false;
    }

    public function getDaysToExpireTempLicense () {

        if ($this->isEnterprise()) return false;

        $paypal = new paypalLicense;
        $lastPayInfo = $this->getPaidInfo();
        if (count((array)$lastPayInfo)) {
            $paidDate = $this->getPaidDate(true);
            return $lastPayInfo->daysValidLicense - $paidDate;
        }

        return false;

    }

    public function setLicense($license, $usuario = null) {
        return $this->update(["license" => $license], self::PUBLIFIELDS_MODE_LICENSE, $usuario);
    }

    /** A diferencia de empresa::getLicense devuelve la licencia selecciona por la empresa y no la activa **/
    public function getSelectedLicense() {
        if ($this->obtenerDato("is_enterprise") || $this->perteneceCorporacion()) return empresa::LICENSE_ENTERPRISE;

        if ($license = $this->obtenerDato('license')) {
            return $license;
        }

        return empresa::LICENSE_FREE;
    }

    public function getLicense() {
        if ($this->obtenerDato("is_enterprise") || $this->perteneceCorporacion()) return empresa::LICENSE_ENTERPRISE;
        if ($this->hasPaid()) return empresa::LICENSE_PREMIUM;
        return empresa::LICENSE_FREE;
    }

    public function needsPay() {
        return ($this->isFree() || $this->mustPay());
    }

    public function mustPay(){

        if ($this->getLicense() === empresa::LICENSE_ENTERPRISE) return false;
        if ($this->hasOptionalPayment()) return false;
        $hasPaid = $this->hasPaid();

        if ($hasPaid) {
            // Si ya caduco el pago anterior
            if ($this->hasExpiredLicense() && $this->getSelectedLicense() == empresa::LICENSE_PREMIUM) return true;

            // Si ya no es valido el tipo de licencia
            if (!$this->hasValidLicense()) {
                return true;
            }

            return false;
        }


        return false;
    }

    public function hasExpiredLicense (){
        $paypal = new paypalLicense;
        $lastPayInfo = $this->getPaidInfo();
        if (!count((array)$lastPayInfo)) return false;
        if ($this->getPaidDate(true) > $lastPayInfo->daysValidLicense) { return true; }
        return false;
    }

    /**
     * Check if the company has the new ranges license
     * @return bool
     */
    public function applyNewLicenseRange()
    {
        $paypal = new paypalLicense;
        $lastPayInfo = $this->getPaidInfo();

        if (false === isset($lastPayInfo->items)) {
            return true;
        }

        $newRangeDate = DateTime::createFromFormat('m/d/Y H:i:s', paypalLicense::NEW_RANGE_DATE_APPLY);
        $payDate = new DateTime($lastPayInfo->date);

        return $newRangeDate < $payDate;
    }

    public function hasValidLicense(){

        if ($this->getLicense() === empresa::LICENSE_ENTERPRISE) return true;
        if ($this->getSelectedLicense() === empresa::LICENSE_FREE) return true;

        $paypal = new paypalLicense;
        $lastPayInfo = $this->getPaidInfo();

        // if not free, not enterprise, and no previous pay exists, the license is not valid
        if (!isset($lastPayInfo->items)) return false;

        $currentPayInfo = $paypal->getPayData($this);

        // Si antes habia menos elementos que ahora
        if ($lastPayInfo->items < $currentPayInfo->quantity) {
            if (false === $this->applyNewLicenseRange()) {
                // Y ademas el precio base cambia...
                if (paypalLicense::getPayPrice($lastPayInfo->items) !== paypalLicense::getPayPrice($currentPayInfo->quantity)) {
                    return false;
                }
            } else {
                // Y ademas el rango de elementos...
                if (paypalLicense::getPreviousRange($lastPayInfo->items) !== paypalLicense::getPreviousRange($currentPayInfo->quantity)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function hasOptionalPayment(){
        return (bool)$this->obtenerDato('pago_no_obligatorio');
    }

    public function timeFreameToRenewLicense($days=self::TIME_NOTIFICCATION_PAYMENT_FRAME){
        if ($this->isFree() || $this->isEnterprise()) return false;

        if ($this->hasPaid()) {
            $paypal = new paypalLicense;
            $lastPayInfo = $this->getPaidInfo();

            $paidDate = $this->getPaidDate(true);
            if ($paidDate && $lastPayInfo && $paidDate <= $lastPayInfo->daysValidLicense) {
                $renewLicenseTimeFrame = $lastPayInfo->daysValidLicense - $days - $paidDate;
                if ($renewLicenseTimeFrame <= 0){
                    return true;
                }
            }
        }
        return false;
    }

    public function dueOutRange()
    {

        if ($this->isEnterprise() || $this->isFree()) return false;
        if (!$this->hasValidLicense()) return false; //already a payment pending

        $paypal = new paypalLicense;
        $lastPayInfo = $this->getPaidInfo(); //last payment info
        $currentPayInfo = $paypal->getPayData($this); //current info elements

        if (false === $this->applyNewLicenseRange()) {
            $newPayPrice = paypalLicense::getPayPrice($lastPayInfo->items) !== paypalLicense::getPayPrice($currentPayInfo->quantity + 1);

            if ($currentPayInfo->quantity + 1 > $lastPayInfo->items && $newPayPrice) {
                return true;
            }
        } else {
            if (paypalLicense::getPreviousRange($currentPayInfo->quantity + 1) > paypalLicense::getPreviousRange($lastPayInfo->items)) {
                return true;
            }
        }

        return false;
    }

    public function justOutRange(){

        if ($this->isEnterprise() || $this->isFree()) return false;

        $paypal = new paypalLicense;
        $lastPayInfo = $this->getPaidInfo();
        $currentPayInfo = $paypal->getPayData($this);
        $oneMoreItem = $currentPayInfo->quantity == $lastPayInfo->items + 1;

        if (false === $this->applyNewLicenseRange()) {
            $newRange = paypalLicense::getPayPrice($lastPayInfo->items) !== paypalLicense::getPayPrice($currentPayInfo->quantity);
        } else {
            $newRange = paypalLicense::getPreviousRange($lastPayInfo->items) !== paypalLicense::getPreviousRange($currentPayInfo->quantity);
        }

        if ($oneMoreItem && $newRange) {
            return true;
        }

        return false;
    }

    public function pagoPorSubcontratacion(){
        if ($this->perteneceCorporacion() || $this->isEnterprise()) return false;

        $arguments = new ArrayObjectList;
        $empresas = $this->obtenerEmpresasSolicitantes();

        foreach ($empresas as $empresa) {
            if ($empresa->pagoActivado()) $arguments[] = $empresa;

            if ($corp = $empresa->perteneceCorporacion()) {
                if ($corp->pagoActivado()) $arguments[] = $empresa;
            }
        }

        if( !count($arguments) ) return false;
        return $arguments->unique();
    }

    public function pagoPorInterconexion(){
        if( $this->perteneceCorporacion() || $this->isEnterprise() ) return false;
        $arguments = array();
        $empresas = $this->obtenerEmpresasSolicitantes();

        if( count($empresas) < 2 ){ return false; }

        return new ArrayObjectList($empresas);
    }

    public function isPaymentRequired()
    {
        if (true === $this->hasOptionalPayment()) {
            return false;
        }

        if (false === $this->pagoPorSubcontratacion()) {
            return false;
        }

        return true;
    }

    public function obtenerEmpleadosConEpi( $estado = false ){
        $sql = "SELECT e.uid_empleado FROM ".TABLE_EMPLEADO." e INNER JOIN ".TABLE_EMPLEADO_EPI." ep USING(uid_empleado) WHERE e.uid_empresa = {$this->uid}";

        if( $estado != false ){
            $sql .= " AND e.estado IN({$estado})";
        }
        $arrEmpleados = $this->db->query( $sql, "*", 0, "empleado" );

        return $arrEmpleados;
    }

    public function obtenerEpis( $eliminadas = false, $limit = false, $estado = false, $tipoEpi = false, $count = false, $filters = NULL ){
        $condicion = elemento::construirCondicion( $eliminadas , $limit );
        if( is_numeric($estado) ){
            switch($estado){
                case epi::ESTADO_ALMACEN: // ESTADO 24 - almacen : EPIS DISPONIBLES
                    $condicion = " uid_epi NOT IN (  SELECT uid_epi FROM ". TABLE_EMPLEADO_EPI ." ) AND " . $condicion;;
                break;
            }
        }

        if( isset($filters) ){
            foreach ($filters as $key => $filter) {
                switch($key){
                    case 'alias':
                        $condicion = " uid_epi IN (  SELECT uid_epi FROM ". TABLE_TIPO_EPI ." INNER JOIN ". TABLE_EPI ." USING(uid_tipo_epi) WHERE (descripcion LIKE '%".$filter."%') OR (nserie LIKE '%".$filter."%')) AND " . $condicion;
                    break;
                    default:
                        $condicion = $key." = ".$filter." AND " . $condicion;
                    break;
                }
            }
        }

        if( $count === true ){
            $condicion .= " AND uid_empresa = {$this->getUID()}";
            return $this->db->query( "SELECT count(uid_epi) FROM ". TABLE_EPI ." WHERE 1 AND {$condicion}", 0, 0);
        }

        $epis = $this->obtenerObjetosRelacionados( TABLE_EPI, "epi", $condicion);
        return new ArrayObjectList($epis);
    }


    public function obtenerNumeroEpis($eliminadas = false, $limit = false, $estado = false, $tipoEpi = false, $count = false, $filters = NULL ){
        return $this->obtenerEpis( $eliminadas, $limit, $estado, $tipoEpi, true, $filters);
    }

    /**
      * CONTEOS DE DOCUMENTOS DE EMPLEADOS O MAQUINA POR EMPRESA
      *
      **/
    public function getNumberOfDocumentsByStatusOfChilds(Iusuario $user = null, $module, $cache = true)
    {
        $statuses = documento::getAllStatus();

        $module = strtolower($module);
        $uidmodulo = util::getModuleId($module);
        $view = TABLE_DOCUMENTO . '_' . $module . '_estado';
        $table = constant('TABLE_' . strtoupper($module));

        $userFilter = $user instanceof Iusuario ? $user->obtenerCondicionDocumentosView($module) : '';

        foreach ($statuses as $code => $status) {
            $compareTo = $status ? " = {$status}" : "IS NULL";
            $primaryKey = "uid_{$module}";

            $userAsDomainEntity = $legacyUser = null;

            if ($user instanceof usuario) {
                $legacyUser = $user;
            }

            if ($user instanceof perfil) {
                $legacyUser = $user->getUser();
            }

            if ($legacyUser instanceof usuario) {
                $userAsDomainEntity = $legacyUser->asDomainEntity();
            }

            $indexList = (string) $this->app['index.repository']->getIndexOf(
                $module,
                $this->asDomainEntity(),
                $userAsDomainEntity,
                false
            );

            $sql = "SELECT COUNT(uid_solicituddocumento) as num
            FROM {$view} view
            WHERE 1
            AND {$primaryKey} IN ($indexList)
            AND descargar = 0
            AND obligatorio = 1
            AND estado {$compareTo}
            {$userFilter}";

            $num = $this->db->query($sql, 0, 0);
            $statuses[$code] = $num;
        }

        return $statuses;
    }

    public function getEmailFor($plantilla, $ob = false){
        return $this->obtenerEmailContactos($plantilla);
    }

    public function obtenerInformes(){
        $coleccionInformes = array();
        $sql = "SELECT uid_informe
            FROM ". TABLE_INFORME ."
            WHERE uid_elemento = $this->uid
            AND uid_modulo = ". $this->getModuleId() ."
        ";

        $coleccionIDInformes = $this->db->query($sql,"*", 0);

        if( is_array($coleccionIDInformes) ){
            foreach($coleccionIDInformes as $uid){
                $coleccionInformes[] = new informe($uid, $this);
            }
        }
        return $coleccionInformes;
    }

    /***
       * Devuelve empresa si pertenece a una corporación o false si no.
       *
       *
       *
       **/
    public function perteneceCorporacion(){
        $cacheKey = implode('-', array(__FUNCTION__, $this));
        if (($val = $this->cache->get($cacheKey)) !== NULL) {
            return $val ? empresa::factory($val) : false;
        }

        $table = TABLE_EMPRESA;

        $sql = "SELECT e.uid_empresa FROM {$table}_relacion r
        INNER JOIN {$table} e ON r.uid_empresa_superior = e.uid_empresa
        WHERE r.uid_empresa_inferior = {$this->getUID()}
        AND e.activo_corporacion = 1
        LIMIT 1
        ";

        if ($uid = $this->db->query($sql, 0, 0)) {
            $corp = new empresa($uid);
            $this->cache->set($cacheKey, $corp, 60*60*15);

            return $corp;
        } else {
            $this->cache->set($cacheKey, false, 60*60*15);
            return false;
        }
    }

    public function belongsToCorporation()
    {
        return $this->perteneceCorporacion();
    }


    /** SI EL CLIENTE TIENE ACTIVA COPORACION, SE PUEDEN ASIGNAR AGRUPAMIENTOS A SUS EMPRESAS */
    public function actualizarAgrupamientos($availables, $assigned, Iusuario $usuario = NULL){
        //---- como siempre, borramos todo en un principio
        if (count($availables)) {
            $sql = "DELETE FROM ". TABLE_EMPRESA ."_agrupamiento WHERE uid_empresa = ". $this->getUID() . " AND uid_agrupamiento IN (". $availables->toComaList() .")";
            if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }
        }

        //---- asignamos los que se han indicado en el formulario
        $inserts = array();
        if (count($assigned)) {
            foreach ($assigned as $idEmpresaAsignada) {
                $inserts[] = "(". $this->getUID() .", ". $idEmpresaAsignada->getUID() .")";
            }
            $sql = "INSERT IGNORE INTO ". TABLE_EMPRESA ."_agrupamiento ( uid_empresa, uid_agrupamiento ) VALUES ". implode(",", $inserts);
            if (!$this->db->query($sql)) { return $this->db->lastErrorString(); }
        }

        if (count($availables)) {
            set_time_limit(0);
            $setAgrAvailables = $availables->foreachCall("obtenerAgrupadores");
            if (count($assigned)) {
                $setAgrAssigned = $assigned->foreachCall("obtenerAgrupadores");
            } else {
                $setAgrAssigned = new ArrayObjectList;
            }

            $this->quitarAgrupadores($setAgrAvailables->toIntList()->getArrayCopy(), $usuario, $setAgrAssigned->toIntList()->getArrayCopy());
        }

        $this->actualizarSolicitudDocumentos();
        return true;
    }

    /***
       * Get the degrees connection between $this as source and @param company as target
       *
       *
       * @param $target - Empresa
       *
       */
    public function getConnectionDegrees (empresa $target) {
        $startList = $this->getStartIntList();
        $degrees = [];

        // first chance is to be a direct contract
        if ($target->esContrata($this)) {
            $degrees[] = 1;
        }

        $sql = "SELECT n1, n2, n3, n4 FROM ". TABLE_EMPRESA ."_contratacion WHERE n1 IN ({$startList->toComaList()}) AND (
                (n3 = {$target->getUID()} AND (n4 IS NULL OR n4 = 0))
            OR  (n4 = {$target->getUID()})
        )
        ";

        $connections = $this->db->query($sql, true);

        if (!$connections) {
            return $degrees;
        }

        foreach ($connections as $connection) {
            $degrees[] = count(array_filter($connection)) - 1;
        }

        return $degrees;
    }


    /** OBTENER UN CONJUNTO DE ArrayObjectList QUE REPRESENTAN UNA CADENA DE EMPRESAS
      *  Probablemente necesitaríamos revisar esto para un futuro por temas de rendimiento
      */
    public function obtenerCaminos(empresa $destino, empresa $companyPath = NULL, $fastest = false){
        $cachestring = "way-from-{$this}-to-{$destino}-{$fastest}";


        if ( isset($cacheString) && ($estado = $this->cache->getData($cacheString)) !== null ){
            $set = $estado;
        } else {

            $startList = $this->getStartIntList();
            if( !count($startList) ) return false;

            $sql = "SELECT n1, n2, n3, n4 FROM ". TABLE_EMPRESA ."_jerarquia WHERE n1 IN ({$startList->toComaList()}) AND (
                        (n2 = {$destino->getUID()} AND (n3 IS NULL OR n3 = 0) AND (n4 IS NULL OR n4 = 0))
                    OR  (n3 = {$destino->getUID()} AND (n4 IS NULL OR n4 = 0))
                    OR  (n4 = {$destino->getUID()})
                )
            ";

            if ($companyPath) {
                $sql .= " AND (n1 = {$companyPath->getUID()} OR n2 = {$companyPath->getUID()} OR n3 = {$companyPath->getUID()})";
            }

            $set = $this->db->query($sql, true);
            // Almacenamos aqui la cache, por que no compensa perder legibilidad contra la mínima mejora de rendimiento que podemos obtener
            $this->cache->addData($cachestring, $set);
        }

        $caminos = new ArrayObjectList;
        foreach($set as $caminoIDS){
            $camino = new ArrayObjectList;
            foreach($caminoIDS as $nodeID){
                if( $nodeID && is_numeric($nodeID) ){
                    $node = new empresa($nodeID);
                    $camino[] = $node;
                }
            }

            if( count($camino) ){
                if( $fastest ){
                    return $camino;
                }

                $caminos[] = $camino;
            }
        }

        return $caminos;
    }


    public function getForeignKey(empresa $parent){
        $sql = "SELECT uid_empresa_relacion FROM ". TABLE_EMPRESA ."_relacion WHERE uid_empresa_superior = {$parent->getUID()} AND uid_empresa_inferior = {$this->getUID()}";
        $key = $this->db->query($sql, 0, 0);
        return $key;
    }

    public function obtenerPais() {
        if ($uid = $this->obtenerDato('uid_pais')) {
            return new pais($uid);
        }
        return false;
    }

    // fgomez: los campos 'localidad' y 'provincia' de empresa no están normalizados.
    // habría que normalizarlos y crear un interface 'datosGeograficos','hasLocation' o similar para
    // el trio de funciones obtenerPais, obtenerMunicipio, obtenerProvincia y quizás otras
    public function obtenerMunicipio() {
        if ($uid = $this->obtenerDato('uid_municipio')) {
            return new municipio($uid);
        }
        return false;
    }

    public function obtenerProvincia() {
        if ($uid = $this->obtenerDato('uid_provincia')) {
            return new provincia($uid);
        }
        return false;
    }


    // public function triggerAfterCreate(Iusuario $usuario = NULL, Ielemento $elemento = NULL){
    //  if (isset($_REQUEST['tipo_empresa']) && is_numeric($_REQUEST['tipo_empresa'])) {
    //      $arrayIds = array($_REQUEST['tipo_empresa']);
    //      if ($retorno = $elemento->asignarAgrupadores($arrayIds,$usuario,0,true) !== false) {
    //          $elemento->actualizarSolicitudDocumentos();
    //      }
    //  }
    // }

    public function obtenerExportacionmasivas($papelera = false) {
        // el parametro usuario se pasa como ultimo parametro desde https://github.com/Dokify/dokify/commit/5bd6c772e1092aff68d5bd599f46043a116fb565
        if ($papelera instanceof usuario) {
            $papelera = 0;
        } else {
            $papelera = (int) $papelera;
        }
        $sql = "SELECT uid_exportacion_masiva FROM ".TABLE_EXPORTACION_MASIVA." WHERE 1 and papelera={$papelera} and uid_empresa = {$this->getUID()}";
        $coleccion = $this->db->query($sql, "*", 0, "exportacion_masiva");
        return new ArrayObjectList($coleccion);
    }


    public function resumenEpis() {
        $resumenEmpresa = $resumenAlmacen = $resumenEmpleados = null;
        $template = Plantilla::singleton();
        foreach ($this->obtenerEmpleados() as $empleado) {
            $resumenEmpleados .= $empleado->resumenEpis();
        }

        foreach ($this->obtenerEpis(false,false,epi::ESTADO_ALMACEN) as $epiAlmacen) {
            $resumenAlmacen .= $epiAlmacen->resumen();
        }
        if ((!$resumenEmpleados) && (!$resumenAlmacen)) {
            return null;
        }

        $template->assign('elemento',$this);
        $template->assign('resumenEmpleados',$resumenEmpleados);
        $template->assign('resumenAlmacen',$resumenAlmacen);
        $resumenEmpresa = $template->getHTML('epi/empresa.tpl');
        return $resumenEmpresa;
    }

    static public function optionsFilter($uidelemento, $uidmodulo, $user, $publicMode, $config, $tipo, $parent, $extraData = null){
        $condicion = array();

        if( $uidelemento ){
            $empresaUsuario = $user->getCompany();
            $isEnterprise = $empresaUsuario->isEnterprise();
            $esCorporacion = $isEnterprise && $empresaUsuario->esCorporacion() ? true : $empresaUsuario->perteneceCorporacion();
            $corporacion = ($esCorporacion instanceof empresa) ? $esCorporacion : $empresaUsuario;
            $staff = $user->esStaff();
            $empresa = new self($uidelemento);

            if (!$empresa->obtenerDato("pago_aplicacion")) {
                $condicion[] = " uid_accion != 83 ";
            }

            if ($esCorporacion === true && $empresaUsuario->compareTo($empresa)) {
                // enviarpapelera:5, asignacion:20, calendario:33, aptitud:50, clientes:52, certificacion:72, epis:122, centrocotizacion:125
                $condicion[] = "uid_accion NOT IN (5,20,33,50,52,72,122,125)";
            }

            if (!$empresaUsuario->compareTo($empresa) && !$empresa->perteneceCorporacion()) {
                // eliminamos el modulo epis para empresas que no son una misma y que no pertenecen a una corporacion
                $condicion[] = "uid_accion NOT IN (122)";
            }

            if ($user->getAppVersion() === 1) {
                if (!$empresaUsuario->obtenerCamposDinamicos(1)) {
                    $condicion[] = " uid_accion NOT IN (4) ";
                }
            }

            if (!$empresaUsuario->isEnterprise()) {
                $condicion[] = " uid_accion NOT IN (125) ";
            }

            //usuarios:2
            $accionesPropiaEmpresa = "2";
            if ($staff) $accionesPropiaEmpresa = "0";
            $startList = $empresaUsuario->getStartList();



            if (!$startList->contains($empresa)) {
                $condicion[] = "uid_accion NOT IN ($accionesPropiaEmpresa)";


                // Si no es nuestra propia empresa, veremos que cosas podemos hacer sobre las empresas que vemos segun su "distancia"
                //if( !$empresaUsuario->compareTo($empresa) ){

                // clientes:52, enviarpapelera:5, centrocotizacion:125, modificar:4, 50:aptitud, asignar:153
                $accionesEmpresasDirectas = "52, 125, 4, 50, 153";
                if ($staff) $accionesEmpresasDirectas = "52";


                if (!$empresa->esContrata($startList)) {
                    $condicion[] = "uid_accion NOT IN ($accionesEmpresasDirectas)";
                }

            } else {
                $condicion[] = "uid_accion NOT IN (52)"; // no pueden ver visibilidad
            }

        } elseif( $tipo == 3 && !$user->esStaff() ){
            $empresaUsuario = $user->getCompany();
            $empresaCorp = $empresaUsuario->esCorporacion() && $empresaUsuario->obtenerEmpresasInferiores()->contains($parent) ? TRUE : FALSE;

            // 22:crear, 23:verpapelera
            $reservadas = "22, 23";
            if( !$empresaUsuario->compareTo($parent) && !$empresaCorp ){
                // No se pueden crear elementos ...
                $condicion[] = " ( uid_accion NOT IN ($reservadas) ) ";
            }
        }

        if( count($condicion) ){
            return " AND ". implode(" AND ", $condicion);
        }

        return false;
    }


    public static function obtenerConvenios(){
        $sql = "SELECT convenio FROM ". TABLE_EMPRESA ." WHERE convenio != '' GROUP BY convenio ORDER BY convenio";
        $list = db::get($sql, "*", 0);

        if( $list && count($list) ){
            $list = array_map("utf8_encode", $list);
            $list = array_combine($list, $list);
            return $list;
        }

        return false;
    }

    public function updateData($data, Iusuario $usuario = null, $mode = null)
    {

        if (isset($data['uid_pais'])) {
            $country = new pais($data['uid_pais']);
        } else {
            $country = $this->getCountry();
        }

        if ($country->exists() == false) {
            throw new Exception(_('Specify a valid country'));
        }

        if (isset($data['cif']) && vat::checkValidVAT($country, $data['cif']) == false) {
            // notes: VAT for companies
            throw new Exception(_('The VAT number is invalid'));
        }

        if (isset($data['cif']) && $this->getCIF() != $data['cif'] && vat::isInUse($data['cif']) == true) {
            throw new Exception(_('The VAT number is already in use'));
        }

        if ($mode == elemento::PUBLIFIELDS_MODE_EDIT) {
            if (isset($data["nombre"])) {
                if (trim($data["nombre"]) == "") {
                    throw new Exception(_('Error, name field is required'));
                }
            }

            if (isset($data["cp"])) {
                if (trim($data["cp"]) == "") {
                    throw new Exception(_('Error, cp field is required'));
                }
            }

            if (isset($data["direccion"])) {
                if (trim($data["direccion"]) == "") {
                    throw new Exception(_('Error, address field is required'));
                }
            }
        }

        if ($country->getUID() != pais::SPAIN_CODE) {
            $data["uid_provincia"] = '0';
            $data["uid_municipio"] = '0';
        };

        $validTown = isset($data["uid_municipio"]) && is_numeric($data["uid_municipio"]);
        $validState = isset($data["uid_provincia"]) && is_numeric($data["uid_provincia"]);
        if ($validState && $validTown) {
            $validProvincia = ($data["uid_provincia"] != '') && ($data["uid_provincia"] != '0');
            $validMunicipio = ($data["uid_municipio"] != '') && ($data["uid_municipio"] != '0');
            if ($validMunicipio) {
                if (!$validProvincia) {
                    throw new Exception("error_municipio");
                }
                $municipio = new municipio($data["uid_municipio"]);
                $municipiosProvincia = municipio::obtenerPorProvincia($data["uid_provincia"]);
                if (!$municipiosProvincia->contains($municipio)) {
                    throw new Exception("error_municipio");
                }
            }
        } else {
            if ($validTown === false) {
                $data["uid_municipio"] = null;
            }
            if ($validState === false) {
                $data["uid_provincia"] = null;
            }
        }

        if (isset($data["validation_languages"])) {
            $langs = "";
            foreach ($data["validation_languages"] as $language) {
                $langs .=  $language.",";
            }
            $langs = substr_replace($langs, "", -1);
            $data["validation_languages"] = $langs;
        }

        if (isset($data["pay_for_contracts"]) && $data["pay_for_contracts"]) {
            if ((!isset($data["is_enterprise"]) || !$data["is_enterprise"]) && !$this->perteneceCorporacion()) {
                throw new Exception("error_client_pay_option");
            }
        }

        if (isset($data["activo_corporacion"]) && !$data["activo_corporacion"] && $this->esCorporacion()) {
            if (!$this->disableCorporation()) {
                throw new Exception("Error deactivating corporation");
            }
        }

        if (isset($data["is_enterprise"])
            && !$data["is_enterprise"]
            && $this->isEnterprise()
        ) {
            $pendingInvoices = $this->getInvoicesWithoutCustom();

            foreach ($pendingInvoices as $pendingInvoice) {
                $pendingInvoice->regenerateCustom();
            }
        }

        return $data;
    }


    /**
    *   Tasks to do when a company disable corporation
    *
    *
    */
    public function disableCorporation() {
        $deletedGroupings = $this->deleteCorporationGroupings();

        return $deletedGroupings;
    }

    /**
    *   delete all the grouping assign to corporation companies
    *
    *
    */
    public function deleteCorporationGroupings() {
        if (!$this->esCorporacion()) return false;

        $companies = $this->obtenerEmpresasInferiores();

        // If the company doesn't have any company we don't do anything
        if (!count($companies)) return true;
        $companiesComaList = $companies->toComaList();

        $sql = "DELETE FROM ". TABLE_EMPRESA . "_agrupamiento WHERE uid_empresa IN ({$companiesComaList})";

        return $this->db->query($sql);
    }


    /** NOS DEVUELVE UN COJUNTO DE OBJETOS "campo" PUDIENDO FILTRAR POR MODULO Y FILTRANDO SIEMPRE POR CLIENTE */
    public function obtenerCamposDinamicos($modulo=false){

        $cacheString = "camposdinamicos-empresa-".$this->getUID()."-".$modulo;
        $estado = $this->cache->getData($cacheString);
        if( $estado !== null ){
            return $estado;
        }

        $coleccionCampos = array();
        $sql = "SELECT uid_campo
                FROM ". TABLE_EMPRESA ."_campo INNER JOIN ". TABLE_CAMPO ." USING(uid_campo)
                WHERE uid_empresa = ". $this->getUID();
        if( $modulo ){
            if( !is_numeric($modulo) ){ $modulo = util::getModuleId($modulo); }
            if( !is_numeric($modulo) ){ return false; }
            $sql .= " AND uid_modulo = $modulo";
        }

        $sql .=" ORDER BY prioridad";
        $lineasCampos = $this->db->query($sql, true );
        foreach( $lineasCampos as $infoCampo ){
            $coleccionCampos[] = new campo($infoCampo["uid_campo"]);
        }
        $this->cache->addData($cacheString, $coleccionCampos);
        return $coleccionCampos;
    }


    /** ACTUALIZA LA RELACION DE CLIENTE <-> CAMPO A TRAVES DE LA VARIABLE $_REQUEST */
    public function actualizarCampos(){
        //---- eliminamos todos los registros
        $sql = "DELETE FROM ". TABLE_EMPRESA ."_campo WHERE uid_empresa = ". $this->getUID();
        if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }

        //---- asignamos los que se han indicado en el formulario
        $inserts = array();
        $asignados = isset($_REQUEST["elementos-asignados"]) ? $_REQUEST["elementos-asignados"] : [];
        foreach ($asignados as $idCampoAsignado) {
            $inserts[] = "(". $this->getUID() .", ". $idCampoAsignado .")";
        }

        if (!count($inserts)) return true;

        $sql = "INSERT INTO ". TABLE_EMPRESA ."_campo ( uid_empresa, uid_campo ) VALUES ". implode(",", $inserts);
        if( !$this->db->query($sql) ){ return $this->db->lastErrorString(); }

        //----- si todo va bien
        return true;
    }

    /** NOS DEVUELVE UN CONJUNTO DE OBJETOS "plugin" ASIGNADOS A ESTE CLIENTE */
    public function obtenerPlugins(){
        if ($this->getSelectedLicense() > empresa::LICENSE_FREE) {
            return plugin::getAll();
        }

        return false;
        //return $this->obtenerObjetosRelacionados( TABLE_EMPRESA ."_plugin", "plugin");
    }

    /** ACTUALIZAR LOS PLUGINS DE UN CLIENTE */
    public function actualizarPlugins(){
        return $this->actualizarRelacionRequest( TABLE_EMPRESA ."_plugin" );
    }

    public function esCorporacion(){
        return (bool) $this->obtenerDato("activo_corporacion");
    }


    /***
        obtener las noticias que puede ver una empresa
        !!!! OJO esta funcion no es estandar para poder recuperar datos del blog
    */
    public function getNews(Iusuario $usuario = NULL, $latest = true, $limit = 20){
        $latestDays = 30;
        $userWhere = false;
        $usersSQL = "";

        if ($corp = $this->perteneceCorporacion()) {
            $empresas = $corp->getStartList()->toComaList();
        } else {
            $empresas = $this->getUID();
        }

        if ($usuario instanceof usuario) {

            $empresasSuperiores = $this->obtenerEmpresasSolicitantes();
            $empresasSuperiores = count($empresasSuperiores) ? $empresasSuperiores->toComaList() : '0';

            if ($usuario->configValue("externo")) {
                $userWhere = " (uid_empresa IN ($empresas) AND visible_usuarios_externo = 1) ";
            } else {
                $userWhere = " (uid_empresa IN ($empresasSuperiores) AND uid_empresa NOT IN ($empresas) AND visible_usuarios_contratas = 1)
                                 OR
                               (uid_empresa IN ($empresas) AND visible_usuarios = 1) ";
            }

        } elseif ($usuario instanceof empleado) {

            if ($usuario->isManager()) {
                $employeeFilter = " AND (managers = 1 OR empleados = 1) ";
            } else {
                $employeeFilter = " AND empleados = 1 ";
            }

            $userWhere = " ( uid_empresa IN ($empresas) $employeeFilter ) ";
        }

        if ($userWhere) {
            $usersSQL = "
                SELECT uid_noticia, fecha_alta as post_date, '' as post_content, '' as guid, '' as ID
                FROM ". TABLE_NOTICIA ." WHERE $userWhere
            ";
        }

        $inAppPosts = "
            SELECT object_id FROM ". DB_WEB .".wp_term_relationships r
            INNER JOIN ". DB_WEB .".wp_term_taxonomy t USING(term_taxonomy_id)
            INNER JOIN ". DB_WEB .".wp_terms USING(term_id)
            WHERE slug = '". empresa::INAPP_POST_CATEGORY ."'
            GROUP BY object_id
        ";

        if (!$usuario instanceof empleado) {
            //El blog no se lo mostramos a los empleados
            $blogSQL = " UNION
                SELECT post_title, post_date, post_content, guid, ID
                FROM ". DB_WEB .".wp_posts
                WHERE post_status = 'publish'
                AND post_type = 'post' AND wp_posts.ID IN ($inAppPosts)
            ";
        }

        $sql = "
            SELECT uid_noticia, post_date, post_content, guid, ID FROM (
                $usersSQL $blogSQL
            ) as news WHERE 1
        ";

        if ($latest) $sql .= " AND DATEDIFF(NOW(), post_date) < {$latestDays}";
        $sql .= " ORDER BY post_date DESC";

        if (is_numeric($limit)) {
            $sql .= " LIMIT {$limit}";
        }



        $collection = new ArrayObjectList;
        if (($newsList = $this->db->query($sql, true)) === false) {
            //dump($this->db, $sql);

        } else {
            foreach ($newsList as $news) {
                $fromBlog = !is_numeric($news['uid_noticia']);

                if ($fromBlog) {
                    $news['title'] = $news['uid_noticia']; // usamos este campo para diferenciar
                    unset($news['uid_noticia']);

                    $collection[] = array_map('utf8_encode', $news);
                } else {
                    $collection[] = new noticia($news['uid_noticia']);
                }
            }
        }

        return $collection;
    }

    /***
        COLECCION DE NOTICIAS publicadas por esta empresa
    */
    public function obtenerNoticias($condicion=false, Iusuario $usuario = NULL, $latest = false){
        $coleccionNoticias = array();
        $sql = "SELECT uid_noticia FROM ". TABLE_NOTICIA ." WHERE uid_empresa = ". $this->getUID();
        if( $condicion ){ $sql .= " AND $condicion "; }


        if( $usuario instanceof Iusuario ){
            if( $usuario instanceof empleado ){
                if( $usuario->isManager() ){
                    $sql .= " AND managers = 1 ";
                } else {
                    $sql .= " AND empleados = 1 ";
                }
            } else {
                $sql .= " AND empleados = 0 AND managers = 0";
            }
        }

        if ($latest) {
            $sql .= " AND DATEDIFF(NOW(), fecha_alta) < 30";
        }

        $sql .= " ORDER BY fecha_alta DESC";

        $coleccion = $this->db->query($sql, "*", 0, "noticia");
        return new ArrayObjectList($coleccion);
    }


    public function obtenerMessages($condicion=false, Iusuario $usuario = NULL, $latest = false){
        $coleccionNoticias = array();
        $sql = "SELECT uid_message FROM ". TABLE_MESSAGE ." WHERE uid_empresa = ". $this->getUID();
        if( $condicion ){ $sql .= " AND $condicion "; }


        $sql .= " ORDER BY uid_message DESC";

        $coleccion = $this->db->query($sql, "*", 0, "message");
        return new ArrayObjectList($coleccion);
    }

    public function pagoActivado(){
        $sql = "SELECT pago_aplicacion FROM ".TABLE_EMPRESA." WHERE uid_empresa = ".$this->getUID();
        return $this->db->query($sql,0,0);
    }

    /** ACTUALIZAR ETIQUETAS ASIGNADAS A ESTE USUARIO */
    public function actualizarEtiquetas($list=array()){
        // $tabla = TABLE_PERFIL ."_etiqueta";
        //$currentUIDElemento = ( $uid ) ? $uid : obtener_uid_seleccionado();//db::scape( $_REQUEST["poid"] );

        $tabla = "{$this->tabla}_etiqueta";
        $campo = db::getPrimaryKey($this->tabla);
        $sql = "DELETE FROM $tabla WHERE $campo = {$this->getUID()}";

        if( $this->db->query($sql) ){
            if( !count($list) ){ return true; }
            $idEtiquetas = array_map("db::scape", $list);
            $inserts = array();

            foreach( $idEtiquetas as $idEtiqueta ){
                if( $idEtiqueta )
                    $inserts[] = "( {$this->getUID()}, $idEtiqueta )";
            }

            if( !count($inserts) ){ return "error_no_datos"; }

            $sql = "INSERT INTO $tabla ( $campo, uid_etiqueta ) VALUES ". implode(",", $inserts);
            $estado = $this->db->query( $sql );
            if( $estado ){ return true; } else { return $this->db->lastErrorString(); }
        } else {
            return $this->db->lastErrorString();
        }
    }


    /***
       * return ArrayObjectList of the labels of this company
       *
       *
       *
       */
    public function getLabels ()
    {
        $origin = $this->getOriginCompanies();

        $labels = $this->tabla . "_etiqueta";
        $sql = "SELECT uid_etiqueta FROM {$labels} WHERE uid_empresa IN ({$origin->toComaList()})";
        $array = $this->db->query($sql, "*", 0, "etiqueta");

        if ($array && count($array)) {
            return new ArrayObjectList($array);
        }

        return new ArrayObjectList;
    }

    /***
       * deprecated, use getLabels
       *
       *
       *
       */
    public function obtenerEtiquetas()
    {
        return $this->getLabels();
    }

    public function obtenerTipoepis() {
        $epis = TABLE_EPI;
        $types = TABLE_TIPO_EPI;
        $intList = $this->getStartIntList();
        $collection = new ArrayObjectList;
        $sql = "SELECT uid_tipo_epi FROM {$epis} epis INNER JOIN {$types} t USING (uid_tipo_epi) WHERE epis.uid_empresa IN ({$intList->toComaList()}) GROUP BY uid_tipo_epi ORDER BY descripcion";

        if ($list = $this->db->query($sql, "*", 0, 'tipo_epi')) {
            $collection = $collection->merge($list);
        }

        $sql = "SELECT uid_tipo_epi FROM {$types} WHERE 1";

        if (count($list)) {
            $sql .= " AND uid_tipo_epi NOT IN ({$collection->toComaList()})";
        }

        $sql .= " ORDER BY descripcion";

        if ($list = $this->db->query($sql, "*", 0, 'tipo_epi')) {
            $collection = $collection->merge($list);
        }

        return $collection;
    }

    public function getStyleString($implode=""){
        return implode($implode, $this->getStyleArray());
    }


    public function getStyleSelectorData($selector, $toString=true){
        $data = empresa::defaultStyleData();
        if( isset($data[$selector]) ){
            $buffer = $data[$selector];
            return $buffer;
        }
        return false;
    }

    public static function defaultStyleData() {
        $data = array(
                ".stat_0"  => "background-color:#ccc;",
                ".stat_1"  => "background-color:#add8e6;",
                ".stat_2"  => "background-color:#89b84f;",
                ".stat_3"  => "background-color:#f1961f;",
                ".stat_4"  => "background-color:#e83838;",
                ".stat_-1" => "background-color:#ffff00;"
            );
        return $data;
    }


    public function getStyleArray(){
        $res = empresa::defaultStyleData();
        $selectores = array();
        foreach($res as $selector => $valor){
            $selectores[] = $selector . "{". $valor ."}";
        }

        // Default styles
        if( $color = trim($this->obtenerDato("color")) ){
            $selectores[] = ".chat-window-actions img:hover { background-color:". color($color,-50) ."; }";
            $selectores[] = "#credits, #head, .chat-window-options, .option-block.open, .option-block div, .curren-user-info, tr.box-tabs .box-tab { background-color: ". color($color,-40) ."; }";
            $selectores[] = "#main-menu{ background-color:$color; }";
            $selectores[] = "#main-menu ul li, .option-block.open, .curren-user-info, tr.box-tabs .box-tab { border-color:#FFF; border-bottom-color:#000;  border-top-color:". color($color,-10) ."; background-image:url(". RESOURCES_DOMAIN ."/img/menudeg.png); background-repeat: repeat-x; background-position: 0 -8px; }";

            //#main-menu ul li.seleccionado, #main-menu ul li.seleccionado:first-child  { background-color:#CCC;border-color:#BBBBBB; box-shadow:0 0 3px -2px;  }
            $selectores[] = "#main-menu ul li.seleccionado, #main-menu ul li.seleccionado:first-child { border-color: #000 #000 ". color($color,-35) .";  }";
            $selectores[] = "#main-menu ul li.seleccionado, #main-menu ul li.seleccionado:first-child, #sub-head, #informacion-navegacion .separator { background-color:". color($color,-35) ."; }";


            $selectores[] = "#data-menu-top, #data-menu-bottom, #left-panel-title,.line-data tr.extra-line td, .line-data tr.extra-line td:hover{background-color:$color;border-color:". color($color,-60) .";}";
            $selectores[] = ".page-options .options, .page-options .toggle, .box .title{background-color: ". color($color,10) ."; }";
            $selectores[] = ".box .title, .box .content{border-bottom: 1px solid ". color($color,-40) ."; }";

            $selectores[] = ".news .box .content,.news .box .title{border-color:". color($color,-40) .";}";


            // Anque este selector no tiene ninguna variable de color necesitamos el background image
            $selectores[] = "#sub-head, #credits, #informacion-navegacion .separator, .chat-window-options{ background-image:url(".RESOURCES_DOMAIN."/img/trdeg.png);background-repeat: repeat-x; background-position: 0 330%;}";
            $selectores[] = "#sub-head { border-top: 1px solid #000; }";
            $selectores[] = "#head { border-bottom-color:#000; }";
            $selectores[] = "#informacion-navegacion ul li:after {content:'»';}";
            $selectores[] = "#informacion-navegacion li > a, #informacion-navegacion li > span { background-image:none; }";
            $selectores[] = "#informacion-navegacion ul li:first-child{background-image:none;padding-left:8px;}";
        }


        $headheight = "110";
        $imghead = RESOURCES_DOMAIN . "/img/header-dokify.png";

        $skin = $this->getSkinName();
        $img = RESOURCES_DOMAIN . "/img/cliente/".$skin ."/head.png";
        $file = DIR_IMG . "cliente/". $skin ."/head.png";
        if (file_exists($file) && $imghead = $img) {
            $selectores[] = "#head {background-image:url($imghead);}";
            $selectores[] = "#head-buttons{ height:40px;padding-top:110px;vertical-align:bottom;}";
        }

        return $selectores;

    }

    public function getURLBase(){

        if (!defined(CURRENT_DOMAIN)){
            return (CURRENT_ENV=='dev') ? CURRENT_DOMAIN ."/" : "https://dokify.net/";
        }

        return CURRENT_DOMAIN;

    }

    public function getReportes(){
        $coleccion = array();
        $coleccion[] = new reporte( reporte::TYPE_DOCUMENTOS_ANEXADOS);
        $coleccion[] = new reporte( reporte::TYPE_ATENCION_TELEFONICA);
        $coleccion[] = new reporte( reporte::TYPE_LISTADO_EMPLEADOS);
        $coleccion[] = new reporte( reporte::TYPE_LISTADO_MAQUINAS);
        return $coleccion;
    }

    /** COLECCION DE OBJETOS "ATRIBUTO_DOCUMENTO" ASOCIADOS A LA EMPRESA */
    public function getAttributesDocuments ($condicion=false, $order = false, $count = false, $includeAssigned = false, $corporacion = false) {

        $filters = array();
        if( $condicion ){
            $condicion = is_array($condicion) ? $condicion : array($condicion);

            foreach($condicion as $filter){
                if( is_string($filter) ){
                    $filters[] = " $filter";
                } elseif( is_array($filter) ){
                    foreach($filter as $field => $val ){
                        if( is_string($val) ){
                            $filters[] = " $field = '". db::scape($val) ."'";
                        } elseif( is_numeric(implode("",$val)) ){
                            $filters[] = " $field  IN (". implode(",",$val) .")";
                        }
                    }
                } elseif( $filter instanceof usuario ){
                    if( $filter->isViewFilterByLabel() ){
                        $etiquetas = $filter->obtenerEtiquetas();
                        if( $etiquetas && count($etiquetas) ){
                            $filters[] = " uid_documento_atributo IN (
                                SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta WHERE uid_etiqueta IN ({$etiquetas->toComaList()})
                            )";
                        } else {
                            $filters[] = " uid_documento_atributo NOT IN (SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta)";
                        }
                    }
                }
            }
        }

        $orderField = ($order) ? ",".$order : ""; //Para poder ordenar los union
        $field = $count ? "count(uid_documento_atributo)" : "uid_documento_atributo";
        $atributosPropios = " SELECT $field $orderField FROM ". TABLE_DOCUMENTO_ATRIBUTO ." attr WHERE uid_empresa_propietaria = $this->uid ";


        if (count($filters) ) $atributosPropios .= " AND ". implode(" AND ", $filters);

        $corp = $this->perteneceCorporacion();
        $agrupadoresAsignados = $this->obtenerAgrupadoresCorporacionAsignados();
        $agrupadoresAsignados = count($agrupadoresAsignados) ? $agrupadoresAsignados->toComaList() : '0';

        if ($includeAssigned && $agrupadoresAsignados && $corp) {
            $moduloOrigenAgrupador = util::getModuleId("agrupador");
            $moduloOrigenEmpresa = util::getModuleId("empresa");

            $atributosVisibles = " SELECT $field $orderField FROM ". TABLE_DOCUMENTO_ATRIBUTO ." attr
                                    WHERE (uid_modulo_origen = $moduloOrigenAgrupador AND uid_elemento_origen IN ({$agrupadoresAsignados}))
                                            OR
                                          (uid_modulo_origen = $moduloOrigenEmpresa AND uid_elemento_origen = {$this->getUID()})
                                            OR
                                          (uid_modulo_origen = $moduloOrigenEmpresa AND uid_elemento_origen = {$corp->getUID()})




                                          ";

            if (count($filters) ) $atributosVisibles .= " AND ". implode(" AND ", $filters);

            $sql = " SELECT $field from (
                        $atributosPropios
                      UNION
                        $atributosVisibles
                    ) as tmp

                    ";

            if ($order) $sql .= " ORDER BY $order";

        } else {
            if ($order) $sql = $atributosPropios ." ORDER BY $order";
            else $sql = $atributosPropios;
        }

        if ($corporacion && $count) {
            if ($corp = $this->perteneceCorporacion()) {
                $numDocs = $this->db->query($sql, 0, 0) + $corp->getAttributesDocuments(false, false, true);
                return $numDocs;
            }
        }

        if ($count) {
            return $this->db->query($sql, 0, 0);
        }

        $coleccion = $this->db->query($sql, "*", 0, 'documento_atributo');

        $coleccion = new ArrayObjectList($coleccion);
        return $coleccion;

    }

        /** COLECCION DE OBJETOS DOCUMENTOS ASOCIADOS A LA EMPRESA */
    public function getVisibleDocuments( $condicion=false, $order = false, $includeAssigned = false ){

        $filters = array();
        if( $condicion ){
            $condicion = is_array($condicion) ? $condicion : array($condicion);

            foreach($condicion as $filter){
                if( is_string($filter) ){
                    $filters[] = " $filter";
                } elseif( is_array($filter) ){
                    foreach($filter as $field => $val ){
                        if( is_string($val) ){
                            $filters[] = " $field = '". db::scape($val) ."'";
                        } elseif( is_numeric(implode("",$val)) ){
                            $filters[] = " $field  IN (". implode(",",$val) .")";
                        }
                    }
                } elseif( $filter instanceof usuario ){
                    if( $filter->isViewFilterByLabel() ){
                        $etiquetas = $filter->obtenerEtiquetas();
                        if( $etiquetas && count($etiquetas) ){
                            $filters[] = " uid_documento_atributo IN (
                                SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta WHERE uid_etiqueta IN ({$etiquetas->toComaList()})
                            )";
                        } else {
                            $filters[] = " uid_documento_atributo NOT IN (SELECT uid_documento_atributo FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_etiqueta)";
                        }
                    }
                }
            }
        }

        $orderField = ($order) ? ",".$order : ""; //Para poder ordenar los union
        $documentosPropios = "SELECT uid_documento,da.uid_documento_atributo $orderField FROM ".TABLE_DOCUMENTOS_ELEMENTOS ." de INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." da
        USING( uid_documento_atributo, uid_modulo_destino ) WHERE uid_empresa_propietaria = {$this->getUID()}";

        if (count($filters) ) $documentosPropios .= " AND ". implode(" AND ", $filters);

        $corp = $this->perteneceCorporacion();
        $agrupadoresAsignados = $this->obtenerAgrupadoresCorporacionAsignados();
        $agrupadoresAsignados = count($agrupadoresAsignados) ? $agrupadoresAsignados->toComaList() : '0';

        if ($includeAssigned && $agrupadoresAsignados && $corp) {
            $moduloOrigenAgrupador = util::getModuleId("agrupador");
            $moduloOrigenEmpresa = util::getModuleId("empresa");
            $documentosVisibles = "SELECT uid_documento,da.uid_documento_atributo $orderField FROM ".TABLE_DOCUMENTOS_ELEMENTOS ." de INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." da
                                    USING( uid_documento_atributo, uid_modulo_destino )
                                    WHERE (uid_modulo_origen = $moduloOrigenAgrupador AND uid_elemento_origen  IN ({$agrupadoresAsignados}))
                                            OR
                                          (uid_modulo_origen = $moduloOrigenEmpresa AND uid_elemento_origen = {$this->getUID()})
                                            OR
                                          (uid_modulo_origen = $moduloOrigenEmpresa AND uid_elemento_origen = {$corp->getUID()})
                                    ";

            if (count($filters) ) $documentosVisibles .= " AND ". implode(" AND ", $filters);

            $sql = " SELECT uid_documento from (
                        $documentosPropios
                      UNION
                        $documentosVisibles
                    ) as tmp

                    GROUP BY uid_documento";

            if ($order) $sql .= " ORDER BY $order";
        } else {

            $sql = $documentosPropios." GROUP BY uid_documento";
            if( $order ) $sql .= " ORDER BY $order";
        }

        $coleccion = $this->db->query($sql, "*", 0, 'documento');

        $coleccion = new ArrayObjectList($coleccion);
        return $coleccion;
    }

    /* Devuelve el máximo nivel de recursividad que puede tener una empresa */
    public function maxRecursionLevel(){
        if ($this->esCorporacion()){
            return self::DEFAULT_DISTANCIA + 1;
        }
        return self::DEFAULT_DISTANCIA;
    }

    /* Array de todos los logos disponibles */
    public static function getAllLogos(){
        $files = array();
        $files["nativo"] = "Ninguno";
        $dir = realpath( dirname(__FILE__) . "/../res/img/logos/" ) . "/";
        foreach( glob($dir."*.png") as $file){
            $file = reset( new ArrayObject( explode(".", basename($file) ) ) );
            $files[ $file ] = $file;
        }
        return $files;
    }

    public function getAllUsers(){
        $coleccionUsuarios = array();
        $relacionados = $this->obtenerRelacionados( TABLE_PERFIL, "uid_empresa", "uid_usuario" );

        $arrayIDUsuarios = array();
        foreach( $relacionados as $relacion ){
            if( !in_array($relacion["uid_usuario"], $arrayIDUsuarios) ){
                $uidUsuario = $relacion["uid_usuario"];

                $coleccionUsuarios[] = new usuario( $uidUsuario );

                $arrayIDUsuarios[] = $uidUsuario;
            }
        }

        return $coleccionUsuarios;
    }

    /** NOS DEVUELVE PARA UN MODULO DADO EL AGRUPAMIENTO QUE ACTUA COMO ORGANIZADOR **/
    public function obtenerOrganizador(){
        $sql = "SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ." WHERE  organizador = 1 AND uid_empresa = ". $this->getUID();
        $uid = $this->db->query($sql, 0, 0);
        $uid = ( true === is_countable($uid) && count($uid)>1 ) ? reset($uid) : $uid;
        if( is_numeric($uid) ){
            return new agrupamiento($uid);
        }
        return false;
    }

    /** ESTABLECE PARA ESTE CLIENTE Y DADO UN MODULO, EL AGRUPAMIENTO QUE ACTUARÁ COMO ORGANIZADOR **/
    public function establecerOrganizador(agrupamiento $agrupamiento){
        $sql = "UPDATE ". TABLE_AGRUPAMIENTO ." SET organizador = 1 WHERE uid_agrupamiento = " .$agrupamiento->getUID(). " AND uid_empresa = ". $this->getUID();
        return $this->db->query($sql);
    }

    /** QUITA CUALQUIER AGRUPAMIENTO QUE ACTUE COMO ORGANIZADOR PARA EL MODULO DADO **/
    public function quitarOrganizador(agrupamiento $agrupamiento){
        $sql = "UPDATE ". TABLE_AGRUPAMIENTO ." SET organizador = 0 WHERE uid_agrupamiento = " .$agrupamiento->getUID(). " AND uid_empresa = ". $this->getUID();
        return $this->db->query($sql); // eliminamos el otro organizador
    }


    /** NOS RETORNA UN ARRAY CON TODOS LOS OBJETOS EMPRESAS ENTERPRISE EXISTENTES*/
    public static function getEnterpriseCompanies(){
        $result = db::get("SELECT uid_empresa FROM ". TABLE_EMPRESA ." WHERE is_enterprise = 1 ORDER BY nombre", "*", 0, "empresa");
        return new ArrayObjectList($result);
    }

    /** Empresas Activas en los ultimos 150 dias (últimos 5 meses) */
    public static function getActiveCompanies ($sqlOptions = []) {

        $sql = "SELECT uid_empresa FROM ". TABLE_EMPRESA ." empresa
        INNER JOIN ". TABLE_PERFIL ." using(uid_empresa)
        INNER JOIN ". TABLE_USUARIO ." using(uid_usuario)
        WHERE DATEDIFF(NOW(), FROM_UNIXTIME(fecha_ultimo_acceso)) < 150";

        if (isset($sqlOptions['where'])) {
            $sql .= " AND {$sqlOptions['where']}";
        }

        $sql .= " GROUP BY uid_empresa ORDER BY empresa.nombre";


        $result = db::get($sql, "*", 0, "empresa");
        return new ArrayObjectList($result);
    }

    public function isActive() {
        $sql = "SELECT uid_empresa FROM ". TABLE_EMPRESA ." empresa
        INNER JOIN ". TABLE_PERFIL ." using(uid_empresa)
        INNER JOIN ". TABLE_USUARIO ." using(uid_usuario)
        WHERE DATEDIFF(NOW(), FROM_UNIXTIME(fecha_ultimo_acceso)) < 365
        AND empresa.uid_empresa = {$this->getUID()}";

        $result = db::get($sql, "*", 0, "empresa");
        $activeCompany = new ArrayObjectList($result);

        return count($activeCompany) > 0;
    }

    /** INSTANCIAR UNA EMPRESA DESDE EL CIF*/
    static public function fromCif($cif){
        $db = db::singleton();
        $sql = "SELECT uid_empresa FROM ". TABLE_EMPRESA ." WHERE cif = '". db::scape($cif) ."'";

        $uidEmpresa = $db->query($sql, 0, 0);
        if( is_numeric($uidEmpresa) ){
            return new self($uidEmpresa);
        } else {
            return false;
        }
    }

    /* Dado una empresa cliente, el nivel de recursividad, con estos datos y los que nos llegan por parámetro calculamos el espacio usado
       en disco duro que está utilizando esa empresa a través de sus documentos

                PARAMETROS:
                        - $usuario - El usuario queve
                        - $eliminadas [ false = activos , true = solo en papelera, null = todos ]
                        - $vigentes [ true = vigentes , false = historicos ]

                Por defecto muestra el tamaño ocupado de los documentos vigentes activos.
    */
    public function getEspacioUsado($usuario, $eliminadas = false, $vigentes = true)
    {
        set_time_limit(0);
        $db = db::singleton();
        $elementos = array();
        $totalEspacio = 0;

        $recursividad = $this->maxRecursionLevel();
        if ($this->esCorporacion()) {
            $recursividad++;
        }

        $subcontratas = $this->obtenerEmpresasInferioresMasActual($eliminadas, false, $usuario, $recursividad);
        $listEmpresas = $elementos["empresa"] = $subcontratas->toComaList();


        //rellenamos la lista de empleados a partir de la lista de empresas
        $sqlEmpleados = "SELECT `uid_empleado` FROM ".TABLE_EMPLEADO."_empresa WHERE uid_empresa IN ({$listEmpresas})  GROUP BY uid_empleado";
        if ($datos = $db->query($sqlEmpleados, "*", 0)) {
            $listaEmpleados = array_unique($datos);
            $elementos["empleado"] = implode(",", $listaEmpleados);
        }

        //rellenamos la lista de maquinas a partir de la lista de empresas
        $sqlMaquinas = "SELECT uid_maquina FROM ".TABLE_MAQUINA."_empresa WHERE uid_empresa IN ({$listEmpresas}) GROUP BY uid_maquina";
        if ($datos = $db->query($sqlMaquinas, "*", 0)) {
            $listMaquinas = array_unique($datos);
            $elementos["maquina"]= implode(",", $listaMaquinas);
        }

        $prefijo = ($vigentes === true) ? PREFIJO_ANEXOS : PREFIJO_ANEXOS_HISTORICO;
        foreach ($elementos as $modulo => $lista) {
            if (count($lista)) {
                // si estado es true, tratamos los archivos vigentes
                //calculamos el espacio en disco de documentos  de las empresas
                $sqlArchivoDocs = "SELECT archivo FROM {$prefijo}{$modulo} WHERE uid_{$modulo} IN ({$lista})";
                // si activo es true, tratamos los archivos activos
                if ($eliminadas === false) {
                    $sqlExtra = " AND uid_documento_atributo in (
                        SELECT uid_documento_atributo
                        FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
                        WHERE uid_elemento_destino IN ({$lista}) AND uid_modulo_destino =".util::getModuleId($modulo)."
                    )";
                    $sqlArchivoDocs .= $sqlExtra;
                }


                $resultset = $db->query($sqlArchivoDocs);
                if ($num = $db->getNumRows($resultset)) {
                    while ($row = db::fetch_array($resultset, MYSQLI_NUM)) {
                        $path = DIR_FILES . reset($row);
                        if (is_readable($path)) {
                            $totalEspacio += filesize($path);
                        }
                    }
                }

            }
        }

        return $totalEspacio;
    }

    /** ENVIAR EL EMAIL UTILIZANDO UNOS PARAMETROS CONCRETOS
            $this - Empresa donde se enviará el email a su contacto principal

            $asunto - string que queremos utilizar como Asunto del email
            $tpl - tpl smarty que se enviará en el email(Ejmp. feedback). Tiene que estar en la carpeta tpl/email. (Podemos utilizar ruta relativa como /solicitud/nuestratpl)
            $params - array donde cada par indice valor es el nombre de la variable y su valor para smarty. Ejmpl array ("request" => $this);
            $logParams - Formato array que se utiliza en al calse log. Ejmpl array('empresasolicitud','email solicitud',$usuario->getUserVisibleName());
            $plantillaContactos - En caso de incluir un objeto plantillaemail, se obtendrán los contactos pertinentes utilizando la plantilla dada
            $contacsEmail - Especificar a que emails mandamos el correo. Array de strings.
            $blackListEmail - Emails where we do not want to send
    */

    public function sendEmailWithParams($asunto, $tpl, array $params, array $logParams, $plantillaContactos = null, array $contacsEmail = null, ArrayObjectList $blackListEmail = null)
    {
        set_time_limit(0);
        $contacts = array();
        $template = new Plantilla();
        $status = false;

        if ($logParams) {
            $log = log::singleton();
            $method = array($log,'info');
            call_user_func_array($method, $logParams);
        }

        foreach ($params as $key => $value) {
            $template->assign($key, $value);
        }

        try {
            if ($contacsEmail) {
                //Send to specific emails addresses. Not contacts
                $lang = $this->getCountry()->getLanguage();
                $status = $this->sendEmail($contacsEmail, $template, $asunto, $lang, $tpl);

            } else {
                if (isset($plantillaContactos)) {
                    $contacts = $this->obtenerContactos($plantillaContactos);
                } else if ($contacto = $this->obtenerContactoPrincipal()) {
                    $contacts = array($contacto);
                }

                foreach ($contacts as $contacto) {
                    if ($address = $contacto->obtenerEmail()) {
                        if (isset($blackListEmail) && $blackListEmail->contains($address)) {
                            continue;
                        }
                        $nombreContacto = $contacto->obtenerDato('nombre');
                        $lang = $contacto->getLanguage();
                        $template->assign('nombreContacto', $nombreContacto);
                        $template->assign('locale', $lang);
                        $status = $this->sendEmail($address, $template, $asunto, $lang, $tpl);
                        if (!$status) {
                            if ($logParams) {
                                $log->resultado("error $status", true);
                            }
                            return false;
                        }
                        if (isset($params['invoice'])) {
                            $invoice = $params['invoice'] instanceof invoice ? $params['invoice']->getUID() : $params['invoice'];
                            $contacto->writeLogUI(
                                logui::ACTION_AVISOEMAIL_VALIDACION,
                                "invoice = {$invoice}",
                                null,
                                $this
                            );
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($logParams) {
                $log->resultado("error $status", true);
            }

            // almost the 90% of calls to this method are not under try-catch
            // and because this is legacy code, I'll try to find errors
            // rather than fix the code problem itself.

            // I'm going to comment the exception and writing proper log
            // throw new Exception($status);
            error_log("can't send email to {$e->getMessage()} [{$e->getTraceAsString()}]");
            return false;
        }

        if ($logParams) {
            if ($status) {
                $log->resultado("ok ", true);
            } else {
                $log->resultado("error $status", true);
            }
        }


        return true;
    }

    /*  Auxiliary method to refactor on sendEmailWithParams
        To send an email, use sendEmailWithParams

        address: email address or array of addreses
        template: name of template we are sending
        subject: subject of the email
        log: logging the action send email
        $lang: langauage of the email
        $tpl: name of the template, must be on /email
    */
    protected function sendEmail ($address, $template, $subject, $lang, $tpl) {

        if (CURRENT_ENV == 'dev') {
            if (php_sapi_name() === 'cli') {
                $original = is_array($address) ? implode(', ', $address) : $address;
                print "Original email to company {$this->getUID()}: {$original}\n";
            }

            $address = email::$developers;
        }

        if (is_string($address)) {
            //Create an array from a string spliting by ";" or ",".
            //Similar to explode but spliting by two characters
            $address = array_filter(preg_split("/(;|,)/", trim($address)));
        }

        $template->assign("lang", $lang);
        $email = new email($address);
        $htmlPath ='email/'.$tpl.'.tpl';
        $html = $template->getHTML($htmlPath);
        $email->establecerContenido($html);

        if (!is_array($subject)) {
            $subject = $template->getString($subject, $lang);
        } else {
            $subject[0] = $template->getString($subject[0], $lang);
            $subject = call_user_func_array("sprintf", $subject);

        }
        $subject = (strlen($subject) > 103) ? substr($subject,0,100).'...' : $subject;
        $email->establecerAsunto($subject);

        $estado = $email->enviar();
        if( $estado !== true ){
            $estado = $estado && trim($estado) ? trim($estado) : $template('error_desconocido');
            $addresses = implode(',', $address);
            throw new Exception($addresses . "[{$estado}]");
        }

        return true;
    }

    public function getPartners(){
        $sql = "SELECT uid_partner FROM ". TABLE_EMPRESA_PARTNER ." WHERE uid_empresa = {$this->getUID()}";

        $result = db::get($sql, "*", 0, "empresa");
        return new ArrayObjectList($result);
    }

    public static function getAllPartners($language = null){
        $sql = "SELECT uid_empresa FROM ". TABLE_EMPRESA ." WHERE is_validation_partner = 1 ";

        if ($language) {
            $language = system::getIdLanguage($languages);
            $sql .= " AND validation_languages LIKE '%$language%' ";
        }

        $sql .= " ORDER BY nombre ";
        $result = db::get($sql, "*", 0, "empresa");
        return new ArrayObjectList($result);
    }


    public function isPartner($type = empresaPartner::TYPE_VALIDATOR){
        if ($type == empresaPartner::TYPE_VALIDATOR) {
            return (bool)$this->obtenerDato('is_validation_partner');
        }
        return false;

    }

    public function contractsNeedPayValidation (){
        $partner = empresaPartner::getEmpresasPartners($this, null, ['validation_payment_method' => 'all'], true);
        return ($partner && $partner instanceof empresaPartner);
    }


    public function getValidationPrice($isUrgent = false){
        $info = $this->getInfo();
        if ($isUrgent){
            return $info["partner_validation_price_urgent"];
        }
        return $info["partner_validation_price"];
    }

    public function getCost(){
        $cacheString = "getCost-empresa-{$this}";
        if( ($cost = $this->cache->getData($cacheString)) !== null ){
            return $cost;
        }

        $info = $this->getInfo();
        $cost = $info["cost"];
        $this->cache->addData($cacheString, $cost);
        return $cost;
    }


    /**
     * Get the company partner asociated to this company
     * @param  integer $language The documents language
     * @param  string  $type     The documents template type, see documento_atributo TEMPLATE_TYPE constants
     * @param  string  $target   The documents the partner validate, see empresaPartner VALIDATION_TARGET constants
     * @return false|empresa     Returns the partner company with the params data, false if there isn't any partner with this configuration
     */
    public function getPartner(
        $language = 2,
        $type = documento_atributo::TEMPLATE_TYPE_BOTH,
        $target = empresaPartner::VALIDATION_TARGET_ALL
    ){
        $validationDocs = ($type != documento_atributo::TEMPLATE_TYPE_BOTH) ? " validation_docs = '" . $type . "' " : "1";
        $table = TABLE_EMPRESA_PARTNER;

        $sql = "SELECT uid_partner
        FROM {$table}
        WHERE language = '{$language}'
        AND ($validationDocs OR validation_docs = 'both')
        AND uid_empresa = {$this->getUID()}
        AND (validation_target_docs = '{$target}' OR validation_target_docs = 'all')";

        $uid = $this->db->query($sql, 0, 0);

        if ($uid) {
            return new empresa($uid);
        }
        return false;
    }

    public function getEmpresaItem(solicitable $item){
        return empresaitem::getByCompanyItem($this, $item);
    }

    public function getValidationCompanies()
    {
        $validationConfigTable = TABLE_EMPRESA_PARTNER;
        $validationConfigHistoricTable = TABLE_EMPRESA_PARTNER . "_historic";

        $allValidationConfigTable = "(SELECT * FROM {$validationConfigTable}
        UNION ALL
        SELECT * FROM {$validationConfigHistoricTable}) AS all_validation_config";

        $sql = "SELECT uid_empresa
        FROM {$allValidationConfigTable}
        WHERE uid_partner = {$this->getUID()}
        GROUP BY uid_empresa";

        $companies = $this->db->query($sql, "*", 0, "empresa");

        if ($companies) {
            return new ArrayObjectList($companies);
        }

        return new ArrayObjectList();
    }


    public function getFeeAmount($total, \DateTimeImmutable $date)
    {
        $fees = Fees::createForAmountOnDate(Money::withDefaultCurrency($total), $date);
        return $fees->amount()->amount();
    }

    public function getLanguagesValidation(){
        $empresasPartner = $this->getEmpresasPartnersAsCompany();
        $languages = $empresasPartner->foreachCall("getLanguage")->unique();
        return $languages;
    }


    public function getAllItemsRequests(Iusuario $user = NULL, $filters = [])
    {
        $employees = $this->getEmployees($user);
        $machines = $this->getMachines($user);

        $items = $employees->merge($machines);
        $items[] = $this;

        $requests = new ArrayObjectList;

        foreach ($items as $item) {
            $itemRequests = $item->obtenerSolicitudDocumentos($user, $filters);
            $requests = $requests->merge($itemRequests);
        }

        return $requests;
    }


    /**
     * Get the fileId documents this partner company has to to validate
     * @param  usuario $usuario
     * @param  boolean $isUrgent
     * @param  boolean $limit
     * @param  boolean $count
     * @param  boolean $leaks    True if you want to get the documents with a not configured language
     * @param  boolean $random
     * @param  boolean $oldest
     * @return false|ArrayObjectList Returns the FileId objects that the partner has to validate, the arrayObjectList may be empty, returns false if the company can't validate documents
     */
    public function getDocumentsPendingValidation(
        usuario $usuario,
        $isUrgent = false,
        $limit = false,
        $count = false,
        $leaks = false,
        $random = false,
        $oldest = false,
        $clients = false,
        $reqtypeFilter = false,
        $getReqtypes = false
    ) {
        $empresasPartner = $this->getEmpresasPartnersAsPartner();

        if (!$empresasPartner) {
            return false;
        }

        if ($leaks) {
            $filters = $subFiltersAND = $subFiltersOR = array();
            foreach ($empresasPartner as $empresaPartner) {
                $subFilter = array();
                $subFilter[] = "uid_empresa_propietaria = {$empresaPartner->getCompany()->getUID()}";
                $custom = $empresaPartner->isCustom();
                $company = $empresaPartner->getCompany();
                if (isset($custom)) {
                    $subFilter[] .= "is_custom = $custom";
                }
                $languages = $company->getLanguagesValidation()->getArrayCopy();
                $subFilter[] = " anexo.language NOT IN (". implode(",", $languages) .")";
                $targetDocs = $empresaPartner->getValidationTarget();

                switch ($targetDocs) {
                    case empresaPartner::VALIDATION_TARGET_SELF:
                        $startComaList = $company->getStartList()->toComaList();
                        $subFilter[] = "anexo.uid_empresa_anexo IN ({$startComaList})";
                        break;

                    case empresaPartner::VALIDATION_TARGET_CONTRACTS:
                        $startComaList = $company->getStartList()->toComaList();
                        $subFilter[] = "anexo.uid_empresa_anexo NOT IN ({$startComaList})";
                        break;
                }

                $subFiltersOR[] = "(". implode(" AND ", $subFilter) .")";
            }

            if (isset($subFiltersOR) && count($subFiltersOR)) {
                $filters[] = "( ". implode(" OR ", $subFiltersOR). " )";
            }

        } else {
            $filters = $subFiltersAND = $subFiltersOR = array();
            foreach ($empresasPartner as $empresaPartner) {
                $company = $empresaPartner->getCompany();
                if ($clients && in_array($company->getUID(), $clients) === false) {
                    continue;
                }
                $subFilter = array();
                $subFilter[] = "da.uid_empresa_propietaria = {$empresaPartner->getCompany()->getUID()}";
                $custom = $empresaPartner->isCustom();
                if (isset($custom)) {
                    $subFilter[] .= "is_custom = $custom";
                }

                $subFilter[] = "anexo.language = {$empresaPartner->getLanguage()}";
                $targetDocs = $empresaPartner->getValidationTarget();

                switch ($targetDocs) {
                    case empresaPartner::VALIDATION_TARGET_SELF:
                        $startComaList = $company->getStartList()->toComaList();
                        $subFilter[] = "anexo.uid_empresa_anexo IN ({$startComaList})";
                        break;

                    case empresaPartner::VALIDATION_TARGET_CONTRACTS:
                        $startComaList = $company->getStartList()->toComaList();
                        $subFilter[] = "anexo.uid_empresa_anexo NOT IN ({$startComaList})";
                        break;
                }

                $subFiltersOR[] = "(". implode(" AND ", $subFilter) .")";
            }

            // When there is a client filter active but it is not corresponded to this partner
            if ($clients && 0 === count($subFiltersOR)) {
                return new ArrayObjectList();
            }

            if (isset($subFiltersOR) && count($subFiltersOR)) {
                $filters[] = "( ". implode("\n OR ", $subFiltersOR). " )";
            }

            if (!$random) {
                $filters[] = " is_urgent = ".(int) $isUrgent;
            }
        }

        if ($random) {
            $filters[] = " ((estado = 2 OR estado = 4) OR (estado != 2 AND reverse_status != 1)) ";
            $filters[] = " val.uid_usuario != {$usuario->getUID()} ";
        } else {
            $filters[] = "(screen_uid_usuario IS NULL OR screen_uid_usuario = {$usuario->getUID()})";
            $filters[] = " (estado = 1 OR (estado = 2 AND reverse_status = 1)) ";
        }

        if (false !== $reqtypeFilter && count($reqtypeFilter)) {
            $reqtypeComaList = implode(', ', $reqtypeFilter);
            $filters[] = "uid_documento IN ({$reqtypeComaList})";
        }

        $where = implode(" AND ", $filters);


        // ---- Hasta aqui el where
        // ---- Ahora montamos el SELECT

        $moduloItems = solicitable::getModules();
        $unionPart = array();
        $removeUser = "";
        foreach ($moduloItems as $id => $modulo) {

            if ($random) {
                $removeUser = " INNER JOIN ". TABLE_VALIDATION_STATUS ." vs ON uid_anexo_{$modulo} = uid_anexo
                                INNER JOIN ". TABLE_VALIDATION ." val ON  val.uid_validation = vs.uid_validation";
            }

            if ($random) {
                $table = PREFIJO_ANEXOS ."$modulo";
                $tmpName = "agd_tmp.attachments_{$modulo}_". uniqid();
                $tmpAttachment = "CREATE TEMPORARY TABLE {$tmpName} LIKE {$table}";

                if (!$this->db->query($tmpAttachment)) {
                    error_log("Cant create temporary table {$tmpName}");
                    return new ArrayObjectList;
                }

                $fill = "INSERT INTO {$tmpName} SELECT * FROM {$table} ORDER BY uid_anexo_{$modulo} DESC LIMIT 1000";
                if (!$this->db->query($fill)) {
                    error_log("Cant fill temporary table {$tmpName}");
                    return new ArrayObjectList;
                }

                $table = $tmpName;
            } else {
                $attachmentLimit = $oldest ? "" : "LIMIT ".ATTACHMENTS_VALIDATION_LIMIT;

                $table = "(
                    SELECT fileId, uid_{$modulo}, uid_documento_atributo, uid_agrupador, uid_empresa_referencia, fecha_anexion, language, is_urgent, screen_time_seen, screen_uid_usuario, estado, uid_anexo_renovation, reverse_status, uid_empresa_anexo
                    FROM ". PREFIJO_ANEXOS ."$modulo anexo
                    WHERE (estado = 1 OR (estado = 2 AND reverse_status = 1))
                    ORDER BY uid_anexo_{$modulo} DESC
                    {$attachmentLimit}
                )";
            }

            $moduleTable = constant('TABLE_' . strtoupper($modulo));

            $sql = "
                SELECT fileId, '$modulo' as module, fecha_anexion, uid_documento
                FROM $table anexo
                INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." da
                ON da.uid_documento_atributo = anexo.uid_documento_atributo
                AND da.activo = 1
                AND da.descargar = 0
                INNER JOIN ". TABLE_DOCUMENTOS_ELEMENTOS ." de
                ON anexo.uid_documento_atributo = de.uid_documento_atributo
                AND anexo.uid_$modulo = de.uid_elemento_destino
                AND anexo.uid_empresa_referencia = de.uid_empresa_referencia
                AND anexo.uid_agrupador = de.uid_agrupador
                AND de.uid_modulo_destino = {$id}
                AND de.papelera = 0
                {$removeUser}
                WHERE 1
                AND fileId IS NOT NULL
                AND $where
            ";

            if ($id == util::getModuleId('empleado') || $id == util::getModuleId('maquina')) {

                $relationTable = constant('TABLE_' . strtoupper($modulo) . "_EMPRESA");

                $sql .= "
                    AND uid_{$modulo} IN (SELECT uid_{$modulo} FROM {$moduleTable} itable
                    INNER JOIN {$relationTable} relation using(uid_{$modulo})
                    WHERE
                        relation.uid_empresa = anexo.uid_empresa_anexo and relation.papelera = 0
                        AND itable.uid_{$modulo} = anexo.uid_{$modulo}
                    )";
            } else {
                $sql .= " AND uid_{$modulo} IN (SELECT uid_{$modulo} FROM {$moduleTable} itable WHERE itable.uid_{$modulo} = anexo.uid_{$modulo}) ";
            }

            $sql = "(SELECT fileId, '$modulo' as module, fecha_anexion, uid_documento FROM ($sql) as att GROUP BY fileId)";
            // --- we cant limit here if we want to order once
            // if (!$random && $limit === 1) $sql .= " LIMIT 1";

            $unionPart[] = $sql;
        }

        $sql = implode(" UNION ALL ", $unionPart);

        if ($count) {
            $sql = "SELECT count(fileId) FROM ($sql) as setUnion";
            return $this->db->query($sql, 0, 0);
        } elseif (true === $getReqtypes) {
            $sql = "SELECT uid_documento FROM ($sql) as setUnion GROUP BY uid_documento";
            $reqtypeList = $this->db->query($sql, '*', 0, 'documento');

            return new ArrayObjectList($reqtypeList);
        } else {
            $sql = "SELECT setUnion.fileId, setUnion.module FROM ($sql) as setUnion ";

            if ($random) {
                $sql .= " ORDER BY RAND() ";
            } else {
                $sql .= " ORDER BY fecha_anexion ASC ";
            }
        }

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        $result = $this->db->query($sql, true);
        // --- cuando la cola de validacion este vacia, miramos anexos que se hayan quedado "olvidados"
        if ($oldest === false && count($result) == 0 && $limit === 1) {
            return $this->getDocumentsPendingValidation($usuario, $isUrgent, $limit, $count, $leaks, $random, true, $clients, $reqtypeFilter, $getReqtypes);
        }

        $fileIds = array();
        foreach ($result as $elem) {
            $fileIds[] = new fileId($elem["fileId"], $elem["module"]);
        }

        return new ArrayObjectList($fileIds);
    }

    /*
     * Get the number of validation pending to audit
     */
    public function getDocumentsOfValidationsPendingAuditCount()
    {
        $sql = "SELECT COUNT(uid_validation)
        FROM agd_data.validation
        WHERE audit_status = 'pending'
        AND uid_partner = {$this->getUID()}";

        return $this->db->query($sql, 0, 0);
    }

    /**
     * Get the validation statuses of the first validation pending to audit
     * @param  usuario $usuario
     */
    public function getFirstValidationPendingAudit(
        usuario $usuario,
        $clientFilter = false,
        $reqtypeFilter = false
    ) {
        $filters = ["1 = 1"];

        if (false !== $clientFilter && count($clientFilter)) {
            $clientComaList = implode(', ', $clientFilter);
            $filters[] = "vs.uid_empresa_propietaria IN ({$clientComaList})";
        }

        if (false !== $reqtypeFilter && count($reqtypeFilter)) {
            $reqtypeComaList = implode(', ', $reqtypeFilter);
            $filters[] = "da.uid_documento IN ({$reqtypeComaList})";
        }

        $filtersCondition = implode(" AND ", $filters);

        $sql = "SELECT v.uid_validation
        FROM agd_data.validation_status vs
        INNER JOIN agd_data.validation v USING (uid_validation)
        INNER JOIN agd_docs.documento_atributo da USING (uid_documento_atributo)
        WHERE v.audit_status = 'pending'
        AND v.uid_partner = {$this->getUID()}
        AND (v.screen_audit_uid_usuario IS NULL OR v.screen_audit_uid_usuario = {$usuario->getUID()})
        AND {$filtersCondition}
        ORDER BY v.uid_validation
        LIMIT 1";

        $validations = $this->db->query($sql, "*", 0, "validation");

        if (0 === count($validations)) {
            return false;
        }

        return reset($validations);
    }

    public function allClientsPendingsAudit()
    {
        $sql = "SELECT DISTINCT vs.uid_empresa_propietaria
        FROM agd_data.validation_status vs
        INNER JOIN agd_data.validation v USING (uid_validation)
        WHERE v.audit_status = 'pending'
        AND v.uid_partner = {$this->getUID()}";

        $result = $this->db->query($sql, "*", 0, "empresa");

        if ($result) {
            return new ArrayObjectList($result);
        } else {
            return new ArrayObjectList;
        }
    }

    public function allReqtypesPendingAudit()
    {
        $sql = "SELECT DISTINCT da.uid_documento
        FROM agd_data.validation_status vs
        INNER JOIN agd_data.validation v USING (uid_validation)
        INNER JOIN agd_docs.documento_atributo da USING (uid_documento_atributo)
        WHERE v.audit_status = 'pending'
        AND v.uid_partner = {$this->getUID()}";

        $result = $this->db->query($sql, "*", 0, "documento");

        if ($result) {
            return new ArrayObjectList($result);
        } else {
            return new ArrayObjectList;
        }
    }

    public function getEmpresasPartnersAsCompany(){
        return empresaPartner::getEmpresasPartners($this);
    }


    public function getEmpresasPartnersAsPartner(){
        return empresaPartner::getEmpresasPartners(null, $this);
    }

    public function getValidatedCompanies () {
        $moduloItems = anexo::getModules();

        $unions = array();
        foreach ($moduloItems as $key => $modulo) {
            $modulo = str_replace ("anexo_", "", $modulo);
            $onClausure = (strstr($modulo, 'historico')) ?  "USING(uid_anexo)" : "ON uid_anexo_{$modulo} = uid_anexo";

            $sql = "
                SELECT uid_empresa_propietaria
                FROM (
                    SELECT uid_anexo FROM ". TABLE_VALIDATION ."
                    INNER JOIN ". TABLE_VALIDATION_STATUS ." USING(uid_validation)
                    WHERE uid_modulo = {$key} AND uid_partner = {$this->getUID()}
                ) as v
                INNER JOIN ". PREFIJO_ANEXOS ."$modulo $onClausure
                INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." using(uid_documento_atributo)
                GROUP BY uid_empresa_propietaria
            ";

            $unions[] = $sql;
        }


        $sql = "SELECT uid_empresa_propietaria FROM (". implode(' UNION ', $unions) .") as unions GROUP BY uid_empresa_propietaria";

        if ($list = $this->db->query($sql, '*', 0, 'empresa')) {
            return new ArrayObjectList($list);
        }

        return new ArrayObjectList;
    }


    public function getPendingInvoices(){
        return invoice::getPending($this);
    }

    public function getInvoicesWithoutCustom()
    {
        $sql = "SELECT uid_invoice
        FROM " . TABLE_INVOICE . "
        WHERE custom IS NULL
        AND uid_empresa = {$this->getUID()}
        ORDER BY date ASC";

        $invoices = $this->db->query($sql, "*", 0, "invoice");

        if ($invoices) {
            return new ArrayObjectList($invoices);
        }

        return new ArrayObjectList();
    }

    public function getLanguages(){
        $resultLanguages = array();
        $languages = explode(",",trim($this->obtenerDato("validation_languages")));
        foreach ($languages as $language) {
            $resultLanguages[$language] = $language;
        }
        return $resultLanguages;
    }


    public function hasInvoicesNotPayedFrameTime($force = false){

        if ($this->isEnterprise()) return false;
        $invoices = $this->getPendingInvoices();

        foreach ($invoices as $invoice) {
            $date = new DateTime($invoice->getSentDate());
            $now = new DateTime();
            $datediff = date_diff($now, $date);
            $insideTimeFrame = $datediff->days > invoice::TIME_FRAME_CLOSE_NOTIFICATION || $datediff->days > invoice::TIME_FRAME_REMINDER_PAYMENT;
            if ($insideTimeFrame || $force) return $invoice;
        }

        return false;

    }

    public function getPendingValidationsStatusNotInvoiced($limit = NULL){

        $db = db::singleton();
        $moduloItems = anexo::getModules();
        $sqlDate = ($limit) ? " AND date <= '". $limit ."'" : "";

        $setValidationStatus = new ArrayObjectList();
        foreach ($moduloItems as $moduleUID => $modulo) {

            $modulo = str_replace ("anexo_", "", $modulo);
            $onClausure = (strstr($modulo, 'historico')) ?  "vs.uid_anexo = anexo.uid_anexo" : "uid_anexo_{$modulo} = vs.uid_anexo";

            $sql = "SELECT uid_validation_status FROM  " .TABLE_VALIDATION. " val
                    INNER JOIN " .TABLE_VALIDATION_STATUS. " vs using(uid_validation)
                    INNER JOIN " .PREFIJO_ANEXOS. "$modulo anexo ON $onClausure
                    WHERE vs.amount!=0 AND vs.uid_empresa_payment = {$this->getUID()}
                    AND uid_validation_status NOT IN (SELECT uid_reference FROM " .TABLE_INVOICE_ITEM. ")
                    AND vs.uid_modulo = $moduleUID
                    AND vs.already_payed != 1
                    $sqlDate
                    ORDER BY uid_validation_status ASC";

            $partialValidationStatus = $db->query($sql, "*", 0, "validationStatus");
            if ($partialValidationStatus) $setValidationStatus = $setValidationStatus->merge($partialValidationStatus);
        }

        return $setValidationStatus;

    }

    public static function getCompaniesNotInvoiced(){

        $moduloItems = solicitable::getModules();
        $unionPart = array();

        foreach ($moduloItems as $modulo) {
                $unionPart[] = " SELECT vs.uid_empresa_payment, uid_validation_status FROM  " .TABLE_VALIDATION_STATUS. " vs  INNER JOIN
                " .PREFIJO_ANEXOS. "$modulo anexo ON uid_anexo_$modulo = uid_anexo ";
                $unionPart[] = " SELECT vs.uid_empresa_payment, uid_validation_status FROM  " .TABLE_VALIDATION_STATUS. " vs  INNER JOIN
                " .PREFIJO_ANEXOS_HISTORICO. "$modulo anexo using(uid_anexo) ";
        }

        if (isset($unionPart) && count($unionPart)) $sql = implode(" UNION ", $unionPart);

        $sql = "SELECT anexos.uid_empresa_payment, anexos.uid_validation_status
        FROM ($sql) as anexos
        LEFT OUTER JOIN ". TABLE_INVOICE_ITEM ."
        item on uid_reference = uid_validation_status
        INNER JOIN ". TABLE_EMPRESA ." e
        ON e.uid_empresa = anexos.uid_empresa_payment
        WHERE anexos.uid_empresa_payment != 0
        AND item.uid_invoice_item is null
        GROUP BY anexos.uid_empresa_payment";

        $companies = db::get($sql, "*", 0, "empresa");
        if ($companies) return new ArrayObjectList($companies);
        return new ArrayObjectList();
    }

    /**
     * Empieza por "OBTENER" para hacerlo compatible con list.php
     * @param array $SQLOptions
     * @return ArrayObjectList
     */
    public function obtenerInvoices($SQLOptions = [])
    {
        $count = false;
        $sort = false;
        $limit = false;
        $joins = false;
        $conditions = false;

        if (true === is_array($SQLOptions)) {
            $count = true === isset($SQLOptions['count']) && true === $SQLOptions['count'];
            if (true === isset($SQLOptions['sort'])) {
                $sort = $SQLOptions['sort'];
            }
            if (true === isset($SQLOptions['limit'])) {
                $limit = $SQLOptions['limit'];
            }
            if (true === isset($SQLOptions['unpaid'])) {
                $joins = " LEFT OUTER JOIN " . TABLE_TRANSACTION . " paypal using(custom)"
                    . " INNER JOIN agd_data.empresa empresa using (uid_empresa)";
                $conditions = " AND ((empresa.is_enterprise = 0"
                    . " AND empresa.activo_corporacion = 0"
                    . " AND empresa.corporation IS NULL"
                    . " AND paypal.uid_paypal IS NULL AND sent_date > '" . invoice::DATE_CREDIT_MEMO . "'"
                    . ") OR empresa.is_enterprise = 1"
                    . " OR empresa.activo_corporacion = 1"
                    . " OR empresa.corporation IS NOT NULL"
                    . " OR paypal.uid_paypal IS NOT NULL"
                    . ")";
            }
        }

        $field = $count ? "count(uid_invoice)" : "uid_invoice";

        $sql = "SELECT {$field} FROM " . TABLE_INVOICE;

        if (true === is_string($joins)) {
            $sql .= " {$joins}";
        }

        $sql .= " WHERE uid_empresa = {$this->getUID()}
        AND sent_date IS NOT NULL";

        if (true === is_string($conditions)) {
            $sql .= " {$conditions}";
        }

        if ($sort) {
            $sql .= " ORDER BY {$sort}";
        }

        if ($limit) {
            $sql .= " LIMIT {$limit[0]}, {$limit[1]}";
        }

        if ($count) {
            return $this->db->query($sql, 0, 0);
        }

        $invoices = $this->db->query($sql, "*", 0, "invoice");
        if ($invoices) return new ArrayObjectList($invoices);

        return new ArrayObjectList();
    }

    public function obtenerValidationPrices(){
        $clients = $this->obtenerEmpresasSolicitantes();
        $clients = $clients && count($clients) ? $clients->toComaList() : '0';

        $sql = "SELECT uid_empresa_partner FROM " .TABLE_EMPRESA_PARTNER. "
                WHERE
                (uid_empresa IN ($clients) AND uid_empresa != {$this->getUID()} AND validation_payment_method = '".empresaPartner::PAYMENT_ALL."')
                    OR
                (uid_empresa = {$this->getUID()} AND validation_payment_method = '".empresaPartner::PAYMENT_SELF."')

                GROUP BY uid_empresa, language";

        $empresasPartner = $this->db->query($sql, "*", 0, "empresaPartner");
        if ($empresasPartner) return new ArrayObjectList($empresasPartner);

        return new ArrayObjectList();
    }

    /**
     * Get the validation configuration
     * @return Array The list of distinct validation configurations in Array format
     */
    public function getValidationConfig() {
        $validationConfigTable = TABLE_EMPRESA_PARTNER;
        $paymentAllMethod = empresaPartner::PAYMENT_ALL;

        $sql = "SELECT uid_empresa_partner
        FROM {$validationConfigTable}
        WHERE uid_empresa = {$this->getUID()}
        AND validation_payment_method = '{$paymentAllMethod}'
        GROUP BY uid_empresa, language";

        $empresaPartner = $this->db->query($sql, "*", 0, "empresaPartner");

        if ($empresaPartner === false) {
            return [];
        }

        $validationConfig = new ArrayObjectList($empresaPartner);

        return $validationConfig->toArray();
    }

    public function getAVGTimeValidate ($isUrgent = false) {
        $isUrgent = (int)$isUrgent;
        $sql = "SELECT time FROM ". TABLE_AVG_TIME ." where is_urgent = {$isUrgent} and uid_partner = {$this->getUID()}";
        $avgTime = $this->db->query($sql, 0, 0);
        if ($avgTime === false) {
            error_log("error while retrieving the average time from partner [uid: {$this->getUID()}]");
            return false;
        }
        return $avgTime;
    }

    public function getLastInvoicedEmited(){
        $sql = "SELECT uid_invoice FROM " .TABLE_INVOICE. " WHERE uid_empresa = {$this->getUID()} AND sent_date IS NOT NULL ORDER BY date DESC Limit 1";
        $data = db::get($sql, 0, 0);

        if ($data) return new invoice($data);
        return false;
    }

    public function mustPayTaxes(){
        if (!$country = $this->getCountry())  {
            return false;
        }

        $countryId = $country->getUID();
        $notSpain = $countryId != pais::SPAIN_CODE;
        $province = $this->obtenerProvincia();
        $provinceWithoutTaxes = $province && in_array($province->getUID(), provincia::$provinciasSinIVA);
        $freeTaxes = $countryId == pais::SPAIN_CODE && $provinceWithoutTaxes;
        if ($freeTaxes || $notSpain) {
            return false;
        }

        return true;
    }


    public function getValidations($partner = null, $firstDate = null, $endDate = null)
    {
        $validationTable = TABLE_VALIDATION;
        $validationStatusTable = TABLE_VALIDATION_STATUS;
        $filters = [];

        if ($partner) {
            $filters[] = " uid_partner = {$partner->getUID()}";
        }

        if ($firstDate) {
            $filters[] = "date >= FROM_UNIXTIME($firstDate)";
        }

        if ($endDate) {
            $filters[] = "date <= FROM_UNIXTIME($endDate)";
        }

        $filterCondition = implode(" AND ", $filters);

        $validators = $partner->obtenerValidadores();
        $validatorsList = $validators && count($validators) ? $validators->toComaList() : '0';

        $sql = "SELECT SUM(CAST((1/num_anexos) AS DECIMAL(10, 6))) as count, uid_empresa_propietaria, language, uid_partner
        FROM {$validationTable}
        INNER JOIN {$validationStatusTable} USING(uid_validation)
        WHERE uid_empresa_propietaria = {$this->getUID()}
        AND {$filterCondition}
        AND uid_usuario IN ({$validatorsList})
        GROUP BY uid_empresa_propietaria, language
        ";

        $validationsUser = db::get($sql, true, false, "ArrayValidationStats");

        return new ArrayValidationStats($validationsUser, $this);
    }

    public function getCompaniesValidatedByPartner($firstDate = null, $endDate = null)
    {
        $cacheString = $this . "-" . __FUNCTION__ . "-" . $firstDate . "-" . $endDate;
        if (null !== ($dato = $this->cache->getData($cacheString))) {
            return ArrayObjectList::factory($dato);
        }

        $validationTable = TABLE_VALIDATION;
        $validationStatusTable = TABLE_VALIDATION_STATUS;

        $validators = $this->obtenerValidadores();
        $validatorsList = $validators && count($validators) ? $validators->toComaList() : '0';

        $sql = "SELECT DISTINCT(uid_empresa_propietaria)
        FROM {$validationTable}
        INNER JOIN {$validationStatusTable} USING(uid_validation)
        WHERE uid_usuario IN ({$validatorsList})
        AND uid_partner is NOT NULL
        AND uid_empresa_validadora = {$this->getUID()}
        AND uid_empresa_propietaria <> 0
        ";

        if ($firstDate) {
            $sql .=  " AND `date` >= FROM_UNIXTIME($firstDate)";
        }

        if ($endDate) {
            $sql .=  " AND `date` <= FROM_UNIXTIME($endDate)";
        }

        $companyList = db::get($sql, '*', 0, 'empresa');

        if (0 === count($companyList)) {
            return new ArrayObjectList;
        }

        $companies = new ArrayObjectList($companyList);

        $this->cache->set($cacheString, $companies);
        return $companies;
    }

    public function payForMyContracts() {
        return (bool)$this->obtenerDato("pay_for_contracts");
    }

    public function clientPayingForMe()
    {
        $limit = self::DEFAULT_DISTANCIA;
        $directClients = $this->obtenerEmpresasSuperiores();

        if (count($directClients) == 1) {
            $client = reset($directClients);

            while ($client instanceof empresa && !$client->payForMyContracts() && $limit >= 0) {
                $directClients = $client->obtenerEmpresasSuperiores();
                if (count($directClients) > 1) {
                    return false;
                }

                $client = reset($directClients);
                $limit--;
            }

            if ($client instanceof empresa && $client->payForMyContracts() && $client->isEnterprise()) {
                return $client;
            }
        }

        return false;
    }

    //Quadenor does not support the languages as we do.
    //So we use this method as a translate method
    //Speaking spanish countries -> spanish
    //France and Portugal -> frech and portuguese
    //the rest -> english
    public function getCompatibleLanguageQuaderno() {
        $country = $this->getCountry();
        $spanishSpeaking = pais::getCountriesSpanishSpeaking();
        if (array_key_exists($country->getUID(), $spanishSpeaking)) {
            return 'ES';
        } else {
            switch ($country->getUID()) {
                case pais::PORTUGAL_CODE:
                    return 'PT';
                    break;
                case pais::FRANCE_CODE:
                    return 'FR';
                    break;
                default:
                    return 'EN';
            }
        }

        return 'EN';
    }

    public function getInvitationsExpiringToday() {

        $sql = "SELECT uid_signin_request FROM " .TABLE_SIGNINREQUEST. "
                WHERE  (state = ". signinRequest::STATE_PENDING ." OR state = ". signinRequest::STATE_NOT_SENT .")
                AND (DATEDIFF(NOW(), FROM_UNIXTIME(date)) = ". signinRequest::DAYS_TO_EXPIRE ." OR DATEDIFF(NOW(), deadline_ok) = 0)
                AND uid_empresa = {$this->getUID()}";


        $invitations = $this->db->query($sql, "*", 0, 'signinRequest');
        if ($invitations) return new ArrayObjectList($invitations);
        return new ArrayObjectList();
    }

    public function countItems ($class, usuario $user = null, $network = true) {
        $indexList = (string) $this->app['index.repository']->getIndexOf(
            $class,
            $this->asDomainEntity(),
            $user->asDomainEntity(),
            $network
        );

        $num = count(
            explode(',', $indexList)
        );

        if ($network) {
            // minus us
            return $num - 1;
        }

        return $num;
    }

    public function countElements () {
        $numEmployes = $this->obtenerEmpleados(false, false, false, true);
        $numMachines = $this->obtenerMaquinas(false, false, false, true);
        return $numEmployes + $numMachines;
    }

    public function hasUser(usuario $user){
        $sql = "SELECT uid_usuario
                FROM ". TABLE_PERFIL ." INNER JOIN ". TABLE_USUARIO ." USING(uid_usuario)
                WHERE uid_empresa IN (".$this->getStartIntList().")
                AND uid_usuario = {$user->getUID()}
                GROUP BY uid_usuario";
        return (bool)$this->db->query($sql, 0, 0);
    }

    public function getPendingInvitations ($sqlOptions = []) {
        $signinTable = TABLE_SIGNINREQUEST;

        $states = [signinRequest::STATE_PENDING, signinRequest::STATE_NOT_SENT, signinRequest::STATE_CONFIGURATION];

        $count = isset($sqlOptions['count']) ? $sqlOptions['count'] : false;
        $limit = isset($sqlOptions['limit']) ? $sqlOptions['limit'] : false;
        $query = isset($sqlOptions['q']) ? utf8_decode(db::scape($sqlOptions['q'])) : false;
        $states = isset($sqlOptions['states']) ? $sqlOptions['states'] : $states;

        $primary = 'uid_signin_request';
        if ($count) {
            $primary = 'count(uid_signin_request)';
        }

        $sql = "SELECT $primary FROM $signinTable WHERE uid_empresa = {$this->getUID()}";

        if (count($states)) {
            $stateList = implode(',', $states);
            $sql .= " AND state IN ({$stateList})";
        }

        if ($query) {
            $sql .= " AND nombre LIKE '%{$query}%'";
        }

        $sql .= " ORDER BY date DESC";

        if ($count) {
            return $this->db->query($sql, 0, 0);
        }

        if ($limit) {
            $offset = $limit[0];
            $limit = $limit[1];
            $sql    .= " LIMIT $offset, $limit ";
        }

        $invitations = $this->db->query($sql, "*", 0, 'signinRequest');
        if ($invitations) return new ArrayObjectList($invitations);
        return new ArrayObjectList;
    }

    public function hasPendingInvitation (empresa $invitedCompany) {
        $sql = "SELECT uid_signin_request FROM " .TABLE_SIGNINREQUEST. "
        WHERE
        uid_empresa_invitada = {$invitedCompany->getUID()}
        AND uid_empresa = {$this->getUID()}
        AND (
            state = ". signinRequest::STATE_PENDING ."
            OR state = ". signinRequest::STATE_CONFIGURATION .
            " OR state = ". signinRequest::STATE_NOT_SENT .
        " ) ";

        if ($uid = $this->db->query($sql, 0, 0)) {
            return new signinRequest($uid);
        }
        return false;
    }

    public function getSignInRequest(empresa $companyInvited)
    {
        $invitationTable = TABLE_SIGNINREQUEST;
        $pendingStatus = signinRequest::STATE_PENDING;

        $sql = "SELECT uid_signin_request
        FROM {$invitationTable}
        WHERE uid_empresa_invitada = {$companyInvited->getUID()}
        AND uid_empresa = {$this->getUID()}
        AND state = {$pendingStatus}
        ORDER BY uid_signin_request DESC";

        $signinRequestId = $this->db->query($sql, 0, 0);

        if (!$signinRequestId) {
            return false;
        }

        $signinRequest = new signinRequest($signinRequestId);

        if (!$signinRequest instanceof signinRequest) {
            return false;
        }

        return $signinRequest;
    }

    public function updateSignInRequest(empresa $companyInvited, $state = signinRequest::STATE_ACCEPTED)
    {
        $signinRequest = $this->getSignInRequest($companyInvited);

        if (false === $signinRequest) {
            return false;
        }

        if ($state == signinRequest::STATE_ACCEPTED) {
            return $signinRequest->accept();
        } else {
            return $signinRequest->cancel();
        }
    }


    /***
       * Indicates if we have to show organizations marked as 'ondemand'
       *
       */
    public function canShowOnDemand () {
        return true;
    }

    /***
       * Indicates if we have to hide empty organizations
       *
       */
    public function filterEmptyOrganizations () {
        return false;
    }

    /***
       * Indicates the url to redirect the user if it needs to go to a payment page
       *
       */
    public function needsRedirectToPayment() {
        $haPagado = $this->hasPaid();
        $renewTime = $this->timeFreameToRenewLicense();
        $mustPay = $this->mustPay();
        $optPayment = $this->hasOptionalPayment();
        $url = false;
        $temporary = $this->isTemporary() || $this->hasTemporaryPayment();
        $invoice = $this->hasInvoicesNotPayedFrameTime(true);

        if ($temporary && $this->isPremium()) {
            $url = "/app/payment/temporary";
        } elseif ($haPagado && $mustPay && !$optPayment) {
            $url = "/app/payment/license";
        } elseif ($invoice instanceof invoice && $invoice->getDaysToClose() < 0) {
            $url = "/app/payment/invoice";
        } elseif ($haPagado && $renewTime && !$optPayment) {
            $url = "/app/payment/license";
        } elseif ($invoice instanceof invoice) {
            $url = "/app/payment/invoice";
        }

        return $url;
    }

    /***
       * Return true or false if the @param user can create groups on demand in the @param organization for $this
       *
       */
    public function canApplyOnDemand (agrupamiento $organization, Iusuario $user) {
        if (false === $organization->isOnDemand()) {
            return false;
        }

        $company = $user->getCompany();
        $startList = $company->getStartList();

        // on-demand only applies to us or to our direct contracts
        $notSubcontract = $company->compareTo($this) || $this->esContrata($startList);

        if ($notSubcontract && $organization->isHandledBy($startList)) {
            return true;
        }

        return false;
    }

    /** DATOS VISIBLES DE LAS EMPRESAS */
    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){

        if( $usuario instanceof usuario && $empresa = $usuario->getCompany() ){
            $camposExtra = $empresa->obtenerCamposDinamicos(1);
        }


        $arrayCampos = new FieldList();

        if ($modo != self::PUBLIFIELDS_MODE_PARTNER){
            $arrayCampos["nombre"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
        }

        if ($modo != self::PUBLIFIELDS_MODE_PARTNER) {

            $editCIF = ($modo == elemento::PUBLIFIELDS_MODE_EDIT && $usuario instanceof usuario && $usuario->esStaff()) || ($modo != elemento::PUBLIFIELDS_MODE_EDIT);

            if ($editCIF) {
                $arrayCampos["cif"] = new FormField(array("tag" => "input", "type" => "text"));
            } else {
                $arrayCampos["cif"] = new FormField(array("tag" => "span", "type" => "text"));
            }

        }

        switch( $modo ) {
            case elemento::PUBLIFIELDS_MODE_ENDEVE:
                $arrayCampos = new FieldList;
                $arrayCampos["endeve_id"]= new FormField(array("blank" => false));
                return $arrayCampos;
            break;

            case elemento::PUBLIFIELDS_MODE_SEARCH:
                $arrayCampos['nombre_comercial']= new FormField(array("tag" => "input",     "type" => "text"));
                return $arrayCampos;
            break;

            case elemento::PUBLIFIELDS_MODE_SYSTEM:
                $arrayCampos['date_last_summary']= new FormField(array());
                $arrayCampos['updated']= new FormField(array());
                $arrayCampos['is_transfer_pending']= new FormField(array());
                return $arrayCampos;
            break;

            case elemento::PUBLIFIELDS_MODE_EDIT: case elemento::PUBLIFIELDS_MODE_NEW: case elemento::PUBLIFIELDS_MODE_INIT:
                if ($modo == elemento::PUBLIFIELDS_MODE_INIT) {
                    $arrayCampos["cif"]["className"] = 'needcheck';
                }

                $arrayCampos['nombre_comercial'] = new FormField(array("tag" => "input",     "type" => "text"));
                $arrayCampos['representante_legal'] = new FormField(array("tag" => "input",     "type" => "text"));
                $arrayCampos["uid_pais"] = new FormField(array("tag" => "select", 'default' => 'Seleccionar',"data" => pais::obtenerTodos(), "objeto" => "pais" ));
                $arrayCampos['uid_provincia'] = new FormField( array('tag' => 'select',
                    'default' => 'Seleccionar',
                    'data' => provincia::obtenerTodos(),
                    "objeto" => "provincia",
                    "depends" => array("uid_pais", pais::SPAIN_CODE) ));

                $arrayCampos['uid_municipio'] = new FormField(array('tag' => 'select',
                    'default' => 'Seleccionar',
                    'data' => municipio::obtenerTodos(),
                    'objeto' => 'municipio',
                    "depends" => array("uid_pais", pais::SPAIN_CODE) ));

                $arrayCampos["direccion"] = new FormField(array("tag" => "textarea",    "type" => "text", "blank" => false));
                $arrayCampos["cp"] = new FormField(array("tag" => "input",  "type" => "text", "blank" => false));
                $arrayCampos["organizacion_preventiva[]"] = new FormField(array("tag" => "select",  "data" => organizacionPreventiva::getAll(),  "objeto" => "organizacionPreventiva", "depends" => array("uid_pais", pais::SPAIN_CODE)));
                $arrayCampos["requirements_origin_company_cloneables"] = new FormField(array("tag" => "input", "innerHTML" => "requirements_origin_company_cloneables", "type" => "checkbox",  "className" => "iphone-checkbox"));

                if ($modo == elemento::PUBLIFIELDS_MODE_EDIT) {
                    if ($usuario instanceof usuario && $objeto instanceof self && $usuario->getCompany()->compareTo($objeto)) {
                        $arrayCampos['kind'] = new FormField(array(
                            'tag' => 'select',
                            'default' => _("Choose one"),
                            'data' => self::getKindsSelect()
                        ));

                        if ($usuario instanceof usuario && $usuario->esStaff()){
                            $arrayCampos['uid_referrer'] = new FormField([
                                'tag' => 'select',
                                'data' => self::getReferrersSelect()
                            ]);
                        }
                    }

                    $convenios = empresa::obtenerConvenios();
                    if ( $convenios && is_traversable($convenios) ){
                        $convenio = array("tag" => "select", "type" => "text", "data" => $convenios, "others" => true, "default" => "Seleccionar");
                    } else {
                        $convenio = array("tag" => "input", "type" => "text");
                    }
                    $arrayCampos["convenio"] = new FormField($convenio);

                    if ( $usuario instanceof usuario && $usuario->esStaff() ){
                        $arrayCampos['skin'] = new FormField(array("tag" => "span" ));
                        $arrayCampos['pago_aplicacion'] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox" ));

                        if ($objeto instanceof empresa && !$objeto->perteneceCorporacion()) {
                            $arrayCampos['activo_corporacion'] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox"));
                        }

                        $arrayCampos['receive_summary'] = new FormField(array('tag' => 'input', 'type' => 'checkbox', 'className' => 'iphone-checkbox'));
                    }

                }

                if ($modo == elemento::PUBLIFIELDS_MODE_NEW && $modo != elemento::PUBLIFIELDS_MODE_INIT) {
                    $arrayCampos['created'] = new FormField(array("tag" => "span", "date_format" => "%d/%m/%Y"));
                    $arrayCampos['kind'] = new FormField(array());
                    $arrayCampos['prevention_service'] = new FormField(array());
                }


                // No mostramos campos de la propia empresa si no somos nosotros mismos
                if ($modo == elemento::PUBLIFIELDS_MODE_EDIT && $usuario instanceof usuario && $objeto instanceof self){
                    $empresa = $usuario->getCompany();
                    if (!$empresa->getStartList()->contains($objeto)) {
                        $arrayCampos = new FieldList;
                    }
                }

                if ($modo != elemento::PUBLIFIELDS_MODE_INIT &&  $modo != elemento::PUBLIFIELDS_MODE_NEW) {
                    if (isset($camposExtra) && is_traversable($camposExtra) && count($camposExtra)) {
                        foreach($camposExtra as $campoExtra){
                            $arrayCampos[ $campoExtra->getFormName() ] = new FormField(array(
                                "tag" => $campoExtra->getTag(),
                                "type" => $campoExtra->getFieldType(),
                                "uid_campo" => $campoExtra->getUID(),
                                "data" => $campoExtra->getData(),
                                "blank" => $campoExtra->obtenerDato("obligatorio") ? false : true
                            ));
                        }
                    }
                }

                if ($usuario instanceof usuario && $usuario->esStaff() && $modo != elemento::PUBLIFIELDS_MODE_NEW) {
                    $economics = $usuario->configValue("economicos");
                    $admin = $objeto instanceof empresa && $usuario->esAdministrador();

                    // --- solo si no pertenece a corporacion
                    if ($objeto instanceof empresa && !$objeto->perteneceCorporacion()) {
                        if ($economics) {
                            $arrayCampos["pago_no_obligatorio"] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox"));
                        }

                        if ($modo == elemento::PUBLIFIELDS_MODE_EDIT && $usuario->esStaff()) {
                            $arrayCampos["is_enterprise"] = new FormField(array("tag" => "input", "value" => 1, "type" => "checkbox", "className" => "iphone-checkbox"));
                        }
                    }



                    if ($admin) {
                        $arrayCampos['pay_for_contracts'] = new FormField(array('tag' => 'input', 'type' => 'checkbox', 'className' => 'iphone-checkbox', "info" => true));
                        $arrayCampos['is_validation_partner'] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox", "value" => 1));
                        $arrayCampos["has_custom_login"] = new FormField(array("tag" => "input", "value" => 1, "type" => "checkbox", "className" => "iphone-checkbox"));
                    }

                    if ($economics) {
                        $arrayCampos["invoice_periodicity"] = new FormField(array("tag" => "slider",    "type" => "text",   "match" => "^([0-6])$", "count" => "6" ,  "info" => true, "hr" => true));
                    }
                }

            break;
            case elemento::PUBLIFIELDS_MODE_QUERY:
                $arrayCampos["nombre"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                $arrayCampos["uid_empresa"] = new FormField(array("tag" => "span"));
            case elemento::PUBLIFIELDS_MODE_FOLDER:
            break;

            case elemento::PUBLIFIELDS_MODE_TAB:
                if ($objeto instanceof empresa) {
                    $pais = ($pais = $objeto->obtenerPais()) ? $pais->getUserVisibleName() : "";
                    $provincia = ($provincia = $objeto->obtenerProvincia()) ? $provincia->getUserVisibleName() : "";
                    $municipio = ($municipio = $objeto->obtenerMunicipio()) ? $municipio->getUserVisibleName() : "";


                    $arrayCampos["direccion"] = new FormField(array("tag" => "input", "type" => "text"));
                    $arrayCampos["uid_municipio"] = new FormField(array("tag" => "input", "innerHTML" => utf8_decode($municipio), "nodb" => true));
                    $arrayCampos["uid_provincia"] = new FormField(array("tag" => "input", "innerHTML" => utf8_decode($provincia), "nodb" => true));
                    $arrayCampos["cp"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                    $arrayCampos["uid_pais"] = new FormField(array("tag" => "input", "innerHTML" => utf8_decode($pais), "nodb" => true));
                }

                $arrayCampos['created'] = new FormField(array("tag" => "span", "date_format" => "%d/%m/%Y"));

            break;
            case elemento::PUBLIFIELDS_MODE_IMPORT:
                $arrayCampos["localidad"] = new FormField(array("tag" => "input",   "type" => "text", "blank" => false));
                $arrayCampos["provincia"] = new FormField(array("tag" => "input",   "type" => "text", "blank" => false));
                $arrayCampos["direccion"] = new FormField(array("tag" => "textarea", "type" => "text", "blank" => false));
                $arrayCampos["cp"] = new FormField(array("tag" => "input",   "type" => "text", "blank" => false));
            break;

            case elemento::PUBLIFIELDS_MODE_DELTA:
                $arrayCampos = array('cif','nombre','direccion','uid_provincia','uid_municipio','cp');

            break;

            case self::PUBLIFIELDS_MODE_LICENSE:
                $arrayCampos["license"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
                $arrayCampos['is_transfer_pending'] = new FormField(array());
            break;

            case self::PUBLIFIELDS_MODE_PARTNER: case self::PUBLIFIELDS_MODE_PARTNER_EDIT:
                $arrayCampos['partner_validation_price'] = new FormField(array("tag" => "input",     "type" => "text"));
                $arrayCampos['partner_validation_price_urgent'] = new FormField(array("tag" => "input",     "type" => "text"));
                $arrayCampos['validation_languages[]'] = new FormField(array(
                            "tag" => "select", "data" => system::getLanguages(), "list" => true
                        ));
                $arrayCampos['cost'] = new FormField(array("tag" => "input",     "type" => "text"));

                break;
        }

        return $arrayCampos;
    }


    public function getLocale()
    {
        $country = $this->getCountry();

        return $country->getLocale();
    }

    public static function getKindsSelect()
    {
        return [
            \Dokify\Domain\Company\Company::KIND_COMPANY                    => _("Company (Ltd. or Inc.)"),
            \Dokify\Domain\Company\Company::KIND_COMPANY_NO_EMPLOYEES       => _("Company (Ltd. or Inc.) without employees"),
            \Dokify\Domain\Company\Company::KIND_SELF_EMPLOYED              => _("Self employed"),
            \Dokify\Domain\Company\Company::KIND_SELF_EMPLOYED_EMPLOYEES    => _("Self employed with employees"),
            \Dokify\Domain\Company\Company::KIND_TEMP_AGENCY                => _("Temping agency"),
            \Dokify\Domain\Company\Company::KIND_LIMITED_PARTNERSHIP        => _("Limited Partnership"),
        ];
    }

    public static function getReferrersSelect()
    {
        $db = db::singleton();
        $tableReferrer = DB_DATA . ".referrer";
        $referrerSelect = [];

        $sql = "SELECT uid_referrer, name
        FROM {$tableReferrer}
        ";

        $referrerList = $db->query($sql, true);

        $referrerSelect['NULL'] = _('select');

        foreach ($referrerList as $referrerRow) {
            $key = $referrerRow['uid_referrer'];
            $referrerSelect[$key] = $referrerRow['name'];
        }

        return $referrerSelect;
    }

    /** ALIAS PARA SHOW COLUMNS DE ESTA TABLA **/
    public function getTableFields()
    {
        return array(
            array("Field" => "uid_empresa",                         "Type" => "int(10)",        "Null" => "NO",     "Key" => "PRI", "Default" => "",                    "Extra" => "auto_increment"),
            array("Field" => "endeve_id",                           "Type" => "varchar(60)",    "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "pago_no_obligatorio",                 "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "nombre",                              "Type" => "varchar(100)",   "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "nombre_comercial",                    "Type" => "varchar(200)",   "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "representante_legal",                 "Type" => "varchar(512)",   "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "cif",                                 "Type" => "varchar(20)",    "Null" => "NO",     "Key" => "UNI", "Default" => "",                    "Extra" => ""),
            array("Field" => "pais",                                "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "1",                   "Extra" => ""),
            array("Field" => "uid_pais",                            "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "1",                   "Extra" => ""),
            array("Field" => "direccion",                           "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "localidad",                           "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "provincia",                           "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "cp",                                  "Type" => "int(8)",         "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "accion",                              "Type" => "timestamp",      "Null" => "NO",     "Key" => "",    "Default" => "CURRENT_TIMESTAMP",   "Extra" => ""),
            array("Field" => "updated",                             "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "uid_provincia",                       "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_municipio",                       "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "convenio",                            "Type" => "varchar(255)",   "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "created",                             "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "aviso_caducidad_subcontratas",        "Type" => "int(1)",         "Null" => "YES",    "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "color",                               "Type" => "varchar(10)",    "Null" => "YES",    "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "email",                               "Type" => "varchar(255)",   "Null" => "YES",    "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "is_enterprise",                       "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "logo",                                "Type" => "tinytext",       "Null" => "YES",    "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "skin",                                "Type" => "varchar(300)",   "Null" => "NO",     "Key" => "",    "Default" => "dokify",              "Extra" => ""),
            array("Field" => "lang",                                "Type" => "varchar(2)",     "Null" => "NO",     "Key" => "",    "Default" => "es",                  "Extra" => ""),
            array("Field" => "activo_corporacion",                  "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "pago_aplicacion",                     "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "receive_summary",                     "Type" => "int(1)",         "Null" => "YES",    "Key" => "",    "Default" => "1",                   "Extra" => ""),
            array("Field" => "date_last_summary",                   "Type" => "int(16)",        "Null" => "YES",    "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "license",                             "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "is_validation_partner",               "Type" => "int(1)",         "Null" => "YES",    "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "partner_validation_price",            "Type" => "float",          "Null" => "YES",    "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "partner_validation_price_urgent",     "Type" => "float",          "Null" => "YES",    "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "validation_languages",                "Type" => "varchar(250)",   "Null" => "YES",    "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "cost",                                "Type" => "float",          "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "pay_for_contracts",                   "Type" => "int(1)",         "Null" => "YES",    "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "invoice_periodicity",                 "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "1",                   "Extra" => ""),
            array("Field" => "req_attached",                        "Type" => "int(9)",         "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "req_validated",                       "Type" => "int(9)",         "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "req_rejected",                        "Type" => "int(9)",         "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "req_expired",                         "Type" => "int(9)",         "Null" => "NO",     "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "is_transfer_pending",                 "Type" => "int(1)",         "Null" => "YES",    "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "kind",                                "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "min_app_version",                     "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "2",                   "Extra" => ""),
            array("Field" => "prevention_service",                  "Type" => "varchar(20)",    "Null" => "YES",    "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "is_idle",                             "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "corporation",                         "Type" => "int(11)",        "Null" => "YES",    "Key" => "",    "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_referrer",                        "Type" => "int(11)",        "Null" => "YES",    "Key" => "MUL", "Default" => "",                    "Extra" => ""),
            array("Field" => "requirement_count",                   "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "invoice_count",                       "Type" => "int(11)",        "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "is_referrer",                         "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "uid_manager",                         "Type" => "int(11)",        "Null" => "YES",    "Key" => "MUL", "Default" => "",                    "Extra" => ""),
            array("Field" => "is_self_controlled",                  "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "1",                   "Extra" => ""),
            array("Field" => "has_custom_login",                    "Type" => "int(1)",         "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
            array("Field" => "header_img",                          "Type" => "tinytext",       "Null" => "YES",    "Key" => "",    "Default" => "",                    "Extra" => ""),            array("Field" => "uid_manager",                         "Type" => "int(11)",        "Null" => "YES",    "Key" => "MUL", "Default" => "",                    "Extra" => ""),
            array("Field" => "uid_referrer",                        "Type" => "int(11)",        "Null" => "YES",    "Key" => "MUL", "Default" => "",                    "Extra" => ""),
            array("Field" => "requirements_origin_company_cloneables", "Type" => "int(1)",      "Null" => "NO",     "Key" => "",    "Default" => "0",                   "Extra" => ""),
        );
    }

    /**
     * @param empresa $company
     * @param agrupador $group
     * @param bool $trash
     * @return bool
     */
    public function isContractVerifiedWithAssignmentVersion(empresa $company, agrupador $group, $trash = false)
    {
        if (true === $this->compareTo($company)) {
            return false;
        }

        $cacheKey = implode('-', [$this, 'esContrata', $company->getUID(), $group->getUID(), $trash]);

        if (null !== ($cacheData = $this->cache->getData($cacheKey))) {
            return $cacheData;
        }

        $database = DB_DATA;
        $tableCompany = TABLE_EMPRESA;
        $tableGroup = TABLE_AGRUPADOR;
        $uidModule = util::getModuleId("empresa");

        $sql = "SELECT er.uid_empresa_inferior
                FROM {$tableCompany}_relacion er
                INNER JOIN {$tableGroup}_elemento ae ON ae.uid_elemento = er.uid_empresa_inferior
                WHERE ae.uid_agrupador = {$group->getUID()}
                AND ae.uid_modulo = {$uidModule}
                AND ae.uid_elemento = {$this->getUID()}
                AND er.uid_empresa_superior = {$company->getUID()}
                AND (
                    ae.uid_agrupador_elemento NOT IN (SELECT av1.uid_assignment FROM {$database}.assignment_version av1)
                    OR ae.uid_agrupador_elemento IN (
                        SELECT av2.uid_assignment
                        FROM {$database}.assignment_version av2
                        WHERE av2.uid_assignment = ae.uid_agrupador_elemento
                        AND av2.uid_company IN (er.uid_empresa_superior, {$this->getUID()})
                    )
                )";

        if (true === is_bool($trash)) {
            $sql .= " AND papelera = " . (int)$trash;
        }

        $uid = $this->db->query($sql, 0, 0);
        $isContract = (bool)is_numeric($uid);

        $this->cache->set($cacheKey, $isContract);

        return $isContract;
    }

    /**
     * @param empresa $company
     * @param array $positionsSubcontrats
     * @param array $positionsCompany
     * @param agrupador $group
     * @return ArrayObjectList|mixed
     */
    public function getChainsVerifiedWithAssignmentVersion(
        empresa $company,
        $positionsSubcontrats = [2],
        $positionsCompany = [1],
        agrupador $group
    ) {
        $collection = new ArrayObjectList;
        $database = DB_DATA;
        $tableCompany = TABLE_EMPRESA;
        $tableGroup = TABLE_AGRUPADOR;
        $uidModule = util::getModuleId("empresa");

        if (true === $this->esCorporacion()) {
            $corporationCompanies = $this->obtenerEmpresasInferiores();

            foreach ($corporationCompanies as $corporationCompany) {
                $collection = $collection->merge(
                    $corporationCompany->getChainsVerifiedWithAssignmentVersion(
                        $company, $positionsSubcontrats, $positionsCompany, $group
                    )
                );
            }
        } else {
            $sql = "SELECT ec.uid_empresa_contratacion
                    FROM {$tableCompany}_contratacion ec
                    INNER JOIN {$tableGroup}_elemento ae ON ae.uid_elemento = {$company->getUID()}
                    WHERE uid_modulo = {$uidModule}
                    AND ae.uid_agrupador = {$group->getUID()}";

            foreach ($positionsCompany as $position) {
                $sqlCompany[] = "n{$position} = {$this->getUID()} ";
            }

            if (true === isset($sqlCompany)) {
                $sql .= " AND ( " . implode(' OR ', $sqlCompany) . " ) ";
            }

            foreach ($positionsSubcontrats as $position) {
                $prevPosition = $position - 1;
                $sqlSubcontract[] = "(n{$position} = {$company->getUID()} 
                    AND (ae.uid_agrupador_elemento NOT IN (
                        SELECT av1.uid_assignment FROM {$database}.assignment_version av1)
                    OR ae.uid_agrupador_elemento IN (
                        SELECT av2.uid_assignment FROM {$database}.assignment_version av2
                        WHERE av2.uid_assignment = ae.uid_agrupador_elemento
                        AND av2.uid_company = ec.n{$prevPosition}))
                    )";
            }

            if (true === isset($sqlSubcontract)) {
                $sql .= " AND  ( " . implode(' OR ', $sqlSubcontract) . " ) ";
            }

            $collection = $this->db->query($sql, '*', 0, 'empresaContratacion');

            if (false !== $collection) {
                $collection = new ArrayObjectList($collection);
            }
        }

        return $collection;
    }

    /**
     * @return array
     */
    public function getChainOfCompanies3Levels(): array
    {
        $sql = "SELECT uid_empresa 
                  FROM
                     (SELECT uid_empresa_inferior AS uid_empresa
                          FROM agd_data.empresa_relacion
                          INNER JOIN agd_data.empresa ON uid_empresa_inferior = uid_empresa
                          WHERE uid_empresa_superior = {$this->getUID()} AND papelera = 0
                            UNION
                          SELECT er2.uid_empresa_inferior AS uid_empresa
                          FROM agd_data.empresa_relacion er
                          INNER JOIN agd_data.empresa_relacion er2 ON er2.uid_empresa_superior = er.uid_empresa_inferior AND er2.papelera = 0
                          INNER JOIN agd_data.empresa ON er2.uid_empresa_inferior = uid_empresa
                          WHERE er.uid_empresa_superior = {$this->getUID()} AND er.papelera = 0
                            UNION
                          SELECT er3.uid_empresa_inferior AS uid_empresa
                          FROM agd_data.empresa_relacion er
                          INNER JOIN agd_data.empresa_relacion er2 ON er2.uid_empresa_superior = er.uid_empresa_inferior AND er2.papelera = 0
                          INNER JOIN agd_data.empresa_relacion er3 ON er3.uid_empresa_superior = er2.uid_empresa_inferior AND er3.papelera = 0
                          INNER JOIN agd_data.empresa ON er3.uid_empresa_inferior = uid_empresa
                          WHERE er.uid_empresa_superior = {$this->getUID()} AND er.papelera = 0
                            UNION
                          SELECT er4.uid_empresa_inferior AS uid_empresa
                          FROM agd_data.empresa_relacion er
                          INNER JOIN agd_data.empresa_relacion er2 ON er2.uid_empresa_superior = er.uid_empresa_inferior AND er2.papelera = 0
                          INNER JOIN agd_data.empresa_relacion er3 ON er3.uid_empresa_superior = er2.uid_empresa_inferior AND er3.papelera = 0
                          INNER JOIN agd_data.empresa_relacion er4 ON er4.uid_empresa_superior = er3.uid_empresa_inferior AND er4.papelera = 0
                          INNER JOIN agd_data.empresa ON er4.uid_empresa_inferior = uid_empresa
                          WHERE er.uid_empresa_superior = {$this->getUID()} AND er.papelera = 0
                     ) AS tbl";

        $companyUids = \db::singleton()->query($sql, true);

        return array_map('intval', array_column($companyUids, 'uid_empresa'));
    }

    /**
     * @param int $companyUid
     *
     * @return bool
     */
    public function companyExistsInChainOf3Levels(int $companyUid): bool
    {
        return in_array($companyUid, $this->getChainOfCompanies3Levels());
    }
}
