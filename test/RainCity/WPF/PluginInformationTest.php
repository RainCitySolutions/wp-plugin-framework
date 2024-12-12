<?php
namespace RainCity\WPF;

use PHPUnit\Framework\Attributes\CoversClass;
use RainCity\TestHelper\TestStackTrace;
use RainCity\WPF\Helpers\WordpressTestCase;


// Override method in our namespace for testing
function debug_backtrace() {    // NOSONAR
    return TestStackTrace::$testBacktrace;
}

#[CoversClass(\RainCity\WPF\PluginInformation::class)]
class PluginInformationTest extends WordpressTestCase
{
    private $orgBackTrace;

    private const TEST_PLUGIN_PACKAGE = 'unit-test-plugin';
    private const TEST_PLUGIN_NAME = 'UnitTestPlugin';
    private const TEST_PLUGIN_VERSION = '4.75';
    private const TEST_PLUGIN_TEXT_DOMAIN = 'unitTestPlugin';
    private const TEST_PLUGIN_DIRECTORY = '/var/www/wp-content/plugins/'.self::TEST_PLUGIN_PACKAGE;
    private const TEST_PLUGIN_ENTRYPOINT = self::TEST_PLUGIN_PACKAGE.'/entryPoint.php';

    private const TEST_PLUGIN_DATA = [
        'Name' => self::TEST_PLUGIN_NAME,
        'PluginURI' => 'http://test.wp.com/wp-content/plugins/'.self::TEST_PLUGIN_PACKAGE,
        'Version' => self::TEST_PLUGIN_VERSION,
        'Description' => '',     // Plugin description.
        'Author' => '',          // Plugin author’s name.
        'AuthorURI' => '',       // Plugin author’s website address (if set).
        'TextDomain' => self::TEST_PLUGIN_TEXT_DOMAIN,
        'DomainPath' => '',      // Plugin’s relative directory path to .mo files.
        'Network' => false,      // Whether the plugin can only be activated network-wide.
        'RequiresWP' => '',      // Minimum required version of WordPress.
        'RequiresPHP' => '8.2',  // Minimum required version of PHP.
        'UpdateURI' => '',       // ID of the plugin for update purposes, should be a URI.
        'RequiresPlugins' => '', // Comma separated list of dot org plugin slugs.
        'Title' => 'Unit Test Plugin', // Title of the plugin and link to the plugin’s site (if set).
        'AuthorName' => ''       // Plugin author’s name.
    ];

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
            array ('file' => '/var/www/wp-content/plugins/'.self::TEST_PLUGIN_PACKAGE.'/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/PluginInformation.php'),
            array ('file' => '/var/www/wp-content/plugins/'.self::TEST_PLUGIN_PACKAGE.'/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/Logging/WordPressLogger.php'),
            array ('file' => '/var/www/wp-content/plugins/'.self::TEST_PLUGIN_PACKAGE.'/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/Logging/Logger.php'),
            array ('file' => '/var/www/wp-content/plugins/'.self::TEST_PLUGIN_PACKAGE.'/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/Singleton.php'),
            array ('file' => '/var/www/wp-content/plugins/'.self::TEST_PLUGIN_PACKAGE.'/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/WordPressPlugin.php'),
            array ('file' => '/var/www/wp-content/plugins/'.self::TEST_PLUGIN_PACKAGE.'/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/Singleton.php'),
            array (), // [function] => instance, [class] => RainCity\Singleton
            array ('file' => '/var/www/wp-content/plugins/'.self::TEST_PLUGIN_PACKAGE.'/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/WordPressPlugin.php'),
            array ('file' => '/var/www/wp-content/plugins/'.self::TEST_PLUGIN_ENTRYPOINT),
            array ('file' => '/var/www/wp-settings.php'),
            array ('file' => '/var/www/wp-config.php'),
            array ('file' => '/var/www/wp-load.php'),
            array ('file' => '/var/www/wp-admin/admin.php'),
            array ('file' => '/var/www/wp-admin/network/admin.php'),
            array ('file' => '/var/www/wp-admin/network/plugins.php')
        );

        \Brain\Monkey\Functions\when('plugin_dir_path')->alias(function ($file) {   // NOSONAR
            return '/var/www/wp-content/plugins/'.self::TEST_PLUGIN_PACKAGE.'/vendor/raincity/wp-plugin-framework/src/RainCity/WPF/PluginInformation.php';
        });
        \Brain\Monkey\Functions\when('get_plugin_data')->alias(function($file) {
           return self::TEST_PLUGIN_DATA;
        });

        $this->addPlugin(self::TEST_PLUGIN_ENTRYPOINT, self::TEST_PLUGIN_DATA);
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
            return '/var/www/wp-content/plugins/'.self::TEST_PLUGIN_PACKAGE.'/src/RainCity/WPF/PluginInformation.php';
        });

        $info = PluginInformation::getPluginInfo();

        self::assertEquals('unknown', $info->getPackage());
        self::assertEmpty($info->getPath());
    }

    public function testGetPluginInfo_withNoPlugins() {
        update_option('active_plugins', array());
        update_site_option('active_sitewide_plugins', array());

        $info = PluginInformation::getPluginInfo();

        self::assertEquals(self::TEST_PLUGIN_PACKAGE, $info->getPackage());
        self::assertEquals(self::TEST_PLUGIN_DIRECTORY, $info->getPath());
    }

    public function testGetPluginInfo_withActivePlugins() {
        update_option('active_plugins', array(self::TEST_OTHER_PLUGIN_ENTRYPOINT, self::TEST_PLUGIN_ENTRYPOINT));
        update_site_option('active_sitewide_plugins', array());

        $info = PluginInformation::getPluginInfo();

        self::assertEquals(self::TEST_PLUGIN_PACKAGE, $info->getPackage());
        self::assertEquals(self::TEST_PLUGIN_DIRECTORY, $info->getPath());
    }

    public function testGetPluginInfo_withSitewidePlugins() {
        update_option('active_plugins', array());
        update_site_option(
            'active_sitewide_plugins',
            array(self::TEST_OTHER_PLUGIN_ENTRYPOINT => 111111, self::TEST_PLUGIN_ENTRYPOINT => 222222)
            );

        $info = PluginInformation::getPluginInfo();

        self::assertEquals(self::TEST_PLUGIN_PACKAGE, $info->getPackage());
        self::assertEquals(self::TEST_PLUGIN_DIRECTORY, $info->getPath());
    }

    public function testGetPluginInfo_withBothPlugins() {
        update_option('active_plugins', array(self::TEST_OTHER_PLUGIN_ENTRYPOINT));
        update_site_option('active_sitewide_plugins', array(self::TEST_PLUGIN_ENTRYPOINT => 222222));

        $info = PluginInformation::getPluginInfo();

        self::assertEquals(self::TEST_PLUGIN_PACKAGE, $info->getPackage());
        self::assertEquals(self::TEST_PLUGIN_DIRECTORY, $info->getPath());
    }

    public function testIsPluginActive_true() {
        $testPlugin = 'testPlugin.php';

        $pluginInfo =array(
            'Name' => self::TEST_PLUGIN_NAME,
            'Version' => self::TEST_PLUGIN_VERSION,
            'TextDomain' => self::TEST_PLUGIN_TEXT_DOMAIN
        );

        $this->addPlugin($testPlugin, $pluginInfo);

        self::assertTrue(PluginInformation::isPluginActive($testPlugin));
    }

    public function testIsPluginActive_false() {

        self::assertFalse(PluginInformation::isPluginActive('inactive/plugin.php'));
    }

    public function testGetVersion(): void
    {
        update_option('active_plugins', [self::TEST_PLUGIN_ENTRYPOINT]);
        update_site_option('active_sitewide_plugins', array());

        $info = PluginInformation::getPluginInfo();

        $this->assertEquals(self::TEST_PLUGIN_VERSION, $info->getVersion());
    }
}
