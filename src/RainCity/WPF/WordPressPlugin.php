<?php
namespace RainCity\WPF;

use RainCity\DataCache;
use RainCity\Singleton;
use RainCity\Logging\Logger;
use RainCity\WPF\Documentation\DocumentationTab;
use RainCity\WPF\Logging\WordPressLogger;
use RainCity\WPF\ShortCode\EmailShortCode;
use RainCity\WPF\ShortCode\ShortCodeImplInf;
use RainCity\WPF\ShortCode\ShortCodeRegInf;
use RainCity\WPF\ShortCode\UsernameShortCode;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 */
abstract class WordPressPlugin
    extends Singleton
    implements WordPressPluginInf
{
    const DATABASE_VERSIONS_OPTIONS_NAME = 'raincity_wpf_database_versions';

    const ON_PLUGIN_ACTIVATION_ACTION = 'raincity_wpf_plugin_activation_action';
    const ON_PLUGIN_DEACTIVATION_ACTION = 'raincity_wpf_plugin_deactivation_action';
    const ON_PLUGIN_UNINSTALL_ACTION = 'raincity_wpf_plugin_uninstall_action';

    const ON_REGISTER_SHORTCODE_ACTION = 'raincity_wpf_register_shortcode_action';


    protected $pluginName;
    protected $pluginVersion;
    protected $pluginSlug;
    protected $mainPluginFilename;
    protected $basePluginUrl;


    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @access   protected
     * @var      ActionFilterLoader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     *
     * @var string Version of the database. Used to detect when updates are
     *             needed to the database.
     */
    private $databaseVersion;


    /**
     *
     * {@inheritDoc}
     * @see \RainCity\Singleton::__construct()
     */
    protected function __construct(array $args) {
        parent::__construct();

        if (isset($args[0])) {
            $pluginData = $args[0];
            $this->pluginName    = $pluginData['Name'];
            $this->pluginVersion = $pluginData['Version'];
            $this->pluginSlug    = $pluginData['TextDomain'];

            $this->mainPluginFilename = Utils::getPluginFile($this->pluginName);
            $this->basePluginUrl = plugin_dir_url($this->mainPluginFilename);
        }
    }


    /**
     *
     * {@inheritDoc}
     * @see \RainCity\Singleton::initializeInstance()
     */
    protected function initializeInstance() {
        $this->setup_actions();

        /**
         * Hook to load a plugin specific functions.php from the plugin's
         * write folder, if it exists, after the theme has been loaded. The
         * write folder is wp-content/uploads/<pluginSlug>
         *
         * Allows for customization without a change to theme or plugin code.
         *
         * <p><strong>Use With Care!</strong>
         */
        $this->loader->add_action('after_setup_theme', null,
            function () {
                $functionsPhp = Utils::getPluginWriteDir() . '/functions.php';

                if (file_exists($functionsPhp)) {
                    require_once $functionsPhp;
                }
            },
            100);

        // delay running database upgrades until WordPress is initialized
        $this->loader->add_action( 'init', $this, 'privUpgradeDatabase', 0 );
        $this->loader->add_action( 'init', $this, 'fireRegisterShortCodeAction');

        $this->loader->add_action('admin_enqueue_scripts', $this, 'onAdminEnqueueScripts');
        $this->loader->add_action('wp_enqueue_scripts', $this, 'onWpEnqueueScripts');
        $this->loader->add_filter('script_loader_tag', $this, 'onScriptLoaderTag', 10, 2);

        // Add hook to register our short codes.
        $this->loader->add_action(self::ON_REGISTER_SHORTCODE_ACTION, $this, 'privRegisterShortCodes');

        $this->loader->run();
    }


    /**
     * Allow any short codes to be registered
     */
    public function fireRegisterShortCodeAction() {
        // Fire action
        do_action(
            self::ON_REGISTER_SHORTCODE_ACTION,
            new class() implements ShortCodeRegInf {
                /**
                 * Handle registration of any short codes.
                 *
                 * {@inheritDoc}
                 * @see \RainCity\WPF\ShortCode\ShortCodeRegInf::registerShortCode()
                 */
                public function registerShortCode(ShortCodeImplInf $shortCodeImpl) {
                    add_shortcode($shortCodeImpl->getTagName(), array($shortCodeImpl, 'renderShortCode'));
                    add_filter(DocumentationTab::DOCUMENTATION_FILTER, array($shortCodeImpl, 'getDocumentation'));
                    add_filter('shortcode_atts_'.$shortCodeImpl->getTagName(), array($shortCodeImpl, 'filterAttributes'), 10, 4);
                }
            }
            );
    }


    /**
     * Handler for 'admin_enqueue_scripts' action.
     *
     * Derived classes should override this function to register admin
     * scripts and styles. This function should be used for any scripts
     * or styles that need to be available on any admin page.
     *
     * <p>Option Tabs should use the
     * {@link AdminSettingsTab::onEnqueueScripts()} function for registering
     * tab specific scripts and styles.
     */
    public function onAdminEnqueueScripts() {}


    /**
     * Handler for 'wp_enqueue_scripts' action.
     *
     * Derived classes should override this function to register public
     * scripts and styles. This function should be used for any scripts
     * or styles that need to be available on any public page.
     */
    public function onWpEnqueueScripts() {}


    /**
     * Handler 'script_loader_tag' filter.
     *
     * Checks if the script tag contains 'async' or 'defer'. If so, modifies
     * the &lt;script&gt; element to have the corresponding tag.
     *
     * @param string $tag The &lt;script&gt; tag.
     * @param string $handle The handle for the script.
     *
     * @return string  A possibly modified script tag.
     */
    public function onScriptLoaderTag(string $tag, string $handle) {
        $replacements = array('<script');

        // if the unique handle/name of the registered script has 'async' in it
        if (strpos($handle, 'async') !== false) {
            // include the async attribute in the tag
            array_push($replacements, 'async');
        }

        // if the unique handle/name of the registered script has 'defer' in it
        if (strpos($handle, 'defer') !== false) {
            // include the defer attribute in the tag
            array_push($replacements, 'defer');
        }

        return str_replace( '<script', join(' ', $replacements), $tag );
    }


    /**
     * Trigger any database upgrades. Hooked via the 'init' action.
     *
     * @throws \Exception
     */
    public function privUpgradeDatabase() {
        $dbVersions = get_option(WordPressPlugin::DATABASE_VERSIONS_OPTIONS_NAME, array ($this->pluginSlug => '1.0.0'));

        /*
         * If the version is the default, assume this is the first time this
         * code has run since installation in which case the database would
         * be new and insync with the current plugin version.
         */
        if (!isset($dbVersions[$this->pluginSlug]) || $dbVersions[$this->pluginSlug] === '1.0.0') {
            $dbVersions[$this->pluginSlug] = $this->pluginVersion;
            update_option (WordPressPlugin::DATABASE_VERSIONS_OPTIONS_NAME, $dbVersions);
        }
        else {
            $this->databaseVersion = $dbVersions[$this->pluginSlug];

            if ($this->doDatabaseUpgrade($this->pluginVersion)) {
                /*
                 * It's possible for the database upgrade process to trigger
                 * additional requests (ajax) into WordPress. To ensure the upgrade
                 * is only performed once we wrap it with a transient flag.
                 */
                $transName = 'raincity_wpf_UpgradeDatabaseActive';

                $upgradeActive = get_transient($transName);

                if (false === $upgradeActive) {
                    set_transient($transName, true, MINUTE_IN_SECONDS * 2);

                    $this->doDbUpgrades($this->getDatabaseUpgrades());

                    $dbVersions[$this->pluginSlug] = $this->pluginVersion;
                    update_option (WordPressPlugin::DATABASE_VERSIONS_OPTIONS_NAME, $dbVersions);

                    delete_transient($transName);
                }
            }
        }
    }

    private function doDbUpgrades(array $upgrades) {
        uksort($upgrades, 'version_compare');

        foreach ($upgrades as $version => $upgradeFunc) {
            if ($this->doDatabaseUpgrade($version)) {
                if (is_callable($upgradeFunc)) {
                    call_user_func($upgradeFunc);
                }
                else {
                    throw new \Exception("Unable to call upgrade function for version ${version}"); // NOSONAR
                }

                $this->log->debug("Upgraded database to $version");
                $this->databaseVersion = $version;
            }
        }
    }

    /**
     * Check if the database needs to be upgraded to the specified version.
     *
     * @param string $version The version number to check
     *
     * @return bool Returns true if the database upgrade should be performed.
     *      Otherwise returns false.
     */
    private function doDatabaseUpgrade(string $version): bool {
        return version_compare($this->databaseVersion, $version) == -1;
    }


    /**
     * Setup the default hooks and actions
     *
     * @access protected
     */
    protected function setup_actions() {
        $this->loader = new ActionFilterLoader($this->pluginSlug);

        // Add actions to plugin activation and deactivation hooks
        register_activation_hook( $this->mainPluginFilename, array($this, 'activate_plugin' ));
        register_deactivation_hook( $this->mainPluginFilename, array($this, 'deactivate_plugin' ));
        register_uninstall_hook( $this->mainPluginFilename, array(get_class($this), 'uninstall_plugin' ));

        $this->loader->add_action('init', null, function () {
            new PluginUpdater($this->pluginName, $this->pluginSlug, $this->mainPluginFilename, $this->pluginVersion); // NOSONAR
        });

        $this->loader->add_filter('plugin_action_links', $this, 'addPluginActionLinks', 10, 4);
    }


    /**
     * Handler for self::ON_REGISTER_SHORTCODE_ACTION.
     *
     * @param ShortCodeRegInf $regHandler Interface to use in registering
     *          short code handlers.
     */
    public final function privRegisterShortCodes(ShortCodeRegInf $regHandler) {
        $regHandler->registerShortCode(new UsernameShortCode());
        $regHandler->registerShortCode(new EmailShortCode());
    }


    /**
     * Hook for the 'plugin_action_links' filter.
     *
     * @param array     $actions    Array of plugin action links
     * @param string    $pluginFile Path to the plugin file
     * @param array     $pluginData Array of plugin data
     * @param string    $context    The plugin context
     *
     * @return array    A potentially updated array of plugin action links.
     */
    public function addPluginActionLinks(array $actions, string $pluginFile, array $pluginData, string $context): array {
        if ($pluginFile == $this->mainPluginFilename) {
            $settingsSlug = $this->getSettingsPageSlug();

            if (isset($settingsSlug)) {
                $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=' . $settingsSlug . '">Settings</a>';
                array_unshift($actions, $settings_link);
            }
        }

        return $actions;
    }

    /**
     * Retrieves the Settings page slug of the plugin. This method should be
     * overwritten by a plugin implementation if it has a Settings page.
     *
     * @return NULL
     */
    protected function getSettingsPageSlug(): ?string {
        return null;
    }


    /**
     * The code that runs during plugin activation.
     */
    public function activate_plugin() {
        Logger::getLogger(get_called_class (), $this->pluginSlug)->debug('Activating '.$this->pluginName);

        $options = static::getOptions();
        if (isset($options) && is_array($options)) {
            if (isset($options['name'])) {
                add_option($options['name'], $options['initialValue']);
            }
            else {
                foreach ($options as $optionEntry) {
                    if (is_array($optionEntry)) {
                        add_option($optionEntry['name'], $optionEntry['initialValue']);
                    }
                }
            }
        }

        do_action(self::ON_PLUGIN_ACTIVATION_ACTION);
    }


    /**
     * The code that runs during plugin deactivation.
     */
    public function deactivate_plugin() {
        Logger::getLogger(get_called_class ())->debug('Deactivating '.$this->pluginName);

        do_action(self::ON_PLUGIN_DEACTIVATION_ACTION);

        CronJob::deactivate();
    }


    /**
     * The code that runs during plugin removal.
     */
    public static function uninstall_plugin() {
        Logger::getLogger(get_called_class (), Utils::getPluginPackageName())->debug('Uninstalling '.Utils::getPluginName());

        do_action(self::ON_PLUGIN_UNINSTALL_ACTION);

        $options = static::getOptions();
        if (isset($options) && is_array($options)) {
            if (isset($options['name'])) {
                delete_option($options['name']);
            }
            else {
                foreach ($options as $optionEntry) {
                    if (is_array($optionEntry)) {
                        delete_option($optionEntry['name']);
                    }
                }
            }
        }

        DataCache::uninstall();
        Logger::uninstall();    // Logger should always be last
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @param   AdminHelperInf   $adminInst Reference to a class implementing the Admin_Helper_Inf interface.
     */
    public function register_admin_helper(AdminHelperInf $adminInst) {

        if (is_admin() && isset($adminInst)) {
            $this->loader->add_action( 'admin_enqueue_scripts', $adminInst, 'onAdminEnqueueScripts' );

            $this->loader->add_action( 'admin_init', $adminInst, 'addSettings' );
            $this->loader->add_action( 'admin_menu', $adminInst, 'addSettingsMenu' );
        }
    }


    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    ActionFilterLoader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }


    /**
     * Kick start a plugin.
     *
     * @param string    $pluginClass    The fully qualified name of the plugin class.
     * @param string    $entryPointFile The entrypoint file for the class.
     *
     * @return object   An instance of the plugin class.
     */
    public static function kickstart(string $pluginClass, string $entryPointFile)
    {
        if (defined('ABSPATH')) { // Wrap in case we get invoked via unit testing
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        Logger::setLogger(WordPressLogger::class);

        $pluginData = get_plugin_data($entryPointFile, false, false);

        static $shutdownFunctionRegistered = false;

        if (!$shutdownFunctionRegistered) {
            $shutdownFunctionRegistered = true;
            $pluginSlug = $pluginData['TextDomain'];

            register_shutdown_function(function () use ($pluginSlug) {
                $error = error_get_last();

                if( $error !== NULL) {
                    $logger = Logger::getLogger(WordPressLogger::BASE_LOGGER, $pluginSlug);

                    switch ($error['type']) {
                        case E_ERROR:
                            $level = \Psr\Log\LogLevel::CRITICAL;
                            break;

                        case E_WARNING:
                            $level = \Psr\Log\LogLevel::WARNING;
                            break;

                        case E_NOTICE:
                            $level = \Psr\Log\LogLevel::NOTICE;
                            break;

                        case E_DEPRECATED:
                            $level = \Psr\Log\LogLevel::DEBUG;
                            break;

                        default:
                            $level = \Psr\Log\LogLevel::INFO;
                            break;
                    }

                    // Don't log DEBUG messages
                    if (\Psr\Log\LogLevel::DEBUG != $level) {
                        $logger->log($level, 'PHP Error: ', $error);
                    }
                }
            });
        }

        return forward_static_call(array($pluginClass, 'instance'), $pluginData);
    }
}
