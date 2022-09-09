<?php namespace RainCity\WPF;

interface WordPressPluginInf
{
    /**
     * Fetches the Options from the child class. Used to initialize or
     * cleanup options used by a plugin
     *
     * @return array    An array with entries for 'name' and 'initialValue'.
     *                  Can also be an array of these arrays if there is more
     *                  than one option being used by the plugin.
     */
    public static function getOptions();

    /**
     * Fetch array of database upgrades to perform.
     *
     * The array returned is an associative array where the key is a
     * version number and the value is a reference to a callable function.
     * The declaration order of the entries is not important. The functions
     * will be executed in version order.
     * <p>
     * Functions can be any callable function include global functions,
     * static class functions and class member functions. When using class
     * functions they must be public.
     *
     * @example
     * E.g. array (<br/>
     *     '1.0.3' => function() {//do work to upgrade to version 1.0.3},<br/>
     *     '1.0.5' => function() {//do work to upgrade to version 1.0.5},<br/>
     *     '1.0.1' => function() {//do work to upgrade to version 1.0.1}<br/>
     *     );
     *
     * @return array An associative array of database upgrade functions.
     */
    public function getDatabaseUpgrades(): array;
}
