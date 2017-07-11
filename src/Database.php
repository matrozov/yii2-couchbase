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
     * @var Bucked[] list of baskets.
     */
    private $_buckeds = [];

    /**
     * Returns the CouchBase bucked with the given name.
     * @param string $name bucked name
     * @param string $password bucked password
     * @return Bucked CouchBase collection instance.
     */
    public function getBucked($name = 'default', $password = null)
    {
        if (!array_key_exists($name, $this->_buckeds)) {
            $this->_buckeds[$name] = $this->selectBucked($name, $password);
        }

        return $this->_buckeds[$name];
    }

    /**
     * Selects bucked with given name and password.
     *
     * @param string $name     bucked name.
     * @param string $password bucked password.
     *
     * @return Bucked collection instance.
     * @throws \Exception
     */
    protected function selectBucked($name = 'default', $password = '')
    {
        $bucked = $this->connection->cluster->openBucket($name, $password);

        if (!$bucked) {
            throw new \Exception();
        }

        return Yii::createObject([
            'class' => 'matrozov\couchbase\Bucked',
            'database' => $this,
            'bucked' => $bucked,
        ]);
    }

    /**
     * Clears internal buckeds lists.
     * This method can be used to break cycle references between [[Database]] and [[Collection]] instances.
     */
    public function clearBuckeds()
    {
        $this->_buckeds = [];
    }
}