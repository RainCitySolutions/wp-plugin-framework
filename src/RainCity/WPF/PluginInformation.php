<?php declare(strict_types=1);
namespace RainCity\WPF;

use RainCity\Logging\Helper;

class PluginInformation {
    private string $pluginPackage;
    private string $pluginPath;
    /** @var array<string, mixed> @see \get_plugin_data() */
    private array $pluginData = [];

    private function __construct() {
        $this->pluginPackage = 'unknown';
        $this->pluginPath = '';
    }

    public function getPackage(): string
    {
        return $this->pluginPackage;
    }

    public function getPath(): string
    {
        return $this->pluginPath;
    }

    public function getVersion(): string
    {
        return $this->pluginData['Version'];
    }


    public static function getPluginFile(string $pluginName): ?string
    {
        if (defined('ABSPATH')) { // Wrap in case we get invoked via unit testing
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        foreach( $plugins as $pluginFile => $pluginInfo ) {
            if ( $pluginInfo['Name'] == $pluginName ) {
                return $pluginFile;
            }
        }

        return null;
    }

    public static function getPluginFileByName(string $pluginName): ?string
    {
        return self::getPluginFile($pluginName);
    }

    public static function getPluginFileBySlug(string $pluginSlug): ?string
    {
        if (defined('ABSPATH')) { // Wrap in case we get invoked via unit testing
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        foreach (array_keys($plugins) as $pluginFile) {
            $slug = dirname(plugin_basename($pluginFile));

            if ($slug == $pluginSlug) {
                return $pluginFile;
            }
        }

        return null;
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
     * @access private
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
                    $pluginInfo->pluginPath = $matches[1];
                    $pluginInfo->pluginPackage = $matches[2];
                    $pluginInfo->pluginData = \get_plugin_data($normalizedPath);
                    break;
                }
            }
        }

        /*
         * If we weren't able to find a match above, assume we are running
         * within the plugin's code base and get the information from this
         * file's path.
         */
        if (strcmp('unknown', $pluginInfo->pluginPackage) == 0) {
            self::extractPluginInfoFromPath($pluginInfo, $pluginPathRegex, $stackTrace);
        }

        return $pluginInfo;
    }

    /**
     *
     * @param PluginInformation $pluginInfo
     * @param string $pluginPathRegex
     * @param array<mixed> $stackTrace
     */
    private static function extractPluginInfoFromPath(
        PluginInformation &$pluginInfo,
        string $pluginPathRegex,
        array $stackTrace
        ): void
    {
        $matches = array();

        $path = \wp_normalize_path(plugin_dir_path( __FILE__ ) ) ;

        if (preg_match($pluginPathRegex, $path, $matches) ) {
            self::updatePluginInfoFromSlug($matches[2], $matches[1], $pluginInfo);
        }
        else {
            // Assume the plugin is immediately prior to the vendor folder
            if (preg_match('/(.+\/(.*))\/vendor\/.*/', $path, $matches)) {
                self::updatePluginInfoFromSlug($matches[2], $matches[1], $pluginInfo);
            }
            else {
                Helper::log(
                    'Unable to determine plugin package name: ',
                    array('regex' => $pluginPathRegex, 'stack' =>  $stackTrace)
                    );
            }
        }
    }

    private static function updatePluginInfoFromSlug(
        string $slug,
        string $path,
        PluginInformation &$pluginInfo): void
    {
        $pluginFile = self::getPluginFileBySlug($slug);
        $normalizedPath = \wp_normalize_path($pluginFile);

        $pluginInfo->pluginPackage = $slug;
        $pluginInfo->pluginPath = $path;
        $pluginInfo->pluginData = \get_plugin_data($normalizedPath);
    }


    /**
     * Returns the plugin package name based on the folder name following
     * 'plugins' in the file path. This is the name used by the plugin zip
     * file, the root folder within it and thus the name of the folder under
     * the 'plugins' folder.
     *
     * @return string Name of the current plugin
     */
    public static function getPluginPackageName (): string
    {
        $pluginInfo = self::getPluginInfo();

        return $pluginInfo->pluginPackage;
    }


    public static function getPluginName(): ?string
    {
        $pluginPackage = self::getPluginPackageName();

        $plugins = \get_plugins();
        foreach( $plugins as $plugin_info ) {
            if ( $plugin_info['TextDomain'] == $pluginPackage ) {
                return $plugin_info['Name'];
            }
        }
        return null;
    }


    public static function getPluginUrl(): string
    {
        return \plugins_url() .'/'. self::getPluginPackageName() .'/';
    }


    public static function getPluginWriteDir(): string
    {
        $path = null;

        if (function_exists('wp_upload_dir')) {
            $path = \wp_upload_dir()['basedir'] . '/'. self::getPluginPackageName();
        } else {
            $path = sys_get_temp_dir();
        }

        return $path;
    }

    public static function isPluginActive(string $pluginFile): bool
    {
        if (defined('ABSPATH')) { // Wrap in case we get invoked via unit testing
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return \is_plugin_active($pluginFile);
    }
}
