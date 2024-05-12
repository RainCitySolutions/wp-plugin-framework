<?php
namespace RainCity\WPF;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use RainCity\TestHelper\ReflectionHelper;

/**
 *
 * @covers RainCity\WPF\Formidable
 *
 */
class FormidableTest extends TestCase
{
    const FORM_KEY = 'formKey';
    const FIELD_KEY = 'fieldKey';
    const VIEW_KEY = 'viewKey';

    private static $mockFrmForm;
    private static $mockFrmField;
    private static $mockFrmViewsDisplay;

    private static int $testId = 0;

    /**
     * Set the identifier to be returned by the mock object
     *
     * @param int $id An identifier
     */
    private function setTestId(int $id): void
    {
        self::$testId = $id;
    }

    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        /* Check if the actual Formidable forms are available. If so, we
         * can't use our test classes.
         */
        try {
            new \ReflectionClass(\FrmField::class); // NOSONAR

            self::markTestSkipped('Real Formidable classes are available, unable to test');
        }
        catch (ReflectionException $re) {
            // We expect the exception to be throw if the Formidable classes are not loaded
        }

        self::$mockFrmForm = \Mockery::mock('overload:\FrmForm');
        self::$mockFrmForm->shouldReceive('get_id_by_key')->andReturnUsing(fn() => self::$testId);

        self::$mockFrmField = \Mockery::mock('overload:\FrmField');
        self::$mockFrmField->shouldReceive('get_id_by_key')->andReturnUsing(fn() => self::$testId);

        self::$mockFrmViewsDisplay = \Mockery::mock('overload:\FrmViewsDisplay');
        self::$mockFrmViewsDisplay->shouldReceive('get_id_by_key')->andReturnUsing(fn() => self::$testId);
    }

    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        $this->resetFormidableCache('formIdCache');
        $this->resetFormidableCache('fieldIdCache');
        $this->resetFormidableCache('viewIdCache');
    }

    private function resetFormidableCache(string $cacheField) {
        ReflectionHelper::setClassProperty(__NAMESPACE__.'\Formidable', $cacheField, []);
    }

    private function assertCacheEmpty(string $cacheField) {
        $this->assertEmpty(
            ReflectionHelper::getClassProperty(__NAMESPACE__.'\Formidable', $cacheField)
            );
    }

    /************************************************************************
     * FrmForm Tests
     ************************************************************************/
    public function testGetFormId_missingId () {
        $this->setTestId(0);

        $id = Formidable::getFormId(self::FORM_KEY);

        $this->assertNull($id);
    }

    public function testGetFormId_notCached () {
        $testId = 27;
        $this->setTestId($testId);

        $this->assertCacheEmpty('formIdCache');

        $id = Formidable::getFormId(self::FORM_KEY);

        $this->assertEquals($testId, $id);
    }

    public function testGetFormId_cached () {
        $testId = 97;
        $this->setTestId($testId);

        $this->assertCacheEmpty('formIdCache');
        $id = Formidable::getFormId(self::FORM_KEY);

        $this->assertEquals(self::$testId, $id);

        $this->setTestId(-1);   // Cached id should be returned
        $id = Formidable::getFormId(self::FORM_KEY);

        $this->assertEquals($testId, $id);
    }

    /************************************************************************
     * FrmField Tests
     ************************************************************************/
    public function testGetFieldId_missingId () {
        $this->setTestId(0);

        $id = Formidable::getFieldId(self::FIELD_KEY);

        $this->assertNull($id);
    }

    public function testGetFieldId_notCached () {
        $testId = 39;
        $this->setTestId($testId);

        $this->assertCacheEmpty('fieldIdCache');
        $id = Formidable::getFieldId(self::FIELD_KEY);

        $this->assertEquals($testId, $id);
    }

    public function testGetFieldId_cached () {
        $testId = 79;
        $this->setTestId($testId);

        $this->assertCacheEmpty('fieldIdCache');
        $id = Formidable::getFieldId(self::FIELD_KEY);

        $this->assertEquals($testId, $id);

        $this->setTestId(-1);   // Cached id should be returned
        $id = Formidable::getFieldId(self::FIELD_KEY);

        $this->assertEquals($testId, $id);
    }

    /************************************************************************
     * FrmView Tests
     ************************************************************************/
    public function testGetViewId_missingId () {
        $this->setTestId(0);

        $id = Formidable::getViewId(self::VIEW_KEY);

        $this->assertNull($id);
    }

    public function testGetViewId_notCached () {
        $testId = 72;
        $this->setTestId($testId);

        $this->assertCacheEmpty('viewIdCache');
        $id = Formidable::getViewId(self::VIEW_KEY);

        $this->assertEquals(self::$testId, $id);
    }

    public function testGetViewId_cached () {
        $testId = 82;
        $this->setTestId($testId);

        $this->assertCacheEmpty('viewIdCache');
        $id = Formidable::getViewId(self::VIEW_KEY);

        $this->assertEquals($testId, $id);

        $this->setTestId(-1);   // Cached id should be returned
        $id = Formidable::getViewId(self::VIEW_KEY);

        $this->assertEquals($testId, $id);
    }

    public function testGetAllId_unique () {
        $key = 'SameKey';

        $testFormId = 77;
        $testFieldId = 88;
        $testViewId = 99;

        $this->assertCacheEmpty('formIdCache');
        $this->assertCacheEmpty('fieldIdCache');
        $this->assertCacheEmpty('viewIdCache');

        $this->setTestId($testFormId);
        $formId = Formidable::getFormId($key);

        $this->setTestId($testFieldId);
        $fieldId = Formidable::getFieldId($key);

        $this->setTestId($testViewId);
        $viewId = Formidable::getViewId($key);

        $this->assertEquals($testFormId, $formId);
        $this->assertEquals($testFieldId, $fieldId);
        $this->assertEquals($testViewId, $viewId);

        $this->setTestId(-1);

        // And again to hit the cached values
        $formId = Formidable::getFormId($key);
        $fieldId = Formidable::getFieldId($key);
        $viewId = Formidable::getViewId($key);

        $this->assertEquals($testFormId, $formId);
        $this->assertEquals($testFieldId, $fieldId);
        $this->assertEquals($testViewId, $viewId);
    }
}
