<?php
namespace RainCity\WPF;

/**
 *  Trait to define wp_die method so that when unit tests are running PHP
 *  won't actually die.
 *
 *  Utilize by adding 'use InterceptWpDie;' with the class definition and
 *  calling $this->wp_die() instead of wp_die().
 
 *  Relies on define('PHPUNIT_RUNNING').
 *
 *  @phpstan-ignore trait.unused
 */
trait InterceptWpDie
{
    use \RainCity\InterceptDie {
        \RainCity\InterceptDie::die as parentDie;
    }
    
    public function die(string $msg = '') {
        $this->parentDie($msg);
    }
    
    public function wp_die(string|\WP_Error $msg = '', string|int $title = '', string|array|int $args = array())
    {
        if (!defined('PHPUNIT_RUNNING') || !PHPUNIT_RUNNING) {
            wp_die($msg, $title, $args);
        }
    }
}
