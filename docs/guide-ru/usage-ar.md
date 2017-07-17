Использование Couchbase ActiveRecord
==============================

Это расширение предоставляет ActiveRecord решение аналогично [[\yii\db\ActiveRecord]].
Чтобы объявить класс ActiveRecord вам необходимо расширить [[\matrozov\couchbase\ActiveRecord]] и реализовать метод `attributes`:

```php
use matrozov\couchbase\ActiveRecord;

class Customer extends ActiveRecord
{
    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return ['_id', 'name', 'email', 'address', 'status'];
    }
}
```
> Note: Атрибуты могут быть автоматически получены из объявленных rules. Первичный ключ с названием ('_id') будет добавлен в этом случае автоматически.

> Note: первичный ключ названия коллекции ('_id') должен быть всегда установлен в явном виде в качестве атрибута.

Вы можете использовать [[\yii\data\ActiveDataProvider]] с [[\matrozov\couchbase\Query]] и [[\matrozov\couchbase\ActiveQuery]]:

```php
use yii\data\ActiveDataProvider;
use matrozov\couchbase\Query;

$query = new Query();
$query->from('customer')->where(['status' => 2]);
$provider = new ActiveDataProvider([
    'query' => $query,
    'pagination' => [
        'pageSize' => 10,
    ]
]);
$models = $provider->getModels();
```

```php
use yii\data\ActiveDataProvider;
use app\models\Customer;

$provider = new ActiveDataProvider([
    'query' => Customer::find(),
    'pagination' => [
        'pageSize' => 10,
    ]
]);
$models = $provider->getModels();
```