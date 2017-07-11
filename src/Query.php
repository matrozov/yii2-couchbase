<?php
/**
 *
 */

namespace matrozov\couchbase;

use Yii;
use yii\base\Component;
use yii\db\QueryInterface;
use yii\db\QueryTrait;

class Query extends Component implements QueryInterface
{
    use QueryTrait;

    /**
     * @var array the columns being selected. For example, `['id', 'name']`.
     * This is used to construct the SELECT clause in a N1QL statement. If not set, it means selecting all columns.
     * @see select()
     */
    public $select;

    /**
     * @var string the bucked to be selected from.
     * @see from()
     */
    public $from;

    /**
     * Returns the CouchBase bucked for this query.
     *
     * @param Database $db
     *
     * @return Bucked CouchBase collection instance.
     */
    public function getBucked($db = null)
    {
        if ($db === null) {
            $db = Yii::$app->get('couchbase');
        }

        return $db->getBucked($this->from);
    }

    /**
     * Set the bucked to be selected from.
     *
     * @param $bucked
     *
     * @return $this
     */
    public function from($bucked)
    {
        $this->from = $bucked;

        return $this;
    }

    public function count($q = '*', $db = null)
    {
        $bucked = $this->getBucked($db);

        // TODO: Implement count() method.
    }

    public function exists($db = null)
    {
        $bucked = $this->getBucked($db);

        // TODO: Implement exists() method.
    }

    public function one($db = null)
    {
        $bucked = $this->getBucked($db);

        // TODO: Implement one() method.
    }

    public function all($db = null)
    {
        $bucked = $this->getBucked($db);

        // TODO: Implement all() method.
    }

    /**
     * Converts the raw query results into the format as specified by this query.
     * This method is internally used to convert the data fetched from database
     * into the format as required by this query.
     *
     * @param array $rows the raw query result from database
     *
     * @return array the converted query result
     */
    public function populate($rows)
    {
        if ($this->indexBy === null) {
            return $rows;
        }

        $result = [];

        foreach ($rows as $row) {
            if (is_string($this->indexBy)) {
                $key = $row[$this->indexBy];
            }
            else {
                $key = call_user_func($this->indexBy, $row);
            }

            $result[$key] = $row;
        }

        return $result;
    }
}