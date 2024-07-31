<?php
namespace RainCity\WPF\ShortCode;

interface ShortCodeRegInf
{
    /**
     * Register a short code.
     *
     * A class implementing this interface is expect to render a single short
     * code.
     *
     * @param ShortCodeImplInf $shortCode A class implementing a short code.
     */
    public function registerShortCode(ShortCodeImplInf $shortCode): void;
}
