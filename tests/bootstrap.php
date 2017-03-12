<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__.'/class-wp-error.php';
require_once __DIR__ . '/MockObjects.php';

global $mockRestEndpoints;

$env = getenv('TEST_ENVIRONMENT');
define('TEST_ENVIRONMENT', $env ? $env : 'VAGRANT');

define('ABSPATH', __DIR__ . '/fixtures/var/');
define('WP_PLUGIN_DIR', __DIR__ . '/fixtures/var/wp-content/plugins');
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
define('WP_MAX_MEMORY_LIMIT', 128);
define('WP_MEMORY_LIMIT', 64);
define('WP_AUTO_UPDATE_CORE', true);

@mkdir(ABSPATH, 0777, true);
@mkdir(WP_PLUGIN_DIR, 0777, true);

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

function setUpWp()
{
    exec('rm -rf ' . ABSPATH . '*');

    $path = ABSPATH;

    switch (TEST_ENVIRONMENT) {
        case 'VAGRANT':
            _exec("rm -rf /vagrant/www/wordpress-test");
            _exec("ln -s $path /vagrant/www/wordpress-test");
            $url = 'http://test.devenv.local';
            $wp = "wp";
            break;
        case 'TRAVIS':
            $url = 'http://localhost:8080';
            $wp = "\$WP_CLI_BIN_DIR/wp";
            break;
        default:
            die("Unrecognized TEST_ENVIRONEMT " . TEST_ENVIRONMENT);
    }

    $configArgs =
        ' --dbname=wordpress-test' .
        ' --dbuser=wordpress' .
        ' --dbpass=wordpress' .
        ' --dbhost=localhost';

    $installArgs =
        " --url=$url" .
        " --title=test" .
        " --admin_user=admin" .
        " --admin_password=admin" .
        " --admin_email=admin@local.dev" .
        " --skip-email";

    // Set up WordPress
    _exec("$wp --path=$path core download");
    _exec("$wp --path=$path core config $configArgs");
    _exec("$wp --path=$path db reset --yes");
    _exec("$wp --path=$path core install $installArgs");

    // Inject our plugin
    $pluginPath = dirname(__DIR__);
    _exec("ln -s $pluginPath $path/wp-content/plugins");
    _exec("$wp --path=$path plugin activate integrity-checker");
}

function _exec($cmd)
{
    echo "$cmd\n";
    exec($cmd);
}
