<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace matrozov\couchbase;

use Couchbase\Cluster;
use Couchbase\ClusterManager;
use yii\base\Component;
use yii\base\InvalidConfigException;
use Yii;
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
     * couchbase://localhost:27017
     */
    public $dsn;

    /**
     * @var string CouchBase manager username
     */
    public $managerUserName;

    /**
     * @var string CouchBase manager password
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
     * @var Cluster CouchBase driver cluster.
     */
    public $cluster;

    /**
     * @var ClusterManager CouchBase cluster manager driver.
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
     * Returns the CouchBase bucket with the given name.
     * @param string|array $name bucket name. If string considered as the name of the bucket
     * inside the default database. If array - first element considered as the name of the database,
     * second - as name of bucket inside that database
     * @param string $password bucket password
     * @return Bucket CouchBase basket instance.
     */
    public function getBucket($name = null, $password = null)
    {
        if ($name === null) {
            $name = $this->defaultBucket;
        }

        if (!array_key_exists($name, $this->_buckets)) {
            $this->_buckets[$name] = $this->selectBucket($name, $password);
        }

        return $this->_buckets[$name];
    }

    /**
     * Selects bucket with given name and password.
     *
     * @param string $name     bucket name.
     * @param string $password bucket password.
     *
     * @return Bucket bucket instance.
     * @throws Exception
     */
    protected function selectBucket($name, $password = '')
    {
        $this->open();

        $bucket = $this->cluster->openBucket($name, $password);

        if (!$bucket) {
            throw new Exception();
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
     * Returns a value indicating whether the CouchBase connection is established.
     * @return bool whether the CouchBase connection is established
     */
    public function getIsActive()
    {
        return $this->cluster !== null;
    }

    /**
     * Returns a value indicating whether the CouchBase connection is established
     * and you has administration privilege.
     * @return bool whether the CouchBase connection is established and has privilege.
     */
    public function getIsManagerActive()
    {
        return $this->manager !== null;
    }

    /**
     * Establishes a CouchBase connection.
     * It does nothing if a CouchBase connection has already been established.
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

        $token = 'Opening CouchBase connection: ' . $this->dsn;

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
     * Closes the currently active DB connection.
     * It does nothing if the connection is already closed.
     */
    public function close()
    {
        if ($this->cluster === null) {
            return;
        }

        Yii::trace('Closing CouchBase connection: ' . $this->dsn, __METHOD__);

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
        $this->open();

        if (!$this->getIsManagerActive()) {
            throw new Exception('Active manager required!');
        }

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
        $this->open();

        if (!$this->getIsManagerActive()) {
            throw new Exception('Active manager required!');
        }

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
        $this->open();

        if (!$this->getIsManagerActive()) {
            throw new Exception('Active manager required!');
        }

        return $this->manager->listBuckets();
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
     * @return string id of bucket
     */
    public function createId()
    {
        return (new Query)->select(new Expression('UUID()'))->scalar($this);
    }
}