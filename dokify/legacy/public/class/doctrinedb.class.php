<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Dokify\Infrastructure\Application\Silex\Container;

class doctrinedb
{
    /**
     * @var string
     */
    const MASTER_PREFIX = "mysql.master_";

    /**
     * @var string
     */
    const MYSQL_USER_KEY = "mysqli.default_user";

    /**
     * @var string
     */
    const MYSQL_PASS_KEY = "mysqli.default_pw";

    /**
     * @var string
     */
    const MYSQL_HOST_KEY = "mysqli.default_host";

    /**
     * @var string
     */
    const ERROR_FILE = '/var/log/nginx/mysql-error.log';

    /**
     * @var null|self
     */
    private static $instance = null;

    /**
     * @var Connection|MasterSlaveConnection
     */
    private $connection;

    /**
     * @var string
     */
    private $sqlWithError;

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * @var Statement|null
     */
    private $lastStatement;

    /**
     * @var int
     */
    private $lastInsertId = 0;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $sql
     * @param bool|int|string $rowSelectExpression
     * @param bool|int|string $columnSelectExpression
     * @param bool|\Closure|string $rowTransform
     * @param bool $debug
     *
     * @return array|bool|Statement|null
     */
    public function query(
        string $sql,
        $rowSelectExpression = false,
        $columnSelectExpression = false,
        $rowTransform = false,
        $debug = true
    ) {
        if (true === $this->isReadOnly() && true === $this->isWriteOperation($sql)) {
            return true;
        }

        $statement = $this->prepareStatement($sql, $debug);

        if (false === $statement) {
            return false;
        }

        if (true === $rowSelectExpression) {
            return $this->fetchAssoc($statement);
        }

        if (true === is_numeric($rowSelectExpression) && $columnSelectExpression === '*') {
            return $this->getAllColumnsOfRowNumber(
                $statement,
                $rowSelectExpression
            );
        }

        if (true === is_numeric($rowSelectExpression) && true === is_numeric($columnSelectExpression)) {
            return $this->getColumnsNumberOfRowNumber(
                $statement,
                $rowSelectExpression,
                $columnSelectExpression
            );
        }

        if ($rowSelectExpression === '*' && true === is_numeric($columnSelectExpression)) {
            $rows = $this->fetchColumn(
                $statement,
                $columnSelectExpression
            );

            $rowsWithoutNulls = array_values(array_filter($rows));

            if (true === is_string($rowTransform)) {
                return array_map(function ($element) use ($rowTransform) {
                    return new $rowTransform($element);
                }, $rowsWithoutNulls);
            }

            if (true === is_callable($rowTransform)) {
                return array_map(function ($element) use ($rowTransform) {
                    return call_user_func($rowTransform, $element);
                }, $rowsWithoutNulls);
            }

            return $rowsWithoutNulls;
        }

        return $statement;
    }

    /**
     * @param string $sql
     * @param bool|int|string $rowSelectExpression
     * @param bool|int|string $columnSelectExpression
     * @param bool|\Closure|string $rowTransform
     * @param bool $debug
     *
     * @return array|bool|Statement|null
     */
    public static function get(
        string $sql,
        $rowSelectExpression = false,
        $columnSelectExpression = false,
        $rowTransform = false,
        $debug = true
    ) {
        return self::singleton()->query($sql, $rowSelectExpression, $columnSelectExpression, $rowTransform, $debug);
    }

    /**
     * @return int
     */
    public function getNumRows(): int
    {
        return $this->lastStatement->rowCount();
    }

    /**
     * @return int
     */
    public function getAffectedRows(): int
    {
        return (int) $this->lastStatement;
    }

    /**
     * @return int
     */
    public function getLastId(): int
    {
        return (int) $this->lastInsertId;
    }

    public function close()
    {
        $this->connection->close();
    }

    /**
     * @param Statement $statement
     * @param int $rowNumber
     *
     * @return mixed
     */
    protected function getAllColumnsOfRowNumber(Statement $statement, int $rowNumber)
    {
        $rows = $this->fetchAssoc($statement);

        if ($statement->rowCount() === 0) {
            return null;
        }

        if ($rowNumber <= $statement->rowCount()) {
            return $rows[$rowNumber];
        }

        return current($rows);
    }

