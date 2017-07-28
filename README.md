Couchbase Extension for Yii2
============================

This extension provides the [Couchbase](https://couchbase.com) integration for the [Yii framework 2.0](http://www.yiiframework.com) with ActiveRecord, QueryBuilder and Migration supports.

For license information check the [LICENSE](LICENSE.md)-file.

Documentation is at [docs/guide-ru/README.md](docs/guide-ru/README.md).

[![Latest Stable Version](https://poser.pugx.org/matrozov/yii2-couchbase/v/stable.png)](https://packagist.org/packages/matrozov/yii2-couchbase)
[![Total Downloads](https://poser.pugx.org/matrozov/yii2-couchbase/downloads.png)](https://packagist.org/packages/matrozov/yii2-couchbase)
[![Build Status](https://travis-ci.org/matrozov/yii2-couchbase.svg?branch=master)](https://travis-ci.org/matrozov/yii2-couchbase)
[![License](https://poser.pugx.org/matrozov/yii2-couchbase/license)](https://packagist.org/packages/matrozov/yii2-couchbase)

## Installation

This extension requires [Couchbase PHP Extension](https://developer.couchbase.com/documentation/server/current/sdk/php/start-using-sdk.html) version 2.3 or higher.

This extension requires [Couchbase server](https://www.couchbase.com/products/server) version 4.6 or higher.

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

## Configuration

To use this extension, simply add the following code in your application configuration:

```php
return [
    //....
    'components' => [
        'couchbase' => [
            'class' => '\matrozov\couchbase\Connection',
            'dsn' => 'couchbase://localhost:11210',
            'userName' => 'Administrator',
            'password' => 'Administrator',
        ],
    ],
];
```