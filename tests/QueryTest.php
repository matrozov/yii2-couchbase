<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace yiiunit\extensions\couchbase;

use matrozov\couchbase\Connection;
use matrozov\couchbase\Query;
use Yii;

class QueryTest extends TestCase
{
    protected function setUp()
    {
        $rows = [];

        for ($i = 0; $i < 100; $i++) {
            $rows[] = ['var_1' => $i, 'var_2' => 99 - $i, 'var_3' => 'test_' . $i];
        }

        self::$db->batchInsert(self::$bucketName, $rows);
    }

    protected function tearDown()
    {
        self::$db->delete(self::$bucketName);
    }

    public function testCommand()
    {
        // Simple select

        $res = (new Query)
            ->select('var_1')
            ->from(self::$bucketName)
            ->all();

        $this->assertCount(100, $res);
        $this->assertEquals(array_keys($res[0]), ['var_1']);

        // Simple where

        $res = (new Query)
            ->select(['var_1'])
            ->from(self::$bucketName)
            ->where(['var_1' => 1])
            ->one();

        $this->assertNotNull($res);
        $this->assertEquals($res['var_1'], 1);

        // Extended where

        $res = (new Query)
            ->select(['var_2', 'var_3'])
            ->from(self::$bucketName)
            ->where(['>=', 'var_2', 50])
            ->count();

        $this->assertEquals($res, 50);

        // IN

        $res = (new Query)
            ->from(self::$bucketName)
            ->where(['var_3' => ['test_1', 'test_2']])
            ->count();

        $this->assertEquals($res, 2);
    }
}