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
     * @param string $sql the SQL statement to be executed
     * @param array $params input parameters (name => value) for the SQL execution.
     * See [[Command::execute()]] for more details.
     */
    public function execute($sql, $params = [])
    {
        echo "    > execute N1QL: $sql ...";

        $time = microtime(true);

        $this->db->createCommand($sql)->bindValues($params)->execute();

        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }


    // TODO:FixIt many other functions

    /**
     * @param string      $bucketName
     * @param string|null $indexName name of primary index (optional)
     * @param array       $options
     */
    public function createPrimaryIndex($bucketName, $indexName = null, $options = [])
    {
        $this->beginProfile($token = "    > create primary index $bucketName ...");

        $this->db->createCommand()->createPrimaryIndex($bucketName, $indexName, $options)->execute();

        $this->endProfile($token);
    }

    /**
     * @param string $bucketName
     */
    public function dropPrimaryIndex($bucketName)
    {
        $this->beginProfile($token = "    > drop index $bucketName ...");

        $this->db->createCommand()->dropPrimaryIndex($bucketName)->execute();

        $this->endProfile($token);
    }

    /**
     * @param string     $bucketName
     * @param string     $indexName
     * @param array      $columns
     * @param array|null $condition
     * @param array      $params
     * @param array      $options
     */
    public function createIndex($bucketName, $indexName, $columns, $condition = null, &$params = [], $options = [])
    {
        $this->beginProfile($token = "    > create index $bucketName.$indexName ...");

        $this->db->createCommand()->createIndex($bucketName, $indexName, $columns, $condition, $params, $options)->execute();

        $this->endProfile($token);
    }

    /**
     * @param string $bucketName
     * @param string $indexName
     */
    public function dropIndex($bucketName, $indexName)
    {
        $this->beginProfile($token = "    > drop index $bucketName.$indexName ...");

        $this->db->createCommand()->dropIndex($bucketName, $indexName)->execute();

        $this->endProfile($token);
    }

    /**
     * Creates new bucket with the specified options.
     * @param string $bucketName name of the bucket
     * @param array $options bucket options in format: "name" => "value"
     */
    public function createBucket($bucketName, $options = [])
    {
        $this->beginProfile($token = "    > create bucket $bucketName ...");

        $this->db->createBucket($bucketName, $options);

        $this->endProfile($token);
    }

    /**
     * Drops existing bucket.
     * @param string $bucketName name of the bucket
     */
    public function dropBucket($bucketName)
    {
        $this->beginProfile($token = "    > drop bucket $bucketName ...");

        $this->db->getBucket($bucketName)->drop();

        $this->endProfile($token);
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