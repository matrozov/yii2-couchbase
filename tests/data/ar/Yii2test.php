<?php

namespace yiiunit\extensions\couchbase\data\ar;

use matrozov\couchbase\ActiveRecord;

class Yii2test extends ActiveRecord
{
    public function attributes()
    {
        return ['_id', 'a', 'b'];
    }
}