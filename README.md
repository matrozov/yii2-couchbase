Couchbase Extension for Yii2 (alpha, not fully tested)
======================================================

This extension provides the [Couchbase](https://couchbase.com) integration for the [Yii framework 2.0](http://www.yiiframework.com) with ActiveRecord and Migration supports.

For license information check the [LICENSE](LICENSE.md)-file.

Installation
------------

This extension requires [Couchbase PHP Extension](https://developer.couchbase.com/documentation/server/current/sdk/php/start-using-sdk.html) version 2.3 or higher.

This extension requires Couchbase server version 4.6 or higher.

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```
php composer.phar require --prefer-dist matrozov/yii2-couchbase
```

or add

```
"matrozov/yii2-couchbase": "dev-master"
```

to the require section of your composer.json.

Configuration
-------------

To use this extension, simply add the following code in your application configuration:

```php
return [
    //....
    'components' => [
        'couchbase' => [
            'class' => '\matrozov\couchbase\Connection',
            'dsn' => 'couchbase://localhost:11210',
        ],
    ],
];
```