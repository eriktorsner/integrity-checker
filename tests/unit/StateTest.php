<?php
namespace integrityChecker;

class StateTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    public function testConstruct()
    {
        $state = new State('foobar');
    }

    public function testGetTestState()
    {
        $t = time();
        \WP_Mock::userFunction('get_option', array(
            'args' => array('foobar_status_footest', false),
            'return_in_order' => array(false, (object)array(
                'state' => 'finished',
                'started' => $t - 200,
                'finished' => $t - 100,
            )),
            'times' => 2,
        ));

        $state = new State('foobar');
        $testState = $state->getTestState('footest');

        $this->assertEquals('never_started', $testState->state);
        $this->assertEquals(null, $testState->started);
        $this->assertEquals(null, $testState->finished);
        $this->assertEquals('1970-01-01 00:00:00', $testState->startedIso);
        $this->assertEquals('1970-01-01 00:00:00', $testState->finishedIso);

        $testState = $state->getTestState('footest');
        $this->assertEquals('finished', $testState->state);
        $this->assertEquals($t - 200, $testState->started);
        $this->assertEquals($t - 100, $testState->finished);
        $this->assertEquals(date('Y-m-d H:i:s', $t - 200), $testState->startedIso);
        $this->assertEquals(date('Y-m-d H:i:s', $t - 100), $testState->finishedIso);
    }

    public function testUpdateTestState()
    {
        $testState = (object)array(
            'oneparam' => 1,
            'stringparam' => 'string',
        );

        \WP_Mock::userFunction('update_option', array(
            'args' => array('foobar_status_footest', $testState),
            'times' => 1,
        ));

        $state = new State('foobar');
        $state->updateTestState('footest', $testState);
    }

    public function testStoreTestResult()
    {
        $testResult = (object)array(
            'oneparam' => 1,
            'stringparam' => 'string',
        );

        \WP_Mock::userFunction('update_option', array(
            'args' => array('foobar_result_footest', $testResult, false),
            'times' => 1,
        ));

        $state = new State('foobar');
        $state->storeTestResult('footest', $testResult);
    }

    public function testGetTestResult()
    {
        $state = new State('foobar');
        \WP_Mock::userFunction('get_option', array(
            'args' => array('foobar_result_footest', false),
            'times' => 1,
            'return' => 'someresult',
        ));

        $testResult = $state->getTestResult('footest');
        $this->assertEquals('someresult', $testResult);
    }
}

