<?php
namespace RainCity\WPF\Helpers;

use RainCity\TestHelper\RainCityTestCase;

/**
 * WordpressTestCase base class.
 */
abstract class WordpressTestCase
    extends RainCityTestCase
{
    const WP_DB_PREFIX = "test_wpdb_";

    private $optionsTable, $siteOptionsTable, $userMeta, $plugins;

    /**
     * Runs before each test.
     */
    protected function setUp(): void {
        parent::setUp();

        global $wpdb;

        $wpdb = \Mockery::mock( '\wpdb' );
        $wpdb->makePartial();
        $wpdb->prefix = self::WP_DB_PREFIX;

        // reset mock db tables
        $this->optionsTable = $this->siteOptionsTable = $this->userMeta = array();

        // get_plugins() response
        $this->plugins = array();

        \Brain\Monkey\Functions\when('_doing_it_wrong')->alias(function ( $function, $message, $version) {
            trigger_error(
                sprintf('%1$s was called <strong>incorrectly</strong>. %2$s %3$s', $function, $message, $version),
                E_USER_NOTICE);
        });
        \Brain\Monkey\Functions\when('wp_normalize_path')->alias(function ($path) {
            return $path;
        });
        \Brain\Monkey\Functions\when('plugin_dir_path')->alias(function ($file) {
            return '/var/www/wp-content/plugins/test-plugin/'.basename($file);
        });
        \Brain\Monkey\Functions\when('plugin_dir_url')->alias(function (string $pluginFile) {   // NOSONAR - ignored param
            return 'http://test.org/wp-content/plugins/test-plugin/';
        });


        \Brain\Monkey\Functions\when('add_option')->alias(array($this, 'add_option'));
        \Brain\Monkey\Functions\when('update_option')->alias(array($this, 'update_option'));
        \Brain\Monkey\Functions\when('get_option')->alias(array($this, 'get_option'));
        \Brain\Monkey\Functions\when('delete_option')->alias(array($this, 'delete_option'));

        \Brain\Monkey\Functions\when('get_site_option')->alias(array($this, 'get_site_option'));
        \Brain\Monkey\Functions\when('update_site_option')->alias(array($this, 'update_site_option'));

        \Brain\Monkey\Functions\when('get_user_meta')->alias(array($this, 'get_user_meta'));
        \Brain\Monkey\Functions\when('update_user_meta')->alias(array($this, 'update_user_meta'));

        \Brain\Monkey\Functions\when('register_activation_hook')->alias(function() { /* Do nothing */ });
        \Brain\Monkey\Functions\when('register_deactivation_hook')->alias(function() { /* Do nothing */ });
        \Brain\Monkey\Functions\when('register_uninstall_hook')->alias(function() { /* Do nothing */ });

        \Brain\Monkey\Functions\when('get_plugins')->alias(function (): array {
            return $this->plugins;
        });

    }

    public function add_option (string $option, $value = '', string $deprecated = '', $autoload = 'yes' ) {
        $result = false;

        if (!isset($this->optionsTable[$option])) {
            $this->optionsTable[$option] = $value;
            $result = true;
        }

        return $result;
    }

    public function update_option (string $option, $value, $autoload = null) {
        $this->optionsTable[$option] = $value;
        return true;
    }

    public function get_option (string $option, $default = false) {
        $result = $default;

        if (isset($this->optionsTable[$option])) {
            $result = $this->optionsTable[$option];
        }

        return $result;
    }

    public function delete_option (string $option) {
        $result = false;

        if (isset($this->optionsTable[$option])) {
            unset ($this->optionsTable[$option]);
            $result = true;
        }

        return $result;
    }

    public function update_site_option (string $option, $value) {
        $this->siteOptionsTable[$option] = $value;
        return true;
    }

    public function get_site_option (string $option, $default = false, bool $deprecated = true) {
        $result = false;

        if (isset($this->siteOptionsTable[$option])) {
            $result = $this->siteOptionsTable[$option];
        }

        return $result;
    }

    public function get_user_meta (int $user_id, string $key = '', bool $single = false) {
        $result = false;

        if (isset($this->userMeta[$user_id])) {
            $userMetaRef = $this->userMeta[$user_id];

            if (isset($userMetaRef[$key])) {
                $result = $userMetaRef[$key];
            }
        }

        return $result;
    }

    public function update_user_meta (int $user_id, string $meta_key, $meta_value, $prev_value = '') {
        $result = false;

        // Ensure the user has an entry in the array
        if (!isset($this->userMeta[$user_id])) {
            $this->userMeta[$user_id] = array();
        }

        $userMetaRef = &$this->userMeta[$user_id];

        if (isset($userMetaRef[$meta_key])) {
            if ($meta_value !== $userMetaRef[$meta_key]) {
                $userMetaRef[$meta_key] = serialize($meta_value);
                $result = true;
            }
        }
        else {
            $userMetaRef[$meta_key] = serialize($meta_value);
            $result = 1;
        }

        return $result;
    }

    /**
     * Add a plugin to the list of plugins that will be returned by the
     * get_plugins() function.
     *
     * @param string $pluginFile The name of the main plugin file (need not be real)
     * @param array $pluginInfo The array of plugin information
     */
    protected function addPlugin(string $pluginFile, array $pluginInfo): void {
        $this->plugins[$pluginFile] = $pluginInfo;
    }
}

class WordPressRestServerStub   // A test stub of WP_REST_Server
{
    const READABLE = 'GET';
}
