<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace matrozov\couchbase;

use Yii;

/**
 * Class Query
 *
 * @package matrozov\couchbase
 */
class Query extends \yii\db\Query
{
    public $useIndex;

    /**
     * Creates a DB command that can be used to execute this query.
     * @param Connection $db the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return Command the created DB command instance.
     */
    public function createCommand($db = null)
    {
        if ($db === null) {
            $db = Yii::$app->get('couchbase');
        }

        list($sql, $params) = $db->getQueryBuilder()->build($this);

        $bucketName = is_array($this->from) ? reset($this->from) : $this->from;

        return $db->createCommand($sql, $params)->setBucketName($bucketName);
    }

    /**
     * Sets the USE INDEX part of the query.
     * @param string $index index name.
     * @param string $using null/GSI/VIEW
     *
     * @return $this the query object itself
     */
    public function useIndex($index, $using = null)
    {
        $this->useIndex = ['index' => $index, 'using' => $using];
        return $this;
    }

    /**
     * Appends a SQL statement using UNION operator.
     * @param string|Query $sql the SQL statement to be appended using UNION
     * @param bool $all TRUE if using UNION ALL and FALSE if using UNION
     * @return $this the query object itself
     */
    public function union($sql, $all = false)
    {
        $this->union[] = ['type' => 'UNION', 'query' => $sql, 'all' => $all];
        return $this;
    }

    /**
     * Appends a SQL statement using INTERSECT operator.
     * @param string|Query $sql the SQL statement to be appended using INTERSECT
     * @param bool $all TRUE if using INTERSECT ALL and FALSE if using INTERSECT
     * @return $this the query object itself
     */
    public function intersect($sql, $all = false)
    {
        $this->union[] = ['type' => 'INTERSECT', 'query' => $sql, 'all' => $all];
        return $this;
    }

    /**
     * Appends a SQL statement using EXCEPT operator.
     * @param string|Query $sql the SQL statement to be appended using EXCEPT
     * @param bool $all TRUE if using EXCEPT ALL and FALSE if using EXCEPT
     * @return $this the query object itself
     */
    public function except($sql, $all = false)
    {
        $this->union[] = ['type' => 'EXCEPT', 'query' => $sql, 'all' => $all];
        return $this;
    }
}