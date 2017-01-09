<?php
namespace integrityChecker;

/**
 * Class Process
 * @package integrityChecker
 */
class Process
{
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
        $integrityChecker = integrityChecker::getInstance();
        $tests = $integrityChecker->getTestNames();
        $name = $request->get_param('name');
        if (!is_null($name) && !in_array($name, $tests)) {
            return new \WP_Error('fail', 'Unknown test name', array('status' => 404));
        }

        $state = new State();
        $procStates = array();
        foreach ($tests as $testName) {
            $procStates[$testName] = $state->getTestState($testName);
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
        $integrityChecker = integrityChecker::getInstance();
        $tests = $integrityChecker->getTestNames();

        if (is_null($name) || !in_array($name, $tests)) {
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
                $obj = $this->testFactory($name);
                $obj->start($request);
                return $obj->state();
                break;
        }

        return false;
    }

    /**
     * @param $testName
     *
     * @return Tests\BaseTest
     */
    private function testFactory($testName)
    {
        switch ($testName) {
            case 'checksum':
                return Tests\Checksum::getInstance();
                break;
            case 'permissions':
                return Tests\Permissions::getInstance();
                break;
            case 'settings':
                return Tests\Settings::getInstance();

        }

        return null;
    }


}