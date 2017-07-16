<?php

namespace yiiunit\extensions\couchbase;

use matrozov\couchbase\Query;
use Yii;

class CommandTest extends TestCase
{
    public function testCommand()
    {
        // Insert record

        $id = Yii::$app->couchbase->insert(self::$bucketName, [
            'a' => 'hello',
        ]);

        $this->assertNotFalse($id);

        // Update record

        $affectedRows = Yii::$app->couchbase->update(self::$bucketName, ['a' => 'hello2'], ['META().id' => $id]);

        $this->assertTrue($affectedRows === 1);

        // Delete record

        $affectedRows = Yii::$app->couchbase->delete(self::$bucketName, ['META().id' => $id]);

        $this->assertTrue($affectedRows === 1);

        // Batch insert

        $insertIds = Yii::$app->couchbase->batchInsert(self::$bucketName, [
            ['a' => 'hello_1', 'case' => 'batch-insert'],
            ['a' => 'hello_2', 'case' => 'batch-insert'],
            ['a' => 'hello_3', 'case' => 'batch-insert'],
        ]);

        $this->assertTrue(count($insertIds) == 3);

        // Delete condition

        $affectedRows = Yii::$app->couchbase->delete(self::$bucketName, [
            'case' => 'batch-insert',
        ]);

        $this->assertTrue($affectedRows === 3);
    }
}