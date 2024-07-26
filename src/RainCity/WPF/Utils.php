<?php
namespace RainCity\WPF;

/**
 *
 * @since      1.0.0
 * @package    utils
 */
class Utils
{
    /**
     * Injects a hook to require that users are logged in in order to access pages
     *
     */
    public static function requireLogin(): void
    {
        /**
         * Filter 'login_url' to account for Formidable User Registration plugin
         */
        add_filter('login_url',
            function(string $login_url, string $redirect, bool $force_reauth) { // NOSONAR
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
            if (self::isIgnoredRequestType()) {
                return;
            }

            // Redirect unauthorized visitors
            if ( ! is_user_logged_in() ) {
                $url = self::getVisitedUrl();

                /**
                 * Bypass filters.
                 */
                $bypass = apply_filters('raincity_wpf_requirelogin_bypass', false, $url);

                if (preg_replace( '/\?.*/', '', $url ) !== preg_replace( '/\?.*/', '', wp_login_url() ) &&
                    ! $bypass
                //                    && ! in_array( $url, $whitelist )
                    ) {
                        // Determine redirect URL
                        $redirectUrl = apply_filters( 'raincity_wpf_requirelogin_redirect', $url );
                        // Set the headers to prevent caching
                        nocache_headers();
                        // Redirect
                        wp_safe_redirect( wp_login_url( $redirectUrl ), 302 );
                        exit;
                    }
            }
            else {
                self::checkInvalidMultiSiteAccess();
            }
        });
    }

    private static function isIgnoredRequestType(): bool
    {
        $result = false;

        if (( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
            ( defined( 'DOING_CRON' ) && DOING_CRON ) ||
            ( defined( 'WP_CLI' ) && WP_CLI ) )
        {
            $result = true;
        }

        return $result;
    }

    private static function checkInvalidMultiSiteAccess(): void
    {
        // Only allow Multisite users access to their assigned sites
        if (function_exists('is_multisite') &&
            is_multisite() &&
            !is_user_member_of_blog() &&
            !current_user_can('setup_network') )
        {
            wp_die(
                __( "You're not authorized to access this site.", 'wp-force-login' ),
                get_option('blogname') . ' &rsaquo; ' . __( "Error", 'wp-force-login' )
                );
        }
    }

    private static function getVisitedUrl(): string
    {
        $url  = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';
        $url .= '://' . $_SERVER['HTTP_HOST'];

        // port is prepopulated here sometimes
        if ( strpos( $_SERVER['HTTP_HOST'], ':' ) === false ) {
            $url .= in_array( $_SERVER['SERVER_PORT'], array('80', '443') ) ? '' : ':' . $_SERVER['SERVER_PORT'];
        }

        $url .= $_SERVER['REQUEST_URI'];

        return $url;
    }

    /**
     * Fetch a WP_User object for a user by their id
     *
     * If an identifier is not provided, the current user is returned. This
     * may be a non existent user if a user is not currently logged in.
     * <p>
     * If there is no user with the identifier provided a not existent user
     * object is returned.
     * <p>
     * If the user cannot be determined from the identifer, or currently
     * logged in user, the method will use the $_POST['frm_user_id']
     * paramenter, if present, to fetch the user object.
     *
     * @param int $userId (optional) The identifier for a user.
     *
     * @return \WP_User An object representing a WordPress user which may be
     *      a non-existent user.
     */
    public static function getWPUser (int $userId = null): \WP_User
    {
        if (is_null($userId)) {
            $wpUser = wp_get_current_user();
        } else {
            $wpUser = get_user_by('ID', $userId);
            if (false === $wpUser) {
                $wpUser = new \WP_User(0);
            }
        }

        if (!$wpUser->exists() && isset($_POST['frm_user_id'])) {
            $wpUser = new \WP_User($_POST['frm_user_id']);
        }

        return $wpUser;
    }

    /**
     * Fetch the WP_Post object for a post/page with the specified page name.
     *
     * @param string $postName The post_name of a post
     * @param string $postType The post_type of the post, defaults to 'page'
     * @param string $postStatus The post_status of the post, defaults to 'publish'
     *
     * @return \WP_Post|NULL The object representing the post or null if the
     *      post does not exist.
     */
    public static function getWPPostByName(
        string $postName,
        string $postType = 'page',
        string $postStatus = 'publish'
        ): ?\WP_Post
    {
        $query = new \WP_Query(
            [
                'post_type' => $postType,
                'post_status' => $postStatus,
                'name' => $postName
            ]);

        return $query->have_posts() ? reset($query->posts) : null;
    }
}
