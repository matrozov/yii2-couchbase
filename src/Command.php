<?php
/**
 *
 */

namespace matrozov\couchbase;

use Couchbase\N1qlQuery;
use Exception;
use Yii;
use yii\base\Object;
use yii\db\DataReader;

class Command extends Object
{
    const FETCH_ALL = 'fetchAll';
    const FETCH_ONE = 'fetchOne';
    const FETCH_SCALAR = 'fetchScalar';
    const FETCH_COLUMN = 'fetchColumn';

    /**
     * @var Connection the CouchBase connection that this command is associated with.
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
    private $n1ql;

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
        }
        catch (\Exception $e) {
            throw new \Exception();
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
     * @return string|null|false the value of the first column in the first row of the query result.
     * False is returned if there is no value.
     * @throws Exception execution failed
     */
    public function queryScalar()
    {
        return $this->queryInternal(self::FETCH_SCALAR);
    }

    /**
     * Executes the SQL statement and returns the first column of the result.
     * This method is best used when only the first column of result (i.e. the first element in each row)
     * is needed for a query.
     * @return array the first column of the query result. Empty array is returned if the query results in nothing.
     * @throws Exception execution failed
     */
    public function queryColumn()
    {
        return $this->queryInternal(self::FETCH_COLUMN);
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
     * The method will properly escape the column names, and bind the values to be inserted.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $bucket the table that new rows will be inserted into.
     * @param array|\yii\db\Query $columns the column data (name => value) to be inserted into the table or instance
     * of [[yii\db\Query|Query]] to perform INSERT INTO ... SELECT SQL statement.
     * Passing of [[yii\db\Query|Query]] is available since version 2.0.11.
     * @return $this the command object itself
     */
    public function insert($bucket, $columns)
    {
        $params = [];
        $sql = $this->db->getQueryBuilder()->insert($bucket, $columns, $params);

        return $this->setSql($sql)->bindValues($params);
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
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column names
     * @param array $rows the rows to be batch inserted into the table
     * @return $this the command object itself
     */
    public function batchInsert($table, $columns, $rows)
    {
        $sql = $this->db->getQueryBuilder()->batchInsert($table, $columns, $rows);

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
     * @param string $bucket the bucket to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param string|array $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the parameters to be bound to the command
     * @return $this the command object itself
     */
    public function update($bucket, $condition, $columns, $params = [])
    {
        $sql = $this->db->getQueryBuilder()->update($bucket, $condition, $columns, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates a DELETE command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->delete('user', 'status = 0')->execute();
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * Note that the created command is not executed until [[execute()]] is called.
     *
     * @param string $table the table where the data will be deleted from.
     * @param string|array $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the parameters to be bound to the command
     * @return $this the command object itself
     */
    public function delete($table, $condition = '', $params = [])
    {
        $sql = $this->db->getQueryBuilder()->delete($table, $condition, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates a SQL command for creating a new index.
     * @param string $name the name of the index. The name will be properly quoted by the method.
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by the method.
     * @param string|array $columns the column(s) that should be included in the index. If there are multiple columns, please separate them
     * by commas. The column names will be properly quoted by the method.
     * @param bool $unique whether to add UNIQUE constraint on the created index.
     * @return $this the command object itself
     */
    public function createIndex($name, $table, $columns, $unique = false)
    {
        $sql = $this->db->getQueryBuilder()->createIndex($name, $table, $columns, $unique);

        return $this->setSql($sql);
    }

    /**
     * Creates a SQL command for dropping an index.
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     * @return $this the command object itself
     */
    public function dropIndex($name, $table)
    {
        $sql = $this->db->getQueryBuilder()->dropIndex($name, $table);

        return $this->setSql($sql);
    }

    /**
     * Executes the SQL statement.
     * This method should only be used for executing non-query SQL statement, such as `INSERT`, `DELETE`, `UPDATE` SQLs.
     * No result set will be returned.
     * @return int number of rows affected by the execution.
     * @throws Exception execution failed
     */
    public function execute()
    {
        $sql = $this->getSql();

        list($profile, $rawSql) = $this->logQuery(__METHOD__);

        if ($sql == '') {
            return 0;
        }

        $this->prepare();

        try {
            $profile and Yii::beginProfile($rawSql, __METHOD__);

            $res = $this->db->getBucket()->bucket->query($this->n1ql);

            $profile and Yii::endProfile($rawSql, __METHOD__);

            return $res;
        } catch (\Exception $e) {
            $profile and Yii::endProfile($rawSql, __METHOD__);
            //throw $this->db->getSchema()->convertException($e, $rawSql ?: $this->getRawSql());
            //TODO: FixIt
            throw $e;
        }
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
        } else {
            return [true, isset($rawSql) ? $rawSql : $this->getRawSql()];
        }
    }

    /**
     * Performs the actual DB query of a SQL statement.
     *
     * @param string $method method of PDOStatement to be called
     * @param int    $columnIndex
     *
     * @return mixed the method execution result
     * @throws Exception if the query causes any problem
     * @since 2.0.1 this method is protected (was private before).
     */
    protected function queryInternal($method = null, $columnIndex = 0)
    {
        list($profile, $rawSql) = $this->logQuery('yii\db\Command::query');

        $this->prepare();

        $result = false;

        var_dump($this->getRawSql());

        try {
            $profile and Yii::beginProfile($rawSql, 'yii\db\Command::query');

            $res = $this->db->getBucket()->bucket->query($this->n1ql, true);

            switch ($method) {
                case self::FETCH_ALL: {
                    if ($res->status !== 'success') {
                        return false;
                    }

                    $result = $res->rows;
                } break;
                case self::FETCH_ONE: {
                    if ($res->status !== 'success') {
                        return false;
                    }

                    if (!empty($res->rows)) {
                        $result = $res->rows[0];
                    }
                } break;
                case self::FETCH_SCALAR: {
                    if ($res->status !== 'success') {
                        return false;
                    }

                    if (!empty($res->rows)) {
                        $key = array_keys($res->rows[0])[0];

                        $result = $res[0][$key];
                    }
                } break;
                case self::FETCH_COLUMN: {
                    if ($res->status !== 'success') {
                        return false;
                    }

                    if (!empty($res->rows)) {
                        $key = array_keys($res->rows[0])[0];

                        $result = [];

                        foreach ($res->rows as $row) {
                            $result[][$key] = $row[$key];
                        }
                    }
                } break;
                default: {
                    $result = $res;
                }
            }

            $profile and Yii::endProfile($rawSql, 'yii\db\Command::query');
        } catch (\Exception $e) {
            $profile and Yii::endProfile($rawSql, 'yii\db\Command::query');
            //throw $this->db->getSchema()->convertException($e, $rawSql ?: $this->getRawSql());
            //TODO: FixIt
            throw $e;
        }

        if (isset($cache, $cacheKey, $info)) {
            $cache->set($cacheKey, [$result], $info[1], $info[2]);
            Yii::trace('Saved query result in cache', 'yii\db\Command::query');
        }

        return $result;
    }

}