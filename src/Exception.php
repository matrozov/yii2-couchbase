<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace matrozov\couchbase;

/**
 * Exception represents an exception that is caused by some CouchBase-related operations.
 *
 * @package matrozov\couchbase
 */
class Exception extends \yii\base\Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'CouchBase Exception';
    }
}