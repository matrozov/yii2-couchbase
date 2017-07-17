Установка
============

## Требования

Это расширение требует [Couchbase PHP Extension](https://developer.couchbase.com/documentation/server/current/sdk/php/start-using-sdk.html) версии 4.6 или выше.

Это расширение требует [Couchbase server](https://www.couchbase.com/products/server) версии 4.6 или выше.

Предпочтительный способ установки расширения через  [composer](http://getcomposer.org/download/).

Для этого запустите

```
php composer.phar require --prefer-dist matrozov/yii2-couchbase
```

или добавьте

```
"matrozov/yii2-couchbase": "dev-master"
```

в секцию require вашего composer.json.

## Настройка приложения

Для использования расширения, просто добавьте этот код в конфигурацию вашего приложения:

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