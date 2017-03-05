<?php
namespace integrityChecker\Tests;

use integrityChecker\ApiClient;
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
	protected $session = null;

    /**
     * Singleton, ensure we have at most one instance of
     * each test type
     *
     * @param null $session
     *
     * @return mixed
     */
	static public function getInstance($session = null)
	{
		$class = get_called_class();
		if (!array_key_exists($class, self::$instances)) {
			self::$instances[$class] = new $class($session);
		}
		return self::$instances[$class];
	}


    /**
     * BaseTest constructor.
     *
     * @param null $session
     */
	public function __construct($session = null)
	{
		if ($session) {
			$this->session = $session;
			$this->transientState = get_transient( 'tt_teststate_' . $this->session);
			if (!$this->transientState) {
				$this->transientState = null;
			}

			$objState = new State();
			$state = $objState->getTestState($this->name);
			$this->started = $state->started;
		}

		$this->apiClient = new ApiClient();
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
        $this->started = time();
        $this->stateString = 'started';
        $payload = json_decode($request->get_body());
        $this->source = isset($payload->source) ? $payload->source : null;
        $this->sourceInstance = isset($payload->sourceInstance) ? $payload->sourceInstance: null;

        $state = new State();
        $state->updateTestState($this->name, $this->state());
    }

    /**
     * Store results and clean up after test
     */
    public function finish()
    {
        $this->finished = time();
        $this->stateString = 'finished';
        $state = new State();
        $state->updateTestState($this->name, $this->state());

        if ($this->transientState) {
	        if (is_array($this->transientState['result'])) {
		        $this->transientState['result']['ts'] = $this->finished;
	        }
            $state->storeTestResult($this->name,  $this->transientState['result']);
        }

        $this->transientState = false;

        delete_transient('tt_teststate_' . $this->session);
	    delete_transient('tt_teststarted_' . $this->session);

        do_action('integrity_checker_test_finished', $this->name, $this->state());

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
            $ret->session = $this->session;
            $bgProcess = new BackgroundProcess($this->session);
            $ret->jobCount = $bgProcess->jobCount();
	    }

	    return $ret;
    }

    public function storeTestResult($result)
    {
        $state = new State();
        $state->storeTestResult($this->name, $result);
    }

    protected function rglob($path='', $pattern='*', $flags = 0)
    {
	    $paths = glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
	    $files = glob($path.$pattern, $flags);
	    foreach ($paths as $path) {
		    $files = array_merge($files, $this->rglob($path, $pattern, $flags));
	    }
	    return $files;
    }

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
     * @param $dependency
     * @param $limit
     *
     * @return BackgroundProcess
     */
    protected function getBackgroundProcess($dependency, $request, $limit)
    {
        $objState = new State();
        $state = $objState->getTestState($dependency->name);

        if ($state->state == 'finished' && time() < $state->finished + $limit) {
            return new BackgroundProcess();
        }

        if ($state->state == 'started' && time() < $state->started + $limit) {
            return new BackgroundProcess($state->session);
        }

        // Else start the dependant test
        $dependency->start($request);
        return new BackgroundProcess($dependency->session);

    }
}