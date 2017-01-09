<?php
namespace integrityChecker;

use integrityChecker\Admin\AdminPage;


/**
 * Class integrityChecker
 * @package integrityChecker
 */
class integrityChecker
{
    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since   1.0.0
     * @var     string
     */
    const VERSION = '1.0.0';

    /**
     * Unique identifier for this plugin.
     *
     * @since    1.0.0
     * @var      string
     */
    public $pluginSlug = 'integrity-checker';

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      integrityChecker
     */
    protected static $instance = null;

    /**
     * Return the plugin slug.
     *
     * @since    1.0.0
     *
     * @return  string  Plugin slug variable.
     */
    public function getPluginSlug()
    {
        return $this->pluginSlug;
    }

    /**
     * Return the plugin version
     *
     * @since   1.0.0
     *
     * @return string
     */
    public function getVersion()
    {
        return self::VERSION;
    }

    /**
     * All integrityChecker owned tests
     *
     * @var array
     */
    private $testNames = array();

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    integrityChecker  A single instance of this class.
     */
    public static function getInstance()
    {
        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * integrityChecker constructor.
     */
    public function __construct()
    {
        $this->testNames = array(
            'checksum', 'permissions', 'settings',
        );
    }

    /**
     *
     */
    public function getTestNames()
    {
        return apply_filters('integrity-checker_get_test_names', $this->testNames);
    }

    /**
     * Initialize the plugin, setting localization and loading public scripts etc
     * and styles.
     *
     * @since     1.0.0
     */
    public function init()
    {

        if (is_admin())
        {
            // Allow our own classes to register admin page etc.
            $this->registerClasses();
        }

        // Load plugin text domain
        $this->loadPluginTextdomain();

        // Load the REST endpoints
        $rest = new Rest();

	    // Hook up Background processes to cron
	    $bgProgress = new BackgroundProcess();
	    $bgProgress->registerCron();

    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function loadPluginTextdomain()
    {
        $domain = $this->pluginSlug;
        load_plugin_textdomain($domain, false, INTEGRITY_CHECKER_ROOT . '/languages/' );
    }

	/**
	 * Initiate classes and libraries
	 */
    private function registerClasses()
    {
        if (is_admin()) {

            $adminObjects = array(new AdminPage(),);
            foreach ($adminObjects as $obj) {
                $obj->register();
            }
        }
    }
}
