<?php
/**
 *
 */

namespace matrozov\couchbase;

use yii\base\Component;
use yii\base\InvalidConfigException;
use Yii;

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
     * couchbase://[username:password@]host1[:port1][,host2[:port2:],...][/dbname]
     * For example:
     * couchbase://localhost:27017
     * couchbase://developer:password@localhost:27017
     * couchbase://developer:password@localhost:27017/mydatabase
     */
    public $dsn;

    /**
     * @var string CouchBase user name
     */
    public $username = '';

    /**
     * @var string CouchBase password
     */
    public $password = '';

    /**
     * @var string Default bucked name
     */
    public $defaultBucked = 'default';

    /**
     * @var \CouchbaseCluster CouchBase driver cluster.
     */
    public $cluster;

    public $commandClass = 'matrozov\couchbase\Command';

    /**
     * @var string name of the CouchBase database to use by default.
     * If this field left blank, connection instance will attempt to determine if from
     * [[dsn]] automatically, if needed.
     */
    private $_defaultDatabaseName;

    /**
     * @var Database[] list of CouchBase databases.
     */
    private $_databases;

    /**
     * Sets default database name.
     * @param string $name default database name.
     */
    public function setDefaultDatabaseName($name)
    {
        $this->_defaultDatabaseName = $name;
    }

    /**
     * Returns default database name, if it is not set,
     * attempts to determine it from [[dsn]] value.
     * @return string default database name
     * @throws \yii\base\InvalidConfigException if unable to determine default database name.
     */
    public function getDefaultDatabaseName()
    {
        if ($this->_defaultDatabaseName === null) {
            if (preg_match('/^couchbase:\\/\\/.+\\/([^?&]+)/s', $this->dsn, $matches)) {
                $this->_defaultDatabaseName = $matches[1];
            }
            else {
                throw new InvalidConfigException("Unable to determine default database name from dsn.");
            }
        }

        return $this->_defaultDatabaseName;
    }

    /**
     * Returns the CouchBase database with the given name.
     * @param string|null $name database name, if null default one will be used.
     * @return Database database instance.
     */
    public function getDatabase($name = null)
    {
        if ($name === null) {
            $name = $this->getDefaultDatabaseName();
        }

        if (!array_key_exists($name, $this->_databases)) {
            $this->_databases[$name] = $this->selectDatabase($name);
        }

        return $this->_databases[$name];
    }

    /**
     * Selects the database with given name.
     * @param string $name database name.
     * @return Database database instance.
     */
    protected function selectDatabase($name)
    {
        return Yii::createObject([
            'class' => 'matrozov\couchbase\Database',
            'name' => $name,
            'connection' => $this,
        ]);
    }

    /**
     * Returns the CouchBase bucked with the given name.
     * @param string|array $name bucked name. If string considered as the name of the collection
     * inside the default database. If array - first element considered as the name of the database,
     * second - as name of collection inside that database
     * @param string $password bucked password
     * @return Bucked CouchBase basket instance.
     */
    public function getBucked($name = null, $password = null)
    {
        if ($name === null) {
            $name = $this->defaultBucked;
        }

        return $this->getDatabase()->getBucked($name, $password);
    }

    /**
     * Returns a value indicating whether the CouchBase connection is established.
     * @return bool whether the Mongo connection is established
     */
    public function getIsActive()
    {
        return is_object($this->cluster);
    }

    /**
     * Establishes a CouchBase connection.
     * It does nothing if a CouchBase connection has already been established.
     * @throws \Exception if connection fails
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

            $this->cluster = new \CouchbaseCluster($this->dsn, $this->username, $this->password);
            $this->initConnection();

            Yii::endProfile($token, __METHOD__);
        } catch (\Exception $e) {
            Yii::endProfile($token, __METHOD__);

            throw new \Exception($e->getMessage(), (int) $e->getCode(), $e);
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

        foreach ($this->_databases as $database) {
            $database->clearBuckeds();
        }

        $this->_databases = [];
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

    public function quoteColumnName($columnName)
    {
        return $columnName;
    }

    public function quoteBuckedName($buckedName)
    {
        return '`' . $buckedName . '`';
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
}