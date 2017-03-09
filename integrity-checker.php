<?php
/**
 *
 * @author    Torgesta Technology <info@torgesta.com>
 * @license   GPL-2.0+
 *
 * @link      http://www.torgesta.com
 *
 * @copyright 2014 Torgesta Technology
 *
 * @wordpress-plugin
 * Plugin Name:       Integrity Checker
 * Plugin URI:        https://www.wpessentials.io/plugins/integrity-checker/
 * Description:       Check your WordPress installation for integrity issues, inconsistencies and potential security problems
 * Version:           0.9.3
 * Author:            Erik Torsner, Torgesta Technology AB
 * Author URI:        http://erik.torgesta.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       integrity-checker
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (! defined('ABSPATH')) {
    die;
}

iCheckBootstrap();

function iCheckBootstrap()
{
    $pluginVersion = '0.9.3';
	if (defined('DOING_AJAX') && DOING_AJAX) {
		return;
	}

    if (version_compare(PHP_VERSION, '5.3.9', '>=')) {
        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/RuntimeProvider.php';

        if (!defined('INTEGRITY_CHECKER_ROOT')) {
            define('INTEGRITY_CHECKER_ROOT', __DIR__);
        }

        if (!defined('INTEGRITY_CHECKER_VERSION')) {
            define('INTEGRITY_CHECKER_VERSION', $pluginVersion);
        }

        $app = new integrityChecker\Pimple\Container();
        $app->register(new RuntimeProvider());
        $iCheck = $app['interityChecker']; //new integrityChecker\integrityChecker(); //::getInstance();

        add_action('init', array($iCheck, 'init'));

    } else {
        register_activation_hook(__FILE__, 'icheck_php_version_too_low');
    }
}

if (!function_exists('icheck_php_version_too_low')) {
    function icheck_php_version_too_low()
    {
        die('The <strong>iCheck</strong> plugin requires PHP version 5.3.9 or greater.');
    }
}
