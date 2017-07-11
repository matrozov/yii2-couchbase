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
     * @var Basket[] list of baskets.
     */
    private $_baskets = [];
}