    /**
     * @param Statement $statement
     * @param int $rowNumber
     * @param int $columnNumber
     *
     * @return array|null
     */
    protected function getColumnsNumberOfRowNumber(Statement $statement, int $rowNumber, int $columnNumber)
    {
        $row = $this->getAllColumnsOfRowNumber($statement, $rowNumber);

        if (false === is_array($row)) {
            return null;
        }

        $rowByIndex = array_values($row);

        return $rowByIndex[$columnNumber];
    }

    /**
     * @param string $sql
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    private function prepareStatement(string $sql, bool $debug)
    {
        $this->connection->executeQuery('set names latin1');

        $executeCommand = 'executeUpdate';

        if (true === $this->isReadOperation($sql)) {
            $executeCommand = 'executeQuery';
        }

        $this->lastStatement = $this->sqlWithError = $this->errorCode = $this->errorMessage = null;

        try {
            $this->lastStatement = $statement = $this->connection->$executeCommand($sql);
            if (true === $this->isWriteOperation($sql)) {
                $statement = true;

                if (($insertId = $this->connection->lastInsertId()) > 0) {
                    $this->lastInsertId = $insertId;
                }
            }
        } catch (DBALException $exception) {
            if (true === $debug) {
                $this->logError($sql);
            }

            $statement = false;
        }

        $this->connection->executeQuery('set names utf8');
        return $statement;
    }

    /**
     * @return self
     */
    public static function singleton()
    {
        if (null === self::$instance) {
            self::$instance = new self(Container::instance()['db']);
        }

        return self::$instance;
    }

    /**
     * @param $statement
     *
     * @return mixed
     */
    private function fetchAssoc(Statement $statement)
    {
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param Statement $statement
     * @param int $columnIndex
     *
     * @return bool|string
     */
    private function fetchColumn(Statement $statement, int $columnIndex)
    {
        return $statement->fetchAll(\PDO::FETCH_COLUMN, $columnIndex);
    }

    /**
     * @param $tabla
     *
     * @return array|bool|Statement
     */
    public static function getMaxId($SchemaAndTableName)
    {
        $primaryKey = self::extractPrimaryKeyFromSchemaAndTableOrTable($SchemaAndTableName);

        $sql = "SELECT max({$primaryKey}) FROM {$SchemaAndTableName}";

        return self::singleton()->query($sql, 0, 0);
    }

    /**
     * @param $tabla
     *
     * @return array|bool|Statement
     */
    public static function getNextAutoincrement($schemaAndTableName)
    {
        $tableName = self::extractTableNameFromSchemaAndTable($schemaAndTableName);
        $schemaName = self::extractSchemaFromSchemaAndTable($schemaAndTableName);

        $sql = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$schemaName}' AND TABLE_NAME = '{$tableName}'";

        return self::singleton()->query($sql, 0, 0);
    }

    /**
     * @param $schemaAndTableName
     *
     * @return bool
     */
    public function tableExists($schemaAndTableName)
    {
        $tableName = self::extractTableNameFromSchemaAndTable($schemaAndTableName);
        $schemaName = self::extractSchemaFromSchemaAndTable($schemaAndTableName);

        $sql = "SELECT 1 FROM information_schema.tables WHERE table_name = '{$tableName}'";
        $schemaPart = " AND table_schema = '{$schemaName}'";

        if (true === $this->hasSchemaAndTableName($schemaAndTableName)) {
            $sql = $sql. $schemaPart;
        }

        $statement = self::singleton()->query($sql);

        return $statement->rowCount() === 1;
    }

    /**
     * @param $tableName
     *
     * @return array
     */
    public static function getColumnNames($tableName)
    {
        return
            self::singleton()->query(
                "SHOW COLUMNS FROM {$tableName}",
                "*",
                0
            );
    }

    /**
     * @param $schemaAndTableName
     *
     * @return bool
     */
    private function hasSchemaAndTableName($schemaAndTableName): bool
    {
        $tableNameArray = explode(".", $schemaAndTableName);
        return count($tableNameArray) === 2;
    }

