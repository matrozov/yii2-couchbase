<?php
/**
 *
 */

namespace matrozov\couchbase;

use Exception;
use yii\base\Object;
use Yii;

class Database extends Object
{
    /**
     * @var Connection CouchBase connection.
     */
    public $connection;

    /**
     * @var string name of this database.
     */
    public $name;

    /**
     * @var Bucket[] list of baskets.
     */
    private $_buckets = [];

    /**
     * Returns the CouchBase bucket with the given name.
     *
     * @param string $name bucket name
     * @param string $password bucket password
     *
     * @return Bucket CouchBase bucket instance.
     */
    public function getBucket($name = 'default', $password = null)
    {
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
     * @throws \Exception
     */
    protected function selectBucket($name, $password = '')
    {
        $this->connection->open();

        $bucket = $this->connection->cluster->openBucket($name, $password);

        if (!$bucket) {
            throw new \Exception();
        }

        return Yii::createObject([
            'class' => 'matrozov\couchbase\Bucket',
            'database' => $this,
            'name' => $name,
            'bucket' => $bucket,
        ]);
    }

    /**
     * Clears internal buckets lists.
     * This method can be used to break cycle references between [[Database]] and [[Bucket]] instances.
     */
    public function clearBuckets()
    {
        $this->_buckets = [];
    }

    /**
     * Creates CouchBase command associated with this database.
     * @return Command command instance.
     */
    public function createCommand()
    {
        return $this->connection->createCommand();
    }

    /**
     * Creates new bucket.
     * @param string $bucketName name of the collection
     * @param array $options collection options in format: "name" => "value"
     * @return bool whether operation was successful.
     * @throws Exception on failure.
     */
    public function createCollection($bucketName, $options = [])
    {
        return $this->connection->createBucket($bucketName, $options);
    }

    /**
     * Drops specified bucket.
     * @param string $bucketName name of the bucket
     * @return bool whether operation was successful.
     */
    public function dropBucket($bucketName)
    {
        return $this->connection->dropBucket($bucketName);
    }

    /**
     * Returns the list of available buckets in this database.
     * @return array buckets information.
     */
    public function listBuckets()
    {
        return $this->connection->listBuckets();
    }
}