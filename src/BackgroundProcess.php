<?php
namespace integrityChecker;

/**
 * Class BackgroundProcess
 */
class BackgroundProcess
{
	/**
	 * @var int
	 */
	protected $memoryLimit = 128;

	/**
	 * @var int
	 */
	protected $timeLimit = 15;

	/**
	 * @var string
	 */
	protected $prefix = 'tt_bgprocess';

	/**
	 *
	 * @var int
	 */
	private $startTime = 0;

	/**
	 * The unique id of the current process queue/session
	 *
	 * @var string
	 */
	public $session;

	/**
	 * Our cron hook identifier
	 *
	 * @var string
	 */
	protected $cronHookIdentifier;

	/**
	 * Our custom cron interval. Defaults to 5 minutes
	 *
	 * @var string
	 */
	public $cronIntervalIdentifier;

    /**
     * @var Tests\TestFactory
     */
    private $testFactory;


	/**
	 * BackgroundProcess constructor.
	 *
     * @param Tests\TestFactory $testFactory
	 */
	public function __construct($testFactory)
	{
        $this->testFactory = $testFactory;
		$this->cronHookIdentifier     = $this->prefix . '_cron';
		$this->cronIntervalIdentifier = $this->prefix . '_cron_interval';
	}

	public function init($session = null)
    {
        if ($session) {
            $this->session = $session;
        } else {
            $this->session = md5( microtime() . NONCE_SALT);
        }
    }

    /**
     * Regsiter WordPress REST endpoints
     */
    public function registerRestEndPoints()
    {
        $rest = $this;

        register_rest_route('integrity-checker/v1', 'background/(?P<session>[a-zA-Z0-9-]+)', array(
            'methods' => array('GET'),
            'callback' => function($request) use($rest){
                $session = $request->get_param('session');
                $rest->init($session);
                $rest->process();
                return null;
            }
        ));
    }

    /**
     * @param int $memoryLimit
     */
    public function setMemoryLimit($memoryLimit)
    {
        $this->memoryLimit = $memoryLimit;
    }

    /**
     * @param int $timeLimit
     */
    public function setTimeLimit($timeLimit)
    {
        $this->timeLimit = $timeLimit;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

	/**
	 * Make sure we catch stalled or failed processes via cron
	 */
	public function registerActions()
	{
        // Add our shutdown handler
        add_action( 'shutdown', array($this, 'onShutdown'));

        // Hook into cron
		add_action($this->cronHookIdentifier, array($this, 'handleCronHealthCheck'));
		add_filter('cron_schedules', array($this, 'scheduleCronHealthCheck'));

        // register Rest endpoints when needed
        add_action('rest_api_init', array($this, 'registerRestEndpoints'));
	}


    /**
     * Process the next jobs in queue and fire next
     * REST call if needed
     *
     * @param bool $yield Don't process any queued jobs, just fire next REST call
     *
     */
	public function process()
	{
		if ($this->isRunning()) {
			return;
		}

        $queueTransientName = $this->transientName('queue');
        $jobs = $this->getQueue($queueTransientName);

        $lockTransientName = $this->transientName('lock');
        set_transient($lockTransientName, time());

        // Make sure there's a cron job scheduled to check
        // the overall health every 5 minutes
        $this->ensureScheduled();

        $this->startTime = time();
        $obj             = null;

        $done = (count($jobs) == 0 || $this->timeExceeded() || $this->memoryExceeded());
        while ( ! $done) {

            $job = array_shift($jobs);
            set_transient($queueTransientName, $jobs);

            $obj = $this->dispatch($job);

            $jobs = $this->getQueue($queueTransientName);

            // check for empty queue, time or memory constraints
            $done = (count($jobs) == 0 || $this->timeExceeded() || $this->memoryExceeded());
        }

        $this->doneRunning($obj);

		if (count($jobs) == 0) {
			delete_transient($queueTransientName);
		}
	}

    /**
     * When the WordPress request is all but done. We'll check
     * for jobs to process in a new request
     */
	public function onShutdown()
    {
        if (is_null($this->session)) {
            return;
        }
        $queueTransientName = $this->transientName('queue');
        $jobs = $this->getQueue($queueTransientName);

        if (count($jobs) > 0) {
            $this->newRequest();
        }
    }

	/**
	 * Launch a new async request
	 *
	 * @param string|null $session
	 */
	private function newRequest($session = null)
	{
		$session = $session ? $session : $this->session;
		$url     = get_rest_url() . 'integrity-checker/v1/background/' . $session;
		$args    =  array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => apply_filters('https_local_ssl_verify', false),
		);

		wp_remote_get($url, $args);
	}


	/**
	 * @return bool
	 */
	private function memoryExceeded()
	{
		$currentMemory = memory_get_usage(true) / 1024 / 1024;
		return $currentMemory >= $this->memoryLimit;
	}

	/**
	 * @return bool
	 */
	private function timeExceeded()
	{
		return time() >= ($this->startTime + $this->timeLimit);
	}

	/**
	 * @param $job
	 *
	 * @return mixed
	 */
	private function dispatch($job)
	{
		$class = $job->class;
		$method = $job->method;
        $obj = $this->testFactory->getTestObject($class);
        $obj->setBackgroundProcess($this);
        $obj->init($this->session);
		$obj->$method($job);

		return $obj;
	}

