<?php
namespace RainCity\WPF;

use RainCity\TestHelper\ReflectionHelper;
use RainCity\WPF\Helpers\WordpressTestCase;

/**
 * @covers \RainCity\WPF\WordPressUserData
 *
 */
class WordPressUserDataTest extends WordpressTestCase
{
    const TEST_USER_ID = 98765;
    const TEST_KEY = 'TestKey';
    const TEST_DATA = 'TestData';

    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        global $_TestData;
        unset($_TestData[TestWordPressUserData::USER_META_KEY]);
    }

    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDownAfterClass()
     */
    public static function tearDownAfterClass(): void
    {
        global $_TestData;
        unset($_TestData);
    }


    public function testMissingConst () {
        $this->expectException("Error");

        new class(self::TEST_USER_ID) extends WordPressUserData {};
    }

    public function testCtor () {
        $obj = new TestWordPressUserData(self::TEST_USER_ID);

        $propValue = ReflectionHelper::getObjectProperty(WordPressUserData::class, 'wpUserId', $obj);

        $this->assertEquals(self::TEST_USER_ID, $propValue);
    }

    public function testCtor_existingData () {
        $obj = new TestWordPressUserData(self::TEST_USER_ID);
        $obj->setData(self::TEST_KEY, self::TEST_DATA);

        $obj2 = new TestWordPressUserData(self::TEST_USER_ID);

        $data = ReflectionHelper::getObjectProperty(WordPressUserData::class, 'data', $obj2);

        $this->assertEquals(self::TEST_DATA, unserialize($data[self::TEST_KEY]));
    }

    public function testGetData_noData () {
        $obj = new TestWordPressUserData(self::TEST_USER_ID);

        $result = $obj->getData('unknown_key');

        $this->assertNull($result, 'Request for unknown data should have returned null');
    }

    public function testGetData_hasData() {
        $obj = new TestWordPressUserData(self::TEST_USER_ID);

        ReflectionHelper::setObjectProperty(WordPressUserData::class, 'data', array(self::TEST_KEY => serialize(self::TEST_DATA)), $obj);

        $data = $obj->getData(self::TEST_KEY);

        $this->assertEquals(self::TEST_DATA, $data);
    }

    public function testSetData() {
        $obj = new TestWordPressUserData(self::TEST_USER_ID);

        $obj->setData(self::TEST_KEY, self::TEST_DATA);

        $data = ReflectionHelper::getObjectProperty(WordPressUserData::class, 'data', $obj);

        $this->assertEquals(self::TEST_DATA, unserialize($data[self::TEST_KEY]));
    }
}

class TestWordPressUserData extends WordPressUserData {
    const USER_META_KEY = 'AnonUserData';
};

