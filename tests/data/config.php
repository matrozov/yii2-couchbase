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
            'dsn' => 'couchbase://localhost',
            'userName' => 'Administrator',
            'password' => 'Administrator',
        ],
    ],
];
