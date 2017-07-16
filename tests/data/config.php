<?php

/**
 * Application configuration shared by all test types
 */
return [
    'id' => 'couchbase-tests',
    'basePath' => realpath(__DIR__ . '/../../'),
    'language' => 'en-US',
    'components' => [
        'couchbase' => [
            'class' => 'matrozov\couchbase\Connection',
            'dsn' => 'couchbase://localhost',
            'managerUserName' => 'Administrator',
            'managerPassword' => 'Administrator',
        ],
    ],
];
