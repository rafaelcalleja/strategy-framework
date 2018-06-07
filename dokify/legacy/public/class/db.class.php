<?php

use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Doctrine\DBAL\Logging\SQLLogger;
use Dokify\Infrastructure\Application\Silex\Container;
use Dokify\Infrastructure\Persistence\Doctrine\Connection\DBALToMysqliConverter;

class db
{
    public $conexion;
    public $resultset;

    protected $master;
    protected $sqlerror;
    protected $errorno;
    protected $error;

    private static $instance;
    private static $readonly = null;

    /**
     * @var DBALConnection
     */
    private $doctrineConnection;

    /**
     * @var DBALToMysqliConverter
     */
    private $mysqliConverter;

    const MYSQL_USER_KEY = "mysqli.default_user";
    const MYSQL_PASS_KEY = "mysqli.default_pw";
    const MYSQL_HOST_KEY = "mysqli.default_host";

    const MASTER_PREFIX = "mysql.master_";
    const ON_SERVER_ERROR = "<script>location.href='/mantenimiento.html'</script>";

    const ERROR_FILE = '/var/log/nginx/mysql-error.log';

    /**
     * @var SQLLogger
     */
    private $SQLLogger;

    //constructor
    public function __construct($database = false)
    {
        $db = $database ? $database : "agd_core";

        $container = Container::instance();
        $this->doctrineConnection = $container['db'];
        $this->mysqliConverter = $container['db.mysqli_converter'];

        //conectamos
        $this->connect(null, null, null, $db);
    }

    public static function isReadOnly(){
        if (defined("DB_READ_ONLY")) {
            return DB_READ_ONLY;
        }
        $readonly = @strtolower(get_cfg_var('dokify.readonly'));
        if( $readonly === "true" || $readonly === "on" || $readonly == "1" ) return true;

        if( self::$readonly === NULL ){
            // Fuerza la conexión si no existe ya para determinar si es modo de solo lectura
            db::singleton();
        }

        return self::$readonly;
    }

    /** NOS RETORNA UNA INSTANCIA DEL OBJETO DB SIN DUPLICAR LA CONEXION */
    public static function singleton($database=false, $force=false){
        if( !isset(self::$instance) || $force){
            $c = __CLASS__;
            self::$instance = new $c($database);
        }
        return self::$instance;
    }


    public function run($filename, $host = NULL, $output = false, $code = false){
        $user = get_cfg_var(self::MYSQL_USER_KEY);
        $pass = get_cfg_var(self::MYSQL_PASS_KEY);
        $host = $host ? $host : get_cfg_var(self::MYSQL_HOST_KEY);

        if( !is_file($filename) || !is_readable($filename) ) return false;

        $command = 'mysql'
                . ' -h ' . $host
                . ' -u ' . $user
                . ' -p'  . $pass
                . " < {$filename}"
        ;

        if ($output) {
            exec($command, $out, $exitCode);

            if ($code) return $exitCode;
            return $out;
        } else {
            $command .= "  >/dev/null 2>&1 &";
            return shell_exec($command);
        }
    }



    /* PARA HACER QUERYS SIMPLES SIN NECESIDAD DE INSTANCIAR DIRECTAMENTE */
    public static function simple($sql)
    {
        $db = self::singleton();
        return $db->query($sql);
    }

    /** SI QUEREMOS CONECTARNOS A UNA BASE DE DATOS DIFERENTE */
    public function connect($host = null, $user = null, $pass = null, $db)
    {
        if (!is_callable("mysqli_connect")) {
            die("Es necesario instalar php5-mysql");
        }

        $this->conexion = $this->mysqliConverter->convert($this->doctrineConnection);

        if (null !== $user || null !== $pass || null !== $host) {
            $user = $user ? $user : get_cfg_var(self::MYSQL_USER_KEY);
            $pass = $pass ? $pass : get_cfg_var(self::MYSQL_PASS_KEY);
            $host = $host ? $host : get_cfg_var(self::MYSQL_HOST_KEY);

            $this->conexion = mysqli_init();
            mysqli_options($this->conexion, MYSQLI_OPT_LOCAL_INFILE, true);
            mysqli_real_connect($this->conexion, $host, $user, $pass, $db);

            if (!$this->conexion) {
                no_cache_die(self::ON_SERVER_ERROR);
            }
        }

        // Por ahora parece que la conexion es normal
        self::$readonly = false;

        // Si hay definido un servidor maestro vamos a conectar para escritura
        if( $masterHost = trim( get_cfg_var(self::MASTER_PREFIX . "host") ) ){
            $masterUser = trim( get_cfg_var(self::MASTER_PREFIX . "user") );
            $masterPass = trim( get_cfg_var(self::MASTER_PREFIX . "password") );

            if( $masterUser && $masterPass ){
                $this->master = mysqli_connect($masterHost, $masterUser, $masterPass, $db);
            }

            if( !$this->master ){
                self::$readonly = true;
            }
        }

        // self::$readonly = true; // testing

        return true;
    }


