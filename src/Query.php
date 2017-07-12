<?php
/**
 *
 */

namespace matrozov\couchbase;

use Yii;

class Query extends \yii\db\Query
{
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
}