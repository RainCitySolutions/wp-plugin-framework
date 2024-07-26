<?php
namespace RainCity\WPF\ShortURL;

use function PHPUnit\Framework\any;
use RainCity\Logging\Logger;
use RainCity\TestHelper\ReflectionHelper;
use RainCity\TestHelper\StubLogger;
use RainCity\WPF\Helpers\WordpressTestCase;

/**
 * @require(s) extension skip_tests
 * @covers \RainCity\WPF\ShortURL\ShortUrlHandler
 *
 */
class ShortUrlHandlerTest extends WordpressTestCase
{
    const TEST_PREFIX = 'test_prefix';

    /* @var ShortUrlHandler */
    private $testHandler;

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
        ReflectionHelper::setClassProperty(ShortUrlHandler::class, 'instance', array(), true);

        $this->testHandler = TestShortUrlHandler::instance(self::TEST_PREFIX);
    }

    public function testCtor()
    {
        /** @var ShortUrlHandler */
        $handler = ShortUrlHandler::instance();

        self::assertNotNull($handler);
        self::assertInstanceOf(ShortUrlHandler::class, $handler);
    }

    public function testNotShortUrl () {
        // Reset test object
        $this->resetTestObject();

        $GLOBALS['_SERVER']['REQUEST_URI'] = '/foobar';

        \Brain\Monkey\Functions\expect('wp_redirect')
            ->never();

        $this->testHandler->templateRedirectAction ();
    }

    public function testWithQueryParams() {
        // Reset test object
        $this->resetTestObject();

        $testUrlCode = 'testUrlCode';

        $GLOBALS['_SERVER']['REQUEST_URI'] =
            self::getShortCodeUrl($testUrlCode) .
            '?fbclid=IwAR21Rwxa7gk49D8Bg1UOBmr-FdOujmtmEj_eCVvLa6MwGJo_x42f_eiEhhE';

        global $wpdb;

        $wpdb->shouldReceive( 'prepare' )
            ->once()
            ->andReturn("testSqlStmt");

        $wpdb->shouldReceive( 'get_var' )
            ->once()
            ->andReturn( self::getShortCodeUrl($testUrlCode) );

        \Brain\Monkey\Functions\expect('wp_redirect')
            ->once()
            ->with(self::getShortCodeUrl($testUrlCode));

        $this->testHandler->templateRedirectAction ();
    }

    private function getShortCodeUrl(string $shortCode): string {
        return '/' . self::TEST_PREFIX . '/' . $shortCode;
    }
}

class TestShortUrlHandler extends ShortUrlHandler
{
}
