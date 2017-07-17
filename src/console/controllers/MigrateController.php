<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace matrozov\couchbase\console\controllers;

use matrozov\couchbase\Connection;
use matrozov\couchbase\Exception;
use matrozov\couchbase\Migration;
use matrozov\couchbase\Query;
use Yii;
use yii\console\controllers\BaseMigrateController;
use yii\helpers\ArrayHelper;

/**
 *
 */
class MigrateController extends BaseMigrateController
{
    /**
     * @var string|array the name of the bucket for keeping applied migration information.
     */
    public $migrationBucket = 'migration';

    /**
     * @inheritdoc
     */
    public $templateFile = '@matrozov/couchbase/views/migration.php';

    /**
     * @var Connection|string the DB connection object or the application
     * component ID of the DB connection.
     */
    public $db = 'couchbase';

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['migrationBucket', 'db'] // global for all actions
        );
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * It checks the existence of the [[migrationPath]].
     * @param \yii\base\Action $action the action to be executed.
     * @throws Exception if db component isn't configured
     * @return bool whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            if ($action->id !== 'create') {
                if (is_string($this->db)) {
                    $this->db = Yii::$app->get($this->db);
                }

                if (!$this->db instanceof Connection) {
                    throw new Exception("The 'db' option must refer to the application component ID of a Couchbase connection.");
                }
            }

            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Creates a new migration instance.
     * @param string $class the migration class name
     * @return Migration the migration instance
     */
    protected function createMigration($class)
    {
        // since Yii 2.0.12 includeMigrationFile() exists, which replaced the code below
        // remove this construct when composer requirement raises above 2.0.12
        if (method_exists($this, 'includeMigrationFile')) {
            $this->includeMigrationFile($class);
        }
        else {
            $class = trim($class, '\\');

            if (strpos($class, '\\') === false) {
                $file = $this->migrationPath . DIRECTORY_SEPARATOR . $class . '.php';

                require_once($file);
            }
        }

        return new $class(['db' => $this->db]);
    }


    /**
     * @inheritdoc
     */
    protected function getMigrationHistory($limit)
    {
        $this->ensureBaseMigrationHistory();

        $query = (new Query())
            ->select(['version', 'apply_time'])
            ->from($this->migrationBucket)
            ->orderBy(['apply_time' => SORT_DESC, 'version' => SORT_DESC]);

        if (empty($this->migrationNamespaces)) {
            $query->limit($limit);
            $rows = $query->all($this->db);
            $history = ArrayHelper::map($rows, 'version', 'apply_time');
            unset($history[self::BASE_MIGRATION]);
            return $history;
        }

        $rows = $query->all($this->db);
        $history = [];

        foreach ($rows as $key => $row) {
            if ($row['version'] === self::BASE_MIGRATION) {
                continue;
            }

            if (preg_match('/m?(\d{6}_?\d{6})(\D.*)?$/is', $row['version'], $matches)) {
                $time = str_replace('_', '', $matches[1]);
                $row['canonicalVersion'] = $time;
            }
            else {
                $row['canonicalVersion'] = $row['version'];
            }

            $row['apply_time'] = (int)$row['apply_time'];
            $history[] = $row;
        }

        usort($history, function ($a, $b) {
            if ($a['apply_time'] === $b['apply_time']) {
                if (($compareResult = strcasecmp($b['canonicalVersion'], $a['canonicalVersion'])) !== 0) {
                    return $compareResult;
                }

                return strcasecmp($b['version'], $a['version']);
            }

            return ($a['apply_time'] > $b['apply_time']) ? -1 : +1;
        });

        $history = array_slice($history, 0, $limit);
        $history = ArrayHelper::map($history, 'version', 'apply_time');

        return $history;
    }

    private $baseMigrationEnsured = false;

    /**
     * Ensures migration history contains at least base migration entry.
     */
    protected function ensureBaseMigrationHistory()
    {
        if ($this->baseMigrationEnsured) {
            return;
        }

        try {
            $this->db->getBucket($this->migrationBucket);
        }
        catch (Exception $e) {
            if (($e->getPrevious() instanceof \Couchbase\Exception) && ($e->getPrevious()->getCode() == 2)) {
                $this->db->createBucket($this->migrationBucket);
            }
            else {
                throw $e;
            }
        }

        $row = (new Query)->select(['version'])
            ->from($this->migrationBucket)
            ->andWhere(['version' => self::BASE_MIGRATION])
            ->limit(1)
            ->one($this->db);

        if (empty($row)) {
            $this->addMigrationHistory(self::BASE_MIGRATION);
        }

        $this->baseMigrationEnsured = true;
    }

    /**
     * @inheritdoc
     */
    protected function addMigrationHistory($version)
    {
        $this->db->getBucket($this->migrationBucket)->insert([
            'version' => $version,
            'apply_time' => time(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function removeMigrationHistory($version)
    {
        $this->db->getBucket($this->migrationBucket)->delete([
            'version' => $version,
        ]);
    }
}
