<?php declare(strict_types=1);
namespace RainCity\WPF;

use RainCity\Logging\Helper;

class PluginInformation {
    // Local keys
    private const PLUGIN_DATA_SLUG = 'Slug';    // Plugin slug
    private const PLUGIN_DATA_FILE = 'File';    // Entrypoint file relative to plugins directory
    private const PLUGIN_DATA_PATH = 'Path';    // TBD

    // WordPress keys
    private const PLUGIN_DATA_NAME = 'Name';
    private const PLUGIN_DATA_VERSION = 'Version';
    /*
     private const PLUGIN_DATA_PLUGIN_URI = 'PluginURI';
     private const PLUGIN_DATA_DESCRIPTION = 'Description';
     private const PLUGIN_DATA_AUTHOR = 'Author';
     private const PLUGIN_DATA_AUTHOR_URI = 'AuthorURI';
     private const PLUGIN_DATA_TEXT_DOMAIN = 'TextDomain';
     private const PLUGIN_DATA_DOMAIN_PATH = 'DomainPath';
     private const PLUGIN_DATA_NETWORK = 'Network';
     private const PLUGIN_DATA_REQUIRES_WP = 'RequiresWP';
     private const PLUGIN_DATA_REQUIRES_PHP = 'RequiresPHP';
     private const PLUGIN_DATA_UPDATE_URI = 'UpdateURI';
     private const PLUGIN_DATA_REQUIRES_PLUGINS = 'RequiresPlugins';
     private const PLUGIN_DATA_TITLE = 'Title';
     private const PLUGIN_DATA_AUTHOR_NAME = 'AuthorName';
     */

    /** @var array<string, mixed> @see \get_plugin_data() */
    private array $pluginData = [];

    /**
     * @param string $pluginFile
     * @param array<string, mixed> $pluginInfo
     */
    private function __construct(string $pluginFile = '', array $pluginInfo = [])
    {
        $this->pluginData = array_merge(
            [
                self::PLUGIN_DATA_SLUG => empty($pluginFile) ? 'unknown' : dirname(plugin_basename($pluginFile)),
                self::PLUGIN_DATA_PATH => empty($pluginFile) ? '' : WP_PLUGIN_DIR . '/' . dirname($pluginFile),
                self::PLUGIN_DATA_FILE => $pluginFile
            ],
            $pluginInfo
            );
    }

    public function getSlug(): string
    {
        return $this->pluginData[self::PLUGIN_DATA_SLUG];
    }

    public function getPath(): string
    {
        return $this->pluginData[self::PLUGIN_DATA_PATH];
    }

    public function getVersion(): string
    {
        return $this->pluginData[self::PLUGIN_DATA_VERSION];
    }

    public function getPluginFile(): ?string
    {
        return $this->pluginData[self::PLUGIN_DATA_FILE] ?? null;
    }

    public function getPluginName(): ?string
    {
        return $this->pluginData[self::PLUGIN_DATA_NAME] ?? null;
    }

    public function getPluginUrl(): string
    {
        return \plugins_url() .'/'. $this->getSlug() .'/';
    }

    public function getPluginWriteDir(): string
    {
        if (function_exists('wp_upload_dir')) {
            $path = \wp_upload_dir()['basedir'] . '/'. $this->getSlug();
        } else {
            $path = sys_get_temp_dir();
        }

        return $path;
    }

    public static function getPluginInfoByPluginName(string $pluginName): PluginInformation
    {
        $info = new PluginInformation();

        if (defined('ABSPATH')) { // Wrap in case we get invoked via unit testing
            require_once ABSPATH . '/wp-admin/includes/plugin.php'; // @phpstan-ignore requireOnce.fileNotFound
        }

        $plugins = get_plugins();
        foreach( $plugins as $pluginFile => $pluginInfo ) {
            if ( $pluginInfo['Name'] == $pluginName ) {
                $info = new PluginInformation($pluginFile, $pluginInfo);
            }
        }

        return $info;
    }

    public static function getPluginInfoByPluginSlug(string $pluginSlug): PluginInformation
    {
        $info = new PluginInformation();

        if (defined('ABSPATH')) { // Wrap in case we get invoked via unit testing
            require_once ABSPATH . '/wp-admin/includes/plugin.php'; // @phpstan-ignore requireOnce.fileNotFound
        }

        $plugins = get_plugins();
        foreach ($plugins as $pluginFile => $pluginInfo) {
            if (dirname(plugin_basename($pluginFile)) == $pluginSlug) {
                $info = new PluginInformation($pluginFile, $pluginInfo);
            }
        }

        return $info;
    }


