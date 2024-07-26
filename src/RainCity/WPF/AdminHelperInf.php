<?php
namespace RainCity\WPF;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 */
interface AdminHelperInf
{
    public function addSettingsMenu(): void;
    public function addSettings(): void;

    public function onAdminEnqueueScripts(): void;
}
