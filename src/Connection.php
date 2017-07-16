<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace matrozov\couchbase;

use Couchbase\Cluster;
use Couchbase\ClusterManager;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\db\Expression;

/**
 * Class Connection
 *
 * @property string         $dsn
 * @property string|null    $managerUserName
 * @property string|null    $managerPassword
 * @property string         $bucketPrefix
 * @property string         $defaultBucket
 * @property Cluster        $cluster
 * @property ClusterManager $manager
 * @property string         $commandClass
 * @property bool           $enableLogging
 * @property bool           $enableProfiling
 *
 * @property Bucket         $bucket
 * @property bool           $isActive
 * @property bool           $isManagerActive
 * @property QueryBuilder   $queryBuilder
 *
 * @package matrozov\couchbase
 */
class Connection extends Component
{
    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';

    /**
     * @var string host:port
     *
     * Correct syntax is:
     * couchbase://host1[:port1][,host2[:port2:],...]
     * For example:
     * couchbase://localhost:11210
     */
    public $dsn;

    /**
     * @var string Couchbase manager username
     */
    public $managerUserName;

    /**
     * @var string Couchbase manager password
     */
    public $managerPassword;

    /**
     * @var string the common prefix or suffix for bucket names. If a bucket name is given
     * as `{{%BucketName}}`, then the percentage character `%` will be replaced with this
     * property value. For example, `{{%post}}` becomes `{{tbl_post}}`.
     */
    public $bucketPrefix = '';

    /**
     * @var string Default bucket name
     */
    public $defaultBucket = 'default';

    /**
     * @var Cluster Couchbase driver cluster.
     */
    public $cluster;

    /**
     * @var ClusterManager Couchbase cluster manager driver.
     */
    public $manager;

    /**
     * @var string the class used to create new database [[Command]] objects. If you want to extend the [[Command]] class,
     * you may configure this property to use your extended version of the class.
     * @see createCommand
     */
    public $commandClass = 'matrozov\couchbase\Command';

    /**
     * @var bool whether to enable logging of database queries. Defaults to true.
     * You may want to disable this option in a production environment to gain performance
     * if you do not need the information being logged.
     * @see enableProfiling
     */
    public $enableLogging = true;

    /**
     * @var bool whether to enable profiling of database queries. Defaults to true.
     * You may want to disable this option in a production environment to gain performance
     * if you do not need the information being logged.
     * @see enableLogging
     */
    public $enableProfiling = true;

    /**
     * @var Bucket[] list of baskets.
     */
    private $_buckets = [];

    /**
     * @var QueryBuilder
     */
    private $_builder;

    /**
     * Returns the Couchbase bucket with the given name.
     * @param string $bucketName bucket name. If string considered as the name of the bucket
     * inside the default database. If array - first element considered as the name of the database,
     * second - as name of bucket inside that database
     * @param string $password bucket password
     * @return Bucket Couchbase basket instance.
     */
    public function getBucket($bucketName = null, $password = null)
    {
        if ($bucketName === null) {
            $bucketName = $this->defaultBucket;
        }

        if (!array_key_exists($bucketName, $this->_buckets)) {
            $this->_buckets[$bucketName] = $this->selectBucket($bucketName, $password);
        }

        return $this->_buckets[$bucketName];
    }

    /**
     * Selects bucket with given name and password.
     *
     * @param string $name     bucket name.
     * @param string $password bucket password.
     *
     * @return Bucket|null bucket instance.
     * @throws Exception
     */
    protected function selectBucket($name, $password = '')
    {
        $this->open();

        try {
            $bucket = $this->cluster->openBucket($name, $password);
        }
        catch (\Exception $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }

        return Yii::createObject([
            'class'  => 'matrozov\couchbase\Bucket',
            'db'     => $this,
            'name'   => $name,
            'bucket' => $bucket,
        ]);
    }

    /**
     * Clears internal buckets lists.
     * This method can be used to break cycle references between [[Connection]] and [[Bucket]] instances.
     */
    public function clearBuckets()
    {
        $this->_buckets = [];
    }

    /**
     * Returns a value indicating whether the Couchbase connection is established.
     * @return bool whether the Couchbase connection is established
     */
    public function getIsActive()
    {
        return $this->cluster !== null;
    }

    /**
     * Returns a value indicating whether the Couchbase connection is established
     * and you has administration privilege.
     *
     * @return bool whether the Couchbase connection is established and has privilege.
     */
    public function getIsManagerActive()
    {
        return $this->manager !== null;
    }

