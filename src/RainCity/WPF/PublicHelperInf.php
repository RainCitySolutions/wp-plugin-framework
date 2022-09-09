<?php
namespace RainCity\WPF;

/**
 * The public-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-specific stylesheet and JavaScript.
 */
interface PublicHelperInf
{
    /**
     * Register the scripts and stylesheets for the public area.
     *
     * @since    1.0.0
     */
    public function onEnqueueStyles();
}
