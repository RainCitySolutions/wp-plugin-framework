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
        ?>
        <script type='text/javascript'>
        	jQuery(document).ready(function($) {
        		$(".wpfclearcache").click(function() {
        			$.post(
        				"<?php echo admin_url('admin-ajax.php'); ?>",
        				{
        					_ajax_nonce: "<?php echo wp_create_nonce($this->pluginName); ?>",
        					action: "<?php echo self::AJAX_ACTION_CLEAR_DATA_CACHE; ?>",
        					plugin: "<?php echo $this->pluginName; ?>"
        				},
        				function(data) {}
        				);
        		});
        	});
        </script>
        <?php
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
