<?php
/**
 *
 */

namespace matrozov\couchbase;

use yii\base\Object;
use Yii;

class Bucked extends Object
{
    /**
     * @var Database CouchBase database instance
     */
    public $database;

    /**
     * @var \CouchbaseBucket CouchBase bucked instance
     */
    public $bucked;

    /**
     * @return string name of this bucked.
     */
    public function getName()
    {
        return $this->bucked->getName();
    }

    /**
     * @return string full name of this bucked, including database name.
     */
    public function getFullName()
    {
        return $this->database->name . '.' . $this->name;
    }
}