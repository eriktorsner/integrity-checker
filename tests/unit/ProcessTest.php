<?php
namespace integrityChecker;

class ProcessTest extends \PHPUnit_Framework_TestCase
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
        $testFactory = new \MockTestFactory();
        $settings = new \MockSettings();
        $state = new \MockState();
        $backgroundProcess = new \MockBackgroundProcess();

        $proc = new Process($testFactory, $settings, $state, $backgroundProcess);
    }

    public function testStatus()
    {
        $testFactory = new \MockTestFactory(array('footest' => 0));
        $settings = new \MockSettings();
        $state = new \MockState();
        $backgroundProcess = new \MockBackgroundProcess();

        // Try an existing test
        $req = new \MockRequest(array('parameters' => array('name' => 'footest')));
        $proc = new Process($testFactory, $settings, $state, $backgroundProcess);
        $ret = $proc->status($req);
        $this->assertTrue(is_object($ret));

        // Try an non-existing test
        $req = new \MockRequest(array('parameters' => array('name' => 'voidtest')));
        $proc = new Process($testFactory, $settings, $state, $backgroundProcess);
        $ret = $proc->status($req);
        $this->assertTrue(is_wp_error($ret));

        // Try w/o a given test name
        $req = new \MockRequest(array());
        $proc = new Process($testFactory, $settings, $state, $backgroundProcess);
        $ret = $proc->status($req);
        $this->assertTrue(is_array($ret));
    }

    public function testUpdate()
    {
        $testFactory = new \MockTestFactory(array('footest' => 0));
        $settings = new \MockSettings();
        $state = new \MockState();
        $backgroundProcess = new \MockBackgroundProcess();

        // Try with a non-existing test name
        $req = new \MockRequest(array('parameters' => array('name' => 'voidtest')));
        $proc = new Process($testFactory, $settings, $state, $backgroundProcess);
        $ret = $proc->update($req);
        $this->assertTrue(is_wp_error($ret));

        // Try with an existing test name, but invalid payload
        $req = new \MockRequest(array(
            'parameters' => array('name' => 'footest'),
            'body' => 'invalidjson',
        ));
        $proc = new Process($testFactory, $settings, $state, $backgroundProcess);
        $ret = $proc->update($req);
        $this->assertTrue(is_wp_error($ret));

        // Try with an existing test name and valid json payload, but not our state param
        $req = new \MockRequest(array(
            'parameters' => array('name' => 'footest'),
            'body' => json_encode((object)array('foobar' => 'hello')),
        ));
        $proc = new Process($testFactory, $settings, $state, $backgroundProcess);
        $ret = $proc->update($req);
        $this->assertTrue(is_wp_error($ret));

        // Try with an existing test name and valid json payload and valid parameter
        $req = new \MockRequest(array(
            'parameters' => array('name' => 'footest'),
            'body' => json_encode((object)array('state' => 'started')),
        ));
        $proc = new Process($testFactory, $settings, $state, $backgroundProcess);
        $ret = $proc->update($req);
        $this->assertTrue(is_object($ret));

        // Try with an existing test name and valid json payload and valid parameter but unknown state
        $req = new \MockRequest(array(
            'parameters' => array('name' => 'footest'),
            'body' => json_encode((object)array('state' => 'unknown')),
        ));
        $proc = new Process($testFactory, $settings, $state, $backgroundProcess);
        $ret = $proc->update($req);
        $this->assertEquals(false, $ret);
    }

    public function testGetTestResults()
    {
        $testFactory = new \MockTestFactory(array('footest' => 0));
        $settings = new \MockSettings();
        $state = new \MockState(false, array('footest' => 'someresult'));
        $backgroundProcess = new \MockBackgroundProcess();

        // Try with an invalid test name
        $req = new \MockRequest(array('parameters' => array('name' => 'voidtest')));
        $proc = new Process($testFactory, $settings, $state, $backgroundProcess);
        $ret = $proc->getTestResults($req);
        $this->assertTrue(is_wp_error($ret));

        // Try with a valid test name
        $req = new \MockRequest(array('parameters' => array('name' => 'footest')));
        $proc = new Process($testFactory, $settings, $state, $backgroundProcess);
        $ret = $proc->getTestResults($req);
        $this->assertEquals('someresult', $ret);

    }
}