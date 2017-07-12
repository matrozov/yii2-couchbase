<?php
/**
 *
 */

namespace matrozov\couchbase;

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
    public function getbucket($name = 'default', $password = null)
    {
        if (!array_key_exists($name, $this->_buckets)) {
            $this->_buckets[$name] = $this->selectbucket($name, $password);
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
    protected function selectbucket($name, $password = '')
    {
        $bucket = $this->connection->cluster->openBucket($name, $password);

        if (!$bucket) {
            throw new \Exception();
        }

        return Yii::createObject([
            'class' => 'matrozov\couchbase\Bucket',
            'database' => $this,
            'bucket' => $bucket,
        ]);
    }

    /**
     * Clears internal buckets lists.
     * This method can be used to break cycle references between [[Database]] and [[Bucket]] instances.
     */
    public function clearbuckets()
    {
        $this->_buckets = [];
    }
}