	/**
	 * Add a job to the queue with an optional priority
	 *
	 * @param object $job
	 * @param integer $priority
	 */
	public function addJob($job, $priority = 10)
	{
		$queueTransientName = $this->transientName('queue');
		$jobs = $this->getQueue($queueTransientName);

		if (!isset($job->priority)) {
			$job->priority = $priority;
		}

		$jobs[] = $job;
		set_transient($queueTransientName, $jobs);
	}

	/**
	 * Add jobs in bulk
	 *
	 * @param array $newJobs
	 * @param integer $priority
	 */
	public function addJobs($newJobs, $priority = 10)
	{
		$queueTransientName = $this->transientName('queue');
		$jobs = $this->getQueue($queueTransientName);

		foreach ($newJobs as $job) {

			if (!isset($job->priority)) {
				$job->priority = $priority;
			}
			$jobs[] = $job;
		}

		set_transient($queueTransientName, $jobs);
	}

	/**
	 * @param string $queueTransientName
	 *
	 * @return array The queue
	 */
	private function getQueue($queueTransientName)
	{
		$jobs = get_transient($queueTransientName);
		if (!$jobs) {
			$jobs = array();
		}

		usort($jobs, function($a, $b) {
			return $a->priority - $b->priority;
		});

		return $jobs;
	}

	/**
	 * Check if there is already a process working on this
	 * queue. If the queue is locked but the lock has not been touched
	 * in a while we assume that the process died.
	 *
	 * @return bool
	 */
	private function isRunning()
	{
		$staleLimit = $this->timeLimit * 2;
		$lockTransientName = $this->transientName('lock');
		$lock = get_transient($lockTransientName);
		if ($lock && ($lock + $staleLimit) > time()) {
			return true;
		}

		return false;
	}

	/**
	 * Called whenever a process stops managing a queue. Could
	 * be because the queue is empty or that the time and/or memory
	 * limits were exceeded.
	 *
	 * @param Tests\BaseTest $obj
	 */
	private function doneRunning($obj)
	{
		if ($obj) {
			$obj->serializeState();
		}

		$lockTransientName = $this->transientName('lock');
		delete_transient($lockTransientName);
	}

	/**
	 * @param string $name
     * @param string $session (optional)
	 *
	 * @return string
	 */
	private function transientName($name, $session = null)
	{
        $session = $session ? $session : $this->session;
		return $this->prefix . '_' . $name . '_' . $session;
	}

	/**
	 * Ensure there's a 5 minute cron interval
	 * in this WP install
	 *
	 * @param array $schedules
	 *
	 * @return mixed
	 */
	public function scheduleCronHealthCheck($schedules)
	{
		// Adds every 5 minutes to the existing schedules.
		$schedules[$this->cronIntervalIdentifier] = array(
			'interval' => MINUTE_IN_SECONDS * 5,
			'display'  => sprintf( __( 'Every %d Minutes', 'integrity-checker'), 5),
		);
		return $schedules;
	}

	/**
	 * Look for processes that might have gone bad and
	 * needs to be restarted or dropped
	 */
	public function handleCronHealthCheck()
	{
		$staleLimit = $this->timeLimit * 2;

		// look if there are any stale processes
		$transients = $this->getAllTransients('lock');
		foreach ($transients as $transient) {

			// If the process lock is too young, skip
			if ($transient['value'] + $staleLimit > time()) {
				continue;
			}

			// Launch an async worker to deal with this queue
			$this->newRequest($transient['session']);

		}

		if (count($transients) == 0) {
			$this->clearScheduledEvents();
		}
	}

    /**
     * Get the priority of the last queued item
     *
     * @return int
     */
	public function lastQueuePriority()
    {
        $queueTransientName = $this->transientName('queue');
        $jobs = $this->getQueue($queueTransientName);
        $lastJob = end($jobs);
        if ($lastJob) {
            return $lastJob->priority;
        }

        return 0;
    }

    /**
     * Get the length of the current queue
     *
     * @return int
     */
    public function jobCount($session = null)
    {
        $queueTransientName = $this->transientName('queue', $session);
        $jobs = $this->getQueue($queueTransientName);

        return count($jobs);
    }

	/**
	 * Make sure that there's a scheduled cron event that takes
	 * care of any failed processes/queues
	 */
	private function ensureScheduled()
	{
		if (!wp_next_scheduled($this->cronHookIdentifier)) {
			wp_schedule_event(time(), $this->cronIntervalIdentifier, $this->cronHookIdentifier);
		}
	}

	/**
	 * Remove any scheduled events
	 */
	private function clearScheduledEvents()
	{
		$timestamp = wp_next_scheduled($this->cronHookIdentifier);
		if ($timestamp) {
			wp_unschedule_event($timestamp, $this->cronHookIdentifier);
		}
	}

	/**
	 * Get all transients of a specific type created by us,
	 * regardless of the unique session identifier.
	 *
	 * @param string $name The transient type/name to get
	 *
	 * @return array
	 */
	private function getAllTransients($name)
	{
		global $wpdb;
		$sql = "SELECT `option_name` AS `name`, `option_value` AS `value`
            FROM  $wpdb->options
            WHERE `option_name` LIKE '%transient_{$this->prefix}_{$name}%'
            ORDER BY `option_name`";

		$results = $wpdb->get_results($sql);
		$transients = array();
		foreach ($results as $result) {
			$parts = explode('_', $result->name);
			$transient = array(
				'dbname' => $result->name,
				'name' => $name,
				'session' => $parts[count($parts) - 1],
				'value'  => $result->value,
			);

			$transients[] = $transient;
		}

		return $transients;
	}
}
