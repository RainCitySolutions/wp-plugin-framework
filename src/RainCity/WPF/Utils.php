<?php
namespace RainCity\WPF;

use RainCity\Logging\Helper;

/**
 *
 * @since      1.0.0
 * @package    utils
 */
class Utils
{
    public static function getPluginFile( $plugin_name ) {
        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        $plugins = get_plugins();
        foreach( $plugins as $plugin_file => $plugin_info ) {
            if ( $plugin_info['Name'] == $plugin_name ) return $plugin_file;
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
    public static function getPluginInfo(): PluginInformation {
        $pluginInfo = new PluginInformation();

        // Get the names of the currently active plugins
        $plugins = array_unique(
                        array_merge(
                            get_option('active_plugins'),
                            array_keys((array) get_site_option( 'active_sitewide_plugins', array() ))
                        ),
                        SORT_REGULAR);
        foreach ($plugins as $ndx => $pluginEntryPoint) {
            $parts = explode('/', $pluginEntryPoint);
            $plugins[$ndx] = $parts[0];
        }

        // Create a Regex pattern with the plugin names
        $pluginPathRegex = sprintf(self::PLUGIN_PATH_PATTERN, implode ('|', $plugins));

        $matches = array();
        $stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        /*
         * For each entry in the stacktrace, check if the file is not in
         * the 'vendor' folder and is in a plugin folder.
         */
        foreach ($stackTrace as $entry) {
            if (isset($entry['file'])) {
                $normalizedPath = wp_normalize_path($entry['file']);

                if (!preg_match(self::VENDOR_IN_PATH_REGEX, $normalizedPath, $matches) &&
                    preg_match($pluginPathRegex, $normalizedPath, $matches) ) {
                        // Now that we've found a match, save the info and exit the loop
                        $pluginInfo->pluginPackage = $matches[2];
                        $pluginInfo->pluginPath = $matches[1];
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
            $path = wp_normalize_path(plugin_dir_path( __FILE__ ) ) ;

            if (preg_match($pluginPathRegex, $path, $matches) ) {
                $pluginInfo->pluginPackage = $matches[2];
                $pluginInfo->pluginPath = $matches[1];
            }
            else {
                // Assume the plugin is immediately prior to the vendor folder
                if (preg_match('/(.+\/(.*))\/vendor\/.*/', $path, $matches)) {
                    $pluginInfo->pluginPackage = $matches[2];
                    $pluginInfo->pluginPath = $matches[1];
                }
                else {
                    Helper::log('Unable to determine plugin package name: ', array('regex' => $pluginPathRegex, 'stack' =>  $stackTrace));
                }
            }
        }

        return $pluginInfo;
    }


    /**
     * Returns the plugin package name based on the folder name following
     * 'plugins' in the file path. This is the name used by the plugin zip
     * file, the root folder within it and thus the name of the folder under
     * the 'plugins' folder.
     *
     * @return string Name of the current plugin
     */
    public static function getPluginPackageName () {
        $pluginInfo = self::getPluginInfo();

        return $pluginInfo->pluginPackage;
    }


    public static function getPluginName () {
        $pluginPackage = self::getPluginPackageName();

        $plugins = get_plugins();
        foreach( $plugins as $plugin_info ) {
            if ( $plugin_info['TextDomain'] == $pluginPackage )
                return $plugin_info['Name'];
        }
        return null;
    }


    public static function getPluginUrl () {
        return plugins_url() .'/'. self::getPluginPackageName() .'/';
    }


    public static function getPluginWriteDir () {
        $path = null;

        if (function_exists('wp_upload_dir')) {
            $path = wp_upload_dir()['basedir'] . '/'. self::getPluginPackageName();
        } else {
            $path = sys_get_temp_dir();
        }

        return $path;
    }

    public static function isPluginActive($pluginFile) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        return is_plugin_active($pluginFile);
    }

    /**
     * Injects a hook to require that users are logged in in order to access pages
     *
     */
    public static function requireLogin ()
    {
        /**
         * Filter 'login_url' to account for Formidable User Registration plugin
         */
        add_filter('login_url',
            function(string $login_url, string $redirect, bool $force_reauth) {
                if (class_exists('FrmRegLoginController')) {
                    $login_url = \FrmRegLoginController::login_page_url('');
                }

                return $login_url;
            },
            10,
            3);


        /**
         * Based on https://wordpress.org/plugins/wp-force-login/
         */
        add_action( 'template_redirect', function () {
            // Exceptions for AJAX, Cron, or WP-CLI requests
            if (( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
                ( defined( 'DOING_CRON' ) && DOING_CRON ) ||
                ( defined( 'WP_CLI' ) && WP_CLI ) ) {
                    return;
                }

                // Redirect unauthorized visitors
                if ( ! is_user_logged_in() ) {
                    // Get visited URL
                    $url  = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';
                    $url .= '://' . $_SERVER['HTTP_HOST'];
                    // port is prepopulated here sometimes
                    if ( strpos( $_SERVER['HTTP_HOST'], ':' ) === FALSE ) {
                        $url .= in_array( $_SERVER['SERVER_PORT'], array('80', '443') ) ? '' : ':' . $_SERVER['SERVER_PORT'];
                    }
                    $url .= $_SERVER['REQUEST_URI'];

                    /**
                     * Bypass filters.
                     */
                    $bypass = apply_filters('raincity_wpf_requirelogin_bypass', false, $url);
                    //                $whitelist = apply_filters( 'raincity_wpf_requirelogin_whitelist', array() );

                    if (preg_replace( '/\?.*/', '', $url ) !== preg_replace( '/\?.*/', '', wp_login_url() ) &&
                        ! $bypass
                    //                    && ! in_array( $url, $whitelist )
                        ) {
                            // Determine redirect URL
                            $redirect_url = apply_filters( 'raincity_wpf_requirelogin_redirect', $url );
                            // Set the headers to prevent caching
                            nocache_headers();
                            // Redirect
                            wp_safe_redirect( wp_login_url( $redirect_url ), 302 );
                            exit;
                        }
                }
                elseif ( function_exists('is_multisite') && is_multisite() ) {
                    // Only allow Multisite users access to their assigned sites
                    if ( ! is_user_member_of_blog() && ! current_user_can('setup_network') ) {
                        wp_die( __( "You're not authorized to access this site.", 'wp-force-login' ), get_option('blogname') . ' &rsaquo; ' . __( "Error", 'wp-force-login' ) );
                    }
                }
        });
    }

    public static function getWPUser () {
        $wpUser = wp_get_current_user();

        if (!$wpUser->exists() && isset($_POST['frm_user_id'])) {
            $wpUser = new \WP_User($_POST['frm_user_id']);
        }

        return $wpUser;
    }

}

class PluginInformation {
    public $pluginPackage;
    public $pluginPath;

    public function __construct() {
        $this->pluginPackage = 'unknown';
        $this->pluginPath = '';
    }
}
