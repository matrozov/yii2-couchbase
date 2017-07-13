<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace matrozov\couchbase;

use yii\base\Object;
use yii\db\Expression;

/**
 * Class Bucket
 *
 * @property Connection        $db
 * @property string            $name
 * @property \Couchbase\Bucket $bucket
 *
 * @package matrozov\couchbase
 */
class Bucket extends Object
{
    /**
     * @var Connection Couchbase database instance
     */
    public $db;

    /**
     * @var string name of this bucket.
     */
    public $name;

    /**
     * @var \Couchbase\Bucket Couchbase bucket instance
     */
    public $bucket;

    /**
     * Drops this bucket.
     * @throws Exception on failure.
     * @return bool whether the operation successful.
     */
    public function drop()
    {
        return $this->db->dropBucket($this->name);
    }

    /**
     * Inserts new data into bucket.
     * @param array|object $data data to be inserted.
     * @param array $options list of options in format: optionName => optionValue.
     * @return null|int new record ID instance.
     */
    public function insert($data, $options = [])
    {
        $id = $this->db->createId();

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
        $insertedIds = $this->db->createCommand()->batchInsert($this->name, $rows, $options);

        foreach ($rows as $key => $row) {
            $rows[$key]['_id'] = $insertedIds[$key];
        }

        return $rows;
    }

    /**
     * Updates the rows, which matches given criteria by given data.
     * Note: for "multi" mode Couchbase requires explicit strategy "$set" or "$inc"
     * to be specified for the "newData". If no strategy is passed "$set" will be used.
     * @param array $condition description of the objects to update.
     * @param array $newData the object with which to update the matching records.
     * @param array $options list of options in format: optionName => optionValue.
     * @return int|bool number of updated documents or whether operation was successful.
     */
    public function update($condition, $newData, $options = [])
    {
        return $this->db->createCommand()->update($this->name, $condition, $newData, $options)->execute();
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
     * Removes data from the bucket.
     * @param array $condition description of records to remove.
     * @param array $options list of options in format: optionName => optionValue.
     * @return int|bool number of updated documents or whether operation was successful.
     */
    public function remove($condition = [], $options = [])
    {
        return $this->db->createCommand()->delete($this->name, $condition, $options)->execute();
    }

    /**
     * Counts records in this bucket.
     * @param array $condition query condition
     * @param array $options list of options in format: optionName => optionValue.
     * @return int records count.
     */
    public function count($condition = [], $options = [])
    {
        return $this->db->createCommand()->count($this->name, $condition, $options);
    }
}