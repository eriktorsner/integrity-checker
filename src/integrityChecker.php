<?php
namespace integrityChecker;

use integrityChecker\Admin\AdminPage;
use integrityChecker\Cron\CronExpression;

/**
 * Class integrityChecker
 * @package integrityChecker
 */
class integrityChecker
{
    /**
     * Unique identifier for this plugin.
     *
     * @since    1.0.0
     * @var      string
     */
    public $pluginSlug;

    /**
     * @var Settings
     */
    public $settings;

    /**
     * @var int
     */
    private $dbVersion = 1;

    /**
     * @var
     */
    private $adminUIHooks;

    /**
     * @var
     */
    private $adminPage;

    /**
     * Internal identifier for the cron event
     *
     * @var string
     */
    private $scheduledScanCron;

    /**
     * @var string
     */
    private $cronInvervalName;

    /**
     * @var BackgroundProcess
     */
    private $backgroundProcess;


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
        return INTEGRITY_CHECKER_VERSION;
    }

    /**
     * integrityChecker constructor.
     *
     * @param Settings          $settings
     * @param AdminUIHooks      $adminUIHooks
     * @param Admin\AdminPage   $adminPage
     * @param Rest              $rest
     * @param BackgroundProcess $backgroundProcess
     */
    public function __construct($settings, $adminUIHooks, $adminPage, $rest, $backgroundProcess)
    {
        $this->settings = $settings;
        $this->adminUIHooks = $adminUIHooks;
        $this->adminPage = $adminPage;
        $this->rest = $rest;
        $this->backgroundProcess = $backgroundProcess;

        $this->pluginSlug = $settings->slug;

        $this->scheduledScanCron = $this->pluginSlug . '_scheduled_scan';
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

            // Load plugin text domain
            $this->loadPluginTextdomain();
        }

        // ensure correct DB version
        $this->checkDbVersion();

        // Load the REST endpoints
        add_action('rest_api_init', array($this->rest, 'registerRestEndpoints'));
        add_action('rest_api_init', array($this->backgroundProcess, 'registerRestEndpoints'));

	    // Hook up Background processes to cron
        $this->backgroundProcess->registerCron();

        // Reuse the 5-min interval defined in bgProcess and make sure it's scheduled
        $this->cronInvervalName = $this->backgroundProcess->cronIntervalIdentifier;

        // Handler for the scheduled events
        add_action($this->scheduledScanCron, array($this, 'runScheduledScans'));

        // Handler for finished tests
        add_action("{$this->slug}_test_finished", array($this, 'finishedTest'), 10, 2);

        // Handler for
        add_filter("{$this->slug}_test_state", array($this, 'getTestState'), 10, 1);

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

            $adminObjects = array($this->adminPage, $this->adminUIHooks);
            foreach ($adminObjects as $obj) {
                $obj->register();
            }
        }
    }

    private function checkDbVersion()
    {
        $currentDbVersion = get_option($this->pluginSlug . '_dbversion', 0);
        if ($currentDbVersion != $this->dbVersion) {
            $this->createTables();
        }
    }

    public function createTables()
    {
        global $wpdb;
        @require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        $slug = str_replace('-', '_', $this->getPluginSlug());
        $tableName = $wpdb->prefix . $slug . '_files';

        $sql = "CREATE TABLE $tableName (
          id int(11) NOT NULL AUTO_INCREMENT,
          checkpoint tinyint(1) NOT NULL DEFAULT 0,
          name text NOT NULL,
          namehash char(32) NOT NULL,
          hash char(64) DEFAULT '',
          modified int(11) DEFAULT 0,
          isdir tinyint(1) NOT NULL DEFAULT 0,
          islink tinyint(1) NOT NULL DEFAULT 0,
          size bigint(20) NOT NULL DEFAULT 0,          
          mask smallint(5) NOT NULL DEFAULT 0,
          fileowner varchar(32) DEFAULT '',
          filegroup varchar(32) DEFAULT '',
          mime varchar(50) NOT NULL DEFAULT '',
          permissionsresult smallint(5) DEFAULT 0,
          status enum('','deleted') NOT NULL DEFAULT '',
          PRIMARY KEY (id),
          KEY namehash (namehash),
          KEY permissionsresult (permissionsresult),
        ) $charset_collate;";

        dbDelta($sql);

        update_option($this->pluginSlug . '_dbversion', $this->dbVersion);
    }

    /**
     * @param object $procStatus
     *
     * @return object
     */
    public function getTestState($procStatus)
    {
        if (isset($procStatus->session) && $procStatus->finished === 0) {
            $procStatus->jobCount = $this->backgroundProcess->jobCount($procStatus->session);
        }

        return $procStatus;
    }

    /**
     *
     */
    public function ensureScheduledTasks()
    {
        $reSchedule = false;
        $nextScheduled = wp_next_scheduled($this->scheduledScanCron);
        try {
            $cronExpr = CronExpression::factory($this->settings->cron);
            $next = $cronExpr->getNextRunDate();
            $nextTs = $next->getTimestamp();
            if ($nextScheduled != $nextTs) {
                $reSchedule = true;
            }
        } catch (\Exception $e) {

        }

        if ($reSchedule) {
            wp_clear_scheduled_hook($this->scheduledScanCron);
            wp_schedule_event($nextTs, $this->cronInvervalName, $this->scheduledScanCron);
        }
    }

    /**
     * Launch scheduled scans, if enabled.
     */
    public function runScheduledScans()
    {
        if (!$this->settings->enableScheduleScans) {
            return;
        }

        $schedulerSessionId = md5(microtime(true));
        $tests = array();

        if ($this->settings->scheduleScanChecksums) {
            $this->startScheudledRestProcess('checksum', $schedulerSessionId);
            $tests[] = 'checksum';
        }

        if ($this->settings->scheduleScanPermissions) {
            $this->startScheudledRestProcess('files', $schedulerSessionId);
            $tests[] = 'files';
        }

        if ($this->settings->scheduleScanSettings) {
            $this->startScheudledRestProcess('settings', $schedulerSessionId);
            $tests[] = 'settings';
        }

        update_option(
            'integrity_checker_scheduledrun',
            (object)array(
                'instance' => $schedulerSessionId,
                'tests' => $tests,
                'remainingTests' => $tests,
            )
        );

        $this->ensureScheduledTasks();
    }

    /**
     * Handle a finished scheduled scan.
     *
     * @param $testName
     * @param $state
     */
    public function finishedTest($testName, $state)
    {
        if (!isset($state->source) || $state->source !== 'scheduler') {
            return;
        }

        if (!isset($state->sourceInstance) || !$state->sourceInstance) {
            return;
        }

        wp_cache_delete("{$this->slug}_scheduledrun");
        $scheduledRun = get_option("{$this->slug}_scheduledrun", new \stdClass());
        if ($state->sourceInstance === $scheduledRun->instance) {
            if(($key = array_search($testName, $scheduledRun->remainingTests)) !== false) {
                unset($scheduledRun->remainingTests[$key]);
                update_option("{$this->slug}_scheduledrun", $scheduledRun);
            }
        }

        if (count($scheduledRun->remainingTests) == 0) {
            $this->finishedScheduledScans($scheduledRun);
        }
    }

    private function startScheudledRestProcess($process, $schedulerSession)
    {
        $url = get_rest_url() . 'integrity-checker/v1/process/status/' . $process;
        $args    =  array(
            'timeout'   => 0.01,
            'blocking'  => false,
            'sslverify' => apply_filters('https_local_ssl_verify', false),
            'headers' => array(
                'X-HTTP-Method-Override'=> 'PUT',
                'X-WP-NONCE' => wp_create_nonce('wp_rest'),
            ),
            'body' => json_encode(array(
                'state' => 'started',
                'source' => 'scheduler',
                'sourceInstance' => $schedulerSession,
            )),
        );
        wp_remote_post($url, $args);
    }

    private function finishedScheduledScans($scheduledRun)
    {
        // This is where we analyze and perhaps trigger an alert.
    }
}
