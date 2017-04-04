<?php
namespace integrityChecker;

/**
 * Class Process
 * @package integrityChecker
 */
class Process
{
    /**
     * @var array
     */
    private $testFactory;

    /**
     * @var State
     */
    private $state;

    /**
     * @var BackgroundProcess
     */
    private $backgroundProcess;

    /**
     * Process constructor.
     *
     * @param Tests\TestFactory $testFactory
     * @param Settings $settings
     * @param State $state
     * @param BackgroundProcess $backgroundProcess
     */
    public function __construct($testFactory, $settings, $state, $backgroundProcess)
    {
        $this->testFactory= $testFactory;
        $this->state = $state;
        $this->backgroundProcess = $backgroundProcess;
    }

    /**
     * Return the current status of all or a single test
     *
     * @param \WP_REST_Request $request
     *
     * @return array|bool|mixed
     */
    public function status($request)
    {
        // Make sure we recognize the testName
        $name = $request->get_param('name');
        if (!is_null($name) && !$this->testFactory->hasTest($name)) {
            return new \WP_Error('fail', 'Unknown test name', array('status' => 404));
        }

        $procStates = array();
        foreach ($this->testFactory->getTestNames() as $testName) {
            $procStates[$testName] = $this->state->getTestState($testName);
        }

        if (!is_null($name)) {
            return $procStates[$name];
        } else {
            return $procStates;
        }
    }

    /**
     * Handle the PUT method that is used to
     * start or abort a process
     *
     * @param \WP_REST_Request $request
     *
     * @return bool|object
     */
    public function update($request)
    {
        $name = $request->get_param('name');
        if (is_null($name) || !$this->testFactory->hasTest($name)) {
            return new \WP_Error('fail', 'Unknown test name', array('status' => 404));
        }

        // We must have a valid JSON payload for an update command
        $payload = json_decode($request->get_body());
        if (!$payload ) {
            return new \WP_Error('fail', 'Invalid payload', array('status' => 400));
        }

        // We really only support a process state to be updated
        if (!isset($payload->state)) {
            return new \WP_Error('fail', 'Invalid parameter', array('status' => 400));
        }

        switch ($payload->state) {
            case 'started':
	            // TODO: Allow restart when current state is older than XX seconds
                $obj = $this->testFactory->getTestObject($name);
                $obj->setBackgroundProcess($this->backgroundProcess);
                $obj->start($request);
                return $obj->state();
                break; // @codeCoverageIgnore
        }

        return false;
    }

    /**
     * Get the latest result of a test
     *
     * @param \WP_REST_Request $request
     *
     * @return mixed|void|\WP_Error
     */
    public function getTestResults($request)
    {
        $name = $request->get_param('name');
        if (is_null($name) || !$this->testFactory->hasTest($name)) {
            return new \WP_Error('fail', 'Unknown test name', array('status' => 404));
        }

        // Get the test result from options
        $result = $this->state->getTestResult($name);

        // The test class might want to add details
        $objTest = $this->testFactory->getTestObject($name);
        $objTest->setBackgroundProcess($this->backgroundProcess);
        $objTest->setBackgroundProcess($this->backgroundProcess);

        return $objTest->getRestResults($result);

    }

    /**
     * Get the latest result of a test
     *
     * @param $name
     * @param $operation
     * @param $data
     *
     * @return mixed|void|\WP_Error
     */
    public function changeTestResults($name, $operation, $data)
    {
        if (is_null($name) || !$this->testFactory->hasTest($name)) {
            return new \WP_Error('fail', 'Unknown test name', array('status' => 404));
        }

        $ret = new \WP_Error('fail', 'Unknown operation name', array('status' => 404));

        $objTest = $this->testFactory->getTestObject($name);
        if (method_exists($objTest, $operation)) {
            $ret = $objTest->$operation($data);
        }

        return $ret;

    }




}