<?php
namespace integrityChecker\Admin;

use \integrityChecker\integrityChecker;

/**
 * Integrity Checker main admin page
 *
 * @version 1.0.0
 */
class AdminPage {

    /**
     * Option key, and option page slug
     * @var string
     */
    private $key = 'integrity-checker_options';

    /**
     * Holds an instance of the object
     *
     * @var AdminPage
     */
    private static $instance = null;

    /**
     * @var array
     */
    private $tabs = array();


    /**
     * Constructor
     * @since 1.0.0
     */
    public function __construct()
    {
        self::$instance = $this;
        $this->title = __('Integrity Checker Options', 'integrity-checker');
    }

    /**
     *
     * Called by the main Plugin object to
     * allow us to run our hooks
     *
     */
    public function register()
    {
        add_action('admin_init', array($this, 'init'));
        add_action('admin_menu', array($this, 'adminMenu'));
    }


    /**
     * Register our setting to WP
     * @since 1.0.0
     */
    public function init() {
        add_action("admin_enqueue_scripts", array($this, 'enqueueAdminScripts'), 10, 1);

        if (isset($_REQUEST['page']) && $_REQUEST['page'] == $this->key) {
            $this->tabs = array(
                new OverviewTab($this),
                new ChecksumScanTab($this),
                new FilesScanTab($this),
                new SettingsScanTab($this),
                new SettingsTab($this),
                new AboutTab($this),
            );
        }
    }

    /**
     * Add link to the Tools menu
     */
    public function adminMenu()
    {
        add_submenu_page(
            'tools.php',
            __('Integrity Checker', 'integrity-checker'),
            __('Integrity Checker', 'integrity-checker'),
            'manage_options',
            $this->key,
            array($this, 'renderAdminPage')
        );
    }

    /**
     * Returns the running object
     *
     * @return AdminPage
     */
    public static function get_instance()
    {
        return self::$instance;
    }


	/**
	 * Enqueue admin scripts if it's our page
	 *
	 * @param string $arg
	 */
    public function enqueueAdminScripts($arg)
    {
        if ($arg != 'tools_page_' . $this->key) {
            return;
        }

        $plugin = integrityChecker::getInstance();

        wp_enqueue_script(
            $plugin->getPluginSlug() . '-main',
            plugins_url() . '/' . $plugin->getPluginSlug() . '/js/main.js',
            array('jquery', 'backbone','wp-util', 'jquery-ui-dialog'),
            $plugin->getVersion(),
            true
        );

        wp_localize_script(
            $plugin->getPluginSlug() . '-main',
            'integrityCheckerApi',
            array(
                'url'    => esc_url_raw(rtrim(rest_url(), '/')),
                'nonce' => wp_create_nonce('wp_rest'),
            )
        );

        wp_enqueue_script(
            'jqCron',
            plugins_url() . '/' . $plugin->getPluginSlug() . '/js/jqCron.js',
            array('jquery', $plugin->getPluginSlug() . '-main'),
            $plugin->getVersion(),
            true
        );

        wp_enqueue_style(
            $plugin->getPluginSlug() . '-maincss',
            plugins_url() . '/' . $plugin->getPluginSlug() . '/css/style.css',
            array(),
            $plugin->getVersion()
        );

        wp_enqueue_style(
            'jqCron',
            plugins_url() . '/' . $plugin->getPluginSlug() . '/css/jqCron.css',
            array(),
            $plugin->getVersion()
        );


        wp_enqueue_style(
            $plugin->getPluginSlug() . '-fa',
            plugins_url() . '/' . $plugin->getPluginSlug() . '/css/font-awesome.min.css',
            array(),
            $plugin->getVersion()
        );

        wp_enqueue_style('wp-jquery-ui-dialog');

        foreach ($this->tabs as $tab) {
            foreach ($tab->getScripts() as $script) {
                wp_enqueue_script(
                    $plugin->getPluginSlug() . $script['id'],
                    plugins_url() . '/' . $plugin->getPluginSlug() . $script['file'],
                    $script['deps'],
                    $plugin->getVersion(),
                    true
                );
            }
        }
    }


    /**
     * Render the actual page
     */
    public function renderAdminPage()
    {
        $activeTab = reset($this->tabs);

        if (isset($_REQUEST['tab'])) {
            foreach ($this->tabs as $tab) {
                if ($_REQUEST['tab'] == $tab->tabId) {
                    $activeTab = $tab;
                }
            }
        }
        $activeTab->active = true;

        include __DIR__ . '/views/AdminPage.php';
    }
}