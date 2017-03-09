<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__.'/class-wp-error.php';
require_once __DIR__ . '/MockObjects.php';

// Call the bootstrap method of WP Mock
define('ABSPATH', __DIR__ . '/fixtures/var/');

WP_Mock::bootstrap();
define('INTEGRITY_CHECKER_ROOT', dirname(__DIR__));
define('INTEGRITY_CHECKER_VERSION', '0.9.3');

function __($str, $context)
{
    return $str;
}

function is_wp_error( $thing ) {
    return ( $thing instanceof WP_Error );
}