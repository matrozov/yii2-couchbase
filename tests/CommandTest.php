<?php

namespace yiiunit\extensions\couchbase;

class CommandTest extends TestCase
{
    private static $insertId;

    public function testInsert()
    {
        self::$insertId = self::$db->insert(self::$bucketName, [
            'a' => 'hello',
        ]);

        $this->assertNotFalse(self::$insertId);
    }

    public function testUpdate()
    {
        $affectedRows = self::$db->update(self::$bucketName, ['a' => 'hello2'], ['META().id' => self::$insertId]);

        $this->assertTrue($affectedRows === 1);
    }

    public function testDelete()
    {
        $affectedRows = self::$db->delete(self::$bucketName, ['META().id' => self::$insertId]);

        $this->assertTrue($affectedRows === 1);
    }

    public function testBatchInsert()
    {
        $insertIds = self::$db->batchInsert(self::$bucketName, [
            ['a' => 'hello_1', 'case' => 'batch-insert'],
            ['a' => 'hello_2', 'case' => 'batch-insert'],
            ['a' => 'hello_3', 'case' => 'batch-insert'],
        ]);

        $this->assertTrue(count($insertIds) == 3);
    }

    public function testDeleteCondition()
    {
        $affectedRows = self::$db->delete(self::$bucketName, [
            'case' => 'batch-insert',
        ]);

        $this->assertTrue($affectedRows === 3);
    }
}