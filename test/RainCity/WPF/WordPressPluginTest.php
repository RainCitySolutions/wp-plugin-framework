<?php
namespace RainCity\WPF;

use RainCity\WPF\Helpers\WordpressTestCase;

/**
 * @covers \RainCity\WPF\WordPressPlugin
 *
 * @covers RainCity\WPF\PluginInformation::getPluginFile
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

        $pluginInfo =array(
            'Name' => 'TestPlugin',
            'Version' => '1.0.0',
            'TextDomain' => 'testPlugin'
            );

        $this->addPlugin('testPlugin.php', $pluginInfo);

        $this->wordPressPlugin = TestPlugin::instance($pluginInfo);
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

        self::assertStringNotContainsString(self::ASYNC_FLAG, $result);
        self::assertStringNotContainsString(self::DEFER_FLAG, $result);
    }

    public function testOnScriptLoaderTag_async () {
        $result = $this->wordPressPlugin->onScriptLoaderTag(self::TEST_SCRIPT_TAG, 'handle_async');

        self::assertStringContainsString(self::ASYNC_FLAG, $result);
        self::assertStringNotContainsString(self::DEFER_FLAG, $result);
    }

    public function testOnScriptLoaderTag_defer () {
        $result = $this->wordPressPlugin->onScriptLoaderTag(self::TEST_SCRIPT_TAG, 'handle_defer');

        self::assertStringNotContainsString(self::ASYNC_FLAG, $result);
        self::assertStringContainsString(self::DEFER_FLAG, $result);
    }

    public function testOnScriptLoaderTag_asyncAndDefer () {
        $result = $this->wordPressPlugin->onScriptLoaderTag(self::TEST_SCRIPT_TAG, 'handle_async_defer');

        self::assertStringContainsString(self::ASYNC_FLAG, $result);
        self::assertStringContainsString(self::DEFER_FLAG, $result);
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

    protected function initializeInstance(): void
    {
        // Don't call parent when testing
    }
}
