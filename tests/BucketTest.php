<?php

namespace yiiunit\extensions\couchbase;

use yii\helpers\ArrayHelper;

class BucketTest extends TestCase
{
    public function testBucketCreate()
    {
        try {
            self::$db->createBucket('yii2test-new-bucket');

            $this->assertTrue(true);
        }
        catch (\Exception $e) {
            $this->assertTrue(false);
        }
    }

    public function testBucketList()
    {
        try {
            $list = self::$db->listBuckets();
            $list = ArrayHelper::getColumn($list, 'name');

            $this->assertContains('yii2test', $list);
            $this->assertContains('yii2test-new-bucket', $list);
        }
        catch (\Exception $e) {
            $this->assertTrue(false);
        }
    }

    public function testBucketDrop()
    {
        try {
            self::$db->dropBucket('yii2test-new-bucket');

            $this->assertTrue(true);
        }
        catch (\Exception $e) {
            $this->assertTrue(false);
        }
    }
}