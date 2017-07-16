<?php

namespace yiiunit\extensions\couchbase;

use Yii;
use yii\helpers\ArrayHelper;

class ManageBucketTest extends TestCase
{
    public function testManageBucket()
    {
        // Create bucket

        try {
            Yii::$app->couchbase->createBucket('yii2test-new-bucket');
        }
        catch (\Exception $e) {
            $this->assertTrue(false);
        }

        // List bucket

        try {
            $list = Yii::$app->couchbase->listBuckets();
        }
        catch (\Exception $e) {
            $list = [];
        }

        $list = ArrayHelper::getColumn($list, 'name');

        $this->assertContains('yii2test', $list);
        $this->assertContains('yii2test-new-bucket', $list);

        // Drop bucket

        try {
            Yii::$app->couchbase->dropBucket('yii2test-new-bucket');
        }
        catch (\Exception $e) {
            $this->assertTrue(false);
        }
    }
}