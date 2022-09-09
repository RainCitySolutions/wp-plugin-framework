<?php
namespace RainCity\WPF\REDCap;

use RainCity\REDCap\RedCapProject;
use RainCity\TestHelper\ReflectionHelper;
use RainCity\WPF\Helpers\WordpressTestCase;

/**
 *
 * @covers \RainCity\WPF\REDCap\PDFHandler
 *
 */
class PDFHandlerTest extends WordpressTestCase
{
    private const TEST_PDF_CONTENTS = 'Test to represent the contents of a PDF file.';

    protected $stubRedcapProj;

    protected function setUp(): void
    {
        $this->stubRedcapProj = $this->createMock(RedCapProject::class);
        $this->stubRedcapProj->method('exportPdfFileOfInstruments')->willReturn(self::TEST_PDF_CONTENTS);

        \Brain\Monkey\Functions\when('sanitize_file_name')->returnArg(1);
    }

    public function testConstructor_noArg()
    {
        $this->expectException("ArgumentCountError");
        new PDFHandler();
    }

    public function testConstructor_invalidArg()
    {
        $this->expectException("TypeError");
        new PDFHandler((object)[]);
    }

    public function testConstructor_validArg()
    {
        $obj = new PDFHandler($this->stubRedcapProj);

        $proj = ReflectionHelper::getObjectProperty(PDFHandler::class, 'redcapProject', $obj);

        $this->assertEquals($this->stubRedcapProj, $proj);
    }

    public function testLoadActions()
    {
        \Mockery::namedMock('WP_REST_Server', 'RainCity\WPF\Helpers\WP_REST_ServerStub');

        $obj = new PDFHandler($this->stubRedcapProj);

        $actionsStub = $this->createMock(\RainCity\WPF\ActionFilterLoader::class);
        $actionsStub->expects($this->exactly(1))
            ->method('add_action')
            ->with('wp_ajax_'.PDFHandler::FETCH_PDF_ACTION, $obj, 'fetchPdf');

        $obj->loadActions($actionsStub);
    }

    public function testCreatePdfLink_noArgs()
    {
        $this->expectException("ArgumentCountError");
        PDFHandler::createPdfLink();
    }

    public function testCreatePdfLink_nullArgs()
    {
        $this->expectException("TypeError");
        PDFHandler::createPdfLink(null, null, null, null);
    }

    public function testCreatePdfLink()
    {
        $testNonce = rand();
        \Brain\Monkey\Functions\when('wp_create_nonce')->justReturn($testNonce);
        \Brain\Monkey\Functions\when('admin_url')->alias(function(string $path = '', string $scheme = 'admin') {
            return 'http://test.wordpress.local/wp-admin/' . $path;
        });

        $link = PDFHandler::createPdfLink('rcd-test-1', 'form-test-1', 'event-test-1', 'pdf-test-1');
        $this->assertNotEmpty($link, 'No link returned');

        $this->assertTrue(strstr($link, admin_url()) !== false, 'Link is missing the expected host');
        $this->assertTrue(strstr($link, 'action=FetchRedcapPdf') !== false, 'Link is missing the expected action');
        $this->assertTrue(strstr($link, "nonce={$testNonce}") !== false, 'Link is missing the nonce');
        $this->assertTrue(strstr($link, 'data=') !== false, 'Link is missing the PDF file data');
    }

    public function testFetchPdf() {
        \Brain\Monkey\Functions\when('wp_create_nonce')->justReturn(rand());
        \Brain\Monkey\Functions\when('wp_verify_nonce')->justReturn(true);

        $obj = new PDFHandler($this->stubRedcapProj);

        $formName = 'testForm';
        $_REQUEST['data'] = (new \RainCity\WPF\REDCap\PDFHandlerData('rcd', $formName, null, 'pdf'))->toString();
        $_REQUEST['nonce'] = wp_create_nonce($formName);

        ob_start(function($output) {
            $this->assertNotEmpty($output);
            $this->assertEquals(self::TEST_PDF_CONTENTS, $output);
        });

        $obj->fetchPdf();

        ob_end_flush();
    }

    public function testFetchPdf_noData() {
        $obj = new PDFHandler($this->stubRedcapProj);

        unset($_REQUEST['data']);
        unset($_REQUEST['nonce']);

        ob_start(function($output) {
            $this->assertEmpty($output);
        });

        $obj->fetchPdf();

        ob_end_flush();
    }

}
