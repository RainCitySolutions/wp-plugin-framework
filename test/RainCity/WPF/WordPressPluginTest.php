<?php
namespace RainCity\WPF;

use RainCity\WPF\Helpers\WordpressTestCase;

/**
 * @covers \RainCity\WPF\WordPressPlugin
 */
class WordPressPluginTest extends WordpressTestCase
{
    const TEST_SCRIPT_TAG = '<script src="noScript.js"></script>';
    const ASYNC_FLAG = ' async ';
    const DEFER_FLAG = ' defer ';

    /** @var WordPressPlugin */
    private $wordPressPlugin;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        \Brain\Monkey\Functions\when('get_plugins')->alias(function () {
            $result = array();

            $result['testPlugin.php'] = array (
                'Name' => 'TestPlugin'
                );

            return $result;
        });

        \Brain\Monkey\Functions\when('plugin_dir_url')->alias(function (string $pluginFile) {   // NOSONAR - ignored param
            return 'http://some.server.com/wp-content/plugins/testPlugin/';
        });

        $this->wordPressPlugin = TestPlugin::instance(array(
            'Name' => 'TestPlugin',
            'Version' => '1.0.0',
            'TextDomain' => 'testPlugin'
        ));
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        $this->wordPressPlugin = null;

        parent::tearDown();
    }

    public function testOnScriptLoaderTag_plain () {
        $result = $this->wordPressPlugin->onScriptLoaderTag(self::TEST_SCRIPT_TAG, 'plain');

        $this->assertStringNotContainsString(self::ASYNC_FLAG, $result);
        $this->assertStringNotContainsString(self::DEFER_FLAG, $result);
    }

    public function testOnScriptLoaderTag_async () {
        $result = $this->wordPressPlugin->onScriptLoaderTag(self::TEST_SCRIPT_TAG, 'handle_async');

        $this->assertStringContainsString(self::ASYNC_FLAG, $result);
        $this->assertStringNotContainsString(self::DEFER_FLAG, $result);
    }

    public function testOnScriptLoaderTag_defer () {
        $result = $this->wordPressPlugin->onScriptLoaderTag(self::TEST_SCRIPT_TAG, 'handle_defer');

        $this->assertStringNotContainsString(self::ASYNC_FLAG, $result);
        $this->assertStringContainsString(self::DEFER_FLAG, $result);
    }

    public function testOnScriptLoaderTag_asyncAndDefer () {
        $result = $this->wordPressPlugin->onScriptLoaderTag(self::TEST_SCRIPT_TAG, 'handle_async_defer');

        $this->assertStringContainsString(self::ASYNC_FLAG, $result);
        $this->assertStringContainsString(self::DEFER_FLAG, $result);
    }
}

class TestPlugin extends WordPressPlugin
{
    public static function getOptions(): array
    {
        return array();
    }

    public function getDatabaseUpgrades(): array    // NOSONAR - Don't care about being the same as getOptions
    {
        return array();
    }

    protected function initializeInstance() {
        // Don't call parent when testing
    }
}
