<?php
/**
 *
 */

namespace matrozov\couchbase;

use yii\base\Object;

class Bucket extends Object
{
    /**
     * @var Database CouchBase database instance
     */
    public $database;

    /**
     * @var \Couchbase\Bucket CouchBase bucket instance
     */
    public $bucket;

    /**
     * @return string name of this bucket.
     */
    public function getName()
    {
        return $this->bucket->getName();
    }

    /**
     * @return string full name of this bucket, including database name.
     */
    public function getFullName()
    {
        return $this->database->name . '.' . $this->name;
    }
}