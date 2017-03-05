<?php
namespace integrityChecker;

/**
 * Class State
 * @package integrityChecker
 */
class State
{
    /**
     * @var string
     */
    private $slug;

    /**
     * State constructor.
     */
    public function __construct()
    {
        $plugin = integrityChecker::getInstance();
        $this->slug = $plugin->getPluginSlug();
    }

    /**
     * @param $testName
     * @return array|mixed|void
     */
    public function getTestState($testName)
    {
        $procStatus = get_option("{$this->slug}_status_$testName", false);
        if (!$procStatus) {
            $procStatus = $this->noState();
        }
        $procStatus->startedIso = date('Y-m-d H:i:s', $procStatus->started);
        $procStatus->finishedIso = date('Y-m-d H:i:s', $procStatus->finished);

        if (isset($procStatus->session)) {
            $bgProcess = new BackgroundProcess($procStatus->session);
            $procStatus->jobCount = $bgProcess->jobCount();
        }

        return $procStatus;
    }

    /**
     * Store state in wp_options
     *
     * @param string        $testName
     * @param array|object  $state
     */
    public function updateTestState($testName, $state)
    {
        update_option("{$this->slug}_status_$testName", $state);
    }

	/**
	 * @param string $testName
	 * @param mixed $result
	 */
    public function storeTestResult($testName, $result)
    {
        update_option("integrity-checker_result_{$testName}", $result, false);
    }

    /**
     * Get test result from WP_Options table
     *
     * @param string $testName
     *
     * @return mixed|void
     */
    public function getTestResult($testName)
    {
        $result = get_option("{$this->slug}_result_$testName", false);
        return $result;
    }

    /**
     * @return object
     */
    private function noState()
    {
        return (object)array(
            'state' => 'never_started',
            'started' => null,
            'finished' => null,
        );
    }

}