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
     *
     * @throws Exception on failure.
     * @return bool whether the operation successful.
     */
    public function drop()
    {
        return $this->db->dropBucket($this->name);
    }

    /**
     * Inserts new data into bucket.
     *
     * @param array|object $data data to be inserted.
     *
     * @return null|int new record ID instance.
     */
    public function insert($data)
    {
        return $this->db->insert($this->name, $data);
    }

    /**
     * Inserts several new rows into bucket.
     * @param array $rows array of arrays or objects to be inserted.
     * @return array inserted data, each row will have "_id" key assigned to it.
     */
    public function batchInsert($rows)
    {
        $insertedIds = $this->db->batchInsert($this->name, $rows);

        foreach ($rows as $key => $row) {
            $rows[$key]['_id'] = $insertedIds[$key]['_id'];
        }

        return $rows;
    }

    /**
     * Updates the rows, which matches given criteria by given data.
     * Note: for "multi" mode Couchbase requires explicit strategy "$set" or "$inc"
     * to be specified for the "newData". If no strategy is passed "$set" will be used.
     *
     * @param array $columns the object with which to update the matching records.
     * @param array $condition description of the objects to update.
     * @param array $params list of options in format: optionName => optionValue.
     *
     * @return int|bool number of updated documents or whether operation was successful.
     */
    public function update($columns, $condition, $params = [])
    {
        return $this->db->update($this->name, $columns, $condition, $params);
    }

    /**
     * Upsert record.
     *
     * @param string $id the document id.
     * @param array $data the column data (name => value) to be inserted into the bucket or instance.
     *
     * @return bool
     */
    public function upsert($id, $data)
    {
        return $this->db->upsert($this->name, $id, $data);
    }

    /**
     * Update the existing database data, otherwise insert this data
     *
     * @param array|object $data data to be updated/inserted.
     *
     * @return int|null updated/new record id instance.
     */
    public function save($data)
    {
        if (empty($data['_id'])) {
            return $this->insert($data);
        }

        $id = $data['_id'];

        unset($data['_id']);

        $bucketName = $this->db->quoteBucketName($this->name);

        $this->update($data, ["META($bucketName).id" => $id]);

        return $id;
    }

    /**
     * Delete data from the bucket.
     *
     * @param array $condition description of records to remove.
     * @param array $options list of options in format: optionName => optionValue.
     *
     * @return int|bool number of updated documents or whether operation was successful.
     */
    public function delete($condition = [], $options = [])
    {
        return $this->db->delete($this->name, $condition, $options);
    }

    /**
     * Counts records in this bucket.
     *
     * @param array $condition query condition
     * @param array $params list of options in format: optionName => optionValue.
     *
     * @return int records count.
     */
    public function count($condition = [], $params = [])
    {
        return $this->db->count($this->name, $condition, $params);
    }

    /**
     * Build index.
     *
     * @param string|string[] $indexNames names of index
     *
     * @return bool
     */
    public function buildIndex($indexNames)
    {
        return $this->db->buildIndex($this->name, $indexNames);
    }

    /**
     * Create primary index.
     *
     * @param string|null $indexName name of primary index (optional)
     * @param array       $options
     *
     * @return bool
     */
    public function createPrimaryIndex($indexName = null, $options = [])
    {
        return $this->db->createPrimaryIndex($this->name, $indexName, $options);
    }

    /**
     * Drop unnamed primary index.
     *
     * @return bool
     */
    public function dropPrimaryIndex()
    {
        return $this->db->dropPrimaryIndex($this->name);
    }

    /**
     * Creates index.
     *
     * @param string     $indexName
     * @param array      $columns
     * @param array|null $condition
     * @param array      $params
     * @param array      $options
     *
     * @return bool
     */
    public function createIndex($indexName, $columns, $condition = null, &$params = [], $options = [])
    {
        return $this->db->createIndex($this->name, $indexName, $columns, $condition, $params, $options);
    }

    /**
     * Drop index.
     *
     * @param string $indexName
     *
     * @return bool
     */
    public function dropIndex($indexName)
    {
        return $this->db->dropIndex($this->name, $indexName);
    }
}