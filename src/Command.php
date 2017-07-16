<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace matrozov\couchbase;

use Couchbase\N1qlQuery;
use Yii;
use yii\base\Object;
use yii\db\DataReader;

/**
 * Class Command
 *
 * @property Connection $db
 * @property array      $params
 * @property N1qlQuery  $n1ql
 *
 * @property string     $sql
 * @property string     $rawSql
 *
 * @package matrozov\couchbase
 */
class Command extends Object
{
    const FETCH_ALL = 'fetchAll';
    const FETCH_ONE = 'fetchOne';
    const FETCH_SCALAR = 'fetchScalar';
    const FETCH_COLUMN = 'fetchColumn';

    /**
     * @var Connection the Couchbase connection that this command is associated with.
     */
    public $db;

    /**
     * @var array the parameters (name => value) that are bound to the current PDO statement.
     * This property is maintained by methods such as [[bindValue()]]. It is mainly provided for logging purpose
     * and is used to generate [[rawSql]]. Do not modify it directly.
     */
    public $params = [];

    /**
     * @var N1qlQuery
     */
    public $n1ql;

    /**
     * @var string the N1QL statement that this command represents
     */
    private $_sql;

    /**
     * Returns the N1QL statement for this command.
     * @return string the N1QL statement to be executed
     */
    public function getSql()
    {
        return $this->_sql;
    }

    public function setSql($sql)
    {
        if ($sql !== $this->_sql) {
            $this->cancel();

            $this->_sql = $this->db->quoteSql($sql);
            $this->params = [];
        }

        return $this;
    }

    /**
     * Returns the raw SQL by inserting parameter values into the corresponding placeholders in [[sql]].
     * Note that the return value of this method should mainly be used for logging purpose.
     * It is likely that this method returns an invalid SQL due to improper replacement of parameter placeholders.
     * @return string the raw SQL with parameter values inserted into the corresponding placeholders in [[sql]].
     */
    public function getRawSql()
    {
        if (empty($this->params)) {
            return $this->_sql;
        }

        $params = [];

        foreach ($this->params as $name => $value) {
            if (is_string($name) && strncmp(':', $name, 1)) {
                $name = '$' . $name;
            }

            if (is_string($value)) {
                $params[$name] = $this->db->quoteValue($value);
            }
            elseif (is_bool($value)) {
                $params[$name] = ($value ? 'TRUE' : 'FALSE');
            }
            elseif ($value === null) {
                $params[$name] = 'NULL';
            }
            elseif (!is_object($value) && !is_resource($value)) {
                $params[$name] = $value;
            }
        }

        $this->prepare();

        if (!isset($params[1])) {
            return strtr($this->_sql, $params);
        }

        $sql = '';

        foreach (explode('$', $this->_sql) as $i => $part) {
            $sql .= (isset($params[$i]) ? $params[$i] : '') . $part;
        }

        return $sql;
    }