    public static function get ($sql, $param1 = false, $param2=false, $callback = false) {
        $db = db::singleton();
        return $db->query($sql, $param1, $param2, $callback);
    }

    /** HACER UNA QUERY DIRECTAMENTE
        Funcionamiento de los parametros:
            - 2 parametro = true -> convertir a array la salida
            - 2 y 3 parametro son numericos -> retornar de la fila "parametro1" la columna "parametro2"
            - 2 parametro = * y 3 parametro numerico -> retornar de todas las filas la columna "parametro2"
    */
    public function query( $sql, $param1 = false, $param2=false, $callback = false, $errors = true ){
        $isCacheableEnabled = false;

        if (!isset($this) || !$this instanceof db) {
            $db = db::singleton();
            return $db->query($sql, $param1, $param2, $callback);
        }

        $sql = trim($sql);

        if (null !== $this->SQLLogger()) {
            $this->SQLLogger()->startQuery($sql);
        }

        $sqlMethod = strtoupper(trim(substr($sql, 0, strpos($sql," "))));
        $sessionHandler = (isset($sqlMethod) && $sqlMethod === 'INSERT' && self::getTable($sql) == customSession::TABLE);
        $readOnlyMethods = array('SELECT', "SHOW");


        $conn = $this->conexion;

        // Si es un comando de escritura
        if( !in_array($sqlMethod, $readOnlyMethods) && !$sessionHandler){
            if (self::isReadOnly()) {
                return true;
            }

            if ($this->doctrineConnection instanceof MasterSlaveConnection) {
                $this->doctrineConnection->connect('master');
                $this->conexion = $this->mysqliConverter->convert($this->doctrineConnection);
                $conn = $this->conexion;
            }

            if( $this->master ){
                $conn = $this->master;
            }
        }


        $resultset = $this->resultset = mysqli_query($conn, $sql);
        if (!$resultset && $errors) {
            $this->sqlerror = $sql;
            $this->errorno = mysqli_errno($conn);
            $this->error = mysqli_error($conn);
        }

        //--- SI EL RESULTADO ES UN ERROR
        if (!$resultset) {
            $param1 = false;
            if ($errors) $this->error( mysqli_error($conn), $sql );

            if (null !== $this->SQLLogger()) {
                $this->SQLLogger()->stopQuery();
            }

            return false;
        }

        //--- SI SE QUIERE UN CAMPO ESPECIFICO DE TODAS LAS LINEAS
        if( $param1 === "*" && is_numeric($param2) ){
            $data = array();
            while($row = mysqli_fetch_row($resultset)){
                if( isset($row[$param2]) ){
                    $value = $row[$param2];
                    if( is_callable($callback) ){
                        $value = call_user_func( $callback, $value );
                    } elseif(is_string($callback)){
                        $value = new $callback($row[$param2]);
                    }
                    $data[] = $value;
                }
            }

            if( $isCacheableEnabled && count($data) ){
                if( in_array($sqlMethod, $availableMethodsOnReadOnlyMode ) ){
                    $this->historicoSQL[] = $sql;
                    $this->historicoData[] = $data;
                }
            }

            mysqli_free_result($resultset);

            if (null !== $this->SQLLogger()) {
                $this->SQLLogger()->stopQuery();
            }

            return $data;
        }

        //--- SI SE QUIERE TODAS LAS COLUMNAS DE UNA SOLA LINEA
        if( is_numeric($param1) && $param2 === "*" ){
            if( $param1 > 0 ) mysqli_data_seek($resultset, $param1);
            $data = mysqli_fetch_assoc($resultset);

            if( $isCacheableEnabled && count($data) ){
                if( in_array($sqlMethod, $availableMethodsOnReadOnlyMode ) ){
                    $this->historicoSQL[] = $sql;
                    $this->historicoData[] = $data;
                }
            }
            mysqli_free_result($resultset);

            if (null !== $this->SQLLogger()) {
                $this->SQLLogger()->stopQuery();
            }

            return $data;
        }

        //--- SI SE QUEIERE UN CAMPO ESPECIFICO DE UNA LINEA CONCRETA
        if( is_numeric($param1) && is_numeric($param2) ){
            if( $param1 > 0 ) mysqli_data_seek($resultset, $param1);

            $row = mysqli_fetch_row($resultset);

            // obtener columna
            $result = $row[$param2];

            if( $isCacheableEnabled && $result ){
                if( in_array($sqlMethod, $availableMethodsOnReadOnlyMode ) ){
                    $this->historicoSQL[] = $sql;
                    $this->historicoData[] = $result;
                }
            }
            mysqli_free_result($resultset);

            if (null !== $this->SQLLogger()) {
                $this->SQLLogger()->stopQuery();
            }

            return $result;
        }

        //--- SI SE CONVIERTE A ARRAY
        if ($param1 === true && $resultset) {

            $data = array();

            if ($resultset instanceof mysqli_result) {
                while ($row = mysqli_fetch_assoc($resultset)) {
                    $data[] = $row;
                }

                mysqli_free_result($resultset);
            }

            if (null !== $this->SQLLogger()) {
                $this->SQLLogger()->stopQuery();
            }

            return $data;
        } else {
            if (null !== $this->SQLLogger()) {
                $this->SQLLogger()->stopQuery();
            }

            return $resultset;
        }
    }

