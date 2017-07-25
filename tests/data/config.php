<?php

/**
 * Application configuration shared by all test types
 */
return [
    'id' => 'couchbase-tests',
    'basePath' => dirname(__DIR__),
    'language' => 'en-US',
    'components' => [
        'couchbase' => [
            'class' => 'matrozov\couchbase\Connection',
            'dsn' => 'http://127.0.0.1:8091',
            'userName' => 'Administrator',
            'password' => 'Administrator',
        ],
    ],
];
