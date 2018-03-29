<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace matrozov\couchbase;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\BaseActiveRecord;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * Class ActiveRecord
 *
 * @property Connection $db
 *
 * @package matrozov\couchbase
 */
class ActiveRecord extends BaseActiveRecord
{
    /**
     * Returns the Couchbase connection used by this AR class.
     * By default, the "couchbase" application component is used as the Couchbase connection.
     * You may override this method if you want to use a different database connection.
     * @return Connection the database connection used by this AR class.
     * @throws InvalidConfigException
     */
    public static function getDb()
    {
        return Yii::$app->get('couchbase');
    }

    /**
     * Updates all documents in the bucket using the provided attribute values and conditions.
     * For example, to change the status to be 1 for all customers whose status is 2:
     *
     * ```php
     * Customer::updateAll(['status' => 1], ['status' => 2]);
     * ```
     *
     * @param array $attributes attribute values (name-value pairs) to be saved into the bucket
     * @param array $condition  description of the objects to update.
     *                          Please refer to [[Query::where()]] on how to specify this parameter.
     * @param array $options    list of options in format: optionName => optionValue.
     *
     * @return int the number of documents updated.
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function updateAll($attributes, $condition = [], $options = [])
    {
        return static::getBucket()->update($condition, $attributes, $options);
    }

    /**
     * Updates all documents in the bucket using the provided counter changes and conditions.
     * For example, to increment all customers' age by 1,
     *
     * ```php
     * Customer::updateAllCounters(['age' => 1]);
     * ```
     *
     * @param array $counters  the counters to be updated (attribute name => increment value).
     *                         Use negative values if you want to decrement the counters.
     * @param array $condition description of the objects to update.
     *                         Please refer to [[Query::where()]] on how to specify this parameter.
     * @param array $options   list of options in format: optionName => optionValue.
     *
     * @return int the number of documents updated.
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function updateAllCounters($counters, $condition = [], $options = [])
    {
        return static::getBucket()->update($condition, ['$inc' => $counters], $options);
    }

    /**
     * Deletes documents in the bucket using the provided conditions.
     * WARNING: If you do not specify any condition, this method will delete documents rows in the bucket.
     *
     * For example, to delete all customers whose status is 3:
     *
     * ```php
     * Customer::deleteAll(['status' => 3]);
     * ```
     *
     * @param array $condition description of the objects to delete.
     *                         Please refer to [[Query::where()]] on how to specify this parameter.
     * @param array $options   list of options in format: optionName => optionValue.
     *
     * @return int the number of documents deleted.
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function deleteAll($condition = [], $options = [])
    {
        return static::getBucket()->delete($condition, $options);
    }

    /**
     * @inheritdoc
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::className(), [get_called_class()]);
    }
    
    /**
     * Declares the name of the Couchbase bucket associated with this AR class.
     *
     * Bucket name can be either a string or array:
     *  - if string considered as the name of the bucket inside the default database.
     *  - if array - first element considered as the name of the database, second - as
     *    name of bucket inside that database
     *
     * By default this method returns the class name as the bucket name by calling [[Inflector::camel2id()]].
     * For example, 'Customer' becomes 'customer', and 'OrderItem' becomes
     * 'order_item'. You may override this method if the bucket is not named after this convention.
     * @return string|array the bucket name
     */
    public static function bucketName()
    {
        return Inflector::camel2id(StringHelper::basename(get_called_class()), '_');
    }

    /**
     * Return the Couchbase bucket instance for this AR class.
     * @return Bucket bucket instance.
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function getBucket()
    {
        return static::getDb()->getBucket(static::bucketName());
    }
    
    /**
     * Returns the primary key name(s) for this AR class.
     * The default implementation will return ['_id'].
     *
     * Note that an array should be returned even for a bucket with single primary key.
     *
     * @return string[] the primary keys of the associated Couchbase bucket.
     */
    public static function primaryKey()
    {
        return ['_id'];
    }

