<?php
/**
 * @link https://github.com/matrozov/yii2-couchbase
 * @author Oleg Matrozov <oleg.matrozov@gmail.com>
 */

namespace yiiunit\extensions\couchbase;

use Yii;
use yii\helpers\ArrayHelper;

class ManageBucketTest extends TestCase
{
    public function testManageBucket()
    {
        // Create bucket

        try {
            self::$db->createBucket('yii2test-new-bucket');
        }
        catch (\Exception $e) {
            $this->assertTrue(false);
        }

        // List bucket

        try {
            $list = self::$db->listBuckets();
        }
        catch (\Exception $e) {
            $list = [];
        }

        $list = ArrayHelper::getColumn($list, 'name');

        $this->assertContains('yii2test', $list);
        $this->assertContains('yii2test-new-bucket', $list);

        // Drop bucket

        try {
            self::$db->dropBucket('yii2test-new-bucket');
        }
        catch (\Exception $e) {
            $this->assertTrue(false);
        }
    }
}