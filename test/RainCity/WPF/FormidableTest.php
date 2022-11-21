<?php
namespace RainCity\WPF {

use PHPUnit\Framework\TestCase;
    use ReflectionException;

/**
 *
 * @covers \RainCity\WPF\Formidable
 *
 */
class FormidableTest extends TestCase
{
    const FORM_KEY = 'formKey';
    const FIELD_KEY = 'fieldKey';
    const VIEW_KEY = 'viewKey';

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
            $class = new \ReflectionClass('\FrmField');
            $class->getProperty('callCnt');
        }
        catch (ReflectionException $re) {
            self::markTestSkipped('Real Formidable classes are not available, unable to test');
        }
    }

    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        \FrmField::reset();
        \FrmForm::reset();
        \FrmViewsDisplay::reset();

        $this->resetFormidableCache('formIdCache');
        $this->resetFormidableCache('fieldIdCache');
        $this->resetFormidableCache('viewIdCache');
    }

    private function resetFormidableCache(string $cacheField) {
        $class = new \ReflectionClass(__NAMESPACE__.'\Formidable');
        $property = $class->getProperty($cacheField);
        $property->setAccessible(true);
        $property->setValue(array());
        $property->setAccessible(false);
    }

    private function assertCacheEmpty(string $cacheField) {
        $class = new \ReflectionClass(__NAMESPACE__.'\Formidable');
        $property = $class->getProperty($cacheField);
        $property->setAccessible(true);
        $this->assertEmpty($property->getValue());
        $property->setAccessible(false);
    }

    public function testGetFormId_missingId () {
        $id = Formidable::getFormId(self::FORM_KEY);

        $this->assertEquals(1, \FrmForm::$callCnt);
        $this->assertNull($id);
    }

    public function testGetFormId_notCached () {
        \FrmForm::$returnId = 27;

        $this->assertCacheEmpty('formIdCache');
        $id = Formidable::getFormId(self::FORM_KEY);

        $this->assertEquals(1, \FrmForm::$callCnt);
        $this->assertEquals(\FrmForm::$returnId, $id);
    }

    public function testGetFormId_cached () {
        \FrmForm::$returnId = 99;

        $this->assertCacheEmpty('formIdCache');
        $id = Formidable::getFormId(self::FORM_KEY);

        $this->assertEquals(1, \FrmForm::$callCnt);
        $this->assertEquals(\FrmForm::$returnId, $id);

        $id = Formidable::getFormId(self::FORM_KEY);

        $this->assertEquals(1, \FrmForm::$callCnt);
        $this->assertEquals(\FrmForm::$returnId, $id);
    }

    public function testGetFieldId_missingId () {
        $id = Formidable::getFieldId(self::FIELD_KEY);

        $this->assertEquals(1, \FrmField::$callCnt);
        $this->assertNull($id);
    }

    public function testGetFieldId_notCached () {
        \FrmField::$returnId = 39;

        $this->assertCacheEmpty('fieldIdCache');
        $id = Formidable::getFieldId(self::FIELD_KEY);

        $this->assertEquals(1, \FrmField::$callCnt);
        $this->assertEquals(\FrmField::$returnId, $id);
    }

    public function testGetFieldId_cached () {
        \FrmField::$returnId = 77;

        $this->assertCacheEmpty('fieldIdCache');
        $id = Formidable::getFieldId(self::FIELD_KEY);

        $this->assertEquals(1, \FrmField::$callCnt);
        $this->assertEquals(\FrmField::$returnId, $id);

        $id = Formidable::getFieldId(self::FIELD_KEY);

        $this->assertEquals(1, \FrmField::$callCnt);
        $this->assertEquals(\FrmField::$returnId, $id);
    }

    public function testGetViewId_missingId () {
        $id = Formidable::getViewId(self::VIEW_KEY);

        $this->assertEquals(1, \FrmViewsDisplay::$callCnt);
        $this->assertNull($id);
    }

    public function testGetViewId_notCached () {
        \FrmViewsDisplay::$returnId = 72;

        $this->assertCacheEmpty('viewIdCache');
        $id = Formidable::getViewId(self::VIEW_KEY);

        $this->assertEquals(1, \FrmViewsDisplay::$callCnt);
        $this->assertEquals(\FrmViewsDisplay::$returnId, $id);
    }

    public function testGetViewId_cached () {
        \FrmViewsDisplay::$returnId = 88;

        $this->assertCacheEmpty('viewIdCache');
        $id = Formidable::getViewId(self::VIEW_KEY);

        $this->assertEquals(1, \FrmViewsDisplay::$callCnt);
        $this->assertEquals(\FrmViewsDisplay::$returnId, $id);

        $id = Formidable::getViewId(self::VIEW_KEY);

        $this->assertEquals(1, \FrmViewsDisplay::$callCnt);
        $this->assertEquals(\FrmViewsDisplay::$returnId, $id);
    }

    public function testGetAllId_unique () {
        $key = 'SameKey';

        \FrmForm::$returnId = 77;
        \FrmField::$returnId = 88;
        \FrmViewsDisplay::$returnId = 99;

        $this->assertCacheEmpty('formIdCache');
        $this->assertCacheEmpty('fieldIdCache');
        $this->assertCacheEmpty('viewIdCache');

        $formId = Formidable::getFormId($key);
        $fieldId = Formidable::getFieldId($key);
        $viewId = Formidable::getViewId($key);

        $this->assertEquals(1, \FrmForm::$callCnt);
        $this->assertEquals(1, \FrmField::$callCnt);
        $this->assertEquals(1, \FrmViewsDisplay::$callCnt);

        $this->assertEquals(\FrmForm::$returnId, $formId);
        $this->assertEquals(\FrmField::$returnId, $fieldId);
        $this->assertEquals(\FrmViewsDisplay::$returnId, $viewId);

        // And again to hit the cached values
        $formId = Formidable::getFormId($key);
        $fieldId = Formidable::getFieldId($key);
        $viewId = Formidable::getViewId($key);

        $this->assertEquals(1, \FrmForm::$callCnt);
        $this->assertEquals(1, \FrmField::$callCnt);
        $this->assertEquals(1, \FrmViewsDisplay::$callCnt);

        $this->assertEquals(\FrmForm::$returnId, $formId);
        $this->assertEquals(\FrmField::$returnId, $fieldId);
        $this->assertEquals(\FrmViewsDisplay::$returnId, $viewId);
    }
}
}


// Global namespace
namespace {
    if (!class_exists('FrmForm')) {
        class FrmForm {
            public static $callCnt = 0;
            public static $returnId = 0;

            public static function reset() {
                self::$callCnt = 0;
                self::$returnId = 0;
            }

            public static function get_id_by_key(string $key): int {    // NOSONAR
                self::$callCnt++;

                return (int)self::$returnId;
            }
        }
    }

    if (!class_exists('FrmField')) {
        class FrmField {
            public static $callCnt = 0;
            public static $returnId = 0;

            public static function reset() {
                self::$callCnt = 0;
                self::$returnId = 0;
            }

            public static function get_id_by_key(string $key): int {    // NOSONAR
                self::$callCnt++;

                return (int)self::$returnId;
            }
        }
    }

    if (!class_exists('FrmViewsDisplay')) {
        class FrmViewsDisplay {
            public static $callCnt = 0;
            public static $returnId = 0;

            public static function reset() {
                self::$callCnt = 0;
                self::$returnId = 0;
            }

            public static function get_id_by_key(string $key): int {    // NOSONAR
                self::$callCnt++;

                return (int)self::$returnId;
            }
        }
    }
}
