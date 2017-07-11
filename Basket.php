<?php
/**
 *
 */

namespace matrozov\couchbase;

use yii\base\Object;
use Yii;

class Basket extends Object
{
    /**
     * @var Database CouchBase database instance
     */
    public $database;

    /**
     * @var string name of the basket
     */
    public $name;
}