<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__.'/class-wp-error.php';
require_once __DIR__ . '/MockObjects.php';

global $mockRestEndpoints;

// Call the bootstrap method of WP Mock
define('ABSPATH', __DIR__ . '/fixtures/var/');
define('WP_PLUGIN_DIR', __DIR__ . '/fixtures/var/wp-content/plugins');
@mkdir(ABSPATH, 0777, true);
@mkdir(WP_PLUGIN_DIR, 0777, true);

define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
define('WP_MAX_MEMORY_LIMIT', 128);
define('WP_MEMORY_LIMIT', 64);
define('WP_AUTO_UPDATE_CORE', true);

WP_Mock::bootstrap();
define('INTEGRITY_CHECKER_ROOT', dirname(__DIR__));
define('INTEGRITY_CHECKER_VERSION', '0.9.3');
define('NONCE_SALT', 'h T/HR6HE{-wV-a!>$_5k,6;&L.Q-JGP;6mDU9vJ:{iD9qBo+hb}O)#]OkPP`Mei');

function __($str, $context)
{
    return $str;
}

function is_wp_error( $thing ) {
    return ( $thing instanceof WP_Error );
}

function register_rest_route($base, $endPoint, $args)
{
    global $mockRestEndpoints;

    $method = $args['methods'][0];

    $mockRestEndpoints = $mockRestEndpoints ? $mockRestEndpoints : array();
    $mockRestEndpoints[$base] = isset($mockRestEndpoints[$base]) ? $mockRestEndpoints[$base] : array();
    $mockRestEndpoints[$base][$method] = isset($mockRestEndpoints[$base][$method]) ?
        $mockRestEndpoints[$base][$method] :
        array();

    $mockRestEndpoints[$base][$endPoint][$method] = $args;

}