    /**
     * Establishes a Couchbase connection.
     * It does nothing if a Couchbase connection has already been established.
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->cluster !== null) {
            return;
        }

        if (empty($this->dsn)) {
            throw new InvalidConfigException($this->className() . '::dsn cannot be empty.');
        }

        $token = 'Opening Couchbase connection: ' . $this->dsn;

        try {
            Yii::trace($token, __METHOD__);
            Yii::beginProfile($token, __METHOD__);

            $this->cluster = new Cluster($this->dsn);

            if ($this->managerUserName) {
                $this->manager = $this->cluster->manager($this->managerUserName, $this->managerPassword);
            }

            $this->initConnection();

            Yii::endProfile($token, __METHOD__);
        }
        catch (\Exception $e) {
            Yii::endProfile($token, __METHOD__);

            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Open a CouchbaseManager.
     * It does nothing if a CouchbaseManager has already been opened.
     * @throws Exception if open fails
     */
    public function openManager()
    {
        if ($this->manager !== null) {
            return;
        }

        if ($this->cluster === null) {
            $this->open();
        }

        if (empty($this->managerUserName) || empty($this->managerPassword)) {
            throw new InvalidConfigException($this->className() . '::managerUserName/managerPassword cannot be empty.');
        }

        $token = 'Opening CouchbaseManager: ' . $this->managerUserName;

        try {
            Yii::trace($token, __METHOD__);
            Yii::beginProfile($token, __METHOD__);

            $this->manager = $this->cluster->manager($this->managerUserName, $this->managerPassword);

            Yii::endProfile($token, __METHOD__);
        }
        catch (\Exception $e) {
            Yii::endProfile($token, __METHOD__);

            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Closes the currently active DB connection.
     * It does nothing if the connection is already closed.
     */
    public function close()
    {
        if ($this->cluster === null) {
            return;
        }

        Yii::trace('Closing Couchbase connection: ' . $this->dsn, __METHOD__);

        $this->cluster = null;
        $this->manager = null;

        $this->clearBuckets();
    }

    /**
     * Initializes the DB connection.
     * This method is invoked right after the DB connection is established.
     * The default implementation triggers an [[EVENT_AFTER_OPEN]] event.
     */
    protected function initConnection()
    {
        $this->trigger(self::EVENT_AFTER_OPEN);
    }

    /**
     * Quotes a string value for use in a query.
     * Note that if the parameter is not a string, it will be returned without change.
     * @param string $value string to be quoted
     * @return string the properly quoted string
     * @see http://php.net/manual/en/pdo.quote.php
     */
    public function quoteValue($value)
    {
        return '"' . addcslashes(str_replace('"', '\"', $value), "\000\n\r\\\032") . '"';
    }

    /**
     * Quotes a bucket name for use in a query.
     * If the bucket name contains schema prefix, the prefix will also be properly quoted.
     * If the bucket name is already quoted or contains special characters including '(', '[[' and '{{',
     * then this method will do nothing.
     * @param string $bucketName bucket name
     * @return string the properly quoted bucket name
     */
    public function quoteBucketName($bucketName)
    {
        return strpos($bucketName, '`') !== false ? $bucketName : "`$bucketName`";
    }

    /**
     * Quotes a column name for use in a query.
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains special characters including '(', '[[' and '{{',
     * then this method will do nothing.
     * @param string $columnName column name
     * @return string the properly quoted column name
     */
    public function quoteColumnName($columnName)
    {
        return strpos($columnName, "`") !== false ? $columnName : "`" . $columnName . "`";
    }

    /**
     * Processes a SQL statement by quoting bucket and column names that are enclosed within double brackets.
     * Tokens enclosed within double curly brackets are treated as bucket names, while
     * tokens enclosed within double square brackets are column names. They will be quoted accordingly.
     * Also, the percentage character "%" at the beginning or ending of a bucket name will be replaced
     * with [[bucketPrefix]].
     * @param string $sql the SQL to be quoted
     * @return string the quoted SQL
     */
    public function quoteSql($sql)
    {
        return preg_replace_callback(
            '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return $this->quoteColumnName($matches[3]);
                }
                else {
                    return str_replace('%', $this->bucketPrefix, $this->quoteBucketName($matches[2]));
                }
            },
            $sql
        );
    }

    /**
     * Creates a command for execution.
     * @param string $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     * @return Command the DB command
     */
    public function createCommand($sql = null, $params = [])
    {
        /** @var Command $command */
        $command = new $this->commandClass([
            'db' => $this,
            'sql' => $sql,
        ]);

        return $command->bindValues($params);
    }

    /**
     * Creates new bucket in database associated with this command.s
     *
     * @param string $bucketName bucket name
     * @param array  $options    bucket options in format: "name" => "value"
     *
     * @throws Exception
     */
    public function createBucket($bucketName, array $options = [])
    {
        $this->openManager();

        $this->manager->createBucket($bucketName, $options);
    }

