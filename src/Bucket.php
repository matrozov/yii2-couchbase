<?php
/**
 *
 */

namespace matrozov\couchbase;

use yii\base\Object;

class Bucket extends Object
{
    /**
     * @var Database CouchBase database instance
     */
    public $database;

    /**
     * @var string name of this collection.
     */
    public $name;

    /**
     * @var \Couchbase\Bucket CouchBase bucket instance
     */
    public $bucket;

    /**
     * @return string full name of this bucket, including database name.
     */
    public function getFullName()
    {
        return $this->database->name . '.' . $this->name;
    }

    /**
     * Inserts new data into bucket.
     * @param array|object $data data to be inserted.
     * @param array $options list of options in format: optionName => optionValue.
     * @return null|int new record ID instance.
     */
    public function insert($data, $options = [])
    {
        $id = (string)rand(1, 1000000);

        $data['_id'] = $id;

        if (!$this->bucket->insert($id, $data, $options)) {
            return null;
        }

        return $id;
    }

    /**
     * Inserts several new rows into bucket.
     * @param array $rows array of arrays or objects to be inserted.
     * @param array $options list of options in format: optionName => optionValue.
     * @return array inserted data, each row will have "_id" key assigned to it.
     */
    public function batchInsert($rows, $options = [])
    {
        $insertedIds = $this->database->createCommand()->batchInsert($this->name, $rows, $options);

        foreach ($rows as $key => $row) {
            $rows[$key]['_id'] = $insertedIds[$key];
        }

        return $rows;
    }

    /**
     * Updates the rows, which matches given criteria by given data.
     * Note: for "multi" mode Mongo requires explicit strategy "$set" or "$inc"
     * to be specified for the "newData". If no strategy is passed "$set" will be used.
     * @param array $condition description of the objects to update.
     * @param array $newData the object with which to update the matching records.
     * @param array $options list of options in format: optionName => optionValue.
     * @return int|bool number of updated documents or whether operation was successful.
     */
    public function update($condition, $newData, $options = [])
    {
        return $this->database->createCommand()->update($this->name, $condition, $newData, $options)->execute();
    }

    /**
     * Update the existing database data, otherwise insert this data
     * @param array|object $data data to be updated/inserted.
     * @param array $options list of options in format: optionName => optionValue.
     * @return int|null updated/new record id instance.
     */
    public function save($data, $options = [])
    {
        if (empty($data['_id'])) {
            return $this->insert($data, $options);
        }
        else {
            $id = $data['_id'];

            unset($data['_id']);

            $this->update(['_id' => $id], ['$set' => $data], $options);

            return $id;
        }
    }

    /**
     * Removes data from the collection.
     * @param array $condition description of records to remove.
     * @param array $options list of options in format: optionName => optionValue.
     * @return int|bool number of updated documents or whether operation was successful.
     */
    public function remove($condition = [], $options = [])
    {
        $options = array_merge(['limit' => 0], $options);

        $writeResult = $this->database->createCommand()->delete($this->name, $condition, $options);

        return $writeResult->getDeletedCount();
    }

    /**
     * Counts records in this collection.
     * @param array $condition query condition
     * @param array $options list of options in format: optionName => optionValue.
     * @return int records count.
     */
    public function count($condition = [], $options = [])
    {
        return $this->database->createCommand()->count($this->name, $condition, $options);
    }
}