    /**
     * Returns the list of all attribute names of the model.
     * This method must be overridden by child classes to define available attributes.
     * Note: primary key attribute "_id" should be always present in returned array.
     * For example:
     *
     * ```php
     * public function attributes()
     * {
     *     return ['_id', 'name', 'address', 'status'];
     * }
     * ```
     *
     * @throws \yii\base\InvalidConfigException if not implemented
     * @return array list of attribute names.
     */
    public function attributes()
    {
        $rules = $this->rules();

        if (empty($rules)) {
            throw new InvalidConfigException('The attributes() method of Couchbase ActiveRecord has to be implemented by child classes.');
        }

        $attributes = [];
        $attributes['_id'] = true;

        foreach ($rules as $rule) {
            if (is_array($rule[0])) {
                foreach ($rule[0] as $field) {
                    $attributes[$field] = true;
                }
            }
            else {
                $attributes[$rule[0]] = true;
            }
        }

        return array_keys($attributes);
    }


    /**
     * @inheritdoc
     */
    protected static function findByCondition($condition)
    {
        $query = static::find();

        if (!ArrayHelper::isAssociative($condition)) {
            $condition = ["META().id" => $condition];
        }

        return $query->andWhere($condition);
    }
    
    /**
     * Inserts a row into the associated Couchbase bucket using the attribute values of this record.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeValidate()]] when `$runValidation` is true. If validation
     *    fails, it will skip the rest of the steps;
     * 2. call [[afterValidate()]] when `$runValidation` is true.
     * 3. call [[beforeSave()]]. If the method returns false, it will skip the
     *    rest of the steps;
     * 4. insert the record into bucket. If this fails, it will skip the rest of the steps;
     * 5. call [[afterSave()]];
     *
     * In the above step 1, 2, 3 and 5, events [[EVENT_BEFORE_VALIDATE]],
     * [[EVENT_BEFORE_INSERT]], [[EVENT_AFTER_INSERT]] and [[EVENT_AFTER_VALIDATE]]
     * will be raised by the corresponding methods.
     *
     * Only the [[dirtyAttributes|changed attribute values]] will be inserted into database.
     *
     * If the primary key  is null during insertion, it will be populated with the actual
     * value after insertion.
     *
     * For example, to insert a customer record:
     *
     * ```php
     * $customer = new Customer();
     * $customer->name = $name;
     * $customer->email = $email;
     * $customer->insert();
     * ```
     *
     * @param bool $runValidation whether to perform validation before saving the record.
     * If the validation fails, the record will not be inserted into the bucket.
     * @param array $attributes list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded will be saved.
     * @return bool whether the attributes are valid and the record is inserted successfully.
     * @throws Exception in case insert failed.
     */
    public function insert($runValidation = true, $attributes = null)
    {
        if ($runValidation && !$this->validate($attributes)) {
            return false;
        }

        $result = $this->insertInternal($attributes);

        return $result;
    }
    
    /**
     * @see ActiveRecord::insert()
     */
    protected function insertInternal($attributes = null)
    {
        if (!$this->beforeSave(true)) {
            return false;
        }

        $values = $this->getDirtyAttributes($attributes);

        if (empty($values)) {
            $currentAttributes = $this->getAttributes();

            if (isset($currentAttributes['_id'])) {
                $values['_id'] = $currentAttributes['_id'];
            }
        }

        $newId = static::getBucket()->insert($values);

        if ($newId !== null) {
            $this->setAttribute('_id', $newId);
            $values['_id'] = $newId;
        }

        $changedAttributes = array_fill_keys(array_keys($values), null);

        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);

