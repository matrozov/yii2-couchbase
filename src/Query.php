<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace matrozov\couchbase;

use Yii;
use yii\db\Expression;

/**
 * Class Query
 *
 * @property string|array|Expression $returning
 *
 * @package matrozov\couchbase
 */
class Query extends \yii\db\Query
{
    /**
     * @var array how to returning the query results. For example, `['company', 'department']`.
     * This is used to construct the RETURNING clause in a SQL statement.
     */
    public $returning;

    /**
     * Sets the RETURNING part of the query.
     * @param string|array|Expression $columns the columns to be grouped by.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     *
     * @return $this the query object itself
     * @see addGroupBy()
     */
    public function returning($columns)
    {
        if ($columns instanceof Expression) {
            $columns = [$columns];
        }
        elseif (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }

        $this->returning = $columns;

        return $this;
    }
    /**
     * Adds additional RETURNING columns to the existing ones.
     * @param string|array $columns additional columns to be returning.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     *
     * @return $this the query object itself
     * @see returning()
     */
    public function addReturning($columns)
    {
        if ($columns instanceof Expression) {
            $columns = [$columns];
        }
        elseif (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }

        if ($this->returning === null) {
            $this->returning = $columns;
        }
        else {
            $this->returning = array_merge($this->returning, $columns);
        }

        return $this;
    }

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

        return $db->createCommand($sql, $params);
    }

    /**
     * @inheritdoc
     */
    public static function create($from)
    {
        /** @var Query $query */
        $query = parent::create($from);
        $query->returning = $from->returning;

        return $query;
    }
}