    public function prepare()
    {
        $sql = $this->getSql();

        $this->db->open();

        try {
            $this->n1ql = N1qlQuery::fromString($sql);
            $this->n1ql->namedParams($this->params);
            $this->n1ql->consistency(N1qlQuery::REQUEST_PLUS);
        }
        catch (\Exception $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Cancels the execution of the SQL statement.
     * This method mainly sets [[pdoStatement]] to be null.
     */
    public function cancel()
    {
        $this->n1ql = null;
    }

    /**
     * Binds a parameter to the SQL statement to be executed.
     * @param string|int $name parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form `:name`. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value the PHP variable to bind to the SQL statement parameter (passed by reference)
     * @return $this the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindParam.php
     */
    public function bindParam($name, &$value)
    {
        $name = ltrim($name, '$');

        $this->params[$name] =& $value;

        return $this;
    }

    /**
     * Binds a value to a parameter.
     * @param string|int $name Parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form `:name`. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter
     * @return $this the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindValue.php
     */
    public function bindValue($name, $value)
    {
        $name = ltrim($name, '$');

        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Binds a list of values to the corresponding parameters.
     * This is similar to [[bindValue()]] except that it binds multiple values at a time.
     * Note that the SQL data type of each value is determined by its PHP type.
     * @param array $values the values to be bound. This must be given in terms of an associative
     * array with array keys being the parameter names, and array values the corresponding parameter values,
     * e.g. `[':name' => 'John', ':age' => 25]`. By default, the PDO type of each value is determined
     * by its PHP type. You may explicitly specify the PDO type by using an array: `[value, type]`,
     * e.g. `[':name' => 'John', ':profile' => [$profile, \PDO::PARAM_LOB]]`.
     * @return $this the current command being executed
     */
    public function bindValues($values)
    {
        if (empty($values)) {
            return $this;
        }

        foreach ($values as $name => $value) {
            $this->bindValue($name, $value);
        }

        return $this;
    }

    /**
     * Creates an INSERT command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->insert('user', [
     *     'name' => 'Sam',
     *     'age' => 30,
     * ])->execute();
     * ```
     *
     * or
     *
     * ```
     * $id = $connection->createCommand()->insert('user', [
     *      'name' => 'Sam',
     *      'age' => 30,
     * ])->queryScalar();
     * ```
     *
     * The method will properly escape the column names, and bind the values to be inserted.
     *
     * Note that the created command is not executed until [[execute()]] or [[queryScalar()]] is called.
     *
     * @param string $bucketName the bucket that new rows will be inserted into.
     * @param array $data the column data (name => value) to be inserted into the bucket or instance.
     *
     * @return $this the command object itself
     */
    public function insert($bucketName, $data)
    {
        $sql = $this->db->getQueryBuilder()->insert($bucketName, $data);

        return $this->setSql($sql);
    }

    /**
     * Creates a batch INSERT command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->batchInsert('user', ['name', 'age'], [
     *     ['Tom', 30],
     *     ['Jane', 20],
     *     ['Linda', 25],
     * ])->execute();
     * ```
     *
     * The method will properly escape the column names, and quote the values to be inserted.
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * Also note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $bucketName the bucket that new rows will be inserted into.
     * @param array $rows the rows to be batch inserted into the bucket.
     *
     * @return $this the command object itself
     */
    public function batchInsert($bucketName, $rows)
    {
        $sql = $this->db->getQueryBuilder()->batchInsert($bucketName, $rows);

        return $this->setSql($sql);
    }

    /**
     * Creates an UPDATE command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->update('user', ['status' => 1], 'age > 30')->execute();
     * ```
     *
     * The method will properly escape the column names and bind the values to be updated.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $bucketName the bucket to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param string|array $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the parameters to be bound to the command
     *
     * @return $this the command object itself
     */
    public function update($bucketName, $columns, $condition, $params = [])
    {
        $sql = $this->db->getQueryBuilder()->update($bucketName, $columns, $condition, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates an UPSERT command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->upsert('user', 'my-id', ['status' => 1])->execute();
     * ```
     *
     * The method will properly escape the column names and bind the values to be updated.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $bucketName the bucket to be updated.
     * @param string $id the document id.
     * @param array $data the column data (name => value) to be inserted into the bucket or instance.
     *
     * @return $this the command object itself
     */
    public function upsert($bucketName, $id, $data)
    {
        $sql = $this->db->getQueryBuilder()->upsert($bucketName, $id, $data);

        return $this->setSql($sql);
    }

    /**
     * Creates a DELETE command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->delete('user', 'status = 0')->execute();
     * ```
     *
     * The method will properly escape the bucket and column names.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $bucketName the bucket where the data will be deleted from.
     * @param string|array $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the parameters to be bound to the command
     *
     * @return $this the command object itself
     */
    public function delete($bucketName, $condition = '', $params = [])
    {
        $sql = $this->db->getQueryBuilder()->delete($bucketName, $condition, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates a SELECT COUNT command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->count('user', 'status = 0')->queryScalar();
     * ```
     *
     * The method will properly escape the bucket and column names.
     *
     * Note that the created command is not executed until [[queryScalar()]] is called.
     *
     * @param string $bucketName the bucket where the data will be deleted from.
     * @param string|array $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the parameters to be bound to the command
     *
     * @return $this the command object itself
     */
    public function count($bucketName, $condition = '', $params = [])
    {
        $sql = $this->db->getQueryBuilder()->count($bucketName, $condition, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Create a SQL command for build index.
     *
     * @param string          $bucketName
     * @param string|string[] $indexNames names of index
     *
     * @return $this the command object itself
     */
    public function buildIndex($bucketName, $indexNames)
    {
        $sql = $this->db->getQueryBuilder()->buildIndex($bucketName, $indexNames);

        return $this->setSql($sql);
    }

    /**
     * Create a SQL command for creating a new primary index.
     *
     * @param string      $bucketName
     * @param string|null $indexName name of primary index (optional)
     * @param array       $options
     *
     * @return $this the command object itself
     */
    public function createPrimaryIndex($bucketName, $indexName = null, $options = [])
    {
        $sql = $this->db->getQueryBuilder()->createPrimaryIndex($bucketName, $indexName, $options);

        return $this->setSql($sql);
    }

    /**
     * Create a SQL command for dropping an unnamed primary index.
     *
     * @param string $bucketName
     *
     * @return $this the command object itself
     */
    public function dropPrimaryIndex($bucketName)
    {
        $sql = $this->db->getQueryBuilder()->dropPrimaryIndex($bucketName);

        return $this->setSql($sql);
    }

    /**
     * Creates a SQL command for creating a new index.
     *
     * @param string     $bucketName
     * @param string     $indexName
     * @param array      $columns
     * @param array|null $condition
     * @param array      $params
     * @param array      $options
     *
     * @return $this the command object itself
     */
    public function createIndex($bucketName, $indexName, $columns, $condition = null, &$params = [], $options = [])
    {
        $sql = $this->db->getQueryBuilder()->createIndex($bucketName, $indexName, $columns, $condition, $params, $options);

        return $this->setSql($sql);
    }

    /**
     * Creates a SQL command for dropping an index.
     *
     * @param string $bucketName
     * @param string $indexName
     *
     * @return $this the command object itself
     */
    public function dropIndex($bucketName, $indexName)
    {
        $sql = $this->db->getQueryBuilder()->dropIndex($bucketName, $indexName);

        return $this->setSql($sql);
    }

    /**
     * Executes the SQL statement.
     * This method should only be used for executing non-query SQL statement, such as `INSERT`, `DELETE`, `UPDATE` SQLs.
     * No result set will be returned.
     * @return int|bool number of rows affected by the execution.
     * @throws Exception
     */
    public function execute()
    {
        list($profile, $rawSql) = $this->logQuery(__METHOD__);

        $this->prepare();

        $result = true;

        try {
            $profile and Yii::beginProfile($rawSql, __METHOD__);

            $res = $this->db->getBucket()->bucket->query($this->n1ql, true);

            if ($res->status === 'success') {
                if (isset($res->metrics['mutationCount'])) {
                    $result = $res->metrics['mutationCount'];
                }
            }
            else {
                $result = false;
            }

            $profile and Yii::endProfile($rawSql, __METHOD__);
        }
        catch (\Exception $e) {
            $profile and Yii::endProfile($rawSql, __METHOD__);
            //throw $this->db->getSchema()->convertException($e, $rawSql ?: $this->getRawSql());
            //TODO: FixIt
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }

        return $result;
    }

    /**
     * Executes the SQL statement and returns query result.
     * This method is for executing a SQL query that returns result set, such as `SELECT`.
     * @return DataReader the reader object for fetching the query result
     * @throws Exception execution failed
     */
    public function query()
    {
        return $this->queryInternal();
    }

    /**
     * Executes the SQL statement and returns ALL rows at once.
     * @return array all rows of the query result. Each array element is an array representing a row of data.
     * An empty array is returned if the query results in nothing.
     * @internal param int $fetchMode the result fetch mode. Please refer to [PHP manual](http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php)
     * for valid fetch modes. If this parameter is null, the value set in [[fetchMode]] will be used.
     */
    public function queryAll()
    {
        return $this->queryInternal(self::FETCH_ALL);
    }

    /**
     * Executes the SQL statement and returns the first row of the result.
     * This method is best used when only the first row of result is needed for a query.
     * @return array|false the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     * @internal param int $fetchMode the result fetch mode. Please refer to [PHP manual](http://php.net/manual/en/pdostatement.setfetchmode.php)
     * for valid fetch modes. If this parameter is null, the value set in [[fetchMode]] will be used.
     */
    public function queryOne()
    {
        return $this->queryInternal(self::FETCH_ONE);
    }

    /**
     * Executes the SQL statement and returns the value of the first column in the first row of data.
     * This method is best used when only a single value is needed for a query.
     *
     * @param null|string $columnName
     *
     * @return false|null|string the value of the first column in the first row of the query result.
     * False is returned if there is no value.
     */
    public function queryScalar($columnName = null)
    {
        return $this->queryInternal(self::FETCH_SCALAR, $columnName);
    }

    /**
     * Executes the SQL statement and returns the first column of the result.
     * This method is best used when only the first column of result (i.e. the first element in each row)
     * is needed for a query.
     *
     * @param null|string $columnName
     *
     * @return array the first column of the query result. Empty array is returned if the query results in nothing.
     */
    public function queryColumn($columnName = null)
    {
        return $this->queryInternal(self::FETCH_COLUMN, $columnName);
    }

    /**
     * Performs the actual DB query of a SQL statement.
     *
     * @param string      $method method of PDOStatement to be called
     * @param null|string $columnName
     *
     * @return mixed the method execution result
     * @throws Exception
     */
    protected function queryInternal($method = null, $columnName = null)
    {
        list($profile, $rawSql) = $this->logQuery('yii\db\Command::query');

        $this->prepare();

        $result = false;

        try {
            $profile and Yii::beginProfile($rawSql, 'yii\db\Command::query');

            $res = $this->db->getBucket()->bucket->query($this->n1ql, true);

            if ($res->status == 'success') {
                switch ($method) {
                    case self::FETCH_ALL: {
                        $result = $res->rows;
                    } break;
                    case self::FETCH_ONE: {
                        if (!empty($res->rows)) {
                            $result = $res->rows[0];
                        }
                    } break;
                    case self::FETCH_SCALAR: {
                        if (!empty($res->rows)) {
                            if ($columnName === null) {
                                $columnName = array_keys($res->rows[0])[0];
                            }

                            $result = $res->rows[0][$columnName];
                        }
                    } break;
                    case self::FETCH_COLUMN: {
                        if (!empty($res->rows)) {
                            if ($columnName === null) {
                                $columnName = array_keys($res->rows[0])[0];
                            }

                            $result = [];

                            foreach ($res->rows as $row) {
                                $result[] = $row[$columnName];
                            }
                        }
                    } break;
                    default: {
                        $result = $res;
                    }
                }
            }

            $profile and Yii::endProfile($rawSql, 'yii\db\Command::query');
        }
        catch (\Exception $e) {
            $profile and Yii::endProfile($rawSql, 'yii\db\Command::query');
            //throw $this->db->getSchema()->convertException($e, $rawSql ?: $this->getRawSql());
            //TODO: FixIt
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }

        return $result;
    }

    /**
     * Logs the current database query if query logging is enabled and returns
     * the profiling token if profiling is enabled.
     * @param string $category the log category.
     * @return array array of two elements, the first is boolean of whether profiling is enabled or not.
     * The second is the rawSql if it has been created.
     */
    private function logQuery($category)
    {
        if ($this->db->enableLogging) {
            $rawSql = $this->getRawSql();
            Yii::info($rawSql, $category);
        }

        if (!$this->db->enableProfiling) {
            return [false, isset($rawSql) ? $rawSql : null];
        }
        else {
            return [true, isset($rawSql) ? $rawSql : $this->getRawSql()];
        }
    }
}