    /**
     * [getLastFromSet SQL string to get the last number in a set]
     * @param  [string] $field [the column name]
     * @return [string]        [the SQL ready to retrieve the last item in set]
     */
    public static function getLastFromSet ($field)
    {
        return "(SUBSTRING({$field}, (0-(LOCATE(',', REVERSE({$field}))-1))))";
    }


    /**
     * [getFirstFromSet SQL string to get the first number in a set]
     * @param  [string] $field [the column name]
     * @return [string]        [the SQL ready to retrieve the first item in set]
     */
    public static function getFirstFromSet ($field)
    {
        return "IF (LOCATE(',', {$field}), SUBSTRING({$field}, 1, LOCATE(',', {$field})-1), {$field})";
    }

    /**
     * Implodes all the fileds into a single string, only trythy values are joined
     * @return string the sql field
     */
    public static function implode($fields, $glue = ',')
    {
        if (count($fields) === 0) {
            return '';
        }

        $pieces = [];
        foreach ($fields as $field) {
            $pieces[] = "IF({$field}, {$field}, NULL)";
        }

        $fields = implode(', ', $pieces);
        $field  = "CONCAT_WS('{$glue}', {$fields})";

        return $field;
    }

    public static function getGroupPart($colname, $part=1, $concat=",", $sort = "1"){
        if( $part == 1 ){
            $field = "SUBSTRING_INDEX(group_concat($colname ORDER BY $sort SEPARATOR '$concat'),'$concat',1)";
        } else {
            $field = "REVERSE( SUBSTRING( REVERSE(SUBSTRING_INDEX(group_concat($colname ORDER BY $sort SEPARATOR '$concat'),'$concat',$part)), 1, LOCATE( REVERSE('$concat') ,REVERSE(SUBSTRING_INDEX(group_concat($colname ORDER BY $sort SEPARATOR '$concat'),'$concat',$part)))-1 ))";
        }
        return $field;
    }

    public function tableExists($table)
    {
        if (!isset($this)) {
            $db = db::singleton();
            return $db->tableExists($table);
        }

        $table = db::scape($table);
        if (false === strpos($table, '.')) {
            $sql = "SELECT 1 FROM information_schema.tables WHERE table_name = '{$table}'";
        } else {
            $tableExploded = new ArrayObject(explode('.', $table));
            $tblName = end($tableExploded);

            $tableExploded = new ArrayObject(explode('.', $table));
            $dbName = reset($tableExploded);
            $sql = "SELECT 1 FROM information_schema.tables WHERE table_name = '{$tblName}' AND table_schema = '{$dbName}'";
        }

        $resultset = $this->query($sql);
        return ($resultset && $this->getNumRows($resultset)) ? true : false;
    }

    public static function getMaxId($tabla){
        $db = self::singleton();
        $tblName = end( new ArrayObject( explode(".", $tabla) ) );
        $sql = "SELECT max(uid_".$tblName.") FROM $tabla";
        return $db->query($sql,0,0);
    }

    public static function getNextAutoincrement($tabla){
        $db = self::singleton();
        $tblEndName = new ArrayObject(explode(".", $tabla));
        $tblName = end($tblEndName);
        $dbReseteName = new ArrayObject(explode(".", $tabla));
        $dbName = reset($dbReseteName);
        $sql = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = '$tblName'";
        $auto = $db->query($sql, 0, 0);
        return $auto;
    }

    public static function getColumnNames($table){
        $sql = "SHOW COLUMNS FROM $table";
        $list = db::get($sql, "*", 0);
        return $list;
    }

    public function lastError(){
        return $this->error;
    }

    public function lastErrorNo(){
        return $this->errorno;
    }

    public function lastErrorString(){
        return "mysql_error_" . $this->errorno;
    }

    public function getAffectedRows(){
        $conn = $this->master ? $this->master : $this->conexion;
        return mysqli_affected_rows($conn);
    }

