Основы использования
===========

После установки экземпляра соединения с Couchbase, вы можете выполнять Couchbase команды и запросы
используя [[\matrozov\couchbase\Command]]:

```php
// выполнить команду:
$result = Yii::$app->couchbase->createCommand('CREATE PRIMARY INDEX ON `customer`')->execute();

// выполнить запрос (find):
$rows = Yii::$app->couchbase->createCommand('SELECT * FROM `customer`')->queryAll();

// выполнить пакетную операцию:
$result = Yii::$app->couchbase->createCommand()->batchInsert('customer', [
    ['name' => 'John Smith', 'status' => 1],
    ['name' => 'Erika Smith', 'status' => 2]
])->execute();
```

Используя экземпляр соединения выможете получить доступ к базам данным и коллекциям.
Большинство Couchbase команд доступны через [[\matrozov\couchbase\Bucket]] например:

```php
$customer = Yii::$app->couchbase->getBucket('customer');
$customer->insert(['name' => 'John Smith', 'status' => 1]);
```

Для выполнения `find` запросов, вы должны использовать [[\matrozov\couchbase\Query]]:

```php
use matrozov\couchbase\Query;

$query = new Query();

// составление запроса
$query->select(['name', 'status'])
    ->from('customer')
    ->limit(10);

// выполнение запроса
$rows = $query->all();
```
