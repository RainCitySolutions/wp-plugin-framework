<?php
namespace RainCity\WPF;

class EnqueueScripts
    implements EnqueueScriptsInf
{
    const INLINE_SCRIPT_HANDLER = 'raincity_wpf_InlineScripts';

    private $pluginUrlBase;
    private $pluginVersion;

    public function __construct(string $pluginUrlBase, string $pluginVersion) {
        $this->pluginUrlBase = $pluginUrlBase;
        $this->pluginVersion = $pluginVersion;
    }

    /**
     *
     * {@inheritDoc}
     * @see \RainCity\WPF\EnqueueScriptsInf::enqueueScript()
     */
    public function enqueueScript(string $scriptPath, array $dependencies = array())
    {
        $handle = basename($scriptPath,'.'.pathinfo($scriptPath)['extension']);

        wp_enqueue_script(
            $handle,
            $this->pluginUrlBase . $scriptPath,
            $dependencies,
            $this->pluginVersion,
            true
            );
    }

    /**
     *
     * {@inheritDoc}
     * @see \RainCity\WPF\EnqueueScriptsInf::enqueueStyle()
     */
    public function enqueueStyle(string $stylePath, array $dependencies)
    {
        $handle = basename($stylePath,'.'.pathinfo($stylePath)['extension']);

        wp_enqueue_style(
            $handle,
            $this->pluginUrlBase . $stylePath,
            $dependencies,
            $this->pluginVersion,
            'all'
            );
    }

    /**
     *
     * {@inheritDoc}
     * @see \RainCity\WPF\EnqueueScriptsInf::injectJavaScriptObject()
     */
    public function injectJavaScriptObject(string $objName, array $objValue)
    {
        wp_register_script(self::INLINE_SCRIPT_HANDLER, false, array(), $this->pluginVersion);

        $script = "var $objName = " . wp_json_encode( $objValue ) . ';';
        wp_add_inline_script(self::INLINE_SCRIPT_HANDLER, $script);
    }
}

