<?php
namespace integrityChecker\Tests;

use integrityChecker\ApiClient;
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
	 * @var array
	 */
	public $transientState = array();

    /**
     * Instance of the wpessentials.io client
     *
     * @var object
     */
    protected $apiClient;

	/**
	 * @var string
	 */
	protected $session = null;

	static public function getInstance($session = null)
	{
		$class = get_called_class();
		if (!array_key_exists($class, self::$instances)) {
			self::$instances[$class] = new $class($session);
		}
		return self::$instances[$class];
	}


	public function __construct($session = null)
	{
		if ($session) {
			$this->session = $session;
			$this->transientState = get_transient( 'tt_teststate_' . $this->session);
			if (!$this->transientState) {
				$this->transientState = array();
			}

			$objState = new State();
			$state = $objState->getTestState($this->name);
			$this->started = $state->started;
		}

		$this->apiClient = new ApiClient();
	}

	public function serializeState()
	{
		if ($this->transientState) {
			set_transient('tt_teststate_' . $this->session, $this->transientState);
		}
	}


    public function start($request)
    {
        $this->started = time();
        $this->stateString = 'started';
        $state = new State();
        $state->updateTestState($this->name, $this->state());
    }

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
        );

	    if ($ret->finished === 0) {
		    $ret->session = $this->session;
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
}