<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace matrozov\couchbase;

use yii\base\Component;
use yii\db\MigrationInterface;
use yii\di\Instance;

abstract class Migration extends Component implements MigrationInterface
{
    /**
     * @var Connection|array|string the Couchbase connection object or the application component ID of the Couchbase connection
     * that this migration should work with.
     */
    public $db = 'couchbase';

    /**
     * Initializes the migration.
     * This method will set [[db]] to be the 'db' application component, if it is null.
     */
    public function init()
    {
        parent::init();

        $this->db = Instance::ensure($this->db, Connection::className());
    }

    /**
     * Executes a N1QL statement.
     * This method executes the specified SQL statement using [[db]].
     *
     * @param string $sql    the SQL statement to be executed
     * @param array  $params input parameters (name => value) for the SQL execution.
     *                       See [[Command::execute()]] for more details.
     *
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function execute($sql, $params = [])
    {
        echo "    > execute N1QL: $sql ...";

        $time = microtime(true);

        $this->db->createCommand($sql)->bindValues($params)->execute();

        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Creates new bucket with the specified options.
     *
     * @param string $bucketName name of the bucket
     * @param array  $options    bucket options in format: "name" => "value"
     *
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function createBucket($bucketName, $options = [])
    {
        $this->beginProfile($token = "    > create bucket $bucketName ...");

        $this->db->createBucket($bucketName, $options);

        $this->endProfile($token);
    }

    /**
     * Drops existing bucket.
     *
     * @param string $bucketName name of the bucket
     *
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function dropBucket($bucketName)
    {
        $this->beginProfile($token = "    > drop bucket $bucketName ...");

        $this->db->getBucket($bucketName)->drop();

        $this->endProfile($token);
    }

    /**
     * Insert record.
     *
     * @param string $bucketName the bucket that new rows will be inserted into.
     * @param array  $data       the column data (name => value) to be inserted into the bucket or instance
     *
     * @return int|false inserted id
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function insert($bucketName, $data)
    {
        $this->beginProfile($token = "    > insert into $bucketName ...");

        $result = $this->db->insert($bucketName, $data);

        $this->endProfile($token);

        return $result;
    }

    /**
     * Batch insert record.
     *
     * @param string $bucketName the bucket that new rows will be inserted into.
     * @param array  $rows       the rows to be batch inserted into the bucket
     *
     * @return int[]|false inserted ids
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function batchInsert($bucketName, $rows)
    {
        $this->beginProfile($token = "    > batch insert into $bucketName ...");

        $result = $this->db->batchInsert($bucketName, $rows);

        $this->endProfile($token);

        return $result;
    }

    /**
     * Update record.
     *
     * @param string       $bucketName the bucket to be updated.
     * @param array        $columns    the column data (name => value) to be updated.
     * @param string|array $condition  the condition that will be put in the WHERE part. Please
     *                                 refer to [[Query::where()]] on how to specify condition.
     * @param array        $params     the parameters to be bound to the command
     *
     * @return int affected rows
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function update($bucketName, $columns, $condition, $params = [])
    {
        $this->beginProfile($token = "    > update record $bucketName ...");

        $result = $this->db->update($bucketName, $columns, $condition, $params);

        $this->endProfile($token);

        return $result;
    }

    /**
     * Upsert record.
     *
     * @param string $bucketName the bucket to be updated.
     * @param string $id         the document id.
     * @param array  $data       the column data (name => value) to be inserted into the bucket or instance.
     *
     * @return bool
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function upsert($bucketName, $id, $data)
    {
        $this->beginProfile($token = "    > upsert record $bucketName.$id ...");

        $result = $this->db->upsert($bucketName, $id, $data);

        $this->endProfile($token);

        return $result;
    }

    /**
     * Delete record
     *
     * @param string       $bucketName the bucket where the data will be deleted from.
     * @param string|array $condition  the condition that will be put in the WHERE part. Please
     *                                 refer to [[Query::where()]] on how to specify condition.
     * @param array        $params     the parameters to be bound to the command
     *
     * @return int affected rows
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function delete($bucketName, $condition = '', $params = [])
    {
        $this->beginProfile($token = "    > delete record $bucketName ...");

        $result = $this->db->delete($bucketName, $condition, $params);

        $this->endProfile($token);

        return $result;
    }

    /**
     * Build index.
     *
     * @param string          $bucketName
     * @param string|string[] $indexNames names of index
     *
     * @return bool
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function buildIndex($bucketName, $indexNames)
    {
        $this->beginProfile($token = "    > build index $bucketName ...");

        $result = $this->db->buildIndex($bucketName, $indexNames);

        $this->endProfile($token);

        return $result;
    }

    /**
     * @param string      $bucketName
     * @param string|null $indexName name of primary index (optional)
     * @param array       $options
     *
     * @return bool
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function createPrimaryIndex($bucketName, $indexName = null, $options = [])
    {
        $this->beginProfile($token = "    > create primary index $bucketName ...");

        $result = $this->db->createPrimaryIndex($bucketName, $indexName, $options);

        $this->endProfile($token);

        return $result;
    }

    /**
     * @param string $bucketName
     *
     * @return bool
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function dropPrimaryIndex($bucketName)
    {
        $this->beginProfile($token = "    > drop index $bucketName ...");

        $result = $this->db->dropPrimaryIndex($bucketName);

        $this->endProfile($token);

        return $result;
    }

    /**
     * @param string     $bucketName
     * @param string     $indexName
     * @param array      $columns
     * @param array|null $condition
     * @param array      $params
     * @param array      $options
     *
     * @return bool
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function createIndex($bucketName, $indexName, $columns, $condition = null, &$params = [], $options = [])
    {
        $this->beginProfile($token = "    > create index $bucketName.$indexName ...");

        $result = $this->db->createIndex($bucketName, $indexName, $columns, $condition, $params, $options);

        $this->endProfile($token);

        return $result;
    }

    /**
     * @param string $bucketName
     * @param string $indexName
     *
     * @return bool
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function dropIndex($bucketName, $indexName)
    {
        $this->beginProfile($token = "    > drop index $bucketName.$indexName ...");

        $result = $this->db->dropIndex($bucketName, $indexName);

        $this->endProfile($token);

        return $result;
    }

    /**
     * @var array opened profile tokens.
     */
    private $profileTokens = [];

    /**
     * Logs the incoming message.
     * By default this method sends message to 'stdout'.
     * @param string $string message to be logged.
     */
    protected function log($string)
    {
        echo $string;
    }

    /**
     * Marks the beginning of a code block for profiling.
     * @param string $token token for the code block.
     */
    protected function beginProfile($token)
    {
        $this->profileTokens[$token] = microtime(true);

        $this->log($token);
    }

    /**
     * Marks the end of a code block for profiling.
     * @param string $token token for the code block.
     */
    protected function endProfile($token)
    {
        if (isset($this->profileTokens[$token])) {
            $time = microtime(true) - $this->profileTokens[$token];

            unset($this->profileTokens[$token]);
        }
        else {
            $time = 0;
        }

        $this->log(" done (time: " . sprintf('%.3f', $time) . "s)\n");
    }
}