    /**
     * Drops specified bucket.
     *
     * @param string $bucketName name of the bucket to be dropped.
     *
     * @throws Exception
     */
    public function dropBucket($bucketName)
    {
        $this->openManager();

        $this->manager->removeBucket($bucketName);
    }

    /**
     * Returns the list of available buckets.
     *
     * @return array buckets information.
     *
     * @throws Exception
     */
    public function listBuckets()
    {
        $this->openManager();

        return $this->manager->listBuckets();
    }

    /**
     * Insert record.
     *
     * @param string $bucketName the bucket that new rows will be inserted into.
     * @param array $data the column data (name => value) to be inserted into the bucket or instance
     *
     * @return string inserted id
     */
    public function insert($bucketName, $data)
    {
        return $this->createCommand()->insert($bucketName, $data)->queryScalar();
    }

    /**
     * Batch insert record.
     *
     * @param string $bucketName the bucket that new rows will be inserted into.
     * @param array $rows the rows to be batch inserted into the bucket
     *
     * @return int[]|false inserted ids
     */
    public function batchInsert($bucketName, $rows)
    {
        return $this->createCommand()->batchInsert($bucketName, $rows)->queryColumn();
    }

    /**
     * Update record.
     *
     * @param string $bucketName the bucket to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param string|array $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the parameters to be bound to the command
     *
     * @return int affected rows
     */
    public function update($bucketName, $columns, $condition, $params = [])
    {
        return $this->createCommand()->update($bucketName, $columns, $condition, $params)->execute();
    }

    /**
     * Upsert record.
     *
     * @param string $bucketName the bucket to be updated.
     * @param string $id the document id.
     * @param array $data the column data (name => value) to be inserted into the bucket or instance.
     *
     * @return bool
     */
    public function upsert($bucketName, $id, $data)
    {
        return $this->createCommand()->upsert($bucketName, $id, $data)->execute();
    }

    /**
     * Delete record
     *
     * @param string $bucketName the bucket where the data will be deleted from.
     * @param string|array $condition the condition that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the parameters to be bound to the command
     *
     * @return int affected rows
     */
    public function delete($bucketName, $condition = '', $params = [])
    {
        return $this->createCommand()->delete($bucketName, $condition, $params)->execute();
    }

    /**
     * Counts records in this bucket.
     *
     * @param string $bucketName the bucket where the data will be deleted from.
     * @param array $condition query condition
     * @param array $params list of options in format: optionName => optionValue.
     *
     * @return int records count.
     */
    public function count($bucketName, $condition = '', $params = [])
    {
        return $this->createCommand()->count($bucketName, $condition, $params)->queryScalar();
    }

    /**
     * Build index.
     *
     * @param string          $bucketName
     * @param string|string[] $indexNames names of index
     *
     * @return bool
     */
    public function buildIndex($bucketName, $indexNames)
    {
        return $this->createCommand()->buildIndex($bucketName, $indexNames)->execute();
    }

    /**
     * Create primary index.
     *
     * @param string      $bucketName
     * @param string|null $indexName name of primary index (optional)
     * @param array       $options
     *
     * @return bool
     */
    public function createPrimaryIndex($bucketName, $indexName = null, $options = [])
    {
        return $this->createCommand()->createPrimaryIndex($bucketName, $indexName, $options)->execute();
    }

    /**
     * Drop unnamed primary index.
     *
     * @param string $bucketName
     *
     * @return bool
     */
    public function dropPrimaryIndex($bucketName)
    {
        return $this->createCommand()->dropPrimaryIndex($bucketName)->execute();
    }

    /**
     * Creates index.
     *
     * @param string     $bucketName
     * @param string     $indexName
     * @param array      $columns
     * @param array|null $condition
     * @param array      $params
     * @param array      $options
     *
     * @return bool
     */
    public function createIndex($bucketName, $indexName, $columns, $condition = null, &$params = [], $options = [])
    {
        return $this->createCommand()->createIndex($bucketName, $indexName, $columns, $condition, $params, $options)->execute();
    }

    /**
     * Drop index.
     *
     * @param string $bucketName
     * @param string $indexName
     *
     * @return bool
     */
    public function dropIndex($bucketName, $indexName)
    {
        return $this->createCommand()->dropIndex($bucketName, $indexName)->execute();
    }

    /**
     * @return QueryBuilder the query builder for this connection.
     */
    public function getQueryBuilder()
    {
        if ($this->_builder === null) {
            $this->_builder = new QueryBuilder($this);
        }

        return $this->_builder;
    }

    /**
     * Create new ID from UUID()
     *
     * @return string id of bucket
     */
    public function createId()
    {
        return (new Query)->select(new Expression('UUID()'))->scalar($this);
    }
}