    const PLUGIN_PATH_PATTERN = '/(.+\/(%s))\/.*/';
    const VENDOR_IN_PATH_REGEX = '/.+\/vendor\/.*/';

    /**
     * Returns information about the currently exectuing plugin by looking
     * in the stack trace for the first entry where the code is from the
     * plugin itself and not something from the 'vendor' folder.
     *
     * It's possible that there are multiple plugins using this library. PHP
     * will only load the code once so it could be that this library, loaded
     * from the plugin A folder is currently running in the call stack of
     * plugin B. As a result we need to look back in the call stack to find
     * first folder where the call isn't against the 'vendor' folder. This
     * will be a call within the plugin itself.
     *
     * @return PluginInformation An instance of a PluginInformation class
     */
    public static function getPluginInfo(): PluginInformation
    {
        $pluginInfo = new PluginInformation();

        // Get the names of the currently active plugins
        $plugins = array_unique(
            array_merge(
                \get_option('active_plugins'),
                array_keys((array) get_site_option( 'active_sitewide_plugins', array() ))
                ),
            SORT_REGULAR);
        foreach ($plugins as $ndx => $pluginEntryPoint) {
            $parts = explode('/', $pluginEntryPoint);
            $plugins[$ndx] = $parts[0];
        }

        // Create a Regex pattern with the plugin names
        $pluginPathRegex = sprintf(self::PLUGIN_PATH_PATTERN, implode ('|', $plugins));

        $stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        /*
         * For each entry in the stacktrace, check if the file is not in
         * the 'vendor' folder and is in a plugin folder.
         */
        foreach ($stackTrace as $entry) {
            if (isset($entry['file'])) {
                $normalizedPath = \wp_normalize_path($entry['file']);
                /** @var string[] */
                $matches = array();

                if (0 === preg_match(self::VENDOR_IN_PATH_REGEX, $normalizedPath, $matches) &&
                    1 === preg_match($pluginPathRegex, $normalizedPath, $matches))
                {
                    // Now that we've found a match, save the info and exit the loop
                    $pluginInfo = PluginInformation::getPluginInfoByPluginSlug($matches[2]);
                    //                     $pluginInfo->pluginPath = $matches[1];
                    //                     $pluginInfo->pluginPackage = $matches[2];
                    //                     $pluginInfo->pluginData = \get_plugin_data($normalizedPath);
                    break;
                }
            }
        }

        /*
         * If we weren't able to find a match above, assume we are running
         * within the plugin's code base and get the information from this
         * file's path.
         */
        if (strcmp('unknown', $pluginInfo->getSlug()) == 0) {
            $pluginInfo = self::extractPluginInfoFromPath($pluginPathRegex, $stackTrace);
        }

        return $pluginInfo;
    }

    /**
     *
     * @param string $pluginPathRegex
     * @param array<mixed> $stackTrace
     */
    private static function extractPluginInfoFromPath(
        string $pluginPathRegex,
        array $stackTrace
        ): PluginInformation
        {
            $pluginInfo = new PluginInformation();

            $matches = array();

            $path = \wp_normalize_path(plugin_dir_path( __FILE__ ) ) ;

            if (preg_match($pluginPathRegex, $path, $matches) ) {
                $pluginInfo = PluginInformation::getPluginInfoByPluginSlug($matches[2]);
            }
            else {
                // Assume the plugin is immediately prior to the vendor folder
                if (preg_match('/(.+\/(.*))\/vendor\/.*/', $path, $matches)) {
                    $pluginInfo = PluginInformation::getPluginInfoByPluginSlug($matches[2]);
                }
                else {
                    Helper::log(
                        'Unable to determine plugin package name: ',
                        array('regex' => $pluginPathRegex, 'stack' =>  $stackTrace)
                        );
                }
            }

            return $pluginInfo;
    }


    public static function isPluginActive(string $pluginFile): bool
    {
        if (defined('ABSPATH')) { // Wrap in case we get invoked via unit testing
            include_once ABSPATH . 'wp-admin/includes/plugin.php';  // @phpstan-ignore includeOnce.fileNotFound
        }

        return \is_plugin_active($pluginFile);
    }
}