        return true;
    }

    /**
     * @see ActiveRecord::update()
     *
     * @param null $attributes
     *
     * @return bool|int
     * @throws Exception
     * @throws InvalidConfigException
     * @throws StaleObjectException
     */
    protected function updateInternal($attributes = null)
    {
        if (!$this->beforeSave(false)) {
            return false;
        }
        
        $values = $this->getDirtyAttributes($attributes);
        
        if (empty($values)) {
            $this->afterSave(false, $values);
            
            return 0;
        }
        
        $condition = $this->getOldPrimaryKey(true);
        $lock = $this->optimisticLock();

        if ($lock !== null) {
            if (!isset($values[$lock])) {
                $values[$lock] = $this->$lock + 1;
            }

            $condition[$lock] = $this->$lock;
        }

        // We do not check the return value of update() because it's possible
        // that it doesn't change anything and thus returns 0.
        $rows = static::getBucket()->update($values, $condition);

        if ($lock !== null && !$rows) {
            throw new StaleObjectException('The object being updated is outdated.');
        }
        
        if (isset($values[$lock])) {
            $this->$lock = $values[$lock];
        }
        
        $changedAttributes = [];
        
        foreach ($values as $name => $value) {
            $changedAttributes[$name] = $this->getOldAttribute($name);
            $this->setOldAttribute($name, $value);
        }
        
        $this->afterSave(false, $changedAttributes);
        
        return $rows;
    }

    /**
     * Deletes the document corresponding to this active record from the bucket.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeDelete()]]. If the method returns false, it will skip the
     *    rest of the steps;
     * 2. delete the document from the bucket;
     * 3. call [[afterDelete()]].
     *
     * In the above step 1 and 3, events named [[EVENT_BEFORE_DELETE]] and [[EVENT_AFTER_DELETE]]
     * will be raised by the corresponding methods.
     *
     * @return int|bool the number of documents deleted, or false if the deletion is unsuccessful for some reason.
     * Note that it is possible the number of documents deleted is 0, even though the deletion execution is successful.
     * @throws Exception
     * @throws InvalidConfigException
     * @throws StaleObjectException if [[optimisticLock|optimistic locking]] is enabled and the data
     * being deleted is outdated.
     */
    public function delete()
    {
        $result = false;

        if ($this->beforeDelete()) {
            $result = $this->deleteInternal();

            $this->afterDelete();
        }
        
        return $result;
    }

    /**
     * @see ActiveRecord::delete()
     * @return bool|int
     * @throws Exception
     * @throws InvalidConfigException
     * @throws StaleObjectException
     */
    protected function deleteInternal()
    {
        // we do not check the return value of deleteAll() because it's possible
        // the record is already deleted in the database and thus the method will return 0
        $condition = $this->getOldPrimaryKey(true);
        $lock = $this->optimisticLock();

        if ($lock !== null) {
            $condition[$lock] = $this->$lock;
        }

        $result = static::getBucket()->delete($condition);

        if ($lock !== null && !$result) {
            throw new StaleObjectException('The object being deleted is outdated.');
        }

        $this->setOldAttributes(null);
        
        return $result;
    }
    
    /**
     * Returns a value indicating whether the given active record is the same as the current one.
     * The comparison is made by comparing the bucket names and the primary key values of the two active records.
     * If one of the records [[isNewRecord|is new]] they are also considered not equal.
     * @param ActiveRecord $record record to compare to
     * @return bool whether the two active records refer to the same row in the same Couchbase bucket.
     */
    public function equals($record)
    {
        if ($this->isNewRecord || $record->isNewRecord) {
            return false;
        }

        return $this->bucketName() === $record->bucketName() && (string) $this->getPrimaryKey() === (string) $record->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getPrimaryKey($asArray = false)
    {
        if ($asArray) {
            return ['META().id' => $this->getPrimaryKey(false)];
        }
        else {
            return $this->getAttribute('_id');
        }
    }

    /**
     * @inheritdoc
     */
    public function getOldPrimaryKey($asArray = false)
    {
        if ($asArray) {
            return ['META().id' => $this->getPrimaryKey(false)];
        }
        else {
            return $this->getOldAttribute('_id');
        }
    }

    /**
     * @inheritdoc
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = parent::toArray($fields, $expand, false);

        if (!$recursive) {
            return $data;
        }

        return $this->toArrayInternal($data);
    }

    /**
     * Converts data to array recursively, converting Couchbase JSON objects to readable values.
     * @param mixed $data the data to be converted into an array.
     * @return array the array representation of the data.
     */
    private function toArrayInternal($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = $this->toArrayInternal($value);
                }

                if (is_object($value)) {
                    $data[$key] = ArrayHelper::toArray($value);
                }
            }

            return $data;
        }
        elseif (is_object($data)) {
            return ArrayHelper::toArray($data);
        }
        else {
            return [$data];
        }
    }
}