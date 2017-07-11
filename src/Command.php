<?php
/**
 *
 */

namespace matrozov\couchbase;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Object;

class Command extends Object
{
    /**
     * @var Connection the CouchBase connection that this command is associated with.
     */
    public $db;

    /**
     * @var string the N1QL statement that this command represents
     */
    private $_sql;

    /**
     * Returns the N1QL statement for this command.
     * @return string the N1QL statement to be executed
     */
    public function getSql()
    {
        return $this->_sql;
    }

    public function setSql($sql)
    {
        $this->_sql = $sql;
    }

    public function bindValues($params)
    {
        if (empty($values)) {
            return $this;
        }

        foreach ($values as $name => $value) {
            if (is_array($value)) {
                $this->params[$name] = $value[0];
            }
            else {
                $this->params[$name] = $value;
            }
        }

        return $this;
    }
}