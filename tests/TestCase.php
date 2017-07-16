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
        static::$params = require(__DIR__ . '/data/config.php');

        self::mockApplication(static::$params);

        self::$db = Yii::$app->couchbase;
        self::$bucketName = 'yii2test';
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected static function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => realpath(__DIR__ . '/../vendors'),
            'runtimePath' => dirname(__DIR__) . '/runtime',
        ], $config));
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