<?php

namespace yiiunit\extensions\couchbase;

use matrozov\couchbase\Connection;
use Yii;
use yii\helpers\ArrayHelper;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static $params;

    /**
     * @var array Couchbase connection configuration
     */
    protected static $couchbaseDbConfig = [

    ];

    /**
     * @var Connection Couchbase connection instance
     */
    protected static $db;
    protected static $bucketName;

    public static function setUpBeforeClass()
    {
        $config = self::getParam('couchbase');

        self::$db = Yii::createObject($config);
        self::$bucketName = 'yii2test';
    }

    /**
     * Returns a test configuration param from /data/config.php
     * @param  string $name params name
     * @param  mixed $default default value to use when param is not set.
     * @return mixed  the value of the configuration param
     */
    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require(__DIR__ . '/data/config.php');
        }

        return isset(static::$params[$name]) ? static::$params[$name] : $default;
    }
}