    public function getNumRows( $resultset = false ){
        $resultset = ( $resultset ) ? $resultset : $this->resultset;
        return mysqli_num_rows( $resultset );
    }

    public function getLastId(){
        $conn = $this->master ? $this->master : $this->conexion;
        return mysqli_insert_id($conn);
    }

    public static function getPrimaryKey($table){
        if( strpos($table, " ") !== false ){
            $aux = explode(" ", $table);
            $table = $aux[0];
        }
        $aux = explode(".", $table);
        $table = end($aux);
        return "uid_{$table}";
    }

    public static function getSeconds($table){
        $aux = explode(".", $table);

        $db = reset($aux);
        $table = end($aux);

        $sql = "
            SELECT TIMESTAMPDIFF(SECOND,create_time,NOW()) seconds
            FROM INFORMATION_SCHEMA.TABLES
            WHERE table_schema = '$db'
            AND table_name = '$table'
        ";

        return @db::get($sql, 0, 0);
    }

    public static function fetch_row($set){
        return mysqli_fetch_row($set);
    }

    public static function fetch_array($set, $mode = MYSQLI_BOTH)
    {
        return mysqli_fetch_array($set, $mode);
    }

    public static function getMethod($SQL) {
        return strtoupper(trim(substr($SQL, 0, strpos($SQL," "))));
    }

    public static function getTable($SQL) {
        $pos = NULL;
        switch ($method=self::getMethod($SQL)) {
            case "INSERT": $offset = 2; break;
            case "SHOW": $offset = 3; break;
            default:
                error_log("Developer!!!! You need to setup db::getTable form method {$method}");
                die("Internal error");
            break;
        }

        if ($offset) {
            $aux = explode(" ", $SQL);
            return $aux[$offset];
        }
    }

    public function info(){
        return mysqli_info($this->conexion);
    }

    public static function scape($str){
        $db = db::singleton();
        return mysqli_real_escape_string($db->conexion, $str);
    }

    public function close()
    {
        self::$instance = null;
        return mysqli_close($this->conexion);
    }

    protected function error($error, $sql) {
        $sql = preg_replace("/[\r\t]/","",$sql); //limpiamos los saltos
        $sql = preg_replace("/[\n]/"," ",$sql); //limpiamos los saltos

        $trace = implode(" <- ", trace(true));
        $errStr = "{$error} [{$sql}] {$trace}";

        if (is_writable(self::ERROR_FILE)) {
            file_put_contents(self::ERROR_FILE, $errStr."\n", FILE_APPEND);
        } else {
            error_log($errStr);
        }
    }

    public function putTableComment($table,$comment){
        $comment = json_encode($comment);
        $sql = "ALTER TABLE $table comment='".$comment."'";
        return $this->query($sql);
    }

    public function getTableComment($table)
    {
        $tableExploded = new ArrayObject(explode('.', $table));
        $dbName = reset($tableExploded);

        $tableExploded = new ArrayObject(explode('.', $table));
        $tblName = end($tableExploded);

        $sql = "SELECT TABLE_COMMENT
        FROM INFORMATION_SCHEMA.TABLES
        WHERE table_name = '{$tblName}'
        AND table_schema = '{$dbName}'";

        $comment = $this->query($sql, 0, 0);
        return json_decode($comment);
    }

    public static function valueNull($value){
        if (empty($value)) return 'NULL';
        return "'". db::scape($value) ."'";
    }

    /**
     * @return DBALToMysqliConverter
     */
    public function mysqliConverter(): DBALToMysqliConverter
    {
        return $this->mysqliConverter;
    }

    /**
     * @return DBALConnection
     */
    public function doctrineConnection(): DBALConnection
    {
        return $this->doctrineConnection;
    }

    /**
     * @return SQLLogger
     */
    public function SQLLogger()
    {
        return $this->SQLLogger;
    }

    /**
     * @param SQLLogger
     */
    public function setSQLLogger(SQLLogger $SQLLogger)
    {
        $this->SQLLogger = $SQLLogger;
    }

    /**
     * @param int $batchSize
     * @param string $sql
     *
     * @return array
     */
    public function paginateQuery(int $batchSize, string $sql): array
    {
        $normalizeSql = trim(preg_replace('/\s\s+/', ' ', $sql));

        $pattern = '/(SELECT)(.*)(FROM)/i';
        $replace = '${1} COUNT(*) ${3}';
        $countSql = preg_replace($pattern, $replace, $normalizeSql);

        $total = $this->query($countSql, 0, 0);

        $numPages = ceil($total / $batchSize);

        $queries = [];
        for ($x = 1; $x <= $numPages; $x++) {
            $page = $x - 1;
            $offset = $page * $batchSize;

            $queries[] = "{$sql} LIMIT {$batchSize} OFFSET {$offset}";
        }

        return $queries;
    }
}
