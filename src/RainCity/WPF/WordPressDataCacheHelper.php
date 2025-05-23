<?php
namespace RainCity\WPF;

use Psr\Log\LoggerInterface;
use RainCity\DataCache;
use RainCity\Logging\Logger;

/**
 * Helper class for clearing a data cache
 *
 */
class WordPressDataCacheHelper
{
    /** @var LoggerInterface */
    private LoggerInterface $log;

    /** @var DataCache */
    private DataCache $cache;

    /** @var string */
    private string $pluginName;

    const AJAX_ACTION_CLEAR_DATA_CACHE = 'wpfDataCacheClear';


    public function __construct (DataCache $cache, string $pluginName)
    {
        $this->log = Logger::getLogger(get_class($this));
        $this->cache = $cache;
        $this->pluginName = $pluginName;
    }

    public function onRegisterActions(): void
    {
        add_action('wp_ajax_'.self::AJAX_ACTION_CLEAR_DATA_CACHE, array($this, 'ajaxClearDataCache'));
        add_action('admin_print_footer_scripts', array($this, 'injectJavaScript'), 100);
    }

    public function onAdminEnqueueScripts(): void
    {
        wp_localize_script(
            $this->pluginName,
            'raincity_dataCacheObj',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce($this->pluginName),
                'plugin'   => $this->pluginName
            ));
    }

    public function injectButton(string $label = 'Clear Data Cache'): void
    {
       printf('<input type="button" class="wpfclearcache button button-secondary" value="%s" name="clearCache">', $label);
    }

    public function injectJavaScript(): void
    {
        echo "<script type='text/javascript'>\n";
        echo 'var pluginUrl = ' . wp_json_encode( plugin_dir_url('') . '/my_plugin/' ) . ';';
        echo "jQuery(document).ready(function($) {\n";
        echo '    $(".wpfclearcache").click(function() {'."\n";
        echo '        $.post('.admin_url('admin-ajax.php').", {\n";
        echo '            _ajax_nonce: '.wp_create_nonce($this->pluginName).",\n";
        echo '            action: '.self::AJAX_ACTION_CLEAR_DATA_CACHE.",\n";
        echo '            plugin: '.$this->pluginName."\n";
        echo '        }, function(data) {'."\n";
        echo '    });'."\n";
        echo '});',"\n";
        echo '</script>';
    }

    public function ajaxClearDataCache(): void
    {
        $this->log->debug('Ajax request to run Clear DataCache');

        check_ajax_referer($this->pluginName);

        $ajaxPluginName = $_REQUEST['plugin'];
        if ($ajaxPluginName == $this->pluginName) {

            $this->log->debug('Clearing cache');
            // Handle the ajax request
            $this->cache->clear();
        }
        wp_die(); // All ajax handlers die when finished
    }
}
