<?php
namespace RainCity\WPF;

final class LastLoginManager
    implements ActionHandlerInf
{
    const COLUMN_NAME = 'raincity_wpf-last-login';
    const LAST_LOGIN_META_TAG = 'last_login';

    private $textDomain;

    public function __construct($textDomain) {
        $this->textDomain = $textDomain;
    }

    /**
     * Initialize any actions or filters for the helper.
     *
     * @param ActionFilterLoader $loader
     */
    public function loadActions(ActionFilterLoader $loader) {
        $loader->add_action('wp_login', $this, 'wpLoginAction', 10, 2);
        $loader->add_action('user_register', $this, 'userRegisterAction');

        if ( is_admin() ) {
            $loader->add_filter('manage_users_columns', $this, 'addLastLoginColumn');
            $loader->add_filter('wpmu_users_columns', $this, 'addLastLoginColumn');
            $loader->add_filter('manage_users_custom_column', $this, 'manageUsersCustomColumn', 10, 3);
            $loader->add_filter('manage_users_sortable_columns', $this, 'markColumnSortable');
            $loader->add_filter('manage_users-network_sortable_columns', $this, 'markColumnSortable');

            $loader->add_action('admin_print_styles-users.php', $this, 'formatLastLoginColumn');
            $loader->add_action('admin_print_styles-site-users.php', $this, 'formatLastLoginColumn');
            $loader->add_action('pre_get_users', $this, 'preGetUsersAction');
        }
    }

    /**
     * wp_login action hook - Set the user's last login time to now.
     *
     * @param string $userLogin The user's login name.
     * @param \WP_User $wpUser WordPress User object
     */
    public function wpLoginAction(string $userLogin, \WP_User $wpUser) {
        update_user_meta( $wpUser->ID, self::LAST_LOGIN_META_TAG, time() );
    }

    /**
     * user_register action hook - Set the user's last login time to zero.
     *
     * @param int $userId The user ID.
     */
    public function userRegisterAction( $userId ) {
        update_user_meta( $userId, self::LAST_LOGIN_META_TAG, 0 );
    }

    /**
     * manage_users_columns and wpmu_users_columns filter hook
     *
     * Adds the last login column to the admin user list.
     *
     * @param  array $cols The default columns.
     *
     * @return array
     */
    public function addLastLoginColumn(array $columns) {
        $columns[self::COLUMN_NAME] = __( 'Last Login', $this->textDomain );

        return $columns;
    }


    /**
     * admin_print_styles-users.php and admin_print_styles-site-users.php action hook
     *
     * Defines the width of the last login column
     */
    public function formatLastLoginColumn() {
        ?>
		<style type="text/css">
			.column-<?php echo self::COLUMN_NAME; ?> { width: 15%; }
		</style>
		<?php
	}

	/**
	 * manage_users_custom_column filter hook
	 *
	 * Displays the last login value
	 *
     * @param string $output The value to be output by default.
     * @param string $column_name The name of the column.
     * @param int    $user_id The user's id.
     *
     * @return string The last login date if available otherwise 'Never'.
	 */
	public function manageUsersCustomColumn(string $output, string $column_name, int $user_id) {
	    if ( self::COLUMN_NAME === $column_name ) {
	        $output = __( 'Never', $this->textDomain );
	        $last_login = (int) get_user_meta( $user_id, self::LAST_LOGIN_META_TAG, true );

	        if ( $last_login ) {
	            $output  = date_i18n( get_option( 'date_format' ), $last_login );
	        }
	    }

	    return $output;
	}

    /**
     * manage_*_sortable_columns filter hook
     *
     * Mark the column as sortable.
     *
     * @param  array $columns User table columns.
     *
     * @return array
     */
	public function markColumnSortable(array $columns) {
        $columns[self::COLUMN_NAME] = self::COLUMN_NAME;

        return $columns;
    }

    /**
     * pre_get_user action hook
     *
     * Handle ordering by last login.
     *
     * @param  \WP_User_Query $userQuery Request arguments.
     *
     * @return \WP_User_Query
     */
    public function preGetUsersAction(\WP_User_Query $userQuery ) {
        if ( isset( $userQuery->query_vars['orderby'] ) && self::COLUMN_NAME === $userQuery->query_vars['orderby'] ) {
            $userQuery->query_vars = array_merge(
                $userQuery->query_vars,
                array(
                    'meta_key' => self::COLUMN_NAME,
                    'orderby'  => 'meta_value_num',
                )
            );
        }

        return $userQuery;
    }
}
