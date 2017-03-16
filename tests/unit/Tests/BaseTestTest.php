<?php

namespace integrityChecker;

/**
 * This is the base class of all Tests, most of it gets code coverage
 * from testing the child classes. This file focus on the methods that
 * isn't covered by them
 *
 * Class BaseTestTest
 * @package integrityChecker
 */
class BaseTestTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \WP_Mock::setUp();
    }

    public function tearDown()
    {
        \WP_Mock::tearDown();
    }

    public function testInitEtc()
    {
        \WP_Mock::userFunction('get_transient', array('return' => false,));
        \WP_Mock::userFunction('set_transient', array(
            'args' => array('tt_teststate_abc123', 'somestate'),
            'return' => false,
            'times' => 1,
        ));

        $state = new \MockState(array('' => (object)array(
            'started' => time(),
        )));

        $dummy = new \stdClass();
        $s = new Tests\BaseTest($dummy, $state, $dummy, $dummy);
        $s->setBackgroundProcess(new \MockBackgroundProcess());
        $s->init('abc123');
        $s->transientState = 'somestate';
        $s->serializeState();

        $this->assertEquals('abc', $s->getRestResults('abc'));
    }

    public function testStart()
    {
        $req = new \MockRequest(array('body' => json_encode(array("source" => "manual"))));

        $dummy = new \stdClass();
        $s = new Tests\BaseTest($dummy, new \MockState(), $dummy, $dummy);
        $s->setBackgroundProcess(new \MockBackgroundProcess());
        $s->start($req);
    }

    public function testFinish()
    {
        \WP_Mock::userFunction('delete_transient', array(
            'args' => array('tt_teststate_abc123'),
            'return' => null,
            'times' => 1,
        ));


        $dummy = new \stdClass();
        $state = new \MockState();

        $s = new Tests\BaseTest(new \MockSettings(), $state, $dummy, $dummy);
        $s->setBackgroundProcess(new \MockBackgroundProcess());
        $s->session = 'abc123';
        $s->transientState = array('result' => array('somestate' => 1));
        $s->finish();

        $this->assertEquals(1, count($state->arr));
        $testResult = reset($state->arr);

        $this->assertTrue(isset($testResult['ts']));
        $this->assertTrue(isset($testResult['somestate']));

        $testResult['someothervariable'] = 'abc';
        $s->storeTestResult($testResult);
    }

    public function testInitBackgroundProcess()
    {
        $req = new \MockRequest(array('body' => json_encode(array("source" => "manual"))));
        $state = new \MockState(array(
            'dependency' => (object)array('state' => 'finished', 'finished' => time()),
        ));

        $mockTest = new \MockTest('mock');
        $mockTest->setBackgroundProcess(new \MockBackgroundProcess());
        $mockTest->startWDependency(null, $req, 900);

        $mockTest = new \MockTest('mock', $state);
        $mockTest->setBackgroundProcess(new \MockBackgroundProcess());
        $dependency = new \MockTest('dependency', $state);
        $mockTest->startWDependency($dependency, $req, 900);

        $state = new \MockState(array(
            'dependency' => (object)array('state' => 'started', 'started' => time(), 'session' => '999123'),
        ));
        $mockTest = new \MockTest('mock', $state);
        $mockTest->setBackgroundProcess(new \MockBackgroundProcess());
        $dependency = new \MockTest('dependency', $state);
        $mockTest->startWDependency($dependency, $req, 900);

        $state = new \MockState(array(
            'dependency' => (object)array('state' => 'finished', 'finished' => 999),
        ));
        $mockTest = new \MockTest('mock', $state);
        $mockTest->setBackgroundProcess(new \MockBackgroundProcess());
        $dependency = new \MockTest('dependency', $state);
        $mockTest->startWDependency($dependency, $req, 900);
    }
}
