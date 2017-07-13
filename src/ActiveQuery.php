<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace matrozov\couchbase;

use yii\db\ActiveQueryInterface;
use yii\db\ActiveQueryTrait;
use yii\db\ActiveRelationTrait;

/**
 * Class ActiveQuery
 *
 * @property Bucket $bucket
 *
 * @package matrozov\couchbase
 */
class ActiveQuery extends Query implements ActiveQueryInterface
{
    use ActiveQueryTrait;
    use ActiveRelationTrait;

    /**
     * @event Event an event that is triggered when the query is initialized via [[init()]].
     */
    const EVENT_INIT = 'init';

    /**
     * Constructor.
     * @param array $modelClass the model class associated with this query
     * @param array $config configurations to be applied to the newly created query object
     */
    public function __construct($modelClass, $config = [])
    {
        $this->modelClass = $modelClass;

        parent::__construct($config);
    }

    /**
     * Initializes the object.
     * This method is called at the end of the constructor. The default implementation will trigger
     * an [[EVENT_INIT]] event. If you override this method, make sure you call the parent implementation at the end
     * to ensure triggering of the event.
     */
    public function init()
    {
        parent::init();

        $this->trigger(self::EVENT_INIT);
    }

    /**
     * @inheritdoc
     */
    public function prepare($builder)
    {
        $this->getBucket($builder->db);

        if ($this->primaryModel !== null) {
            // lazy loading
            if ($this->via instanceof self) {
                // via pivot bucket
                $viaModels = $this->via->findJunctionRows([$this->primaryModel]);

                $this->filterByModels($viaModels);
            }
            elseif (is_array($this->via)) {
                // via relation
                /* @var $viaQuery ActiveQuery */
                list($viaName, $viaQuery) = $this->via;

                if ($viaQuery->multiple) {
                    $viaModels = $viaQuery->all();
                    $this->primaryModel->populateRelation($viaName, $viaModels);
                }
                else {
                    $model = $viaQuery->one();
                    $this->primaryModel->populateRelation($viaName, $model);
                    $viaModels = $model === null ? [] : [$model];
                }

                $this->filterByModels($viaModels);
            }
            else {
                $this->filterByModels([$this->primaryModel]);
            }
        }

        return parent::prepare($builder);
    }

    /**
     * Executes query and returns all results as an array.
     * @param Connection $db the Couchbase connection used to execute the query.
     * If null, the Couchbase connection returned by [[modelClass]] will be used.
     * @return array|ActiveRecord the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * Executes query and returns a single row of result.
     * @param Connection $db the Couchbase connection used to execute the query.
     * If null, the Couchbase connection returned by [[modelClass]] will be used.
     * @return ActiveRecord|array|null a single row of query result. Depending on the setting of [[asArray]],
     * the query result may be either an array or an ActiveRecord object. Null will be returned
     * if the query results in nothing.
     */
    public function one($db = null)
    {
        $row = parent::one($db);

        if ($row !== false) {
            $models = $this->populate([$row]);

            return reset($models) ?: null;
        }
        else {
            return null;
        }
    }

    /**
     * Performs 'findAndModify' query and returns a single row of result.
     * Warning: in case 'new' option is set to 'false' (which is by default) usage of this method may lead
     * to unexpected behavior at some Active Record features, because object will be populated by outdated data.
     * @param array $update update criteria
     * @param array $options list of options in format: optionName => optionValue.
     * @param Connection $db the Couchbase connection used to execute the query.
     * @return ActiveRecord|array|null the original document, or the modified document when $options['new'] is set.
     * Depending on the setting of [[asArray]], the query result may be either an array or an ActiveRecord object.
     * Null will be returned if the query results in nothing.
     */
    public function modify($update, $options = [], $db = null)
    {
        $row = parent::modify($update, $options, $db);

        if ($row !== null) {
            $models = $this->populate([$row]);

            return reset($models) ?: null;
        }
        else {
            return null;
        }
    }

    /**
     * Returns the Couchbase bucket for this query.
     * @param Connection $db Couchbase connection.
     * @return Bucket bucket instance.
     */
    public function getBucket($db = null)
    {
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;

        if ($db === null) {
            $db = $modelClass::getDb();
        }

        if ($this->select === null) {
            $bucketName = $db->quoteBucketName($modelClass::bucketName());

            $primaryModel = $this->primaryModel ?: ActiveRecord::className();

            $primaryKey = $primaryModel::primaryKey()[0];
            $primaryKey = $db->quoteColumnName($primaryKey);

            $this->select = "meta($bucketName).id AS $primaryKey, $bucketName.*";
        }

        if ($this->from === null) {
            $this->from = $modelClass::bucketName();
        }

        return $db->getBucket($this->from);
    }

    /**
     * @return string bucket name
     */
    protected function getBucketName()
    {
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;

        return $modelClass::bucketName();
    }

    /**
     * Converts the raw query results into the format as specified by this query.
     * This method is internally used to convert the data fetched from Couchbase
     * into the format as required by this query.
     * @param array $rows the raw query result from Couchbase
     * @return array the converted query result
     */
    public function populate($rows)
    {
        if (empty($rows)) {
            return [];
        }

        $models = $this->createModels($rows);

        if (!empty($this->with)) {
            $this->findWith($this->with, $models);
        }

        if (!$this->asArray) {
            foreach ($models as $model) {
                $model->afterFind();
            }
        }

        return $models;
    }
}