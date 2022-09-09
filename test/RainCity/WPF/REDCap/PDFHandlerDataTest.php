<?php
namespace RainCity\WPF\REDCap;

use PHPUnit\Framework\TestCase;

/**
 *
 * @covers \RainCity\WPF\REDCap\PDFHandlerData
 *
 */
class PDFHandlerDataTest extends TestCase
{
    private const TEST_RECORD_ID = 'Test-Rcd-1';
    private const TEST_FORM_NAME = 'Test-Form-1';
    private const TEST_EVENT_NAME = 'Test-Event-1';
    private const TEST_PDF_NAME = 'Test-Pdf-1.pdf';
    private const SERIALIZE_TEST_OBJ = 'S7QysarOtDKwLrYyNLBSCkktLtENSk7RNVSyzrQyBIkaQkXd8otyIcJGIGEjqLBrWWpeCUTcGCRuAhUPSEnTNdQrSElTsq4FAA';
    private const SERIALIZE_TEST_NULL_EVENT = 'S7QysarOtDKwLrYyNLBSCkktLtENSk7RNVSyzrQyBIkaQkXd8otyIcJG1n5A0hgkaQKVDEhJ0zXUK0hJU7KuBQA';

    public function testConstructor_noArg()
    {
        $this->expectException("ArgumentCountError");
        new PDFHandlerData();
    }

    public function testConstructor_invalidRecord()
    {
        $this->expectException("TypeError");
        new PDFHandlerData(array(), '', '', '');
    }

    public function testConstructor_invalidForm()
    {
        $this->expectException("TypeError");
        new PDFHandlerData(self::TEST_RECORD_ID, array(), '', '');
    }

    public function testConstructor_invalidEvent()
    {
        $this->expectException("TypeError");
        new PDFHandlerData(self::TEST_RECORD_ID, self::TEST_FORM_NAME, array(), '');
    }

    public function testConstructor_nullEvent()
    {
        new PDFHandlerData(self::TEST_RECORD_ID, self::TEST_FORM_NAME, null, '');
        $this->assertTrue(true);
    }

    public function testConstructor_invalidPdf()
    {
        $this->expectException("TypeError");
        new PDFHandlerData(self::TEST_RECORD_ID, self::TEST_FORM_NAME, self::TEST_EVENT_NAME, array());
    }

    public function testGetters()
    {
        $obj = new PDFHandlerData(self::TEST_RECORD_ID, self::TEST_FORM_NAME, self::TEST_EVENT_NAME, self::TEST_PDF_NAME);

        $this->assertEquals(self::TEST_RECORD_ID, $obj->getRecord());
        $this->assertEquals(self::TEST_FORM_NAME, $obj->getForm());
        $this->assertEquals(self::TEST_EVENT_NAME, $obj->getEvent());
        $this->assertEquals(self::TEST_PDF_NAME, $obj->getPdf());
    }

    public function testToString()
    {
        $obj = new PDFHandlerData(self::TEST_RECORD_ID, self::TEST_FORM_NAME, self::TEST_EVENT_NAME, self::TEST_PDF_NAME);
        $str = $obj->toString();

        $this->assertEquals(self::SERIALIZE_TEST_OBJ, $str);
    }

    public function testToString_nullEvent()
    {
        $obj = new PDFHandlerData(self::TEST_RECORD_ID, self::TEST_FORM_NAME, null, self::TEST_PDF_NAME);
        $str = $obj->toString();

        $this->assertEquals(self::SERIALIZE_TEST_NULL_EVENT, $str);
    }

    public function testFromString_emptyString()
    {
        $this->expectWarning();
        $obj = PDFHandlerData::fromString('');

        $this->assertNull($obj);
    }

    public function testFromString()
    {
        $obj = PDFHandlerData::fromString(self::SERIALIZE_TEST_OBJ);

        $this->assertEquals(self::TEST_RECORD_ID, $obj->getRecord());
        $this->assertEquals(self::TEST_FORM_NAME, $obj->getForm());
        $this->assertEquals(self::TEST_EVENT_NAME, $obj->getEvent());
        $this->assertEquals(self::TEST_PDF_NAME, $obj->getPdf());
    }

    public function testFromString_nullEvent()
    {
        $obj = PDFHandlerData::fromString(self::SERIALIZE_TEST_NULL_EVENT);

        $this->assertEquals(self::TEST_RECORD_ID, $obj->getRecord());
        $this->assertEquals(self::TEST_FORM_NAME, $obj->getForm());
        $this->assertEquals(self::TEST_PDF_NAME, $obj->getPdf());

        $this->assertEquals(null, $obj->getEvent());
    }

    public function testFromString_noRecordId()
    {
        $obj = PDFHandlerData::fromString(base64_encode(gzdeflate(serialize(array(null, null, null, null)))));

        $this->assertNull($obj);
    }

}
