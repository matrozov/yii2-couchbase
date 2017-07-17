Использование миграции
======================

Couchbase это - schemaless-бд и может создать необходимые коллекции по первому требованию. Однако, есть много случаев когда вам может понадобиться применение постоянных изменений в базу данных Couchbase. Для примера: вам может понадобится создать коллекцию с некоторыми конкретными вариантами или индексы. MongoDB миграции управляются с помощью [[\matrozov\couchbase\console\controllers\MigrateController]], который являетя аналогом регулярного [[\yii\console\controllers\MigrateController]].

Для того, чтобы включить эту команду, вы должны настроить конфигурацию консольного приложения:

```php
return [
    // ...
    'controllerMap' => [
        'couchbase-migrate' => 'matrozov\couchbase\console\controllers\MigrateController'
    ],
];
```

Ниже приведены примеры использования этой команды:

```
# создать миграцию с именем 'create_user_collection'
yii couchbase-migrate/create create_user_collection

# применить ВСЕ новые миграции
yii couchbase-migrate

# отменить последние примененные миграции
yii couchbase-migrate/down
```