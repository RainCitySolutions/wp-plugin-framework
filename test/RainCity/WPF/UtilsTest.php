<?php
namespace RainCity\WPF;

use RainCity\TestHelper\TestStackTrace;
use RainCity\WPF\Helpers\WordpressTestCase;


// Override method in our namespace for testing
function debug_backtrace() {
    return TestStackTrace::$testBacktrace;
}

/**
 * @covers \RainCity\WPF\Utils
 *
 */
class UtilsTest extends WordpressTestCase
{
    private $orgBackTrace;
    private const TEST_PLUGIN_DIRECTOR = '/var/www/wp-content/plugins/test-plugin';
    private const TEST_PLUGIN_ENTRYPOINT = 'test-plugin/entryPoint.php';
    private const TEST_OTHER_PLUGIN_ENTRYPOINT = 'another-plugin/entrypoint.php';

    /**
     * {@inheritDoc}
     * @see WordpressTestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->orgBackTrace = TestStackTrace::$testBacktrace;

        TestStackTrace::$testBacktrace = array(
            array ('file' => '/var/www/wp-content/plugins/test-plugin/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/Utils.php'),
            array ('file' => '/var/www/wp-content/plugins/test-plugin/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/Logging/WordPressLogger.php'),
            array ('file' => '/var/www/wp-content/plugins/test-plugin/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/Logging/Logger.php'),
            array ('file' => '/var/www/wp-content/plugins/test-plugin/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/Singleton.php'),
            array ('file' => '/var/www/wp-content/plugins/test-plugin/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/WordPressPlugin.php'),
            array ('file' => '/var/www/wp-content/plugins/test-plugin/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/Singleton.php'),
            array (), // [function] => instance, [class] => RainCity\Singleton
            array ('file' => '/var/www/wp-content/plugins/test-plugin/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/WordPressPlugin.php'),
            array ('file' => '/var/www/wp-content/plugins/test-plugin/entryPoint.php'),
            array ('file' => '/var/www/wp-settings.php'),
            array ('file' => '/var/www/wp-config.php'),
            array ('file' => '/var/www/wp-load.php'),
            array ('file' => '/var/www/wp-admin/admin.php'),
            array ('file' => '/var/www/wp-admin/network/admin.php'),
            array ('file' => '/var/www/wp-admin/network/plugins.php')
        );

        \Brain\Monkey\Functions\when('plugin_dir_path')->alias(function ($file) {   // NOSONAR
            return '/var/www/wp-content/plugins/test-plugin/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/Utils.php';
        });
    }

    /**
     * {@inheritDoc}
     * @see WordpressTestCase::tearDown()
     */
    protected function tearDown(): void
    {
        TestStackTrace::$testBacktrace = $this->orgBackTrace;
        parent::tearDown();
    }

    public function testGetPluginInfo_withNoVendorPath() {
        update_option('active_plugins', array());
        update_site_option('active_sitewide_plugins', array());

        \Brain\Monkey\Functions\when('plugin_dir_path')->alias(function ($file) {   // NOSONAR
            return '/var/www/wp-content/plugins/test-plugin/src/RainCity/WPF/Utils.php';
        });

        $info = Utils::getPluginInfo();

        $this->assertEquals('unknown', $info->pluginPackage);
        $this->assertEmpty($info->pluginPath);
    }

    public function testGetPluginInfo_withNoPlugins() {
        update_option('active_plugins', array());
        update_site_option('active_sitewide_plugins', array());

        $info = Utils::getPluginInfo();

        $this->assertEquals('test-plugin', $info->pluginPackage);
        $this->assertEquals(self::TEST_PLUGIN_DIRECTOR, $info->pluginPath);
    }

    public function testGetPluginInfo_withActivePlugins() {
        update_option('active_plugins', array(self::TEST_OTHER_PLUGIN_ENTRYPOINT, self::TEST_PLUGIN_ENTRYPOINT));
        update_site_option('active_sitewide_plugins', array());

        $info = Utils::getPluginInfo();

        $this->assertEquals('test-plugin', $info->pluginPackage);
        $this->assertEquals(self::TEST_PLUGIN_DIRECTOR, $info->pluginPath);
    }

    public function testGetPluginInfo_withSitewidePlugins() {
        update_option('active_plugins', array());
        update_site_option('active_sitewide_plugins', array(self::TEST_OTHER_PLUGIN_ENTRYPOINT => 111111, self::TEST_PLUGIN_ENTRYPOINT => 222222));

        $info = Utils::getPluginInfo();

        $this->assertEquals('test-plugin', $info->pluginPackage);
        $this->assertEquals(self::TEST_PLUGIN_DIRECTOR, $info->pluginPath);
    }

    public function testGetPluginInfo_withBothPlugins() {
        update_option('active_plugins', array(self::TEST_OTHER_PLUGIN_ENTRYPOINT));
        update_site_option('active_sitewide_plugins', array(self::TEST_PLUGIN_ENTRYPOINT => 222222));

        $info = Utils::getPluginInfo();

        $this->assertEquals('test-plugin', $info->pluginPackage);
        $this->assertEquals(self::TEST_PLUGIN_DIRECTOR, $info->pluginPath);
    }
}
