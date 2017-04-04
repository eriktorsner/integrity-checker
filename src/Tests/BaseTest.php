<?php
namespace integrityChecker\Tests;

use integrityChecker\BackgroundProcess;
use integrityChecker\State;

class BaseTest
{
	/**
	 * @var array
	 */
	static protected $instances = array();

	/**
     * @var int
     */
    public $started = 0;

    /**
     * @var int
     */
    public $finished = 0;

    /**
     * @var string
     */
    public $stateString = '';

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var \stdClass
     */
    public $result = null;

	/**
	 * @var mixed
	 */
	public $transientState;

    /**
     * Instance of the wpessentials.io client
     *
     * @var object
     */
    protected $apiClient;

    /**
     * Source name, if this test was started by a specific
     * source (scheduler)
     *
     * @var string
     */
    private $source;

    /**
     * Id of the source instance
     *
     * @var string
     */
    private $sourceInstance;

	/**
	 * @var string
	 */
	public $session = null;

    /**
     * @var BackgroundProcess
     */
    protected $backgroundProcess = null;

    /**
     * @var \integrityChecker\Settings
     */
    protected $settings;

    /**
     * @var TestFactory
     */
    protected $testFactory;

    /**
     * @var State
     */
    protected $state;


    /**
     * BaseTest constructor.
     *
     * @param \integrityChecker\Settings            $settings
     * @param \integrityChecker\State               $state
     * @param \integrityChecker\ApiClient           $apiClient
     * @param \integrityChecker\TestFactory         $testFactory
     */
	public function __construct($settings, $state, $apiClient, $testFactory)
	{
        $this->settings = $settings;
        $this->state = $state;
		$this->apiClient = $apiClient;
        $this->testFactory = $testFactory;
	}

    /**
     * @param BackgroundProcess $backgroundProcess
     */
    public function setBackgroundProcess($backgroundProcess)
    {
        $this->backgroundProcess = $backgroundProcess;
    }

    /**
     * @param $session
     */
	public function init($session)
    {
        $this->session = $session;

        // read transient state from database
        if ($this->session && !$this->transientState) {
            $this->transientState = get_transient('tt_teststate_' . $this->session);
            // ensure transient state is null if get_transient returned false
            if (!$this->transientState) {
                $this->transientState = null;
            }

            $testState = $this->state->getTestState($this->name);
            $this->started = $testState->started;
        }
    }

    /**
     * Store temporary state
     */
	public function serializeState()
	{
		if ($this->transientState) {
			set_transient('tt_teststate_' . $this->session, $this->transientState);
		}
	}

    /**
     * Called when test results are requested via REST
     * Gives each subclass a chance to add data to the
     * stored test results
     *
     * @param object $result
     *
     * @return object
     */
    public function getRestResults($result)
    {
        return $result;
    }

    /**
     * Start a new test
     *
     * @param $request
     */
    public function start($request)
    {
        $this->session = $this->backgroundProcess->session;
        $this->started = time();
        $this->stateString = 'started';
        $payload = json_decode($request->get_body());
        $this->source = isset($payload->source) ? $payload->source : null;
        $this->sourceInstance = isset($payload->sourceInstance) ? $payload->sourceInstance: null;

        $this->state->updateTestState($this->name, $this->state());
    }

    /**
     * Store results and clean up after test
     */
    public function finish()
    {
        $this->finished = time();
        $this->stateString = 'finished';
        $this->state->updateTestState($this->name, $this->state());

        if ($this->transientState) {
	        if (is_array($this->transientState['result'])) {
		        $this->transientState['result']['ts'] = $this->finished;
	        }
            $this->state->storeTestResult($this->name,  $this->transientState['result']);
        }

        $this->transientState = false;

        delete_transient('tt_teststate_' . $this->session);

        do_action("{$this->settings->slug}_test_finished", $this->name, $this->state());

    }

	/**
	 * Return the current state of this test
	 *
	 * @return object
	 */
    public function state()
    {
        $ret =  (object)array(
            'state' => $this->stateString,
            'started' => $this->started,
            'finished' => $this->finished,
            'source' => $this->source,
            'sourceInstance' => $this->sourceInstance,
        );

	    if ($ret->finished === 0) {
            $ret->jobCount = $this->backgroundProcess->jobCount();
            $ret->session = $this->session;
	    }

	    return $ret;
    }

    /**
     * Store results using State class
     * @param $result
     */
    public function storeTestResult($result)
    {
        $this->state->storeTestResult($this->name, $result);
    }

    /**
     * Recursive glob
     *
     * @param string  $path
     * @param string  $pattern
     * @param int     $flags
     * @param boolean $followSymlinks
     * @param array   $links
     *
     * @return array
     */
    protected function rglob($path = '', $pattern = '*', $flags = 0, $followSymlinks, $links = array())
    {
        $paths = glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
        $files = glob($path.$pattern, $flags);

        foreach ($paths as $path) {
            $trimmed = rtrim($path, '/');
            if (is_link($trimmed)) {
                if (!$followSymlinks) {
                    // remove it from the files array
                    if(($key = array_search($path, $files)) !== false) {
                        unset($files[$key]);
                    }
                    // and don't recurse into any sub folders.
                    continue;
                }

                $link = readlink($trimmed);
                if (in_array($link, $links)) {
                    continue;
                }
                $links[] = $link;
            }
            $files = array_merge($files, $this->rglob($path, $pattern, $flags, $followSymlinks, $links));
        }
        return $files;
    }

    /**
     * Impersonate an administrator
     */
    protected function impersonateAdmin()
    {

	    $adminUsers = get_users(array(
	    	'role' => 'Administrator',
	    ));

	    if (count($adminUsers) > 0) {
		    $admin = reset($adminUsers);
		    wp_set_current_user($admin->data->ID, $admin->data->user_login);
	    }
    }

    /**
     * Start a new background process, or latch on to an existing
     *
     * @param $dependency
     * @param $limit
     *
     * @return BackgroundProcess
     */
    protected function initBackgroundProcess($dependency, $request, $limit)
    {
        // Did we even get a depednecy
        if (is_null($dependency)) {
            $this->backgroundProcess->init();
            return;
        }

        // If the dependency finished recently, don't rerun it
        $state = $this->state->getTestState($dependency->name);
        if ($state->state == 'finished' && time() < $state->finished + $limit) {
            $this->backgroundProcess->init();
            return;
        }

        // If the dependency is already running, latch on to the existing session
        if ($state->state == 'started' && time() < $state->started + $limit) {
            $this->backgroundProcess->init($state->session);
            return;
        }

        // Else start the dependent test, the backgroundProcess gets a session
        // id as a side effect
        $dependency->start($request);
    }

    /**
     * @return string
     */
    protected function getTableName()
    {
        global $wpdb;

        $dbSlug = str_replace('-', '_', $this->settings->slug);
        return $wpdb->prefix . $dbSlug. '_files';
    }
}