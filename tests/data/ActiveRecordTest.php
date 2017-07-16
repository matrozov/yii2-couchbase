<?php

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
    private static $id;

    public function testSaveRecord()
    {
        $rec = new Yii2test();
        $rec->a = 'hello_a';
        $rec->b = 'hello_b';

        $this->assertTrue($rec->save());

        self::$id = $rec->_id;

        $this->assertNotFalse(self::$id);
    }

    public function testLoadRecord()
    {
        $rec = Yii2test::findOne(self::$id);

        $this->assertNotNull($rec);
    }

    public function testLoadNullRecord()
    {
        $rec = Yii2test::findOne('invalid_id');

        $this->assertNull($rec);
    }

    public function testFindRecord()
    {
        $rec = Yii2test::findOne(['a' => 'hello_a']);

        $this->assertNotNull($rec);
        $this->assertEquals($rec->_id, self::$id);
    }

    public function testUpdateRecord()
    {
        $rec = Yii2test::findOne(self::$id);

        $this->assertNotNull($rec);

        $rec->a = 'hello_a+';

        $this->assertTrue($rec->save());

        $rec = Yii2test::findOne(self::$id);

        $this->assertNotNull($rec);

        $this->assertEquals($rec->a, 'hello_a+');
        $this->assertEquals($rec->b, 'hello_b');
    }

    public function testDeleteRecord()
    {
        $rec = Yii2test::findOne(self::$id);

        $this->assertNotNull($rec);

        $res = $rec->delete();

        $this->assertNotNull($res);
    }
}