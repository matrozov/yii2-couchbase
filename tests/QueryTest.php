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
    public static $bucketNameJoin = 'yii2test-join';

    protected function setUp()
    {
        self::$db->createBucket(self::$bucketNameJoin);

        self::$db->createPrimaryIndex(self::$bucketNameJoin);

        $id = self::$db->insert(self::$bucketNameJoin, [
            'var_4' => 'bugaga',
        ]);

        $rows = [];

        for ($i = 0; $i < 100; $i++) {
            $rows[] = ['var_1' => $i, 'var_2' => 99 - $i, 'var_3' => 'test_' . $i, 'var_rel' => $id];
        }

        self::$db->batchInsert(self::$bucketName, $rows);
    }

    protected function tearDown()
    {
        self::$db->delete(self::$bucketName);

        self::$db->dropBucket(self::$bucketNameJoin);
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

        // JOIN

        /*$res = (new Query)
            ->from(self::$bucketNameJoin)
            ->join('JOIN', ['b' => self::$bucketName], '`b`.`var_rel`')
            ->createCommand()->rawSql;

        echo PHP_EOL . PHP_EOL;
        echo $res;
        echo PHP_EOL . PHP_EOL;*/

        // UNION

        $q = (new Query)
            ->from(self::$bucketName)
            ->limit(1);

        $res = (new Query)
            ->from(self::$bucketNameJoin)
            ->union($q)
            ->all();

        $this->assertCount(2, $res);
    }
}