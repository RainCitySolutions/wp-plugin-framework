<?php
namespace RainCity\WPF;

interface EnqueueScriptsInf
{
    /**
     * Enqueue a script to be included in the web page
     *
     * @param string    $scriptPath     A relative path to the script, from
     *      the plugin base folder.
     * @param array     $dependencies   An array of script slugs this script
     *      id dependent on.
     */
    public function enqueueScript(string $scriptPath, array $dependencies): void;

    /**
     * Enqueue a script to be included in the web page
     *
     * @param string    $stylePath      A relative path to the css file, from
     *      the plugin base folder.
     * @param array     $dependencies   An array of script slugs this script
     *      id dependent on.
     */
    public function enqueueStyle(string $stylePath, array $dependencies): void;

    /**
     * Inject a JavaScript object to the page.
     *
     * @param string $objName   A name for the JavaScript object.
     * @param array  $objValue  An array to be assocated with the object.
     */
    public function injectJavaScriptObject(string $objName, array $objValue): void;
}
