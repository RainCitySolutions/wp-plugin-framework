<?php
namespace RainCity\WPF;

use RainCity\Logging\Logger;
use RainCity\TestHelper\ReflectionHelper;
use RainCity\TestHelper\StubLogger;
use RainCity\WPF\Helpers\WordpressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(\RainCity\WPF\WordPressOptions::class)]
class WordPressOptionsTest extends WordpressTestCase
{
    /* @var WordPressOptions */
    private $testOptions;

    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void {
        Logger::setLogger(StubLogger::class);
    }

    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetTestObject();
    }

    /**
     * Resets the test object, in effect causing it to re-read the options
     * from the "database".
     */
    private function resetTestObject() {
        // Remove any current singletons
        ReflectionHelper::setClassProperty(WordPressOptions::class, 'instance', array(), true);

        $this->testOptions = TestOptions::instance();
    }

    public function testInitializeOptions_initNewOption () {
        delete_option(TestOptions::OPTIONS_NAME);

        // Reset test object
        $this->resetTestObject();

        foreach (TestOptions::OPTION_NAMES as $option) {
            $value = $this->testOptions->getValue($option);

            self::assertNotNull($value);
            self::assertEquals('', $value);
        }
    }

    public function testInitializeOptions_removeOldOption () {
        $oldOption = 'oldOption';

        update_option(TestOptions::OPTIONS_NAME,
            array(
                $oldOption => 'XoptionX',
                TestOptions::OPTION_1 => 'value1',
                TestOptions::OPTION_2 => 'value2'
            ));

        // Reset test object
        $this->resetTestObject();

        self::assertNull($this->testOptions->getValue($oldOption));
        self::assertEquals('value1', $this->testOptions->getValue(TestOptions::OPTION_1));
        self::assertEquals('value2', $this->testOptions->getValue(TestOptions::OPTION_2));
    }

    public function testInitializeOptions_addNewOption () {
        update_option(TestOptions::OPTIONS_NAME,
            array(
                TestOptions::OPTION_2 => 'option2'
            ));

        // Reset test object
        $this->resetTestObject();

        $value = $this->testOptions->getValue(TestOptions::OPTION_1);

        self::assertNotNull($value);
        self::assertEquals('', $value);
    }

    public function testGetValues_noData () {
        delete_option(TestOptions::OPTIONS_NAME);

        // Reset test object
        $this->resetTestObject();

        $values = $this->testOptions->getValues();

        self::assertCount(count(TestOptions::OPTION_NAMES), $values);

        foreach (TestOptions::OPTION_NAMES as $option) {
            self::assertArrayHasKey($option, $values);
            self::assertEquals('', $values[$option]);
        }
    }

    public function testSetValue_invalidOption () {
        $invalidOption = 'badKey';

        $this->testOptions->setValue($invalidOption, 'testValue');

        $value = $this->testOptions->getValue($invalidOption);

        self::assertNull($value);
    }

    public function testSetValue_validOption () {
        $testValue = 'testValue';

        $this->testOptions->setValue(TestOptions::OPTION_2, $testValue);

        $value = $this->testOptions->getValue(TestOptions::OPTION_2);

        self::assertEquals($testValue, $value);
    }

    public function testSave () {
        $testValue = 'saveValue';

        $this->testOptions->setValue(TestOptions::OPTION_2, $testValue);

        // Verify the new value is not saved yet
        $options = get_option(TestOptions::OPTIONS_NAME);

        self::assertArrayHasKey(TestOptions::OPTION_2, $options);
        self::assertNotEquals($testValue, $options[TestOptions::OPTION_2]);

        $this->testOptions->save();

        // Verify the new value has now been saved
        $options = get_option(TestOptions::OPTIONS_NAME);

        self::assertArrayHasKey(TestOptions::OPTION_2, $options);
        self::assertEquals($testValue, $options[TestOptions::OPTION_2]);
    }

    public function testGetFormFieldInfo_invalidKey() {
        $value = $this->testOptions->getFormFieldInfo('invalidKey');

        self::assertNull($value);
    }

    public function testGetFormFieldInfo_validKeyNoValue() {
        update_option(TestOptions::OPTIONS_NAME,
            array (
                TestOptions::OPTION_1 => 'aValue',
                TestOptions::OPTION_2 => null
            )
        );

        // Reset test object
        $this->resetTestObject();
        $value = $this->testOptions->getFormFieldInfo(TestOptions::OPTION_2);

        self::assertIsArray($value);
        self::assertCount(3, $value);
        self::assertEquals(TestOptions::OPTION_2, $value[0]);
        self::assertEquals(TestOptions::OPTIONS_NAME.'['.TestOptions::OPTION_2.']' , $value[1]);
        self::assertEquals('', $value[2]);
    }

    public function testGetFormFieldInfo_validKeyWithValue() {
        update_option(TestOptions::OPTIONS_NAME,
            array (
                TestOptions::OPTION_1 => 'aValue',
                TestOptions::OPTION_2 => 'bValue'
            )
        );

        // Reset test object
        $this->resetTestObject();
        $value = $this->testOptions->getFormFieldInfo(TestOptions::OPTION_2);

        self::assertIsArray($value);
        self::assertCount(3, $value);
        self::assertEquals(TestOptions::OPTION_2, $value[0]);
        self::assertEquals(TestOptions::OPTIONS_NAME.'['.TestOptions::OPTION_2.']' , $value[1]);
        self::assertEquals('bValue', $value[2]);
    }
}

class TestOptions extends WordPressOptions
{
    const OPTIONS_NAME = 'test_options';

    const OPTION_1 = 'option1';
    const OPTION_2 = 'option2';

    const OPTION_NAMES = array(self::OPTION_1, self::OPTION_2);

    protected function initializeInstance():void
    {
        parent::initializeInstance();

        $this->initializeOptions(TestOptions::OPTIONS_NAME, self::OPTION_NAMES);
    }
}
