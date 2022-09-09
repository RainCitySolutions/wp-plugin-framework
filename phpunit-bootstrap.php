<?php

require_once __DIR__ . '/vendor/autoload.php';

/*
 * Try and determine if WordPress is or can be loaded
 */
/*
$wordpressDir = getenv ('WORDPRESS_DIR');

if (!isset($wordpressDir)) {
    if (isset($_SERVER['WORDPRESS_DIR'])) {
        $wordpressDir = $_SERVER['WORDPRESS_DIR'];
    }
    else {
        if (defined('WORDPRESS_DIR')) {
            $wordpressDir = WORDPRESS_DIR;
        }
        else {
            echo "WORDPRESS_DIR not defined\n";
        }
    }
}

if (isset($wordpressDir) && $wordpressDir !== FALSE) {
    $wpLoad = $wordpressDir . '/wp-load.php';
    require_once($wpLoad);
}
*/
?>