    /**
     * @param $schemaAndTableName
     *
     * @return string
     */
    private static function extractTableNameFromSchemaAndTable($schemaAndTableName)
    {
        $offset = strpos($schemaAndTableName, ' ');
        if (false !== $offset) {
            $schemaAndTableName = strstr($schemaAndTableName, ' ', true);
        }

        $tableNameArray = explode(".", $schemaAndTableName);
        $tableName = end($tableNameArray);
        return $tableName;
    }

    /**
     * @param $schemaAndTableName
     *
     * @return string
     */
    private static function extractSchemaFromSchemaAndTable($schemaAndTableName)
    {
        $tableNameArray = explode(".", $schemaAndTableName);
        return current($tableNameArray);
    }

    /**
     * @param $schemaAndTableName
     *
     * @return string
     */
    private static function extractPrimaryKeyFromSchemaAndTableOrTable($schemaAndTableName): string
    {
        $tableName = self::extractTableNameFromSchemaAndTable($schemaAndTableName);
        $primaryKey = "uid_" . $tableName;
        return $primaryKey;
    }

    /**
     * @param $schemaTableOrTableName
     *
     * @return string
     */
    public static function getPrimaryKey($schemaTableOrTableName)
    {
        return self::extractPrimaryKeyFromSchemaAndTableOrTable($schemaTableOrTableName);
    }

    /**
     * @param $schemaTableOrTableName
     *
     * @return string
     */
    public static function getSeconds($schemaAndTableName)
    {
        $tableName = self::extractTableNameFromSchemaAndTable($schemaAndTableName);
        $schemaName = self::extractSchemaFromSchemaAndTable($schemaAndTableName);

        $sql = "
            SELECT TIMESTAMPDIFF(SECOND,create_time,NOW()) seconds
            FROM INFORMATION_SCHEMA.TABLES
            WHERE table_schema = '{$schemaName}'
            AND table_name = '{$tableName}'
        ";

        return self::singleton()->query($sql, 0, 0);
    }

    /**
     * @param string $schemaAndTableName
     * @param string $comment
     *
     * @return array|bool|Statement
     */
    public function putTableComment($schemaAndTableName, $comment)
    {
        $jsonComment = json_encode($comment);
        $sql = "ALTER TABLE {$schemaAndTableName} comment='{$jsonComment}'";

        return $this->query($sql);
    }

    /**
     * @param $schemaAndTableName
     *
     * @return string
     */
    public function getTableComment($schemaAndTableName)
    {
        $tableName = self::extractTableNameFromSchemaAndTable($schemaAndTableName);
        $schemaName = self::extractSchemaFromSchemaAndTable($schemaAndTableName);

        $sql = "
            SELECT TABLE_COMMENT
            FROM INFORMATION_SCHEMA.TABLES
            WHERE table_name = '{$tableName}'
            AND table_schema = '{$schemaName}'
            ";

        $comment = $this->query($sql, 0, 0);
        return json_decode($comment);
    }

    /**
     * [getLastFromSet SQL string to get the last number in a set]
     * @param  [string] $field [the column name]
     * @return [string]        [the SQL ready to retrieve the last item in set]
     */
    public static function getLastFromSet($field)
    {
        return "(SUBSTRING({$field}, (0-(LOCATE(',', REVERSE({$field}))-1))))";
    }

    /**
     * [getFirstFromSet SQL string to get the first number in a set]
     * @param  [string] $field [the column name]
     * @return [string]        [the SQL ready to retrieve the first item in set]
     */
    public static function getFirstFromSet($field)
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
        $field = "CONCAT_WS('{$glue}', {$fields})";

