<?php
/**
 *
 */

namespace matrozov\couchbase;

class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * The prefix for automatically generated query binding parameters.
     */
    const PARAM_PREFIX = '$qp';
}