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

    public $dsn;

    /**
     * @var \CouchbaseCluster CouchBase driver cluster.
     */
    public $cluster;

    /**
     * @var Database list of CouchBase databases.
     */
    private $_databases;
}