        return $field;
    }

    public static function getGroupPart($colname, $part = 1, $concat = ",", $sort = "1")
    {
        if ($part == 1) {
            $field = "SUBSTRING_INDEX(group_concat($colname ORDER BY $sort SEPARATOR '$concat'),'$concat',1)";
        } else {
            $field = "REVERSE( SUBSTRING( REVERSE(SUBSTRING_INDEX(group_concat($colname ORDER BY $sort SEPARATOR '$concat'),'$concat',$part)), 1, LOCATE( REVERSE('$concat') ,REVERSE(SUBSTRING_INDEX(group_concat($colname ORDER BY $sort SEPARATOR '$concat'),'$concat',$part)))-1 ))";
        }
        return $field;
    }

    /**
     * @param string $sql
     *
     * @return bool
     */
    private function isReadOperation(string $sql): bool
    {
        return preg_match('/((?!SELECT.* FOR UPDATE)SELECT|SHOW|DESC)/', $sql) > 0;
    }

    /**
     * @param string $sql
     *
     * @return bool
     */
    private function isWriteOperation(string $sql): bool
    {
        return false === $this->isReadOperation($sql);
    }

    /**
     * @return Connection|MasterSlaveConnection
     */
    public function connection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return Connection
     */
    public function doctrineConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function lastError()
    {
        return $this->errorMessage;
    }

    /**
     * @return int
     */
    public function lastErrorNo()
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function lastErrorString()
    {
        return "mysql_error_" . $this->errorCode;
    }

    /**
     * @param string $sql
     */
    protected function logError(string $sql)
    {
        $errorInfo = $this->connection->errorInfo();

        $this->sqlWithError = $sql;
        $this->errorCode = $errorInfo[1] ?? $this->connection->errorCode();
        $this->errorMessage = end($errorInfo);

        $sql = preg_replace("/[\r\t]/", "", $sql);
        $sql = preg_replace("/[\n]/", " ", $sql);

        $trace = implode(" <- ", trace(true));
        $errStr = "{$this->errorMessage} [{$sql}] {$trace}";

        if (true === is_writable(self::ERROR_FILE)) {
            file_put_contents(self::ERROR_FILE, $errStr."\n", FILE_APPEND);
        } else {
            error_log($errStr);
        }
    }

    /**
     * @param Statement $statement
     *
     * @return mixed
     */
    public static function fetch_row(Statement $statement)
    {
        return $statement->fetch(\PDO::FETCH_NUM);
    }

    /**
     * @param Statement $statement
     *
     * @return mixed
     */
    public static function fetch_array(Statement $statement, int $mode = \PDO::FETCH_BOTH)
    {
        return $statement->fetch($mode);
    }

    /**
     * @param $text
     *
     * @return string
     */
    public static function scape($text)
    {
        $quoteText = self::singleton()->connection->getWrappedConnection()->quote($text);
        return substr($quoteText, 1, -1);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public static function valueNull($value)
    {
        if (true === empty($value)) {
            return 'NULL';
        }

        return self::singleton()->connection->getWrappedConnection()->quote($value);
    }

    /**
     * @return null
     */
    public function info()
    {
        return null;
    }

    /**
     * @param $filename
     * @param null $host
     * @param bool $output
     * @param bool $code
     *
     * @return bool|string
     */
    public function run($filename, $host = null, $output = false, $code = false)
    {
        $user = get_cfg_var(self::MYSQL_USER_KEY);
        $pass = get_cfg_var(self::MYSQL_PASS_KEY);
        $host = $host ? $host : get_cfg_var(self::MYSQL_HOST_KEY);

        if (!is_file($filename) || !is_readable($filename)) {
            return false;
        }

        $command = 'mysql'
            . ' -h ' . $host
            . ' -u ' . $user
            . ' -p'  . $pass
            . " < {$filename}";

        if ($output) {
            exec($command, $out, $exitCode);

            if ($code) {
                return $exitCode;
            }
            return $out;
        } else {
            $command .= "  >/dev/null 2>&1 &";
            return shell_exec($command);
        }
    }

    public static function isReadOnly()
    {
        if (true === defined("DB_READ_ONLY")) {
            return constant("DB_READ_ONLY");
        }

        if (true === self::singleton()->connection instanceof MasterSlaveConnection) {
            $cloneConnection = clone self::singleton()->connection;
            try {
                $cloneConnection->connect('master');
            } catch (ConnectionException $exception) {
                return true;
            }
        }

        return (bool) get_cfg_var('dokify.readonly');
    }
}
