<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace yiiunit\extensions\couchbase;

use yiiunit\extensions\couchbase\data\ar\Yii2test;

/**
 * Class ActiveRecordTest
 * @package yiiunit\extensions\couchbase
 *
 * @property string $_id;
 * @property string $a;
 * @property string $b;
 */
class ActiveRecordTest extends TestCase
{
    public function testRecord()
    {
        // Create record

        $rec = new Yii2test();
        $rec->a = 'hello_a';
        $rec->b = 'hello_b';

        $this->assertTrue($rec->save());

        $id = $rec->_id;

        $this->assertNotFalse($id);

        // Load record

        $rec = Yii2test::findOne($id);

        $this->assertNotNull($rec);
        $this->assertEquals($rec->_id, $id);

        // Load invalid record

        $recInvalid = Yii2test::findOne('invalid_id');

        $this->assertNull($recInvalid);

        // Update record

        $rec->a = 'hello_a+';

        $this->assertTrue($rec->save());

        $rec = Yii2test::findOne($id);

        $this->assertNotNull($rec);

        $this->assertEquals($rec->a, 'hello_a+');
        $this->assertEquals($rec->b, 'hello_b');

        // Delete record

        $res = $rec->delete();

        $this->assertNotNull($res);
    }
}