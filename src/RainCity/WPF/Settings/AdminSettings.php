<?php
namespace RainCity\WPF\Settings;

use RainCity\Singleton;
use RainCity\Logging\Logger;
use RainCity\WPF\AdminHelperInf;
use RainCity\WPF\Utils;

abstract class AdminSettings
    extends Singleton
    implements AdminHelperInf
{
    /**
     * @var string  $plugin_name    The ID of this plugin.
     */
    protected $plugin_name;

    /**
     * @var string  $version    The current version of this plugin.
     */
    protected $version;

    protected $log;

    protected $optionsPageTitle;
    protected $optionsPageSlug;
    protected $optionsMenuTitle;

    private $tabs = array();

    /**
     * Initialize the class and set its properties.
     *
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    protected function __construct(string $plugin_name, string $version, string $pageTitle, string $pageSlug, string $menuTitle) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->optionsPageTitle = $pageTitle;
        $this->optionsPageSlug = $pageSlug;
        $this->optionsMenuTitle = $menuTitle;

        $this->log = Logger::getLogger(get_class());
    }


    /**
     * Register the settings menu.
     *
     * Hooked into the 'admin_menu' event.
     */
    public final function addSettingsMenu() {
        if (isset($this->optionsPageTitle) &&
            isset($this->optionsPageSlug) &&
            isset($this->optionsMenuTitle))
        {
            add_options_page(
                $this->optionsPageTitle,    // page title
                $this->optionsMenuTitle,    // menu title
                'manage_options',           // capability reqd
                $this->optionsPageSlug,     // menu slug name
                array(                      // function to output page contents
                    $this,
                    'renderSettingsPage'
                )
            );
        }
    }

    /**
     * Register the settings for the active tab.
     *
     * Hooked into the 'admin_init' event and called as a result of the child
     * class being registered with the plugin as the admin helper.
     */
    public final function addSettings() {
        foreach ($this->tabs as $tab) {
            $tab->registerActions();
        }

        $activeTab = $this->getActiveTab();
        $activeTab->initSettings($this->optionsPageSlug);
    }

    /**
     * Register the scripts and stylesheets for the active tab.
     *
     * Hooked into the 'admin_enqueue_scripts' event.
     */
    public final function onAdminEnqueueScripts () {
        $activeTab = $this->getActiveTab();

        $activeTab->onEnqueueScripts($this->plugin_name, Utils::getPluginUrl(), $this->version);
    }

    private function getActiveTab(): AdminSettingsTab {
        $activeTabId = '';

        if (isset( $_GET[ 'tab' ] )) {
            $activeTabId = $_GET[ 'tab' ];
        } else {
            if (isset($_POST[ '_wp_http_referer']) ) {
                $args = array();

                $urlArgs = parse_url($_POST[ '_wp_http_referer'], PHP_URL_QUERY);
                parse_str ($urlArgs, $args);
                if (isset($args[ 'tab' ]) ) {
                    $activeTabId = $args[ 'tab'];
                }
            }
        }

        $activeTab = $this->tabs[array_key_exists($activeTabId, $this->tabs) ?
                        $activeTabId :
                        array_key_first($this->tabs)];

        return $activeTab;
    }

    public function localSanitize($input) {
        $activeTab = $this->getActiveTab();
        $activeTab->sanitize($input);
    }

    public final function registerTab(AdminSettingsTab $tab) {
        $newTab = true;

        foreach ($this->tabs as $existingTab) {
            if (get_class($tab) === get_class($existingTab)) {
                $newTab = false;
                break;
            }
        }

        if ($newTab) {
            $this->tabs[$tab->getId()] = $tab;
        }
    }


    /**
     * Render the settings page
     */
    public final function renderSettingsPage () {
        // check user capabilities
        if ( current_user_can( 'manage_options' ) && count($this->tabs) != 0)
        {
            $activeTab = $this->getActiveTab();

            ob_start();
            ?>
            <div class="wrap">
                <h2><?php echo $this->optionsPageTitle ?></h2>

                <!-- wordpress provides the styling for tabs. -->
                <h2 class="nav-tab-wrapper">
                    <?php
                        $activeTabId = $activeTab->getId();

                        // Generate a link for each registered tab
                        foreach ($this->tabs as $tab) {
                            // when tab buttons are clicked we jump back to the same page but with a new parameter that represents the clicked tab. accordingly we make it active
                            printf('<a href="?page=%s&tab=%s" class="nav-tab %s">%s</a>',
                                $this->optionsPageSlug,
                                $tab->getId(),
                                $activeTabId === $tab->getId() ? 'nav-tab-active' : '',
                                $tab->getName());
                        }
                    ?>
                </h2>

                <form method="post" action="options.php">
                    <?php
                        settings_fields($this->optionsPageSlug);
                        do_settings_sections($this->optionsPageSlug);
                        submit_button( 'Save Changes' );
                    ?>
                </form>

            	<?php
                    $activeTab->renderPostFormData();
                ?>
            </div>
            <?php

            $html = Utils::minifyHtml(ob_get_contents());
            ob_end_clean();

            print $html;
        }
    }
}
