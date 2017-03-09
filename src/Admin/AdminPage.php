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
     * @var array
     */
    private $tabs = array();

    /**
     * Constructor
     *
     * @param \integrityChecker\Settings $settings
     *
     * @since 1.0.0
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     *
     * Called by the main Plugin object to
     * allow us to run our hooks
     *
     */
    public function register()
    {
        $this->title = __('Integrity Checker Options', 'integrity-checker');
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
                new OverviewTab($this->settings),
                new ChecksumScanTab($this->settings),
                new FilesScanTab($this->settings),
                new SettingsScanTab($this->settings),
                new SettingsTab($this->settings),
                new AboutTab($this->settings),
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
	 * Enqueue admin scripts if it's our page
	 *
	 * @param string $arg
	 */
    public function enqueueAdminScripts($arg)
    {
        if ($arg != 'tools_page_' . $this->key) {
            return;
        }

        wp_enqueue_script(
            $this->settings->slug . '-main',
            plugins_url() . '/' . $this->settings->slug . '/js/main.js',
            array('jquery', 'backbone','wp-util', 'jquery-ui-dialog'),
            INTEGRITY_CHECKER_VERSION,
            true
        );

        wp_localize_script(
            $this->settings->slug . '-main',
            'integrityCheckerApi',
            array(
                'url'    => esc_url_raw(rtrim(rest_url(), '/')),
                'nonce' => wp_create_nonce('wp_rest'),
            )
        );

        wp_enqueue_script(
            'jqCron',
            plugins_url() . '/' . $this->settings->slug . '/js/jqCron.js',
            array('jquery', $this->settings->slug . '-main'),
            INTEGRITY_CHECKER_VERSION,
            true
        );

        wp_enqueue_style(
            $this->settings->slug . '-maincss',
            plugins_url() . '/' . $this->settings->slug . '/css/style.css',
            array(),
            INTEGRITY_CHECKER_VERSION
        );

        wp_enqueue_style(
            'jqCron',
            plugins_url() . '/' . $this->settings->slug . '/css/jqCron.css',
            array(),
            INTEGRITY_CHECKER_VERSION
        );


        wp_enqueue_style(
            $this->settings->slug . '-fa',
            plugins_url() . '/' . $this->settings->slug . '/css/font-awesome.min.css',
            array(),
            INTEGRITY_CHECKER_VERSION
        );

        wp_enqueue_style('wp-jquery-ui-dialog');

        foreach ($this->tabs as $tab) {
            foreach ($tab->getScripts() as $script) {
                wp_enqueue_script(
                    $this->settings->slug . $script['id'],
                    plugins_url() . '/' . $this->settings->slug . $script['file'],
                    $script['deps'],
                    INTEGRITY_CHECKER_VERSION,
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