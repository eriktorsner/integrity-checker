<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/MockObjects.php';

global $mockRestEndpoints, $testUrl;

// assume we're running under Vagrant:
$testUrl = 'http://test.devenv.local';

$env = getenv('TEST_ENVIRONMENT');
define('TEST_ENVIRONMENT', $env ? $env : 'VAGRANT');

define('ABSPATH', __DIR__ . '/fixtures/var/');
define('WPINC', 'wp-includes');
define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
define('WP_PLUGIN_DIR', __DIR__ . '/fixtures/var/wp-content/plugins');
define('WP_LANG_DIR', ABSPATH . 'wp-content/languages');

define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
define('WP_MAX_MEMORY_LIMIT', 128);
define('WP_MEMORY_LIMIT', 64);
define('WP_AUTO_UPDATE_CORE', true);
define('WP_DEBUG', false);

@mkdir(ABSPATH, 0777, true);
@mkdir(WP_PLUGIN_DIR, 0777, true);

define('INTEGRITY_CHECKER_ROOT', dirname(__DIR__));
define('INTEGRITY_CHECKER_VERSION', '0.9.3');
define('NONCE_SALT', 'h T/HR6HE{-wV-a!>$_5k,6;&L.Q-JGP;6mDU9vJ:{iD9qBo+hb}O)#]OkPP`Mei');

function setUpWp($downloadOnly = false)
{
    global $testUrl;

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

    $testUrl = $url;

    $configArgs =
        ' --dbname=wordpress-test' .
        ' --dbuser=wordpress' .
        ' --dbpass=wordpress' .
        ' --dbhost=localhost' .
        " --extra-php <<PHP\ndefine('INTEGRITY_CHECKER_NO_REST_AUTH',true);\nPHP";

    $installArgs =
        " --url=$url" .
        " --title=test" .
        " --admin_user=admin" .
        " --admin_password=admin" .
        " --admin_email=admin@local.dev" .
        " --skip-email";

    // Download WordPress
    _exec("$wp --path=$path core download");
    if ($downloadOnly) {
        return;
    }

    // Configure and setup
    _exec("$wp --path=$path core config $configArgs");
    _exec("$wp --path=$path db reset --yes");
    _exec("$wp --path=$path core install $installArgs");

    // Inject our plugin
    $pluginPath = dirname(__DIR__);
    _exec("ln -s $pluginPath {$path}wp-content/plugins");
    _exec("$wp --path=$path plugin activate integrity-checker");

    // Ignore our plugin when doing checksum stuff
    $ignore = json_encode(array("plugins" => array("integrity-checker/integrity-checker.php")));
    $ignore = escapeshellarg($ignore);
    _exec("$wp --path=$path option update integrity-checker_checksum_ignore $ignore --format=json");
    _exec("$wp --path=$path option update wp_checksum_apikey YWIyZGIxZjc6N2QxMzZmNDMyNA==");
}

function _exec($cmd)
{
    //echo "$cmd\n";
    exec($cmd);
}

function callPrivate($obj, $name, $args)
{
    $reflection = new \ReflectionClass(get_class($obj));
    $method = $reflection->getMethod($name);
    $method->setAccessible(true);
    return $method->invoke($obj, $args);
}
